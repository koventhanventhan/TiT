<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslateController extends Controller
{
    /**
     * Translate text using Google Gemini AI.
     * POST /api/translate { "text": "...", "target_lang": "ta"|"si" }
     */
    public function translate(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
            'target_lang' => 'required|string|in:ta,si',
        ]);

        $text = $request->text;
        $targetLang = $request->target_lang;
        $langName = $targetLang === 'ta' ? 'Tamil' : 'Sinhala';

        $cacheKey = 'gemini_translate_' . $targetLang . '_' . md5($text);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return response()->json(['translated_text' => $cached]);
        }

        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            Log::warning('Gemini API key not configured');
            return response()->json(['translated_text' => $text], 200);
        }

        $prompt = "Translate the following text to {$langName}. Output only the translation, nothing else. No explanations.\n\n" . $text;

        try {
            $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $apiKey;
            $response = Http::timeout(15)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 2048,
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(['translated_text' => $text], 200);
            }

            $data = $response->json();
            $translated = $data['candidates'][0]['content']['parts'][0]['text'] ?? $text;
            $translated = trim($translated);

            Cache::put($cacheKey, $translated, now()->addDays(30));

            return response()->json(['translated_text' => $translated]);
        } catch (\Throwable $e) {
            Log::error('Gemini translate exception: ' . $e->getMessage());
            return response()->json(['translated_text' => $text], 200);
        }
    }
}
