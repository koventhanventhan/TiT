<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login form
     */
    public function showLoginForm(Request $request)
    {
        // dd('Reached showLoginForm');
        // If already logged in, redirect to dashboard
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        
        // Ensure session is started to generate CSRF token
        $request->session()->start();
        
        // Check if token is provided (from React login)
        $token = $request->query('token');
        if ($token) {
            // dd('Token found in request', $token);
            // Try to authenticate using Sanctum token
            try {
                // Decode URL-encoded token if needed
                $token = urldecode($token);
                // dd('Before Sanctum check', $token);
                
                $tokenModel = \Laravel\Sanctum\Sanctum::personalAccessTokenModel();
                \Log::info('Token model class: ' . $tokenModel);
                $accessToken = $tokenModel::findToken($token);
                /*
                dd('After Sanctum check', [
                    'model' => $tokenModel,
                    'found' => $accessToken ? 'Yes' : 'No',
                    'tokenable' => $accessToken ? $accessToken->tokenable : 'N/A'
                ]);
                */
                
                if ($accessToken && $accessToken->tokenable) {
                    $user = $accessToken->tokenable;
                    \Log::info('Token found, user: ' . $user->email . ', role: ' . $user->role);
                    
                    // Only allow admin users
                    if ($user->role === 'admin') {
                        /*
                        dd('User is admin, attempting login', [
                            'user_id' => $user->id,
                            'role' => $user->role,
                            'session_id' => $request->session()->getId()
                        ]);
                        */
                        Auth::login($user);
                        $request->session()->regenerate();
                        \Log::info('Admin authenticated, redirecting to dashboard');
                        return redirect()->route('admin.dashboard');
                    } else {
                        \Log::warning('User is not admin', ['user_id' => $user->id, 'role' => $user->role]);
                        // Redirect to React frontend with error message
                        $frontendUrl = env('FRONTEND_URL', 'http://localhost:4000');
                        return redirect($frontendUrl . '?error=admin_required');
                    }
                } else {
                    \Log::warning('Token not found or invalid', ['token' => substr($token, 0, 20) . '...']);
                    // Redirect to React frontend
                    $frontendUrl = env('FRONTEND_URL', 'http://localhost:4000');
                    return redirect($frontendUrl . '?error=invalid_token');
                }
            } catch (\Exception $e) {
                // Token invalid, redirect to React frontend
                \Log::error('Token authentication failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $frontendUrl = env('FRONTEND_URL', 'http://localhost:4000');
                return redirect($frontendUrl . '?error=login_required');
            }
        }
        
        // No token provided - show the actual login form
        return view('admin.auth.login');
    }

    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $credentials = $request->only('email', 'password');
            $remember = $request->filled('remember');

            if (Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();
                return redirect()->intended(route('admin.dashboard'));
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        } catch (\Illuminate\Session\TokenMismatchException $e) {
            // CSRF token mismatch - redirect back with error
            return redirect()->route('admin.login')
                ->withInput($request->except('password'))
                ->withErrors(['_token' => 'Your session has expired. Please try again.']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.login')
                ->withInput($request->except('password'))
                ->withErrors(['email' => 'An error occurred. Please try again.']);
        }
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        // Get the user before logout
        $user = Auth::user();
        
        // Logout from web session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Redirect to React frontend with logout parameter
        // React will detect this and clear localStorage
        $reactUrl = env('FRONTEND_URL', 'http://localhost:4000');
        return redirect($reactUrl . '?logout=1&from=dashboard');
    }
}
