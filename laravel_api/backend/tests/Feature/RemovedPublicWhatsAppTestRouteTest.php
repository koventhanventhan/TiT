<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RemovedPublicWhatsAppTestRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_test_admin_whatsapp_endpoint_returns_404(): void
    {
        $this->get('/api/test-admin-whatsapp')->assertStatus(404);
        $this->getJson('/api/test-admin-whatsapp')->assertStatus(404);
    }

    public function test_cli_integrations_test_whatsapp_command_uses_institute_credentials_and_never_prints_token(): void
    {
        $institute = Institute::withoutGlobalScopes()->create([
            'name' => 'Institute CLI WA',
            'slug' => 'inst-cli-wa',
            'is_active' => true,
        ]);

        $secretToken = 'EAASuperSecretCliTestToken_998877';

        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $institute->id,
            'provider' => 'whatsapp',
            'credentials' => [
                'access_token' => $secretToken,
            ],
            'settings' => [
                'phone_number_id' => '555666777888',
                'api_version' => 'v21.0',
            ],
            'status' => 'configured',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.CLI_TEST_OK_1']],
            ], 200),
        ]);

        $exitCode = Artisan::call('integrations:test-whatsapp', [
            'institute' => (string) $institute->id,
            'to' => '+94771234567',
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('wamid.CLI_TEST_OK_1', $output);
        $this->assertStringNotContainsString($secretToken, $output);

        Http::assertSent(function ($request) use ($secretToken) {
            return str_contains($request->url(), '555666777888/messages')
                && $request->hasHeader('Authorization', 'Bearer ' . $secretToken);
        });
    }
}
