<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResponsiveTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'name' => 'Responsive Test Academy',
            'slug' => 'resp-test-' . uniqid(),
            'code' => 'RTA' . rand(100, 999),
            'domain' => 'resp-' . uniqid() . '.test',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Tester',
            'email' => 'admin@resp.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
            'institute_id' => $this->institute->id,
            'is_deactivated' => false,
        ]);
    }

    public function test_admin_dashboard_renders_responsive_viewport_and_css_links(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('name="viewport"', false);
        $response->assertSee('admin-theme/css/admin-responsive.css', false);
        $response->assertSee('admin-theme/css/tit-theme.css', false);
        $response->assertSee('admin-theme/css/style.css', false);
    }

    public function test_admin_header_includes_touch_hamburger_and_responsive_structure(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('id="customHamburger"', false);
        $response->assertSee('class="custom-hamburger"', false);
        $response->assertSee('class="search_bar"', false);
        $response->assertSee('class="header-content"', false);
    }

    public function test_admin_students_toolbar_includes_wrapping_and_responsive_containers(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/students');

        $response->assertStatus(200);
        $response->assertSee('students-toolbar', false);
        $response->assertSee('students-action-buttons', false);
        $response->assertSee('premium-filter-group', false);
        $response->assertSee('Assign index numbers', false);
        $response->assertSee('Index Number Lists', false);
    }

    public function test_admin_responsive_css_file_contains_critical_responsive_rules(): void
    {
        $cssPath = public_path('admin-theme/css/admin-responsive.css');
        $this->assertFileExists($cssPath);

        $css = file_get_contents($cssPath);

        // 1. Viewport overflow protection
        $this->assertStringContainsString('overflow-x: hidden', $css);
        $this->assertStringContainsString('touch-action: manipulation', $css);

        // 2. Minimum 44px touch targets
        $this->assertStringContainsString('min-height: 44px', $css);
        $this->assertStringContainsString('custom-hamburger', $css);

        // 3. Students toolbar responsive layout
        $this->assertStringContainsString('.students-toolbar', $css);
        $this->assertStringContainsString('.students-action-buttons', $css);
        $this->assertStringContainsString('.premium-filter-group', $css);

        // 4. Mobile search bar compact restraint
        $this->assertStringContainsString('.header-left .search_bar', $css);
        $this->assertStringContainsString('focus-within', $css);
    }
}

