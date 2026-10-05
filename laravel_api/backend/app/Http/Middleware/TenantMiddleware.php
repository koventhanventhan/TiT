<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. If user is super admin, they have unrestricted access
        if (auth()->check() && auth()->user()->role === 'super_admin') {
            return $next($request);
        }

        // 2. Identify institute context
        $instituteId = $request->header('X-Institute-Id');
        
        // If logged in, prioritize the user's institute_id
        if (auth()->check()) {
            $user = auth()->user();
            
            // Security check: If header is provided, it must match user's institute
            if ($instituteId && $instituteId != $user->institute_id) {
                return response()->json(['message' => 'Institute context mismatch'], 403);
            }
            
            $instituteId = $user->institute_id;
        }

        if (!$instituteId) {
            // Default to institute 1 for single-tenant setups
            $instituteId = 1;
        }

        // 3. Verify Institute status
        $institute = \App\Models\Institute::find($instituteId);
        if (!$institute) {
            return response()->json(['message' => 'Institute not found'], 404);
        }

        if ($institute->status !== 'active') {
            return response()->json([
                'message' => 'Institute access is ' . $institute->status,
                'status' => $institute->status
            ], 403);
        }

        // 4. Check subscription expiry
        if ($institute->expires_at && $institute->expires_at->isPast()) {
            return response()->json([
                'message' => 'Institute subscription has expired',
                'status' => 'expired'
            ], 403);
        }

        // Save to session or request for downstream usage if needed
        $request->merge(['current_institute' => $institute]);
        
        return $next($request);
    }
}
