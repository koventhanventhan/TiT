<?php

namespace Tests\Feature;

use App\Mail\PaymentReceiptMail;
use App\Models\AdvancePaymentRequest;
use App\Models\Institute;
use App\Models\MediumChangeRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminAdvancePaymentsTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute1;
    private Institute $institute2;
    private User $admin1;
    private User $admin2;
    private User $student1;
    private User $student2;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->institute1 = Institute::create([
            'id' => 1,
            'name' => 'Institute 1',
            'slug' => 'inst-1',
            'domain' => 'inst1.local',
            'status' => 'active',
            'index_prefix' => 'IN1',
            'index_seq_start' => 100,
        ]);

        $this->institute2 = Institute::create([
            'id' => 2,
            'name' => 'Institute 2',
            'slug' => 'inst-2',
            'domain' => 'inst2.local',
            'status' => 'active',
            'index_prefix' => 'IN2',
            'index_seq_start' => 100,
        ]);

        $this->admin1 = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute1->id,
            'full_name' => 'Admin One',
            'email' => 'admin1@tit.com',
        ]);

        $this->admin2 = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute2->id,
            'full_name' => 'Admin Two',
            'email' => 'admin2@tit.com',
        ]);

        $this->student1 = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute1->id,
            'name' => 'Student One',
            'full_name' => 'Student One Full',
            'email' => 'student1@tit.com',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 10',
            'medium' => 'english',
        ]);

        $this->student2 = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute2->id,
            'name' => 'Student Two',
            'full_name' => 'Student Two Full',
            'email' => 'student2@tit.com',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 10',
            'medium' => 'english',
        ]);
    }

    /**
     * Requirement 6 (a): other-institute pending request does not crash the page, is not listed, hidden-count info shown
     */
    public function test_other_institute_pending_request_does_not_crash_page_is_hidden_and_info_shown(): void
    {
        $req1 = AdvancePaymentRequest::create([
            'user_id' => $this->student1->id,
            'months_count' => 2,
            'amount' => 5000,
            'status' => 'pending',
        ]);

        $req2 = AdvancePaymentRequest::create([
            'user_id' => $this->student2->id,
            'months_count' => 3,
            'amount' => 7500,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin1, 'web')->get(route('admin.students.advance-payments'));

        $response->assertStatus(200);
        $response->assertSee('Student One Full');
        $response->assertSee(number_format(5000, 2));
        $response->assertDontSee('Student Two Full');
        $response->assertSee('1 request(s) hidden (student not found / other institute)');
    }

    /**
     * Requirement 6 (b): request with a user row missing/other institute: approve -> 404/flash error, status unchanged, no payments
     */
    public function test_request_with_missing_user_or_other_institute_cannot_be_approved(): void
    {
        // Case 1: Other institute request
        $reqOther = AdvancePaymentRequest::create([
            'user_id' => $this->student2->id,
            'months_count' => 2,
            'amount' => 5000,
            'status' => 'pending',
        ]);

        $responseOther = $this->actingAs($this->admin1, 'web')->post(
            route('admin.students.advance-payments.approve', $reqOther->id),
            ['received_by' => 'cash']
        );

        $responseOther->assertStatus(404);
        $this->assertEquals('pending', $reqOther->fresh()->status);
        $this->assertEquals(0, Payment::where('kind', 'advance')->count());

        // Case 2: Missing user (orphan request)
        $orphanReq = AdvancePaymentRequest::create([
            'user_id' => 999999,
            'months_count' => 1,
            'amount' => 2500,
            'status' => 'pending',
        ]);

        $responseOrphan = $this->actingAs($this->admin1, 'web')->post(
            route('admin.students.advance-payments.approve', $orphanReq->id),
            ['received_by' => 'cash']
        );

        $responseOrphan->assertStatus(404);
        $this->assertEquals('pending', $orphanReq->fresh()->status);
        $this->assertEquals(0, Payment::where('kind', 'advance')->count());
    }

    /**
     * Requirement 6 (c): normal request: page 200, approve -> advance payment(s) + exactly one receipt mail
     */
    public function test_normal_request_approve_creates_advance_payments_and_exactly_one_receipt(): void
    {
        Mail::fake();

        $req = AdvancePaymentRequest::create([
            'user_id' => $this->student1->id,
            'months_count' => 2,
            'amount' => 6000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin1, 'web')->get(route('admin.students.advance-payments'));
        $response->assertStatus(200);

        $approveResponse = $this->actingAs($this->admin1, 'web')->post(
            route('admin.students.advance-payments.approve', $req->id),
            ['received_by' => 'bank_transfer']
        );

        $approveResponse->assertRedirect();
        $this->assertEquals('approved', $req->fresh()->status);

        $payments = Payment::where('user_id', $this->student1->id)->where('kind', 'advance')->get();
        $this->assertCount(2, $payments);
        foreach ($payments as $p) {
            $this->assertEquals('offline', $p->payment_method);
            $this->assertEquals('paid', $p->status);
            $this->assertEquals(3000, (float) $p->amount);
            $this->assertNotEmpty($p->receipt_no);
        }

        // Exactly one receipt mail sent for the batch
        Mail::assertSent(PaymentReceiptMail::class, 1);
    }

    /**
     * Requirement 6 (d): reject works
     */
    public function test_reject_advance_payment_works(): void
    {
        $req = AdvancePaymentRequest::create([
            'user_id' => $this->student1->id,
            'months_count' => 1,
            'amount' => 3000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin1, 'web')->post(
            route('admin.students.advance-payments.reject', $req->id)
        );

        $response->assertRedirect();
        $this->assertEquals('rejected', $req->fresh()->status);
    }

    /**
     * Requirement 6 (e): a second institute's admin cannot approve institute 1's request
     */
    public function test_second_institute_admin_cannot_approve_institute_one_request(): void
    {
        $req = AdvancePaymentRequest::create([
            'user_id' => $this->student1->id,
            'months_count' => 1,
            'amount' => 3000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin2, 'web')->post(
            route('admin.students.advance-payments.approve', $req->id),
            ['received_by' => 'cash']
        );

        $response->assertStatus(404);
        $this->assertEquals('pending', $req->fresh()->status);
        $this->assertEquals(0, Payment::where('kind', 'advance')->count());
    }

    /**
     * Requirement 6 (f): medium-changes page with a missing user returns 200
     */
    public function test_medium_changes_page_with_missing_user_returns_200(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'full_name' => 'Super Admin',
            'email' => 'super@tit.com',
        ]);

        $orphanMediumReq = MediumChangeRequest::create([
            'user_id' => 888888,
            'current_medium' => 'tamil',
            'requested_medium' => 'english',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($superAdmin, 'web')->get(route('admin.students.medium-changes'));

        $response->assertStatus(200);
        $response->assertSee('Student not found (#888888)');
        $response->assertSee('Reject');
    }

    /**
     * Advance payments page with missing user as super admin returns 200 and only reject button
     */
    public function test_advance_payments_page_with_missing_user_as_super_admin_returns_200_and_only_reject_button(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'full_name' => 'Super Admin 2',
            'email' => 'super2@tit.com',
        ]);

        $orphanAdvanceReq = AdvancePaymentRequest::create([
            'user_id' => 777777,
            'months_count' => 2,
            'amount' => 6000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($superAdmin, 'web')->get(route('admin.students.advance-payments'));

        $response->assertStatus(200);
        $response->assertSee('Student not found (#777777)');
        $response->assertSee('Reject');
        $response->assertDontSee('Approve (2 Months)');
    }

    /**
     * Test advance-payments:report artisan command
     */
    public function test_advance_payments_report_artisan_command(): void
    {
        AdvancePaymentRequest::create([
            'user_id' => $this->student1->id,
            'months_count' => 2,
            'amount' => 5000,
            'status' => 'pending',
        ]);

        AdvancePaymentRequest::create([
            'user_id' => 999999,
            'months_count' => 1,
            'amount' => 2500,
            'status' => 'pending',
        ]);

        $this->artisan('advance-payments:report', ['--status' => 'pending'])
            ->expectsOutputToContain('Student One')
            ->expectsOutputToContain('Missing User')
            ->expectsOutputToContain('Total Requests: 2')
            ->assertExitCode(0);
    }
}
