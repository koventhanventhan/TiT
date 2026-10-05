<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            $timeout = $user->profile_settings['session_timeout'] ?? null;

            if ($timeout) {
                // Laravel session lifetime is in minutes.
                // We can't easily change the config per-user because it's loaded early,
                // but we can check the 'last_activity' manually if we want strict control.
                // However, the simplest way to "honor" this is to rely on Laravel's session 
                // but this setting is more of a preference for how long the session SHOULD last.
                
                // For a real implementation, we would store 'last_activity' in the session 
                // and compare it here.
                $lastActivity = session('last_activity_time');
                $currentTime = time();
                
                if ($lastActivity && ($currentTime - $lastActivity) > ($timeout * 60)) {
                    auth()->logout();
                    session()->flush();
                    return redirect()->route('admin.login')->with('error', 'Session expired due to inactivity.');
                }
                
                session(['last_activity_time' => $currentTime]);
            }
        }

        return $next($request);
    }
}
