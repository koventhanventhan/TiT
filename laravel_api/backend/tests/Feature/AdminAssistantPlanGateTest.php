<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAssistantPlanGateTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;
    protected User $admin;

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

        // Create a plan without ai_assistant feature
        $planWithoutAi = SubscriptionPlan::create([
            'name' => 'Basic Tier Without AI',
            'slug' => 'basic-no-ai',
            'monthly_price' => 2000,
            'yearly_price' => 20000,
            'features' => ['zoom', 'student_analytics'], // ai_assistant is omitted!
            'is_active' => true,
        ]);

        $this->institute = Institute::create([
            'name' => 'Plan Gate Institute',
            'slug' => 'plan-gate-inst',
            'status' => 'active',
            'subscription_plan_id' => $planWithoutAi->id,
            'public_assistant_enabled' => false,
            'rag_enabled' => false,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Restricted Admin',
            'email' => 'restricted@plangate.lk',
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);
    }

    /**
     * Verify institute without ai_assistant receives friendly 403 on GET /admin/assistant.
     */
    public function test_get_admin_assistant_returns_friendly_403_page_when_plan_lacks_feature(): void
    {
        $this->actingAs($this->admin, 'web');

        $response = $this->get('/admin/assistant');

        $response->assertStatus(403);
        $response->assertViewIs('errors.feature-not-in-plan');
        $response->assertSee('AI Learning Assistant');
        $response->assertSee('This feature is not included in your plan');
    }

    /**
     * Verify all mutation routes return 403 when plan lacks ai_assistant feature.
     */
    public function test_all_admin_assistant_actions_return_403_when_plan_lacks_feature(): void
    {
        $this->actingAs($this->admin, 'web');

        $routes = [
            ['method' => 'post', 'uri' => '/admin/assistant/toggle-public', 'payload' => ['enabled' => 1]],
            ['method' => 'post', 'uri' => '/admin/assistant/toggle-study', 'payload' => ['enabled' => 1]],
            ['method' => 'post', 'uri' => '/admin/assistant/contacts', 'payload' => ['phone' => '+94 77 123 4567']],
            ['method' => 'post', 'uri' => '/admin/assistant/faqs', 'payload' => ['question' => 'Q?', 'answer' => 'A']],
            ['method' => 'put', 'uri' => '/admin/assistant/faqs/1', 'payload' => ['question' => 'Q2?', 'answer' => 'A2']],
            ['method' => 'delete', 'uri' => '/admin/assistant/faqs/1', 'payload' => []],
            ['method' => 'post', 'uri' => '/admin/assistant/materials/1/reindex', 'payload' => []],
        ];

        foreach ($routes as $r) {
            $response = $this->{$r['method']}($r['uri'], $r['payload']);
            $response->assertStatus(403);
            $response->assertViewIs('errors.feature-not-in-plan');
        }
    }
}

