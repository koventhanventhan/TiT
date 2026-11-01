<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class InstituteDomainCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute1;
    protected Institute $institute2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute1 = Institute::create([
            'name' => 'First Institute',
            'slug' => 'first-inst',
            'status' => 'active',
        ]);

        $this->institute2 = Institute::create([
            'name' => 'Second Institute',
            'slug' => 'second-inst',
            'status' => 'active',
        ]);
    }

    /**
     * Test institutes:add-domain adds and normalizes domains.
     */
    public function test_add_domain_normalizes_and_binds_to_institute(): void
    {
        $exitCode = Artisan::call('institutes:add-domain', [
            'institute' => $this->institute1->id,
            'domain' => 'https://Sub.My-Domain.lk:8080/path/test',
        ]);

        $this->assertEquals(0, $exitCode);

        $domainRecord = InstituteDomain::where('domain', 'sub.my-domain.lk')->first();
        $this->assertNotNull($domainRecord);
        $this->assertEquals($this->institute1->id, $domainRecord->institute_id);
        // First domain should default to primary
        $this->assertTrue($domainRecord->is_primary);
    }

    /**
     * Test institutes:add-domain with --primary safely reassigns primary flag.
     */
    public function test_add_domain_with_primary_reassigns_primary_flag(): void
    {
        // Add domain 1 as primary
        Artisan::call('institutes:add-domain', [
            'institute' => $this->institute1->id,
            'domain' => 'alpha.lk',
            '--primary' => true,
        ]);

        $dom1 = InstituteDomain::where('domain', 'alpha.lk')->first();
        $this->assertTrue($dom1->is_primary);

        // Add domain 2 as new primary
        Artisan::call('institutes:add-domain', [
            'institute' => $this->institute1->id,
            'domain' => 'alpha-new.lk',
            '--primary' => true,
        ]);

        $dom1->refresh();
        $dom2 = InstituteDomain::where('domain', 'alpha-new.lk')->first();

        $this->assertFalse($dom1->is_primary);
        $this->assertTrue($dom2->is_primary);
    }

    /**
     * Test institutes:add-domain rejects domain owned by another institute.
     */
    public function test_add_domain_rejects_domain_already_owned_by_another_institute(): void
    {
        Artisan::call('institutes:add-domain', [
            'institute' => $this->institute1->id,
            'domain' => 'shared-domain.lk',
        ]);

        $exitCode = Artisan::call('institutes:add-domain', [
            'institute' => $this->institute2->id,
            'domain' => 'shared-domain.lk',
        ]);

        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('already assigned to Institute #1', $output);
    }

    /**
     * Test institutes:list-domains renders tabular list of institutes.
     */
    public function test_list_domains_displays_table(): void
    {
        InstituteDomain::create([
            'institute_id' => $this->institute1->id,
            'domain' => 'first.lk',
            'is_primary' => true,
            'verified_at' => now(),
            'verification_token' => 'tok1',
        ]);

        InstituteDomain::create([
            'institute_id' => $this->institute1->id,
            'domain' => 'www.first.lk',
            'is_primary' => false,
            'verified_at' => now(),
            'verification_token' => 'tok2',
        ]);

        $exitCode = Artisan::call('institutes:list-domains');
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('First Institute', $output);
        $this->assertStringContainsString('first.lk', $output);
        $this->assertStringContainsString('www.first.lk', $output);
    }

    /**
     * Test institutes:check-domain diagnoses domain with mocked DNS and HTTP.
     */
    public function test_check_domain_succeeds_with_mocked_dns(): void
    {
        InstituteDomain::create([
            'institute_id' => $this->institute1->id,
            'domain' => 'check-test.lk',
            'is_primary' => true,
            'verified_at' => now(),
            'verification_token' => 'tok_check',
        ]);

        config([
            'tenant.mock_dns.check-test.lk' => ['192.168.1.100'],
            'tenant.server_ips' => ['192.168.1.100'],
            'tenant.mock_http.check-test.lk' => [
                'success' => true,
                'status' => 200,
                'url' => 'https://check-test.lk/api/settings',
                'institute_name' => 'First Institute',
                'ssl' => true,
                'message' => 'OK',
            ],
        ]);

        $exitCode = Artisan::call('institutes:check-domain', [
            'domain' => 'check-test.lk',
        ]);

        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Mapped to Institute #1 (First Institute)', $output);
        $this->assertStringContainsString('Points correctly to server IP', $output);
        $this->assertStringContainsString('HTTPS connection active and valid', $output);
    }
}
