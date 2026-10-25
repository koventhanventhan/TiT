<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Mail\PaymentReceiptMail;
use App\Models\Assignment;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\MediumChangeRequest;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PaymentReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class OfflineMonthlyPaymentTest extends TestCase
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
            'slug' => 'tit',
            'status' => 'active',
            'index_prefix' => 'TIT',
            'index_format' => 'grade',
            'index_seq_start' => 1,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
            'name' => 'Admin User',
            'full_name' => 'Administrator',
            'email' => 'admin@tit.com',
        ]);

        Subject::firstOrCreate(
            ['name' => 'Mathematics', 'category' => 'grade_6_to_9'],
            ['price' => 1500, 'medium' => 'both', 'institute_id' => $this->institute->id]
        );
        Subject::firstOrCreate(
            ['name' => 'Science', 'category' => 'grade_6_to_9'],
            ['price' => 1500, 'medium' => 'both', 'institute_id' => $this->institute->id]
        );
        Subject::firstOrCreate(
            ['name' => 'Tamil', 'category' => 'grade_6_to_9'],
            ['price' => 1200, 'medium' => 'both', 'institute_id' => $this->institute->id]
        );

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Koventhan',
            'full_name' => 'Koventhan Venthan',
            'email' => 'koventhan@student.com',
            'phone_number' => '0771234567',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 8',
            'medium' => 'tamil',
            'selected_subjects' => json_encode(['Mathematics', 'Science']),
        ]);
    }

    /**
     * Requirement 1: Student receives payment_request_received notification with email fallback
     * (and never receives payment_reminder upon requesting offline payment).
     */
    public function test_student_offline_monthly_payment_sends_payment_request_received_notification(): void
    {
        Mail::fake();

        Sanctum::actingAs($this->student);

        $response = $this->postJson('/api/student/pay-monthly-offline');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'payment' => ['id', 'status', 'amount', 'payment_method', 'year_month', 'created_at'],
            ]);

        $monthName = now()->format('F Y');

        // Check that NotificationMail for payment_request_received was sent
        Mail::assertSent(NotificationMail::class, function ($mail) use ($monthName) {
            $isCorrectTemplate = ($mail->type === 'payment_request_received');
            $html = $mail->render();
            $cleanText = html_entity_decode(strip_tags($html));
            $hasMessage = str_contains($cleanText, "We received your offline payment request for {$monthName}");
            $hasAccessNotice = str_contains($cleanText, "access opens as soon as the admin confirms");
            return $isCorrectTemplate && $hasMessage && $hasAccessNotice;
        });

        // Ensure payment_reminder was NOT sent
        Mail::assertNotSent(NotificationMail::class, function ($mail) {
            return $mail->type === 'payment_reminder';
        });
    }

    /**
     * Requirement 2: Admin is alerted on offline monthly payment, and deduplication
     * reuses the pending row and suppresses a second alert within 10 minutes.
     */
    public function test_admin_notified_and_offline_request_deduplicated_within_10_minutes(): void
    {
        Sanctum::actingAs($this->student);

        // Spy on NotificationService
        $notifier = Mockery::spy(NotificationService::class);
        $this->app->instance(NotificationService::class, $notifier);

        // First submission
        $res1 = $this->postJson('/api/student/pay-monthly-offline');
        $res1->assertStatus(200);
        $paymentId1 = $res1->json('payment.id');

        $currentMonth = now()->format('Y-m');
        $monthName = now()->format('F Y');

        // Verify admin notification was called
        $notifier->shouldHaveReceived('notifyAdmin')
            ->with(
                'admin_alert',
                'tit_admin_approved',
                Mockery::any(),
                Mockery::on(function ($args) use ($monthName) {
                    return isset($args['student_name']) &&
                        $args['student_name'] === 'Koventhan Venthan' &&
                        $args['grade'] === 'Grade 8' &&
                        $args['medium'] === 'tamil' &&
                        $args['month'] === $monthName &&
                        str_contains($args['link'], (string) $this->student->id);
                })
            )
            ->once();

        // Second submission within 10 minutes
        $res2 = $this->postJson('/api/student/pay-monthly-offline');
        $res2->assertStatus(200);
        $paymentId2 = $res2->json('payment.id');

        // Same row reused
        $this->assertEquals($paymentId1, $paymentId2);
        $this->assertEquals(1, Payment::where('user_id', $this->student->id)->where('year_month', $currentMonth)->count());

        // notifyAdmin must NOT have been called a second time
        $notifier->shouldHaveReceived('notifyAdmin')->once();
    }

    /**
     * Requirement 3: Admin dashboard and students list show Pending payments badge and filter
     * with student age and one-click "Mark Paid".
     */
    public function test_admin_dashboard_and_students_list_pending_payment_badge_and_filter(): void
    {
        $currentMonth = now()->format('Y-m');

        // Create a pending offline payment for student
        $payment = Payment::create([
            'user_id' => $this->student->id,
            'amount' => 3000,
            'status' => 'pending',
            'payment_method' => 'offline',
            'year_month' => $currentMonth,
            'gateway_ref' => 'OFFLINE_' . uniqid(),
            'institute_id' => $this->institute->id,
            'created_at' => now()->subMinutes(15),
        ]);

        // Second student with no pending payment
        $student2 = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'PaidStudent',
            'full_name' => 'Paid Student',
            'email' => 'paid@student.com',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 8',
        ]);

        // Visit admin dashboard
        $dashRes = $this->actingAs($this->admin, 'web')->get(route('admin.dashboard'));
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Pending Payments');

        // Visit admin students list with filter ?pending_payments=1
        $listRes = $this->actingAs($this->admin, 'web')->get(route('admin.students.index', ['pending_payments' => 1]));
        $listRes->assertStatus(200);

        // Student 1 (pending) is visible with age and Mark Paid form
        $listRes->assertSee('Koventhan Venthan');
        $listRes->assertSee('Pending (');
        $listRes->assertSee(route('admin.students.mark-paid', $this->student->id));

        // Student 2 (not pending) is not visible in the pending list
        $listRes->assertDontSee('Paid Student');
    }

    /**
     * Requirement 4: Confirmed student with PENDING current-month payment gets 403 needs_payment,
     * and after admin Mark Paid gets 200 on /api/student/zoom-classes, materials, assignments,
     * and receipt mail is queued.
     */
    public function test_confirmed_student_with_pending_payment_gets_403_and_after_mark_paid_gets_200(): void
    {
        Mail::fake();

        $currentMonth = now()->format('Y-m');

        // Seed a learning material and assignment
        LearningMaterial::create([
            'title' => 'Grade 8 Mathematics Notes',
            'grade' => 'Grade 8',
            'medium' => 'tamil',
            'type' => 'note',
            'institute_id' => $this->institute->id,
        ]);

        Assignment::create([
            'title' => 'Grade 8 Math Homework',
            'grade' => 'Grade 8',
            'teacher_id' => $this->admin->id,
            'due_date' => now()->addDays(7),
            'institute_id' => $this->institute->id,
        ]);

        // 1. Student has pending payment for current month
        Payment::create([
            'user_id' => $this->student->id,
            'amount' => 3000,
            'status' => 'pending',
            'payment_method' => 'offline',
            'year_month' => $currentMonth,
            'gateway_ref' => 'OFFLINE_' . uniqid(),
            'institute_id' => $this->institute->id,
        ]);

        Sanctum::actingAs($this->student);

        // Expect 403 needs_payment on restricted routes
        $this->getJson('/api/student/zoom-classes')
            ->assertStatus(403)
            ->assertJson([
                'needs_payment' => true,
                'redirect' => '/student/payment',
            ]);

        $this->getJson('/api/student/materials')
            ->assertStatus(403)
            ->assertJson([
                'needs_payment' => true,
            ]);

        $this->getJson('/api/student/assignments')
            ->assertStatus(403)
            ->assertJson([
                'needs_payment' => true,
            ]);

        // 2. Admin Marks Paid
        $this->partialMock(PaymentReceiptService::class, function ($mock) {
            $mock->makePartial();
            $mock->shouldReceive('pdf')->andReturn('fake-pdf-content');
        });

        $markPaidRes = $this->actingAs($this->admin, 'web')->post(
            route('admin.students.mark-paid', $this->student->id),
            ['year_month' => $currentMonth]
        );
        $markPaidRes->assertSessionHas('success');

        // Verify payment is now paid in database
        $this->assertDatabaseHas('payments', [
            'user_id' => $this->student->id,
            'year_month' => $currentMonth,
            'status' => 'paid',
        ]);

        // 3. Student re-tests restricted routes and receives 200
        Sanctum::actingAs($this->student->fresh());

        $this->getJson('/api/student/zoom-classes')->assertStatus(200);
        $this->getJson('/api/student/materials')->assertStatus(200);
        $this->getJson('/api/student/assignments')->assertStatus(200);

        // Verify receipt email was queued/sent
        Mail::assertSent(PaymentReceiptMail::class, function ($mail) {
            return $mail->student->id === $this->student->id;
        });
    }

    /**
     * Requirement 5: Status API returns pending and cancelled payment records with created_at and amount.
     */
    public function test_payment_status_api_returns_pending_and_cancelled_states(): void
    {
        $currentMonth = now()->format('Y-m');

        // Pending offline payment
        $payment = Payment::create([
            'user_id' => $this->student->id,
            'amount' => 3000,
            'status' => 'pending',
            'payment_method' => 'offline',
            'year_month' => $currentMonth,
            'gateway_ref' => 'OFFLINE_TEST',
            'institute_id' => $this->institute->id,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->student);

        $resPending = $this->getJson('/api/student/payment-status');
        $resPending->assertStatus(200)
            ->assertJson([
                'is_paid' => false,
                'payment' => [
                    'id' => $payment->id,
                    'status' => 'pending',
                    'payment_method' => 'offline',
                ],
            ]);
        $this->assertNotNull($resPending->json('payment.created_at'));

        // Admin cancels / rejects payment
        $payment->update(['status' => 'cancelled']);

        $resCancelled = $this->getJson('/api/student/payment-status');
        $resCancelled->assertStatus(200)
            ->assertJson([
                'is_paid' => false,
                'payment' => [
                    'id' => $payment->id,
                    'status' => 'cancelled',
                ],
            ]);
    }

    /**
     * Requirement 6: Changing subjects after paying current month does NOT create a new payment
     * row and does NOT unlock or lock access. Medium change creates MediumChangeRequest.
     */
    public function test_changing_subjects_after_paying_does_not_create_new_payment_row_and_does_not_lock_or_unlock(): void
    {
        $currentMonth = now()->format('Y-m');

        // Student has already paid for current month
        Payment::create([
            'user_id' => $this->student->id,
            'amount' => 3000,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => $currentMonth,
            'gateway_ref' => 'PH_PAID_ALREADY',
            'institute_id' => $this->institute->id,
        ]);

        $initialPaymentCount = Payment::where('user_id', $this->student->id)->count();
        $this->assertEquals(1, $initialPaymentCount);

        Sanctum::actingAs($this->student);

        // Change subjects to Mathematics + Science + Tamil, and request English medium
        $response = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science', 'Tamil'],
            'medium' => 'english',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_paid' => true,
                'mediumChangeRequested' => true,
            ]);

        // 1. Payment rows count is unchanged
        $this->assertEquals($initialPaymentCount, Payment::where('user_id', $this->student->id)->count());

        // 2. The paid payment for current month is untouched
        $payment = Payment::where('user_id', $this->student->id)->where('year_month', $currentMonth)->first();
        $this->assertEquals('paid', $payment->status);

        // 3. Student access is not locked
        $this->getJson('/api/student/zoom-classes')->assertStatus(200);

        // 4. MediumChangeRequest was created while current medium remains tamil
        $this->assertDatabaseHas('medium_change_requests', [
            'user_id' => $this->student->id,
            'current_medium' => 'tamil',
            'requested_medium' => 'english',
            'status' => 'pending',
        ]);
        $this->assertEquals('tamil', $this->student->fresh()->medium);
    }
}
