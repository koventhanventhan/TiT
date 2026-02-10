<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        // Determine registration type based on fields provided
        $isAdminRegistration = $request->has('first_name') && $request->has('last_name') && $request->has('phone_number');
        $isStudentRegistration = $request->has('full_name') || $request->has('date_of_birth') || $request->has('school_name');

        // Base validation
        $validationRules = [];

        // Admin registration validation
        if ($isAdminRegistration) {
            $validationRules['email'] = 'required|string|email|max:255|unique:users';
            $validationRules['password'] = 'required|string|min:8';
            $validationRules['first_name'] = 'required|string|max:255';
            $validationRules['last_name'] = 'required|string|max:255';
            $validationRules['phone_number'] = 'required|string|max:20';
            $validationRules['username'] = 'nullable|string|max:255|unique:users,name';
        } else {
            // Student registration validation - no email/password required
            $validationRules['username'] = 'required|string|max:255|unique:users,name';
            $validationRules['full_name'] = 'required|string|max:255';
            $validationRules['date_of_birth'] = 'required|date';
            $validationRules['gender'] = 'required|in:male,female';
            $validationRules['school_name'] = 'required|string|max:255';
            $validationRules['medium'] = 'required|in:english,tamil';
            $validationRules['online_experience'] = 'required|boolean';
            $validationRules['device_used'] = 'required|string|max:255';
            $validationRules['current_grade'] = 'required|string|max:50';
            $validationRules['stream'] = 'nullable|string|max:50|in:arts,bio_maths';
            $validationRules['selected_subjects'] = 'required|string';
        }

        $request->validate($validationRules);

        // Generate username if not provided (for admin)
        $username = $request->username ?? ($isAdminRegistration ? $request->email : null);
        if (!$username) {
            $username = $isAdminRegistration ? $request->email : 'student_' . time(); // Fallback
        }

        $userData = [
            'name' => $username,
            'role' => $isAdminRegistration ? 'admin' : 'user',
        ];

        // Add email and password only for admin registration
        if ($isAdminRegistration) {
            $userData['email'] = $request->email;
            $userData['password'] = Hash::make($request->password);
        } else {
            // For student registration, set default email and password
            $userData['email'] = $username . '@student.local';
            $userData['password'] = Hash::make('student123'); // Default password
        }

        // Add admin fields if provided
        if ($isAdminRegistration) {
            $userData['first_name'] = $request->first_name;
            $userData['last_name'] = $request->last_name;
            $userData['phone_number'] = $request->phone_number;
        }

        // Add student fields if provided
        if ($isStudentRegistration) {
            if ($request->has('full_name')) {
                $userData['full_name'] = $request->full_name;
            }
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
        }
        
        // Assign default institute for all registrations
        $userData['institute_id'] = 1; 

        $user = User::create($userData);

        $token = $user->createToken('auth_token')->plainTextToken;

        $responseData = [
            'id' => $user->id,
            'username' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        // Add admin fields to response
        if ($isAdminRegistration) {
            $responseData['first_name'] = $user->first_name;
            $responseData['last_name'] = $user->last_name;
            $responseData['phone_number'] = $user->phone_number;
        }

        // Add student fields to response
        if ($isStudentRegistration) {
            $responseData['full_name'] = $user->full_name;
            $responseData['date_of_birth'] = $user->date_of_birth;
            $responseData['gender'] = $user->gender;
            $responseData['school_name'] = $user->school_name;
            $responseData['medium'] = $user->medium;
            $responseData['online_experience'] = $user->online_experience;
            $responseData['device_used'] = $user->device_used;
            $responseData['current_grade'] = $user->current_grade;
            $responseData['stream'] = $user->stream;
            $responseData['selected_subjects'] = $user->selected_subjects;
        }

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $responseData,
            'token' => $token,
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
        ]);

        // Check if input is email or username
        $field = filter_var($request->usernameOrEmail, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        
        $user = User::where($field, $request->usernameOrEmail)->first(); // Kept original logic for username/email

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid login credentials',
            ], 401);
        }

        // Check Institute Status
        if ($user->institute_id) {
            $institute = \App\Models\Institute::withoutGlobalScopes()->find($user->institute_id);
            if ($institute && $institute->status !== 'active') {
                return response()->json([
                    'message' => 'Your institute access is ' . $institute->status . '. Please contact support.',
                ], 403);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'username' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'institute_id' => $user->institute_id, // Added institute_id to login response
            ],
            'token' => $token,
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $u = $request->user();
        $data = [
            'id' => $u->id,
            'username' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'institute_id' => $u->institute_id, // Added institute_id to user data
        ];
        if ($u->full_name) {
            $data['full_name'] = $u->full_name;
        }
        if ($u->phone_number) {
            $data['phone_number'] = $u->phone_number;
        }
        return response()->json(['user' => $data]);
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
}

