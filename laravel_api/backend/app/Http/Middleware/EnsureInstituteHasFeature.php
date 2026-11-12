<?php

namespace App\Http\Middleware;

use App\Models\Institute;
use Closure;
use Illuminate\Http\Request;

class EnsureInstituteHasFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $feature
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $feature)
    {
        $institute = Institute::current()
            ?? (auth()->check() ? auth()->user()->institute : null)
            ?? Institute::find(
                (int) ($request->header('X-Institute-Id') ?: (function_exists('current_institute_id') ? current_institute_id() : 1))
            );

        if (!$institute || $institute->hasFeature($feature)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'This feature is not included in your plan',
                'code' => 'feature_not_in_plan',
                'feature' => $feature,
            ], 403);
        }

        return response()->view('errors.feature-not-in-plan', [
            'feature' => $feature,
            'institute' => $institute,
        ], 403);
    }
}

