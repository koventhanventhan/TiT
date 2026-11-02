<?php

namespace Tests\Unit;

use App\Services\GeminiTranslationService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GeminiTranslationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        GeminiTranslationService::resetLoggedUnconfigured();
    }

    public function test_unconfigured_service_returns_original_text_and_does_not_throw(): void
    {
        $origConfig = config('services.gemini.api_key');
        $origEnv = $_ENV['GEMINI_API_KEY'] ?? null;
        $origServer = $_SERVER['GEMINI_API_KEY'] ?? null;

        config(['services.gemini.api_key' => null]);
        unset($_ENV['GEMINI_API_KEY'], $_SERVER['GEMINI_API_KEY']);
        putenv('GEMINI_API_KEY');

        try {
            $service = new GeminiTranslationService();
            $this->assertFalse($service->isConfigured());

            $result = $service->translate('Physics Mechanics', 'ta');
            $this->assertEquals('Physics Mechanics', $result);
        } finally {
            config(['services.gemini.api_key' => $origConfig]);
            if ($origEnv !== null) {
                $_ENV['GEMINI_API_KEY'] = $origEnv;
                putenv("GEMINI_API_KEY={$origEnv}");
            }
            if ($origServer !== null) {
                $_SERVER['GEMINI_API_KEY'] = $origServer;
            }
        }
    }

    public function test_unconfigured_service_returns_null_when_null_passed(): void
    {
        $service = new GeminiTranslationService(null);
        $this->assertFalse($service->isConfigured());

        $this->assertNull($service->translate(null, 'si'));
    }

    public function test_is_configured_returns_false_for_empty_string(): void
    {
        $service = new GeminiTranslationService('   ');
        $this->assertFalse($service->isConfigured());
    }

    public function test_is_configured_returns_true_when_key_present(): void
    {
        $service = new GeminiTranslationService('test-valid-key-12345');
        $this->assertTrue($service->isConfigured());
    }

    public function test_logs_once_when_not_configured(): void
    {
        Log::spy();

        $service = new GeminiTranslationService(null);
        $service->translate('Sample 1', 'ta');
        $service->translate('Sample 2', 'si');

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Gemini API Key is not configured');
            });
    }
}
