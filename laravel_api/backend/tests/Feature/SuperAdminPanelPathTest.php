<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminPanelPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_path_is_single_sourced_and_routes_and_access_guards_work(): void
    {
        $this->assertSame('super-admin', config('filament.path'));

        $panel = Filament::getPanel('super-admin');
        $this->assertSame(config('filament.path'), $panel->getPath());

        // Named route resolves to /super-admin
        $dashboardUrl = route('filament.super-admin.pages.dashboard', [], false);
        $this->assertSame('/super-admin', $dashboardUrl);

        // Guest gets 200 on /super-admin/login and redirect on /super-admin
        $this->get('/super-admin/login')->assertOk();
        $this->get('/super-admin')->assertRedirect('/super-admin/login');

        // Non-super-admin roles get 403 on /super-admin
        $inst = Institute::create([
            'id' => 1,
            'name' => 'Primary Institute',
            'slug' => 'primary-inst',
            'status' => 'active',
        ]);

        foreach (['admin', 'teacher', 'user'] as $role) {
            $nonSuper = User::withoutGlobalScopes()->create([
                'name' => ucfirst($role) . ' Account',
                'email' => "{$role}@primary-inst.lk",
                'password' => Hash::make('StrongPass!123'),
                'role' => $role,
                'institute_id' => $inst->id,
            ]);

            $this->actingAs($nonSuper)
                ->get('/super-admin')
                ->assertForbidden();
        }

        // Super-admin gets 200 on /super-admin
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Owner',
            'email' => 'owner@platform.lk',
            'password' => Hash::make('OwnerPass!12345'),
            'role' => 'super_admin',
            'institute_id' => $inst->id,
        ]);

        $this->actingAs($superAdmin)
            ->get('/super-admin')
            ->assertOk();
    }

    public function test_legacy_super_panel_route_reaches_laravel_and_301_redirects_to_super_admin(): void
    {
        $this->get('/super-panel')
            ->assertStatus(301)
            ->assertRedirect('/super-admin');

        $this->get('/super-panel/login?next=institutes')
            ->assertStatus(301)
            ->assertRedirect('/super-admin/login?next=institutes');

        $this->get('/super-panel/institutes/create')
            ->assertStatus(301)
            ->assertRedirect('/super-admin/institutes/create');
    }

    public function test_htaccess_and_docs_contain_both_super_admin_and_super_panel_and_panel_brand_is_scoped_to_institute_one(): void
    {
        $repoRoot = dirname(base_path(), 2);

        $files = [
            $repoRoot . '/.htaccess',
            $repoRoot . '/docs/DEPLOYMENT.md',
            $repoRoot . '/docs/INSTITUTE_INTEGRATIONS.md',
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file);
            $contents = (string) file_get_contents($file);
            $this->assertMatchesRegularExpression(
                '/RewriteCond\s+%\{REQUEST_URI\}\s+\^\/\(admin\|api\|super-admin\|super-panel\|livewire\|receipts\|sitemap\\\.xml\|robots\\\.txt\)/',
                $contents,
                "Expected updated RewriteCond regex with both super-admin and super-panel in {$file}"
            );
        }

        // Verify Super-Admin brandName never picks up tenant #2's site_name when institute #1 has no site_name row
        $inst1 = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit',
            'status' => 'active',
        ]);
        $inst2 = Institute::create([
            'name' => 'Second Tenant Academy',
            'slug' => 'second-tenant',
            'status' => 'active',
        ]);
        SiteSetting::setForInstitute('site_name', 'Second Tenant Academy', $inst2->id, 'general');

        $panel = Filament::getPanel('super-admin');
        $this->assertSame('TiT', (string) $panel->getBrandName());

        SiteSetting::setForInstitute('site_name', 'TiT Platform', $inst1->id, 'general');
        $this->assertSame('TiT Platform', (string) $panel->getBrandName());
    }
}
