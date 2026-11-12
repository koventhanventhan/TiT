<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Support\WhatsAppSendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlanFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Primary Institute 1
        Institute::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'TiT Primary Institute',
                'slug' => 'tit',
                'status' => 'active',
                'is_active' => true,
            ]
        );
    }

    /**
     * Create an institute with given features.
     */
    protected function createInstituteWithFeatures(array $features, string $name = 'Feature Inst'): Institute
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Feature Tier',
            'slug' => 'feat-' . uniqid(),
            'monthly_price' => 1000,
            'yearly_price' => 10000,
            'max_students' => 100,
            'max_teachers' => 10,
            'features' => $features,
        ]);

        return Institute::create([
            'name' => $name,
            'slug' => 'inst-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
            'subscription_plan_id' => $plan->id,
        ]);
    }

    /**
     * 1. Zoom: denied without feature, allowed with feature, Institute 1 never denied.
     */
    public function test_zoom_feature_enforcement_web_and_api(): void
    {
        // Inst without Zoom
        $instNoZoom = $this->createInstituteWithFeatures(['whatsapp']);
        $adminNoZoom = User::withoutGlobalScopes()->create([
            'name' => 'Admin No Zoom',
            'email' => 'admin_no_zoom@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'institute_id' => $instNoZoom->id,
        ]);

        // Direct web URL without Zoom -> 403 friendly page
        $resWeb = $this->actingAs($adminNoZoom, 'web')
            ->withHeader('X-Institute-Id', (string) $instNoZoom->id)
            ->get(route('admin.zoom.index'));

        $resWeb->assertStatus(403);
        $resWeb->assertSee('This feature is not included in your plan');
        $resWeb->assertSee('Zoom Classes');

        // Direct API endpoint without Zoom -> 403 JSON with code
        $resApi = $this->actingAs($adminNoZoom, 'web')
            ->withHeader('X-Institute-Id', (string) $instNoZoom->id)
            ->getJson('/api/admin/zoom');

        $resApi->assertStatus(403);
        $resApi->assertJson([
            'code' => 'feature_not_in_plan',
            'feature' => 'zoom',
        ]);

        // Inst with Zoom -> allowed
        $instWithZoom = $this->createInstituteWithFeatures(['zoom']);
        $adminWithZoom = User::withoutGlobalScopes()->create([
            'name' => 'Admin With Zoom',
            'email' => 'admin_with_zoom@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'institute_id' => $instWithZoom->id,
        ]);

        $resWebAllowed = $this->actingAs($adminWithZoom, 'web')
            ->withHeader('X-Institute-Id', (string) $instWithZoom->id)
            ->get(route('admin.zoom.index'));
        $resWebAllowed->assertOk();

        // Institute 1 -> never denied even if plan lacks zoom
        $inst1 = Institute::find(1);
        $inst1->update(['subscription_plan_id' => $instNoZoom->subscription_plan_id]);

        $adminInst1 = User::withoutGlobalScopes()->create([
            'name' => 'Admin Inst 1',
            'email' => 'admin1@tit.lk',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'institute_id' => 1,
        ]);

        $resInst1 = $this->actingAs($adminInst1, 'web')
            ->withHeader('X-Institute-Id', '1')
            ->get(route('admin.zoom.index'));
        $resInst1->assertOk();
    }

    /**
     * 2. AI Assistant: denied without feature, allowed with feature, Institute 1 never denied.
     */
    public function test_ai_assistant_feature_enforcement_public_and_admin(): void
    {
        $instNoAi = $this->createInstituteWithFeatures(['zoom']);
        $adminNoAi = User::withoutGlobalScopes()->create([
            'name' => 'Admin No AI',
            'email' => 'admin_no_ai@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'institute_id' => $instNoAi->id,
        ]);

        // Public assistant chat -> 403 JSON
        $resPublic = $this->withHeader('X-Institute-Id', (string) $instNoAi->id)
            ->postJson('/api/assistant/chat', ['message' => 'Hello']);

        $resPublic->assertStatus(403);
        $resPublic->assertJson([
            'code' => 'feature_not_in_plan',
            'feature' => 'ai_assistant',
        ]);

        // Admin RAG status -> 403 JSON
        $resAdmin = $this->actingAs($adminNoAi, 'web')
            ->withHeader('X-Institute-Id', (string) $instNoAi->id)
            ->getJson('/api/admin/rag/status');

        $resAdmin->assertStatus(403);
        $resAdmin->assertJson([
            'code' => 'feature_not_in_plan',
            'feature' => 'ai_assistant',
        ]);

        // Institute 1 -> never denied
        $this->assertTrue(Institute::find(1)->hasFeature('ai_assistant'));
    }

    /**
     * 3. WhatsApp: skipped with typed result when not in plan.
     */
    public function test_whatsapp_service_skips_when_feature_not_in_plan(): void
    {
        $instNoWa = $this->createInstituteWithFeatures(['zoom']);

        $service = WhatsAppService::forInstitute($instNoWa->id);
        $result = $service->sendTemplateResult('0771234567', 'tit_welcome');

        $this->assertTrue($result->isSkipped());
        $this->assertEquals('feature not in plan', $result->reason);

        // Institute 1 has WhatsApp feature
        $inst1 = Institute::find(1);
        $this->assertTrue($inst1->hasFeature('whatsapp'));
    }

    /**
     * 4. Custom Domain: check allowed/denied and Institute 1 bypass.
     */
    public function test_custom_domain_feature_check(): void
    {
        $instNoDomain = $this->createInstituteWithFeatures(['zoom']);
        $instWithDomain = $this->createInstituteWithFeatures(['custom_domain']);

        $this->assertFalse($instNoDomain->hasFeature('custom_domain'));
        $this->assertTrue($instWithDomain->hasFeature('custom_domain'));

        // Institute 1 is always allowed
        $this->assertTrue(Institute::find(1)->hasFeature('custom_domain'));
    }

    /**
     * 5. Legacy labels mapping works migration-free.
     */
    public function test_legacy_feature_labels_mapping(): void
    {
        $legacyPlan = SubscriptionPlan::create([
            'name' => 'Legacy Plan',
            'slug' => 'legacy-' . uniqid(),
            'monthly_price' => 1000,
            'yearly_price' => 10000,
            'features' => [
                'Zoom Integration',
                'WhatsApp Alerts',
                'Grade Management',
                'Custom Label X',
            ],
        ]);

        $this->assertTrue($legacyPlan->hasFeature('zoom'));
        $this->assertTrue($legacyPlan->hasFeature('whatsapp'));
        $this->assertFalse($legacyPlan->hasFeature('grade_management')); // ignored
        $this->assertFalse($legacyPlan->hasFeature('custom_domain'));
    }

    /**
     * 6. Institute with null plan defaults to true (backwards compatibility).
     */
    public function test_institute_without_plan_defaults_to_all_features_enabled(): void
    {
        $instNoPlan = Institute::create([
            'name' => 'Grandfathered Inst',
            'slug' => 'grand-' . uniqid(),
            'status' => 'active',
            'subscription_plan_id' => null,
        ]);

        $this->assertTrue($instNoPlan->hasFeature('zoom'));
        $this->assertTrue($instNoPlan->hasFeature('ai_assistant'));
        $this->assertTrue($instNoPlan->hasFeature('whatsapp'));
        $this->assertTrue($instNoPlan->hasFeature('custom_domain'));
    }
}

