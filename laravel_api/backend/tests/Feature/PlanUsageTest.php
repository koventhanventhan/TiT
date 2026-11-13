<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PlanUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlanUsageTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $plan;
    protected Institute $institute;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tenant.platform_contact_email' => 'owner@platform.lk',
            'tenant.platform_contact_phone' => '0771234567',
            'tenant.platform_contact_whatsapp' => '0771234567',
        ]);

        $this->plan = SubscriptionPlan::create([
            'name' => 'Growth Tier',
            'slug' => 'growth-tier',
            'monthly_price' => 5000,
            'yearly_price' => 50000,
            'max_students' => 10,
            'max_teachers' => 4,
            'features' => ['zoom', 'whatsapp', 'ai_assistant'],
        ]);

        Institute::withoutGlobalScopes()->find(1) ?: Institute::create([
            'id' => 1,
            'name' => 'Primary TiT',
            'slug' => 'tit',
            'status' => 'active',
        ]);

        $this->institute = Institute::create([
            'name' => 'Horizon College',
            'slug' => 'horizon',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'expires_at' => now()->addDays(10), // Expiring in 10 days (<= 14)
        ]);

        $this->admin = User::withoutGlobalScopes()->create([
            'name' => 'Horizon Admin',
            'email' => 'admin@horizon.lk',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);
    }

    public function test_plan_usage_counting_rules_exclude_deleted_and_rejected_students(): void
    {
        // 2 active students
        User::withoutGlobalScopes()->create([
            'name' => 'Student 1',
            'email' => 's1@horizon.lk',
            'password' => Hash::make('Pass123!'),
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'registration_status' => 'confirmed',
        ]);

        User::withoutGlobalScopes()->create([
            'name' => 'Student 2',
            'email' => 's2@horizon.lk',
            'password' => Hash::make('Pass123!'),
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'registration_status' => 'pending_payment',
        ]);

        // 1 rejected student -> MUST NOT count
        User::withoutGlobalScopes()->create([
            'name' => 'Rejected Student',
            'email' => 's_rejected@horizon.lk',
            'password' => Hash::make('Pass123!'),
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'registration_status' => 'rejected',
        ]);

        // 1 deleted student -> MUST NOT count
        $deleted = User::withoutGlobalScopes()->create([
            'name' => 'Deleted Student',
            'email' => 's_deleted@horizon.lk',
            'password' => Hash::make('Pass123!'),
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'registration_status' => 'confirmed',
        ]);
        $deleted->delete();

        // 1 student from another institute -> MUST NOT count
        User::withoutGlobalScopes()->create([
            'name' => 'Other Inst Student',
            'email' => 'other@other.lk',
            'password' => Hash::make('Pass123!'),
            'role' => 'user',
            'institute_id' => 999,
            'registration_status' => 'confirmed',
        ]);

        $this->assertSame(2, PlanUsage::students($this->institute));
    }

    public function test_plan_usage_percent_and_states(): void
    {
        // Unlimited (null or 0 or -1)
        $this->assertSame(0, PlanUsage::percent(5, null));
        $this->assertSame('unlimited', PlanUsage::state(5, null));
        $this->assertSame('unlimited', PlanUsage::state(5, 0));
        $this->assertSame('unlimited', PlanUsage::state(5, -1));

        // OK (< 80%)
        $this->assertSame(50, PlanUsage::percent(5, 10));
        $this->assertSame('ok', PlanUsage::state(5, 10));

        // Near (>= 80% and < 100%)
        $this->assertSame(80, PlanUsage::percent(8, 10));
        $this->assertSame('near', PlanUsage::state(8, 10));
        $this->assertSame(90, PlanUsage::percent(9, 10));
        $this->assertSame('near', PlanUsage::state(9, 10));

        // Full (>= 100%)
        $this->assertSame(100, PlanUsage::percent(10, 10));
        $this->assertSame('full', PlanUsage::state(10, 10));
        $this->assertSame(100, PlanUsage::percent(12, 10));
        $this->assertSame('full', PlanUsage::state(12, 10));
    }

    public function test_admin_can_view_subscription_page_with_limits_and_features(): void
    {
        $res = $this->actingAs($this->admin, 'web')
            ->withHeaders(['X-Institute-Id' => (string) $this->institute->id])
            ->get(route('admin.subscription'));
        $res->assertOk();
        $res->assertSee('Growth Tier');
        $res->assertSee('Subscription &amp; Limits', false);
        $res->assertSee('Zoom Classes');
        $res->assertSee('WhatsApp Messaging');
        $res->assertSee('AI Learning Assistant');
        $res->assertSee('10 days left');
        $res->assertSee('owner@platform.lk');
        $res->assertSee('0771234567');
    }

    public function test_admin_dashboard_shows_plan_card_and_expiring_soon_notice(): void
    {
        $res = $this->actingAs($this->admin, 'web')
            ->withHeaders(['X-Institute-Id' => (string) $this->institute->id])
            ->get(route('admin.dashboard'));
        $res->assertOk();
        $res->assertSee('Plan &amp; Capacity', false);
        $res->assertSee('Growth Tier');
        $res->assertSee('Your subscription expires in 10 days');
    }
}
