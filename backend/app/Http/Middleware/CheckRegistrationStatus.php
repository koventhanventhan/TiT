<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRegistrationStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Only enforce for students (role 'user')
        if ($user && $user->role === 'user') {
            // If the user hasn't completed the payment step
            if ($user->registration_status === 'pending_payment') {
                return response()->json([
                    'message' => 'Please complete your registration payment first.',
                    'registration_status' => 'pending_payment',
                    'redirect' => '/register'
                ], 403);
            }
        }

        return $next($request);
    }
}
