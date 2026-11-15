<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminPanelAccess
{
    /**
     * Ensure that non-super users who are authenticated in another guard (e.g. web institute admin)
     * are shown a friendly 403 page instead of a raw 403 or being silently dropped.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('super_admin')->user() ?: auth('web')->user() ?: $request->user();

        if ($user && $user->role !== 'super_admin') {
            abort(403, 'This area is for the platform owner');
        }

        return $next($request);
    }
}
