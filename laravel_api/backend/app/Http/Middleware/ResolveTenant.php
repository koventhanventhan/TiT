<?php

namespace App\Http\Middleware;

use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $cacheKey = "tenant_domain_{$bareHost}";
        $instituteId = Cache::remember($cacheKey, 300, function () use ($bareHost, $host) {
            $domainRecord = InstituteDomain::whereIn('domain', [$bareHost, $host, 'www.' . $bareHost])
                ->first();
            return $domainRecord?->institute_id;
        });

        if ($instituteId) {
            $institute = Institute::find($instituteId);
            $matchedViaDomain = true;
        }

        // 3. Safety net: Host of config('app.url') (if not shared localhost) and titjaffna.lk always resolves to Institute 1
        // even if the table row is missing or not yet populated.
        if (!$institute && !$isSharedLocalHost) {
            $appUrlHost = strtolower((string)parse_url(config('app.url', ''), PHP_URL_HOST));
            $appUrlHost = preg_replace('/:\d+$/', '', $appUrlHost);
            $bareAppUrlHost = preg_replace('/^www\./', '', $appUrlHost);

            if (($bareAppUrlHost && !in_array($bareAppUrlHost, ['localhost', '127.0.0.1'], true) && $bareHost === $bareAppUrlHost) || $bareHost === 'titjaffna.lk') {
                $institute = Institute::find(1);
                $matchedViaDomain = true;
            }
        }

        // 4. On shared local hosts in non-production environment:
        if (!$matchedViaDomain && !$isProduction && $isSharedLocalHost) {
            // Check X-Institute-Id header if provided
            if ($request->hasHeader('X-Institute-Id')) {
                $headerId = (int)$request->header('X-Institute-Id');
                if ($headerId > 0) {
                    $institute = Institute::find($headerId);
                }
            }

            // If user is authenticated and no header, use user's institute
            if (!$institute) {
                $user = auth('web')->user();
                if (!$user && $request->bearerToken()) {
                    $user = auth('sanctum')->user();
                }
                if ($user && $user->institute_id) {
                    $institute = Institute::find($user->institute_id);
                }
            }

            // Fallback to default institute
            if (!$institute) {
                $defaultId = (int)config('tenant.default_institute_id', 1);
                $institute = Institute::find($defaultId) ?: Institute::first();
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

        // 6. Platform / Super-admin unrestricted access
        $user = auth()->user();
        if (!$user && $request->bearerToken()) {
            $user = auth('sanctum')->user();
        }

        $platformHosts = config('tenant.platform_hosts', []);
        $isPlatformHost = in_array($bareHost, $platformHosts, true) || in_array($host, $platformHosts, true);
        if ($isPlatformHost && $user && $user->role === 'super_admin') {
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

        // 8. Verify Institute status
        if ($institute->status !== 'active') {
            return response()->json([
                'message' => 'Institute access is ' . $institute->status,
                'status' => $institute->status,
            ], 403);
        }

        // 9. Check subscription expiry
        if ($institute->expires_at && $institute->expires_at->isPast()) {
            return response()->json([
                'message' => 'Institute subscription has expired',
                'status' => 'expired',
            ], 403);
        }

        // 10. Store in container and request
        Tenant::set($institute);
        $request->merge(['current_institute' => $institute]);

        return $next($request);
    }
}
