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
            $state = $user->studentAccessState();

            switch ($state) {
                case 'deactivated':
                    return response()->json([
                        'message' => 'Your account has been deactivated. Please contact administration.',
                    ], 403);

                case 'pending_registration':
                    return response()->json([
                        'message' => 'Please complete your registration payment first.',
                        'registration_status' => 'pending_payment',
                        'redirect' => '/register'
                    ], 403);

                case 'pending_approval':
                    return response()->json([
                        'message' => 'Your account is pending admin approval.',
                        'registration_status' => $user->registration_status,
                        'is_confirmed' => false
                    ], 403);

                case 'payment_due':
                    return response()->json([
                        'message' => 'Please complete your monthly payment to access the dashboard.',
                        'registration_status' => $user->registration_status,
                        'needs_payment' => true,
                        'redirect' => '/student/payment'
                    ], 403);

                case 'ok':
                default:
                    break;
            }
        }

        return $next($request);
    }
}
