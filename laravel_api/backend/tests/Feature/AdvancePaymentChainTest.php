<?php

namespace Tests\Feature;

use App\Models\AdvancePaymentRequest;
use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PaymentReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdvancePaymentChainTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $student;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit-institute',
            'domain' => 'localhost',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
            'email' => 'admin@tit.lk',
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Advance Student',
            'full_name' => 'Advance Student Full',
            'email' => 'advance_student@tit.lk',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 11',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Mathematics', 'Science']),
        ]);
    }

    public function test_full_advance_payment_chain_from_request_to_approval_and_paid_until()
    {
        Mail::fake();

        // 1. Initially student pays current month
        $currentYm = now()->format('Y-m');
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $currentYm,
            'amount' => 2000,
            'status' => 'paid',
            'payment_method' => 'card',
            'paid_at' => now(),
        ]);

        // 2. Student requests 3 advance months
        Sanctum::actingAs($this->student);
        $reqResponse = $this->postJson('/api/student/advance-payment-offline', [
            'months' => 3,
        ]);

        $reqResponse->assertStatus(200)
            ->assertJson([
                'months' => 3,
                'message' => 'Advance payment requested successfully. Admin will confirm shortly.',
            ]);

        $advanceReq = AdvancePaymentRequest::where('user_id', $this->student->id)
            ->where('status', 'pending')
            ->first();

        $this->assertNotNull($advanceReq);
        $this->assertEquals(3, $advanceReq->months_count);

        // 3. Admin approves advance payment
        $approveResponse = $this->actingAs($this->admin, 'web')->post('/admin/students-requests/advance-payments/' . $advanceReq->id . '/approve');
        $approveResponse->assertRedirect();

        $advanceReq->refresh();
        $this->assertEquals('approved', $advanceReq->status);

        // 4. Verify 3 future months of payments are created and marked paid
        $expectedFirstAdvance = now()->startOfMonth()->addMonth();
        for ($i = 0; $i < 3; $i++) {
            $ym = $expectedFirstAdvance->copy()->addMonths($i)->format('Y-m');
            $p = Payment::where('user_id', $this->student->id)
                ->where('year_month', $ym)
                ->where('status', 'paid')
                ->first();

            $this->assertNotNull($p, "Payment for $ym should exist and be paid");
            $this->assertEquals('offline', $p->payment_method);
            $this->assertNotEmpty($p->receipt_no, "Receipt number should be assigned to $ym payment");
        }

        // 5. Verify student payment status reports paid_until correctly
        Sanctum::actingAs($this->student);
        $statusResponse = $this->getJson('/api/student/payment-status');
        $statusResponse->assertStatus(200);

        $expectedLastMonth = $expectedFirstAdvance->copy()->addMonths(2)->format('F Y');
        $this->assertEquals($expectedLastMonth, $statusResponse->json('paid_until'));
    }

    public function test_advance_payment_validation_rejects_invalid_months()
    {
        Sanctum::actingAs($this->student);

        // Months < 1
        $res0 = $this->postJson('/api/student/advance-payment-offline', ['months' => 0]);
        $res0->assertStatus(422);

        // Months > 12
        $res13 = $this->postJson('/api/student/advance-payment-offline', ['months' => 13]);
        $res13->assertStatus(422);
    }
}
