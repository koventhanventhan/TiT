<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInstituteNameSettingsTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
    }

    private function getAdminUser(): array
    {
        $institute = Institute::firstOrCreate(
            ['slug' => 'main'],
            ['name' => 'Lenova Main Academy', 'status' => 'active']
        );

        $user = User::withoutGlobalScopes()->where('email', 'admin@tit.test')->first();
        if (!$user) {
            $user = User::withoutGlobalScopes()->where('role', 'admin')->first();
        }
        if (!$user) {
            $user = User::factory()->create([
                'role' => 'admin',
                'institute_id' => $institute->id,
                'email' => 'admin@tit.test',
            ]);
        } else {
            $user->update(['institute_id' => $institute->id]);
        }

        return [$user, $institute];
    }

    public function test_appearance_page_displays_institute_name_field(): void
    {
        [$user, $institute] = $this->getAdminUser();

        $response = $this->actingAs($user)->get(route('admin.settings.appearance'));

        $response->assertStatus(200);
        $response->assertSee('Institute Identity & Branding', false);
        $response->assertSee('name="institute_name"', false);
        $response->assertSee(e($institute->name));
    }

    public function test_admin_can_update_institute_name(): void
    {
        [$user, $institute] = $this->getAdminUser();

        $response = $this->actingAs($user)->post(route('admin.settings.appearance.update'), [
            'institute_name' => 'TiT Jaffna Campus',
        ]);

        $response->assertRedirect(route('admin.settings.appearance'));
        $response->assertSessionHas('success');

        $institute->refresh();
        $this->assertEquals('TiT Jaffna Campus', $institute->name);
    }

    public function test_institute_name_validation_enforces_2_to_80_characters(): void
    {
        [$user, $institute] = $this->getAdminUser();

        // 1 char - too short
        $resShort = $this->actingAs($user)->post(route('admin.settings.appearance.update'), [
            'institute_name' => 'T',
        ]);
        $resShort->assertSessionHasErrors('institute_name');

        // 81 chars - too long
        $resLong = $this->actingAs($user)->post(route('admin.settings.appearance.update'), [
            'institute_name' => str_repeat('X', 81),
        ]);
        $resLong->assertSessionHasErrors('institute_name');

        // Empty
        $resEmpty = $this->actingAs($user)->post(route('admin.settings.appearance.update'), [
            'institute_name' => '',
        ]);
        $resEmpty->assertSessionHasErrors('institute_name');
    }

    public function test_institute_name_update_only_affects_current_institute(): void
    {
        [$user, $institute] = $this->getAdminUser();

        $otherInstitute = Institute::create([
            'slug' => 'other-branch-' . uniqid(),
            'name' => 'Untouched Branch Academy',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('admin.settings.appearance.update'), [
            'institute_name' => 'TiT Main Headquarters',
        ]);

        $institute->refresh();
        $otherInstitute->refresh();

        $this->assertEquals('TiT Main Headquarters', $institute->name);
        $this->assertEquals('Untouched Branch Academy', $otherInstitute->name);
    }

    public function test_subtitles_print_updated_institute_name(): void
    {
        [$user, $institute] = $this->getAdminUser();

        $institute->update(['name' => 'TiT Educational Institute']);

        $response = $this->actingAs($user)->get(route('admin.settings.index-series'));
        $response->assertStatus(200);
        $response->assertSee('TiT Educational Institute');
    }
}
