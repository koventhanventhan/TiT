<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Models\SiteSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PublicAssistantStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PublicAssistantSharedPredicateTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;
    protected User $admin;
    protected SubscriptionPlan $planWithAi;
    protected SubscriptionPlan $planWithoutAi;

    protected function setUp(): void
    {
        parent::setUp();

        // Create root institute (id 1) so our tested institute is a tenant institute (id > 1)
        // Institute 1 hardcodes hasFeature() => true in this codebase.
        Institute::create([
            'name' => 'Root Platform Institute',
            'slug' => 'root-platform',
            'status' => 'active',
        ]);

        $this->planWithAi = SubscriptionPlan::create([
            'name' => 'Premium with AI',
            'slug' => 'premium-ai',
            'monthly_price' => 5000,
            'yearly_price' => 50000,
            'features' => ['ai_assistant', 'zoom'],
            'is_active' => true,
        ]);

        $this->planWithoutAi = SubscriptionPlan::create([
            'name' => 'Basic without AI',
            'slug' => 'basic-no-ai',
            'monthly_price' => 1000,
            'yearly_price' => 10000,
            'features' => ['zoom'],
            'is_active' => true,
        ]);

        $this->institute = Institute::create([
            'name' => 'Predicate Test Institute',
            'slug' => 'pred-test-inst',
            'status' => 'active',
            'subscription_plan_id' => $this->planWithAi->id,
            'public_assistant_enabled' => false,
            'rag_enabled' => false,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Predicate Admin',
            'email' => 'admin@pred.lk',
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);
    }

    /**
     * Helper to set configured Gemini integration for the test institute.
     */
    protected function configureGemini(): void
    {
        InstituteIntegration::withoutGlobalScopes()->updateOrCreate(
            ['institute_id' => $this->institute->id, 'provider' => InstituteIntegration::PROVIDER_GEMINI],
            ['credentials' => ['api_key' => 'AIzaSyTEST_PREDICATE_KEY_123'], 'status' => InstituteIntegration::STATUS_CONFIGURED]
        );
    }

    /**
     * Helper to set configured reCAPTCHA integration for the test institute.
     */
    protected function configureRecaptcha(): void
    {
        InstituteIntegration::withoutGlobalScopes()->updateOrCreate(
            ['institute_id' => $this->institute->id, 'provider' => InstituteIntegration::PROVIDER_RECAPTCHA],
            ['credentials' => ['site_key' => '6L_SITE_TEST_123', 'secret_key' => '6L_SECRET_TEST_123'], 'status' => InstituteIntegration::STATUS_CONFIGURED]
        );
    }

    /**
     * Helper to configure official contact details.
     */
    protected function configureContacts(): void
    {
        SiteSetting::setForInstitute('assistant_public_contact_phone', '+94 77 123 4567', $this->institute->id, 'assistant_public_contact');
        SiteSetting::setForInstitute('assistant_public_contact_email', 'contact@pred.lk', $this->institute->id, 'assistant_public_contact');
        SiteSetting::setForInstitute('assistant_public_contact_address', '100 Main Street', $this->institute->id, 'assistant_public_contact');
    }

    /**
     * Test the 7 blocking states and verify that /api/settings, toggle endpoints,
     * artisan command, and the Blade page agree on all 7 states.
     */
    public function test_all_seven_blocking_states_agree_across_service_settings_command_and_page(): void
    {
        $service = app(PublicAssistantStatusService::class);

        // =========================================================================
        // STATE 1: Platform switch RAG_PUBLIC_ENABLED off
        // =========================================================================
        config(['rag.public_enabled' => false]);
        $status1 = $service->check($this->institute);
        $this->assertSame(PublicAssistantStatusService::STATE_PLATFORM_DISABLED, $status1['state']);
        $this->assertStringContainsString('ask the platform owner', strtolower($status1['sentence']));

        // /api/settings returns public_assistant_enabled false
        $this->actingAs($this->admin, 'sanctum');
        $settings1 = $this->getJson('/api/settings');
        $this->assertFalse((bool) $settings1->json('public_assistant_enabled'));

        // Blade page displays the exact state sentence
        $this->actingAs($this->admin, 'web');
        $blade1 = $this->get('/admin/assistant');
        $blade1->assertOk();
        $blade1->assertSee($status1['sentence']);

        // =========================================================================
        // STATE 2: Platform switch on, but plan lacks ai_assistant
        // =========================================================================
        config(['rag.public_enabled' => true]);
        $this->institute->update(['subscription_plan_id' => $this->planWithoutAi->id]);
        $this->institute->refresh();

        $status2 = $service->check($this->institute);
        $this->assertSame(PublicAssistantStatusService::STATE_PLAN_MISSING_FEATURE, $status2['state']);
        $this->assertStringContainsString('subscription plan does not include the ai assistant feature', strtolower($status2['sentence']));

        $settings2 = $this->actingAs($this->admin, 'sanctum')->getJson('/api/settings');
        $this->assertFalse((bool) $settings2->json('public_assistant_enabled'));

        // =========================================================================
        // STATE 3: Plan allows AI, but Gemini key is missing
        // =========================================================================
        $this->institute->update(['subscription_plan_id' => $this->planWithAi->id]);
        $this->institute->refresh();
        InstituteIntegration::withoutGlobalScopes()->where('institute_id', $this->institute->id)->delete();

        $status3 = $service->check($this->institute);
        $this->assertSame(PublicAssistantStatusService::STATE_GEMINI_MISSING, $status3['state']);
        $this->assertStringContainsString('google gemini api key is missing', strtolower($status3['sentence']));

        $settings3 = $this->actingAs($this->admin, 'sanctum')->getJson('/api/settings');
        $this->assertFalse((bool) $settings3->json('public_assistant_enabled'));

        $blade3 = $this->actingAs($this->admin, 'web')->get('/admin/assistant');
        $blade3->assertOk();
        $blade3->assertSee($status3['sentence']);

        // =========================================================================
        // STATE 4: Gemini key configured, but reCAPTCHA is missing
        // =========================================================================
        $this->configureGemini();

        $status4 = $service->check($this->institute);
        $this->assertSame(PublicAssistantStatusService::STATE_RECAPTCHA_MISSING, $status4['state']);
        $this->assertStringContainsString('google recaptcha is missing', strtolower($status4['sentence']));

        $settings4 = $this->actingAs($this->admin, 'sanctum')->getJson('/api/settings');
        $this->assertFalse((bool) $settings4->json('public_assistant_enabled'));

        $blade4 = $this->actingAs($this->admin, 'web')->get('/admin/assistant');
        $blade4->assertOk();
        $blade4->assertSee($status4['sentence']);

        // =========================================================================
        // STATE 5: reCAPTCHA configured, but official contact details are missing
        // =========================================================================
        $this->configureRecaptcha();
        SiteSetting::withoutGlobalScopes()->where('institute_id', $this->institute->id)->delete();

        $status5 = $service->check($this->institute);
        $this->assertSame(PublicAssistantStatusService::STATE_CONTACT_DETAILS_MISSING, $status5['state']);
        $this->assertStringContainsString('official contact details are incomplete', strtolower($status5['sentence']));

        $settings5 = $this->actingAs($this->admin, 'sanctum')->getJson('/api/settings');
        $this->assertFalse((bool) $settings5->json('public_assistant_enabled'));

        $blade5 = $this->actingAs($this->admin, 'web')->get('/admin/assistant');
        $blade5->assertOk();
        $blade5->assertSee($status5['sentence']);

        // Toggle endpoint refuses activation with 422
        $toggleApi5 = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/rag/toggle-assistant', [
            'public_assistant_enabled' => true,
        ]);
        $toggleApi5->assertStatus(422);

        // Artisan command refuses activation with code 1
        $artisanCode5 = Artisan::call('rag:public', ['id' => $this->institute->id, 'state' => 'on']);
        $this->assertSame(1, $artisanCode5);

        // =========================================================================
        // STATE 6: Official contacts configured, but institute switch is off
        // =========================================================================
        $this->configureContacts();
        $this->institute->update(['public_assistant_enabled' => false]);
        $this->institute->refresh();

        $status6 = $service->check($this->institute);
        $this->assertSame(PublicAssistantStatusService::STATE_INSTITUTE_DISABLED, $status6['state']);
        $this->assertStringContainsString('public assistant switch is turned off', strtolower($status6['sentence']));

        $settings6 = $this->actingAs($this->admin, 'sanctum')->getJson('/api/settings');
        $this->assertFalse((bool) $settings6->json('public_assistant_enabled'));

        $blade6 = $this->actingAs($this->admin, 'web')->get('/admin/assistant');
        $blade6->assertOk();
        $blade6->assertSee($status6['sentence']);

        // =========================================================================
        // STATE 7: Institute switch is on -> LIVE!
        // =========================================================================
        $this->institute->update(['public_assistant_enabled' => true]);
        $this->institute->refresh();

        $status7 = $service->check($this->institute);
        $this->assertSame(PublicAssistantStatusService::STATE_LIVE, $status7['state']);
        $this->assertSame('Live – visitors can use the assistant.', $status7['sentence']);
        $this->assertTrue($status7['is_live']);

        $settings7 = $this->actingAs($this->admin, 'sanctum')->getJson('/api/settings');
        $this->assertTrue((bool) $settings7->json('public_assistant_enabled'));

        $blade7 = $this->actingAs($this->admin, 'web')->get('/admin/assistant');
        $blade7->assertOk();
        $blade7->assertSee('Live – visitors can use the assistant.');
    }
}

