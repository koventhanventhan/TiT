<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiTranslationService
{
    protected ?string $apiKey = null;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
    protected static bool $hasLoggedUnconfigured = false;

    public function __construct(?string $apiKey = null)
    {
        if (func_num_args() > 0) {
            $resolved = $apiKey;
        } else {
            $resolved = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        }
        $this->apiKey = ($resolved !== null && trim((string) $resolved) !== '') ? (string) $resolved : null;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
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

        if (!$this->isConfigured()) {
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
                'x-goog-api-key' => $this->apiKey,
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
