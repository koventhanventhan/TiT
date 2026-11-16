<?php

namespace Tests\Feature;

use App\Filament\SuperAdmin\Pages\InstituteIntegrationsOverview;
use App\Filament\SuperAdmin\Resources\InstituteResource\Pages\CreateInstitute;
use App\Filament\SuperAdmin\Resources\InstituteResource\Pages\ListInstitutes;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\User;
use App\Support\Tenant;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminTenantHostLoginTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $instOne;
    protected Institute $instTwo;
    protected User $superAdminNull;
    protected User $superAdminWithInst;
    protected User $deactivatedSuperAdmin;
    protected User $normalAdminOne;
    protected User $normalAdminTwo;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('super-admin'));

        $this->instOne = Institute::create([
            'name' => 'TiT Primary',
            'slug' => 'tit-primary',
            'status' => 'active',
        ]);
        InstituteDomain::create([
            'institute_id' => $this->instOne->id,
            'domain' => 'primary.lk',
            'is_primary' => true,
        ]);

        $this->instTwo = Institute::create([
            'name' => 'Second Academy',
            'slug' => 'second-academy',
            'status' => 'active',
        ]);
        InstituteDomain::create([
            'institute_id' => $this->instTwo->id,
            'domain' => 'second-academy.lk',
            'is_primary' => true,
        ]);

        // 1. Super admin with institute_id = NULL
        $this->superAdminNull = User::factory()->create([
            'name' => 'Super Admin Global',
            'email' => 'superadmin@lenova.lk',
            'role' => 'super_admin',
            'password' => 'supersecret123',
            'institute_id' => null,
            'deactivated_at' => null,
        ]);

        // 2. Super admin with non-NULL institute_id
        $this->superAdminWithInst = User::factory()->create([
            'name' => 'Super Admin Local',
            'email' => 'superadmin.inst1@lenova.lk',
            'role' => 'super_admin',
            'password' => 'supersecret123',
            'institute_id' => $this->instOne->id,
            'deactivated_at' => null,
        ]);

        // 3. Deactivated super admin
        $this->deactivatedSuperAdmin = User::factory()->create([
            'name' => 'Deactivated Super Admin',
            'email' => 'disabled.super@lenova.lk',
            'role' => 'super_admin',
            'password' => 'supersecret123',
            'institute_id' => null,
            'deactivated_at' => now()->subDay(),
        ]);

        // 4. Normal institute admins
        $this->normalAdminOne = User::factory()->create([
            'name' => 'Admin One',
            'email' => 'admin@primary.lk',
            'role' => 'admin',
            'password' => 'password123',
            'institute_id' => $this->instOne->id,
        ]);

        $this->normalAdminTwo = User::factory()->create([
            'name' => 'Admin Two',
            'email' => 'admin@second-academy.lk',
            'role' => 'admin',
            'password' => 'password123',
            'institute_id' => $this->instTwo->id,
        ]);
    }

    public function test_super_admin_with_null_institute_id_logs_in_on_tenant_host_via_livewire(): void
    {
        request()->headers->set('HOST', 'second-academy.lk');
        $_SERVER['HTTP_HOST'] = 'second-academy.lk';

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'superadmin@lenova.lk',
                'password' => 'supersecret123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($this->superAdminNull, 'super_admin');
    }

    public function test_super_admin_with_null_institute_id_logs_in_on_primary_domain_and_www_alias(): void
    {
        // 1. Primary domain
        request()->headers->set('HOST', 'primary.lk');
        $_SERVER['HTTP_HOST'] = 'primary.lk';

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'superadmin@lenova.lk',
                'password' => 'supersecret123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($this->superAdminNull, 'super_admin');

        // 2. www. alias
        auth('super_admin')->logout();
        request()->headers->set('HOST', 'www.second-academy.lk');
        $_SERVER['HTTP_HOST'] = 'www.second-academy.lk';

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'superadmin@lenova.lk',
                'password' => 'supersecret123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($this->superAdminNull, 'super_admin');
    }

    public function test_super_admin_with_non_null_institute_id_can_also_log_in(): void
    {
        request()->headers->set('HOST', 'second-academy.lk');
        $_SERVER['HTTP_HOST'] = 'second-academy.lk';

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'superadmin.inst1@lenova.lk',
                'password' => 'supersecret123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($this->superAdminWithInst, 'super_admin');
    }

    public function test_super_admin_login_rejects_wrong_password(): void
    {
        request()->headers->set('HOST', 'second-academy.lk');
        $_SERVER['HTTP_HOST'] = 'second-academy.lk';

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'superadmin@lenova.lk',
                'password' => 'wrong-password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest('super_admin');
    }

    public function test_disabled_super_admin_cannot_log_in(): void
    {
        request()->headers->set('HOST', 'second-academy.lk');
        $_SERVER['HTTP_HOST'] = 'second-academy.lk';

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'disabled.super@lenova.lk',
                'password' => 'supersecret123',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest('super_admin');
    }

    public function test_non_super_user_cannot_log_in_to_filament(): void
    {
        request()->headers->set('HOST', 'second-academy.lk');
        $_SERVER['HTTP_HOST'] = 'second-academy.lk';

        // Admin of institute 2 attempts to use super-admin login page
        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'admin@second-academy.lk',
                'password' => 'password123',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest('super_admin');
    }

    public function test_logged_in_normal_admin_visiting_super_admin_sees_friendly_page(): void
    {
        // Normal admin logged into normal web session visits /super-admin
        $response = $this->actingAs($this->normalAdminTwo, 'web')
            ->get('/super-admin');

        $response->assertStatus(403);
        $response->assertSee('This area is for the platform owner');
        $response->assertSee('Return to Institute Dashboard');
        $response->assertSee(route('admin.dashboard'));
    }

    public function test_tenant_isolation_of_normal_login_is_strictly_preserved(): void
    {
        // Attempting normal admin login for Institute 1 admin on Institute 2 host must fail
        $response = $this->withServerVariables(['HTTP_HOST' => 'second-academy.lk'])
            ->from('http://second-academy.lk/admin/login')
            ->post('http://second-academy.lk/admin/login', [
                'email' => 'admin@primary.lk',
                'password' => 'password123',
            ]);

        $this->assertGuest('web');
    }

    public function test_make_super_admin_artisan_command_safely_creates_and_resets(): void
    {
        $targetEmail = 'newsuper@platform.lk';

        $this->artisan('make:super-admin', ['email' => $targetEmail])
            ->expectsQuestion('Enter super-admin full name:', 'New Platform Super')
            ->expectsQuestion('Enter super-admin password (hidden input, min 12 characters):', 'PlatformSecure2026!')
            ->expectsQuestion('Confirm password:', 'PlatformSecure2026!')
            ->expectsOutputToContain('successfully created')
            ->assertExitCode(0);

        $user = User::withoutGlobalScopes()->where('email', $targetEmail)->first();
        $this->assertNotNull($user);
        $this->assertNull($user->institute_id);
        $this->assertSame('super_admin', $user->role);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('PlatformSecure2026!', $user->password));

        // Test password reset via command
        $this->artisan('make:super-admin', ['email' => $targetEmail])
            ->expectsQuestion('Enter super-admin full name:', 'New Platform Super')
            ->expectsQuestion('Enter super-admin password (hidden input, min 12 characters):', 'PlatformUpdated2026!')
            ->expectsQuestion('Confirm password:', 'PlatformUpdated2026!')
            ->expectsOutputToContain('successfully updated')
            ->assertExitCode(0);

        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('PlatformUpdated2026!', $user->password));
    }

    public function test_logged_in_super_admin_can_access_institutes_list_wizard_and_overview_pages_on_tenant_host(): void
    {
        request()->headers->set('HOST', 'second-academy.lk');
        $_SERVER['HTTP_HOST'] = 'second-academy.lk';

        // 1. Authenticate as super_admin on the panel guard
        $this->actingAs($this->superAdminNull, 'super_admin');
        Filament::setCurrentPanel(Filament::getPanel('super-admin'));

        // 2. HTTP GET panel home, institutes list, and create wizard
        $this->withServerVariables(['HTTP_HOST' => 'second-academy.lk'])
            ->get('http://second-academy.lk/super-admin')
            ->assertOk();

        $this->withServerVariables(['HTTP_HOST' => 'second-academy.lk'])
            ->get('http://second-academy.lk/super-admin/institutes')
            ->assertOk();

        $this->withServerVariables(['HTTP_HOST' => 'second-academy.lk'])
            ->get('http://second-academy.lk/super-admin/institutes/create')
            ->assertOk();

        // 3. Exercise Livewire components: ListInstitutes, CreateInstitute, InstituteIntegrationsOverview
        Livewire::test(ListInstitutes::class)
            ->assertCanSeeTableRecords([$this->instOne, $this->instTwo])
            ->assertSee('TiT Primary')
            ->assertSee('Second Academy');

        Livewire::test(CreateInstitute::class)
            ->assertSee('Create institute')
            ->assertSee('Website address');

        Livewire::test(InstituteIntegrationsOverview::class)
            ->assertStatus(200)
            ->assertSee('TiT Primary')
            ->assertSee('Second Academy');
    }

    public function test_forged_referer_on_tenant_host_does_not_set_tenant_to_null(): void
    {
        $updateUri = class_exists(\Livewire\Livewire::class)
            ? \Livewire\Livewire::getUpdateUri()
            : '/livewire/update';

        // A guest or student on Institute B sends POST to Livewire update endpoint with forged Referer header
        // 1. With non-Filament component (or fake component)
        $this->withServerVariables([
            'HTTP_HOST' => 'second-academy.lk',
            'HTTP_REFERER' => 'https://second-academy.lk/super-admin',
        ])->withHeaders([
            'X-Livewire' => 'true',
        ])->postJson('http://second-academy.lk' . $updateUri, [
            'components' => [
                [
                    'snapshot' => json_encode([
                        'memo' => ['name' => 'non-existent-or-guest-component'],
                    ]),
                    'updates' => [],
                    'calls' => [],
                ],
            ],
        ]);

        // Tenant was NOT bypassed to null; Tenant resolves to Second Academy ($this->instTwo)
        $this->assertNotNull(Tenant::get());
        $this->assertSame($this->instTwo->id, Tenant::get()?->id);

        // 2. Even if forged request attempts to name a SuperAdmin component, unauthenticated requester cannot bypass tenant
        Tenant::set(null);
        $this->withServerVariables([
            'HTTP_HOST' => 'second-academy.lk',
            'HTTP_REFERER' => 'https://second-academy.lk/super-admin',
        ])->withHeaders([
            'X-Livewire' => 'true',
        ])->postJson('http://second-academy.lk' . $updateUri, [
            'components' => [
                [
                    'snapshot' => json_encode([
                        'memo' => ['name' => ListInstitutes::class],
                    ]),
                    'updates' => [],
                    'calls' => [],
                ],
            ],
        ]);

        $this->assertNotNull(Tenant::get());
        $this->assertSame($this->instTwo->id, Tenant::get()?->id);
    }

    public function test_real_filament_livewire_request_sets_tenant_to_null_for_authenticated_super_admin(): void
    {
        $updateUri = class_exists(\Livewire\Livewire::class)
            ? \Livewire\Livewire::getUpdateUri()
            : '/livewire/update';

        $this->actingAs($this->superAdminNull, 'super_admin');

        $this->withServerVariables([
            'HTTP_HOST' => 'second-academy.lk',
        ])->withHeaders([
            'X-Livewire' => 'true',
        ])->postJson('http://second-academy.lk' . $updateUri, [
            'components' => [
                [
                    'snapshot' => json_encode([
                        'memo' => ['name' => ListInstitutes::class],
                    ]),
                    'updates' => [],
                    'calls' => [],
                ],
            ],
        ]);

        // Super admin request legitimately ran with Tenant::get() === null
        $this->assertNull(Tenant::get());
    }

    public function test_student_login_on_institute_b_cannot_see_institute_a_users(): void
    {
        $studentOne = User::factory()->create([
            'name' => 'Student In Inst One',
            'email' => 'student1@primary.lk',
            'role' => 'student',
            'institute_id' => $this->instOne->id,
        ]);

        $studentTwo = User::factory()->create([
            'name' => 'Student In Inst Two',
            'email' => 'student2@second-academy.lk',
            'role' => 'student',
            'institute_id' => $this->instTwo->id,
        ]);

        // Set host to Institute B (second-academy.lk)
        $this->withServerVariables(['HTTP_HOST' => 'second-academy.lk']);
        $this->actingAs($studentTwo, 'web');

        // ResolveTenant sets Tenant to instTwo
        $this->get('http://second-academy.lk/admin/login');
        $this->assertSame($this->instTwo->id, Tenant::get()?->id);

        // Querying scoped User model must strictly isolate: only instTwo users visible, instOne users excluded
        $visibleUsers = User::all();
        $this->assertTrue($visibleUsers->contains($studentTwo));
        $this->assertTrue($visibleUsers->contains($this->normalAdminTwo));
        $this->assertFalse($visibleUsers->contains($studentOne));
        $this->assertFalse($visibleUsers->contains($this->normalAdminOne));
    }
}
