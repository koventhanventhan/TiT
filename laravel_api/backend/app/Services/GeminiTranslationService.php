<?php

namespace App\Services;

use App\Support\Integrations;
use App\Support\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiTranslationService
{
    protected ?string $explicitApiKey = null;
    protected ?int $explicitInstituteId = null;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
    protected static bool $hasLoggedUnconfigured = false;

    public function __construct(?string $apiKey = null, ?int $instituteId = null)
    {
        $this->explicitApiKey = ($apiKey !== null && trim((string) $apiKey) !== '') ? (string) $apiKey : null;
        $this->explicitInstituteId = $instituteId;
    }

    public function forInstitute(?int $instituteId): self
    {
        $clone = clone $this;
        $clone->explicitInstituteId = $instituteId;
        return $clone;
    }

    public function resolveApiKey(): ?string
    {
        if ($this->explicitApiKey !== null) {
            return $this->explicitApiKey;
        }

        $instituteId = $this->explicitInstituteId ?? Tenant::id();
        if ($instituteId !== null && $instituteId > 0) {
            $creds = Integrations::for($instituteId)->gemini();
            if ($creds !== null && $creds->isConfigured()) {
                return $creds->apiKey;
            }
        }

        if (auth()->check() && auth()->user()?->institute_id) {
            $creds = Integrations::for((int) auth()->user()->institute_id)->gemini();
            if ($creds !== null && $creds->isConfigured()) {
                return $creds->apiKey;
            }
        }

        return null;
    }

    public function isConfigured(): bool
    {
        return !empty($this->resolveApiKey());
    }

    public static function resetLoggedUnconfigured(): void
    {
        self::$hasLoggedUnconfigured = false;
    }

    /**
     * Translate text to target language.
     *
     * @param string|null $text
     * @param string $targetLocale 'ta' or 'si'
     * @return string|null
     */
    public function translate(?string $text, string $targetLocale): ?string
    {
        if ($text === null || trim($text) === '') {
            return $text;
        }

        $apiKey = $this->resolveApiKey();
        if (empty($apiKey)) {
            if (!self::$hasLoggedUnconfigured) {
                Log::info('Gemini API Key is not configured. Translation skipped.');
                self::$hasLoggedUnconfigured = true;
            }
            return $text;
        }

        $languageName = $targetLocale === 'ta' ? 'Tamil' : ($targetLocale === 'si' ? 'Sinhala' : 'English');
        
        $prompt = "Translate the following word/phrase to {$languageName}. Return ONLY the translated text, no explanation or extra characters. Text: \"{$text}\"";

        try {
            $url = $this->baseUrl;
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 2048,
                ],
            ]);

            if (!$response->successful()) {
                $cleanBody = preg_replace('/(\?|&)(key|apiKey)=[A-Za-z0-9._-]+/i', '', $response->body());
                $cleanBody = preg_replace('/key=[A-Za-z0-9._-]+/i', 'key=***', $cleanBody);
                Log::error('Gemini API Error: ' . $cleanBody);
                return $text;
            }

            $data = $response->json();
            $translated = $data['candidates'][0]['content']['parts'][0]['text'] ?? $text;
            return trim($translated);
        } catch (\Exception $e) {
            $cleanMsg = preg_replace('/(\?|&)(key|apiKey)=[A-Za-z0-9._-]+/i', '', $e->getMessage());
            $cleanMsg = preg_replace('/key=[A-Za-z0-9._-]+/i', 'key=***', $cleanMsg);
            Log::error('Gemini Translation Exception: ' . $cleanMsg);
            return $text;
        }
    }
}
