<?php

namespace App\Http\Middleware;

use App\Support\Integrations;
use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify Google reCAPTCHA v3 token on public-facing endpoints.
 *
 * For configured institutes:
 *   Verifies the token with Google using the institute's secret key and score threshold.
 * For unconfigured non-fallback institutes:
 *   Works without captcha but enforces a stricter throttle: 5 attempts / 10 minutes / IP / institute (HTTP 429).
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
        // Skip reCAPTCHA verification in tests unless explicitly opted in
        if (app()->environment('testing') && !config('services.recaptcha.verify_in_tests', false)) {
            return $next($request);
        }

        $instituteId = Tenant::id() ?? 1;
        $creds = Integrations::for($instituteId)->recaptcha();

        // If reCAPTCHA is NOT configured for this institute:
        // Forms work without captcha, but under a stricter rate limit:
        // 5 submits / 10 minutes / IP / institute -> 429 with clear message.
        if ($creds === null || !$creds->isConfigured()) {
            $ip = $request->ip() ?? '127.0.0.1';
            $routePath = $request->path();
            $throttleKey = "recaptcha_unconfigured:{$instituteId}:" . md5($routePath) . ":{$ip}";
            $maxAttempts = 5;
            $decaySeconds = 600; // 10 minutes

            if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($throttleKey);
                return response()->json([
                    'message' => 'Too many requests. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                    'code' => 'too_many_requests',
                    'retry_after' => $seconds,
                ], 429);
            }

            RateLimiter::hit($throttleKey, $decaySeconds);

            return $next($request);
        }

        // reCAPTCHA is configured: verify with Google endpoint
        $token = $request->input('recaptcha_token');

        if (empty($token)) {
            Log::warning('VerifyRecaptcha: Missing recaptcha_token', [
                'institute_id' => $instituteId,
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
            $response = Http::asForm()->timeout(10)->post(self::VERIFY_URL, [
                'secret' => $creds->secretKey(),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            $result = $response->json();

            if (!($result['success'] ?? false)) {
                Log::warning('VerifyRecaptcha: Verification failed', [
                    'institute_id' => $instituteId,
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

            $score = (float) ($result['score'] ?? 0);
            $threshold = (float) ($creds->scoreThreshold() ?: 0.5);

            if ($score < $threshold) {
                Log::warning('VerifyRecaptcha: Low score — suspected bot', [
                    'institute_id' => $instituteId,
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
                'institute_id' => $instituteId,
                'score' => $score,
                'action' => $result['action'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            Log::error('VerifyRecaptcha: API call failed — allowing request through', [
                'institute_id' => $instituteId,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
        }

        return $next($request);
    }
}
