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
        // Simple tenant identification from header or session
        $instituteId = $request->header('X-Institute-Id');
        
        if ($instituteId) {
            session(['institute_id' => $instituteId]);
        }
        
        return $next($request);
    }
}
