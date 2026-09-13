<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ProfileContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $profileId = $request->header('X-Profile-Id');
        
        if ($profileId) {
            $parentUser = Auth::user();
            
            if ($parentUser) {
                // If the requested profile ID is the parent itself, or a child of the parent
                if ($parentUser->id == $profileId || $parentUser->children()->where('id', $profileId)->exists()) {
                    $profileUser = User::find($profileId);
                    if ($profileUser) {
                        Auth::setUser($profileUser);
                    }
                }
            }
        }

        return $next($request);
    }
}
