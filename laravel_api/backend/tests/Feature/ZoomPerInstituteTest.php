<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\InstituteIntegration;
use App\Models\User;
use App\Models\ZoomAccount;
use App\Services\ZoomService;
use App\Support\Integrations;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZoomPerInstituteTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $instOne;
    protected Institute $instTwo;
    protected User $adminOne;
    protected User $adminTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instOne = Institute::create([
            'name' => 'TiT Primary',
            'slug' => 'tit-primary',
            'status' => 'active',
        ]);

        $this->instTwo = Institute::create([
            'name' => 'Colombo Campus',
            'slug' => 'colombo-campus',
            'status' => 'active',
        ]);

        InstituteDomain::create([
            'institute_id' => $this->instOne->id,
            'domain' => 'primary.test',
            'status' => 'active',
        ]);

        InstituteDomain::create([
            'institute_id' => $this->instTwo->id,
            'domain' => 'colombo.test',
            'status' => 'active',
        ]);

        $this->adminOne = User::factory()->create([
            'institute_id' => $this->instOne->id,
            'role' => 'admin',
        ]);

        $this->adminTwo = User::factory()->create([
            'institute_id' => $this->instTwo->id,
            'role' => 'admin',
        ]);

        config([
            'integrations.env_fallback_institutes' => [$this->instOne->id],
            'services.zoom.account_id' => 'ENV_ZOOM_ACC_ID_111',
            'services.zoom.client_id' => 'ENV_ZOOM_CLIENT_ID_111',
            'services.zoom.client_secret' => 'ENV_ZOOM_CLIENT_SECRET_111',
        ]);
    }

    public function test_encrypted_column_is_not_plaintext_in_db_and_legacy_plaintext_row_still_decrypts_through_cast(): void
    {
        // 1. Model creation encrypts client_secret at rest
        $account = ZoomAccount::create([
            'institute_id' => $this->instTwo->id,
            'email' => 'colombo@zoom.us',
            'account_id' => 'ACC_COLOMBO_222',
            'client_id' => 'CLIENT_COLOMBO_222',
            'client_secret' => 'super_secret_plain_colombo_9999',
            'max_concurrent' => 3,
            'is_active' => true,
        ]);

        $rawDbValue = DB::table('zoom_accounts')->where('id', $account->id)->value('client_secret');
        $this->assertNotEquals('super_secret_plain_colombo_9999', $rawDbValue);
        $this->assertSame('super_secret_plain_colombo_9999', Crypt::decryptString($rawDbValue));

        // Eloquent read decrypts correctly
        $fresh = ZoomAccount::find($account->id);
        $this->assertSame('super_secret_plain_colombo_9999', $fresh->client_secret);
        $this->assertSame('••••9999', $fresh->masked_secret);

        // Hidden in toArray / toJson
        $this->assertArrayNotHasKey('client_secret', $fresh->toArray());
        $this->assertStringNotContainsString('super_secret_plain_colombo_9999', $fresh->toJson());

        // 2. Legacy plaintext row directly inserted in DB
        $legacyId = DB::table('zoom_accounts')->insertGetId([
            'institute_id' => $this->instTwo->id,
            'email' => 'legacy@zoom.us',
            'account_id' => 'ACC_LEGACY',
            'client_id' => 'CLIENT_LEGACY',
            'client_secret' => 'unencrypted_legacy_secret_7777',
            'max_concurrent' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Backward-compatible read: legacy row still decrypts/reads properly through the custom cast
        $legacyAccount = ZoomAccount::withoutGlobalScopes()->find($legacyId);
        $this->assertSame('unencrypted_legacy_secret_7777', $legacyAccount->client_secret);
        $this->assertSame('••••7777', $legacyAccount->masked_secret);

        // 3. Dry-run command prints counts only and does not write
        $this->artisan('integrations:encrypt-zoom-secrets', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame('unencrypted_legacy_secret_7777', DB::table('zoom_accounts')->where('id', $legacyId)->value('client_secret'));

        // 4. Actual command run encrypts the legacy row
        $this->artisan('integrations:encrypt-zoom-secrets')
            ->assertExitCode(0);

        $rawAfterEncrypt = DB::table('zoom_accounts')->where('id', $legacyId)->value('client_secret');
        $this->assertNotEquals('unencrypted_legacy_secret_7777', $rawAfterEncrypt);
        $this->assertSame('unencrypted_legacy_secret_7777', Crypt::decryptString($rawAfterEncrypt));

        // Reading through Eloquent still returns plain value
        $this->assertSame('unencrypted_legacy_secret_7777', ZoomAccount::withoutGlobalScopes()->find($legacyId)->client_secret);

        // 5. Running again is completely idempotent
        $this->artisan('integrations:encrypt-zoom-secrets')
            ->assertExitCode(0);
    }

    public function test_other_institutes_accounts_are_never_listed_or_used(): void
    {
        $accOne = ZoomAccount::create([
            'institute_id' => $this->instOne->id,
            'email' => 'inst1@zoom.us',
            'account_id' => 'ACC1',
            'client_id' => 'CLIENT1',
            'client_secret' => 'SECRET1',
            'max_concurrent' => 5,
            'is_active' => true,
        ]);

        $accTwo = ZoomAccount::create([
            'institute_id' => $this->instTwo->id,
            'email' => 'inst2@zoom.us',
            'account_id' => 'ACC2',
            'client_id' => 'CLIENT2',
            'client_secret' => 'SECRET2',
            'max_concurrent' => 5,
            'is_active' => true,
        ]);

        $service = app(ZoomService::class);

        // For Institute 2: getAvailableAccount must ONLY return Account 2, never Account 1
        $chosenForInst2 = $service->forInstitute($this->instTwo->id)->getAvailableAccount(now());
        $this->assertNotNull($chosenForInst2);
        $this->assertSame($accTwo->id, $chosenForInst2->id);
        $this->assertSame('inst2@zoom.us', $chosenForInst2->email);

        // For Institute 1: getAvailableAccount returns Account 1
        $chosenForInst1 = $service->forInstitute($this->instOne->id)->getAvailableAccount(now());
        $this->assertNotNull($chosenForInst1);
        $this->assertSame($accOne->id, $chosenForInst1->id);

        // listUsers for inst 2 returns only inst 2
        $users2 = $service->listUsers($this->instTwo->id);
        $this->assertCount(1, $users2);
        $this->assertSame('inst2@zoom.us', $users2[0]['email']);
    }

    public function test_schedule_creation_for_institute_two_with_no_accounts_returns_clear_error_and_zero_http(): void
    {
        Http::fake();

        // Inst 2 has zero zoom accounts and is NOT in env fallback institutes
        Tenant::run($this->instTwo, function () {
            $response = $this->actingAs($this->adminTwo)
                ->from('http://colombo.test/admin/zoom/create')
                ->post('http://colombo.test/admin/zoom', [
                    'title' => 'Biology Revision',
                    'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                    'subject' => 'Biology',
                    'grade' => '12',
                    'medium' => 'english',
                ]);

            $response->assertRedirect('http://colombo.test/admin/zoom/create');
            $response->assertSessionHas('error', 'Add a Zoom account under Settings → Zoom Accounts');
        });

        // Zero outbound HTTP calls were sent!
        Http::assertNothingSent();
    }

    public function test_institute_two_cannot_receive_institute_one_cached_token(): void
    {
        $accOne = ZoomAccount::create([
            'institute_id' => $this->instOne->id,
            'email' => 'inst1@zoom.us',
            'account_id' => 'ACC1',
            'client_id' => 'CLIENT1',
            'client_secret' => 'SECRET1',
            'is_active' => true,
        ]);

        $accTwo = ZoomAccount::create([
            'institute_id' => $this->instTwo->id,
            'email' => 'inst2@zoom.us',
            'account_id' => 'ACC2',
            'client_id' => 'CLIENT2',
            'client_secret' => 'SECRET2',
            'is_active' => true,
        ]);

        // Seed token for Institute 1 account 1
        Cache::put("zoom:token:{$this->instOne->id}:{$accOne->id}", 'TOKEN_INST_ONE_SUPER_SECRET', 3600);

        $capturedAuth = [];

        Http::fake([
            'https://zoom.us/oauth/token*' => function (Request $request) use (&$capturedAuth) {
                $capturedAuth[] = $request->header('Authorization')[0] ?? null;
                return Http::response([
                    'access_token' => 'TOKEN_INST_TWO_FRESH_XYZ',
                    'token_type' => 'bearer',
                    'expires_in' => 3599,
                ], 200);
            },
        ]);

        $service = app(ZoomService::class);

        // Institute 2 requests token
        $tokenInstTwo = $service->forInstitute($this->instTwo->id)->getAccessToken($accTwo);

        // Must NOT receive Institute 1's cached token!
        $this->assertNotSame('TOKEN_INST_ONE_SUPER_SECRET', $tokenInstTwo);
        $this->assertSame('TOKEN_INST_TWO_FRESH_XYZ', $tokenInstTwo);

        // HTTP call was made with Institute 2 credentials
        $this->assertCount(1, $capturedAuth);
        $this->assertSame('Basic ' . base64_encode('CLIENT2:SECRET2'), $capturedAuth[0]);

        // Cache key stored with institute 2 and account 2
        $this->assertSame('TOKEN_INST_TWO_FRESH_XYZ', Cache::get("zoom:token:{$this->instTwo->id}:{$accTwo->id}"));
    }

    public function test_test_connection_button_success_updates_integration_status(): void
    {
        $acc = ZoomAccount::create([
            'institute_id' => $this->instTwo->id,
            'email' => 'zoom-test@colombo.lk',
            'account_id' => 'ACC_TEST',
            'client_id' => 'CLIENT_TEST',
            'client_secret' => 'SECRET_TEST_1234',
            'is_active' => true,
        ]);

        Http::fake([
            'https://zoom.us/oauth/token*' => Http::response([
                'access_token' => 'VALID_ZOOM_TOKEN',
                'token_type' => 'bearer',
            ], 200),
            'https://api.zoom.us/v2/users/me' => Http::response([
                'id' => 'zoom_user_1',
                'email' => 'zoom-test@colombo.lk',
            ], 200),
        ]);

        $response = $this->actingAs($this->adminTwo)
            ->postJson("/admin/zoom-accounts/{$acc->id}/test");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertStringContainsString('verified', $response->json('message'));

        $status = Integrations::for($this->instTwo->id)->statusSummary();
        $this->assertSame(InstituteIntegration::STATUS_VERIFIED, $status['zoom']['status']);
    }

    public function test_test_connection_button_failure_updates_integration_status_and_masks_errors(): void
    {
        $acc = ZoomAccount::create([
            'institute_id' => $this->instTwo->id,
            'email' => 'zoom-fail@colombo.lk',
            'account_id' => 'ACC_FAIL',
            'client_id' => 'CLIENT_FAIL',
            'client_secret' => 'SECRET_FAIL_1234',
            'is_active' => true,
        ]);

        Http::fake([
            'https://zoom.us/oauth/token*' => Http::response([
                'code' => 124,
                'message' => 'Invalid client_secret SECRET_FAIL_1234 provided.',
            ], 401),
        ]);

        $failResponse = $this->actingAs($this->adminTwo)
            ->postJson("/admin/zoom-accounts/{$acc->id}/test");

        $failResponse->assertStatus(422);
        $failResponse->assertJson([
            'success' => false,
        ]);
        // Secret must NEVER leak in the error message
        $this->assertStringNotContainsString('SECRET_FAIL_1234', $failResponse->json('message'));

        $statusAfterFail = Integrations::for($this->instTwo->id)->statusSummary();
        $this->assertSame(InstituteIntegration::STATUS_FAILING, $statusAfterFail['zoom']['status']);
    }

    public function test_edit_zoom_account_form_leaves_secret_unchanged_when_field_is_empty(): void
    {
        $acc = ZoomAccount::create([
            'institute_id' => $this->instTwo->id,
            'email' => 'zoom-edit@colombo.lk',
            'account_id' => 'ACC_ORIG',
            'client_id' => 'CLIENT_ORIG',
            'client_secret' => 'ORIGINAL_SECRET_5555',
            'max_concurrent' => 3,
            'is_active' => true,
        ]);

        // Submit edit without client_secret
        $response = $this->actingAs($this->adminTwo)
            ->put("/admin/zoom-accounts/{$acc->id}", [
                'email' => 'zoom-edit@colombo.lk',
                'account_id' => 'ACC_UPDATED',
                'client_id' => 'CLIENT_UPDATED',
                'client_secret' => '', // empty!
                'max_concurrent' => 4,
                'is_active' => '1',
            ]);

        $response->assertRedirect('/admin/zoom-accounts');

        $fresh = $acc->fresh();
        $this->assertSame('ACC_UPDATED', $fresh->account_id);
        $this->assertSame('CLIENT_UPDATED', $fresh->client_id);
        // Secret is preserved!
        $this->assertSame('ORIGINAL_SECRET_5555', $fresh->client_secret);
    }
}
