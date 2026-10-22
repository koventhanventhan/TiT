<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional Sanctum Authentication Middleware.
 *
 * If a valid Sanctum Bearer token is provided, authenticates the user for the request.
 * If no token is provided or the token is invalid, allows the request to continue as
 * an unauthenticated public visitor without throwing 401 Unauthorized.
 */
class OptionalSanctumAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if ($token) {
            try {
                $user = Auth::guard('sanctum')->user();
                if ($user) {
                    Auth::setUser($user);
                    $request->setUserResolver(fn() => $user);
                }
            } catch (\Throwable $e) {
                // Ignore invalid tokens; treat request as public visitor
            }
        }

        return $next($request);
    }
}
