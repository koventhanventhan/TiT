<?php

namespace Tests\Feature\Filament;

use App\Filament\SuperAdmin\Resources\InstituteResource\Pages\CreateInstitute;
use App\Filament\SuperAdmin\Resources\InstituteResource\Pages\ViewInstitute;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\SiteSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\DomainChecker;
use App\Services\InstituteProvisioner;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CreateInstituteWizardTest extends TestCase
{
    use RefreshDatabase;

    private Institute $primaryInstitute;
    private User $superAdmin;
    private SubscriptionPlan $unlimitedPlan;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('super-admin'));

        $this->primaryInstitute = Institute::create([
            'name' => 'Primary Institute',
            'slug' => 'primary-institute',
            'status' => 'active',
        ]);

        InstituteDomain::create([
            'institute_id' => $this->primaryInstitute->id,
            'domain' => 'primary.lk',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $this->superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Owner',
            'email' => 'owner@primary.lk',
            'password' => Hash::make('StrongOwnerPass!123'),
            'role' => 'super_admin',
            'institute_id' => $this->primaryInstitute->id,
        ]);

        $this->unlimitedPlan = SubscriptionPlan::create([
            'name' => 'Enterprise Unlimited',
            'slug' => 'enterprise-unlimited',
            'monthly_price' => 9500,
            'yearly_price' => 95000,
            'max_students' => 0,
            'max_teachers' => -1,
            'features' => [
                'online_payments' => true,
                'whatsapp_alerts' => true,
            ],
        ]);
    }

    public function test_non_super_admins_receive_403_on_panel_and_create_wizard(): void
    {
        foreach (['admin', 'teacher', 'user'] as $role) {
            $nonSuper = User::withoutGlobalScopes()->create([
                'name' => ucfirst($role) . ' Account',
                'email' => "{$role}@primary.lk",
                'password' => Hash::make('Pass123456!'),
                'role' => $role,
                'institute_id' => $this->primaryInstitute->id,
            ]);

            $this->actingAs($nonSuper)
                ->get('/super-admin')
                ->assertStatus(403);

            $this->actingAs($nonSuper)
                ->get('/super-admin/institutes/create')
                ->assertStatus(403);
        }
    }

    public function test_each_wizard_step_validates_inputs_before_advancing(): void
    {
        $this->actingAs($this->superAdmin);

        $existingUser = User::withoutGlobalScopes()->create([
            'name' => 'Existing Teacher',
            'email' => 'taken@primary.lk',
            'password' => Hash::make('Pass123456!'),
            'role' => 'teacher',
            'institute_id' => $this->primaryInstitute->id,
        ]);

        $wizard = Livewire::test(CreateInstitute::class);

        // Ensure developer terms (artisan, table/column names) and hidden 'midnight' preset do not appear in visible UI text
        $wizard->assertDontSeeText('artisan')
            ->assertDontSeeText('institute_domains')
            ->assertDontSeeText('subscription_plan_id')
            ->assertDontSeeText('Midnight');

        // Step 1 validation: empty/too-short name fails
        $wizard->fillForm(['name' => 'A'])
            ->goToWizardStep(2)
            ->assertHasFormErrors(['name']);

        // Valid Step 1 auto-populates short_brand_name and non-TIT index_prefix
        $wizard->fillForm([
            'name' => 'Titans Learning Center',
            'contact_email' => 'hello@titans.lk',
            'contact_phone' => '0771122334',
        ])
            ->goToWizardStep(2)
            ->assertHasNoFormErrors();

        $this->assertSame('Titans Learning Center', $wizard->get('data.short_brand_name'));
        $this->assertNotSame('TIT', $wizard->get('data.index_prefix'));

        // Step 2 validation: empty domain when defer_domain is unchecked fails
        $wizard->fillForm([
            'defer_domain' => false,
            'domain' => '',
        ])
            ->goToWizardStep(3)
            ->assertHasFormErrors(['domain']);

        // Step 2 validation: localhost / *.test / owned domain rejected (naming owner)
        $wizard->fillForm(['domain' => 'school.test'])
            ->goToWizardStep(3)
            ->assertHasFormErrors(['domain']);

        $wizard->fillForm(['domain' => 'https://www.primary.lk/login'])
            ->goToWizardStep(3)
            ->assertHasFormErrors(['domain'])
            ->assertSee('Primary Institute');

        // Valid unverified domain passes Step 2
        $wizard->fillForm(['domain' => 'https://www.Titans-Center.lk/'])
            ->goToWizardStep(3)
            ->assertHasNoFormErrors();
        $this->assertSame('titans-center.lk', $wizard->get('data.domain'));

        // Step 3 validation: missing plan fails; valid plan shows Unlimited summary
        $wizard->fillForm(['subscription_plan_id' => null])
            ->goToWizardStep(4)
            ->assertHasFormErrors(['subscription_plan_id']);

        $wizard->fillForm(['subscription_plan_id' => $this->unlimitedPlan->id])
            ->call('applyExpiryPreset', '1y')
            ->assertSee('Unlimited')
            ->goToWizardStep(4)
            ->assertHasNoFormErrors();

        // Step 4 validation: duplicate email triggers exact message before creating anything
        $wizard->fillForm([
            'create_admin' => true,
            'admin_name' => 'Titans Principal',
            'admin_email' => $existingUser->email,
            'admin_phone' => '0779988776',
        ])
            ->goToWizardStep(5)
            ->assertHasFormErrors(['admin_email'])
            ->assertSee(InstituteProvisioner::DUPLICATE_EMAIL_MESSAGE);

        // Missing admin_phone when create_admin = true fails
        $wizard->fillForm([
            'admin_email' => 'principal@titans-center.lk',
            'admin_phone' => '',
        ])
            ->goToWizardStep(5)
            ->assertHasFormErrors(['admin_phone']);

        $wizard->fillForm([
            'admin_phone' => '0779988776',
        ])
            ->goToWizardStep(5)
            ->assertHasNoFormErrors();

        // Step 5 validation: midnight preset rejected, invalid prefix rejected
        $wizard->fillForm([
            'theme_preset' => 'midnight',
            'index_prefix' => 'A!',
        ])
            ->goToWizardStep(6)
            ->assertHasFormErrors(['theme_preset', 'index_prefix']);

        $wizard->call('selectThemePreset', 'ocean')
            ->fillForm([
                'index_prefix' => 'tlc',
                'index_format' => 'grade',
            ])
            ->assertSee('TLC/08/001')
            ->goToWizardStep(6)
            ->assertHasNoFormErrors();
    }

    public function test_happy_path_creates_institute_and_redirects_to_institute_ready_screen_with_show_once_credentials(): void
    {
        config(['tenant.server_ips' => ['203.0.113.55']]);
        $this->app->instance(
            DomainChecker::class,
            new DomainChecker(dnsResolver: static fn (string $host): array => $host === 'northern-academy.lk' ? ['203.0.113.55'] : [])
        );

        $this->actingAs($this->superAdmin);

        $wizard = Livewire::test(CreateInstitute::class)
            ->fillForm([
                'name' => 'Northern Science Academy',
                'short_brand_name' => 'Northern Academy',
                'contact_email' => 'info@northern-academy.lk',
                'contact_phone' => '0712233445',
                'defer_domain' => false,
                'domain' => 'https://www.northern-academy.lk',
                'subscription_plan_id' => $this->unlimitedPlan->id,
                'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
                'create_admin' => true,
                'admin_name' => 'Dr. S. Nimalan',
                'admin_email' => 'admin@northern-academy.lk',
                'admin_phone' => '0719988776',
                'theme_preset' => 'emerald',
                'index_prefix' => 'NSA',
                'index_format' => 'grade',
            ])
            ->call('checkWizardDomainDns')
            ->assertSee('Points to this server')
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Institute::where('slug', 'northern-science-academy')->firstOrFail();
        $this->assertSame('active', $created->status);
        $this->assertSame('NSA', $created->index_prefix);
        $this->assertSame('grade', $created->index_format);

        $domainRow = InstituteDomain::where('institute_id', $created->id)->firstOrFail();
        $this->assertSame('northern-academy.lk', $domainRow->domain);
        $this->assertTrue($domainRow->is_primary);
        $this->assertNotNull($domainRow->verified_at);

        $admin = User::withoutGlobalScopes()
            ->where('institute_id', $created->id)
            ->where('role', 'admin')
            ->firstOrFail();
        $this->assertSame('admin@northern-academy.lk', $admin->email);

        $this->assertSame('Northern Science Academy', $created->name);
        $this->assertSame('Northern Academy', SiteSetting::getForInstitute('site_name', $created->id));
        $this->assertSame('Northern Academy', SiteSetting::getForInstitute('admin_company_name', $created->id));
        $this->assertSame('emerald', SiteSetting::getForInstitute('theme_preset', $created->id));
        $this->assertSame('info@northern-academy.lk', SiteSetting::getForInstitute('footer_email', $created->id));
        $this->assertSame('0712233445', SiteSetting::getForInstitute('footer_phone', $created->id));

        // Verify redirect goes to ViewInstitute ("Institute ready") with ?ready= token (NOT the list)
        $redirectUrl = (string) data_get($wizard->effects, 'redirect');
        $this->assertStringContainsString("/super-admin/institutes/{$created->id}", $redirectUrl);
        $this->assertStringContainsString('ready=', $redirectUrl);

        parse_str((string) parse_url($redirectUrl, PHP_URL_QUERY), $query);
        $readyToken = (string) ($query['ready'] ?? '');
        $this->assertNotEmpty($readyToken);

        $readyScreen = Livewire::withQueryParams(['ready' => $readyToken])
            ->test(ViewInstitute::class, ['record' => $created->getKey()])
            ->assertSee('Northern Science Academy is ready')
            ->assertSee('https://northern-academy.lk/admin/login')
            ->assertSee('admin@northern-academy.lk')
            ->assertSee('I have saved it in a safe place')
            ->assertSee('Setup checklist')
            ->assertSee('Add domain')
            ->assertSee('Set up online payments')
            ->assertSee('Set up WhatsApp &amp; e-mail', false)
            ->assertSee("Open this institute's admin", false);

        $shownPassword = (string) $readyScreen->get('oneTimeAdminPassword');
        $this->assertGreaterThanOrEqual(14, strlen($shownPassword));
        $this->assertTrue(Hash::check($shownPassword, $admin->password));

        // Full HTTP GET of the ViewInstitute page (renders Filament layout + flashed notification)
        // and verifies the one-time password does NOT survive a subsequent page load.
        $httpRes = $this->get("/super-admin/institutes/{$created->id}?ready={$readyToken}");
        $httpRes->assertOk();
        $httpRes->assertDontSee($shownPassword);
    }
}

