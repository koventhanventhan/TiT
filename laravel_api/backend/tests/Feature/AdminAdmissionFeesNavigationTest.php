<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdmissionFeesNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit-institute',
            'domain' => 'localhost',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
            'name' => 'Admin User',
            'email' => 'admin@tit.lk',
        ]);
    }

    public function test_admission_fees_page_renders_with_proper_title_breadcrumb_and_navigation(): void
    {
        $this->actingAs($this->admin, 'web');

        $response = $this->get(route('admin.settings.admission-fees'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Title and Header assertions
        $this->assertStringContainsString('<title>Admission Fees - ', $content);
        $this->assertStringContainsString('Admission Fees', $content);
        $this->assertStringContainsString('Payment Settings', $content);

        // 2. Breadcrumb assertions
        $this->assertStringContainsString('class="breadcrumb-item active"><a href="javascript:void(0)">Admission Fees</a>', $content);

        // 3. Navigation active-state: Admission Fees inside Payment Settings is active
        $this->assertMatchesRegularExpression('/<li class="mm-active">\s*<a href="[^"]*\/admin\/settings\/admission-fees">Admission Fees<\/a>\s*<\/li>/', $content);

        // 4. Old "Admission & Payments" item must NOT exist under Settings or anywhere in sidebar
        $this->assertStringNotContainsString('Admission & Payments', $content);
    }

    public function test_payments_alias_url_returns_301_redirect_to_canonical_admission_fees(): void
    {
        $this->actingAs($this->admin, 'web');

        // Test route name works
        $this->assertEquals(url('/admin/settings/payments'), route('admin.settings.payments'));

        // Test HTTP 301 Permanent Redirect to canonical URL
        $response = $this->get('/admin/settings/payments');
        $response->assertStatus(301);
        $response->assertRedirect(route('admin.settings.admission-fees'));
    }
}
