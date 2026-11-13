<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Integrations;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class InstituteIntegrationsStorageTest extends TestCase
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
            'name' => 'TiT Jaffna',
            'slug' => 'tit-jaffna',
            'status' => 'active',
        ]);

        $this->instTwo = Institute::create([
            'name' => 'Colombo Academy',
            'slug' => 'colombo-academy',
            'status' => 'active',
        ]);

        $this->adminOne = User::factory()->create([
            'institute_id' => $this->instOne->id,
            'role' => 'admin',
            'email' => 'admin@titjaffna.lk',
        ]);

        $this->adminTwo = User::factory()->create([
            'institute_id' => $this->instTwo->id,
            'role' => 'admin',
            'email' => 'admin@colomboacademy.lk',
        ]);

        config([
            'integrations.env_fallback_institutes' => [$this->instOne->id],
            'integrations.mail_platform_fallback' => true,
            'payhere.merchant_id' => 'ENV_MERCHANT_001',
            'payhere.merchant_secret' => 'ENV_SUPER_SECRET_9876',
            'payhere.app_id' => 'ENV_APP_001',
            'payhere.app_secret' => 'ENV_APP_SECRET_5432',
            'payhere.is_sandbox' => true,
            'payhere.currency' => 'LKR',
            'services.meta_whatsapp.token' => 'EAAG_ENV_WHATSAPP_TOKEN_4321',
            'services.meta_whatsapp.phone_number_id' => '109988776655',
            'services.meta_whatsapp.api_version' => 'v21.0',
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.platform.example.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.encryption' => 'tls',
            'mail.mailers.smtp.username' => 'mailer@platform.example.com',
            'mail.mailers.smtp.password' => 'PLATFORM_SMTP_PASS_7788',
            'mail.from.address' => 'noreply@platform.example.com',
            'mail.from.name' => 'Platform Default',
        ]);
    }

    public function test_credentials_are_encrypted_at_rest_hidden_in_json_and_masked_properly(): void
    {
        $plainSecret = 'MERCHANT_SECRET_ULTRA_PRIVATE_1234';

        $this->actingAs($this->adminTwo);

        $integration = InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_PAYHERE,
            'credentials' => [
                'merchant_secret' => $plainSecret,
                'app_secret' => 'xyz', // short secret <= 4 chars must never expose full value
            ],
            'settings' => [
                'merchant_id' => '1223344',
                'is_sandbox' => true,
                'currency' => 'LKR',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
            'last_error' => "Failed auth with {$plainSecret} and password=LEAKED_PASS",
        ]);

        // 1. Encrypted at rest in raw DB column
        $rawCiphertext = DB::table('institute_integrations')
            ->where('id', $integration->id)
            ->value('credentials');

        $this->assertNotEmpty($rawCiphertext);
        $this->assertStringNotContainsString($plainSecret, $rawCiphertext);

        // 2. Hidden from array / JSON serialization
        $json = $integration->toJson();
        $this->assertStringNotContainsString($plainSecret, $json);
        $this->assertArrayNotHasKey('credentials', $integration->toArray());

        // 3. maskedCredentials returns •••• + last 4 chars only (and •••• for <=4 chars)
        $masked = $integration->maskedCredentials();
        $this->assertSame('••••1234', $masked['merchant_secret']);
        $this->assertSame('••••', $masked['app_secret']);

        // 4. last_error is sanitised automatically
        $integration->refresh();
        $this->assertStringNotContainsString($plainSecret, (string) $integration->last_error);
        $this->assertStringNotContainsString('LEAKED_PASS', (string) $integration->last_error);
    }

    public function test_payhere_and_whatsapp_fallback_only_for_listed_institutes_and_whatsapp_logs_once_per_day(): void
    {
        // Institute 1 (listed in INTEGRATIONS_ENV_FALLBACK_INSTITUTES) gets env fallback when no DB row exists
        $phOne = Integrations::for($this->instOne->id)->payhere();
        $this->assertNotNull($phOne);
        $this->assertSame('env', $phOne->source);
        $this->assertSame('ENV_MERCHANT_001', $phOne->merchantId);

        $waOne = Integrations::for($this->instOne->id)->whatsapp();
        $this->assertNotNull($waOne);
        $this->assertSame('env', $waOne->source);
        $this->assertSame('109988776655', $waOne->phoneNumberId);

        // Institute 2 (NOT listed) NEVER gets platform PayHere or WhatsApp
        $this->assertNull(Integrations::for($this->instTwo->id)->payhere());

        Log::spy();

        $this->assertNull(Integrations::for($this->instTwo->id)->whatsapp());
        $this->assertNull(Integrations::for($this->instTwo->id)->whatsapp());
        $this->assertNull(Integrations::for($this->instTwo->id)->whatsapp());

        // Logged ONCE per day for Institute 2
        Log::shouldHaveReceived('info')
            ->with("WhatsApp not configured for institute {$this->instTwo->id}")
            ->once();

        // Once Institute 2 configures its own DB rows, they resolve with source = db
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_PAYHERE,
            'credentials' => ['merchant_secret' => 'INST2_SECRET_9999'],
            'settings' => ['merchant_id' => 'INST2_MID', 'is_sandbox' => false, 'currency' => 'USD'],
            'status' => InstituteIntegration::STATUS_VERIFIED,
        ]);

        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_WHATSAPP,
            'credentials' => ['access_token' => 'EAAG_INST2_TOKEN_8888'],
            'settings' => [
                'phone_number_id' => '222333444',
                'api_version' => 'v22.0',
                'templates' => ['welcome' => 'colombo_welcome'],
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        Tenant::run($this->instTwo, function () {
            $phTwo = Integrations::for()->payhere();
            $this->assertNotNull($phTwo);
            $this->assertSame('db', $phTwo->source);
            $this->assertSame('INST2_MID', $phTwo->merchantId);
            $this->assertFalse($phTwo->isSandbox);
            $this->assertSame('USD', $phTwo->currency);

            $waTwo = Integrations::for()->whatsapp();
            $this->assertNotNull($waTwo);
            $this->assertSame('db', $waTwo->source);
            $this->assertSame('colombo_welcome', $waTwo->templateFor('welcome'));
            $this->assertSame('colombo_welcome', $waTwo->templateFor('tit_welcome'));
            $this->assertSame('tit_payment_receipt', $waTwo->templateFor('payment_receipt'));
        });
    }

    public function test_mail_fallback_uses_institute_name_and_contact_reply_to_for_all_institutes(): void
    {
        SiteSetting::setForInstitute('footer_email', 'hello@colomboacademy.lk', $this->instTwo->id, 'contact');

        $mailTwo = Integrations::for($this->instTwo->id)->mail();
        $this->assertNotNull($mailTwo);
        $this->assertSame('env', $mailTwo->source);
        $this->assertSame('Colombo Academy', $mailTwo->fromName);
        $this->assertSame('hello@colomboacademy.lk', $mailTwo->replyToAddress);
        $this->assertSame('noreply@platform.example.com', $mailTwo->fromAddress);

        // Disabling platform mail fallback returns null when institute has no custom SMTP
        config(['integrations.mail_platform_fallback' => false]);
        $this->assertNull(Integrations::for($this->instTwo->id)->mail());
    }

    public function test_activity_log_records_create_update_and_clear_with_field_names_only_never_values(): void
    {
        $this->actingAs($this->adminTwo);
        $secret1 = 'TOP_SECRET_ALPHA_1111';
        $secret2 = 'TOP_SECRET_BETA_2222';

        $row = InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_PAYHERE,
            'credentials' => ['merchant_secret' => $secret1],
            'settings' => ['merchant_id' => 'MID_ALPHA', 'currency' => 'LKR'],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $row->update([
            'credentials' => ['merchant_secret' => $secret2],
            'settings' => ['merchant_id' => 'MID_ALPHA', 'currency' => 'USD'],
        ]);

        $row->clearCredentials();

        $logs = ActivityLog::withoutGlobalScopes()
            ->where('institute_id', $this->instTwo->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $logs);
        $this->assertSame('integration.created', $logs[0]->action);
        $this->assertSame($this->adminTwo->id, $logs[0]->user_id);
        $this->assertEqualsCanonicalizing(
            ['merchant_secret', 'merchant_id', 'currency'],
            $logs[0]->metadata['changed_fields']
        );

        $this->assertSame('integration.updated', $logs[1]->action);
        $this->assertEqualsCanonicalizing(
            ['merchant_secret', 'currency'],
            $logs[1]->metadata['changed_fields']
        );

        $this->assertSame('integration.cleared', $logs[2]->action);
        $this->assertContains('merchant_secret', $logs[2]->metadata['changed_fields']);

        // Assert zero secret or setting values appear anywhere in activity_logs
        $serializedLogs = $logs->toJson();
        $this->assertStringNotContainsString($secret1, $serializedLogs);
        $this->assertStringNotContainsString($secret2, $serializedLogs);
        $this->assertStringNotContainsString('MID_ALPHA', $serializedLogs);
    }

    public function test_artisan_status_and_import_env_commands_never_expose_secrets(): void
    {
        // 1. Dry-run prints masked values and writes nothing to DB
        $exitDry = Artisan::call('integrations:import-env', [
            'institute' => (string) $this->instOne->id,
            '--dry-run' => true,
        ]);
        $dryOutput = Artisan::output();

        $this->assertSame(0, $exitDry);
        $this->assertSame(0, InstituteIntegration::withoutGlobalScopes()->count());
        $this->assertStringContainsString('••••9876', $dryOutput);
        $this->assertStringContainsString('••••4321', $dryOutput);
        $this->assertStringContainsString('••••7788', $dryOutput);
        $this->assertStringNotContainsString('ENV_SUPER_SECRET_9876', $dryOutput);
        $this->assertStringNotContainsString('EAAG_ENV_WHATSAPP_TOKEN_4321', $dryOutput);
        $this->assertStringNotContainsString('PLATFORM_SMTP_PASS_7788', $dryOutput);

        // 2. Real import writes DB rows for Institute 1
        $exitReal = Artisan::call('integrations:import-env', [
            'institute' => 'tit-jaffna',
        ]);
        $realOutput = Artisan::output();

        $this->assertSame(0, $exitReal);
        $this->assertGreaterThanOrEqual(3, InstituteIntegration::withoutGlobalScopes()->where('institute_id', $this->instOne->id)->count());
        $this->assertStringNotContainsString('ENV_SUPER_SECRET_9876', $realOutput);

        // Even if .env values are later blanked, Institute 1 now resolves from DB!
        config([
            'payhere.merchant_id' => '',
            'payhere.merchant_secret' => '',
            'services.meta_whatsapp.token' => '',
            'services.meta_whatsapp.phone_number_id' => '',
        ]);

        $phOne = Integrations::for($this->instOne->id)->payhere();
        $this->assertNotNull($phOne);
        $this->assertSame('db', $phOne->source);
        $this->assertSame('ENV_MERCHANT_001', $phOne->merchantId);

        // 3. integrations:status shows db for Institute 1 and none/env for Institute 2 without secrets
        $exitStatus = Artisan::call('integrations:status');
        $statusOutput = Artisan::output();

        $this->assertSame(0, $exitStatus);
        $this->assertStringContainsString('TiT Jaffna', $statusOutput);
        $this->assertStringContainsString('Colombo Academy', $statusOutput);
        $this->assertStringContainsString('••••9876', $statusOutput);
        $this->assertStringNotContainsString('ENV_SUPER_SECRET_9876', $statusOutput);
        $this->assertStringNotContainsString('EAAG_ENV_WHATSAPP_TOKEN_4321', $statusOutput);
        $this->assertStringNotContainsString('PLATFORM_SMTP_PASS_7788', $statusOutput);
    }
}
