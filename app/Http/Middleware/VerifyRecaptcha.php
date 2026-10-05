<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify Google reCAPTCHA v3 token on public-facing endpoints.
 *
 * Rejects requests that fail bot-detection scoring to prevent automated
 * form submissions that send emails to non-existent addresses (Ticket #370951).
 *
 * Configuration:
 *   RECAPTCHA_SECRET_KEY — Google reCAPTCHA v3 secret key (.env)
 *   RECAPTCHA_SCORE_THRESHOLD — Minimum score to pass (default: 0.5)
 *
 * Skips validation when:
 *   - RECAPTCHA_SECRET_KEY is not configured (local dev without keys)
 *   - APP_ENV is 'testing'
 */
class VerifyRecaptcha
{
    /**
     * Google reCAPTCHA verification endpoint.
     */
    protected const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secretKey = config('services.recaptcha.secret_key');

        // Skip in testing or when keys aren't configured (local dev)
        if (app()->environment('testing') || empty($secretKey)) {
            return $next($request);
        }

        $token = $request->input('recaptcha_token');

        if (empty($token)) {
            Log::warning('VerifyRecaptcha: Missing recaptcha_token', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'message' => 'Bot verification failed. Please refresh the page and try again.',
                'errors' => [
                    'recaptcha' => ['reCAPTCHA verification is required.'],
                ],
            ], 422);
        }

        try {
            $response = Http::asForm()->post(self::VERIFY_URL, [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            $result = $response->json();

            if (!($result['success'] ?? false)) {
                Log::warning('VerifyRecaptcha: Verification failed', [
                    'ip' => $request->ip(),
                    'error_codes' => $result['error-codes'] ?? [],
                ]);

                return response()->json([
                    'message' => 'Bot verification failed. Please refresh the page and try again.',
                    'errors' => [
                        'recaptcha' => ['reCAPTCHA verification failed. Please try again.'],
                    ],
                ], 422);
            }

            $score = $result['score'] ?? 0;
            $threshold = config('services.recaptcha.score_threshold', 0.5);

            if ($score < $threshold) {
                Log::warning('VerifyRecaptcha: Low score — suspected bot', [
                    'ip' => $request->ip(),
                    'score' => $score,
                    'threshold' => $threshold,
                    'action' => $result['action'] ?? 'unknown',
                ]);

                return response()->json([
                    'message' => 'Request blocked due to suspicious activity. If you are human, please refresh and try again.',
                    'errors' => [
                        'recaptcha' => ['Suspicious activity detected. Please try again.'],
                    ],
                ], 422);
            }

            Log::debug('VerifyRecaptcha: Passed', [
                'score' => $score,
                'action' => $result['action'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            // If the reCAPTCHA API is unreachable, allow the request through
            // to avoid blocking legitimate users due to Google service outage
            Log::error('VerifyRecaptcha: API call failed — allowing request through', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
        }

        return $next($request);
    }
}
