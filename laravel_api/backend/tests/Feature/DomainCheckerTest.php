<?php

namespace Tests\Feature;

use App\Filament\SuperAdmin\Resources\InstituteResource\Pages\EditInstitute;
use App\Filament\SuperAdmin\Resources\InstituteResource\RelationManagers\DomainsRelationManager;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\User;
use App\Services\DomainChecker;
use DomainException;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class DomainCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalizes_scheme_www_port_path_and_case(): void
    {
        $checker = new DomainChecker(fn (): array => []);

        $this->assertSame('academy.lk', $checker->normalize('  HTTPS://WWW.Academy.LK:8443/login?next=/admin#top  '));
        $this->assertSame('portal.school-edu.com', $checker->normalize('http://portal.school-edu.com/'));
    }

    public function test_validates_hostnames_rejects_ips_localhost_test_platform_hosts_and_duplicates(): void
    {
        config(['tenant.platform_hosts' => ['titjaffna.lk', 'localhost', '127.0.0.1']]);

        $owner = Institute::create([
            'name' => 'Northern Star Institute',
            'slug' => 'northern-star',
            'status' => 'active',
        ]);

        InstituteDomain::create([
            'institute_id' => $owner->id,
            'domain' => 'northernstar.lk',
            'is_primary' => true,
        ]);

        $checker = new DomainChecker(fn (): array => []);

        // Valid real hostnames
        $this->assertNull($checker->validate('bright-future.lk'));
        $this->assertNull($checker->validate('https://www.learn.bright-future.org.uk:443/home'));

        // Rejects IPs
        $this->assertNotNull($checker->validate('192.168.1.10'));
        $this->assertNotNull($checker->validate('2001:db8::1'));

        // Rejects localhost and *.test
        $this->assertNotNull($checker->validate('localhost'));
        $this->assertNotNull($checker->validate('academy.localhost'));
        $this->assertNotNull($checker->validate('academy.test'));

        // Rejects platform host
        $platformErr = $checker->validate('https://www.titjaffna.lk');
        $this->assertNotNull($platformErr);
        $this->assertStringContainsStringIgnoringCase('platform', $platformErr);

        // Rejects malformed hostnames / short TLD
        $this->assertNotNull($checker->validate('nodot'));
        $this->assertNotNull($checker->validate('-leadinghyphen.lk'));
        $this->assertNotNull($checker->validate('trailinghyphen-.lk'));
        $this->assertNotNull($checker->validate('bad_underscore.lk'));
        $this->assertNotNull($checker->validate('shorttld.x'));

        // Rejects domain owned by another institute and names that institute
        $dupErr = $checker->validate('www.northernstar.lk');
        $this->assertNotNull($dupErr);
        $this->assertStringContainsString('Northern Star Institute', $dupErr);
    }

    public function test_check_dns_badges_and_verified_at_with_injected_fake_resolver(): void
    {
        $institute = Institute::create([
            'name' => 'Southern Scholars',
            'slug' => 'southern-scholars',
            'status' => 'active',
        ]);

        $domainRecord = InstituteDomain::create([
            'institute_id' => $institute->id,
            'domain' => 'southernscholars.lk',
            'is_primary' => true,
            'verified_at' => null,
        ]);

        // 1. Server IP empty -> Not checked + mentions TENANT_SERVER_IPS
        config(['tenant.server_ips' => []]);
        $checkerUnconfigured = new DomainChecker(fn (string $host): array => ['203.0.113.50']);
        $resUnconfigured = $checkerUnconfigured->checkDns('southernscholars.lk', $domainRecord);

        $this->assertSame(DomainChecker::STATUS_NOT_CHECKED, $resUnconfigured['status']);
        $this->assertSame('Not checked', $resUnconfigured['badge']);
        $this->assertStringContainsString('TENANT_SERVER_IPS', $resUnconfigured['message']);
        $this->assertNull($domainRecord->fresh()->verified_at);

        // Configure server IP for remaining checks
        config(['tenant.server_ips' => ['203.0.113.10']]);

        // 2. Empty A records -> Not found
        $checkerNotFound = new DomainChecker(fn (string $host): array => []);
        $resNotFound = $checkerNotFound->checkDns('southernscholars.lk', $domainRecord);
        $this->assertSame(DomainChecker::STATUS_NOT_FOUND, $resNotFound['status']);
        $this->assertSame('Not found', $resNotFound['badge']);
        $this->assertNull($domainRecord->fresh()->verified_at);

        // 3. Points elsewhere -> Points elsewhere
        $checkerElsewhere = new DomainChecker(fn (string $host): array => ['198.51.100.99']);
        $resElsewhere = $checkerElsewhere->checkDns('southernscholars.lk', $domainRecord);
        $this->assertSame(DomainChecker::STATUS_POINTS_ELSEWHERE, $resElsewhere['status']);
        $this->assertSame('Points elsewhere', $resElsewhere['badge']);
        $this->assertNull($domainRecord->fresh()->verified_at);

        // 4. Points to this server -> Points to this server + sets verified_at!
        $checkerMatch = new DomainChecker(fn (string $host): array => ['203.0.113.10']);
        $resMatch = $checkerMatch->checkDns('southernscholars.lk', $domainRecord);
        $this->assertSame(DomainChecker::STATUS_POINTS_HERE, $resMatch['status']);
        $this->assertSame('Points to this server', $resMatch['badge']);
        $this->assertTrue($resMatch['verified']);
        $this->assertNotNull($domainRecord->fresh()->verified_at);
    }

    public function test_cpanel_instructions_cover_alias_docroot_autossl_and_manual_owner_note(): void
    {
        config(['tenant.server_ips' => ['203.0.113.10']]);
        $checker = new DomainChecker(fn (): array => []);
        $info = $checker->cpanelInstructions('academy.lk');

        $joined = implode(' ', $info['steps']) . ' ' . $info['manual_note'];
        $this->assertStringContainsString('203.0.113.10', $joined);
        $this->assertStringContainsString('/home/titjaffn/public_html', $joined);
        $this->assertStringContainsString('Alias / Parked domain', $joined);
        $this->assertStringContainsString('titjaffna.lk', $joined);
        $this->assertStringContainsString('AutoSSL', $joined);
        $this->assertStringContainsString('Check DNS now', $joined);
        $this->assertStringContainsString('cPanel', $info['manual_note']);
    }

    public function test_domains_relation_manager_add_make_primary_verify_and_removal_guards(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('super-admin'));

        // Institute #1 (protected primary institute)
        $inst1 = Institute::create([
            'name' => 'TiT Primary',
            'slug' => 'tit-primary',
            'status' => 'active',
        ]);
        $inst1Domain = InstituteDomain::create([
            'institute_id' => $inst1->id,
            'domain' => 'titjaffna.lk',
            'is_primary' => true,
        ]);

        // Institute #2 (customer institute with a user)
        $inst2 = Institute::create([
            'name' => 'Central Academy',
            'slug' => 'central-academy',
            'status' => 'active',
        ]);

        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Super Admin',
            'email' => 'root@platform.lk',
            'password' => Hash::make('StrongRootPass123!'),
            'role' => 'super_admin',
            'institute_id' => $inst1->id,
        ]);

        User::withoutGlobalScopes()->create([
            'name' => 'Central Admin',
            'email' => 'admin@central.lk',
            'password' => Hash::make('StrongAdminPass123!'),
            'role' => 'admin',
            'institute_id' => $inst2->id,
        ]);

        config(['tenant.server_ips' => ['203.0.113.77']]);
        $this->app->instance(DomainChecker::class, new DomainChecker(
            fn (string $host): array => $host === 'central.lk' ? ['203.0.113.77'] : []
        ));

        $this->actingAs($superAdmin);

        $component = Livewire::test(DomainsRelationManager::class, [
            'ownerRecord' => $inst2,
            'pageClass' => EditInstitute::class,
        ]);

        // 1. Add first domain -> automatically primary & unverified
        $component->callTableAction('add_domain', data: [
            'domain' => 'https://www.central.lk',
            'is_primary' => false,
        ])->assertHasNoTableActionErrors();

        $dom1 = InstituteDomain::where('domain', 'central.lk')->firstOrFail();
        $this->assertTrue($dom1->is_primary);
        $this->assertNull($dom1->verified_at);

        // 2. Verify DNS via relation manager -> sets verified_at
        $component->callTableAction('verify_dns', $dom1);
        $this->assertNotNull($dom1->fresh()->verified_at);

        // 3. Add second domain and promote to primary
        $component->callTableAction('add_domain', data: [
            'domain' => 'portal.central.lk',
            'is_primary' => false,
        ])->assertHasNoTableActionErrors();

        $dom2 = InstituteDomain::where('domain', 'portal.central.lk')->firstOrFail();
        $this->assertFalse($dom2->is_primary);

        $component->callTableAction('make_primary', $dom2);
        $this->assertTrue($dom2->fresh()->is_primary);
        $this->assertFalse($dom1->fresh()->is_primary);

        // Remove non-last domain (dom1) -> succeeds cleanly
        $component->callTableAction('remove_domain', $dom1);
        $this->assertDatabaseMissing('institute_domains', ['id' => $dom1->id]);

        // 4. Removing the LAST domain when institute has users requires explicit confirmation checkbox
        $component->callTableAction('remove_domain', $dom2, data: [
            'confirm_last_domain_removal' => false,
        ])->assertHasTableActionErrors(['confirm_last_domain_removal']);
        $this->assertDatabaseHas('institute_domains', ['id' => $dom2->id]);

        // Succeeds when checkbox is confirmed on the mounted modal
        $component->fillForm([
            'confirm_last_domain_removal' => true,
        ])->callMountedAction()->assertHasNoFormErrors();
        $this->assertDatabaseMissing('institute_domains', ['id' => $dom2->id]);

        // 5. Server-side guard: cannot delete last domain of Institute #1
        try {
            $inst1Domain->delete();
            $this->fail('Expected DomainException when deleting last domain of Institute #1');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Institute #1', $e->getMessage());
        }
        $this->assertDatabaseHas('institute_domains', ['id' => $inst1Domain->id]);

        // 6. Server-side guard: cannot delete domain matching current request host
        $activeHostDomain = InstituteDomain::create([
            'institute_id' => $inst2->id,
            'domain' => 'current-panel.lk',
            'is_primary' => true,
        ]);
        $fakeReq = Request::create('https://current-panel.lk/super-admin/institutes/2/edit', 'GET');
        $this->app->instance('request', $fakeReq);

        try {
            $activeHostDomain->delete();
            $this->fail('Expected DomainException when deleting domain matching current request host');
        } catch (DomainException $e) {
            $this->assertStringContainsString('currently using', $e->getMessage());
        }
        $this->assertDatabaseHas('institute_domains', ['id' => $activeHostDomain->id]);
    }
}

