<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payment;
use App\Notifications\AdminNotification;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    protected WhatsAppService $whatsApp;

    public function __construct(WhatsAppService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
    }

    /**
     * Helper to get subject category based on user's grade and stream
     */
    private function getSubjectCategoryForUser(User $user)
    {
        $grade = $user->current_grade;
        $stream = strtolower($user->stream ?? '');
        $gradeNum = 0;

        if (preg_match('/Grade\s*(\d+)/i', $grade, $m)) {
            $gradeNum = (int)$m[1];
        } elseif (preg_match('/(\d+)/', $grade, $m)) {
            $gradeNum = (int)$m[1];
        }

        if ($gradeNum >= 1 && $gradeNum <= 5) {
            return 'grade_1_to_5';
        }

        if ($gradeNum >= 6 && $gradeNum <= 11) {
            return 'grade_6_to_11';
        }

        if ($gradeNum >= 12 && $gradeNum <= 13) {
            if (str_contains($stream, 'art')) {
                return 'arts_stream';
            }
            if (str_contains($stream, 'bio') || str_contains($stream, 'math')) {
                return 'bio_maths_stream';
            }
            // Add other streams as needed
            return 'grade_6_to_11'; // Fallback
        }

        return null;
    }

    /**
     * Get payment details for the current user (used by DeactivatedDashboard)
     */
    public function getPaymentDetails(Request $request)
    {
        $user = $request->user();
        if (!$user->selected_subjects) {
            return response()->json([
                'total' => 500,
                'subjects' => []
            ]);
        }

        $subjects = $user->selected_subjects;
        \Illuminate\Support\Facades\Log::info("Payment details request for User ID: {$user->id}, Email: {$user->email}, Raw Subjects: '{$subjects}'");

        if (empty($subjects)) {
            return response()->json([
                'total' => 500,
                'subjects' => [],
                'debug' => 'Empty subjects string'
            ]);
        }

        $trimmedSubjects = trim($subjects);
        $selectedSubjectsRaw = [];
        try {
            if (str_starts_with($trimmedSubjects, '[')) {
                $selectedSubjectsRaw = json_decode($trimmedSubjects, true);
            } else {
                $selectedSubjectsRaw = array_map('trim', explode(',', $trimmedSubjects));
            }
        } catch (\Exception $e) {
            $selectedSubjectsRaw = array_map('trim', explode(',', $subjects));
        }

        $selectedSubjects = array_unique(array_filter($selectedSubjectsRaw));

        if (empty($selectedSubjects)) {
            \Illuminate\Support\Facades\Log::warning("No subjects parsed for User ID: {$user->id}");
            return response()->json([
                'total' => 500.0,
                'subjects' => [],
                'debug' => 'No subjects parsed from string: ' . $subjects
            ]);
        }

        $category = $this->getSubjectCategoryForUser($user);

        $subjectData = \App\Models\Subject::when($category, function($query) use ($category) {
            return $query->where('category', $category);
        })->whereIn('name', $selectedSubjects)->get(['name', 'price']);

        $total = $subjectData->sum('price');

        return response()->json([
            'total' => $total > 0 ? (float)$total : 500.0,
            'subjects' => $subjectData,
            'category' => $category,
            'user_grade' => $user->current_grade
        ]);
    }
    /**
     * Step 1: Validate student form and create user with pending_payment.
     * Requires phone_number for WhatsApp.
     */
    public function step1(Request $request)
    {
        // Check if user is already authenticated (via basic signup or login)
        // We ONLY update if the authenticated user is a 'user' (student)
        $user = auth('sanctum')->user();
        if ($user && $user->role !== 'user') {
            $user = null; // Ignore admin/teacher sessions for registration
        }

        $gradeNum = null;
        if ($request->current_grade) {
            if (preg_match('/தரம்\s*(\d+)/', $request->current_grade, $m)) {
                $gradeNum = (int) $m[1];
            } elseif (preg_match('/Grade\s*(\d+)/i', $request->current_grade, $m)) {
                $gradeNum = (int) $m[1];
            }
        }

        $rules = [
            'username' => [
                $user ? 'nullable' : 'required',
                'string',
                'max:255',
                Rule::unique('users', 'name')->ignore($user?->id),
            ],
            'selected_subjects' => 'required|string',
        ];

        // Dynamic validation for fixed fields based on setting labels
        $fixedFields = [
            'full_name' => 'register_fullname_label',
            'phone_number' => 'register_phone_label',
            'date_of_birth' => 'register_dob_label',
            'gender' => 'register_gender_label',
            'school_name' => 'register_school_label',
            'medium' => 'register_medium_label',
            'online_experience' => 'register_experience_label',
            'device_used' => 'register_device_label',
            'current_grade' => 'register_grade_label',
        ];

        foreach ($fixedFields as $field => $settingKey) {
            $label = \App\Models\SiteSetting::get($settingKey);
            if (!empty($label)) {
                if ($field === 'phone_number') {
                    $rules[$field] = 'required|digits_between:10,15|unique:users,phone_number,' . ($user ? $user->id : 'NULL');
                } elseif ($field === 'date_of_birth') {
                    $rules[$field] = 'required|date';
                } elseif ($field === 'gender') {
                    $rules[$field] = 'required|in:male,female';
                } elseif ($field === 'medium') {
                    $rules[$field] = 'required|in:english,tamil';
                } elseif ($field === 'online_experience') {
                    $rules[$field] = 'required|boolean';
                } else {
                    $rules[$field] = 'required|string|max:255';
                }
            } else {
                $rules[$field] = 'nullable';
            }
        }

        // Stream is special
        $streamLabel = \App\Models\SiteSetting::get('register_stream_label');
        if (!empty($streamLabel)) {
            $rules['stream'] = 'nullable|string|max:50';
        }

        $request->validate($rules);

        // Identify custom fields (everything else in request except fixed keys and internal ones)
        $internalKeys = [
            'username', 'full_name', 'phone_number', 'date_of_birth', 
            'gender', 'school_name', 'medium', 'online_experience', 
            'device_used', 'current_grade', 'stream', 'selected_subjects', 
            '_token'
        ];
        
        $customFieldsData = array_diff_key($request->all(), array_flip($internalKeys));

        $userData = [
            'full_name' => $request->full_name,
            'phone_number' => $request->phone_number,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'school_name' => $request->school_name,
            'medium' => $request->medium,
            'online_experience' => $request->has('online_experience') ? $request->boolean('online_experience') : null,
            'device_used' => is_array($request->device_used)
                ? json_encode($request->device_used)
                : $request->device_used,
            'current_grade' => $request->current_grade,
            'stream' => $request->stream ?? null,
            'selected_subjects' => $request->selected_subjects,
            'custom_fields' => !empty($customFieldsData) ? $customFieldsData : null,
            'registration_status' => 'pending_payment',
            'institute_id' => $request->header('X-Institute-Id') ?: 1,
        ];

        \Log::info('RegistrationController@step1 - Start', ['request' => $request->except(['_token', 'password'])]);

        if ($user) {
            // Update existing user
            if ($request->username) {
                $userData['name'] = $request->username;
            }
            $user->update($userData);
            \Log::info('RegistrationController@step1 - Updated existing user', ['id' => $user->id]);
        } else {
            // Create new user
            $userData['name'] = $request->username;
            // If username looks like email, use it directly
            if (filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
                $userData['email'] = $request->username;
            } else {
                $userData['email'] = $request->username . '@student.local';
            }
            $userData['password'] = Hash::make('student123');
            $userData['role'] = 'user';
            $userData['admin_confirmed_at'] = null;
            
            \Log::info('RegistrationController@step1 - Creating new student', ['data' => array_diff_key($userData, ['password' => 1])]);
            
            $user = User::create($userData);

            // Notify All Admins of new registration
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                \Log::info('RegistrationController@step1 - Notifying admin: ' . $admin->id);
                try {
                    $admin->notify(new AdminNotification(
                        "New Student Registered: " . ($user->full_name ?? $user->name),
                        'info',
                        route('admin.students.show', $user->id),
                        'admission_new'
                    ));
                } catch (\Exception $e) {
                    \Log::error('RegistrationController@step1 - Notification failed for admin ' . $admin->id . ': ' . $e->getMessage());
                }
            }
            if ($admins->isEmpty()) {
                \Log::error('RegistrationController@step1 - Admin users not found for notification');
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Send Welcome WhatsApp via Template
        if ($user->phone_number) {
            $this->whatsApp->sendTemplate(
                $user->phone_number,
                'tit_welcome',
                'en',
                [$user->full_name ?? $user->name, $user->name]
            );
        }

        return response()->json([
            'message' => 'Registration step 1 complete. Proceed to payment.',
            'user' => $this->formatUserResponse($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Step 2: Payment choice. Offline = just success. Online = create payment order (Razorpay).
     */
    public function step2(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:offline,online',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $user = $request->user();
        if (!$user) {
            \Log::error('RegistrationController@step2 - No authenticated user found');
            return response()->json(['message' => 'Not authenticated.'], 401);
        }

        \Log::info('RegistrationController@step2 - Checking user:', [
            'id' => $user->id,
            'role' => $user->role,
            'full_name' => $user->full_name,
            'has_full_name' => !empty($user->full_name)
        ]);
        
        if ($user->role !== 'user' || empty($user->full_name)) {
            \Log::warning('RegistrationController@step2 - Invalid user check failed', [
                'id' => $user->id,
                'role' => $user->role,
                'full_name' => $user->full_name
            ]);
            return response()->json(['message' => 'Invalid user. Please complete Step 1 again.'], 403);
        }

        if ($request->payment_method === 'offline') {
            // Mark registration as completed — email is now locked
            $user->update(['registration_status' => 'payment_completed']);

            return response()->json([
                'message' => 'Registration submitted. Please complete payment offline. Admin will confirm and you will receive a WhatsApp message.',
                'registration_status' => $user->registration_status,
                'user' => $this->formatUserResponse($user),
            ]);
        }

        // Online: create Razorpay order if configured, else create a pending payment record and return order_id placeholder
        $amount = 0;
        if ($user->selected_subjects) {
            $category = $this->getSubjectCategoryForUser($user);
            $selectedSubjects = array_filter(array_map('trim', explode(',', $user->selected_subjects)));
            
            $subjectData = \App\Models\Subject::when($category, function($query) use ($category) {
                return $query->where('category', $category);
            })->whereIn('name', $selectedSubjects)->get(['name', 'price']);
            
            $amount = (float) $subjectData->sum('price');
        }

        // Fallback to monthly amount if no subjects or sum is 0
        if ($amount <= 0) {
            $amount = $request->amount ? (float) $request->amount : (float) config('payment.monthly_amount', 500);
        }

        $yearMonth = now()->format('Y-m');

        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => 'pending',
            'year_month' => $yearMonth,
            'gateway_ref' => 'order_' . uniqid(),
        ]);

        // Mark registration as completed — email is now locked
        $user->update(['registration_status' => 'payment_completed']);

        $orderId = $payment->gateway_ref;

        // If Razorpay is configured, create real order (we'll add Razorpay service later)
        $razorpayOrderId = null;
        if (config('payment.razorpay_key') && config('payment.razorpay_secret')) {
            try {
                $razorpayOrderId = $this->createRazorpayOrder($payment);
                if ($razorpayOrderId) {
                    $payment->update(['gateway_ref' => $razorpayOrderId]);
                    $orderId = $razorpayOrderId;
                }
            } catch (\Throwable $e) {
                \Log::warning('Razorpay order creation failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Payment order created.',
            'order_id' => $orderId,
            'amount' => $amount * 100, // paise for Razorpay
            'currency' => 'INR',
            'key' => config('payment.razorpay_key'),
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Called by frontend after successful gateway payment. Verify and mark payment paid.
     */
    public function paymentSuccess(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'payment_id' => 'nullable|string', // Razorpay payment_id
        ]);

        $user = $request->user();
        $payment = Payment::where('user_id', $user->id)
            ->where('gateway_ref', $request->order_id)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found or already processed.'], 404);
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $user->update(['registration_status' => 'paid_pending_confirm']);

        // Notify Admin of successful payment
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new AdminNotification(
                "Registration Payment Received: " . ($user->full_name ?? $user->name) . " - LKR " . number_format($payment->amount, 2),
                'success',
                route('admin.students.edit', $user->id),
                'admission_payments'
            ));
        }

        // Send Payment Success WhatsApp via Template
        if ($user->phone_number) {
            $this->whatsApp->sendTemplate(
                $user->phone_number,
                'tit_payment_success',
                'en',
                [$user->full_name ?? $user->name]
            );
        }

        return response()->json([
            'message' => 'Payment successful. Admin will confirm and you will receive a WhatsApp message.',
            'registration_status' => 'paid_pending_confirm',
        ]);
    }

    private function createRazorpayOrder(Payment $payment): ?string
    {
        $key = config('payment.razorpay_secret');
        if (!$key || !class_exists(\Razorpay\Api\Api::class)) {
            return null;
        }
        $amountPaise = (int) round($payment->amount * 100);
        $client = new \Razorpay\Api\Api(config('payment.razorpay_key'), $key);
        $order = $client->order->create([
            'amount' => $amountPaise,
            'currency' => 'INR',
            'receipt' => 'pay_' . $payment->id,
        ]);
        return $order['id'] ?? null;
    }

    private function formatUserResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'full_name' => $user->full_name,
            'phone_number' => $user->phone_number,
            'date_of_birth' => $user->date_of_birth,
            'gender' => $user->gender,
            'school_name' => $user->school_name,
            'medium' => $user->medium,
            'online_experience' => $user->online_experience,
            'device_used' => $user->device_used,
            'current_grade' => $user->current_grade,
            'stream' => $user->stream,
            'selected_subjects' => $user->selected_subjects,
            'registration_status' => $user->registration_status,
        ];
    }
}
