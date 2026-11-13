<?php

namespace Tests\Feature;

use App\Filament\SuperAdmin\Resources\InstituteResource\Pages\EditInstitute;
use App\Filament\SuperAdmin\Resources\InstituteResource\Pages\ListInstitutes;
use App\Filament\SuperAdmin\Resources\InstituteResource\Pages\ViewInstitute;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\InstituteIntegration;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use DomainException;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class InstituteListAndSafetyTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('super-admin'));

        $this->plan = SubscriptionPlan::create([
            'name' => 'Standard Plan',
            'slug' => 'standard-plan',
            'monthly_price' => 3000,
            'yearly_price' => 30000,
            'max_students' => 100,
            'max_teachers' => 10,
        ]);
    }

    public function test_setup_column_badges_for_four_seeded_situations_without_exposing_secrets(): void
    {
        // Situation 1 (ID 1): Verified domain, admin, all 3 integrations configured
        $inst1 = Institute::create([
            'name' => 'Primary Institute',
            'slug' => 'primary-institute',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);
        InstituteDomain::create([
            'institute_id' => $inst1->id,
            'domain' => 'primary.lk',
            'is_primary' => true,
            'verified_at' => now(),
        ]);
        $this->superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Super Admin',
            'email' => 'sa@primary.lk',
            'password' => Hash::make('StrongRoot123!'),
            'role' => 'super_admin',
            'institute_id' => $inst1->id,
        ]);
        User::withoutGlobalScopes()->create([
            'name' => 'Primary Admin',
            'email' => 'admin@primary.lk',
            'password' => Hash::make('StrongAdmin123!'),
            'role' => 'admin',
            'institute_id' => $inst1->id,
        ]);
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $inst1->id,
            'provider' => 'payhere',
            'credentials' => ['merchant_id' => '1223344', 'merchant_secret' => 'TOP_SECRET_PAYHERE_KEY'],
            'settings' => ['sandbox' => true, 'currency' => 'LKR'],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $inst1->id,
            'provider' => 'whatsapp',
            'credentials' => ['access_token' => 'TOP_SECRET_WA_TOKEN'],
            'settings' => ['phone_number_id' => '1122334455'],
            'status' => InstituteIntegration::STATUS_VERIFIED,
        ]);
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $inst1->id,
            'provider' => 'mail',
            'credentials' => ['password' => 'TOP_SECRET_SMTP_PASS'],
            'settings' => ['host' => 'smtp.primary.lk', 'port' => 587, 'from_address' => 'no-reply@primary.lk'],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        // Situation 2: Bare institute (no domain, no admin, 0 integrations)
        $instBare = Institute::create([
            'name' => 'Bare Institute',
            'slug' => 'bare-institute',
            'status' => 'active',
        ]);

        // Situation 3: Unverified domain + admin + 0 integrations
        $instUnverified = Institute::create([
            'name' => 'Unverified Domain Institute',
            'slug' => 'unverified-domain-inst',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);
        InstituteDomain::create([
            'institute_id' => $instUnverified->id,
            'domain' => 'unverified.lk',
            'is_primary' => true,
            'verified_at' => null,
        ]);
        User::withoutGlobalScopes()->create([
            'name' => 'Unverified Admin',
            'email' => 'admin@unverified.lk',
            'password' => Hash::make('StrongAdmin123!'),
            'role' => 'admin',
            'institute_id' => $instUnverified->id,
        ]);

        // Situation 4: Verified domain + no admin + 1 integration (PayHere)
        $instPartial = Institute::create([
            'name' => 'Partial Setup Institute',
            'slug' => 'partial-setup-inst',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);
        InstituteDomain::create([
            'institute_id' => $instPartial->id,
            'domain' => 'partial.lk',
            'is_primary' => true,
            'verified_at' => now(),
        ]);
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $instPartial->id,
            'provider' => 'payhere',
            'credentials' => ['merchant_id' => '998877', 'merchant_secret' => 'PARTIAL_SECRET_KEY'],
            'settings' => ['sandbox' => true, 'currency' => 'LKR'],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        // Verify computed setupBadges() across all 4 situations
        $this->assertSame(['Domain: verified', 'Admin: yes', 'Integrations: 3/7'], $inst1->setupBadges());
        $this->assertSame(['Domain: none', 'Admin: no', 'Integrations: 0/7'], $instBare->setupBadges());
        $this->assertSame(['Domain: unverified', 'Admin: yes', 'Integrations: 0/7'], $instUnverified->setupBadges());
        $this->assertSame(['Domain: verified', 'Admin: no', 'Integrations: 1/7'], $instPartial->setupBadges());

        // Render ListInstitutes table and assert badges appear while secrets NEVER appear
        $this->actingAs($this->superAdmin);

        $list = Livewire::test(ListInstitutes::class)
            ->assertCanSeeTableRecords([$inst1, $instBare, $instUnverified, $instPartial])
            ->assertSee('Domain: verified')
            ->assertSee('Domain: unverified')
            ->assertSee('Domain: none')
            ->assertSee('Admin: yes')
            ->assertSee('Admin: no')
            ->assertSee('Integrations: 3/7')
            ->assertSee('Integrations: 1/7')
            ->assertSee('Integrations: 0/7')
            ->assertDontSee('TOP_SECRET_PAYHERE_KEY')
            ->assertDontSee('TOP_SECRET_WA_TOKEN')
            ->assertDontSee('TOP_SECRET_SMTP_PASS')
            ->assertDontSee('PARTIAL_SECRET_KEY');

        // DeleteBulkAction must not exist on InstituteResource
        $list->assertTableBulkActionDoesNotExist(DeleteBulkAction::class);
    }

    public function test_institute_one_and_active_tenant_guards_in_ui_policy_and_model(): void
    {
        // Institute #1 (live primary institute)
        $inst1 = Institute::create([
            'name' => 'TiT Live Primary',
            'slug' => 'tit-live-primary',
            'status' => 'active',
        ]);

        $this->superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Super Admin',
            'email' => 'sa@tit.lk',
            'password' => Hash::make('StrongRoot123!'),
            'role' => 'super_admin',
            'institute_id' => $inst1->id,
        ]);

        // Institute #2: has 1 student -> cannot delete, must suspend instead
        $instWithStudents = Institute::create([
            'name' => 'Populated Academy',
            'slug' => 'populated-academy',
            'status' => 'active',
        ]);
        User::withoutGlobalScopes()->create([
            'name' => 'Enrolled Student',
            'email' => 'student@populated.lk',
            'password' => Hash::make('StudentPass123!'),
            'role' => 'user',
            'institute_id' => $instWithStudents->id,
        ]);

        // Institute #3: has 0 students, 1 payment -> cannot delete, must suspend instead
        $instWithPayments = Institute::create([
            'name' => 'Paid Academy',
            'slug' => 'paid-academy',
            'status' => 'active',
        ]);
        $paidAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Paid Admin',
            'email' => 'admin@paid.lk',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'admin',
            'institute_id' => $instWithPayments->id,
        ]);
        Payment::withoutGlobalScopes()->create([
            'user_id' => $paidAdmin->id,
            'institute_id' => $instWithPayments->id,
            'amount' => 2500,
            'year_month' => '2026-11',
            'payment_method' => 'offline',
            'status' => 'paid',
        ]);

        // Institute #4: empty (0 students, 0 payments) -> can delete ONLY after typing exact name
        $instEmpty = Institute::create([
            'name' => 'Empty Trial Institute',
            'slug' => 'empty-trial',
            'status' => 'active',
        ]);

        $this->actingAs($this->superAdmin);

        // Policy checks
        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('delete', $inst1));
        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('suspend', $inst1));
        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('delete', $instWithStudents));
        $this->assertFalse(Gate::forUser($this->superAdmin)->allows('delete', $instWithPayments));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('delete', $instEmpty));

        // Server-side model guards on Institute #1
        try {
            $inst1->delete();
            $this->fail('Expected DomainException when deleting Institute #1');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Institute #1', $e->getMessage());
        }

        try {
            $inst1->update(['status' => 'suspended']);
            $this->fail('Expected DomainException when suspending Institute #1');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Institute #1', $e->getMessage());
        }
        $this->assertSame('active', $inst1->fresh()->status);

        // Server-side model guard on institute with students or payments
        try {
            $instWithStudents->delete();
            $this->fail('Expected DomainException when deleting institute with students');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Suspend it instead', $e->getMessage());
        }

        // UI visibility on ListInstitutes
        $list = Livewire::test(ListInstitutes::class);
        $list->assertTableActionHidden('suspend', $inst1);
        $list->assertTableActionHidden('delete_institute', $inst1);
        $list->assertTableActionVisible('suspend', $instWithStudents);
        $list->assertTableActionHidden('delete_institute', $instWithStudents);
        $list->assertTableActionHidden('delete_institute', $instWithPayments);
        $list->assertTableActionVisible('delete_institute', $instEmpty);

        // Deleting empty institute requires typing its exact name
        $list->callTableAction('delete_institute', $instEmpty, data: [
            'confirm_name' => 'Wrong Name',
        ])->assertHasTableActionErrors(['confirm_name']);
        $this->assertNotSoftDeleted('institutes', ['id' => $instEmpty->id]);

        $list->fillForm([
            'confirm_name' => 'Empty Trial Institute',
        ])->callMountedAction()->assertHasNoFormErrors();
        $this->assertSoftDeleted('institutes', ['id' => $instEmpty->id]);

        // Trashed slug collision is rejected on EditInstitute
        Livewire::test(EditInstitute::class, ['record' => $instWithStudents->getKey()])
            ->fillForm(['slug' => 'empty-trial'])
            ->call('save')
            ->assertHasFormErrors(['slug']);
    }

    public function test_view_institute_checklist_actions_and_one_time_ready_token_expires_on_refresh(): void
    {
        $inst1 = Institute::create(['name' => 'Root', 'slug' => 'root', 'status' => 'active']);
        $this->superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Super Admin',
            'email' => 'sa@root.lk',
            'password' => Hash::make('StrongRoot123!'),
            'role' => 'super_admin',
            'institute_id' => $inst1->id,
        ]);

        $inst2 = Institute::create([
            'name' => 'Checklist Academy',
            'slug' => 'checklist-academy',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);

        $this->actingAs($this->superAdmin);

        // 1. One-time ready token shows password on first load and disappears on second load (refresh)
        $token = ViewInstitute::issueReadyToken($inst2->id, 'first@checklist.lk', 'OneTimePass#987654');

        Livewire::withQueryParams(['ready' => $token])
            ->test(ViewInstitute::class, ['record' => $inst2->getKey()])
            ->assertSee('Checklist Academy is ready')
            ->assertSee('OneTimePass#987654');

        // Refreshing with the same URL token must NOT show the password again!
        Livewire::withQueryParams(['ready' => $token])
            ->test(ViewInstitute::class, ['record' => $inst2->getKey()])
            ->assertDontSee('OneTimePass#987654');

        // 2. Checklist actions: createAdmin, addDomain, resetAdminPassword
        $view = Livewire::test(ViewInstitute::class, ['record' => $inst2->getKey()]);

        $view->callAction('createAdmin', data: [
            'admin_name' => 'Checklist Admin',
            'admin_email' => 'admin@checklist.lk',
            'admin_phone' => '0775556667',
        ])->assertHasNoActionErrors();

        $firstPassword = $view->get('oneTimeAdminPassword');
        $this->assertNotNull($firstPassword);
        $this->assertGreaterThanOrEqual(14, strlen((string) $firstPassword));

        $admin = User::withoutGlobalScopes()->where('email', 'admin@checklist.lk')->firstOrFail();
        $this->assertTrue(Hash::check($firstPassword, $admin->password));

        // Reset admin password generates a brand-new show-once password
        $view->callAction('resetAdminPassword')->assertHasNoActionErrors();
        $resetPassword = $view->get('oneTimeAdminPassword');
        $this->assertNotNull($resetPassword);
        $this->assertNotSame($firstPassword, $resetPassword);
        $this->assertTrue(Hash::check($resetPassword, $admin->fresh()->password));

        // Add domain action from checklist
        $view->callAction('addDomain', data: [
            'domain' => 'https://www.checklist-academy.lk',
            'is_primary' => true,
        ])->assertHasNoActionErrors();
        $this->assertDatabaseHas('institute_domains', [
            'institute_id' => $inst2->id,
            'domain' => 'checklist-academy.lk',
            'is_primary' => 1,
        ]);
    }
}
