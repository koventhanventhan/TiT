<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectTopup;
use App\Models\User;
use App\Services\PayHereService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubjectTopupPayHereTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;
    protected User $student;
    protected User $admin;
    protected string $merchantId = 'TEST_MERCHANT_123';
    protected string $merchantSecret = 'TEST_SECRET_456';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payhere.merchant_id' => $this->merchantId,
            'payhere.merchant_secret' => $this->merchantSecret,
            'payhere.currency' => 'LKR',
        ]);

        $this->institute = Institute::create([
            'name' => 'TiT PayHere Test Institute',
            'slug' => 'tit-payhere-test',
            'code' => 'TIT',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@tit.local',
        ]);

        Subject::create(['name' => 'Mathematics', 'category' => 'grade_10_to_11', 'medium' => 'english', 'price' => 1500, 'institute_id' => $this->institute->id]);
        Subject::create(['name' => 'Science', 'category' => 'grade_10_to_11', 'medium' => 'english', 'price' => 1200, 'institute_id' => $this->institute->id]);

        $this->student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'current_grade' => '10',
            'medium' => 'english',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'index_number' => 'TIT/10/001',
            'selected_subjects' => json_encode(['Mathematics']),
        ]);
    }

    protected function buildSignedPayload(string $orderId, float $amount, int $statusCode, string $paymentId = 'PAY_TEST_001'): array
    {
        $amountFormatted = number_format($amount, 2, '.', '');
        $secretHash = strtoupper(md5($this->merchantSecret));
        $md5sig = strtoupper(md5(
            $this->merchantId .
            $orderId .
            $amountFormatted .
            'LKR' .
            $statusCode .
            $secretHash
        ));

        return [
            'merchant_id' => $this->merchantId,
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'payhere_amount' => $amountFormatted,
            'payhere_currency' => 'LKR',
            'status_code' => $statusCode,
            'md5sig' => $md5sig,
        ];
    }

    /**
     * Signed notify for a top-up order (TOPUP_<id>_<ts>) ->
     * subjects unlocked, exactly one receipt mail, topup paid;
     * second identical notify -> no extra mail;
     * failed notify -> topup pending, subjects still locked.
     */
    public function test_signed_payhere_notify_unlocks_topup_idempotently(): void
    {
        Mail::fake();

        $ym = now()->format('Y-m');

        // Paid monthly payment for Mathematics
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1500,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->student);

        // Add Science -> creates pending top-up
        $updateResp = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);
        $updateResp->assertStatus(200);

        $topup = SubjectTopup::where('user_id', $this->student->id)->where('status', 'pending')->firstOrFail();

        // Student initiates online payment
        $payOnlineResp = $this->postJson("/api/student/subject-topups/{$topup->id}/pay-online");
        $payOnlineResp->assertStatus(200);
        $orderId = $payOnlineResp->json('order_id');
        $this->assertNotEmpty($orderId);
        $this->assertStringStartsWith('TOPUP_', $orderId);

        // Record initial student metadata that must NOT be overwritten
        $origRegStatus = $this->student->fresh()->registration_status;
        $origConfirmedAt = $this->student->fresh()->admin_confirmed_at;
        $origIndex = $this->student->fresh()->index_number;

        // 1. PayHere sends signed SUCCESS webhook (status_code = 2)
        $notifyPayload = $this->buildSignedPayload($orderId, 1200.00, 2);
        $notifyResp = $this->post(route('payhere.notify'), $notifyPayload);
        $notifyResp->assertStatus(200);

        // Topup must be paid
        $topup->refresh();
        $this->assertEquals('paid', $topup->status, 'SubjectTopup status must become paid after valid notify');

        // Subjects must be unlocked
        $this->assertEquals([], $this->student->fresh()->lockedSubjects(), 'Locked subjects must become empty after top-up paid');
        $this->assertEquals(['Mathematics', 'Science'], $this->student->fresh()->activeSubjects());

        // Registration details must NOT be modified
        $freshStudent = $this->student->fresh();
        $this->assertEquals($origRegStatus, $freshStudent->registration_status);
        $this->assertEquals($origConfirmedAt->toDateTimeString(), $freshStudent->admin_confirmed_at->toDateTimeString());
        $this->assertEquals($origIndex, $freshStudent->index_number);

        // Payment row updated to paid with kind topup
        $payment = Payment::where('gateway_ref', $orderId)->firstOrFail();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('topup', $payment->kind);

        // Exactly 1 receipt email sent
        Mail::assertSent(\App\Mail\PaymentReceiptMail::class, 1);
        $mailsBeforeRetry = count(Mail::sent(\App\Mail\PaymentReceiptMail::class));

        // 2. IDEMPOTENCY: PayHere retries the identical notify
        $retryResp = $this->post(route('payhere.notify'), $notifyPayload);
        $retryResp->assertStatus(200);

        // Still exactly 1 receipt email sent (no duplicate email)
        Mail::assertSent(\App\Mail\PaymentReceiptMail::class, 1);
        $this->assertEquals($mailsBeforeRetry, count(Mail::sent(\App\Mail\PaymentReceiptMail::class)));
    }

    public function test_failed_payhere_notify_keeps_topup_pending_and_subjects_locked(): void
    {
        Mail::fake();

        $ym = now()->format('Y-m');

        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1500,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->student);

        $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);

        $topup = SubjectTopup::where('user_id', $this->student->id)->where('status', 'pending')->firstOrFail();

        $payOnlineResp = $this->postJson("/api/student/subject-topups/{$topup->id}/pay-online");
        $orderId = $payOnlineResp->json('order_id');

        // PayHere sends signed FAILED webhook (status_code = -2)
        $notifyPayload = $this->buildSignedPayload($orderId, 1200.00, -2);
        $notifyResp = $this->post(route('payhere.notify'), $notifyPayload);
        $notifyResp->assertStatus(200);

        // Payment status is failed
        $payment = Payment::where('gateway_ref', $orderId)->firstOrFail();
        $this->assertEquals('failed', $payment->status);

        // Topup MUST stay pending so student can retry
        $topup->refresh();
        $this->assertEquals('pending', $topup->status, 'Topup must stay pending after failed payment');

        // Subjects remain locked
        $this->assertEquals(['Science'], $this->student->fresh()->lockedSubjects());

        // No receipt email
        Mail::assertNothingSent();
    }
}
