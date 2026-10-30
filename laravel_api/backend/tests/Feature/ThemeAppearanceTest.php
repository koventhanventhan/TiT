<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeAppearanceTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute1;
    protected Institute $institute2;
    protected User $admin1;
    protected User $admin2;
    protected User $student;
    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute1 = Institute::create([
            'name' => 'Institute One',
            'slug' => 'institute-one',
            'code' => 'INS1',
            'status' => 'active',
        ]);

        $this->institute2 = Institute::create([
            'name' => 'Institute Two',
            'slug' => 'institute-two',
            'code' => 'INS2',
            'status' => 'active',
        ]);

        $this->admin1 = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute1->id,
            'email' => 'admin1@test.com',
        ]);

        $this->admin2 = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute2->id,
            'email' => 'admin2@test.com',
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute1->id,
            'email' => 'student@test.com',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $this->institute1->id,
            'email' => 'teacher@test.com',
        ]);
    }

    /**
     * Test admin can access GET /admin/settings/appearance.
     */
    public function test_admin_can_view_appearance_settings_page(): void
    {
        $response = $this->actingAs($this->admin1, 'web')->get(route('admin.settings.appearance'));

        $response->assertStatus(200);
        $response->assertSee('Appearance & Theme', false);
        $response->assertSee('Royal Violet');
        $response->assertSee('Ocean Blue');
        $response->assertSee('Emerald Fresh');
        $response->assertSee('Midnight Dark');
        $response->assertSee('Save Appearance');
    }

    /**
     * Test admin can update theme preset via POST /admin/settings/appearance.
     */
    public function test_admin_can_update_theme_preset(): void
    {
        $response = $this->actingAs($this->admin1, 'web')
            ->post(route('admin.settings.appearance.update'), [
                'theme_preset' => 'ocean',
            ]);

        $response->assertRedirect(route('admin.settings.appearance'));
        $response->assertSessionHas('success');

        $saved = SiteSetting::getForInstitute('theme_preset', $this->institute1->id);
        $this->assertEquals('ocean', $saved);
    }

    /**
     * Test validation rejects invalid theme presets.
     */
    public function test_update_appearance_rejects_invalid_preset(): void
    {
        $response = $this->actingAs($this->admin1, 'web')
            ->from(route('admin.settings.appearance'))
            ->post(route('admin.settings.appearance.update'), [
                'theme_preset' => 'neon_purple_invalid',
            ]);

        $response->assertRedirect(route('admin.settings.appearance'));
        $response->assertSessionHasErrors(['theme_preset']);

        // Value in DB should remain unchanged or default
        $saved = SiteSetting::getForInstitute('theme_preset', $this->institute1->id, 'royal');
        $this->assertEquals('royal', $saved);
    }

    /**
     * Test multi-tenant isolation: Institute 1's preset does not affect Institute 2.
     */
    public function test_tenant_isolation_for_theme_presets(): void
    {
        // Admin 1 sets ocean
        $this->actingAs($this->admin1, 'web')->post(route('admin.settings.appearance.update'), [
            'theme_preset' => 'ocean',
        ]);

        // Admin 2 sets emerald
        $this->actingAs($this->admin2, 'web')->post(route('admin.settings.appearance.update'), [
            'theme_preset' => 'emerald',
        ]);

        // Check DB directly
        $this->assertEquals('ocean', SiteSetting::getForInstitute('theme_preset', $this->institute1->id));
        $this->assertEquals('emerald', SiteSetting::getForInstitute('theme_preset', $this->institute2->id));

        // Flush web auth session before making public API requests with tenant headers
        auth('web')->logout();

        // API Endpoint test with X-Institute-Id header
        $apiRes1 = $this->withHeader('X-Institute-Id', $this->institute1->id)->getJson('/api/settings');
        $apiRes1->assertStatus(200);
        $this->assertEquals('ocean', $apiRes1->json('theme_preset'));

        $apiRes2 = $this->withHeader('X-Institute-Id', $this->institute2->id)->getJson('/api/settings');
        $apiRes2->assertStatus(200);
        $this->assertEquals('emerald', $apiRes2->json('theme_preset'));

        // Admin page rendering reflects the respective tenant's preset
        $admin1Page = $this->actingAs($this->admin1, 'web')->get(route('admin.dashboard'));
        $admin1Page->assertSee('data-theme-preset="ocean"', false);

        $admin2Page = $this->actingAs($this->admin2, 'web')->get(route('admin.dashboard'));
        $admin2Page->assertSee('data-theme-preset="emerald"', false);
    }

    /**
     * Test non-admin roles (guests, students, teachers) cannot access appearance routes.
     */
    public function test_unauthorized_users_blocked_from_appearance_settings(): void
    {
        // Guest
        $guestGet = $this->get(route('admin.settings.appearance'));
        $guestGet->assertRedirect(route('admin.login'));

        $guestPost = $this->post(route('admin.settings.appearance.update'), ['theme_preset' => 'ocean']);
        $guestPost->assertRedirect(route('admin.login'));

        // Student
        $studentGet = $this->actingAs($this->student, 'web')->get(route('admin.settings.appearance'));
        $this->assertTrue(in_array($studentGet->status(), [302, 403], true));

        $studentPost = $this->actingAs($this->student, 'web')->post(route('admin.settings.appearance.update'), ['theme_preset' => 'ocean']);
        $this->assertTrue(in_array($studentPost->status(), [302, 403], true));

        // Teacher
        $teacherGet = $this->actingAs($this->teacher, 'web')->get(route('admin.settings.appearance'));
        $this->assertTrue(in_array($teacherGet->status(), [302, 403], true));

        $teacherPost = $this->actingAs($this->teacher, 'web')->post(route('admin.settings.appearance.update'), ['theme_preset' => 'ocean']);
        $this->assertTrue(in_array($teacherPost->status(), [302, 403], true));
    }
}

