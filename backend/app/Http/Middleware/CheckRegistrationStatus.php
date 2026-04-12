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
            // 1. Block if registration payment is not even chosen
            if ($user->registration_status === 'pending_payment') {
                return response()->json([
                    'message' => 'Please complete your registration payment first.',
                    'registration_status' => 'pending_payment',
                    'redirect' => '/register'
                ], 403);
            }

            // 2. Block if admin has not confirmed yet
            if (!$user->admin_confirmed_at) {
                return response()->json([
                    'message' => 'Your account is pending admin approval.',
                    'registration_status' => $user->registration_status,
                    'is_confirmed' => false
                ], 403);
            }

            // 3. Block if student has not paid for the current month
            if (!$user->hasPaidForMonth(now()->format('Y-m'))) {
                return response()->json([
                    'message' => 'Please complete your monthly payment to access the dashboard.',
                    'registration_status' => $user->registration_status,
                    'needs_payment' => true,
                    'redirect' => '/student/payment'
                ], 403);
            }
        }

        return $next($request);
    }
}
