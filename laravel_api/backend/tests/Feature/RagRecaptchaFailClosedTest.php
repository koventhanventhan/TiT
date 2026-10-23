<?php

namespace Tests\Feature;

use App\Models\Institute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RagRecaptchaFailClosedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
    }

    /**
     * Test public assistant fails closed (HTTP 503) in production if RECAPTCHA_SECRET_KEY is empty.
     */
    public function test_public_chat_fails_closed_in_production_without_recaptcha_secret(): void
    {
        $institute = Institute::create([
            'name' => 'Production Institute',
            'slug' => 'prod-institute',
            'domain' => 'prod.localhost',
            'status' => 'active',
            'public_assistant_enabled' => true,
        ]);

        config([
            'app.env' => 'production',
            'rag.public_enabled' => true,
            'services.recaptcha.secret_key' => '',
        ]);

        $response = $this->withHeaders([
            'X-Institute-Id' => (string) $institute->id,
        ])->postJson('/api/assistant/chat', [
            'question' => 'Hello',
        ]);

        $this->assertEquals(503, $response->status());
        $this->assertFalse($response->json('public_enabled'));
        $this->assertStringContainsString('maintenance', strtolower($response->json('message') ?? ''));
    }

    /**
     * Test rag:public on artisan command refuses activation in production without recaptcha secret.
     */
    public function test_artisan_rag_public_on_refuses_activation_without_recaptcha_secret(): void
    {
        $institute = Institute::create([
            'name' => 'Prod Inst Toggle',
            'slug' => 'prod-toggle',
            'domain' => 'toggle.localhost',
            'status' => 'active',
            'public_assistant_enabled' => false,
        ]);

        config([
            'app.env' => 'production',
            'services.recaptcha.secret_key' => '',
        ]);

        $exitCode = Artisan::call('rag:public', [
            'id' => $institute->id,
            'state' => 'on',
        ]);

        $output = Artisan::output();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Refusing to activate Public Assistant without RECAPTCHA_SECRET_KEY', $output);

        // Verify institute was NOT activated
        $this->assertFalse($institute->fresh()->public_assistant_enabled);
    }

    /**
     * Test public assistant is allowed in testing/local environment without recaptcha secret.
     */
    public function test_public_chat_allowed_in_testing_environment(): void
    {
        $institute = Institute::create([
            'name' => 'Testing Institute',
            'slug' => 'test-institute',
            'domain' => 'testing.localhost',
            'status' => 'active',
            'public_assistant_enabled' => true,
        ]);

        config([
            'app.env' => 'testing',
            'rag.public_enabled' => true,
            'services.recaptcha.secret_key' => '',
        ]);

        $response = $this->withHeaders([
            'X-Institute-Id' => (string) $institute->id,
        ])->postJson('/api/assistant/chat', [
            'question' => 'What is the institute address?',
        ]);

        // In testing env without recaptcha secret, it does NOT return 503
        $this->assertNotEquals(503, $response->status());
    }
}
