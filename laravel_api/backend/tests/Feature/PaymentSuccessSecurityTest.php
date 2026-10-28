<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayHereService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentSuccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();

        $this->institute = Institute::create([
            'name' => 'Test Institute',
            'slug' => 'test-inst',
            'is_active' => true,
        ]);

        config(['payhere.merchant_id' => '123456']);
        config(['payhere.merchant_secret' => 'SECRET123']);
        config(['payhere.trust_client_callback' => false]);
    }

    public function test_other_user_order_returns_403()
    {
        $studentA = User::factory()->create([
            'role' => 'user',
            'full_name' => 'Student A',
            'institute_id' => $this->institute->id,
            'registration_status' => 'approved',
        ]);

        $studentB = User::factory()->create([
            'role' => 'user',
            'full_name' => 'Student B',
            'institute_id' => $this->institute->id,
            'registration_status' => 'pending_payment',
        ]);

        $paymentB = Payment::create([
            'user_id' => $studentB->id,
            'amount' => 2500,
            'payment_method' => 'online',
            'status' => 'pending',
            'kind' => 'monthly',
            'year_month' => '2026-10',
            'gateway_ref' => 'ORDER_STUDENT_B_1001',
        ]);

        $response = $this->actingAs($studentA, 'sanctum')->postJson('/api/register/payment-success', [
            'order_id' => 'ORDER_STUDENT_B_1001',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('pending', $paymentB->fresh()->status);
        $this->assertEquals('pending_payment', $studentB->fresh()->registration_status);
    }

    public function test_pending_own_order_returns_202_and_stays_pending()
    {
        $studentA = User::factory()->create([
            'role' => 'user',
            'full_name' => 'Student A',
            'institute_id' => $this->institute->id,
            'registration_status' => 'pending_payment',
        ]);

        $paymentA = Payment::create([
            'user_id' => $studentA->id,
            'amount' => 3000,
            'payment_method' => 'online',
            'status' => 'pending',
            'kind' => 'admission',
            'year_month' => '2026-10',
            'gateway_ref' => 'ORDER_STUDENT_A_2001',
        ]);

        $response = $this->actingAs($studentA, 'sanctum')->postJson('/api/register/payment-success', [
            'order_id' => 'ORDER_STUDENT_A_2001',
        ]);

        $response->assertStatus(202);
        $response->assertJson([
            'status' => 'pending',
            'message' => 'Waiting for payment confirmation',
        ]);

        $this->assertEquals('pending', $paymentA->fresh()->status);
        $this->assertEquals('pending_payment', $studentA->fresh()->registration_status);
        Mail::assertNothingSent();
    }

    protected function buildSignedPayload(string $orderId, float $amount, int $statusCode, string $paymentId = 'PAY_TEST_001'): array
    {
        $amountFormatted = number_format($amount, 2, '.', '');
        $secretHash = strtoupper(md5('SECRET123'));
        $md5sig = strtoupper(md5(
            '123456' .
            $orderId .
            $amountFormatted .
            'LKR' .
            $statusCode .
            $secretHash
        ));

        return [
            'merchant_id' => '123456',
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'payhere_amount' => $amountFormatted,
            'payhere_currency' => 'LKR',
            'status_code' => $statusCode,
            'md5sig' => $md5sig,
        ];
    }

    public function test_after_signed_notify_payment_success_returns_200_paid_and_exactly_one_receipt()
    {
        $studentA = User::factory()->create([
            'role' => 'user',
            'full_name' => 'Student A',
            'institute_id' => $this->institute->id,
            'registration_status' => 'pending_payment',
        ]);

        $paymentA = Payment::create([
            'user_id' => $studentA->id,
            'amount' => 3000,
            'payment_method' => 'online',
            'status' => 'pending',
            'kind' => 'admission',
            'year_month' => '2026-10',
            'gateway_ref' => 'ORDER_STUDENT_A_3001',
        ]);

        // Simulate PayHere signed notify
        $notifyData = $this->buildSignedPayload('ORDER_STUDENT_A_3001', 3000.0, 2);

        $notifyResponse = $this->post('/api/payhere/notify', $notifyData);
        $notifyResponse->assertStatus(200);

        $this->assertEquals('paid', $paymentA->fresh()->status);
        $this->assertEquals('approved', $studentA->fresh()->registration_status);
        Mail::assertSent(\App\Mail\PaymentReceiptMail::class, 1);

        // Now client calls paymentSuccess
        $response = $this->actingAs($studentA, 'sanctum')->postJson('/api/register/payment-success', [
            'order_id' => 'ORDER_STUDENT_A_3001',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'paid',
            'message' => 'Payment already confirmed.',
            'registration_status' => 'approved',
        ]);

        // Still exactly 1 receipt mail
        Mail::assertSent(\App\Mail\PaymentReceiptMail::class, 1);
    }

    public function test_trust_flag_true_restores_legacy_behaviour()
    {
        config(['payhere.trust_client_callback' => true]);

        $studentA = User::factory()->create([
            'role' => 'user',
            'full_name' => 'Student A',
            'institute_id' => $this->institute->id,
            'registration_status' => 'pending_payment',
        ]);

        $paymentA = Payment::create([
            'user_id' => $studentA->id,
            'amount' => 2500,
            'payment_method' => 'online',
            'status' => 'pending',
            'kind' => 'admission',
            'year_month' => '2026-10',
            'gateway_ref' => 'ORDER_STUDENT_A_4001',
        ]);

        $response = $this->actingAs($studentA, 'sanctum')->postJson('/api/register/payment-success', [
            'order_id' => 'ORDER_STUDENT_A_4001',
            'payment_id' => 'PAY_DEV_TEST',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'paid',
            'registration_status' => 'approved',
        ]);

        $this->assertEquals('paid', $paymentA->fresh()->status);
        $this->assertEquals('approved', $studentA->fresh()->registration_status);
    }

    public function test_family_parent_can_access_child_order()
    {
        $parent = User::factory()->create([
            'role' => 'user',
            'full_name' => 'Parent User',
            'institute_id' => $this->institute->id,
        ]);

        $child = User::factory()->create([
            'role' => 'user',
            'full_name' => 'Child Student',
            'parent_id' => $parent->id,
            'institute_id' => $this->institute->id,
            'registration_status' => 'pending_payment',
        ]);

        $paymentChild = Payment::create([
            'user_id' => $child->id,
            'amount' => 2000,
            'payment_method' => 'online',
            'status' => 'pending',
            'kind' => 'monthly',
            'year_month' => '2026-10',
            'gateway_ref' => 'ORDER_CHILD_5001',
        ]);

        $response = $this->actingAs($parent, 'sanctum')->postJson('/api/register/payment-success', [
            'order_id' => 'ORDER_CHILD_5001',
        ]);

        // Returns 202 pending instead of 403 forbidden
        $response->assertStatus(202);
    }
}
