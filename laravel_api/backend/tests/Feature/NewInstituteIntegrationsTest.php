<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Models\User;
use App\Models\ZoomAccount;
use App\Support\Credentials\GeminiCredentials;
use App\Support\Credentials\GoogleCredentials;
use App\Support\Credentials\RecaptchaCredentials;
use App\Support\Credentials\ZoomCredentials;
use App\Support\Integrations;
use App\Support\Redact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewInstituteIntegrationsTest extends TestCase
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
            'name' => 'Second Academy',
            'slug' => 'second-academy',
            'status' => 'active',
        ]);

        $this->adminOne = User::factory()->create([
            'institute_id' => $this->instOne->id,
            'role' => 'admin',
            'email' => 'admin@titprimary.lk',
        ]);

        $this->adminTwo = User::factory()->create([
            'institute_id' => $this->instTwo->id,
            'role' => 'admin',
            'email' => 'admin@secondacademy.lk',
        ]);

        config([
            'integrations.env_fallback_institutes' => [$this->instOne->id],
            'services.gemini.api_key' => 'AIzaSyENV_GEMINI_KEY_TIT_PRIMARY_123',
            'services.recaptcha.site_key' => '6Ld_ENV_SITE_KEY_PRIMARY',
            'services.recaptcha.secret_key' => '6Ld_ENV_SECRET_KEY_PRIMARY_9999',
            'services.recaptcha.score_threshold' => 0.6,
            'services.google.client_id' => 'env-primary-client-id.apps.googleusercontent.com',
            'services.google.client_secret' => 'GOCSPX-ENV_PRIMARY_SECRET_8888',
            'services.google.redirect' => 'https://tit.lk/api/auth/google/callback',
            'services.zoom.client_id' => 'env_zoom_client_primary',
            'services.zoom.client_secret' => 'env_zoom_secret_primary',
            'services.zoom.account_id' => 'env_zoom_account_primary',
        ]);
    }

    public function test_institute_one_falls_back_to_env_for_new_providers(): void
    {
        $resolverOne = Integrations::for($this->instOne->id);

        $gemini = $resolverOne->gemini();
        $this->assertInstanceOf(GeminiCredentials::class, $gemini);
        $this->assertSame('env', $gemini->source);
        $this->assertSame('AIzaSyENV_GEMINI_KEY_TIT_PRIMARY_123', $gemini->apiKey);
        $this->assertTrue($gemini->isConfigured());
        $this->assertSame('••••_123', $gemini->maskedApiKey());

        $recaptcha = $resolverOne->recaptcha();
        $this->assertInstanceOf(RecaptchaCredentials::class, $recaptcha);
        $this->assertSame('env', $recaptcha->source);
        $this->assertSame('6Ld_ENV_SITE_KEY_PRIMARY', $recaptcha->siteKey);
        $this->assertSame('6Ld_ENV_SECRET_KEY_PRIMARY_9999', $recaptcha->secretKey);
        $this->assertSame(0.6, $recaptcha->scoreThreshold);
        $this->assertTrue($recaptcha->isConfigured());
        $this->assertSame('••••9999', $recaptcha->maskedSecretKey());

        $google = $resolverOne->google();
        $this->assertInstanceOf(GoogleCredentials::class, $google);
        $this->assertSame('env', $google->source);
        $this->assertSame('env-primary-client-id.apps.googleusercontent.com', $google->clientId);
        $this->assertSame('GOCSPX-ENV_PRIMARY_SECRET_8888', $google->clientSecret);
        $this->assertTrue($google->isConfigured());
        $this->assertSame('••••8888', $google->maskedClientSecret());

        $zoom = $resolverOne->zoom();
        $this->assertInstanceOf(ZoomCredentials::class, $zoom);
        $this->assertSame('env', $zoom->source);
        $this->assertTrue($zoom->isVirtual);
        $this->assertTrue($zoom->isConfigured());
    }

    public function test_institute_two_without_credentials_gets_unconfigured_and_no_outbound_http(): void
    {
        Http::fake();

        $resolverTwo = Integrations::for($this->instTwo->id);

        $this->assertNull($resolverTwo->gemini());
        $this->assertNull($resolverTwo->recaptcha());
        $this->assertNull($resolverTwo->google());
        $this->assertNull($resolverTwo->zoom());

        Http::assertNothingSent();
    }

    public function test_institute_two_with_own_credentials_uses_them_and_never_leaks_institute_one_keys(): void
    {
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_GEMINI,
            'credentials' => [
                'api_key' => 'AIzaSyINST2_KEY_CUSTOM_4455',
            ],
            'settings' => [],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_RECAPTCHA,
            'credentials' => [
                'secret_key' => '6Ld_INST2_SECRET_KEY_5566',
            ],
            'settings' => [
                'site_key' => '6Ld_INST2_SITE_KEY_5566',
                'score_threshold' => 0.75,
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_GOOGLE,
            'credentials' => [
                'client_secret' => 'GOCSPX-INST2_GOOGLE_SECRET_7788',
            ],
            'settings' => [
                'client_id' => 'inst2-google-client-id.apps.googleusercontent.com',
                'redirect_uri' => 'https://secondacademy.lk/api/auth/google/callback',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $resolverTwo = Integrations::for($this->instTwo->id);

        $gemini = $resolverTwo->gemini();
        $this->assertInstanceOf(GeminiCredentials::class, $gemini);
        $this->assertSame('db', $gemini->source);
        $this->assertSame('AIzaSyINST2_KEY_CUSTOM_4455', $gemini->apiKey);
        $this->assertStringNotContainsString('PRIMARY', $gemini->apiKey);
        $this->assertSame('••••4455', $gemini->maskedApiKey());

        $recaptcha = $resolverTwo->recaptcha();
        $this->assertInstanceOf(RecaptchaCredentials::class, $recaptcha);
        $this->assertSame('db', $recaptcha->source);
        $this->assertSame('6Ld_INST2_SITE_KEY_5566', $recaptcha->siteKey);
        $this->assertSame('6Ld_INST2_SECRET_KEY_5566', $recaptcha->secretKey);
        $this->assertSame(0.75, $recaptcha->scoreThreshold);
        $this->assertStringNotContainsString('PRIMARY', $recaptcha->secretKey);
        $this->assertSame('••••5566', $recaptcha->maskedSecretKey());

        $google = $resolverTwo->google();
        $this->assertInstanceOf(GoogleCredentials::class, $google);
        $this->assertSame('db', $google->source);
        $this->assertSame('inst2-google-client-id.apps.googleusercontent.com', $google->clientId);
        $this->assertSame('GOCSPX-INST2_GOOGLE_SECRET_7788', $google->clientSecret);
        $this->assertStringNotContainsString('PRIMARY', $google->clientSecret);
        $this->assertSame('••••7788', $google->maskedClientSecret());
    }

    public function test_credentials_are_encrypted_at_rest_and_never_serialized(): void
    {
        $secretKey = 'AIzaSySUPER_SECRET_GEMINI_KEY_REST_1122';

        $row = InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_GEMINI,
            'credentials' => [
                'api_key' => $secretKey,
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $rawRow = DB::table('institute_integrations')->where('id', $row->id)->first();
        $this->assertNotNull($rawRow);
        $this->assertStringNotContainsString($secretKey, $rawRow->credentials);

        $json = json_encode($row->toArray());
        $this->assertStringNotContainsString($secretKey, (string) $json);
        $this->assertArrayNotHasKey('credentials', $row->toArray());

        $masked = $row->maskedCredentials();
        $this->assertSame('••••1122', $masked['api_key']);
    }

    public function test_integrations_status_command_outputs_all_seven_providers(): void
    {
        Artisan::call('integrations:status', ['--institute' => $this->instOne->id]);
        $output = Artisan::output();

        $this->assertStringContainsString('payhere', $output);
        $this->assertStringContainsString('whatsapp', $output);
        $this->assertStringContainsString('mail', $output);
        $this->assertStringContainsString('gemini', $output);
        $this->assertStringContainsString('zoom', $output);
        $this->assertStringContainsString('recaptcha', $output);
        $this->assertStringContainsString('google', $output);

        $this->assertStringNotContainsString('AIzaSyENV_GEMINI_KEY_TIT_PRIMARY_123', $output);
        $this->assertStringNotContainsString('GOCSPX-ENV_PRIMARY_SECRET_8888', $output);
        $this->assertStringNotContainsString('6Ld_ENV_SECRET_KEY_PRIMARY_9999', $output);
    }

    public function test_integrations_import_env_dry_run_and_execution(): void
    {
        Artisan::call('integrations:import-env', [
            'institute' => $this->instTwo->id,
            '--dry-run' => true,
        ]);
        $dryRunOutput = Artisan::output();

        $this->assertStringContainsString('DRY RUN', $dryRunOutput);
        $this->assertStringContainsString('gemini', $dryRunOutput);
        $this->assertStringContainsString('recaptcha', $dryRunOutput);
        $this->assertStringContainsString('google', $dryRunOutput);

        $this->assertNull(
            InstituteIntegration::withoutGlobalScopes()
                ->where('institute_id', $this->instTwo->id)
                ->where('provider', InstituteIntegration::PROVIDER_GEMINI)
                ->first()
        );

        Artisan::call('integrations:import-env', [
            'institute' => $this->instTwo->id,
        ]);
        $importOutput = Artisan::output();

        $this->assertStringContainsString('IMPORT COMPLETE', $importOutput);

        $importedGemini = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $this->instTwo->id)
            ->where('provider', InstituteIntegration::PROVIDER_GEMINI)
            ->first();

        $this->assertNotNull($importedGemini);
        $this->assertSame(InstituteIntegration::STATUS_CONFIGURED, $importedGemini->status);
        $this->assertSame('AIzaSyENV_GEMINI_KEY_TIT_PRIMARY_123', $importedGemini->credentials['api_key']);
    }
}
