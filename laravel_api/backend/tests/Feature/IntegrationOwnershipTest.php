<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Models\PlatformGeminiUsage;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\Integrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrationOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $starterPlan;
    protected SubscriptionPlan $proPlan;
    protected SubscriptionPlan $noAiPlan;
    protected Institute $instituteA;
    protected Institute $instituteB;
    protected User $superAdmin;
    protected User $adminA;
    protected User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('super-admin'));

        $this->starterPlan = SubscriptionPlan::create([
            'name' => 'Starter Plan',
            'slug' => 'starter',
            'monthly_price' => 1000,
            'yearly_price' => 10000,
            'features' => ['ai_assistant', 'online_payments'],
        ]);

        $this->proPlan = SubscriptionPlan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro',
            'monthly_price' => 5000,
            'yearly_price' => 50000,
            'features' => ['ai_assistant', 'online_payments', 'whatsapp'],
        ]);

        $this->noAiPlan = SubscriptionPlan::create([
            'name' => 'Basic Plan Without AI',
            'slug' => 'basic',
            'monthly_price' => 500,
            'yearly_price' => 5000,
            'features' => ['online_payments'],
        ]);

        $this->instituteA = Institute::create([
            'name' => 'Alpha Institute',
            'slug' => 'alpha-inst',
            'status' => 'active',
            'subscription_plan_id' => $this->starterPlan->id,
        ]);

        $this->instituteB = Institute::create([
            'name' => 'Beta Academy',
            'slug' => 'beta-inst',
            'status' => 'active',
            'subscription_plan_id' => $this->proPlan->id,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Platform Super Admin',
            'email' => 'super@platform.lk',
            'role' => 'super_admin',
            'institute_id' => null,
        ]);

        $this->adminA = User::factory()->create([
            'name' => 'Admin Alpha',
            'email' => 'admin@alphainst.lk',
            'role' => 'admin',
            'institute_id' => $this->instituteA->id,
        ]);

        $this->adminB = User::factory()->create([
            'name' => 'Admin Beta',
            'email' => 'admin@betainst.lk',
            'role' => 'admin',
            'institute_id' => $this->instituteB->id,
        ]);

        config([
            'services.gemini.api_key' => 'AIzaSyPLATFORM_DEFAULT_GEMINI_KEY_999',
            'services.recaptcha.site_key' => '6Ld_PLATFORM_RECAPTCHA_SITE_KEY',
            'services.recaptcha.secret_key' => '6Ld_PLATFORM_RECAPTCHA_SECRET_KEY',
            'services.google.client_id' => 'platform-google-client.apps.googleusercontent.com',
            'services.google.client_secret' => 'GOCSPX-PLATFORM_GOOGLE_SECRET',
            'integrations.gemini.platform_daily_quota' => 100,
            'integrations.gemini.plan_daily_quotas' => [
                'starter' => 10,
                'pro' => 50,
            ],
        ]);
    }

    public function test_institute_admin_cannot_update_recaptcha_or_google_on_admin_routes(): void
    {
        // 1. POST to recaptcha update route returns 403 for institute admin
        $responseRecaptcha = $this->actingAs($this->adminA)
            ->post(route('admin.settings.ai-security.recaptcha.update'), [
                'site_key' => '6Ld_MALICIOUS_SITE_KEY',
                'secret_key' => '6Ld_MALICIOUS_SECRET_KEY',
                'score_threshold' => 0.5,
            ]);
        $responseRecaptcha->assertStatus(403);

        // 2. POST to google update route returns 403 for institute admin
        $responseGoogle = $this->actingAs($this->adminA)
            ->post(route('admin.settings.ai-security.google.update'), [
                'client_id' => 'malicious-client-id.apps.googleusercontent.com',
                'client_secret' => 'GOCSPX-MALICIOUS_SECRET',
            ]);
        $responseGoogle->assertStatus(403);

        // Verify DB unchanged
        $this->assertDatabaseMissing('institute_integrations', [
            'institute_id' => $this->instituteA->id,
            'provider' => InstituteIntegration::PROVIDER_RECAPTCHA,
        ]);
        $this->assertDatabaseMissing('institute_integrations', [
            'institute_id' => $this->instituteA->id,
            'provider' => InstituteIntegration::PROVIDER_GOOGLE,
        ]);
    }

    public function test_ai_security_blade_page_hides_secrets_and_shows_platform_managed_status(): void
    {
        // Configure reCAPTCHA & Google via DB
        InstituteIntegration::create([
            'institute_id' => $this->instituteA->id,
            'provider' => InstituteIntegration::PROVIDER_RECAPTCHA,
            'credentials' => ['secret_key' => '6Ld_RAW_SECRET_RECAPTCHA_A_12345'],
            'settings' => ['site_key' => '6Ld_RAW_SITE_KEY_A', 'score_threshold' => 0.5],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        InstituteIntegration::create([
            'institute_id' => $this->instituteA->id,
            'provider' => InstituteIntegration::PROVIDER_GOOGLE,
            'credentials' => ['client_secret' => 'GOCSPX-RAW_SECRET_GOOGLE_A_67890'],
            'settings' => ['client_id' => 'alpha-client-id.apps.googleusercontent.com'],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.settings.ai-security'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Must show platform status line
        $response->assertSee('Protection by the platform:');
        $response->assertSee('Active');
        $response->assertSee('Platform default');

        // MUST NOT contain raw secrets
        $this->assertStringNotContainsString('6Ld_RAW_SECRET_RECAPTCHA_A_12345', $content);
        $this->assertStringNotContainsString('GOCSPX-RAW_SECRET_GOOGLE_A_67890', $content);

        // MUST NOT contain input form elements for recaptcha or google
        $this->assertStringNotContainsString('name="site_key"', $content);
        $this->assertStringNotContainsString('name="secret_key"', $content);
        $this->assertStringNotContainsString('name="client_secret"', $content);
    }

    public function test_admin_of_institute_b_cannot_see_or_modify_institute_a_integrations(): void
    {
        // Admin B attempts to post to update Gemini for Institute A - resolution strictly scopes to admin's institute
        $response = $this->actingAs($this->adminB)->post(route('admin.settings.ai-security.gemini.update'), [
            'gemini_mode' => 'custom',
            'api_key' => 'AIzaSyCUSTOM_KEY_B_99999',
            'daily_cap' => 300,
        ]);
        $response->assertRedirect(route('admin.settings.ai-security'));

        // Row should exist ONLY for institute B, NOT institute A
        $this->assertDatabaseHas('institute_integrations', [
            'institute_id' => $this->instituteB->id,
            'provider' => InstituteIntegration::PROVIDER_GEMINI,
        ]);
        $this->assertDatabaseMissing('institute_integrations', [
            'institute_id' => $this->instituteA->id,
            'provider' => InstituteIntegration::PROVIDER_GEMINI,
        ]);
    }

    public function test_gemini_platform_resolution_order_and_quota_enforcement(): void
    {
        // 1. Institute on Starter plan (quota = 10 / day) with no custom key -> resolves to platform key
        $resolverA = Integrations::for($this->instituteA->id);
        $credsA = $resolverA->gemini();
        $this->assertNotNull($credsA);
        $this->assertSame('env', $credsA->source);
        $this->assertSame('AIzaSyPLATFORM_DEFAULT_GEMINI_KEY_999', $credsA->apiKey());

        // 2. Consume up to quota (10 requests)
        for ($i = 0; $i < 10; $i++) {
            PlatformGeminiUsage::incrementToday($this->instituteA->id);
        }
        $this->assertSame(10, PlatformGeminiUsage::getTodayUsage($this->instituteA->id));

        // Quota is now exceeded for Institute A
        $this->assertTrue($resolverA->isPlatformGeminiQuotaExceeded());
        $this->assertNull($resolverA->gemini());

        // 3. Institute B on Pro plan has custom key configured
        InstituteIntegration::create([
            'institute_id' => $this->instituteB->id,
            'provider' => InstituteIntegration::PROVIDER_GEMINI,
            'credentials' => ['api_key' => 'AIzaSyPRO_OWN_KEY_77777'],
            'settings' => ['gemini_mode' => 'custom', 'daily_cap' => 500],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $resolverB = Integrations::for($this->instituteB->id);
        $credsB = $resolverB->gemini();
        $this->assertNotNull($credsB);
        $this->assertSame('db', $credsB->source);
        $this->assertSame('AIzaSyPRO_OWN_KEY_77777', $credsB->apiKey());

        // Pro with own key is NOT restricted by platform quota
        $this->assertFalse($resolverB->isPlatformGeminiQuotaExceeded());

        // 4. Institute on plan WITHOUT ai_assistant -> resolves to null
        $instNoAi = Institute::create([
            'name' => 'No AI School',
            'slug' => 'no-ai-school',
            'status' => 'active',
            'subscription_plan_id' => $this->noAiPlan->id,
        ]);
        $resolverNoAi = Integrations::for($instNoAi->id);
        $this->assertNull($resolverNoAi->gemini());
    }

    public function test_assistant_chat_returns_429_friendly_message_when_platform_quota_hit(): void
    {
        // Consume quota for Institute A
        for ($i = 0; $i < 10; $i++) {
            PlatformGeminiUsage::incrementToday($this->instituteA->id);
        }

        $response = $this->actingAs($this->adminA)->postJson('/api/assistant/chat', [
            'question' => 'Hello assistant, explain photosynthesis',
            'tier' => 'study',
        ]);

        $response->assertStatus(429);
        $response->assertJsonStructure(['message', 'code']);
        $this->assertSame('platform_quota_exceeded', $response->json('code'));
        $this->assertStringContainsString('daily platform AI assistant quota', $response->json('message'));
    }

    public function test_super_admin_manages_platform_integrations_and_activity_log_contains_no_secrets(): void
    {
        $this->actingAs($this->superAdmin, 'super_admin');

        // EditInstitute afterSave logic simulation
        $secretRecaptcha = '6Ld_SUPER_SECRET_RECAPTCHA_98765';
        $secretGoogle = 'GOCSPX-SUPER_SECRET_GOOGLE_54321';

        $livewire = \Livewire\Livewire::test(
            \App\Filament\SuperAdmin\Resources\InstituteResource\Pages\EditInstitute::class,
            ['record' => $this->instituteA->id]
        );

        $livewire->fillForm([
            'recaptcha_site_key' => '6Ld_PLATFORM_INST_A_SITE_KEY',
            'recaptcha_secret_key' => $secretRecaptcha,
            'google_client_id' => 'inst-a-google.apps.googleusercontent.com',
            'google_client_secret' => $secretGoogle,
        ]);

        $livewire->call('save');
        $livewire->assertHasNoFormErrors();

        // Verify DB saved
        $recaptchaRow = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $this->instituteA->id)
            ->where('provider', InstituteIntegration::PROVIDER_RECAPTCHA)
            ->first();
        $this->assertNotNull($recaptchaRow);
        $this->assertSame('6Ld_PLATFORM_INST_A_SITE_KEY', $recaptchaRow->settings['site_key']);
        $this->assertSame($secretRecaptcha, $recaptchaRow->credentials['secret_key']);

        // Check ActivityLog: MUST NOT contain raw secrets
        $logs = ActivityLog::where('institute_id', $this->instituteA->id)
            ->where('action', 'platform_integration_updated')
            ->get();

        $this->assertGreaterThan(0, $logs->count());
        foreach ($logs as $log) {
            $metadataStr = json_encode($log->metadata);
            $this->assertStringNotContainsString($secretRecaptcha, $metadataStr);
            $this->assertStringNotContainsString($secretGoogle, $metadataStr);
            $this->assertStringNotContainsString($secretRecaptcha, $log->description);
            $this->assertStringNotContainsString($secretGoogle, $log->description);
        }
    }

    public function test_institute_admin_cannot_access_filament_institute_resource(): void
    {
        // Institute admin attempting to reach Filament EditInstitute receives 403 Forbidden
        $response = $this->actingAs($this->adminA)
            ->get("/super-admin/institutes/{$this->instituteA->id}/edit");

        // Filament gates panels or returns 403
        $this->assertTrue(in_array($response->status(), [403, 302]));
    }
}

