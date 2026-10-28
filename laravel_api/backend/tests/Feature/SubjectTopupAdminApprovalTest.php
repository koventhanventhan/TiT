<?php

namespace Tests\Feature;

use App\Mail\PaymentReceiptMail;
use App\Models\Institute;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectTopup;
use App\Models\User;
use App\Notifications\AdminNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubjectTopupAdminApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;
    protected User $admin;
    protected User $student;
    protected Subject $mathSubject;
    protected Subject $scienceSubject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'name' => 'TiT Institute Admin Test',
            'slug' => 'tit-adm-test',
            'tagline' => 'Excellence in Tuition',
            'contact_email' => 'admin@tit.lk',
            'contact_phone' => '0771234567',
            'address' => 'Colombo, Sri Lanka',
            'invoice_prefix' => 'TIT',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
            'email' => 'admin@tit.lk',
        ]);

        $this->student = User::factory()->create([
            'name' => 'teststudent',
            'full_name' => 'Test Student',
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'selected_subjects' => json_encode(['Mathematics', 'Science']),
        ]);

        $this->mathSubject = Subject::create([
            'name' => 'Mathematics',
            'category' => 'grade_10_to_11',
            'medium' => 'both',
            'price' => 1000.00,
            'status' => 'active',
        ]);

        $this->scienceSubject = Subject::create([
            'name' => 'Science',
            'category' => 'grade_10_to_11',
            'medium' => 'both',
            'price' => 1200.00,
            'status' => 'active',
        ]);
    }

    public function test_admin_students_list_shows_topup_tag_and_mark_paid_button()
    {
        $ym = now()->format('Y-m');

        // Student has a paid monthly payment for current month
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1000.00,
            'year_month' => $ym,
            'status' => 'paid',
            'kind' => 'monthly',
            'payment_method' => 'online',
            'paid_at' => now(),
        ]);

        // Student added Science as a top-up
        $topup = SubjectTopup::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200.00,
            'status' => 'pending',
            'payment_method' => 'offline',
        ]);

        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200.00,
            'year_month' => $ym,
            'status' => 'pending',
            'kind' => 'topup',
            'payment_method' => 'offline',
            'meta' => [
                'topup_id' => $topup->id,
                'added_subjects' => ['Science'],
            ],
        ]);

        $res = $this->actingAs($this->admin, 'web')
            ->get(route('admin.students.index', ['pending_payments' => 1]));

        $res->assertStatus(200);
        $res->assertSee('Subject top-up');
        $res->assertSee('Science');
        $res->assertSee('1,200.00');
        $res->assertSee(route('admin.students.subject-topups.mark-paid', $topup->id));
    }

    public function test_mark_paid_via_list_unlocks_subjects_and_issues_receipt()
    {
        Mail::fake();
        $ym = now()->format('Y-m');

        $topup = SubjectTopup::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200.00,
            'status' => 'pending',
            'payment_method' => 'offline',
        ]);

        $payment = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200.00,
            'year_month' => $ym,
            'status' => 'pending',
            'kind' => 'topup',
            'payment_method' => 'offline',
            'meta' => [
                'topup_id' => $topup->id,
                'added_subjects' => ['Science'],
            ],
        ]);
        $topup->update(['payment_id' => $payment->id]);

        $this->assertContains('Science', $this->student->fresh()->lockedSubjects());

        $res = $this->actingAs($this->admin, 'web')
            ->post(route('admin.students.subject-topups.mark-paid', $topup->id));

        $res->assertRedirect();
        $this->assertEquals('paid', $topup->fresh()->status);
        $this->assertEquals('paid', $payment->fresh()->status);
        $this->assertEmpty($this->student->fresh()->lockedSubjects());
        $this->assertContains('Science', $this->student->fresh()->activeSubjects());

        Mail::assertSent(PaymentReceiptMail::class, 1);
    }

    public function test_mark_paid_via_payment_history_row_unlocks_subjects()
    {
        Mail::fake();
        $ym = now()->format('Y-m');

        // Existing paid monthly row
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1000.00,
            'year_month' => $ym,
            'status' => 'paid',
            'kind' => 'monthly',
            'paid_at' => now(),
        ]);

        $topup = SubjectTopup::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200.00,
            'status' => 'pending',
            'payment_method' => 'offline',
        ]);

        $topupPayment = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200.00,
            'year_month' => $ym,
            'status' => 'pending',
            'kind' => 'topup',
            'payment_method' => 'offline',
            'meta' => [
                'topup_id' => $topup->id,
                'added_subjects' => ['Science'],
            ],
        ]);
        $topup->update(['payment_id' => $topupPayment->id]);

        $this->assertContains('Science', $this->student->fresh()->lockedSubjects());

        // Mark paid via the payment row
        $res = $this->actingAs($this->admin, 'web')
            ->post(route('admin.students.payments.mark-paid', [
                'id' => $this->student->id,
                'payment' => $topupPayment->id,
            ]));

        $res->assertRedirect();
        $res->assertSessionHasNoErrors();
        $this->assertEquals('paid', $topupPayment->fresh()->status);
        $this->assertEquals('paid', $topup->fresh()->status);
        $this->assertEmpty($this->student->fresh()->lockedSubjects());
        $this->assertContains('Science', $this->student->fresh()->activeSubjects());

        Mail::assertSent(PaymentReceiptMail::class, 1);
    }

    public function test_revert_payment_relocks_subjects()
    {
        $ym = now()->format('Y-m');

        $topup = SubjectTopup::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200.00,
            'status' => 'paid',
            'payment_method' => 'offline',
            'paid_at' => now(),
        ]);

        $topupPayment = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200.00,
            'year_month' => $ym,
            'status' => 'paid',
            'kind' => 'topup',
            'payment_method' => 'offline',
            'paid_at' => now(),
            'meta' => [
                'topup_id' => $topup->id,
                'added_subjects' => ['Science'],
            ],
        ]);
        $topup->update(['payment_id' => $topupPayment->id]);

        $this->assertEmpty($this->student->fresh()->lockedSubjects());

        $res = $this->actingAs($this->admin, 'web')
            ->post(route('admin.students.payments.revert', [
                'id' => $this->student->id,
                'payment' => $topupPayment->id,
            ]));

        $res->assertRedirect();
        $this->assertEquals('pending', $topupPayment->fresh()->status);
        $this->assertEquals('pending', $topup->fresh()->status);
        $this->assertContains('Science', $this->student->fresh()->lockedSubjects());
    }

    public function test_destroy_payment_cancels_and_leaves_subjects_locked()
    {
        $ym = now()->format('Y-m');

        $topup = SubjectTopup::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200.00,
            'status' => 'pending',
            'payment_method' => 'offline',
        ]);

        $topupPayment = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200.00,
            'year_month' => $ym,
            'status' => 'pending',
            'kind' => 'topup',
            'payment_method' => 'offline',
            'meta' => [
                'topup_id' => $topup->id,
                'added_subjects' => ['Science'],
            ],
        ]);
        $topup->update(['payment_id' => $topupPayment->id]);

        $res = $this->actingAs($this->admin, 'web')
            ->delete(route('admin.students.payments.destroy', [
                'id' => $this->student->id,
                'payment' => $topupPayment->id,
            ]));

        $res->assertRedirect();
        $this->assertDatabaseMissing('payments', ['id' => $topupPayment->id]);
        $this->assertEquals('pending', $topup->fresh()->status);
        $this->assertNull($topup->fresh()->payment_id);
        $this->assertContains('Science', $this->student->fresh()->lockedSubjects());
    }

    public function test_monthly_mark_paid_does_not_cancel_pending_topup_in_same_month()
    {
        $ym = now()->format('Y-m');

        $topup = SubjectTopup::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200.00,
            'status' => 'pending',
            'payment_method' => 'offline',
        ]);

        $topupPayment = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200.00,
            'year_month' => $ym,
            'status' => 'pending',
            'kind' => 'topup',
            'payment_method' => 'offline',
            'meta' => [
                'topup_id' => $topup->id,
                'added_subjects' => ['Science'],
            ],
        ]);
        $topup->update(['payment_id' => $topupPayment->id]);

        // Admin marks monthly paid for the student
        $res = $this->actingAs($this->admin, 'web')
            ->post(route('admin.students.mark-paid', $this->student->id), [
                'year_month' => $ym,
                'amount' => 1000.00,
            ]);

        $res->assertRedirect();
        $this->assertEquals('pending', $topupPayment->fresh()->status, 'Pending topup payment must not be cancelled by monthly mark paid');
        $this->assertEquals('pending', $topup->fresh()->status, 'Pending topup must not be cancelled by monthly mark paid');
    }

    public function test_student_status_latest_monthly_payment_ignores_topup_row()
    {
        $ym = now()->format('Y-m');

        $monthlyPaid = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1000.00,
            'year_month' => $ym,
            'status' => 'paid',
            'kind' => 'monthly',
            'paid_at' => now(),
        ]);

        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200.00,
            'year_month' => $ym,
            'status' => 'pending',
            'kind' => 'topup',
            'payment_method' => 'offline',
        ]);

        $res = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/auth/user');

        $res->assertStatus(200);
        $res->assertJsonPath('user.latest_monthly_payment.id', $monthlyPaid->id);
        $res->assertJsonPath('user.latest_monthly_payment.status', 'paid');
    }

    public function test_next_month_monthly_payment_supersedes_stale_topup()
    {
        $pastYM = now()->subMonth()->format('Y-m');
        $currentYM = now()->format('Y-m');

        $topup = SubjectTopup::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $pastYM,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200.00,
            'status' => 'pending',
            'payment_method' => 'offline',
        ]);

        // Student or Admin pays full monthly fee for current month
        $res = $this->actingAs($this->admin, 'web')
            ->post(route('admin.students.mark-paid', $this->student->id), [
                'year_month' => $currentYM,
                'amount' => 2200.00,
            ]);

        $res->assertRedirect();
        $this->assertEquals('superseded', $topup->fresh()->status);
    }

    public function test_pay_offline_notifies_admin_with_student_grade_subjects_amount_and_link()
    {
        Notification::fake();
        $ym = now()->format('Y-m');

        $topup = SubjectTopup::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200.00,
            'status' => 'pending',
            'payment_method' => null,
        ]);

        $res = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/student/subject-topups/{$topup->id}/pay-offline");

        $res->assertStatus(200);

        Notification::assertSentTo(
            $this->admin,
            AdminNotification::class,
            function (AdminNotification $notification) {
                $data = $notification->toArray($this->admin);
                return str_contains($data['message'], 'Science')
                    && str_contains($data['message'], '1,200.00')
                    && str_contains($data['link'], (string) $this->student->id);
            }
        );
    }

    public function test_edit_topup_amount_allowed_only_while_pending()
    {
        $ym = now()->format('Y-m');

        $topup = SubjectTopup::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200.00,
            'status' => 'pending',
            'payment_method' => 'offline',
        ]);

        $topupPayment = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200.00,
            'year_month' => $ym,
            'status' => 'pending',
            'kind' => 'topup',
            'payment_method' => 'offline',
            'meta' => [
                'topup_id' => $topup->id,
                'added_subjects' => ['Science'],
            ],
        ]);
        $topup->update(['payment_id' => $topupPayment->id]);

        // Edit amount while pending -> success
        $res = $this->actingAs($this->admin, 'web')
            ->put(route('admin.students.payments.update', [
                'id' => $this->student->id,
                'payment' => $topupPayment->id,
            ]), [
                'payment_id' => $topupPayment->id,
                'year_month' => $ym,
                'amount' => 1500.00,
                'status' => 'pending',
                'payment_method' => 'offline',
            ]);

        $res->assertRedirect();
        $this->assertEquals(1500.00, (float) $topupPayment->fresh()->amount);
        $this->assertEquals(1500.00, (float) $topup->fresh()->amount);

        // Now mark paid
        $topupPayment->update(['status' => 'paid', 'paid_at' => now()]);
        $topup->update(['status' => 'paid', 'paid_at' => now()]);

        // Attempting to edit amount while paid -> error
        $res2 = $this->actingAs($this->admin, 'web')
            ->put(route('admin.students.payments.update', [
                'id' => $this->student->id,
                'payment' => $topupPayment->id,
            ]), [
                'payment_id' => $topupPayment->id,
                'year_month' => $ym,
                'amount' => 1800.00,
                'status' => 'paid',
                'payment_method' => 'offline',
            ]);

        $res2->assertRedirect();
        $res2->assertSessionHas('error', 'Top-up payment amount can only be edited while pending.');
        $this->assertEquals(1500.00, (float) $topupPayment->fresh()->amount);
    }
}
