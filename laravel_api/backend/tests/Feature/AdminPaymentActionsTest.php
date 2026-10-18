<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentActionsTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $admin;
    private User $student;
    private User $student2;

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

        $this->student2 = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Other Student',
            'full_name' => 'Other Student Full',
            'email' => 'otherstudent@tit.com',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
        ]);
    }

    public function test_non_admin_cannot_update_delete_or_mark_paid(): void
    {
        $payment = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'payment_method' => 'offline',
            'institute_id' => $this->institute->id,
        ]);

        $nonAdmin = User::factory()->create(['role' => 'user', 'institute_id' => $this->institute->id]);

        $resUpdate = $this->actingAs($nonAdmin, 'web')->put(
            route('admin.students.payments.update', ['id' => $this->student->id, 'payment' => $payment->id]),
            ['year_month' => '2026-10', 'amount' => 4000, 'status' => 'paid']
        );
        $resUpdate->assertRedirect(route('admin.login'));

        $resDelete = $this->actingAs($nonAdmin, 'web')->delete(
            route('admin.students.payments.destroy', ['id' => $this->student->id, 'payment' => $payment->id])
        );
        $resDelete->assertRedirect(route('admin.login'));

        $resMarkPaid = $this->actingAs($nonAdmin, 'web')->post(
            route('admin.students.payments.mark-paid', ['id' => $this->student->id, 'payment' => $payment->id])
        );
        $resMarkPaid->assertRedirect(route('admin.login'));

        $resRevert = $this->actingAs($nonAdmin, 'web')->post(
            route('admin.students.payments.revert', ['id' => $this->student->id, 'payment' => $payment->id])
        );
        $resRevert->assertRedirect(route('admin.login'));
    }

    public function test_update_changes_month_amount_status_method_paid_date_and_clears_paid_date_when_not_paid(): void
    {
        $payment = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-09',
            'amount' => 3000,
            'status' => 'pending',
            'payment_method' => 'offline',
            'institute_id' => $this->institute->id,
        ]);

        // Update to paid with date
        $res = $this->actingAs($this->admin, 'web')->put(
            route('admin.students.payments.update', ['id' => $this->student->id, 'payment' => $payment->id]),
            [
                'year_month' => '2026-10',
                'amount' => 3500,
                'status' => 'paid',
                'payment_method' => 'bank_transfer',
                'paid_date' => '2026-10-18',
            ]
        );
        $res->assertRedirect();
        $res->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals('2026-10', $payment->year_month);
        $this->assertEquals(3500, (float) $payment->amount);
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('bank_transfer', $payment->payment_method);
        $this->assertEquals('2026-10-18', $payment->paid_at->format('Y-m-d'));

        // Update to pending -> paid_date should be cleared (null)
        $res2 = $this->actingAs($this->admin, 'web')->put(
            route('admin.students.payments.update', ['id' => $this->student->id, 'payment' => $payment->id]),
            [
                'year_month' => '2026-10',
                'amount' => 3500,
                'status' => 'pending',
                'payment_method' => 'offline',
            ]
        );
        $res2->assertRedirect();
        $payment->refresh();
        $this->assertEquals('pending', $payment->status);
        $this->assertNull($payment->paid_at);
    }

    public function test_update_validates_bad_month_negative_amount_and_bad_status(): void
    {
        $payment = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        $res = $this->actingAs($this->admin, 'web')->put(
            route('admin.students.payments.update', ['id' => $this->student->id, 'payment' => $payment->id]),
            [
                'year_month' => 'invalid-month',
                'amount' => -100,
                'status' => 'unknown_status',
            ]
        );
        $res->assertSessionHasErrors(['year_month', 'amount', 'status']);
        $payment->refresh();
        $this->assertEquals('2026-10', $payment->year_month);
        $this->assertEquals(3500, (float) $payment->amount);
    }

    public function test_cannot_create_second_paid_row_for_same_month_via_edit(): void
    {
        // Row 1: already paid for 2026-10
        Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'paid',
            'paid_at' => '2026-10-01',
            'institute_id' => $this->institute->id,
        ]);

        // Row 2: pending for 2026-09
        $pending = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-09',
            'amount' => 3000,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        // Try to change row 2 to 2026-10 with status 'paid'
        $res = $this->actingAs($this->admin, 'web')->put(
            route('admin.students.payments.update', ['id' => $this->student->id, 'payment' => $pending->id]),
            [
                'year_month' => '2026-10',
                'amount' => 3500,
                'status' => 'paid',
            ]
        );
        $res->assertSessionHasErrors('year_month');

        $pending->refresh();
        $this->assertEquals('2026-09', $pending->year_month);
        $this->assertEquals('pending', $pending->status);
    }

    public function test_changing_row_month_onto_month_with_pending_row_cancels_older_pending_row(): void
    {
        // Existing pending for 2026-10
        $existingPending = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        // Another pending for 2026-09
        $rowToMove = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-09',
            'amount' => 3000,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        // Move rowToMove to 2026-10
        $res = $this->actingAs($this->admin, 'web')->put(
            route('admin.students.payments.update', ['id' => $this->student->id, 'payment' => $rowToMove->id]),
            [
                'year_month' => '2026-10',
                'amount' => 3200,
                'status' => 'paid',
            ]
        );
        $res->assertRedirect();

        $existingPending->refresh();
        $this->assertEquals('cancelled', $existingPending->status);

        $rowToMove->refresh();
        $this->assertEquals('2026-10', $rowToMove->year_month);
        $this->assertEquals('paid', $rowToMove->status);
    }

    public function test_turning_pending_row_into_paid_via_edit_reactivates_deactivated_student(): void
    {
        $this->student->update(['deactivated_at' => now()]);
        $this->assertNotNull($this->student->fresh()->deactivated_at);

        $payment = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        $res = $this->actingAs($this->admin, 'web')->put(
            route('admin.students.payments.update', ['id' => $this->student->id, 'payment' => $payment->id]),
            [
                'year_month' => '2026-10',
                'amount' => 3500,
                'status' => 'paid',
            ]
        );
        $res->assertRedirect();
        $this->assertNull($this->student->fresh()->deactivated_at);
    }

    public function test_mark_paid_on_pending_or_cancelled_row_updates_to_paid_and_fails_if_already_paid(): void
    {
        $pending = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'institute_id' => $this->institute->id,
        ]);

        $countBefore = Payment::where('user_id', $this->student->id)->count();

        $res = $this->actingAs($this->admin, 'web')->post(
            route('admin.students.payments.mark-paid', ['id' => $this->student->id, 'payment' => $pending->id]),
            ['paid_date' => '2026-10-18']
        );
        $res->assertRedirect();
        $res->assertSessionHas('success');

        $this->assertEquals($countBefore, Payment::where('user_id', $this->student->id)->count());
        $pending->refresh();
        $this->assertEquals('paid', $pending->status);
        $this->assertEquals('2026-10-18', $pending->paid_at->format('Y-m-d'));

        // Another payment for same month cannot be marked as paid
        $another = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'cancelled',
            'institute_id' => $this->institute->id,
        ]);

        $res2 = $this->actingAs($this->admin, 'web')->post(
            route('admin.students.payments.mark-paid', ['id' => $this->student->id, 'payment' => $another->id])
        );
        $res2->assertRedirect();
        $res2->assertSessionHas('error');
        $another->refresh();
        $this->assertEquals('cancelled', $another->status);
    }

    public function test_delete_removes_exact_row_logs_activity_and_protects_other_students(): void
    {
        $payment1 = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'paid',
            'institute_id' => $this->institute->id,
        ]);

        $payment2 = Payment::create([
            'user_id' => $this->student2->id,
            'year_month' => '2026-10',
            'amount' => 5000,
            'status' => 'paid',
            'institute_id' => $this->institute->id,
        ]);

        // Attempting to delete student2's payment using student1's URL gives 404
        $res404 = $this->actingAs($this->admin, 'web')->delete(
            route('admin.students.payments.destroy', ['id' => $this->student->id, 'payment' => $payment2->id])
        );
        $res404->assertNotFound();
        $this->assertDatabaseHas('payments', ['id' => $payment2->id]);

        // Deleting student1's payment succeeds
        $res = $this->actingAs($this->admin, 'web')->delete(
            route('admin.students.payments.destroy', ['id' => $this->student->id, 'payment' => $payment1->id])
        );
        $res->assertRedirect();
        $res->assertSessionHas('success');

        $this->assertDatabaseMissing('payments', ['id' => $payment1->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment2->id]);

        // Activity log contains snapshot
        $log = ActivityLog::where('action', 'payment_deleted')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->student->id, $log->metadata['student_id']);
        $this->assertEquals(3500, (float) $log->metadata['payment']['amount']);
    }

    public function test_edit_page_html_contains_buttons_and_modal_markup(): void
    {
        $payment = Payment::create([
            'user_id' => $this->student->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'paid',
            'paid_at' => '2026-10-18',
            'institute_id' => $this->institute->id,
        ]);

        $res = $this->actingAs($this->admin, 'web')->get(
            route('admin.students.edit', $this->student->id)
        );

        $res->assertOk();
        $res->assertSee('Actions');
        $res->assertSee(route('admin.students.payments.update', ['id' => $this->student->id, 'payment' => $payment->id]));
        $res->assertSee(route('admin.students.payments.destroy', ['id' => $this->student->id, 'payment' => $payment->id]));
        $res->assertSee('editPaymentModal_' . $payment->id);
        $res->assertSee('deletePaymentModal_' . $payment->id);
        $res->assertSee('revertPaymentModal_' . $payment->id);
    }
}
