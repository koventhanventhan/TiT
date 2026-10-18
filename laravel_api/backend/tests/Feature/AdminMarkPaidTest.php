<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMarkPaidTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $admin;
    private User $student;

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
            'full_name' => 'Admin User',
            'email' => 'admin@tit.com',
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Test Student',
            'full_name' => 'Test Student Full',
            'email' => 'student@tit.com',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Mathematics', 'Science']),
        ]);
    }

    public function test_pending_row_exists_mark_paid_updates_to_single_paid_row(): void
    {
        $pending = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'payment_method' => 'offline',
            'gateway_ref' => 'REF_123',
            'institute_id' => $this->institute->id,
        ]);

        $response = $this->actingAs($this->admin, 'web')->post(route('admin.students.mark-paid', $this->student->id), [
            'year_month' => '2026-10-16',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $rows = Payment::where('user_id', $this->student->id)->where('year_month', '2026-10')->get();
        $this->assertCount(1, $rows);

        $row = $rows->first();
        $this->assertEquals($pending->id, $row->id);
        $this->assertEquals('paid', $row->status);
        $this->assertEquals('2026-10-16', $row->paid_at->format('Y-m-d'));
        $this->assertEquals(3500, (float) $row->amount);
        $this->assertEquals('REF_123', $row->gateway_ref);
        $this->assertEquals('offline', $row->payment_method);
    }

    public function test_no_row_exists_creates_one_paid_row(): void
    {
        $response = $this->actingAs($this->admin, 'web')->post(route('admin.students.mark-paid', $this->student->id), [
            'year_month' => '2026-10',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $rows = Payment::where('user_id', $this->student->id)->where('year_month', '2026-10')->get();
        $this->assertCount(1, $rows);
        $this->assertEquals('paid', $rows->first()->status);
    }

    public function test_paid_row_exists_flashes_error_and_does_not_create_duplicate(): void
    {
        Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'paid',
            'paid_at' => '2026-10-01',
            'institute_id' => $this->institute->id,
        ]);

        $response = $this->actingAs($this->admin, 'web')->post(route('admin.students.mark-paid', $this->student->id), [
            'year_month' => '2026-10',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $rows = Payment::where('user_id', $this->student->id)->where('year_month', '2026-10')->get();
        $this->assertCount(1, $rows);
    }

    public function test_two_pending_rows_exist_dirty_data_one_paid_the_other_cancelled(): void
    {
        $olderPending = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3000,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        $newestPending = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        $response = $this->actingAs($this->admin, 'web')->post(route('admin.students.mark-paid', $this->student->id), [
            'year_month' => '2026-10-16',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $rows = Payment::where('user_id', $this->student->id)->where('year_month', '2026-10')->get();
        $this->assertCount(2, $rows, 'Total rows should be unchanged');

        $paidRow = $rows->where('status', 'paid')->first();
        $cancelledRow = $rows->where('status', 'cancelled')->first();

        $this->assertNotNull($paidRow);
        $this->assertNotNull($cancelledRow);
        $this->assertEquals($newestPending->id, $paidRow->id, 'The newest pending row should be marked paid');
        $this->assertEquals($olderPending->id, $cancelledRow->id, 'The older pending row should be cancelled');
    }

    public function test_double_submit_call_twice_leaves_one_paid_row(): void
    {
        Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        $response1 = $this->actingAs($this->admin, 'web')->post(route('admin.students.mark-paid', $this->student->id), [
            'year_month' => '2026-10-16',
        ]);
        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        $response2 = $this->actingAs($this->admin, 'web')->post(route('admin.students.mark-paid', $this->student->id), [
            'year_month' => '2026-10-16',
        ]);
        $response2->assertRedirect();
        $response2->assertSessionHas('error');

        $paidRows = Payment::where('user_id', $this->student->id)
            ->where('year_month', '2026-10')
            ->where('status', 'paid')
            ->get();
        $this->assertCount(1, $paidRows);
    }

    public function test_admin_amount_override_is_respected(): void
    {
        Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        $response = $this->actingAs($this->admin, 'web')->post(route('admin.students.mark-paid', $this->student->id), [
            'year_month' => '2026-10-16',
            'amount' => 5000,
        ]);

        $response->assertRedirect();

        $row = Payment::where('user_id', $this->student->id)->where('year_month', '2026-10')->first();
        $this->assertEquals(5000, (float) $row->amount);
        $this->assertEquals('paid', $row->status);
    }

    public function test_deactivated_student_gets_reactivated(): void
    {
        $this->student->update(['deactivated_at' => now()->subDays(5)]);
        $this->assertNotNull($this->student->fresh()->deactivated_at);

        $response = $this->actingAs($this->admin, 'web')->post(route('admin.students.mark-paid', $this->student->id), [
            'year_month' => '2026-10',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Payment marked for 2026-10 and student account reactivated.');
        $this->assertNull($this->student->fresh()->deactivated_at);
    }

    public function test_migration_cleans_up_dirty_data_and_is_idempotent(): void
    {
        // Student A: paid + pending duplicate
        $paidA = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'paid',
            'paid_at' => '2026-10-10',
            'institute_id' => $this->institute->id,
        ]);
        $pendingA = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        // Student B: two pending rows
        $studentB = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'full_name' => 'Student B',
        ]);
        $pendingB1 = Payment::create([
            'user_id' => $studentB->id,
            'year_month' => '2026-10',
            'amount' => 2000,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);
        $pendingB2 = Payment::create([
            'user_id' => $studentB->id,
            'year_month' => '2026-10',
            'amount' => 2000,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        $migration = require database_path('migrations/2026_10_16_000000_cleanup_duplicate_pending_payments.php');
        $migration->up();

        // Student A: pending became cancelled, paid untouched
        $this->assertEquals('paid', $paidA->fresh()->status);
        $this->assertEquals('cancelled', $pendingA->fresh()->status);

        // Student B: older cancelled, newest pending remains pending
        $this->assertEquals('cancelled', $pendingB1->fresh()->status);
        $this->assertEquals('pending', $pendingB2->fresh()->status);

        // Run second time (idempotent)
        $migration->up();

        $this->assertEquals('paid', $paidA->fresh()->status);
        $this->assertEquals('cancelled', $pendingA->fresh()->status);
        $this->assertEquals('cancelled', $pendingB1->fresh()->status);
        $this->assertEquals('pending', $pendingB2->fresh()->status);
    }

    public function test_registration_controller_step2_online_path_cancels_older_pending_row(): void
    {
        $regStudent = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Reg Student',
            'full_name' => 'Reg Student Full',
            'email' => 'regstudent@tit.com',
            'registration_status' => 'pending',
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Mathematics']),
        ]);

        $fakePayHere = $this->createMock(\App\Services\PayHereService::class);
        $fakePayHere->method('isConfigured')->willReturn(true);
        $fakePayHere->method('buildCheckoutParams')->willReturn(['order_id' => 'TEST']);
        $fakePayHere->method('getCheckoutUrl')->willReturn('https://sandbox.payhere.lk/pay/checkout');
        $this->app->instance(\App\Services\PayHereService::class, $fakePayHere);

        \Laravel\Sanctum\Sanctum::actingAs($regStudent);

        $res1 = $this->postJson('/api/register/step2', [
            'payment_method' => 'online',
            'amount' => 1500,
        ]);
        $res1->assertStatus(200);

        $res2 = $this->postJson('/api/register/step2', [
            'payment_method' => 'online',
            'amount' => 1500,
        ]);
        $res2->assertStatus(200);

        $yearMonth = now()->format('Y-m');
        $pendingCount = Payment::where('user_id', $regStudent->id)
            ->where('year_month', $yearMonth)
            ->where('status', 'pending')
            ->count();
        $cancelledCount = Payment::where('user_id', $regStudent->id)
            ->where('year_month', $yearMonth)
            ->where('status', 'cancelled')
            ->count();

        $this->assertEquals(1, $pendingCount, 'Only one pending payment should remain');
        $this->assertEquals(1, $cancelledCount, 'Older pending payment should be cancelled');
    }
}
