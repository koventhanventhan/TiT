<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payment;
use App\Notifications\AdminNotification;
use App\Services\NotificationService;
use App\Services\PayHereService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    use \App\Traits\ValidatesEmail;
    use \App\Traits\HandlesDuplicateAccounts;

    protected NotificationService $notifier;
    protected PayHereService $payHere;

    public function __construct(NotificationService $notifier, PayHereService $payHere)
    {
        $this->notifier = $notifier;
        $this->payHere  = $payHere;
    }

    /**
     * Helper to get subject category based on user's grade and stream
     */
    private function getSubjectCategoryForUser(User $user)
    {
        return $user->getSubjectCategory();
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
        $monthlyFee = $total;
        $admissionFee = 0;
        
        $isFirstPayment = !$user->payments()->where('status', 'paid')->exists();
        
        if ($isFirstPayment && $user->current_grade) {
            $gradeNum = 0;
            if (preg_match('/(\d+)/', $user->current_grade, $m)) {
                $gradeNum = (int)$m[1];
            }
            if ($gradeNum > 0) {
                $configStr = \App\Models\SiteSetting::get('admission_fees_config', '{}');
                $config = json_decode($configStr, true) ?? [];
                
                if (isset($config[$gradeNum]) && $config[$gradeNum]['enabled']) {
                    $admissionFee = (float)$config[$gradeNum]['amount'];
                    $total += $admissionFee;
                }
            }
        }

        return response()->json([
            'total' => $total > 0 ? (float)$total : 500.0,
            'monthly_total' => (float)$monthlyFee,
            'admission_fee' => $admissionFee,
            'is_first_payment' => $isFirstPayment,
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

        // --- PRE-VALIDATION FIXES for Same Name & Phone Number normalization ---
        if ($request->has('phone_number')) {
            $phone = preg_replace('/\D/', '', $request->phone_number);
            if (strlen($phone) == 10 && str_starts_with($phone, '0')) {
                $phone = '94' . substr($phone, 1);
            } elseif (strlen($phone) == 9 && str_starts_with($phone, '7')) {
                $phone = '94' . $phone;
            }
            $request->merge(['phone_number' => $phone]);
        }

        if ($request->has('username') && !filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
            // Auto-increment username if duplicate (to allow same full name)
            $username = strtolower(preg_replace('/\s+/', '', (string)$request->username));
            $originalUsername = $username;
            $counter = 1;
            while (\App\Models\User::where('name', $username)->where('id', '!=', $user?->id ?? 0)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }
            $request->merge(['username' => $username]);
        }
        // -----------------------------------------------------------------------

        $gradeNum = null;
        if ($request->current_grade) {
            if (preg_match('/தரம்\s*(\d+)/', $request->current_grade, $m)) {
                $gradeNum = (int) $m[1];
            } elseif (preg_match('/Grade\s*(\d+)/i', $request->current_grade, $m)) {
                $gradeNum = (int) $m[1];
            }
        }

        // --- CHECK FOR PARENT MERGE ---
        $instituteId = $request->header('X-Institute-Id') ?: 1;
        $verifiedParentId = null;

        if ($request->has('merge_token')) {
            $verifiedParentId = \Illuminate\Support\Facades\Cache::pull('merge_verified_' . $request->merge_token);
        }

        if (!$verifiedParentId) {
            $mergeResponse = $this->checkAndHandleDuplicateParent(
                $request->phone_number, 
                $request->username, 
                $instituteId, 
                $user?->id ?? 0
            );
            
            if ($mergeResponse) {
                return $mergeResponse;
            }
        }
        // ------------------------------

        $usernameRules = [
            $user ? 'nullable' : 'required',
            'string',
            'max:255',
            Rule::unique('users', 'name')->ignore($user?->id),
        ];

        if ($request->has('username') && filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
            $usernameRules[] = Rule::unique('users', 'email')->ignore($user?->id);
        }

        $rules = [
            'username' => $usernameRules,
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
                    $rules[$field] = 'required|digits_between:9,15|unique:users,phone_number,' . ($user ? $user->id : 'NULL');
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

        if ($verifiedParentId) {
            // Remove the unique rules for siblings since they reuse the parent contact
            foreach (['username', 'phone_number'] as $field) {
                if (isset($rules[$field])) {
                    if (is_array($rules[$field])) {
                        $rules[$field] = array_filter($rules[$field], function($rule) {
                            return !is_string($rule) || !str_contains($rule, 'unique:users,email');
                        });
                        $rules[$field] = array_filter($rules[$field], function($rule) {
                            return !($rule instanceof \Illuminate\Validation\Rules\Unique);
                        });
                    } else {
                        $rules[$field] = preg_replace('/\|unique:[^\|]+/', '', $rules[$field]);
                    }
                }
            }
        }

        $request->validate($rules, [
            'phone_number.unique' => 'This phone number is already registered / இந்த தொலைபேசி எண் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது.',
            'username.unique' => 'This username/email is already registered / இந்த மின்னஞ்சல் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது.',
        ]);

        // Email domain validation (DNS/MX)
        if ($request->has('username') && filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
            if (!$this->isValidEmailForSending($request->username)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'username' => ['This email address appears to be invalid or cannot receive emails. Please provide a real, working email address. / இந்த மின்னஞ்சல் முகவரி தவறானது அல்லது வேலை செய்யவில்லை. சரியான மின்னஞ்சலை உள்ளிடவும்.']
                    ]
                ], 422);
            }
        }

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
            
            if ($verifiedParentId) {
                $parent = User::find($verifiedParentId);
                $userData['email'] = 'sibling_' . $verifiedParentId . '_' . time() . '@child.local';
                $userData['phone_number'] = null; // Siblings don't have their own phone number
                $userData['parent_id'] = $verifiedParentId;
                $userData['password'] = $parent->password; // Inherit parent's password
            } else {
                // If username looks like email, use it directly
                if (filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
                    $userData['email'] = $request->username;
                } else {
                    $userData['email'] = $request->username . '@student.local';
                }
                $userData['password'] = Hash::make('student123');
            }
            
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

        // Send Welcome notification (WhatsApp with email fallback)
        $this->notifier->notifyUser(
            $user, 'welcome', 'tit_welcome',
            [$user->full_name ?? $user->name, $user->name],
            ['student_name' => $user->full_name ?? $user->name, 'username' => $user->name]
        );

        // Send Payment Instruction reminder immediately
        $this->notifier->notifyUser(
            $user, 'payment_reminder', 'tit_payment_reminder',
            [$user->full_name ?? $user->name, now()->format('F Y')],
            ['student_name' => $user->full_name ?? $user->name, 'month' => now()->format('F Y')]
        );

        // Notify Admin
        $this->notifier->notifyAdmin(
            'admin_alert', 'tit_welcome',
            ["Admin Notification", "New Student: " . ($user->full_name ?? $user->name)],
            ['alert_title' => 'New Student Registration', 'alert_message' => 'New Student: ' . ($user->full_name ?? $user->name)]
        );

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
            $this->notifier->notifyUser(
                $user, 'payment_reminder', 'tit_payment_reminder',
                [$user->full_name ?? $user->name, now()->format('F Y')],
                ['student_name' => $user->full_name ?? $user->name, 'month' => now()->format('F Y')]
            );

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

                // Send Payment Success notification to Student
                $this->notifier->notifyUser(
                    $user, 'payment_success', 'tit_payment_success',
                    [$user->full_name ?? $user->name],
                    ['student_name' => $user->full_name ?? $user->name]
                );

                // Notify Admin
                $this->notifier->notifyAdmin(
                    'admin_alert', 'tit_payment_success',
                    ["PAYMENT ALERT: " . ($user->full_name ?? $user->name) . " paid LKR " . number_format($payment->amount, 2)],
                    ['alert_title' => 'Payment Received', 'alert_message' => ($user->full_name ?? $user->name) . ' paid LKR ' . number_format($payment->amount, 2)]
                );

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

            // Send Payment Success notification to Student
            $this->notifier->notifyUser(
                $user, 'payment_success', 'tit_payment_success',
                [$user->full_name ?? $user->name],
                ['student_name' => $user->full_name ?? $user->name]
            );

            // Notify Admin
            $this->notifier->notifyAdmin(
                'admin_alert', 'tit_payment_success',
                ["PAYMENT ALERT: " . ($user->full_name ?? $user->name) . " paid LKR " . number_format($payment->amount, 2)],
                ['alert_title' => 'Payment Received', 'alert_message' => ($user->full_name ?? $user->name) . ' paid LKR ' . number_format($payment->amount, 2)]
            );
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
        return $user->calculateMonthlyFee($fallbackAmount);
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
        try {
            $response = $this->notifier->notifyAdmin(
                'admin_alert', 'tit_welcome',
                ["ADMIN TEST", "DEBUG123"],
                ['alert_title' => 'Test Notification', 'alert_message' => 'This is a test notification from TiT Education system.']
            );

            return response()->json([
                'message' => 'Test attempt completed',
                'channel' => env('NOTIFICATION_CHANNEL', 'auto'),
                'result' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception occurred',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send OTP to parent to authorize merging a new sibling account.
     */
    public function sendMergeOtp(Request $request)
    {
        $request->validate([
            'merge_token' => 'required|string',
        ]);

        $ipLimitKey = 'merge_otp_ip_' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($ipLimitKey, 5)) {
            return response()->json([
                'message' => 'Too many OTP requests from this IP. Please try again later.'
            ], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($ipLimitKey, 3600); // 1 hour IP limit

        $parentId = \Illuminate\Support\Facades\Cache::get('merge_token_' . $request->merge_token);
        if (!$parentId) {
            return response()->json(['message' => 'Invalid or expired session. Please try registering again.'], 400);
        }

        // Rate limiting: max 3 requests per 10 minutes
        $rateLimitKey = 'merge_otp_requests_' . $parentId;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => 'Too many OTP requests. Please try again in ' . ceil($seconds / 60) . ' minutes.'
            ], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rateLimitKey, 600); // 10 minutes

        $parent = User::find($parentId);
        if (!$parent || $parent->parent_id !== null || $parent->role !== 'user') {
            return response()->json(['message' => 'Invalid parent account.'], 400);
        }

        $otp = (string) rand(100000, 999999);
        
        // Cache OTP for 10 minutes
        \Illuminate\Support\Facades\Cache::put('merge_otp_' . $parentId, $otp, now()->addMinutes(10));
        // Reset the attempt counter for verify
        \Illuminate\Support\Facades\Cache::forget('merge_otp_attempts_' . $parentId);

        $sentWhatsApp = false;
        if ($parent->phone_number) {
            $whatsappService = app(\App\Services\WhatsAppService::class);
            $sentWhatsApp = $whatsappService->sendTemplate($parent->phone_number, 'tit_otp_verification', 'en', [$otp]);
        }

        if (!$sentWhatsApp && $parent->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($parent->email)->send(new \App\Mail\MergeAccountOtpMail($parent, $otp));
            } catch (\Exception $e) {
                Log::error('MergeAccountOtpMail failed: ' . $e->getMessage());
                // Don't fail the request if email fallback fails, just log it.
            }
        }

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    /**
     * Verify OTP and merge the new sibling account into the parent account.
     */
    public function verifyMergeOtp(Request $request)
    {
        $request->validate([
            'merge_token' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $parentId = \Illuminate\Support\Facades\Cache::get('merge_token_' . $request->merge_token);
        if (!$parentId) {
            return response()->json(['message' => 'Invalid or expired session. Please try registering again.'], 400);
        }

        // Rate limiting verify endpoint: max 10 requests per 10 minutes
        $rateLimitKey = 'merge_verify_requests_' . $parentId;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => 'Too many verification attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.'
            ], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rateLimitKey, 600);

        // Attempt limiting (5 incorrect OTPs invalidates the current OTP)
        $attemptsKey = 'merge_otp_attempts_' . $parentId;
        $attempts = \Illuminate\Support\Facades\Cache::get($attemptsKey, 0);

        if ($attempts >= 5) {
            \Illuminate\Support\Facades\Cache::forget('merge_otp_' . $parentId);
            return response()->json([
                'message' => 'Too many incorrect attempts. The OTP has been invalidated. Please request a new one.'
            ], 400);
        }

        $cachedOtp = \Illuminate\Support\Facades\Cache::get('merge_otp_' . $parentId);
        
        if (!$cachedOtp) {
            return response()->json(['message' => 'OTP expired or not found. Please request a new one.'], 400);
        }

        if ($cachedOtp !== $request->otp) {
            \Illuminate\Support\Facades\Cache::increment($attemptsKey);
            if (\Illuminate\Support\Facades\Cache::get($attemptsKey) >= 5) {
                \Illuminate\Support\Facades\Cache::forget('merge_otp_' . $parentId);
                return response()->json([
                    'message' => 'Too many incorrect attempts. The OTP has been invalidated. Please request a new one.'
                ], 400);
            }
            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        // OTP Valid. Clear it.
        \Illuminate\Support\Facades\Cache::forget('merge_otp_' . $parentId);
        \Illuminate\Support\Facades\Cache::forget($attemptsKey);

        $parent = User::find($parentId);

        // Extract student data
        // If full_name is missing, we are in Phase 1 (early AuthController flow)
        if (!$request->has('full_name') || empty($request->full_name)) {
            // Phase 1: Mark as verified and log the parent in. Defer sibling creation to step 1.
            \Illuminate\Support\Facades\Cache::put('merge_verified_' . $request->merge_token, $parent->id, now()->addMinutes(30));
            
            $token = $parent->createToken('auth_token')->plainTextToken;
            $profiles = collect([$parent])->merge($parent->children)->map(function ($profile) {
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
                'message' => 'Account verified. Please complete student details.',
                'user' => $profiles->first(),
                'profiles' => $profiles,
                'token' => $token,
                'is_deferred' => true
            ]);
        }

        // Phase 2: Create sibling immediately (existing RegistrationController step 1 flow)
        $userData = [
            'first_name' => $request->first_name ?? explode(' ', $request->full_name)[0],
            'last_name' => $request->last_name ?? null,
            'full_name' => $request->full_name,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'school_name' => $request->school_name,
            'medium' => $request->medium,
            'current_grade' => $request->current_grade,
            'stream' => $request->stream ?? null,
            'selected_subjects' => is_array($request->selected_subjects) ? json_encode($request->selected_subjects) : $request->selected_subjects,
        ];

        // Ensure array device_used is handled
        if ($request->has('device_used')) {
            $userData['device_used'] = is_array($request->device_used) ? json_encode($request->device_used) : $request->device_used;
        }
        if ($request->has('online_experience')) {
            $userData['online_experience'] = $request->online_experience === 'yes' ? 1 : 0;
        }
        
        // Handle custom fields
        $internalKeys = [
            'parent_id', 'otp', 'username', 'full_name', 'phone_number', 'email', 'date_of_birth', 
            'gender', 'school_name', 'medium', 'online_experience', 
            'device_used', 'current_grade', 'stream', 'selected_subjects', 
            '_token'
        ];
        $customFieldsData = array_diff_key($request->all(), array_flip($internalKeys));
        if (!empty($customFieldsData)) {
            $userData['custom_fields'] = $customFieldsData;
        }

        $userData['parent_id'] = $parent->id;
        $userData['name'] = strtolower(str_replace(' ', '_', $request->full_name)) . rand(1000, 9999);
        $userData['email'] = 'sibling_' . $parent->id . '_' . time() . '@child.local';
        $userData['password'] = $parent->password; // Inherit password
        $userData['role'] = 'user';
        $userData['registration_status'] = 'pending_payment'; // Sibling needs own payment
        $userData['admin_confirmed_at'] = null; // Sibling needs admin confirmation if applicable, or inherit
        $userData['institute_id'] = $parent->institute_id;
        
        $sibling = User::create($userData);

        Log::info('New sibling account created via OTP merge', ['parent_id' => $parent->id, 'sibling_id' => $sibling->id]);

        // Login the parent
        $token = $parent->createToken('auth_token')->plainTextToken;

        $profiles = collect([$parent])->merge($parent->children)->map(function ($profile) {
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
            'message' => 'Account merged successfully. You are now logged in.',
            'user' => $profiles->first(),
            'profiles' => $profiles,
            'token' => $token,
        ]);
    }
}
