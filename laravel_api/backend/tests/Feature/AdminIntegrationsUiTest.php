<?php

namespace Tests\Feature;

use App\Filament\SuperAdmin\Pages\InstituteIntegrationsOverview;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\InstituteIntegration;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminIntegrationsUiTest extends TestCase
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
            'name' => 'Institute Alpha',
            'slug' => 'institute-alpha',
            'status' => 'active',
        ]);
        InstituteDomain::create([
            'institute_id' => $this->instituteA->id,
            'domain' => 'alpha.academy.lk',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $this->instituteB = Institute::create([
            'name' => 'Institute Beta',
            'slug' => 'institute-beta',
            'status' => 'active',
        ]);
        InstituteDomain::create([
            'institute_id' => $this->instituteB->id,
            'domain' => 'beta.academy.lk',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        config(['integrations.env_fallback_institutes' => []]);

        $this->adminA = User::factory()->create([
            'institute_id' => $this->instituteA->id,
            'role' => 'admin',
            'email' => 'admin@alpha.academy.lk',
            'full_name' => 'Alpha Admin',
        ]);

        $this->adminB = User::factory()->create([
            'institute_id' => $this->instituteB->id,
            'role' => 'admin',
            'email' => 'admin@beta.academy.lk',
            'full_name' => 'Beta Admin',
        ]);

        $this->teacherA = User::factory()->create([
            'institute_id' => $this->instituteA->id,
            'role' => 'teacher',
            'email' => 'teacher@alpha.academy.lk',
            'full_name' => 'Alpha Teacher',
        ]);

        $this->superAdmin = User::factory()->create([
            'institute_id' => $this->instituteA->id,
            'role' => 'super_admin',
            'email' => 'owner@platform.lk',
            'full_name' => 'Platform Super Admin',
        ]);
    }

    public function test_only_institute_admin_role_can_access_integration_pages_and_institute_id_is_never_taken_from_request(): void
    {
        // Unauthenticated -> redirected to login
        $this->get('/admin/settings/online-payments')->assertRedirect();
        $this->get('/admin/settings/messaging')->assertRedirect();

        // Teacher -> forbidden / redirected (not admin)
        $teacherResp = $this->actingAs($this->teacherA, 'web')->get('/admin/settings/online-payments');
        $this->assertTrue(in_array($teacherResp->getStatusCode(), [302, 403], true));

        // Admin A tries to spoof institute_id = Institute B in POST body -> strictly ignored, saved ONLY under Institute A!
        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/online-payments', [
                'institute_id' => $this->instituteB->id,
                'merchant_id' => 'ALPHA_MID_777',
                'merchant_secret' => 'ALPHA_PAYHERE_SUPER_SECRET_9876',
                'is_sandbox' => '1',
                'currency' => 'LKR',
            ])
            ->assertRedirect(route('admin.settings.online-payments'));

        $this->assertDatabaseHas('institute_integrations', [
            'institute_id' => $this->instituteA->id,
            'provider' => 'payhere',
            'status' => 'configured',
        ]);
        $this->assertDatabaseMissing('institute_integrations', [
            'institute_id' => $this->instituteB->id,
            'provider' => 'payhere',
        ]);
    }

    public function test_payhere_form_never_echoes_plaintext_secret_in_html_supports_empty_keep_local_check_and_clear(): void
    {
        $plainMerchantSecret = 'MY_ULTRA_SECRET_PAYHERE_KEY_5432';
        $plainAppSecret = 'MY_ULTRA_SECRET_APP_KEY_8765';

        // 1. Save PayHere credentials
        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/online-payments', [
                'merchant_id' => '1229988',
                'merchant_secret' => $plainMerchantSecret,
                'app_id' => 'APP_101',
                'app_secret' => $plainAppSecret,
                'is_sandbox' => '1',
                'currency' => 'LKR',
            ])
            ->assertRedirect(route('admin.settings.online-payments'))
            ->assertSessionHas('success');

        // 2. Render GET page and assert HTML source NEVER contains plaintext secrets, only masked ••••5432 & ••••8765
        $page = $this->actingAs($this->adminA, 'web')->get('/admin/settings/online-payments');
        $page->assertStatus(200);
        $html = $page->getContent();

        $this->assertStringNotContainsString($plainMerchantSecret, $html);
        $this->assertStringNotContainsString($plainAppSecret, $html);
        $this->assertStringContainsString('••••5432', $html);
        $this->assertStringContainsString('••••8765', $html);
        $this->assertStringContainsString('https://alpha.academy.lk/api/payhere/notify', $html);
        $this->assertStringContainsString('alpha.academy.lk', $html);

        // 3. Empty secret submit keeps existing encrypted secret
        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/online-payments', [
                'merchant_id' => '1229988',
                'merchant_secret' => '',
                'app_id' => 'APP_101',
                'app_secret' => '',
                'is_sandbox' => '0',
                'currency' => 'USD',
            ])
            ->assertRedirect(route('admin.settings.online-payments'))
            ->assertSessionHas('success');

        $row = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $this->instituteA->id)
            ->where('provider', 'payhere')
            ->firstOrFail();
        $this->assertSame($plainMerchantSecret, $row->credentials['merchant_secret']);
        $this->assertSame($plainAppSecret, $row->credentials['app_secret']);
        $this->assertFalse((bool) $row->settings['is_sandbox']);
        $this->assertSame('USD', $row->settings['currency']);

        // 4. Local Check Configuration validates formats & checkout hash and clears failing state
        $row->recordFailure('Simulated previous signature mismatch');
        $this->assertSame('failing', $row->fresh()->status);

        $failingHtml = $this->actingAs($this->adminA, 'web')
            ->get('/admin/settings/online-payments')
            ->getContent();
        $this->assertStringContainsString('Simulated previous signature mismatch', $failingHtml);

        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/online-payments/check')
            ->assertRedirect(route('admin.settings.online-payments'))
            ->assertSessionHas('success');

        $this->assertSame('configured', $row->fresh()->status);
        $this->assertNull($row->fresh()->last_error);

        // 5. Clear disables online payments
        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/online-payments/clear')
            ->assertRedirect(route('admin.settings.online-payments'))
            ->assertSessionHas('success');

        $this->assertSame('unconfigured', $row->fresh()->status);
        $this->assertEmpty($row->fresh()->credentials);
    }

    public function test_messaging_forms_never_echo_plaintext_secrets_validate_inputs_and_enforce_whatsapp_3_per_hour_limit(): void
    {
        Mail::fake();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ui.1']]], 200),
        ]);

        $plainSmtpPassword = 'SMTP_APP_PASSWORD_SECRET_3344';
        $plainWaToken = 'EAAG_META_CLOUD_SECRET_TOKEN_7766';

        // 1. Save Mail & WhatsApp settings
        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/messaging/mail', [
                'host' => 'smtp.alpha.academy.lk',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'noreply@alpha.academy.lk',
                'password' => $plainSmtpPassword,
                'from_address' => 'noreply@alpha.academy.lk',
                'from_name' => 'Alpha Academy',
                'reply_to' => 'office@alpha.academy.lk',
            ])
            ->assertRedirect(route('admin.settings.messaging'))
            ->assertSessionHas('success');

        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/messaging/whatsapp', [
                'access_token' => $plainWaToken,
                'phone_number_id' => '109876543210',
                'api_version' => 'v21.0',
                'templates' => [
                    'welcome' => 'alpha_welcome_tpl',
                    'general_update' => 'alpha_notice_tpl',
                ],
            ])
            ->assertRedirect(route('admin.settings.messaging'))
            ->assertSessionHas('success');

        // 2. Assert GET HTML source NEVER contains plaintext SMTP password or WhatsApp token
        $html = $this->actingAs($this->adminA, 'web')
            ->get('/admin/settings/messaging')
            ->assertStatus(200)
            ->getContent();

        $this->assertStringNotContainsString($plainSmtpPassword, $html);
        $this->assertStringNotContainsString($plainWaToken, $html);
        $this->assertStringContainsString('••••3344', $html);
        $this->assertStringContainsString('••••7766', $html);

        // 3. Send test e-mail to logged-in admin's address
        $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/messaging/mail/test')
            ->assertRedirect(route('admin.settings.messaging'))
            ->assertSessionHas('success');

        // 4. WhatsApp test message validates E.164 and enforces max 3 per hour per institute
        $this->actingAs($this->adminA, 'web')
            ->from(route('admin.settings.messaging'))
            ->post('/admin/settings/messaging/whatsapp/test', [
                'test_to' => '0771234567', // Missing + E.164 prefix -> validation error
            ])
            ->assertRedirect(route('admin.settings.messaging'))
            ->assertSessionHasErrors('test_to');

        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($this->adminA, 'web')
                ->post('/admin/settings/messaging/whatsapp/test', [
                    'test_to' => '+94771234567',
                ])
                ->assertRedirect(route('admin.settings.messaging'))
                ->assertSessionHas('success');
        }

        // 4th attempt within the hour -> blocked by per-institute rate limiter
        $fourth = $this->actingAs($this->adminA, 'web')
            ->post('/admin/settings/messaging/whatsapp/test', [
                'test_to' => '+94771234567',
            ]);
        $fourth->assertRedirect(route('admin.settings.messaging'));
        $fourth->assertSessionHas('error');
    }

    public function test_super_admin_filament_overview_displays_statuses_without_secrets_and_can_clear_credentials(): void
    {
        $secretA = 'SUPER_SECRET_PAYHERE_FOR_OVERVIEW_1928';
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteA->id,
            'provider' => 'payhere',
            'credentials' => ['merchant_secret' => $secretA],
            'settings' => ['merchant_id' => 'MID_A', 'is_sandbox' => true, 'currency' => 'LKR'],
            'status' => 'verified',
            'last_verified_at' => now(),
        ]);

        $this->actingAs($this->superAdmin);
        Filament::setCurrentPanel(Filament::getPanel('super-admin'));

        $component = Livewire::test(InstituteIntegrationsOverview::class);
        $component->assertStatus(200);
        $component->assertSee('Institute Alpha');
        $component->assertSee('verified');
        $component->assertDontSee($secretA);

        // Clear credentials via Super-Admin action
        $component->call('requestClear', $this->instituteA->id, 'payhere');
        $component->call('clearProviderCredentials', $this->instituteA->id, 'payhere');

        $row = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $this->instituteA->id)
            ->where('provider', 'payhere')
            ->firstOrFail();
        $this->assertSame('unconfigured', $row->status);
        $this->assertEmpty($row->credentials);

        // PlatformSettings labels platform defaults clearly
        $platformHtml = $this->actingAs($this->superAdmin)
            ->get('/super-admin/platform-settings')
            ->assertStatus(200)
            ->getContent();
        $this->assertStringContainsString('Platform defaults (only used where fallback is allowed)', $platformHtml);
    }
}
