<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiTranslationService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        if (!$this->apiKey) {
            $this->apiKey = env('GEMINI_API_KEY');
        }
    }

    /**
     * Translate text to target language.
     *
     * @param string $text
     * @param string $targetLocale 'ta' or 'si'
     * @return string
     */
    public function translate(string $text, string $targetLocale): string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API Key is missing.');
            return $text;
        }

        $languageName = $targetLocale === 'ta' ? 'Tamil' : ($targetLocale === 'si' ? 'Sinhala' : 'English');
        
        $prompt = "Translate the following word/phrase to {$languageName}. Return ONLY the translated text, no explanation or extra characters. Text: \"{$text}\"";

        try {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $this->apiKey;
            $response = Http::timeout(15)->post($url, [
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
                Log::error('Gemini API Error: ' . $response->body());
            }

            if ($response->successful()) {
                $data = $response->json();
                $translated = $data['candidates'][0]['content']['parts'][0]['text'] ?? $text;
                return trim($translated);
            }

            Log::error('Gemini API Error: ' . $response->body());
            return $text;
        } catch (\Exception $e) {
            Log::error('Gemini Translation Exception: ' . $e->getMessage());
            return $text;
        }
    }
}
