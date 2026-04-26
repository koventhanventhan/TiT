<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payment;
use App\Notifications\AdminNotification;
use App\Services\WhatsAppService;
use App\Services\PayHereService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    protected WhatsAppService $whatsApp;
    protected PayHereService $payHere;

    public function __construct(WhatsAppService $whatsApp, PayHereService $payHere)
    {
        $this->whatsApp = $whatsApp;
        $this->payHere  = $payHere;
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

        $request->validate($rules, [
            'phone_number.unique' => 'This phone number is already registered / இந்த தொலைபேசி எண் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது.',
            'username.unique' => 'This username/email is already registered / இந்த மின்னஞ்சல் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது.',
        ]);

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

            // Send Payment Instruction reminder immediately
            $this->whatsApp->sendTemplate(
                $user->phone_number,
                'tit_payment_reminder',
                'en',
                [$user->full_name ?? $user->name, now()->format('F Y')]
            );

            // Notify Admin via WhatsApp
            $adminPhone = env('ADMIN_WHATSAPP_NUMBER');
            if ($adminPhone) {
                $this->whatsApp->sendTemplate(
                    $adminPhone,
                    'tit_welcome',
                    'en',
                    [
                        "Admin Notification",
                        "New Student: " . ($user->full_name ?? $user->name)
                    ]
                );
            }
        }

        // Auto-fix status if they have a paid record for this month
        $paidPayment = Payment::where('user_id', $user->id)
            ->where('status', 'paid')
            ->first();
        if ($paidPayment && $user->registration_status !== 'approved') {
            $user->update(['registration_status' => 'approved']);
            Log::info('Auto-fixed student status based on existing payment', ['user_id' => $user->id]);
        }

        return response()->json([
            'message' => 'Registration step 1 complete. Proceed to payment.',
            'user' => $this->formatUserResponse($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Step 2: Payment choice. Offline = just success. Online = create PayHere checkout.
     */
    public function step2(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:offline,online',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $user = $request->user();
        if (!$user) {
            Log::error('RegistrationController@step2 - No authenticated user found');
            return response()->json(['message' => 'Not authenticated.'], 401);
        }

        Log::info('RegistrationController@step2 - Checking user:', [
            'id' => $user->id,
            'role' => $user->role,
            'full_name' => $user->full_name,
            'has_full_name' => !empty($user->full_name)
        ]);
        
        if ($user->role !== 'user' || empty($user->full_name)) {
            Log::warning('RegistrationController@step2 - Invalid user check failed', [
                'id' => $user->id,
                'role' => $user->role,
                'full_name' => $user->full_name
            ]);
            return response()->json(['message' => 'Invalid user. Please complete Step 1 again.'], 403);
        }

        if ($request->payment_method === 'offline') {
            // Mark registration as completed — email is now locked
            $user->update(['registration_status' => 'payment_completed']);

            // Notify user about offline payment submission
            if ($user->phone_number) {
                $this->whatsApp->sendTemplate(
                    $user->phone_number,
                    'tit_payment_reminder',
                    'en',
                    [$user->full_name ?? $user->name, now()->format('F Y')]
                );
            }

            return response()->json([
                'message' => 'Registration submitted. Please complete payment offline. Admin will confirm and you will receive a WhatsApp message.',
                'registration_status' => $user->registration_status,
                'user' => $this->formatUserResponse($user),
            ]);
        }

        // Online: Create PayHere payment
        $amount = $this->calculateUserAmount($user, $request->amount);
        $yearMonth = now()->format('Y-m');

        $payment = Payment::create([
            'user_id'        => $user->id,
            'amount'         => $amount,
            'status'         => 'pending',
            'payment_method' => 'online',
            'year_month'     => $yearMonth,
            'gateway_ref'    => 'PH_' . strtoupper(uniqid()),
            'institute_id'   => $user->institute_id ?? 1,
        ]);

        // Mark registration status
        $user->update(['registration_status' => 'payment_completed']);

        // Build PayHere checkout params for frontend JS SDK
        if ($this->payHere->isConfigured()) {
            $params = $this->payHere->buildCheckoutParams($payment, $user);
            $checkoutUrl = $this->payHere->getCheckoutUrl();

            Log::info('PayHere checkout params generated', [
                'order_id' => $payment->gateway_ref,
                'amount'   => $amount,
                'user_id'  => $user->id,
            ]);

            return response()->json([
                'message'     => 'Payment order created.',
                'payhere_url' => $checkoutUrl,
                'params'      => $params,
                'user'        => $this->formatUserResponse($user),
            ]);
        }

        // PayHere not configured — return error
        Log::warning('PayHere credentials not configured. Cannot process online payment.');
        return response()->json([
            'message' => 'Online payment is not configured yet. Please contact admin or choose offline payment.',
        ], 503);
    }

    /**
     * PayHere server notification callback (public route, called by PayHere servers).
     * This is the most reliable way to confirm payment — it's server-to-server.
     */
    public function payhereNotify(Request $request)
    {
        Log::info('PayHere Notification Received', $request->all());

        $data = $request->all();

        // Verify the notification hash
        if (!$this->payHere->verifyNotification($data)) {
            Log::error('PayHere notification hash verification FAILED', $data);
            return response('Hash verification failed', 403);
        }

        $orderId    = $data['order_id'] ?? '';
        $statusCode = $data['status_code'] ?? '';

        // Find the payment record
        $payment = Payment::where('gateway_ref', $orderId)->first();

        if (!$payment) {
            Log::error('PayHere notification: Payment not found', ['order_id' => $orderId]);
            return response('Payment not found', 404);
        }

        // PayHere status codes:
        // 2 = success
        // 0 = pending
        // -1 = canceled
        // -2 = failed
        // -3 = chargeback
        if ($statusCode == 2) {
            // Payment successful
            $payment->update([
                'status'      => 'paid',
                'paid_at'     => now(),
                'transaction_id' => $data['payment_id'] ?? null,
            ]);

            $user = $payment->user;
            if ($user) {
                // Update registration status if it was a registration payment
                if (in_array($user->registration_status, ['pending_payment', 'payment_completed'])) {
                    $user->update([
                        'registration_status' => 'approved',
                        'admin_confirmed_at'  => now(),
                    ]);
                }

                // Notify Admin of successful payment
                $admin = User::where('role', 'admin')
                    ->where('institute_id', $user->institute_id)
                    ->first();
                if ($admin) {
                    try {
                        $admin->notify(new AdminNotification(
                            "Payment Received: " . ($user->full_name ?? $user->name) . " - LKR " . number_format($payment->amount, 2),
                            'success',
                            route('admin.students.edit', $user->id),
                            'admission_payments'
                        ));
                    } catch (\Throwable $e) {
                        Log::error('PayHere notify: Admin notification failed: ' . $e->getMessage());
                    }
                }

                // Send Payment Success WhatsApp to Student
                if ($user->phone_number) {
                    $this->whatsApp->sendTemplate(
                        $user->phone_number,
                        'tit_payment_success',
                        'en',
                        [$user->full_name ?? $user->name]
                    );
                }

                // Notify Admin via WhatsApp
                $adminPhone = env('ADMIN_WHATSAPP_NUMBER');
                if ($adminPhone) {
                    $this->whatsApp->sendTemplate(
                        $adminPhone,
                        'tit_payment_success',
                        'en',
                        ["PAYMENT ALERT: " . ($user->full_name ?? $user->name) . " paid LKR " . number_format($payment->amount, 2)]
                    );
                }

                Log::info('PayHere payment SUCCESS', [
                    'order_id'   => $orderId,
                    'user_id'    => $user->id,
                    'amount'     => $payment->amount,
                    'payment_id' => $data['payment_id'] ?? 'N/A',
                ]);
            }
        } elseif (in_array($statusCode, ['-1', '-2', '-3'])) {
            // Payment failed/canceled/chargeback
            $payment->update(['status' => 'failed']);
            Log::warning('PayHere payment FAILED/CANCELED', [
                'order_id'    => $orderId,
                'status_code' => $statusCode,
            ]);
        } else {
            // Pending (status_code = 0)
            Log::info('PayHere payment PENDING', [
                'order_id'    => $orderId,
                'status_code' => $statusCode,
            ]);
        }

        return response('OK', 200);
    }

    /**
     * Called by frontend after successful gateway payment (fallback confirmation).
     */
    public function paymentSuccess(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'payment_id' => 'nullable|string',
        ]);

        // Find the payment by order_id (gateway_ref)
        $payment = Payment::where('gateway_ref', $request->order_id)->first();

        if (!$payment) {
            Log::error('paymentSuccess: Payment record not found', ['order_id' => $request->order_id]);
            return response()->json(['message' => 'Payment record not found.'], 404);
        }

        // If already paid via server notification, just return success
        if ($payment->status === 'paid') {
            return response()->json([
                'message' => 'Payment already confirmed.',
                'registration_status' => $payment->user->registration_status ?? 'approved',
            ]);
        }

        // Mark as paid
        $payment->update([
            'status'  => 'paid',
            'paid_at' => now(),
            'transaction_id' => $request->payment_id,
        ]);

        $user = $payment->user;
        if ($user) {
            $user->update([
                'registration_status' => 'approved',
                'admin_confirmed_at'  => now(),
            ]);
            Log::info('paymentSuccess: User automatically approved', ['user_id' => $user->id]);

            // Notify Admin
            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                try {
                    $admin->notify(new AdminNotification(
                        "Registration Payment Received: " . ($user->full_name ?? $user->name) . " - LKR " . number_format($payment->amount, 2),
                        'success',
                        route('admin.students.edit', $user->id),
                        'admission_payments'
                    ));
                } catch (\Throwable $e) {
                    Log::error('Payment success notification failed: ' . $e->getMessage());
                }
            }

            // Send Payment Success WhatsApp to Student
            if ($user->phone_number) {
                $this->whatsApp->sendTemplate(
                    $user->phone_number,
                    'tit_payment_success',
                    'en',
                    [$user->full_name ?? $user->name]
                );
            }

            // Notify Admin via WhatsApp
            $adminPhone = env('ADMIN_WHATSAPP_NUMBER');
            if ($adminPhone) {
                $this->whatsApp->sendTemplate(
                    $adminPhone,
                    'tit_payment_success',
                    'en',
                    ["PAYMENT ALERT: " . ($user->full_name ?? $user->name) . " paid LKR " . number_format($payment->amount, 2)]
                );
            }
        }

        return response()->json([
            'message' => 'Payment successful. Your account is now active.',
            'registration_status' => 'approved',
        ]);
    }

    /**
     * Check if the current student has paid for this month.
     */
    public function checkMonthlyPaymentStatus(Request $request)
    {
        $user = $request->user();
        $yearMonth = now()->format('Y-m');

        $payment = Payment::where('user_id', $user->id)
            ->where('year_month', $yearMonth)
            ->orderBy('created_at', 'desc')
            ->first();

        $amount = $this->calculateUserAmount($user);

        return response()->json([
            'is_paid'    => $payment && $payment->status === 'paid',
            'amount'     => $amount,
            'year_month' => $yearMonth,
            'payment'    => $payment ? [
                'id'         => $payment->id,
                'status'     => $payment->status,
                'amount'     => $payment->amount,
                'paid_at'    => $payment->paid_at,
                'gateway_ref' => $payment->gateway_ref,
            ] : null,
        ]);
    }

    /**
     * Initialize a monthly payment and return PayHere checkout params.
     */
    public function initializeMonthlyPayment(Request $request)
    {
        $user = $request->user();
        $yearMonth = now()->format('Y-m');

        // Check if already paid
        $existingPaid = Payment::where('user_id', $user->id)
            ->where('year_month', $yearMonth)
            ->where('status', 'paid')
            ->exists();

        if ($existingPaid) {
            return response()->json([
                'message' => 'You have already paid for this month.',
            ], 400);
        }

        // Cancel any pending payments for this month
        Payment::where('user_id', $user->id)
            ->where('year_month', $yearMonth)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $amount = $this->calculateUserAmount($user);

        $payment = Payment::create([
            'user_id'        => $user->id,
            'amount'         => $amount,
            'status'         => 'pending',
            'payment_method' => 'online',
            'year_month'     => $yearMonth,
            'gateway_ref'    => 'PH_' . strtoupper(uniqid()),
            'institute_id'   => $user->institute_id ?? 1,
        ]);

        if (!$this->payHere->isConfigured()) {
            return response()->json([
                'message' => 'Online payment is not configured. Please contact admin.',
            ], 503);
        }

        $params = $this->payHere->buildCheckoutParams($payment, $user);
        $checkoutUrl = $this->payHere->getCheckoutUrl();

        Log::info('Monthly PayHere checkout initialized', [
            'user_id'    => $user->id,
            'order_id'   => $payment->gateway_ref,
            'amount'     => $amount,
            'year_month' => $yearMonth,
        ]);

        return response()->json([
            'message'     => 'Monthly payment initialized.',
            'payhere_url' => $checkoutUrl,
            'params'      => $params,
        ]);
    }

    /**
     * Calculate the amount the user needs to pay based on selected subjects.
     */
    private function calculateUserAmount(User $user, ?float $fallbackAmount = null): float
    {
        $amount = 0;

        if ($user->selected_subjects) {
            $category = $this->getSubjectCategoryForUser($user);

            // Parse selected subjects (could be JSON array or comma-separated)
            $subjectsRaw = $user->selected_subjects;
            if (is_string($subjectsRaw) && str_starts_with(trim($subjectsRaw), '[')) {
                $selectedSubjects = json_decode($subjectsRaw, true) ?? [];
            } else {
                $selectedSubjects = array_filter(array_map('trim', explode(',', (string) $subjectsRaw)));
            }

            if (!empty($selectedSubjects)) {
                $subjectData = \App\Models\Subject::when($category, function ($query) use ($category) {
                    return $query->where('category', $category);
                })->whereIn('name', $selectedSubjects)->get(['name', 'price']);

                $amount = (float) $subjectData->sum('price');
            }
        }

        // Fallback
        if ($amount <= 0) {
            $amount = $fallbackAmount ?? 500.0;
        }

        return $amount;
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
    public function testAdminWhatsApp()
    {
        $adminPhone = env('ADMIN_WHATSAPP_NUMBER');
        if (!$adminPhone) {
            return response()->json(['error' => 'ADMIN_WHATSAPP_NUMBER is not set in .env'], 400);
        }

        try {
            $response = $this->whatsApp->sendTemplate(
                $adminPhone,
                'tit_welcome',
                'en',
                ["ADMIN TEST", "DEBUG123"]
            );

            return response()->json([
                'message' => 'Test attempt completed',
                'admin_number' => $adminPhone,
                'meta_response' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception occurred',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
