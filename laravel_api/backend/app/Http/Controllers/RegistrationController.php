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
        \Illuminate\Support\Facades\Log::info("Payment details request for User ID: {$user->id}, Email: {$user->email}, Raw Subjects: " . (is_array($subjects) ? json_encode($subjects) : $subjects));

        if (empty($subjects)) {
            return response()->json([
                'total' => 500,
                'subjects' => [],
                'debug' => 'Empty subjects string'
            ]);
        }

        $selectedSubjects = [];
        if (is_array($subjects)) {
            $selectedSubjects = $subjects;
        } elseif (is_string($subjects)) {
            $decoded = json_decode($subjects, true);
            if (is_array($decoded)) {
                $selectedSubjects = $decoded;
            } elseif (is_string($decoded)) {
                $decoded2 = json_decode($decoded, true);
                if (is_array($decoded2)) {
                    $selectedSubjects = $decoded2;
                } else {
                    $selectedSubjects = array_map('trim', explode(',', $decoded));
                }
            } else {
                $selectedSubjects = array_map('trim', explode(',', $subjects));
            }
        }

        $selectedSubjects = array_unique(array_filter($selectedSubjects));

        if (empty($selectedSubjects)) {
            \Illuminate\Support\Facades\Log::warning("No subjects parsed for User ID: {$user->id}");
            return response()->json([
                'total' => 500.0,
                'subjects' => [],
                'debug' => 'No subjects parsed from string/array'
            ]);
        }

        $category = $this->getSubjectCategoryForUser($user);

        $subjectData = \App\Models\Subject::when($category, function($query) use ($category) {
            if ($category === 'grade_12_to_13') {
                return $query->whereIn('category', ['arts_stream', 'commerce_stream', 'tech_stream', 'bio_maths_stream', 'grade_12_to_13']);
            }
            return $query->where('category', $category);
        })->whereIn('name', $selectedSubjects)->get(['name', 'price']);

        $total = $user->calculateMonthlyFee();
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
                    // Admission fee is already added inside calculateMonthlyFee if first payment.
                    // To prevent double adding, we just record what it is, but wait, calculateMonthlyFee returns the FULL amount including admission fee!
                    // Let's check calculateMonthlyFee again. It DOES add admissionFee if first payment.
                    // So we shouldn't add it again here. $total already has it.
                }
            }
        }

        return response()->json([
            'total' => $total > 0 ? (float)$total : 500.0,
            'monthly_total' => (float)($total - $admissionFee),
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

        // No parent merge logic needed here

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

        // Unique rules already set up in $rules

        $request->validate($rules, [
            'phone_number.unique' => 'This phone number is already registered / இந்த தொலைபேசி எண் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது.',
            'username.unique' => 'This username/email is already registered / இந்த மின்னஞ்சல் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது.',
        ]);

        // Email domain validation (DNS/MX) — DISABLED on live server
        // OTP verification (below) already confirms the email is real and owned by the user.
        // DNS checks cause false rejections on cPanel shared hosting.
        // if ($request->has('username') && filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
        //     if (!$this->isValidEmailForSending($request->username)) {
        //         return response()->json([...], 422);
        //     }
        // }

        if ($request->has('username') && filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
            
            // If creating a new user, check if email was verified via OTP
            if (!$user && !\Illuminate\Support\Facades\Cache::get('email_verified_' . $request->username)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'username' => ['Please verify your email address first. / முதலில் உங்கள் மின்னஞ்சல் முகவரியை சரிபார்க்கவும்.']
                    ]
                ], 422);
            }
        }

        $customFieldLabels = json_decode(\App\Models\SiteSetting::get('register_custom_fields', '[]'), true) ?: [];
        $allowedCustomKeys = array_map(function($label) {
            return preg_replace('/\s+/', '_', strtolower($label));
        }, $customFieldLabels);
        
        $customFieldsData = array_filter($request->only($allowedCustomKeys), function($value) {
            return $value !== null;
        });

        $instituteId = (app()->environment(['local', 'testing']) && $request->header('X-Institute-Id'))
            ? (int) $request->header('X-Institute-Id')
            : ((function_exists('current_institute_id') && current_institute_id()) ? current_institute_id() : ((int) $request->header('X-Institute-Id') ?: 1));

        if (!$user) {
            $institute = \App\Models\Institute::with('plan')->find($instituteId);
            if ($institute) {
                try {
                    \App\Services\PlanLimits::assertCanAddStudent($institute, 1);
                } catch (\App\Exceptions\PlanLimitReachedException $e) {
                    return response()->json([
                        'message' => $e->getMessage(),
                        'code' => 'plan_limit_reached',
                        'errors' => ['username' => [$e->getMessage()]],
                    ], 403);
                }
            }
        }

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
            'institute_id' => $instituteId,
        ];

        \Log::info('RegistrationController@step1 - Start', ['request' => \App\Support\Redact::array($request->except(['_token', 'password']))]);

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
            
            $targetInstitute = \App\Models\Institute::find($instituteId ?: 1);
            try {
                $user = \App\Services\PlanLimits::runWithStudentLock($targetInstitute, 1, function () use ($userData) {
                    return User::create($userData);
                });
            } catch (\App\Exceptions\PlanLimitReachedException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'code' => 'plan_limit_reached',
                    'errors' => ['username' => [$e->getMessage()]],
                ], 403);
            }

            if (filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
                \Illuminate\Support\Facades\Cache::forget('email_verified_' . $request->username);
            }

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

            // Notify user about offline payment submission asynchronously after response
            dispatch(function () use ($user) {
                try {
                    $notifier = app(\App\Services\NotificationService::class);
                    $notifier->notifyUser(
                        $user, 'payment_reminder', 'tit_payment_reminder',
                        [$user->full_name ?? $user->name, now()->format('F Y')],
                        ['student_name' => $user->full_name ?? $user->name, 'month' => now()->format('F Y')]
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Offline step2 notification failed: ' . $e->getMessage());
                }
            })->afterResponse();

            return response()->json([
                'message' => 'Registration submitted. Please complete payment offline. Admin will confirm and you will receive a WhatsApp message.',
                'registration_status' => $user->registration_status,
                'user' => $this->formatUserResponse($user),
            ]);
        }

        // Online: Verify PayHere is configured for this institute BEFORE creating checkout
        $instituteId = (int) ($user->institute_id ?: (\App\Support\Tenant::id() ?: 1));
        $payHere = PayHereService::forInstitute($instituteId);

        if (!$payHere->isConfigured()) {
            Log::warning('PayHere credentials not configured for institute. Online payment unavailable.', [
                'institute_id' => $instituteId,
                'user_id' => $user->id,
            ]);
            return response()->json([
                'code' => 'online_payments_unavailable',
                'error' => 'online_payments_unavailable',
                'message' => 'Online payments are not available for this institute. Please choose bank transfer or cash payment.',
            ], 422);
        }

        $amount = $this->calculateUserAmount($user, $request->amount);
        $yearMonth = now()->format('Y-m');

        // Cancel any pending payments for this month
        Payment::where('user_id', $user->id)
            ->where('year_month', $yearMonth)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $admFee = $user->getAdmissionFeeAmount();
        $meta = ['currency' => $payHere->getCurrency()];
        if ($admFee > 0) {
            $meta['admission_fee'] = $admFee;
        }

        $payment = Payment::create([
            'user_id'        => $user->id,
            'amount'         => $amount,
            'status'         => 'pending',
            'payment_method' => 'online',
            'year_month'     => $yearMonth,
            'gateway_ref'    => 'PH_' . strtoupper(uniqid()),
            'institute_id'   => $instituteId,
            'meta'           => $meta,
        ]);

        // Mark registration status
        $user->update(['registration_status' => 'payment_completed']);

        $params = $payHere->buildCheckoutParams($payment, $user);
        $checkoutUrl = $payHere->getCheckoutUrl();

        Log::info('PayHere checkout params generated', [
            'institute_id' => $instituteId,
            'order_id'     => $payment->gateway_ref,
            'amount'       => $amount,
            'user_id'      => $user->id,
        ]);

        return response()->json([
            'message'     => 'Payment order created.',
            'payhere_url' => $checkoutUrl,
            'params'      => $params,
            'user'        => $this->formatUserResponse($user),
        ]);
    }

    /**
     * PayHere server notification callback (public route, called by PayHere servers).
     * Resolves payment across all tenants FIRST, then verifies signature, merchant_id,
     * amount, and currency strictly inside the owning institute's tenant context.
     */
    public function payhereNotify(Request $request)
    {
        $data = $request->all();
        $redactedPayload = \App\Support\Redact::array($data);

        Log::info('PayHere Notification Received', $redactedPayload);

        $orderId    = trim((string) ($data['order_id'] ?? ''));
        $statusCode = $data['status_code'] ?? '';

        // (a) Find the Payment by gateway_ref with withoutGlobalScopes() FIRST (webhook may arrive on any host)
        $payment = $orderId !== ''
            ? Payment::withoutGlobalScopes()->where('gateway_ref', $orderId)->first()
            : null;

        if (!$payment) {
            Log::error('PayHere notification: Payment not found', ['order_id' => $orderId]);
            \App\Services\EventNotifier::webhookError(
                'PayHere Payment Not Found',
                "Webhook received for order_id '{$orderId}' but no matching payment record exists.",
                ['order_id' => $orderId, 'status_code' => $statusCode]
            );
            return response('Payment not found', 404);
        }

        $paymentInstituteId = (int) ($payment->institute_id ?: 1);

        // (b) Run verification and all side-effects inside Tenant::run($payment->institute_id, ...)
        return \App\Support\Tenant::run($paymentInstituteId, function () use ($payment, $paymentInstituteId, $data, $redactedPayload, $orderId, $statusCode) {
            $payHere = PayHereService::forInstitute($paymentInstituteId);

            // (c, d, e, g) Verify md5sig, merchant_id, payhere_amount, and payhere_currency for THAT institute
            if (!$payHere->verifyNotification($data, $payment)) {
                $reason = $payHere->getLastVerificationError() ?? 'PayHere notification verification failed';
                Log::error('PayHere notification verification FAILED', [
                    'institute_id' => $paymentInstituteId,
                    'order_id'     => $orderId,
                    'reason'       => $reason,
                    'payload'      => $redactedPayload,
                ]);
                \App\Services\EventNotifier::webhookError(
                    'PayHere Verification Failed',
                    "PayHere webhook rejected ({$reason}).",
                    \App\Support\Redact::array([
                        'institute_id'     => $paymentInstituteId,
                        'order_id'         => $orderId ?: 'unknown',
                        'merchant_id'      => $data['merchant_id'] ?? 'unknown',
                        'payhere_amount'   => $data['payhere_amount'] ?? 'unknown',
                        'payhere_currency' => $data['payhere_currency'] ?? 'unknown',
                        'status_code'      => $statusCode ?: 'unknown',
                        'md5sig'           => $data['md5sig'] ?? null,
                    ])
                );
                return response('Hash verification failed', 403);
            }

            // PayHere status codes:
            // 2 = success, 0 = pending, -1 = canceled, -2 = failed, -3 = chargeback
            if ($statusCode == 2) {
                // (f) Idempotent: already paid -> 200 OK with zero duplicate side effects
                if ($payment->status === 'paid') {
                    Log::info('PayHere notification idempotent replay ignored (already paid)', [
                        'institute_id' => $paymentInstituteId,
                        'order_id'     => $orderId,
                    ]);
                    return response('OK', 200);
                }

                if ($payment->kind === 'topup') {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $data) {
                        $payment->update([
                            'status'         => 'paid',
                            'paid_at'        => $payment->paid_at ?: now(),
                            'transaction_id' => $data['payment_id'] ?? $payment->transaction_id,
                        ]);
                        \App\Models\SubjectTopup::markPaid($payment->id, 'online');
                    });

                    $user = User::withoutGlobalScopes()->find($payment->user_id);

                    // Generate receipt and dispatch notifications (topup receipt format)
                    try {
                        $receiptSvc = app(\App\Services\PaymentReceiptService::class);
                        $receiptSvc->ensureReceipt($payment);
                        $receiptSvc->sendReceiptNotifications($payment);
                    } catch (\Throwable $e) {
                        Log::error('PayHere notify: Top-up receipt generation failed: ' . $e->getMessage());
                    }

                    if ($user) {
                        $this->notifier->notifyUser(
                            $user, 'general_update', 'tit_general_update',
                            [$user->full_name ?? $user->name, 'system'],
                            ['update_details' => 'Your new subjects are now open']
                        );
                    }

                    $addedList = !empty($payment->meta['added_subjects'])
                        ? (is_array($payment->meta['added_subjects']) ? implode(', ', $payment->meta['added_subjects']) : (string) $payment->meta['added_subjects'])
                        : 'Added subjects';
                    $studentName = $user ? ($user->full_name ?? $user->name) : 'Student';
                    $amountStr = number_format((float) $payment->amount, 2);
                    $adminMsg = "Subject top-up received: {$studentName} – {$addedList} – Rs {$amountStr}";

                    $this->notifier->notifyAdmin(
                        'admin_alert', 'tit_payment_success',
                        [$adminMsg],
                        ['alert_title' => 'Subject Top-up Received', 'alert_message' => $adminMsg]
                    );

                    $admin = User::withoutGlobalScopes()
                        ->where('role', 'admin')
                        ->where('institute_id', $paymentInstituteId)
                        ->first();
                    if ($admin) {
                        try {
                            $admin->notify(new AdminNotification(
                                $adminMsg,
                                'success',
                                route('admin.students.edit', $user?->id ?? 1),
                                'subject_topups'
                            ));
                        } catch (\Throwable $e) {
                            Log::error('PayHere notify: Admin notification failed: ' . $e->getMessage());
                        }
                    }

                    Log::info('PayHere topup payment SUCCESS', [
                        'institute_id' => $paymentInstituteId,
                        'order_id'     => $orderId,
                        'user_id'      => $user?->id,
                        'amount'       => $payment->amount,
                        'payment_id'   => $data['payment_id'] ?? 'N/A',
                    ]);

                    return response('OK', 200);
                }

                $payment->update([
                    'status'         => 'paid',
                    'paid_at'        => now(),
                    'transaction_id' => $data['payment_id'] ?? null,
                ]);

                $user = User::withoutGlobalScopes()->find($payment->user_id);
                if ($user) {
                    if (in_array($user->registration_status, ['pending_payment', 'payment_completed'])) {
                        $user->update([
                            'registration_status' => 'approved',
                            'admin_confirmed_at'  => now(),
                        ]);

                        if (empty($user->index_number)) {
                            try {
                                $indexSvc = app(\App\Services\StudentIndexService::class);
                                $institute = \App\Models\Institute::withoutGlobalScopes()->find($paymentInstituteId);
                                $format = $institute?->getIndexFormat() ?? 'grade';

                                if ($format === 'grade') {
                                    $indexSvc->assign($user, null);
                                } else {
                                    $batch = $indexSvc->calculateDefaultBatchYear($user) ?? (int) date('y');
                                    $indexSvc->assign($user, $batch);
                                }
                            } catch (\Throwable $e) {
                                Log::warning("Could not auto-assign index number in online payment callback: " . $e->getMessage());
                            }
                        }
                    }

                    $admin = User::withoutGlobalScopes()
                        ->where('role', 'admin')
                        ->where('institute_id', $paymentInstituteId)
                        ->first();
                    if ($admin) {
                        try {
                            $currencyLabel = $payment->meta['currency'] ?? $payHere->getCurrency();
                            $admin->notify(new AdminNotification(
                                "Payment Received: " . ($user->full_name ?? $user->name) . " - {$currencyLabel} " . number_format((float) $payment->amount, 2),
                                'success',
                                route('admin.students.edit', $user->id),
                                'admission_payments'
                            ));
                        } catch (\Throwable $e) {
                            Log::error('PayHere notify: Admin notification failed: ' . $e->getMessage());
                        }
                    }

                    $this->notifier->notifyUser(
                        $user, 'payment_success', 'tit_payment_success',
                        [$user->full_name ?? $user->name],
                        ['student_name' => $user->full_name ?? $user->name]
                    );

                    try {
                        $receiptSvc = app(\App\Services\PaymentReceiptService::class);
                        $receiptSvc->ensureReceipt($payment);
                        $receiptSvc->sendReceiptNotifications($payment);
                    } catch (\Throwable $e) {
                        Log::error('PayHere notify: Receipt generation failed: ' . $e->getMessage());
                    }

                    $currencyLabel = $payment->meta['currency'] ?? $payHere->getCurrency();
                    $this->notifier->notifyAdmin(
                        'admin_alert', 'tit_payment_success',
                        ["PAYMENT ALERT: " . ($user->full_name ?? $user->name) . " paid {$currencyLabel} " . number_format((float) $payment->amount, 2)],
                        ['alert_title' => 'Payment Received', 'alert_message' => ($user->full_name ?? $user->name) . " paid {$currencyLabel} " . number_format((float) $payment->amount, 2)]
                    );

                    Log::info('PayHere payment SUCCESS', [
                        'institute_id' => $paymentInstituteId,
                        'order_id'     => $orderId,
                        'user_id'      => $user->id,
                        'amount'       => $payment->amount,
                        'payment_id'   => $data['payment_id'] ?? 'N/A',
                    ]);
                }
            } elseif (in_array((string) $statusCode, ['-1', '-2', '-3'])) {
                if ($payment->status !== 'paid') {
                    $payment->update(['status' => 'failed']);
                }
                $statusLabel = match ((string) $statusCode) {
                    '-1' => 'Canceled',
                    '-2' => 'Failed',
                    '-3' => 'Chargeback',
                    default => 'Failed',
                };
                $user = User::withoutGlobalScopes()->find($payment->user_id);
                if ($user && $payment->kind !== 'topup') {
                    \App\Services\EventNotifier::paymentFailed($user, $payment, $statusLabel, $orderId);
                }
                Log::warning('PayHere payment FAILED/CANCELED', [
                    'institute_id' => $paymentInstituteId,
                    'order_id'     => $orderId,
                    'status_code'  => $statusCode,
                    'kind'         => $payment->kind,
                ]);
            } else {
                Log::info('PayHere payment PENDING', [
                    'institute_id' => $paymentInstituteId,
                    'order_id'     => $orderId,
                    'status_code'  => $statusCode,
                ]);
            }

            return response('OK', 200);
        });
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

        // Ownership check: payment.user_id must equal the authenticated profile
        // (or be a child/sibling of the authenticated account, as the existing multi-profile logic defines); else 403.
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $allowedUserIds = collect([$user->id]);
        if ($user->parent_id) {
            $allowedUserIds->push($user->parent_id);
            $siblingIds = User::where('parent_id', $user->parent_id)->pluck('id');
            $allowedUserIds = $allowedUserIds->merge($siblingIds);
        } else {
            $childIds = User::where('parent_id', $user->id)->pluck('id');
            $allowedUserIds = $allowedUserIds->merge($childIds);
        }

        $isAuthorized = in_array($user->role, ['admin', 'super_admin']) || $allowedUserIds->contains($payment->user_id);
        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized to access this payment.'], 403);
        }

        // If already paid via server notification, just return success
        if ($payment->status === 'paid') {
            return response()->json([
                'status' => 'paid',
                'message' => 'Payment already confirmed.',
                'registration_status' => $payment->user->registration_status ?? 'approved',
            ]);
        }

        $trustClientCallback = \App\Support\Integrations::trustPayHereClientCallback();

        // If still pending and client callback is not trusted, return 202
        if (!$trustClientCallback) {
            return response()->json([
                'status' => 'pending',
                'message' => 'Waiting for payment confirmation',
                'registration_status' => $payment->user->registration_status ?? 'pending',
            ], 202);
        }

        // If PAYHERE_TRUST_CLIENT_CALLBACK is true (dev/testing fallback):
        if ($payment->kind === 'topup') {
            \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $request) {
                $payment->update([
                    'status'         => 'paid',
                    'paid_at'        => $payment->paid_at ?: now(),
                    'transaction_id' => $request->payment_id ?: $payment->transaction_id,
                ]);
                \App\Models\SubjectTopup::markPaid($payment->id, 'online');
            });

            try {
                $receiptSvc = app(\App\Services\PaymentReceiptService::class);
                $receiptSvc->ensureReceipt($payment);
                $receiptSvc->sendReceiptNotifications($payment);
            } catch (\Throwable $e) {
                Log::error('paymentSuccess (trusted): Top-up receipt generation failed: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'paid',
                'message' => 'Payment successful. Top-up activated.',
                'registration_status' => $payment->user->registration_status ?? 'approved',
            ]);
        }

        // Mark as paid
        $payment->update([
            'status'  => 'paid',
            'paid_at' => now(),
            'transaction_id' => $request->payment_id,
        ]);

        $payee = $payment->user;
        if ($payee) {
            $payee->update([
                'registration_status' => 'approved',
                'admin_confirmed_at'  => now(),
            ]);

            if (empty($payee->index_number)) {
                try {
                    $indexSvc = app(\App\Services\StudentIndexService::class);
                    $institute = $payee->institute ?: \App\Models\Institute::find($payee->institute_id ?: 1);
                    $format = $institute?->getIndexFormat() ?? 'grade';

                    if ($format === 'grade') {
                        $indexSvc->assign($payee, null);
                    } else {
                        $batch = $indexSvc->calculateDefaultBatchYear($payee) ?? (int) date('y');
                        $indexSvc->assign($payee, $batch);
                    }
                } catch (\Throwable $e) {
                    Log::warning("Could not auto-assign index number in paymentSuccess: " . $e->getMessage());
                }
            }

            Log::info('paymentSuccess: User automatically approved', ['user_id' => $payee->id]);

            // Notify Admin
            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                try {
                    $admin->notify(new AdminNotification(
                        "Registration Payment Received: " . ($payee->full_name ?? $payee->name) . " - LKR " . number_format($payment->amount, 2),
                        'success',
                        route('admin.students.edit', $payee->id),
                        'admission_payments'
                    ));
                } catch (\Throwable $e) {
                    Log::error('Payment success notification failed: ' . $e->getMessage());
                }
            }

            // Send Payment Success notification to Student
            $this->notifier->notifyUser(
                $payee, 'payment_success', 'tit_payment_success',
                [$payee->full_name ?? $payee->name],
                ['student_name' => $payee->full_name ?? $payee->name]
            );

            // Generate receipt and dispatch notifications
            try {
                $receiptSvc = app(\App\Services\PaymentReceiptService::class);
                $receiptSvc->ensureReceipt($payment);
                $receiptSvc->sendReceiptNotifications($payment);
            } catch (\Throwable $e) {
                Log::error('paymentSuccess: Receipt generation failed: ' . $e->getMessage());
            }

            // Notify Admin
            $this->notifier->notifyAdmin(
                'admin_alert', 'tit_payment_success',
                ["PAYMENT ALERT: " . ($payee->full_name ?? $payee->name) . " paid LKR " . number_format($payment->amount, 2)],
                ['alert_title' => 'Payment Received', 'alert_message' => ($payee->full_name ?? $payee->name) . ' paid LKR ' . number_format($payment->amount, 2)]
            );
        }

        return response()->json([
            'status' => 'paid',
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

        $payment = Payment::monthly()
            ->where('user_id', $user->id)
            ->where('year_month', $yearMonth)
            ->orderBy('created_at', 'desc')
            ->first();

        // Get the last paid month
        $lastPaidPayment = \App\Models\Payment::monthly()
            ->where('user_id', $user->id)
            ->where('status', 'paid')
            ->orderBy('year_month', 'desc')
            ->first();
            
        $paidUntil = null;
        if ($lastPaidPayment) {
            $paidUntil = \Carbon\Carbon::createFromFormat('Y-m', $lastPaidPayment->year_month)->format('F Y');
        }

        $amount = $this->calculateUserAmount($user);

        // Fetch subjects info for the user
        $selectedSubjects = [];
        if (!empty($user->selected_subjects)) {
            $subs = $user->selected_subjects;
            if (is_array($subs)) {
                $selectedSubjects = $subs;
            } elseif (is_string($subs)) {
                $decoded = json_decode($subs, true);
                if (is_array($decoded)) {
                    $selectedSubjects = $decoded;
                } else {
                    $selectedSubjects = array_map('trim', explode(',', $subs));
                }
            }
        }
        $selectedSubjects = array_values(array_unique(array_filter($selectedSubjects)));
        $category = $this->getSubjectCategoryForUser($user);
        $subjectData = [];
        if (!empty($selectedSubjects)) {
            $subjectData = \App\Models\Subject::when($category, function($query) use ($category) {
                if ($category === 'grade_12_to_13') {
                    return $query->whereIn('category', ['arts_stream', 'commerce_stream', 'tech_stream', 'bio_maths_stream', 'grade_12_to_13']);
                }
                return $query->where('category', $category);
            })->whereIn('name', $selectedSubjects)->get(['name', 'price'])->toArray();
        }

        $history = Payment::where('user_id', $user->id)
            ->orderBy('year_month', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get()
            ->map(function ($p) {
                return [
                    'id'             => $p->id,
                    'year_month'     => $p->year_month,
                    'amount'         => (float) $p->amount,
                    'status'         => $p->status,
                    'paid_at'        => $p->paid_at ? (is_string($p->paid_at) ? $p->paid_at : $p->paid_at->toIso8601String()) : null,
                    'receipt_no'     => $p->receipt_no,
                    'payment_method' => $p->payment_method,
                ];
            });

        return response()->json([
            'is_paid'    => $payment && $payment->status === 'paid',
            'paid_until' => $paidUntil,
            'amount'     => $amount,
            'year_month' => $yearMonth,
            'subjects'   => $subjectData,
            'payment'    => $payment ? [
                'id'             => $payment->id,
                'status'         => $payment->status,
                'amount'         => $payment->amount,
                'paid_at'        => $payment->paid_at,
                'gateway_ref'    => $payment->gateway_ref,
                'payment_method' => $payment->payment_method,
                'receipt_no'     => $payment->receipt_no,
                'created_at'     => $payment->created_at ? (is_string($payment->created_at) ? $payment->created_at : $payment->created_at->toIso8601String()) : null,
            ] : null,
            'history'    => $history,
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
        $existingPaid = Payment::monthly()
            ->where('user_id', $user->id)
            ->where('year_month', $yearMonth)
            ->where('status', 'paid')
            ->exists();

        if ($existingPaid) {
            return response()->json([
                'message' => 'You have already paid for this month.',
            ], 400);
        }

        $instituteId = (int) ($user->institute_id ?: (\App\Support\Tenant::id() ?: 1));
        $payHere = PayHereService::forInstitute($instituteId);

        if (!$payHere->isConfigured()) {
            return response()->json([
                'code'    => 'online_payments_unavailable',
                'error'   => 'online_payments_unavailable',
                'message' => 'Online payments are not available for this institute. Please choose bank transfer or cash payment.',
            ], 422);
        }

        // Cancel any pending monthly payments for this month
        Payment::monthly()
            ->where('user_id', $user->id)
            ->where('year_month', $yearMonth)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        // Auto-supersede any pending top-ups from past months
        \App\Models\SubjectTopup::where('user_id', $user->id)
            ->where('year_month', '<', $yearMonth)
            ->where('status', 'pending')
            ->update(['status' => 'superseded']);

        $amount = $this->calculateUserAmount($user);

        $admFee = $user->getAdmissionFeeAmount();
        $meta = ['currency' => $payHere->getCurrency()];
        if ($admFee > 0) {
            $meta['admission_fee'] = $admFee;
        }

        $payment = Payment::create([
            'user_id'        => $user->id,
            'amount'         => $amount,
            'status'         => 'pending',
            'kind'           => 'monthly',
            'payment_method' => 'online',
            'year_month'     => $yearMonth,
            'gateway_ref'    => 'PH_' . strtoupper(uniqid()),
            'institute_id'   => $instituteId,
            'meta'           => $meta,
        ]);

        $params = $payHere->buildCheckoutParams($payment, $user);
        $checkoutUrl = $payHere->getCheckoutUrl();

        Log::info('Monthly PayHere checkout initialized', [
            'institute_id' => $instituteId,
            'user_id'      => $user->id,
            'order_id'     => $payment->gateway_ref,
            'amount'       => $amount,
            'year_month'   => $yearMonth,
        ]);

        return response()->json([
            'message'     => 'Monthly payment initialized.',
            'payhere_url' => $checkoutUrl,
            'params'      => $params,
        ]);
    }

    /**
     * Initiate an offline monthly payment.
     *
     * Subject/Medium Updates & Fees Policy:
     * - When a student updates their subjects AFTER paying for the current month:
     *   1. The new subject list ($user->selected_subjects) takes effect immediately for learning materials and classes.
     *   2. The already-paid month is NOT re-charged (no duplicate or additional payment row is created).
     *   3. The newly calculated monthly fee will apply only from the NEXT unpaid month.
     *   4. Any requested change to medium creates a pending MediumChangeRequest requiring admin approval;
     *      the student continues on their current medium (and sees current-medium materials) until approved.
     */
    public function offlineMonthlyPayment(Request $request)
    {
        $user = $request->user();
        $yearMonth = now()->format('Y-m');
        $monthName = now()->format('F Y');

        // Check if already paid
        $existingPaid = Payment::monthly()
            ->where('user_id', $user->id)
            ->where('year_month', $yearMonth)
            ->where('status', 'paid')
            ->exists();

        if ($existingPaid) {
            return response()->json([
                'message' => 'You have already paid for this month.',
            ], 400);
        }

        // Auto-supersede any pending top-ups from past months
        \App\Models\SubjectTopup::where('user_id', $user->id)
            ->where('year_month', '<', $yearMonth)
            ->where('status', 'pending')
            ->update(['status' => 'superseded']);

        $amount = $this->calculateUserAmount($user);

        // De-duplicate: if a PENDING offline row for the same month already exists, reuse it
        $existingPending = Payment::monthly()
            ->where('user_id', $user->id)
            ->where('year_month', $yearMonth)
            ->where('payment_method', 'offline')
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $shouldAlertAdmin = false;
        $cacheKey = "offline_monthly_admin_alert_{$user->id}_{$yearMonth}";

        if ($existingPending) {
            $payment = $existingPending;
            if ((float) $payment->amount !== (float) $amount) {
                $payment->amount = $amount;
                $payment->save();
            }

            // Check if created or alerted within 10 minutes (600 seconds)
            $createdAtTs = $payment->created_at ? \Carbon\Carbon::parse($payment->created_at)->timestamp : 0;
            $isRecent = ($createdAtTs > 0 && abs(now()->timestamp - $createdAtTs) < 600)
                || \Illuminate\Support\Facades\Cache::has($cacheKey);

            if (!$isRecent) {
                $shouldAlertAdmin = true;
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(10));
            }
        } else {
            // Cancel any old non-offline pending payments for this month
            Payment::monthly()
                ->where('user_id', $user->id)
                ->where('year_month', $yearMonth)
                ->where('status', 'pending')
                ->where('payment_method', '!=', 'offline')
                ->update(['status' => 'cancelled']);

            $admFee = $user->getAdmissionFeeAmount();
            $meta = [];
            if ($admFee > 0) {
                $meta['admission_fee'] = $admFee;
            }

            $payment = Payment::create([
                'user_id'        => $user->id,
                'amount'         => $amount,
                'status'         => 'pending',
                'kind'           => 'monthly',
                'payment_method' => 'offline',
                'year_month'     => $yearMonth,
                'gateway_ref'    => 'OFFLINE_' . strtoupper(uniqid()),
                'institute_id'   => $user->institute_id ?? 1,
                'meta'           => !empty($meta) ? $meta : null,
            ]);

            $shouldAlertAdmin = true;
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(10));
        }

        // Student details for notifications
        $studentName = $user->full_name ?? $user->name;
        $grade = $user->current_grade ?? 'N/A';
        $medium = $user->medium ?? 'N/A';
        $contactInfo = $user->phone_number;
        if (empty($contactInfo) && $user->parent_id && $user->parent) {
            $contactInfo = 'via Parent: ' . ($user->parent->full_name ?? $user->parent->name);
        } elseif (empty($contactInfo)) {
            $contactInfo = str_ends_with($user->email, '@child.local') ? 'No Contact' : $user->email;
        }

        // Notify user and admin asynchronously after response
        $job = dispatch(function () use ($user, $studentName, $grade, $medium, $contactInfo, $monthName, $amount, $shouldAlertAdmin) {
            try {
                $notifier = app(\App\Services\NotificationService::class);

                // 1. Send student request received notification (with email fallback)
                $studentMessage = "We received your offline payment request for {$monthName} – Rs " . number_format($amount, 2) . ". Please pay at the institute / bank and send the slip; your access opens as soon as the admin confirms. You will get your receipt by e-mail and WhatsApp.";

                $notifier->notifyUser(
                    $user,
                    'payment_request_received',
                    'tit_payment_request_received',
                    [$studentName, $monthName, number_format($amount, 2)],
                    [
                        'student_name' => $studentName,
                        'month'        => $monthName,
                        'amount'       => $amount,
                        'message'      => $studentMessage,
                    ]
                );

                // 2. Alert Admin (if not duplicate within 10 min)
                if ($shouldAlertAdmin) {
                    $editUrl = route('admin.students.edit', $user->id);
                    $adminAlertText = "Student {$studentName} (Grade: {$grade}, Medium: {$medium}, Contact: {$contactInfo}) requested offline payment for {$monthName} (Rs " . number_format($amount, 2) . "). Edit: {$editUrl}";

                    $notifier->notifyAdmin(
                        'admin_alert',
                        'tit_admin_approved',
                        ["ADMIN ALERT: Offline Payment Request from {$studentName}", "admin"],
                        [
                            'alert_title'   => 'Monthly Offline Payment Request',
                            'alert_message' => $adminAlertText,
                            'student_name'  => $studentName,
                            'grade'         => $grade,
                            'medium'        => $medium,
                            'month'         => $monthName,
                            'amount'        => $amount,
                            'contact'       => $contactInfo,
                            'link'          => $editUrl,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Offline monthly payment notification failed: ' . $e->getMessage());
            }
        });

        if (!app()->environment('testing')) {
            $job->afterResponse();
        }

        Log::info('Offline monthly payment initialized', [
            'user_id'    => $user->id,
            'order_id'   => $payment->gateway_ref,
            'amount'     => $amount,
            'year_month' => $yearMonth,
            'reused'     => (bool) $existingPending,
        ]);

        return response()->json([
            'message' => 'Offline payment initiated. Please pay offline. Admin will confirm and you will receive a WhatsApp message.',
            'payment' => [
                'id'             => $payment->id,
                'status'         => $payment->status,
                'amount'         => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'year_month'     => $payment->year_month,
                'created_at'     => $payment->created_at ? $payment->created_at->toIso8601String() : now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Initiate an offline advance payment for multiple months.
     */
    public function offlineAdvancePayment(Request $request)
    {
        $request->validate([
            'months' => 'required|integer|min:1|max:12',
        ]);

        $user = $request->user();
        $monthsCount = $request->input('months');
        $monthlyAmount = $this->calculateUserAmount($user);
        $totalAmount = $monthlyAmount * $monthsCount;

        $advanceRequest = \App\Models\AdvancePaymentRequest::create([
            'user_id' => $user->id,
            'months_count' => $monthsCount,
            'amount' => $totalAmount,
            'status' => 'pending',
        ]);

        // Notify Admin asynchronously after response
        $studentName = $user->full_name ?? $user->name;
        $contactInfo = $user->phone_number;
        if (empty($contactInfo) && $user->parent_id && $user->parent) {
            $contactInfo = 'via Parent: ' . ($user->parent->full_name ?? $user->parent->name);
        } elseif (empty($contactInfo)) {
            $contactInfo = str_ends_with($user->email, '@child.local') ? 'No Contact' : $user->email;
        }

        dispatch(function () use ($studentName, $contactInfo, $monthsCount, $totalAmount) {
            try {
                $notifier = app(\App\Services\NotificationService::class);
                $notifier->notifyAdmin(
                    'admin_alert', 'tit_admin_approved',
                    ["ADMIN ALERT: Advance Payment Request from " . $studentName, "admin"],
                    ['alert_title' => 'Advance Payment Request', 'alert_message' => "Student {$studentName} ({$contactInfo}) requested offline payment for {$monthsCount} months (LKR {$totalAmount})."]
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Offline advance payment notification failed: ' . $e->getMessage());
            }
        })->afterResponse();

        Log::info('Offline advance payment requested', [
            'user_id' => $user->id,
            'months_count' => $monthsCount,
            'amount' => $totalAmount,
            'request_id' => $advanceRequest->id,
        ]);

        return response()->json([
            'message' => 'Advance payment requested successfully. Admin will confirm shortly.',
            'request_id' => $advanceRequest->id,
            'amount' => $totalAmount,
            'months' => $monthsCount
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

        $parentInstituteId = (int) ($parent->institute_id ?: (\App\Support\Tenant::id() ?: 1));
        $sentWhatsApp = false;
        $whatsappService = \App\Services\WhatsAppService::forInstitute($parentInstituteId);
        if ($parent->phone_number && (config('services.whatsapp.enabled', false) || $whatsappService->isConfigured())) {
            $sentWhatsApp = $whatsappService->sendTemplate($parent->phone_number, 'tit_otp_verification', 'en', [$otp]);
        }

        if (!$sentWhatsApp && $parent->email) {
            try {
                $sentEmail = \App\Support\InstituteMail::to($parent->email, $parentInstituteId)
                    ->send(new \App\Mail\MergeAccountOtpMail($parent, $otp));
                if (!$sentEmail) {
                    return response()->json([
                        'message' => 'Could not send verification code, please try again.',
                    ], 500);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('MergeAccountOtpMail failed: ' . \App\Support\Redact::sanitizeText($e->getMessage()));
                return response()->json([
                    'message' => 'Could not send verification code, please try again.',
                ], 500);
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
            $profiles = app(\App\Http\Controllers\AuthController::class)->buildProfilesList($parent);

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
        $customFieldLabels = json_decode(\App\Models\SiteSetting::get('register_custom_fields', '[]'), true) ?: [];
        $allowedCustomKeys = array_map(function($label) {
            return preg_replace('/\s+/', '_', strtolower($label));
        }, $customFieldLabels);
        
        $customFieldsData = array_filter($request->only($allowedCustomKeys), function($value) {
            return $value !== null;
        });
        
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
        
        $targetInstitute = $parent->institute ?: \App\Models\Institute::find($parent->institute_id ?: 1);
        try {
            $sibling = \App\Services\PlanLimits::runWithStudentLock($targetInstitute, 1, function () use ($userData) {
                return User::create($userData);
            });
        } catch (\App\Exceptions\PlanLimitReachedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'plan_limit_reached',
            ], 403);
        }

        Log::info('New sibling account created via OTP merge', ['parent_id' => $parent->id, 'sibling_id' => $sibling->id]);

        // Login the parent
        $token = $parent->createToken('auth_token')->plainTextToken;

        $profiles = app(\App\Http\Controllers\AuthController::class)->buildProfilesList($parent);

        return response()->json([
            'message' => 'Account merged successfully. You are now logged in.',
            'user' => $profiles->first(),
            'profiles' => $profiles,
            'token' => $token,
        ]);
    }
}
