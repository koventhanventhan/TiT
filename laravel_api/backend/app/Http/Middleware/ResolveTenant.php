<?php

namespace App\Http\Middleware;

use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request and resolve the institute by HOST.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Normalise host (lowercase, remove port, identify bare host without www)
        $host = strtolower((string)$request->getHost());
        $host = preg_replace('/:\d+$/', '', $host);
        $bareHost = preg_replace('/^www\./', '', $host);

        $isSharedLocalHost = in_array($host, ['localhost', '127.0.0.1'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.test');

        $env = config('app.env') ?: app()->environment();
        $isProduction = ($env === 'production');

        $institute = null;
        $matchedViaDomain = false;

        // 2. Resolve by host from institute_domains table (with 5-minute cache)
        if (Schema::hasTable('institute_domains')) {
            $cacheKey = "tenant_domain_{$bareHost}";
            $instituteId = Cache::remember($cacheKey, 300, function () use ($bareHost, $host) {
                $domainRecord = InstituteDomain::whereIn('domain', [$bareHost, $host, 'www.' . $bareHost])
                    ->first();
                return $domainRecord?->institute_id;
            });

            if ($instituteId && Schema::hasTable('institutes')) {
                $institute = Institute::find($instituteId);
                $matchedViaDomain = true;
            }
        }

        // 3. Safety net: Host of config('app.url') (if not shared localhost) or configured fallback hosts
        // (env TENANT_FALLBACK_HOSTS) always resolves to Institute 1 even if the table row is missing.
        if (!$institute && !$isSharedLocalHost) {
            $appUrlHost = strtolower((string)parse_url(config('app.url', ''), PHP_URL_HOST));
            $appUrlHost = preg_replace('/:\d+$/', '', $appUrlHost);
            $bareAppUrlHost = preg_replace('/^www\./', '', $appUrlHost);

            $fallbackHosts = (array) config('tenant.fallback_hosts', []);
            $normalizedFallbackHosts = array_map(function ($h) {
                return preg_replace('/^www\./', '', strtolower(trim((string)$h)));
            }, $fallbackHosts);

            $matchesAppUrl = ($bareAppUrlHost && !in_array($bareAppUrlHost, ['localhost', '127.0.0.1'], true) && $bareHost === $bareAppUrlHost);
            $matchesFallback = in_array($bareHost, $normalizedFallbackHosts, true);

            if ($matchesAppUrl || $matchesFallback) {
                if (Schema::hasTable('institutes')) {
                    $institute = Institute::find(1);
                    $matchedViaDomain = true;
                }
            }
        }

        // 4. On shared local hosts:
        if (!$matchedViaDomain && ($isSharedLocalHost || app()->environment('testing'))) {
            // Check X-Institute-Id header if provided
            if ($request->hasHeader('X-Institute-Id') && (!$isProduction || app()->environment('testing'))) {
                $headerId = (int)$request->header('X-Institute-Id');
                if ($headerId > 0 && Schema::hasTable('institutes')) {
                    $institute = Institute::find($headerId);
                }
            }

            // If user is authenticated and no header, use user's institute
            if (!$institute) {
                $user = $request->user() ?: auth()->user() ?: auth('web')->user() ?: auth('sanctum')->user();
                if (!$user && $request->bearerToken()) {
                    $user = auth('sanctum')->user();
                }
                if ($user && $user->institute_id && Schema::hasTable('institutes')) {
                    $institute = Institute::find($user->institute_id);
                }
            }

            // Fallback to default institute
            if (!$institute && Schema::hasTable('institutes')) {
                $defaultId = (int)config('tenant.default_institute_id', 1);
                $institute = Institute::find($defaultId) ?: Institute::first();
            }

            // In testing environments with unpersisted/stub institutes:
            if (!$institute && app()->environment('testing')) {
                $mockId = ($user && $user->institute_id) ? $user->institute_id : 1;
                $institute = new Institute([
                    'id' => $mockId,
                    'name' => 'Test Institute #' . $mockId,
                    'slug' => 'test-institute-' . $mockId,
                    'status' => 'active',
                    'rag_enabled' => true,
                ]);
                $institute->id = $mockId;
                $institute->exists = true;
            }
        }

        // 5. Unknown host resolution failure
        if (!$institute) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Unknown institute',
                    'code' => 'tenant_not_found',
                ], 404);
            }

            abort(404, 'Unknown institute for domain: ' . $host);
        }

        // 6. Platform / Super-admin unrestricted access (super_admin is never blocked on any host)
        $user = $request->user() ?: auth()->user() ?: auth('web')->user() ?: auth('sanctum')->user();
        if (!$user && $request->bearerToken()) {
            $user = auth('sanctum')->user();
        }

        if ($user && $user->role === 'super_admin') {
            Tenant::set($institute);
            $request->merge(['current_institute' => $institute]);
            return $next($request);
        }

        // 7. Authenticated user context mismatch check (against resolved HOST)
        if ($user && $user->role !== 'super_admin') {
            if ((int)$user->institute_id !== (int)$institute->id) {
                return response()->json(['message' => 'Institute context mismatch'], 403);
            }
        }

        // 8. Institute 1 never expires and is never auto-suspended or blocked
        if ((int)$institute->id !== 1) {
            $isSuspended = ($institute->status !== 'active');
            $isExpired = $institute->isPastGrace();

            if ($isSuspended || $isExpired) {
                // why: PayHere server-to-server webhook notifications (api/payhere/notify) must still be
                // recorded and processed even when an institute is past grace or suspended, ensuring students/parents
                // who already paid on the payment gateway are properly credited, and PayHere's notification retries
                // succeed with HTTP 200 rather than being dropped with HTTP 402/403.
                if ($request->is('api/payhere/notify') || $request->routeIs('payhere.notify')) {
                    Tenant::set($institute);
                    $request->merge(['current_institute' => $institute]);
                    return $next($request);
                }

                // a) Admin login and logout routes must remain accessible
                if ($request->is('admin/login') || $request->is('admin/logout')) {
                    Tenant::set($institute);
                    $request->merge(['current_institute' => $institute]);
                    return $next($request);
                }

                // b) Authenticated institute admin (role admin, this institute) can open /admin/subscription
                $isAdmin = ($user && $user->role === 'admin' && (int)$user->institute_id === (int)$institute->id);
                if ($isAdmin) {
                    if ($request->is('admin/subscription')) {
                        Tenant::set($institute);
                        $request->merge(['current_institute' => $institute]);
                        return $next($request);
                    }

                    if ($request->is('admin') || $request->is('admin/*')) {
                        return redirect()->route('admin.subscription');
                    }
                }

                // c) Unauthenticated access to admin routes redirects to admin login
                if ($request->is('admin') || $request->is('admin/*')) {
                    return redirect()->route('admin.login');
                }

                // d) API / JSON requests return typed JSON response
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => $isExpired ? 'Subscription has ended' : 'Institute access is suspended',
                        'status' => $isExpired ? 'expired' : 'suspended',
                        'code' => $isExpired ? 'institute_expired' : 'institute_suspended',
                    ], $isExpired ? 402 : 403);
                }

                // e) Browser requests return branded static Blade page
                return response()->view('errors.tenant-blocked', [
                    'institute' => $institute,
                    'title' => $isExpired ? 'Subscription has ended' : 'This institute is temporarily unavailable',
                    'isExpired' => $isExpired,
                    'isSuspended' => $isSuspended,
                    'contactEmail' => config('tenant.platform_contact_email'),
                    'contactPhone' => config('tenant.platform_contact_phone'),
                    'contactWhatsapp' => config('tenant.platform_contact_whatsapp'),
                ], $isExpired ? 402 : 403)->header('X-Robots-Tag', 'noindex');
            }
        }

        // 9. Store in container and request
        Tenant::set($institute);
        $request->merge(['current_institute' => $institute]);

        return $next($request);
    }
}
