<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\InstituteIntegration;
use App\Models\User;
use App\Support\Integrations;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RecaptchaAndGooglePerInstituteTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $instOne;
    protected Institute $instTwo;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.recaptcha.verify_in_tests' => true]);

        RateLimiter::clear('recaptcha_unconfigured:*');

        $this->instOne = Institute::create([
            'name' => 'TiT Primary',
            'slug' => 'tit-primary',
            'status' => 'active',
            'rag_enabled' => true,
            'public_assistant_enabled' => true,
        ]);

        $this->instTwo = Institute::create([
            'name' => 'Colombo Campus',
            'slug' => 'colombo-campus',
            'status' => 'active',
            'rag_enabled' => true,
            'public_assistant_enabled' => true,
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

        config([
            'integrations.env_fallback_institutes' => [$this->instOne->id],
            // Env platform credentials (available only to instOne via fallback)
            'services.recaptcha.site_key' => '6L_ENV_SITE_KEY_11111111111',
            'services.recaptcha.secret_key' => '6L_ENV_SECRET_KEY_11111111111',
            'services.google.client_id' => 'ENV_GOOGLE_CLIENT_ID_111',
            'services.google.client_secret' => 'ENV_GOOGLE_CLIENT_SECRET_111',
            'services.gemini.api_key' => 'AIzaSyENV_GEMINI_KEY_111',
            'rag.public_enabled' => true,
        ]);
    }

    public function test_institute_two_without_recaptcha_keys_runs_no_captcha_http_applies_strict_throttle_and_disables_public_assistant(): void
    {
        Http::fake();

        // 1. Five submissions without reCAPTCHA token succeed through VerifyRecaptcha middleware
        for ($i = 1; $i <= 5; $i++) {
            $res = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
                ->postJson('http://colombo.test/api/auth/send-verification-otp', [
                    'email' => "user{$i}@example.com",
                ]);

            // Passes middleware (even if email validation fails in controller, it passed 429 and 422 recaptcha required)
            $this->assertNotEquals(429, $res->status(), "Attempt {$i} unexpectedly throttled");
            $this->assertNotEquals('reCAPTCHA verification is required.', $res->json('errors.recaptcha.0'));
        }

        // 2. Sixth submission from same IP hits the strict unconfigured throttle (429)
        $sixth = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->postJson('http://colombo.test/api/auth/send-verification-otp', [
                'email' => 'user6@example.com',
            ]);

        $sixth->assertStatus(429);
        $this->assertSame('too_many_requests', $sixth->json('code'));
        $this->assertStringContainsString('Too many requests', $sixth->json('message'));

        // Zero outbound HTTP calls were sent to Google reCAPTCHA
        Http::assertNothingSent();

        // 3. Public AI assistant chat stays disabled for Institute 2 without reCAPTCHA (costs money)
        // Give Inst 2 Gemini key so Gemini check passes, isolating the reCAPTCHA requirement
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_GEMINI,
            'credentials' => [
                'api_key' => 'AIzaSyCOLOMBO_KEY_2222',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $chatRes = $this->postJson('http://colombo.test/api/assistant/chat', [
            'question' => 'What are your hours?',
        ]);

        $chatRes->assertStatus(403);
        $this->assertSame('recaptcha_not_configured', $chatRes->json('code'));
        $this->assertFalse($chatRes->json('public_enabled'));
    }

    public function test_institute_two_with_recaptcha_keys_verifies_with_its_own_secret_and_enforces_score_threshold(): void
    {
        // Configure custom reCAPTCHA credentials for Institute 2
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_RECAPTCHA,
            'credentials' => [
                'site_key' => '6L_CUSTOM_SITE_KEY_22222222222',
                'secret_key' => '6L_CUSTOM_SECRET_KEY_22222222222',
                'score_threshold' => 0.70,
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $capturedSecrets = [];

        // 1. Missing recaptcha token returns 422
        $missingTokenRes = $this->postJson('http://colombo.test/api/auth/send-verification-otp', [
            'email' => 'student@colombo.test',
        ]);
        $missingTokenRes->assertStatus(422);
        $this->assertSame('reCAPTCHA verification is required.', $missingTokenRes->json('errors.recaptcha.0'));

        // 2. Mock Google verification endpoint: first call returns 0.45 (low score), second call returns 0.95 (high score)
        $callCount = 0;
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => function (Request $request) use (&$capturedSecrets, &$callCount) {
                $capturedSecrets[] = $request['secret'] ?? null;
                $callCount++;
                $score = ($callCount === 1) ? 0.45 : 0.95;

                return Http::response([
                    'success' => true,
                    'score' => $score,
                    'action' => 'submit',
                ], 200);
            },
        ]);

        $lowScoreRes = $this->postJson('http://colombo.test/api/auth/send-verification-otp', [
            'email' => 'student@colombo.test',
            'recaptcha_token' => 'VALID_TOKEN_FROM_CLIENT',
        ]);

        $lowScoreRes->assertStatus(422);
        $this->assertStringContainsString('suspicious activity', $lowScoreRes->json('message'));

        // Assert outbound HTTP call carried Institute 2's secret, NEVER Institute 1's secret!
        $this->assertNotEmpty($capturedSecrets);
        $this->assertSame('6L_CUSTOM_SECRET_KEY_22222222222', $capturedSecrets[0]);

        // 3. High score (0.95 >= 0.70) passes reCAPTCHA check
        $highScoreRes = $this->postJson('http://colombo.test/api/auth/send-verification-otp', [
            'email' => 'student@colombo.test',
            'recaptcha_token' => 'HIGH_SCORE_TOKEN',
        ]);

        // Passed captcha check
        $this->assertNotEquals('reCAPTCHA verification failed. Please try again.', $highScoreRes->json('errors.recaptcha.0'));
        $this->assertNotEquals('Suspicious activity detected. Please try again.', $highScoreRes->json('errors.recaptcha.0'));
    }

    public function test_api_settings_never_contains_secret_and_returns_site_key_and_flags_for_current_host(): void
    {
        // Inst 2 has its own reCAPTCHA key but NO Google credentials
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_RECAPTCHA,
            'credentials' => [
                'site_key' => '6L_CUSTOM_SITE_KEY_COLOMBO',
                'secret_key' => '6L_CUSTOM_SECRET_KEY_COLOMBO',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        // Request on colombo.test host
        $resColombo = $this->getJson('http://colombo.test/api/settings');
        $resColombo->assertOk();

        // 1. Site key matches colombo, not primary
        $this->assertSame('6L_CUSTOM_SITE_KEY_COLOMBO', $resColombo->json('recaptcha_site_key'));
        $this->assertTrue($resColombo->json('recaptcha_available'));

        // 2. Google sign-in is disabled because Institute 2 has no Google credentials
        $this->assertFalse($resColombo->json('google_login_available'));

        // 3. Secrets NEVER appear in /api/settings
        $this->assertArrayNotHasKey('recaptcha_secret_key', $resColombo->json());
        $this->assertArrayNotHasKey('google_client_secret', $resColombo->json());
        $this->assertArrayNotHasKey('gemini_api_key', $resColombo->json());
        $this->assertArrayNotHasKey('zoom_client_secret', $resColombo->json());
        $this->assertStringNotContainsString('6L_CUSTOM_SECRET_KEY_COLOMBO', $resColombo->content());
        $this->assertStringNotContainsString('6L_ENV_SECRET_KEY_11111111111', $resColombo->content());

        // Request on primary.test host (env fallback institute)
        $resPrimary = $this->getJson('http://primary.test/api/settings');
        $resPrimary->assertOk();

        // Site key is primary fallback
        $this->assertSame('6L_ENV_SITE_KEY_11111111111', $resPrimary->json('recaptcha_site_key'));
        // Google is available on primary via env fallback
        $this->assertTrue($resPrimary->json('google_login_available'));
        $this->assertStringNotContainsString('ENV_GOOGLE_CLIENT_SECRET_111', $resPrimary->content());
    }

    public function test_google_redirect_and_callback_use_institute_credentials_and_host_redirect_uri(): void
    {
        // Institute 2 with custom Google credentials
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_GOOGLE,
            'credentials' => [
                'client_id' => 'GOOGLE_CLIENT_ID_COLOMBO_222',
                'client_secret' => 'GOCSPX-SECRET_COLOMBO_222',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        // 1. GET redirect on colombo.test host
        $redirectRes = $this->get('http://colombo.test/api/auth/google/redirect');
        $redirectRes->assertRedirect();
        $targetUrl = $redirectRes->headers->get('Location');

        // Dynamic credentials & host redirect URI configured
        $this->assertStringContainsString('client_id=GOOGLE_CLIENT_ID_COLOMBO_222', $targetUrl);
        $this->assertStringContainsString('redirect_uri=' . urlencode('http://colombo.test/api/auth/google/callback'), $targetUrl);
        $this->assertStringNotContainsString('ENV_GOOGLE_CLIENT_ID_111', $targetUrl);

        // 2. If Google credentials are NOT configured for an institute, returns 422
        $instThree = Institute::create([
            'name' => 'Kandy Branch',
            'slug' => 'kandy-branch',
            'status' => 'active',
        ]);
        InstituteDomain::create([
            'institute_id' => $instThree->id,
            'domain' => 'kandy.test',
            'status' => 'active',
        ]);

        $unconfiguredRes = $this->getJson('http://kandy.test/api/auth/google/redirect');
        $unconfiguredRes->assertStatus(422);
        $this->assertSame('google_not_configured', $unconfiguredRes->json('code'));
    }
}
