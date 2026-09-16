<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ValidatesEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ValidatesEmail;
    use \App\Traits\HandlesDuplicateAccounts;

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        // Student registration validation
        // Email & Password are REQUIRED with strong security rules
        // NOTE: email uniqueness is checked MANUALLY below (not via validation rule)
        // so that incomplete registrations can be re-done
        $validationRules = [
            'username' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',      // at least 1 uppercase
                'regex:/[a-z]/',      // at least 1 lowercase
                'regex:/[0-9]/',      // at least 1 number
                'regex:/[@$!%*?&^#()_+\-=\[\]{}|;:,.<>\/\\\\]/', // at least 1 special char
            ],
            'full_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|digits_between:10,15|unique:users,phone_number',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'school_name' => 'nullable|string|max:255',
            'medium' => 'nullable|in:english,tamil',
            'online_experience' => 'nullable|boolean',
            'device_used' => 'nullable|string|max:255',
            'current_grade' => 'nullable|string|max:50',
            'stream' => 'nullable|string|max:50|in:arts,bio_maths',
            'selected_subjects' => 'nullable|string',
        ];

        $customMessages = [
            'email.required' => 'Email address is required / மின்னஞ்சல் முகவரி தேவை',
            'email.email' => 'Please enter a valid email address / சரியான மின்னஞ்சல் முகவரியை உள்ளிடவும்',
            'password.required' => 'Password is required / கடவுச்சொல் தேவை',
            'password.min' => 'Password must be at least 8 characters / கடவுச்சொல் குறைந்தது 8 எழுத்துகள் இருக்க வேண்டும்',
            'password.regex' => 'Password must include at least 1 uppercase, 1 lowercase, 1 number, and 1 special character',
        ];

        $request->validate($validationRules, $customMessages);

        // --- DNS/MX email domain validation ---
        // Reject emails with unreachable/non-existent domains BEFORE any DB operations
        if (!$this->isValidEmailForSending($request->email)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['This email address appears to be invalid or cannot receive emails. Please use a real, working email address. / இந்த மின்னஞ்சல் முகவரி தவறானது அல்லது வேலை செய்யவில்லை. சரியான மின்னஞ்சலை உள்ளிடவும்.']
                ]
            ], 422);
        }

        // --- Manual email uniqueness check ---
        // Allow re-registration if the user hasn't completed Step 1 (full_name is still NULL).
        // Once Step 1 is done (full_name is set), the email is permanently locked.
        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            // If registration is incomplete (full_name is NULL), allow re-try
            // by updating their password and returning a fresh token
            if ($existingUser->full_name === null) {
                \Log::info('AuthController@register - Allowing re-registration for incomplete user', [
                    'id' => $existingUser->id,
                    'email' => $existingUser->email,
                ]);
                
                // Update password (student may have forgotten the first one)
                $existingUser->update([
                    'password' => Hash::make($request->password),
                ]);

                $token = $existingUser->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'User registered successfully',
                    'user' => [
                        'id' => $existingUser->id,
                        'username' => $existingUser->name,
                        'email' => $existingUser->email,
                        'role' => $existingUser->role,
                        'full_name' => $existingUser->full_name,
                        'registration_status' => $existingUser->registration_status,
                    ],
                    'token' => $token,
                ], 200);
            }

            // Registration is complete
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['This email is already registered / இந்த மின்னஞ்சல் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது.']
                ]
            ], 422);
        }

        // --- New user registration ---
        $username = $request->username ?? $request->email;
        if (!$username) {
            $username = 'student_' . time();
        }

        $fullName = $request->full_name;
        if (!$fullName && $request->has('first_name') && $request->has('last_name')) {
            $fullName = $request->first_name . ' ' . $request->last_name;
        }

        $userData = [
            'name' => $username,
            'role' => 'user', // Always 'user' (student)
            'email' => $request->email,
            'password' => Hash::make($request->password), // Always hashed with Hash::make()
            'full_name' => $fullName,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
        ];

        // Add student fields if provided
        if ($request->has('date_of_birth')) {
            $userData['date_of_birth'] = $request->date_of_birth;
        }
        if ($request->has('gender')) {
            $userData['gender'] = $request->gender;
        }
        if ($request->has('school_name')) {
            $userData['school_name'] = $request->school_name;
        }
        if ($request->has('medium')) {
            $userData['medium'] = $request->medium;
        }
        if ($request->has('online_experience')) {
            $userData['online_experience'] = $request->boolean('online_experience');
        }
        if ($request->has('device_used')) {
            $userData['device_used'] = is_array($request->device_used) 
                ? json_encode($request->device_used) 
                : $request->device_used;
        }
        if ($request->has('current_grade')) {
            $userData['current_grade'] = $request->current_grade;
        }
        if ($request->has('stream')) {
            $userData['stream'] = $request->stream;
        }
        if ($request->has('selected_subjects')) {
            $userData['selected_subjects'] = $request->selected_subjects;
        }
        
        // Identify Institute
        $instituteId = $request->header('X-Institute-Id') ?: 1;
        $institute = \App\Models\Institute::findOrFail($instituteId);

        // Enforce Subscription Limits
        if (!\App\Services\SubscriptionService::canAddStudent($institute)) {
            return response()->json(['message' => 'Student limit reached for this institute.'], 403);
        }
        
        $userData['institute_id'] = $institute->id;

        $user = User::create($userData);

        // NOTE: Admin notification is NOT sent here (basic signup).
        // It is sent in RegistrationController@step1 after the student completes
        // their full registration details (full_name, phone, etc.).
        // This prevents "ghost notifications" that link to incomplete student records (404).
        \Log::info('AuthController@register - New student created (notification deferred to step1)', [
            'id' => $user->id,
            'email' => $user->email,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $responseData = [
            'id' => $user->id,
            'username' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'full_name' => $user->full_name,
            'date_of_birth' => $user->date_of_birth,
            'gender' => $user->gender,
            'school_name' => $user->school_name,
            'medium' => $user->medium,
            'online_experience' => $user->online_experience,
            'device_used' => $user->device_used,
            'current_grade' => $user->current_grade,
            'stream' => $user->stream,
            'selected_subjects' => $user->selected_subjects,
        ];

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $responseData,
            'token' => $token,
        ], 201);
    }

    /**
     * Add a sibling (sub-profile) for an authenticated parent
     */
    public function addSibling(Request $request)
    {
        $parent = $request->user();

        $validationRules = [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'school_name' => 'nullable|string|max:255',
            'medium' => 'nullable|in:english,tamil',
            'current_grade' => 'required|string|max:50',
            'stream' => 'nullable|string|max:50|in:arts,bio_maths',
            'selected_subjects' => 'required|string',
            'online_experience' => 'nullable',
            'device_used' => 'nullable',
        ];

        $request->validate($validationRules);

        $internalKeys = [
            'first_name', 'last_name', 'full_name', 'date_of_birth', 
            'gender', 'school_name', 'medium', 'online_experience', 
            'device_used', 'current_grade', 'stream', 'selected_subjects', 
            '_token'
        ];
        $customFieldsData = array_diff_key($request->all(), array_flip($internalKeys));

        $userData = $request->only([
            'first_name', 'last_name', 'full_name', 'date_of_birth', 'gender',
            'school_name', 'medium', 'current_grade', 'stream', 'selected_subjects'
        ]);

        $userData['online_experience'] = $request->has('online_experience') ? $request->boolean('online_experience') : null;
        $userData['device_used'] = is_array($request->device_used) ? json_encode($request->device_used) : $request->device_used;
        $userData['custom_fields'] = !empty($customFieldsData) ? $customFieldsData : null;

        $userData['parent_id'] = $parent->id;
        $userData['name'] = strtolower(str_replace(' ', '_', $request->full_name)) . rand(1000, 9999);
        $userData['email'] = 'sibling_' . $parent->id . '_' . time() . '@child.local';
        $userData['password'] = $parent->password; // Inherit parent's password
        $userData['role'] = 'user';
        $userData['registration_status'] = $parent->registration_status; // Inherit parent's status
        $userData['admin_confirmed_at'] = $parent->admin_confirmed_at; // Inherit parent's confirmation
        $userData['institute_id'] = $parent->institute_id;
        
        $sibling = User::create($userData);

        return response()->json([
            'message' => 'Sibling profile added successfully',
            'profile' => [
                'id' => $sibling->id,
                'username' => $sibling->name,
                'email' => $sibling->email,
                'role' => $sibling->role,
                'full_name' => $sibling->full_name,
                'medium' => $sibling->medium,
                'current_grade' => $sibling->current_grade,
                'selected_subjects' => $sibling->selected_subjects,
                'institute_id' => $sibling->institute_id,
                'is_deactivated' => !$sibling->isActive(),
                'deactivated_at' => $sibling->deactivated_at,
                'admin_confirmed_at' => $sibling->admin_confirmed_at,
                'registration_status' => $sibling->registration_status,
                'is_paid' => false,
            ]
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'usernameOrEmail' => 'required|string',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ]);

        // Check if input is email or username
        $field = filter_var($request->usernameOrEmail, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        
        $user = User::where($field, $request->usernameOrEmail)->first(); // Kept original logic for username/email

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid login credentials',
            ], 401);
        }

        // Check Admin Approval for Students
        if ($user->role === 'user') {
            // 1. Check Registration Confirmation or Pending Status
            if (!$user->admin_confirmed_at || in_array($user->registration_status, ['pending', 'pending_payment'])) {
                // Auto-approve if they have a successful payment
                $hasPaid = \App\Models\Payment::where('user_id', $user->id)
                    ->where('status', 'paid')
                    ->exists();
                if ($hasPaid) {
                    $user->update([
                        'admin_confirmed_at' => $user->admin_confirmed_at ?? now(),
                        'registration_status' => 'approved'
                    ]);
                    \Log::info('Auto-approved student during login due to existing payment', ['user_id' => $user->id]);
                } elseif (!$user->admin_confirmed_at) {
                    return response()->json([
                        'message' => 'Admin இன்னும் உங்கள் பதிவை உறுதிப்படுத்தவில்லை. தயவுசெய்து காத்திருக்கவும். (Account pending admin approval. Please wait.)',
                    ], 403);
                }
            }

            // 2. Check Payment Status for Manual/Pending users
            // If they completed Step 2 but admin hasn't marked as 'paid' yet
            if ($user->registration_status === 'payment_completed') {
                $isPaid = $user->hasPaidForMonth(now()->format('Y-m'));
                if (!$isPaid) {
                    return response()->json([
                        'message' => 'அட்மின் இன்னும் உங்கள் கட்டணத்தை உறுதிப்படுத்தவில்லை. தயவுசெய்து காத்திருக்கவும். (Admin yet to update your payment status. Please wait.)',
                    ], 403);
                }
            }
        }

        // Check if account is deactivated
        if (!$user->isActive()) {
            // ONLY block admin/teacher if deactivated. 
            // Allow students ('user') to login so they can see the "Pending Payment" screen.
            if ($user->role !== 'user') {
                return response()->json([
                    'message' => 'நிர்வாகி உங்கள் கணக்கை முடக்கியுள்ளார். தயவுசெய்து எங்களைத் தொடர்பு கொள்ளவும். (Your account has been deactivated by admin. Please contact support.)',
                ], 403);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // For web session (if using first-party domains), we can login via Auth guard
        // This sets the laravel_session cookie correctly with the remember flag
        if (filter_var($request->usernameOrEmail, FILTER_VALIDATE_EMAIL) || $user->name) {
            \Illuminate\Support\Facades\Auth::guard('web')->login($user, $request->boolean('remember'));
        }

        $profiles = collect([$user])->merge($user->children)->map(function ($profile) {
            return [
                'id' => $profile->id,
                'username' => $profile->name,
                'email' => $profile->email,
                'role' => $profile->role,
                'full_name' => $profile->full_name,
                'medium' => $profile->medium,
                'current_grade' => $profile->current_grade,
                'selected_subjects' => $profile->selected_subjects,
                'institute_id' => $profile->institute_id,
                'is_deactivated' => !$profile->isActive(),
                'deactivated_at' => $profile->deactivated_at,
                'admin_confirmed_at' => $profile->admin_confirmed_at,
                'registration_status' => $profile->registration_status,
                'is_paid' => $profile->hasPaidForMonth(now()->format('Y-m')),
            ];
        });

        return response()->json([
            'message' => 'Login successful',
            'user' => $profiles->first(), // Maintain backwards compatibility for single accounts
            'profiles' => $profiles,
            'token' => $token,
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $path = $file->store('avatars', 'public');

            // Delete old avatar if it exists
            if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
                // Ignore errors on delete to prevent failing if file doesn't actually exist
                try {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
                } catch (\Exception $e) {}
            }

            $user->avatar = $path;
            $user->save();

            return response()->json([
                'message' => 'Avatar updated successfully',
                'avatar' => '/storage/' . $path
            ]);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $u = $request->user();
        
        // Auto-approve logic for users stuck in pending_payment but have a paid status
        if (!$u->admin_confirmed_at || in_array($u->registration_status, ['pending', 'pending_payment'])) {
            $hasPaid = \App\Models\Payment::where('user_id', $u->id)
                ->where('status', 'paid')
                ->exists();
            if ($hasPaid) {
                $u->update([
                    'admin_confirmed_at' => $u->admin_confirmed_at ?? now(),
                    'registration_status' => 'approved'
                ]);
                $u->refresh();
                \Log::info('Auto-approved student during user API fetch due to existing payment', ['user_id' => $u->id]);
            }
        }

        $data = [
            'id' => $u->id,
            'username' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'institute_id' => $u->institute_id, // Added institute_id to user data
        ];
        if ($u->avatar) {
            // Check if it's already a full URL or starts with /storage/
            $data['avatar'] = (str_starts_with($u->avatar, 'http') || str_starts_with($u->avatar, '/storage/')) 
                ? $u->avatar 
                : '/storage/' . $u->avatar;
        }
        if ($u->full_name) {
            $data['full_name'] = $u->full_name;
        }
        if ($u->name) {
            $data['name'] = $u->name;
        }
        if ($u->first_name) {
            $data['first_name'] = $u->first_name;
        }
        if ($u->last_name) {
            $data['last_name'] = $u->last_name;
        }
        if ($u->phone_number) {
            $data['phone_number'] = $u->phone_number;
        }
        if ($u->selected_subjects) {
            $data['selected_subjects'] = $u->selected_subjects;
        }
        return response()->json([
            'user' => array_merge($data, [
                'is_deactivated' => !$u->isActive(),
                'deactivated_at' => $u->deactivated_at,
                'admin_confirmed_at' => $u->admin_confirmed_at,
                'registration_status' => $u->registration_status,
                'is_paid' => $u->hasPaidForMonth(now()->format('Y-m')),
            ])
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Create web session from API token (for admin dashboard access)
     */
    public function createWebSession(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated',
                    'error' => 'Unauthorized',
                ], 401);
            }
            
            // Only allow admin users to access admin dashboard
            if ($user->role !== 'admin') {
                return response()->json([
                    'message' => 'Access denied. Admin role required.',
                    'error' => 'Unauthorized',
                ], 403);
            }
            
            // Start session if not already started
            if (!$request->hasSession()) {
                $request->session()->start();
            }
            
            // Log the user in via web session (using web guard)
            \Illuminate\Support\Facades\Auth::guard('web')->login($user);
            
            // Regenerate session ID for security
            $request->session()->regenerate();
            
            // Store user in session
            $request->session()->put('auth.user_id', $user->id);
            
            \Log::info('Web session created for admin', [
                'user_id' => $user->id,
                'email' => $user->email,
                'session_id' => $request->session()->getId()
            ]);
            
            $redirectUrl = route('admin.dashboard');
            
            return response()->json([
                'message' => 'Web session created successfully',
                'redirect_url' => $redirectUrl,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create web session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to create web session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Find or create user
            $user = User::where('email', $googleUser->getEmail())->first();
            
            if (!$user) {
                // Determine institute
                $instituteId = 1; // Default or based on logic
                
                $fullName = $googleUser->getName() ?? $googleUser->getNickname() ?? explode('@', $googleUser->getEmail())[0];
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';
                
                $plainPassword = Str::random(10);
                
                $user = User::create([
                    'name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'full_name' => $fullName,
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make($plainPassword),
                    'role' => 'user',
                    'institute_id' => $instituteId,
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                    'registration_status' => 'pending', // Requires admin approval normally
                ]);

                // Send the generated plain text password to the student (with email validation)
                if ($this->isValidEmailForSending($user->email)) {
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\GoogleAutoPasswordMail($user, $plainPassword, false));
                } else {
                    \Illuminate\Support\Facades\Log::warning('AuthController: Skipped password email — invalid address: ' . $user->email);
                }
            } else {
                // Update google_id if not set
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Prepare user data for frontend
            $userData = [
                'id' => $user->id,
                'username' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'full_name' => $user->full_name,
                'selected_subjects' => $user->selected_subjects,
                'institute_id' => $user->institute_id,
                'registration_status' => $user->registration_status,
                'is_deactivated' => !$user->isActive(),
            ];

            // Return a script that sends the token back to the main window and closes the popup
            $data = json_encode([
                'token' => $token,
                'user' => $userData,
                'message' => 'Login successful',
            ]);

            return response("
                <script>
                    window.opener.postMessage($data, '*');
                    window.close();
                </script>
            ");

        } catch (\Exception $e) {
            \Log::error('Google Auth Error: ' . $e->getMessage());
            
            $error = json_encode([
                'error' => 'Authentication failed',
                'message' => $e->getMessage()
            ]);

            return response("
                <script>
                    window.opener.postMessage($error, '*');
                    window.close();
                </script>
            ");
        }
    }
}

