<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public static function emailThrottleKey(string $email, string $ip): string
    {
        return 'admin_login:' . Str::transliterate(Str::lower(trim($email))) . '|' . $ip;
    }

    public static function ipThrottleKey(string $ip): string
    {
        return 'admin_login_ip:' . $ip;
    }

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
            $tokenThrottleKey = 'admin_token_login_ip:' . $request->ip();
            if (RateLimiter::tooManyAttempts($tokenThrottleKey, 10)) {
                event(new Lockout($request));
                ActivityLogService::log(
                    'admin_token_login_lockout',
                    "Admin token login lockout for IP: {$request->ip()}",
                    ['ip' => $request->ip()]
                );
                return redirect($this->resolveFrontendUrl($request) . '?error=too_many_attempts');
            }

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
                    
                    // Only allow admin or super_admin users belonging to this institute
                    $currentInstId = function_exists('current_institute_id') ? current_institute_id() : null;
                    if ($user->role !== 'super_admin' && $currentInstId && (int) $user->institute_id !== (int) $currentInstId) {
                        RateLimiter::hit($tokenThrottleKey, 60);
                        \Log::warning('Cross-tenant admin token rejected', [
                            'user_id' => $user->id,
                            'user_institute_id' => $user->institute_id,
                            'current_institute_id' => $currentInstId,
                        ]);
                        return redirect($this->resolveFrontendUrl($request) . '?error=admin_required');
                    }

                    if ($user->role === 'admin' || $user->role === 'super_admin') {
                        RateLimiter::clear($tokenThrottleKey);
                        Auth::login($user);
                        $request->session()->regenerate();
                        \Log::info('Admin authenticated, redirecting to dashboard');
                        return redirect()->route('admin.dashboard');
                    } else {
                        RateLimiter::hit($tokenThrottleKey, 60);
                        \Log::warning('User is not admin', ['user_id' => $user->id, 'role' => $user->role]);
                        return redirect($this->resolveFrontendUrl($request) . '?error=admin_required');
                    }
                } else {
                    RateLimiter::hit($tokenThrottleKey, 60);
                    \Log::warning('Token not found or invalid', ['token' => substr($token, 0, 20) . '...']);
                    return redirect($this->resolveFrontendUrl($request) . '?error=invalid_token');
                }
            } catch (\Exception $e) {
                RateLimiter::hit($tokenThrottleKey, 60);
                \Log::error('Token authentication failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect($this->resolveFrontendUrl($request) . '?error=login_required');
            }
        }
        
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

            $email = strtolower(trim((string) $credentials['email']));
            $ip = (string) $request->ip();
            $emailThrottleKey = static::emailThrottleKey($email, $ip);
            $ipThrottleKey = static::ipThrottleKey($ip);

            $isEmailLocked = RateLimiter::tooManyAttempts($emailThrottleKey, 5);
            $isIpLocked = RateLimiter::tooManyAttempts($ipThrottleKey, 20);

            if ($isEmailLocked || $isIpLocked) {
                event(new Lockout($request));

                $seconds = max(
                    $isEmailLocked ? RateLimiter::availableIn($emailThrottleKey) : 0,
                    $isIpLocked ? RateLimiter::availableIn($ipThrottleKey) : 0
                );
                if ($seconds <= 0) {
                    $seconds = 60;
                }

                ActivityLogService::log(
                    'admin_login_lockout',
                    "Admin login lockout for email: {$email} from IP: {$ip}",
                    [
                        'email' => $email,
                        'ip' => $ip,
                        'seconds_remaining' => $seconds,
                        'reason' => $isEmailLocked ? 'email_limit' : 'ip_limit',
                    ]
                );

                $message = "Too many sign-in attempts. Please wait {$seconds} seconds and try again.";

                if ($request->expectsJson()) {
                    $exception = ValidationException::withMessages([
                        'email' => [$message],
                    ]);
                    $exception->status(429);
                    throw $exception;
                }

                return redirect()->route('admin.login')
                    ->withInput($request->except('password'))
                    ->withErrors(['email' => $message]);
            }

            if (Auth::attempt($credentials, $remember)) {
                $user = Auth::user();

                // Domain / Tenant verification: If admin belongs to another institute, reject with standard invalid credentials
                $currentInstId = function_exists('current_institute_id') ? current_institute_id() : null;
                if ($user->role !== 'super_admin' && $currentInstId && (int)$user->institute_id !== (int)$currentInstId) {
                    Auth::logout();
                    RateLimiter::hit($emailThrottleKey, 60);
                    RateLimiter::hit($ipThrottleKey, 60);
                    throw ValidationException::withMessages([
                        'email' => ['The provided credentials do not match our records.'],
                    ]);
                }

                // Ensure only admins/super_admins can login to the admin panel
                if ($user->role !== 'admin' && $user->role !== 'super_admin') {
                    Auth::logout();
                    RateLimiter::hit($emailThrottleKey, 60);
                    RateLimiter::hit($ipThrottleKey, 60);
                    return redirect()->route('admin.login')->with('error', 'நிர்வாகி (Admin) அனுமதி தேவை. (Admin access required.)');
                }
                if (!$user->isActive()) {
                    Auth::logout();
                    RateLimiter::hit($emailThrottleKey, 60);
                    RateLimiter::hit($ipThrottleKey, 60);
                    return redirect()->route('admin.login')->with('error', 'நிர்வாகி உங்கள் கணக்கை முடக்கியுள்ளார். (Your account has been deactivated by admin.)');
                }

                RateLimiter::clear($emailThrottleKey);
                $request->session()->regenerate();
                return redirect()->intended(route('admin.dashboard'));
            }

            RateLimiter::hit($emailThrottleKey, 60);
            RateLimiter::hit($ipThrottleKey, 60);

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
        
        // Redirect to tenant-aware frontend with logout parameter
        // React will detect this and clear localStorage
        $reactUrl = $this->resolveFrontendUrl($request);
        return redirect($reactUrl . '?logout=1&from=dashboard');
    }

    private function resolveFrontendUrl(Request $request): string
    {
        $configured = rtrim((string) config('services.frontend_url', ''), '/');
        $configuredHost = strtolower((string) parse_url($configured, PHP_URL_HOST));
        $requestHost = strtolower((string) $request->getHost());
        $currentInstId = function_exists('current_institute_id') ? (int) current_institute_id() : 0;

        if ($currentInstId > 1 || ($requestHost !== '' && $configuredHost !== '' && $requestHost !== $configuredHost)) {
            return rtrim($request->getSchemeAndHttpHost(), '/');
        }

        return $configured !== '' ? $configured : rtrim($request->getSchemeAndHttpHost(), '/');
    }
}
