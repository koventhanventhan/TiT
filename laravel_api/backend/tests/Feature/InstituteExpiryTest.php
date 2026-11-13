<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\InstituteIntegration;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstituteExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $plan;
    protected Institute $institute1;
    protected Institute $institute2;
    protected User $admin1;
    protected User $admin2;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenant.expiry_grace_days' => 7]);

        $this->plan = SubscriptionPlan::create([
            'name' => 'Growth Plan',
            'slug' => 'growth',
            'monthly_price' => 5000,
            'yearly_price' => 50000,
            'max_students' => 100,
            'max_teachers' => 10,
            'features' => ['zoom', 'whatsapp'],
        ]);

        $this->institute1 = Institute::withoutGlobalScopes()->find(1) ?: Institute::create([
            'id' => 1,
            'name' => 'TiT Academy',
            'slug' => 'tit',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'index_prefix' => 'TIT',
            'index_format' => 'grade',
            'expires_at' => now()->subDays(30), // Past expires_at for Institute 1
        ]);

        InstituteDomain::create([
            'institute_id' => 1,
            'domain' => 'tit.test',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $this->institute2 = Institute::create([
            'name' => 'Apex High Academy',
            'slug' => 'apex',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'index_prefix' => 'AHA',
            'index_format' => 'grade',
            'expires_at' => now()->addDays(30),
        ]);

        InstituteDomain::create([
            'institute_id' => $this->institute2->id,
            'domain' => 'apex.test',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $this->admin1 = User::withoutGlobalScopes()->create([
            'name' => 'Admin One',
            'email' => 'admin@tit.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'institute_id' => 1,
        ]);

        $this->admin2 = User::withoutGlobalScopes()->create([
            'name' => 'Admin Two',
            'email' => 'admin@apex.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'institute_id' => $this->institute2->id,
        ]);

        $this->superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@platform.lk',
            'password' => Hash::make('Secret123!'),
            'role' => 'super_admin',
            'institute_id' => 1,
        ]);
    }

    public function test_institute_2_active_returns_200(): void
    {
        $response = $this->get('http://apex.test/');
        $response->assertOk();
    }

    public function test_expires_at_yesterday_returns_200_and_shows_grace_banner_for_admin(): void
    {
        $yesterday = now()->subDay();
        $this->institute2->update(['expires_at' => $yesterday]);

        // Public website still works within grace period
        $response = $this->get('http://apex.test/');
        $response->assertOk();

        // Admin sees the red grace banner
        $adminRes = $this->actingAs($this->admin2, 'web')->get('http://apex.test/admin/dashboard');
        $adminRes->assertOk();
        $adminRes->assertSee('Your subscription expired on');
        $adminRes->assertSee('Access will stop on');
        $adminRes->assertSee('Contact the platform owner to renew.');
    }

    public function test_expires_at_now_minus_eight_days_returns_402_html_for_home_and_402_json_for_api(): void
    {
        $this->institute2->update(['expires_at' => now()->subDays(8)]);

        // 1. Browser request for '/' -> 402 HTML page (assert page text, no JSON)
        $htmlRes = $this->get('http://apex.test/');
        $htmlRes->assertStatus(402);
        $htmlRes->assertHeader('X-Robots-Tag', 'noindex');
        $htmlRes->assertSee('Subscription has ended');
        $this->assertFalse(str_starts_with(trim($htmlRes->getContent()), '{'), 'Expected HTML response, not JSON text');

        // 2. JSON request for '/api/settings' -> 402 JSON with code
        $jsonRes = $this->getJson('http://apex.test/api/settings');
        $jsonRes->assertStatus(402);
        $jsonRes->assertJson([
            'status' => 'expired',
            'code' => 'institute_expired',
        ]);

        // 3. Admin can open /admin/subscription
        $subRes = $this->actingAs($this->admin2, 'web')->get('http://apex.test/admin/subscription');
        $subRes->assertOk();
        $subRes->assertSee('Subscription & Limits');

        // 4. Admin opening /admin/students redirects to /admin/subscription
        $studentsRes = $this->actingAs($this->admin2, 'web')->get('http://apex.test/admin/students');
        $studentsRes->assertRedirect(route('admin.subscription'));
    }

    public function test_suspended_institute_returns_403(): void
    {
        $this->institute2->update(['status' => 'suspended']);

        // HTML request
        $htmlRes = $this->get('http://apex.test/');
        $htmlRes->assertStatus(403);
        $htmlRes->assertHeader('X-Robots-Tag', 'noindex');
        $htmlRes->assertSee('This institute is temporarily unavailable');

        // JSON request
        $jsonRes = $this->getJson('http://apex.test/api/settings');
        $jsonRes->assertStatus(403);
        $jsonRes->assertJson([
            'status' => 'suspended',
            'code' => 'institute_suspended',
        ]);
    }

    public function test_institute_1_with_past_expires_at_and_status_untouched_returns_200(): void
    {
        // Institute 1 has expires_at 30 days in the past
        $this->institute1->update(['expires_at' => now()->subDays(30)]);

        // 1. Public home -> 200
        $homeRes = $this->get('http://tit.test/');
        $homeRes->assertOk();

        // 2. Public API -> 200
        $apiRes = $this->getJson('http://tit.test/api/settings');
        $apiRes->assertOk();

        // 3. Admin dashboard -> 200
        $dashRes = $this->actingAs($this->admin1, 'web')->get('http://tit.test/admin/dashboard');
        $dashRes->assertOk();
    }

    public function test_super_admin_on_blocked_institute_host_still_passes(): void
    {
        // Institute 2 is expired beyond grace
        $this->institute2->update(['expires_at' => now()->subDays(10)]);

        // Super-admin on apex.test passes for all endpoints
        $homeRes = $this->actingAs($this->superAdmin, 'web')->get('http://apex.test/');
        $homeRes->assertOk();

        $apiRes = $this->actingAs($this->superAdmin, 'web')->getJson('http://apex.test/api/settings');
        $apiRes->assertOk();

        $dashRes = $this->actingAs($this->superAdmin, 'web')->get('http://apex.test/admin/dashboard');
        $dashRes->assertOk();
    }

    protected function signPayHereNotify(
        string $merchantId,
        string $merchantSecret,
        string $orderId,
        float $amount,
        string $currency = 'LKR',
        int $statusCode = 2,
        string $paymentId = 'PH_EXP_TX_001'
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

    public function test_payhere_notify_on_expired_beyond_grace_institute_records_payment_while_other_routes_stay_blocked(): void
    {
        $this->institute2->update(['expires_at' => now()->subDays(30)]);

        $merchantId = 'MERCHANT_APEX_TEST';
        $secret = 'SECRET_APEX_TEST_1234';
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->institute2->id,
            'provider' => InstituteIntegration::PROVIDER_PAYHERE,
            'credentials' => ['merchant_secret' => $secret],
            'settings' => [
                'merchant_id' => $merchantId,
                'is_sandbox' => true,
                'currency' => 'LKR',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $student = User::withoutGlobalScopes()->create([
            'name' => 'Apex Student 1',
            'email' => 'apex.s1@apex.test',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'full_name' => 'Apex Student One',
            'institute_id' => $this->institute2->id,
        ]);

        $payment1 = Payment::withoutGlobalScopes()->create([
            'user_id' => $student->id,
            'institute_id' => $this->institute2->id,
            'year_month' => '2026-11',
            'amount' => 2500.00,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_EXPIRED_ORDER_001',
            'meta' => ['currency' => 'LKR'],
        ]);

        // 1. Correctly signed notify -> payment becomes paid, HTTP 200
        $payload1 = $this->signPayHereNotify($merchantId, $secret, 'PH_EXPIRED_ORDER_001', 2500.00);
        $res1 = $this->post('http://apex.test/api/payhere/notify', $payload1);
        $res1->assertOk();
        $this->assertSame('paid', $payment1->fresh()->status);

        // 2. Wrong signature -> 403 and unpaid
        $payment2 = Payment::withoutGlobalScopes()->create([
            'user_id' => $student->id,
            'institute_id' => $this->institute2->id,
            'year_month' => '2026-11',
            'amount' => 3000.00,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_EXPIRED_ORDER_002',
            'meta' => ['currency' => 'LKR'],
        ]);
        $badPayload = $this->signPayHereNotify($merchantId, 'WRONG_SECRET', 'PH_EXPIRED_ORDER_002', 3000.00);
        $res2 = $this->post('http://apex.test/api/payhere/notify', $badPayload);
        $res2->assertStatus(403);
        $this->assertSame('pending', $payment2->fresh()->status);

        // 3. Any other API route on that host still 402
        $settingsRes = $this->getJson('http://apex.test/api/settings');
        $settingsRes->assertStatus(402);
        $settingsRes->assertJson(['code' => 'institute_expired']);

        $materialsRes = $this->getJson('http://apex.test/api/learning-materials');
        $materialsRes->assertStatus(402);
        $materialsRes->assertJson(['code' => 'institute_expired']);
    }

    public function test_payhere_notify_on_suspended_institute_records_payment_while_other_routes_stay_blocked(): void
    {
        $this->institute2->update([
            'status' => 'suspended',
            'expires_at' => now()->addDays(30),
        ]);

        $merchantId = 'MERCHANT_APEX_TEST';
        $secret = 'SECRET_APEX_TEST_1234';
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->institute2->id,
            'provider' => InstituteIntegration::PROVIDER_PAYHERE,
            'credentials' => ['merchant_secret' => $secret],
            'settings' => [
                'merchant_id' => $merchantId,
                'is_sandbox' => true,
                'currency' => 'LKR',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $student = User::withoutGlobalScopes()->create([
            'name' => 'Apex Student 2',
            'email' => 'apex.s2@apex.test',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'full_name' => 'Apex Student Two',
            'institute_id' => $this->institute2->id,
        ]);

        $payment1 = Payment::withoutGlobalScopes()->create([
            'user_id' => $student->id,
            'institute_id' => $this->institute2->id,
            'year_month' => '2026-11',
            'amount' => 1800.00,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_SUSP_ORDER_001',
            'meta' => ['currency' => 'LKR'],
        ]);

        // 1. Correctly signed notify -> payment becomes paid, HTTP 200
        $payload1 = $this->signPayHereNotify($merchantId, $secret, 'PH_SUSP_ORDER_001', 1800.00);
        $res1 = $this->post('http://apex.test/api/payhere/notify', $payload1);
        $res1->assertOk();
        $this->assertSame('paid', $payment1->fresh()->status);

        // 2. Wrong signature -> 403 and unpaid
        $payment2 = Payment::withoutGlobalScopes()->create([
            'user_id' => $student->id,
            'institute_id' => $this->institute2->id,
            'year_month' => '2026-11',
            'amount' => 2200.00,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_SUSP_ORDER_002',
            'meta' => ['currency' => 'LKR'],
        ]);
        $badPayload = $this->signPayHereNotify($merchantId, 'WRONG_SECRET', 'PH_SUSP_ORDER_002', 2200.00);
        $res2 = $this->post('http://apex.test/api/payhere/notify', $badPayload);
        $res2->assertStatus(403);
        $this->assertSame('pending', $payment2->fresh()->status);

        // 3. Other API route on that host still 403
        $settingsRes = $this->getJson('http://apex.test/api/settings');
        $settingsRes->assertStatus(403);
        $settingsRes->assertJson(['code' => 'institute_suspended']);

        $materialsRes = $this->getJson('http://apex.test/api/learning-materials');
        $materialsRes->assertStatus(403);
        $materialsRes->assertJson(['code' => 'institute_suspended']);
    }
}

