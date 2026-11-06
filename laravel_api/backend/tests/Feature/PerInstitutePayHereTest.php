<?php

namespace Tests\Feature;

use App\Mail\PaymentReceiptMail;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\InstituteIntegration;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectTopup;
use App\Models\User;
use App\Services\PayHereService;
use App\Support\Integrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PerInstitutePayHereTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $instituteA;
    protected Institute $instituteB;
    protected User $studentA;
    protected User $studentB;

    protected string $merchantA = 'MERCHANT_INST_A_101';
    protected string $secretA = 'SECRET_INST_A_ALPHA_1111';

    protected string $merchantB = 'MERCHANT_INST_B_202';
    protected string $secretB = 'SECRET_INST_B_BETA_2222';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();

        // Institute 1 (TiT fallback institute) - configure distinct env credentials to prove A and B never use env
        $instOne = Institute::create([
            'name' => 'Platform Default Institute',
            'slug' => 'platform-default',
            'status' => 'active',
        ]);

        config([
            'integrations.env_fallback_institutes' => [$instOne->id],
            'payhere.merchant_id' => 'ENV_PLATFORM_MID',
            'payhere.merchant_secret' => 'ENV_PLATFORM_SECRET_0000',
            'payhere.currency' => 'LKR',
            'payhere.is_sandbox' => true,
            'payhere.trust_client_callback' => false,
        ]);

        $this->instituteA = Institute::create([
            'name' => 'Institute Alpha',
            'slug' => 'institute-alpha',
            'index_prefix' => 'ALP',
            'status' => 'active',
        ]);
        InstituteDomain::create([
            'institute_id' => $this->instituteA->id,
            'domain' => 'alpha.academy.lk',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $this->instituteB = Institute::create([
            'name' => 'Institute Beta',
            'slug' => 'institute-beta',
            'index_prefix' => 'BET',
            'status' => 'active',
        ]);
        InstituteDomain::create([
            'institute_id' => $this->instituteB->id,
            'domain' => 'beta.academy.lk',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteA->id,
            'provider' => InstituteIntegration::PROVIDER_PAYHERE,
            'credentials' => ['merchant_secret' => $this->secretA],
            'settings' => [
                'merchant_id' => $this->merchantA,
                'is_sandbox' => false,
                'currency' => 'LKR',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteB->id,
            'provider' => InstituteIntegration::PROVIDER_PAYHERE,
            'credentials' => ['merchant_secret' => $this->secretB],
            'settings' => [
                'merchant_id' => $this->merchantB,
                'is_sandbox' => true,
                'currency' => 'LKR',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        Subject::withoutGlobalScopes()->create([
            'name' => 'Physics',
            'category' => 'grade_10_to_11',
            'medium' => 'english',
            'price' => 2000,
            'institute_id' => $this->instituteA->id,
        ]);
        Subject::withoutGlobalScopes()->create([
            'name' => 'Chemistry',
            'category' => 'grade_10_to_11',
            'medium' => 'english',
            'price' => 2400,
            'institute_id' => $this->instituteB->id,
        ]);

        $this->studentA = User::factory()->create([
            'institute_id' => $this->instituteA->id,
            'role' => 'user',
            'full_name' => 'Alpha Student',
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'registration_status' => 'payment_completed',
            'selected_subjects' => ['Physics'],
        ]);

        $this->studentB = User::factory()->create([
            'institute_id' => $this->instituteB->id,
            'role' => 'user',
            'full_name' => 'Beta Student',
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'registration_status' => 'payment_completed',
            'selected_subjects' => ['Chemistry'],
        ]);
    }

    protected function signNotify(
        string $merchantId,
        string $merchantSecret,
        string $orderId,
        float $amount,
        string $currency = 'LKR',
        int $statusCode = 2,
        string $paymentId = 'PH_TX_9001'
    ): array {
        $amountFormatted = number_format($amount, 2, '.', '');
        $secretHash = strtoupper(md5($merchantSecret));
        $md5sig = strtoupper(md5(
            $merchantId .
            $orderId .
            $amountFormatted .
            $currency .
            $statusCode .
            $secretHash
        ));

        return [
            'merchant_id' => $merchantId,
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'payhere_amount' => $amountFormatted,
            'payhere_currency' => $currency,
            'status_code' => $statusCode,
            'md5sig' => $md5sig,
        ];
    }

    public function test_valid_a_signed_notify_for_a_payment_marks_paid_verifies_integration_and_derives_primary_notify_url(): void
    {
        $serviceA = PayHereService::forInstitute($this->instituteA->id);
        $this->assertSame('https://www.payhere.lk/pay/checkout', $serviceA->getCheckoutUrl());

        $paymentA = Payment::withoutGlobalScopes()->create([
            'user_id' => $this->studentA->id,
            'institute_id' => $this->instituteA->id,
            'year_month' => '2026-11',
            'amount' => 2000.00,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_ALPHA_ORDER_001',
            'meta' => ['currency' => 'LKR'],
        ]);

        $checkoutParams = $serviceA->buildCheckoutParams($paymentA, $this->studentA);
        $this->assertSame($this->merchantA, $checkoutParams['merchant_id']);
        $this->assertSame('https://alpha.academy.lk/api/payhere/notify', $checkoutParams['notify_url']);

        // Webhook arrives even without X-Institute-Id (resolved via gateway_ref across global scopes)
        $payload = $this->signNotify($this->merchantA, $this->secretA, 'PH_ALPHA_ORDER_001', 2000.00);
        $response = $this->post('/api/payhere/notify', $payload);

        $response->assertStatus(200);
        $this->assertSame('paid', $paymentA->fresh()->status);
        $this->assertSame('approved', $this->studentA->fresh()->registration_status);
        $this->assertStringStartsWith('ALP/', (string) $this->studentA->fresh()->index_number);

        // Integration row for Institute A transitioned from configured -> verified
        $integrationA = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $this->instituteA->id)
            ->where('provider', 'payhere')
            ->firstOrFail();
        $this->assertSame(InstituteIntegration::STATUS_VERIFIED, $integrationA->status);
        $this->assertNotNull($integrationA->last_verified_at);
    }

    public function test_a_signed_notify_for_b_payment_is_rejected_with_403(): void
    {
        $paymentB = Payment::withoutGlobalScopes()->create([
            'user_id' => $this->studentB->id,
            'institute_id' => $this->instituteB->id,
            'year_month' => '2026-11',
            'amount' => 2400.00,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_BETA_ORDER_001',
            'meta' => ['currency' => 'LKR'],
        ]);

        // Attacker signs B's order with A's merchant_id + A's secret
        $forgedWithA = $this->signNotify($this->merchantA, $this->secretA, 'PH_BETA_ORDER_001', 2400.00);
        $resp1 = $this->post('/api/payhere/notify', $forgedWithA);
        $resp1->assertStatus(403);

        // Attacker claims B's merchant_id but signs with A's secret
        $forgedSecretA = $this->signNotify($this->merchantB, $this->secretA, 'PH_BETA_ORDER_001', 2400.00);
        $resp2 = $this->post('/api/payhere/notify', $forgedSecretA);
        $resp2->assertStatus(403);

        $this->assertSame('pending', $paymentB->fresh()->status);
    }

    public function test_right_secret_but_wrong_merchant_id_is_rejected_with_403(): void
    {
        $paymentB = Payment::withoutGlobalScopes()->create([
            'user_id' => $this->studentB->id,
            'institute_id' => $this->instituteB->id,
            'year_month' => '2026-11',
            'amount' => 2400.00,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_BETA_ORDER_002',
            'meta' => ['currency' => 'LKR'],
        ]);

        // Signed with B's actual secret, but payload carries wrong merchant_id
        $wrongMerchantPayload = $this->signNotify('WRONG_MERCHANT_999', $this->secretB, 'PH_BETA_ORDER_002', 2400.00);
        $response = $this->post('/api/payhere/notify', $wrongMerchantPayload);

        $response->assertStatus(403);
        $this->assertSame('pending', $paymentB->fresh()->status);
    }

    public function test_tampered_amount_or_currency_is_rejected_with_403(): void
    {
        $paymentB = Payment::withoutGlobalScopes()->create([
            'user_id' => $this->studentB->id,
            'institute_id' => $this->instituteB->id,
            'year_month' => '2026-11',
            'amount' => 2400.00,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_BETA_ORDER_003',
            'meta' => ['currency' => 'LKR'],
        ]);

        // Valid signature for 100.00 LKR on a 2400.00 LKR payment
        $tamperedAmount = $this->signNotify($this->merchantB, $this->secretB, 'PH_BETA_ORDER_003', 100.00, 'LKR');
        $this->post('/api/payhere/notify', $tamperedAmount)->assertStatus(403);

        // Valid signature for 2400.00 USD on an LKR payment
        $tamperedCurrency = $this->signNotify($this->merchantB, $this->secretB, 'PH_BETA_ORDER_003', 2400.00, 'USD');
        $this->post('/api/payhere/notify', $tamperedCurrency)->assertStatus(403);

        $this->assertSame('pending', $paymentB->fresh()->status);
    }

    public function test_replayed_notify_is_idempotent_with_no_duplicate_side_effects(): void
    {
        $paymentA = Payment::withoutGlobalScopes()->create([
            'user_id' => $this->studentA->id,
            'institute_id' => $this->instituteA->id,
            'year_month' => '2026-11',
            'amount' => 2000.00,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_ALPHA_IDEMPOTENT_01',
            'meta' => ['currency' => 'LKR'],
        ]);

        $payload = $this->signNotify($this->merchantA, $this->secretA, 'PH_ALPHA_IDEMPOTENT_01', 2000.00);

        $this->post('/api/payhere/notify', $payload)->assertStatus(200);
        Mail::assertSent(PaymentReceiptMail::class, 1);
        $receiptNoFirst = $paymentA->fresh()->receipt_no;

        // Replay exact same webhook twice
        $this->post('/api/payhere/notify', $payload)->assertStatus(200);
        $this->post('/api/payhere/notify', $payload)->assertStatus(200);

        Mail::assertSent(PaymentReceiptMail::class, 1);
        $this->assertSame($receiptNoFirst, $paymentA->fresh()->receipt_no);
    }

    public function test_unknown_order_returns_404(): void
    {
        $payload = $this->signNotify($this->merchantA, $this->secretA, 'PH_DOES_NOT_EXIST_999', 2000.00);
        $this->post('/api/payhere/notify', $payload)->assertStatus(404);
    }

    public function test_institute_without_payhere_exposes_online_payments_disabled_rejects_online_checkout_with_422_and_allows_offline(): void
    {
        // Remove Institute B's PayHere credentials
        InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $this->instituteB->id)
            ->where('provider', InstituteIntegration::PROVIDER_PAYHERE)
            ->delete();

        // 1. /api/settings exposes online_payments_enabled = false for Institute B, true for Institute A
        $settingsB = $this->withHeaders(['X-Institute-Id' => (string) $this->instituteB->id])
            ->getJson('/api/settings');
        $settingsB->assertStatus(200)->assertJsonPath('online_payments_enabled', false);

        $settingsA = $this->withHeaders(['X-Institute-Id' => (string) $this->instituteA->id])
            ->getJson('/api/settings');
        $settingsA->assertStatus(200)->assertJsonPath('online_payments_enabled', true);

        Sanctum::actingAs($this->studentB);

        // 2. Online registration checkout -> 422 online_payments_unavailable
        $step2Online = $this->withHeaders(['X-Institute-Id' => (string) $this->instituteB->id])
            ->postJson('/api/register/step2', [
                'payment_method' => 'online',
                'amount' => 2400,
            ]);
        $step2Online->assertStatus(422)
            ->assertJsonPath('code', 'online_payments_unavailable');

        // 3. Online monthly checkout -> 422 online_payments_unavailable
        $monthlyOnline = $this->withHeaders(['X-Institute-Id' => (string) $this->instituteB->id])
            ->postJson('/api/student/pay-monthly');
        $monthlyOnline->assertStatus(422)
            ->assertJsonPath('code', 'online_payments_unavailable');

        // 4. Online top-up checkout -> 422 online_payments_unavailable
        $topup = SubjectTopup::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteB->id,
            'user_id' => $this->studentB->id,
            'year_month' => now()->format('Y-m'),
            'previous_subjects' => ['Chemistry'],
            'added_subjects' => ['Biology'],
            'new_monthly_fee' => 3600,
            'paid_so_far' => 2400,
            'amount' => 1200,
            'status' => 'pending',
        ]);
        $topupOnline = $this->withHeaders(['X-Institute-Id' => (string) $this->instituteB->id])
            ->postJson("/api/student/subject-topups/{$topup->id}/pay-online");
        $topupOnline->assertStatus(422)
            ->assertJsonPath('code', 'online_payments_unavailable');

        // 5. Offline registration step2 still completes cleanly!
        $this->studentB->update(['registration_status' => 'pending_payment']);
        $step2Offline = $this->withHeaders(['X-Institute-Id' => (string) $this->instituteB->id])
            ->postJson('/api/register/step2', [
                'payment_method' => 'offline',
                'amount' => 2400,
            ]);
        $step2Offline->assertStatus(200)
            ->assertJsonPath('registration_status', 'payment_completed');
    }

    public function test_institute_1_with_only_env_values_behaves_exactly_as_before(): void
    {
        $instOne = Institute::withoutGlobalScopes()->where('slug', 'platform-default')->firstOrFail();

        // Confirm no DB row exists for institute 1
        $this->assertDatabaseMissing('institute_integrations', [
            'institute_id' => $instOne->id,
            'provider' => InstituteIntegration::PROVIDER_PAYHERE,
        ]);

        // /api/settings reports online_payments_enabled = true via env fallback
        $this->withHeaders(['X-Institute-Id' => (string) $instOne->id])
            ->getJson('/api/settings')
            ->assertStatus(200)
            ->assertJsonPath('online_payments_enabled', true);

        $studentOne = User::factory()->create([
            'institute_id' => $instOne->id,
            'role' => 'user',
            'full_name' => 'TiT Legacy Student',
            'registration_status' => 'pending_payment',
        ]);

        $paymentOne = Payment::withoutGlobalScopes()->create([
            'user_id' => $studentOne->id,
            'institute_id' => $instOne->id,
            'year_month' => '2026-11',
            'amount' => 1500.00,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_INST1_ENV_001',
            'meta' => ['currency' => 'LKR'],
        ]);

        $svc = PayHereService::forInstitute($instOne->id);
        $this->assertTrue($svc->isConfigured());
        $this->assertSame('ENV_PLATFORM_MID', $svc->getMerchantId());
        $this->assertSame('https://sandbox.payhere.lk/pay/checkout', $svc->getCheckoutUrl());

        $payload = $this->signNotify('ENV_PLATFORM_MID', 'ENV_PLATFORM_SECRET_0000', 'PH_INST1_ENV_001', 1500.00);
        $this->post('/api/payhere/notify', $payload)->assertStatus(200);
        $this->assertSame('paid', $paymentOne->fresh()->status);
    }

    public function test_trust_client_callback_cannot_be_enabled_in_production(): void
    {
        config(['payhere.trust_client_callback' => true]);
        $this->assertTrue(Integrations::trustPayHereClientCallback());

        $origEnv = $this->app['env'];
        try {
            $this->app['env'] = 'production';
            config(['app.env' => 'production']);
            $this->assertFalse(
                Integrations::trustPayHereClientCallback(),
                'trust_client_callback must always return false in production'
            );
        } finally {
            $this->app['env'] = $origEnv;
            config(['app.env' => $origEnv]);
        }
    }
}
