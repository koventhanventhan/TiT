<?php

namespace Tests\Feature;

use App\Filament\SuperAdmin\Pages\InstituteIntegrationsOverview;
use App\Models\AssistantVisitorLog;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\InstituteIntegration;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAiSecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $instituteA;
    protected Institute $instituteB;
    protected User $adminA;
    protected User $adminB;
    protected User $teacherA;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instituteA = Institute::create([
            'name' => 'Colombo Academy',
            'slug' => 'colombo-academy',
            'status' => 'active',
        ]);
        InstituteDomain::create([
            'institute_id' => $this->instituteA->id,
            'domain' => 'colombo.academy.lk',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $this->instituteB = Institute::create([
            'name' => 'Kandy Institute',
            'slug' => 'kandy-institute',
            'status' => 'active',
        ]);
        InstituteDomain::create([
            'institute_id' => $this->instituteB->id,
            'domain' => 'kandy.academy.lk',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        config(['integrations.env_fallback_institutes' => []]);

        $this->adminA = User::factory()->create([
            'institute_id' => $this->instituteA->id,
            'role' => 'admin',
            'email' => 'admin@colombo.academy.lk',
            'full_name' => 'Colombo Admin',
        ]);

        $this->adminB = User::factory()->create([
            'institute_id' => $this->instituteB->id,
            'role' => 'admin',
            'email' => 'admin@kandy.academy.lk',
            'full_name' => 'Kandy Admin',
        ]);

        $this->teacherA = User::factory()->create([
            'institute_id' => $this->instituteA->id,
            'role' => 'teacher',
            'email' => 'teacher@colombo.academy.lk',
            'full_name' => 'Colombo Teacher',
        ]);

        $this->superAdmin = User::factory()->create([
            'institute_id' => $this->instituteA->id,
            'role' => 'super_admin',
            'email' => 'superadmin@platform.lk',
            'full_name' => 'Super Administrator',
        ]);
    }

    public function test_only_institute_admin_role_can_access_ai_security_settings(): void
    {
        // Guest -> redirected
        $this->get('/admin/settings/ai-security')->assertRedirect();

        // Teacher -> forbidden / redirected
        $teacherResp = $this->actingAs($this->teacherA, 'web')->get('/admin/settings/ai-security');
        $this->assertTrue(in_array($teacherResp->getStatusCode(), [302, 403], true));

        // Admin A -> 200 OK
        $this->actingAs($this->adminA, 'web')
            ->get('/admin/settings/ai-security')
            ->assertStatus(200)
            ->assertSee('AI &amp; Security', false)
            ->assertSee('Google Gemini')
            ->assertSee('Google reCAPTCHA v3')
            ->assertSee('Google Sign-in (OAuth 2.0)');
    }

    public function test_gemini_settings_save_mask_test_and_clear(): void
    {
        $rawApiKey = 'AIzaSyTestKeyForGeminiPerInstitute123456';

        // 1. Save new Gemini settings
        $response = $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/ai-security/gemini', [
                'api_key' => $rawApiKey,
                'daily_cap' => 120,
            ]);

        $response->assertRedirect(route('admin.settings.ai-security'));
        $response->assertSessionHas('success');

        $row = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $this->instituteA->id)
            ->where('provider', 'gemini')
            ->firstOrFail();

        $this->assertSame('configured', $row->status);
        $this->assertSame(120, (int) ($row->settings['daily_cap'] ?? 0));
        $this->assertSame($rawApiKey, $row->credentials['api_key']);

        // Check SiteSetting updated
        $siteSettingVal = SiteSetting::withoutGlobalScopes()
            ->where('institute_id', $this->instituteA->id)
            ->where('key', 'assistant_daily_cap')
            ->value('value');
        $this->assertSame('120', $siteSettingVal);

        // 2. View page: verify secret is masked (••••23456 or similar) and raw key never in HTML
        $html = $this->actingAs($this->adminA, 'web')
            ->get('/admin/settings/ai-security')
            ->assertStatus(200)
            ->getContent();

        $this->assertStringNotContainsString($rawApiKey, $html);
        $this->assertStringContainsString('••••', $html);

        // 3. Test Key via fake HTTP
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['models' => []], 200),
        ]);

        $testResp = $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/ai-security/gemini/test');

        $testResp->assertRedirect(route('admin.settings.ai-security'));
        $testResp->assertSessionHas('success');

        $row->refresh();
        $this->assertSame('verified', $row->status);
        $this->assertNotNull($row->last_verified_at);

        // Verify request sent with x-goog-api-key
        Http::assertSent(function ($request) use ($rawApiKey) {
            return $request->hasHeader('x-goog-api-key', $rawApiKey);
        });

        // 4. Clear key
        $clearResp = $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/ai-security/gemini/clear');

        $clearResp->assertRedirect(route('admin.settings.ai-security'));
        $row->refresh();
        $this->assertSame('unconfigured', $row->status);
        $this->assertEmpty($row->credentials);
    }

    public function test_recaptcha_settings_save_mask_test_and_clear(): void
    {
        $siteKey = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
        $secretKey = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

        // 1. Save reCAPTCHA
        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/ai-security/recaptcha', [
                'site_key' => $siteKey,
                'secret_key' => $secretKey,
                'score_threshold' => 0.65,
            ])
            ->assertRedirect(route('admin.settings.ai-security'))
            ->assertSessionHas('success');

        $row = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $this->instituteA->id)
            ->where('provider', 'recaptcha')
            ->firstOrFail();

        $this->assertSame('configured', $row->status);
        $this->assertSame($siteKey, $row->settings['site_key']);
        $this->assertSame(0.65, (float) $row->settings['score_threshold']);
        $this->assertSame($secretKey, $row->credentials['secret_key']);

        // 2. View page: secret masked, site key visible
        $html = $this->actingAs($this->adminA, 'web')
            ->get('/admin/settings/ai-security')
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString($siteKey, $html);
        $this->assertStringNotContainsString($secretKey, $html);
        $this->assertStringContainsString('••••', $html);

        // 3. Test keys
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/ai-security/recaptcha/test')
            ->assertRedirect(route('admin.settings.ai-security'))
            ->assertSessionHas('success');

        $row->refresh();
        $this->assertSame('verified', $row->status);

        Http::assertSent(function ($request) use ($secretKey) {
            return $request['secret'] === $secretKey;
        });

        // 4. Clear reCAPTCHA
        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/ai-security/recaptcha/clear')
            ->assertRedirect(route('admin.settings.ai-security'));

        $row->refresh();
        $this->assertSame('unconfigured', $row->status);
        $this->assertEmpty($row->credentials);
    }

    public function test_google_oauth_settings_save_and_clear_and_redirect_uri_display(): void
    {
        $clientId = '123456789-abcdef.apps.googleusercontent.com';
        $clientSecret = 'GOCSPX-TestGoogleClientSecret12345';

        // 1. Initial view: check redirect URI
        $this->actingAs($this->adminA, 'web')
            ->get('/admin/settings/ai-security')
            ->assertStatus(200)
            ->assertSee('https://colombo.academy.lk/api/auth/google/callback');

        // 2. Save Google OAuth
        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/ai-security/google', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ])
            ->assertRedirect(route('admin.settings.ai-security'))
            ->assertSessionHas('success');

        $row = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $this->instituteA->id)
            ->where('provider', 'google')
            ->firstOrFail();

        $this->assertSame('configured', $row->status);
        $this->assertSame($clientId, $row->settings['client_id']);
        $this->assertSame($clientSecret, $row->credentials['client_secret']);

        // 3. View page: secret masked
        $html = $this->actingAs($this->adminA, 'web')
            ->get('/admin/settings/ai-security')
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString($clientId, $html);
        $this->assertStringNotContainsString($clientSecret, $html);

        // 4. Clear Google
        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/ai-security/google/clear')
            ->assertRedirect(route('admin.settings.ai-security'));

        $row->refresh();
        $this->assertSame('unconfigured', $row->status);
        $this->assertEmpty($row->credentials);
    }

    public function test_super_admin_filament_overview_shows_all_seven_providers(): void
    {
        // Seed integrations for Institute Alpha
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteA->id,
            'provider' => 'gemini',
            'credentials' => ['api_key' => 'AIzaSySecretOverview12345'],
            'settings' => ['daily_cap' => 150],
            'status' => 'verified',
            'last_verified_at' => now(),
        ]);

        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteA->id,
            'provider' => 'recaptcha',
            'credentials' => ['secret_key' => '6LeIxOverviewSecretKey12345'],
            'settings' => ['site_key' => '6LeIxSiteKey12345', 'score_threshold' => 0.5],
            'status' => 'configured',
        ]);

        $this->actingAs($this->superAdmin);
        Filament::setCurrentPanel(Filament::getPanel('super-admin'));

        $component = Livewire::test(InstituteIntegrationsOverview::class);
        $component->assertStatus(200);
        $component->assertSee('Colombo Academy');
        $component->assertSee('AI Assistant (Gemini)');
        $component->assertSee('Zoom Meetings');
        $component->assertSee('Google reCAPTCHA v3');
        $component->assertSee('Google Sign-in (OAuth)');
        $component->assertDontSee('AIzaSySecretOverview12345');
        $component->assertDontSee('6LeIxOverviewSecretKey12345');

        // Clear Gemini provider via Super-Admin action
        $component->call('requestClear', $this->instituteA->id, 'gemini');
        $component->call('clearProviderCredentials', $this->instituteA->id, 'gemini');

        $geminiRow = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $this->instituteA->id)
            ->where('provider', 'gemini')
            ->firstOrFail();
        $this->assertSame('unconfigured', $geminiRow->status);
        $this->assertEmpty($geminiRow->credentials);
    }
}

