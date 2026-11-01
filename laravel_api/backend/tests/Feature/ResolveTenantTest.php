<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResolveTenantTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute1;
    protected Institute $institute2;
    protected InstituteDomain $domain1;
    protected InstituteDomain $domain2;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // Institute 1 (Default / TiT)
        $this->institute1 = Institute::create([
            'name' => 'TiT Education Institute',
            'slug' => 'tit-jaffna',
            'status' => 'active',
        ]);

        $this->domain1 = InstituteDomain::create([
            'institute_id' => $this->institute1->id,
            'domain' => 'titjaffna.lk',
            'is_primary' => true,
            'verified_at' => now(),
            'verification_token' => 'token1',
        ]);

        InstituteDomain::create([
            'institute_id' => $this->institute1->id,
            'domain' => 'www.titjaffna.lk',
            'is_primary' => false,
            'verified_at' => now(),
            'verification_token' => 'token1_www',
        ]);

        // Institute 2 (Second Institute)
        $this->institute2 = Institute::create([
            'name' => 'ABC Tuition Centre',
            'slug' => 'abc-tuition',
            'status' => 'active',
        ]);

        $this->domain2 = InstituteDomain::create([
            'institute_id' => $this->institute2->id,
            'domain' => 'abc-tuition.lk',
            'is_primary' => true,
            'verified_at' => now(),
            'verification_token' => 'token2',
        ]);

        InstituteDomain::create([
            'institute_id' => $this->institute2->id,
            'domain' => 'www.abc-tuition.lk',
            'is_primary' => false,
            'verified_at' => now(),
            'verification_token' => 'token2_www',
        ]);

        // Settings
        SiteSetting::setForInstitute('site_name', 'TiT Official Portal', $this->institute1->id);
        SiteSetting::setForInstitute('site_name', 'ABC Tuition Portal', $this->institute2->id);
    }

    /**
     * Test 1 & 2: GET /api/settings per host returns that institute's settings.
     */
    public function test_api_settings_per_host_returns_correct_institute_settings(): void
    {
        // Host 1: titjaffna.lk
        $resp1 = $this->withServerVariables(['HTTP_HOST' => 'titjaffna.lk'])
            ->getJson('http://titjaffna.lk/api/settings');

        $resp1->assertStatus(200);
        $resp1->assertJsonPath('site_name', 'TiT Official Portal');

        // Host 2: abc-tuition.lk
        $resp2 = $this->withServerVariables(['HTTP_HOST' => 'abc-tuition.lk'])
            ->getJson('http://abc-tuition.lk/api/settings');

        $resp2->assertStatus(200);
        $resp2->assertJsonPath('site_name', 'ABC Tuition Portal');
    }

    /**
     * Test 3: Anonymous caller sending X-Institute-Id: 2 to host 1 in production still gets institute 1.
     */
    public function test_production_ignores_x_institute_id_header(): void
    {
        config(['app.env' => 'production']);
        $this->app['env'] = 'production';

        $resp = $this->withServerVariables(['HTTP_HOST' => 'titjaffna.lk'])
            ->withHeaders(['X-Institute-Id' => (string)$this->institute2->id])
            ->getJson('http://titjaffna.lk/api/settings');

        $resp->assertStatus(200);
        // Must return Institute 1's settings despite header
        $resp->assertJsonPath('site_name', 'TiT Official Portal');
    }

    /**
     * Test 4: Unknown host -> 404 JSON {"message":"Unknown institute","code":"tenant_not_found"}.
     */
    public function test_unknown_host_returns_404_json_in_production(): void
    {
        config(['app.env' => 'production']);
        $this->app['env'] = 'production';

        $resp = $this->withServerVariables(['HTTP_HOST' => 'unknown-random-domain.com'])
            ->getJson('http://unknown-random-domain.com/api/settings');

        $resp->assertStatus(404);
        $resp->assertJson([
            'message' => 'Unknown institute',
            'code' => 'tenant_not_found',
        ]);
    }

    /**
     * Test 5: www. alias works for resolved institute.
     */
    public function test_www_alias_resolves_correct_institute(): void
    {
        $resp = $this->withServerVariables(['HTTP_HOST' => 'www.abc-tuition.lk'])
            ->getJson('http://www.abc-tuition.lk/api/settings');

        $resp->assertStatus(200);
        $resp->assertJsonPath('site_name', 'ABC Tuition Portal');
    }

    /**
     * Test 6: Student of institute 1 using token on host 2 -> 403 context mismatch.
     */
    public function test_student_token_on_wrong_host_returns_403_mismatch(): void
    {
        $student1 = User::create([
            'name' => 'Student One',
            'email' => 'student1@test.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'institute_id' => $this->institute1->id,
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
        ]);

        $token = $student1->createToken('test-token')->plainTextToken;

        // Attempting to access protected API route on host 2
        $resp = $this->withServerVariables(['HTTP_HOST' => 'abc-tuition.lk'])
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('http://abc-tuition.lk/api/student/payment-status');

        $resp->assertStatus(403);
        $resp->assertJson([
            'message' => 'Institute context mismatch',
        ]);
    }

    /**
     * Test 7: Login on wrong host fails like a wrong password (same 401 generic message).
     */
    public function test_login_on_wrong_host_fails_like_wrong_password(): void
    {
        User::create([
            'name' => 'Student One',
            'email' => 'student1@test.com',
            'password' => Hash::make('secret_password'),
            'role' => 'user',
            'institute_id' => $this->institute1->id,
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
        ]);

        // Attempt login on Host 2 with valid password for student1
        $resp = $this->withServerVariables(['HTTP_HOST' => 'abc-tuition.lk'])
            ->postJson('http://abc-tuition.lk/api/auth/login', [
                'usernameOrEmail' => 'student1@test.com',
                'password' => 'secret_password',
            ]);

        $resp->assertStatus(401);
        $resp->assertJson([
            'message' => 'Invalid login credentials',
        ]);

        // Same response when password actually wrong on Host 1
        $respWrongPass = $this->withServerVariables(['HTTP_HOST' => 'titjaffna.lk'])
            ->postJson('http://titjaffna.lk/api/auth/login', [
                'usernameOrEmail' => 'student1@test.com',
                'password' => 'completely_wrong_pass',
            ]);

        $respWrongPass->assertStatus(401);
        $respWrongPass->assertJson([
            'message' => 'Invalid login credentials',
        ]);
    }

    /**
     * Test 8: Suspended institute -> 403.
     */
    public function test_suspended_institute_returns_403(): void
    {
        $this->institute2->update(['status' => 'suspended']);
        Cache::flush();

        $resp = $this->withServerVariables(['HTTP_HOST' => 'abc-tuition.lk'])
            ->getJson('http://abc-tuition.lk/api/settings');

        $resp->assertStatus(403);
        $resp->assertJson([
            'message' => 'Institute access is suspended',
            'status' => 'suspended',
        ]);
    }

    /**
     * Test 9: titjaffna.lk row missing -> safety net still resolves to institute 1.
     */
    public function test_titjaffna_row_missing_safety_net_resolves_institute_1(): void
    {
        config(['app.env' => 'production']);
        $this->app['env'] = 'production';

        // Delete domain rows for Institute 1
        InstituteDomain::where('institute_id', $this->institute1->id)->delete();
        Cache::flush();

        $resp = $this->withServerVariables(['HTTP_HOST' => 'titjaffna.lk'])
            ->getJson('http://titjaffna.lk/api/settings');

        $resp->assertStatus(200);
        $resp->assertJsonPath('site_name', 'TiT Official Portal');
    }
}
