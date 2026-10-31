<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\ActivityLog;
use App\Notifications\AdminNotification;
use App\Services\NotificationService;
use App\Services\StudentPromotionService;
use App\Traits\ValidatesEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    use ValidatesEmail;

    protected NotificationService $notifier;
    protected StudentPromotionService $promotionService;
    protected \App\Services\StudentIndexService $indexService;

    public function __construct(
        NotificationService $notifier,
        StudentPromotionService $promotionService,
        \App\Services\StudentIndexService $indexService
    ) {
        $this->notifier = $notifier;
        $this->promotionService = $promotionService;
        $this->indexService = $indexService;
    }
    /**
     * Display a listing of all students
     */
    public function index(Request $request)
    {
        // Check if user is admin
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $query = User::where('role', 'user')
            ->whereNotNull('full_name')
            ->with('parent');
            
        if ($request->has('needs_review') && $request->needs_review == '1') {
            $query->where('needs_subject_review', true);
        }

        $institute = auth()->check() ? auth()->user()->institute : null;
        $instituteFormat = $institute?->getIndexFormat() ?? 'grade';

        if ($institute && auth()->user()->role !== 'super_admin') {
            $query->where('institute_id', $institute->id);
        }

        if ($request->filled('grade')) {
            $query->where(function ($q) use ($request) {
                $q->where('current_grade', $request->grade)
                  ->orWhere('current_grade', 'Grade ' . $request->grade);
            });
        }

        if ($request->filled('medium') && $request->medium !== 'all') {
            $medium = strtolower(trim($request->medium));
            if (in_array($medium, ['english', 'tamil', 'both'], true)) {
                $query->where('medium', $medium);
            }
        }

        // In grade mode, ignore stray batch_year parameter; only apply in legacy batch mode
        if ($instituteFormat === 'batch' && $request->filled('batch_year')) {
            $query->where('batch_year', (int) $request->batch_year);
        }

        if ($request->has('has_index') && $request->has_index !== '') {
            $val = (string) $request->has_index;
            if ($val === '1' || $val === 'assigned') {
                $query->whereNotNull('index_number');
            } elseif ($val === '0' || $val === 'unassigned') {
                $query->whereNull('index_number');
            }
        }

        if ($request->has('pending_payments') && (string) $request->pending_payments === '1') {
            return redirect()->route('admin.students.pending-payments');
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('phone_number', 'like', "%{$term}%")
                  ->orWhere('index_number', 'like', "%{$term}%");
            });
        }

        // Filtered counts matching current filters for the strip
        $filteredTotalCount = (clone $query)->count();
        $filteredAssignedCount = (clone $query)->whereNotNull('index_number')->where('index_number', '!=', '')->count();
            
        $currentYearMonth = now()->format('Y-m');
        $students = $query->with(['payments' => function ($q) use ($currentYearMonth) {
            $q->where('year_month', $currentYearMonth);
        }, 'subjectTopups' => function ($q) use ($currentYearMonth) {
            $q->where('year_month', $currentYearMonth);
        }])->latest()->paginate(15)->appends($request->query());
        
        $instituteId = auth()->check() ? auth()->user()->institute_id : null;
        $countQuery = User::where('role', 'user')->whereNotNull('full_name');
        if ($instituteId) {
            $countQuery->where('institute_id', $instituteId);
        }
        $totalStudentsCount = (clone $countQuery)->count();
        $assignedIndexCount = (clone $countQuery)->whereNotNull('index_number')->count();
        $pendingPaymentsCount = (clone $countQuery)->whereHas('payments', function ($q) use ($currentYearMonth) {
            $q->where('year_month', $currentYearMonth)
              ->where('status', 'pending');
        })->count();
        $pendingTopupsCount = (clone $countQuery)->whereHas('payments', function ($q) use ($currentYearMonth) {
            $q->where('year_month', $currentYearMonth)
              ->where('status', 'pending')
              ->where('kind', 'topup');
        })->count();
        $pendingMonthlyCount = $pendingPaymentsCount - $pendingTopupsCount;
        $gradeMapping = $this->indexService->getGradeBatchMapping($instituteId);

        return view('admin.students.index', compact(
            'students',
            'totalStudentsCount',
            'assignedIndexCount',
            'filteredTotalCount',
            'filteredAssignedCount',
            'instituteFormat',
            'gradeMapping',
            'pendingPaymentsCount',
            'pendingMonthlyCount',
            'pendingTopupsCount',
            'currentYearMonth'
        ));
    }

    /**
     * Display the specified student
     */
    public function show($id)
    {
        // Check if user is admin
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $student = User::where('role', 'user')
            ->where('id', $id)
            ->first();

        if (!$student) {
            return redirect()->route('admin.students.index')
                ->with('error', 'Student not found. The registration may be incomplete or the record was deleted.');
        }

        $payments = \App\Models\Payment::where('user_id', $student->id)
            ->orderByDesc('year_month')
            ->orderByDesc('id')
            ->get();
        
        // Use edit view as show view is not implemented separately
        return view('admin.students.edit', compact('student', 'payments'));
    }

    /**
     * Show the form for editing the specified student
     */
    public function edit($id)
    {
        // Check if user is admin
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $student = User::where('role', 'user')
            ->where('id', $id)
            ->first();

        if (!$student) {
            return redirect()->route('admin.students.index')
                ->with('error', 'Student not found. The registration may be incomplete or the record was deleted.');
        }

        $payments = Payment::where('user_id', $student->id)
            ->orderByDesc('year_month')
            ->orderByDesc('id')
            ->get();
        
        $medium = strtolower($student->medium ?? '');
        $subjectsQuery = Subject::orderBy('name');
        if ($medium) {
            $subjectsQuery->where(function ($q) use ($medium) {
                $q->where('medium', $medium)->orWhere('medium', 'both');
            });
        }
        $subjects = $subjectsQuery->get()->groupBy('category')->map(function ($items) {
            return $items->unique('name')->values();
        });

        $gradeMapping = $this->indexService->getGradeBatchMapping($student->institute_id);
        
        return view('admin.students.edit', compact('student', 'payments', 'subjects', 'gradeMapping'));
    }

    /**
     * Update the specified student in storage
     */
    public function update(Request $request, $id)
    {
        // Check if user is admin
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $student = User::where('role', 'user')
            ->where('id', $id)
            ->whereNotNull('full_name')
            ->firstOrFail();
        
        // Normalize phone number
        if ($request->has('phone_number')) {
            $phone = preg_replace('/\D/', '', $request->phone_number);
            if (strlen($phone) == 10 && str_starts_with($phone, '0')) {
                $phone = '94' . substr($phone, 1);
            } elseif (strlen($phone) == 9 && str_starts_with($phone, '7')) {
                $phone = '94' . $phone;
            }
            $request->merge(['phone_number' => $phone]);
        }
        
        // Validate the request
        $validationRules = [
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'school_name' => 'required|string|max:255',
            'medium' => 'required|in:english,tamil',
            'online_experience' => 'nullable|boolean',
            'device_used' => 'required|string|max:255',
            'current_grade' => 'required|string|max:100',
            'stream' => 'nullable|string|max:50|in:arts,bio_maths',
            'selected_subjects' => 'nullable|array',
            'password' => 'nullable|string|min:8',
            'custom_fields' => 'nullable|array',
        ];

        if (!$student->parent_id) {
            $validationRules['phone_number'] = 'required|digits_between:9,15|unique:users,phone_number,' . $student->id;
            $validationRules['email'] = ['required', 'email', 'max:255', Rule::unique('users')->ignore($student->id)];
        }

        $validated = $request->validate($validationRules);
        
        // Update student data
        $subjects = null;
        if (isset($validated['selected_subjects']) && is_array($validated['selected_subjects'])) {
            $subjects = json_encode(array_values(array_unique($validated['selected_subjects'])), JSON_UNESCAPED_UNICODE);
        }

        $updateData = [
            'full_name' => $validated['full_name'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'school_name' => $validated['school_name'],
            'medium' => $validated['medium'],
            'online_experience' => $request->has('online_experience') ? (bool) $validated['online_experience'] : $student->online_experience,
            'device_used' => $validated['device_used'],
            'current_grade' => $validated['current_grade'],
            'stream' => $validated['stream'] ?? null,
            'selected_subjects' => $subjects,
            'custom_fields' => $request->custom_fields,
        ];

        if (!$student->parent_id) {
            $updateData['phone_number'] = $validated['phone_number'];
            $updateData['email'] = $validated['email'];
        }

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        } else {
            unset($updateData['password']);
        }

        $student->update($updateData);
        
        return redirect()->route('admin.students.index')
            ->with('success', 'Student information updated successfully!');
    }

    /**
     * Confirm student registration and send WhatsApp message.
     */
    public function confirm(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $student = User::where('role', 'user')
            ->where('id', $id)
            ->whereNotNull('full_name')
            ->firstOrFail();

        $institute = $student->institute ?: \App\Models\Institute::find($student->institute_id ?: 1);
        $format = $institute?->getIndexFormat() ?? 'grade';

        $indexAssigned = false;
        $batchYear = null;
        if ($format === 'grade') {
            $seriesInput = $request->filled('series') ? $request->input('series') : null;
            try {
                $this->indexService->assign($student, $seriesInput);
                $indexAssigned = true;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Could not auto-assign index in confirm: " . $e->getMessage());
            }
        } else {
            if ($request->filled('batch_year')) {
                try {
                    $batchYear = $this->indexService->normalizeBatchYear($request->batch_year);
                } catch (\InvalidArgumentException $e) {
                    // Invalid batch year
                }
            } else {
                $batchYear = $this->indexService->getBatchForGrade($student->current_grade, $student->institute_id);
            }

            if ($batchYear !== null) {
                try {
                    $this->indexService->assign($student, $batchYear);
                    $indexAssigned = true;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Could not auto-assign index in confirm: " . $e->getMessage());
                }
            }
        }

        $student->update([
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
        ]);

        // Notify student (WhatsApp with email fallback)
        $this->notifier->notifyUser(
            $student, 'admin_approved', 'tit_admin_approved',
            [$student->full_name ?? $student->name, 'admin'],
            ['student_name' => $student->full_name ?? $student->name]
        );

        // Notify Admin
        $this->notifier->notifyAdmin(
            'admin_alert', 'tit_admin_approved',
            ["ADMIN ALERT: Approved student " . ($student->full_name ?? $student->name), "admin"],
            ['alert_title' => 'Student Approved', 'alert_message' => 'Approved student: ' . ($student->full_name ?? $student->name)]
        );

        $currentMonth = now()->format('Y-m');
        $hasPaidCurrentMonth = Payment::where('user_id', $student->id)
            ->where('year_month', $currentMonth)
            ->where('status', 'paid')
            ->exists();

        if (!$indexAssigned && $format === 'batch') {
            $gradeDisplay = $student->current_grade ?: 'unknown';
            $response = redirect()->route('admin.students.index')
                ->with('warning', "No batch year for Grade {$gradeDisplay} – set it in Index Number Batches, the number was not assigned");
            if (!$hasPaidCurrentMonth) {
                $response->with('info', 'Payment is still PENDING – use Mark Paid when the money is received.');
            }
            return $response;
        }

        if (!$hasPaidCurrentMonth) {
            return redirect()->route('admin.students.index')
                ->with('success', 'Registration confirmed. Payment is still PENDING – use Mark Paid when the money is received.');
        }

        return redirect()->route('admin.students.index')
            ->with('success', 'Student confirmed and index number assigned.');
    }

    /**
     * Confirm a registration AND mark current month payment as paid (money received).
     */
    public function confirmAndMarkPaid(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $student = User::where('role', 'user')
            ->where('id', $id)
            ->whereNotNull('full_name')
            ->firstOrFail();

        $institute = $student->institute ?: \App\Models\Institute::find($student->institute_id ?: 1);
        $format = $institute?->getIndexFormat() ?? 'grade';

        $indexAssigned = false;
        $batchYear = null;
        if ($format === 'grade') {
            $seriesInput = $request->filled('series') ? $request->input('series') : null;
            try {
                $this->indexService->assign($student, $seriesInput);
                $indexAssigned = true;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Could not auto-assign index in confirmAndMarkPaid: " . $e->getMessage());
            }
        } else {
            if ($request->filled('batch_year')) {
                try {
                    $batchYear = $this->indexService->normalizeBatchYear($request->batch_year);
                } catch (\InvalidArgumentException $e) {
                    // Invalid batch year
                }
            } else {
                $batchYear = $this->indexService->getBatchForGrade($student->current_grade, $student->institute_id);
            }

            if ($batchYear !== null) {
                try {
                    $this->indexService->assign($student, $batchYear);
                    $indexAssigned = true;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Could not auto-assign index in confirmAndMarkPaid: " . $e->getMessage());
                }
            }
        }

        $student->update([
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
        ]);

        // Notify student (WhatsApp with email fallback)
        $this->notifier->notifyUser(
            $student, 'admin_approved', 'tit_admin_approved',
            [$student->full_name ?? $student->name, 'admin'],
            ['student_name' => $student->full_name ?? $student->name]
        );

        // Notify Admin
        $this->notifier->notifyAdmin(
            'admin_alert', 'tit_admin_approved',
            ["ADMIN ALERT: Approved student " . ($student->full_name ?? $student->name), "admin"],
            ['alert_title' => 'Student Approved', 'alert_message' => 'Approved student: ' . ($student->full_name ?? $student->name)]
        );

        $yearMonth = now()->format('Y-m');
        $paidAt = now()->toDateString();
        $amount = $request->filled('amount') ? (float) $request->amount : null;
        $payment = $this->markMonthPaid($student, $yearMonth, $paidAt, $amount, $request->input('received_by'));

        $preflightWarning = null;
        if ($payment) {
            try {
                $receiptService = app(\App\Services\PaymentReceiptService::class);
                $receiptService->ensureReceipt($payment);
                $preflightWarning = $receiptService->preflightCheck($payment);
                $receiptService->sendReceiptNotifications($payment);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Receipt trigger failed in confirmAndMarkPaid: ' . $e->getMessage());
                $preflightWarning = $e->getMessage();
            }
        }

        if (!$indexAssigned && $format === 'batch') {
            $gradeDisplay = $student->current_grade ?: 'unknown';
            $response = redirect()->route('admin.students.index')
                ->with('warning', "No batch year for Grade {$gradeDisplay} – set it in Index Number Batches, the number was not assigned. Payment was marked as paid.");
            if ($preflightWarning) {
                $response->with('error', "Payment saved, but receipt could not be sent: {$preflightWarning}. Use Resend on the student's page.");
            }
            return $response;
        }

        $response = redirect()->route('admin.students.index')
            ->with('success', 'Student confirmed, index number assigned, and payment marked as paid.');
        if ($preflightWarning) {
            $response->with('warning', "Payment marked, but receipt could not be sent: {$preflightWarning}. Use Resend on the student's page.");
        }
        return $response;
    }

    /**
     * Reject a pending registration and email the reason.
     */
    public function reject(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request->validate(['reason' => 'nullable|string|max:500']);

        $student = User::where('role', 'user')->where('id', $id)->firstOrFail();

        $student->update([
            'registration_status' => 'rejected',
            'admin_confirmed_at' => null,
        ]);

        \App\Services\EventNotifier::registrationRejected($student, $request->input('reason'));

        return redirect()->route('admin.students.index')
            ->with('success', 'Registration rejected and the student was notified by email.');
    }

    public function create()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $subjects = Subject::orderBy('name')->get()->groupBy('category');
        $gradeMapping = $this->indexService->getGradeBatchMapping(auth()->user()->institute_id);
        return view('admin.students.create', compact('subjects', 'gradeMapping'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        // Normalize phone number
        if ($request->has('phone_number')) {
            $phone = preg_replace('/\D/', '', $request->phone_number);
            if (strlen($phone) == 10 && str_starts_with($phone, '0')) {
                $phone = '94' . substr($phone, 1);
            } elseif (strlen($phone) == 9 && str_starts_with($phone, '7')) {
                $phone = '94' . $phone;
            }
            $request->merge(['phone_number' => $phone]);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|digits_between:9,15|unique:users,phone_number',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'school_name' => 'required|string|max:255',
            'medium' => 'required|in:english,tamil',
            'online_experience' => 'required|boolean',
            'device_used' => 'required|string|max:255',
            'current_grade' => 'required|string|max:100',
            'stream' => 'nullable|string|max:50|in:arts,bio_maths',
            'selected_subjects' => 'nullable|array',
            'batch_year' => 'nullable|integer',
            'quick_payment' => 'nullable|boolean',
            'custom_payment_month' => 'nullable|date',
        ]);

        // JSON-encode selected subjects array
        $subjects = null;
        if (isset($validated['selected_subjects']) && is_array($validated['selected_subjects'])) {
            $subjects = json_encode(array_values(array_unique($validated['selected_subjects'])), JSON_UNESCAPED_UNICODE);
        }

        // Auto-generate username from email prefix
        $emailPrefix = explode('@', $validated['email'])[0];
        $username = $emailPrefix;
        $counter = 1;
        while (User::where('name', $username)->exists()) {
            $username = $emailPrefix . $counter;
            $counter++;
        }

        // Use admin-entered password
        $user = User::create([
            'name' => $username,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'] ?? null,
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'school_name' => $validated['school_name'],
            'medium' => $validated['medium'],
            'online_experience' => $request->boolean('online_experience'),
            'device_used' => $validated['device_used'],
            'current_grade' => $validated['current_grade'],
            'stream' => $validated['stream'] ?? null,
            'selected_subjects' => $subjects,
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
        ]);

        // Assign automatic student index number
        try {
            $institute = $user->institute ?: \App\Models\Institute::find($user->institute_id ?: 1);
            $format = $institute?->getIndexFormat() ?? 'grade';

            if ($format === 'grade') {
                $seriesInput = $request->filled('series') ? $request->input('series') : null;
                $this->indexService->assign($user, $seriesInput);
            } else {
                $batchYear = $request->filled('batch_year')
                    ? $this->indexService->normalizeBatchYear($request->batch_year)
                    : $this->indexService->getBatchForGrade($user->current_grade, $user->institute_id);
                if ($batchYear !== null) {
                    $this->indexService->assign($user, $batchYear);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning("Could not auto-assign index number in student store(): " . $e->getMessage());
        }

        // Notify Admin of manual student entry
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new AdminNotification(
                "Manual Student Entry: " . ($user->full_name ?? $user->name),
                'info',
                route('admin.students.show', $user->id),
                'admission_new'
            ));
        }

        // Handle Quick Payment (current month)
        $createdPayment = null;
        if ($request->boolean('quick_payment')) {
            $createdPayment = $this->markMonthPaid($user, now()->format('Y-m'), now()->format('Y-m-d H:i:s'), null);
        }

        // Handle Custom Payment Date
        if (!empty($validated['custom_payment_month'])) {
            $customDate = $validated['custom_payment_month'];
            $yearMonth = substr($customDate, 0, 7); // Extract YYYY-MM from YYYY-MM-DD
            $createdPayment = $this->markMonthPaid($user, $yearMonth, $customDate, null);
        }

        $preflightWarning = null;
        if ($createdPayment) {
            try {
                $receiptService = app(\App\Services\PaymentReceiptService::class);
                $receiptService->ensureReceipt($createdPayment);
                $preflightWarning = $receiptService->preflightCheck($createdPayment);
                $receiptService->sendReceiptNotifications($createdPayment);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Receipt trigger failed in student store: ' . $e->getMessage());
                $preflightWarning = $e->getMessage();
            }
        }

        $response = redirect()->route('admin.students.index')->with('success', 'Student added successfully! Username: ' . $username . ' | Password: ' . $validated['password']);
        if ($preflightWarning) {
            $response->with('warning', "Payment saved, but the receipt could not be sent: {$preflightWarning}. Use Resend on the student's page.");
        }
        return $response;
    }

    public function deactivate($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $student = User::where('role', 'user')->where('id', $id)->firstOrFail();
        $student->update(['deactivated_at' => now()]);

        // Send deactivation notification (WhatsApp with email fallback)
        $this->notifier->notifyUser(
            $student, 'account_deactivated', 'tit_account_deactivated',
            [$student->full_name ?? $student->name, 'admin'],
            ['student_name' => $student->full_name ?? $student->name]
        );

        return redirect()->route('admin.students.index')->with('success', 'Student deactivated and notification sent.');
    }

    /**
     * Activate a student account
     */
    public function activate($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $student = User::where('role', 'user')->where('id', $id)->firstOrFail();
        $student->update(['deactivated_at' => null]);
        
        return redirect()->route('admin.students.index')->with('success', 'Student account reactivated successfully and notification sent.');
    }

    /**
     * Delete a student account
     */
    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $student = User::where('role', 'user')->where('id', $id)->firstOrFail();
        
        // Delete the student (this will also trigger cascades if defined in migration)
        $student->delete();
        
        return redirect()->route('admin.students.index')->with('success', 'Student account deleted permanently.');
    }

    public function resetPasswordAndNotify($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Admin access required'], 403);
        }
        $student = User::where('role', 'user')->where('id', $id)->firstOrFail();
        
        $newPassword = \Illuminate\Support\Str::random(10);
        $student->update([
            'password' => Hash::make($newPassword)
        ]);
        
        try {
            $targetEmail = $student->email;
            if ($student->parent_id) {
                $parent = \App\Models\User::find($student->parent_id);
                if ($parent && $parent->email) {
                    $targetEmail = $parent->email;
                }
            }

            \Illuminate\Support\Facades\Log::info('Initiating password reset email for ID: ' . $id . ' with Target Email: ' . $targetEmail);
            
            $mail = new \App\Mail\GoogleAutoPasswordMail($student, $newPassword, true);
            \Illuminate\Support\Facades\Log::info('Mail body preview', ['html_length' => strlen($mail->render())]);
            
            if (!$this->isValidEmailForSending($targetEmail)) {
                \Illuminate\Support\Facades\Log::warning('StudentController: Skipped password reset email — invalid address: ' . $targetEmail);
                return response()->json(['success' => true, 'message' => 'New password generated, but email could not be sent (invalid email address). Please share the password manually.', 'password' => $newPassword]);
            }

            \Illuminate\Support\Facades\Mail::to($targetEmail)->send($mail);
            \Illuminate\Support\Facades\Log::info('Successfully sent password reset email to: ' . $targetEmail);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send password reset email to: ' . $targetEmail . '. Error: ' . $e->getMessage());
            if ($this->isPermanentMailFailure($e)) {
                $this->markEmailAsBounced($targetEmail);
            }
            return response()->json(['success' => false, 'message' => 'Failed to send email. Check logs.']);
        }
        
        return response()->json(['success' => true, 'message' => 'New password generated and emailed to the student.']);
    }

    public function markPaid(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $student = User::where('role', 'user')->where('id', $id)->whereNotNull('full_name')->firstOrFail();

        // Accept either a full date (YYYY-MM-DD) or year_month (YYYY-MM)
        $request->validate([
            'year_month' => 'required|string',
            'received_by' => ['nullable', Rule::in(array_keys(Payment::RECEIVED_BY_OPTIONS))],
        ]);

        $inputValue = $request->year_month;

        // If full date given (YYYY-MM-DD), extract YYYY-MM
        if (strlen($inputValue) === 10) {
            $yearMonth = substr($inputValue, 0, 7);
            $paidAt = $inputValue;
        } else {
            $yearMonth = $inputValue;
            $paidAt = $inputValue . '-01'; // default to 1st of month
        }

        $amount = $request->filled('amount') ? (float) $request->amount : null;

        $payment = $this->markMonthPaid($student, $yearMonth, $paidAt, $amount, $request->input('received_by'));

        if (!$payment) {
            return redirect()->back()->with('error', 'Payment for ' . $yearMonth . ' is already marked as paid!');
        }

        // Trigger receipt generation and notifications
        $preflightWarning = null;
        try {
            $receiptService = app(\App\Services\PaymentReceiptService::class);
            $receiptService->ensureReceipt($payment);
            $preflightWarning = $receiptService->preflightCheck($payment);
            $receiptService->sendReceiptNotifications($payment);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Receipt trigger failed in markPaid: ' . $e->getMessage());
            $preflightWarning = $e->getMessage();
        }

        // If student was deactivated, automatically reactivate them now that payment is made
        $successMsg = $student->deactivated_at
            ? 'Payment marked for ' . $yearMonth . ' and student account reactivated.'
            : 'Payment marked for ' . $yearMonth . '.';

        if ($student->deactivated_at) {
            $student->update(['deactivated_at' => null]);
        }

        $response = redirect()->back()->with('success', $successMsg);
        if ($preflightWarning) {
            $response->with('warning', "Payment saved, but the receipt could not be sent: {$preflightWarning}. Use Resend on the student's page.");
        }
        return $response;
    }

    /**
     * Mark a month as paid for a student, updating any existing pending row or creating a new row.
     * Prevents duplicate rows using transactions and row locking.
     */
    private function markMonthPaid(User $student, string $yearMonth, string $paidAt, ?float $amount = null, ?string $receivedBy = null): ?Payment
    {
        return DB::transaction(function () use ($student, $yearMonth, $paidAt, $amount, $receivedBy) {
            $existingPayments = Payment::monthly()
                ->where('user_id', $student->id)
                ->where('year_month', $yearMonth)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->get();

            if ($existingPayments->where('status', 'paid')->isNotEmpty()) {
                return null;
            }

            // Auto-supersede any pending top-ups from past months
            \App\Models\SubjectTopup::where('user_id', $student->id)
                ->where('year_month', '<', $yearMonth)
                ->where('status', 'pending')
                ->update(['status' => 'superseded']);

            \App\Models\Payment::where('user_id', $student->id)
                ->where('kind', 'topup')
                ->where('year_month', '<', $yearMonth)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            $pendingPayments = $existingPayments->where('status', 'pending');

            if ($pendingPayments->isNotEmpty()) {
                $newestPending = $pendingPayments->first();
                $otherPending = $pendingPayments->slice(1);

                if ($otherPending->isNotEmpty()) {
                    Payment::whereIn('id', $otherPending->pluck('id'))->update(['status' => 'cancelled']);
                }

                $finalAmount = $amount !== null
                    ? $amount
                    : (($newestPending->amount > 0) ? (float) $newestPending->amount : (float) $this->calculateUserAmount($student));

                $updateData = [
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                    'amount' => $finalAmount,
                    'kind' => $newestPending->kind ?: 'monthly',
                ];

                // A pending PayHere checkout the gateway never confirmed becomes OFFLINE money
                $updateData = array_merge(
                    $updateData,
                    $newestPending->adminPaidAttributes($receivedBy, auth()->id())
                );

                $newestPending->update($updateData);

                return $newestPending;
            }

            $finalAmount = $amount !== null ? $amount : (float) $this->calculateUserAmount($student);

            $meta = ['marked_paid_at' => now()->toDateTimeString()];
            if (auth()->id()) {
                $meta['marked_paid_by'] = auth()->id();
            }
            if ($receivedBy) {
                $meta['received_by'] = array_key_exists($receivedBy, Payment::RECEIVED_BY_OPTIONS) ? $receivedBy : 'other';
            }
            $admFee = $student->getAdmissionFeeAmount();
            if ($admFee > 0) {
                $meta['admission_fee'] = $admFee;
            }

            return Payment::create([
                'user_id' => $student->id,
                'year_month' => $yearMonth,
                'amount' => $finalAmount,
                'status' => 'paid',
                'paid_at' => $paidAt,
                'kind' => 'monthly',
                'payment_method' => 'offline',
                'meta' => $meta,
                'institute_id' => $student->institute_id ?? 1,
            ]);
        });
    }

    /**
     * Resolve student IDs from request or matching filters if select_all_matching is true.
     *
     * @param Request $request
     * @return array<int>
     */
    private function resolveStudentIds(Request $request): array
    {
        if ($request->boolean('select_all_matching')) {
            $query = User::where('role', 'user')->whereNotNull('full_name');
            $instituteId = auth()->check() ? auth()->user()->institute_id : null;
            if ($instituteId) {
                $query->where('institute_id', $instituteId);
            }
            if ($request->has('needs_review') && $request->needs_review == '1') {
                $query->where('needs_subject_review', true);
            }
            if ($request->filled('grade')) {
                $query->where(function ($q) use ($request) {
                    $q->where('current_grade', $request->grade)
                      ->orWhere('current_grade', 'Grade ' . $request->grade);
                });
            }
            if ($request->filled('medium') && $request->medium !== 'all') {
                $medium = strtolower(trim($request->medium));
                if (in_array($medium, ['english', 'tamil', 'both'], true)) {
                    $query->where('medium', $medium);
                }
            }
            $inst = auth()->check() ? auth()->user()->institute : null;
            $format = $inst?->getIndexFormat() ?? 'grade';
            if ($format === 'batch' && $request->filled('batch_year')) {
                $query->where('batch_year', (int) $request->batch_year);
            }
            if ($request->has('has_index') && $request->has_index !== '') {
                $val = (string) $request->has_index;
                if ($val === '1' || $val === 'assigned') {
                    $query->whereNotNull('index_number');
                } elseif ($val === '0' || $val === 'unassigned') {
                    $query->whereNull('index_number');
                }
            }
            if ($request->filled('search')) {
                $term = trim($request->search);
                $query->where(function ($q) use ($term) {
                    $q->where('full_name', 'like', "%{$term}%")
                      ->orWhere('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%")
                      ->orWhere('phone_number', 'like', "%{$term}%")
                      ->orWhere('index_number', 'like', "%{$term}%");
                });
            }
            return $query->pluck('id')->all();
        }

        $rawIds = $request->input('ids');
        if (is_array($rawIds)) {
            return array_values(array_map('intval', array_filter($rawIds)));
        }
        if (is_string($rawIds)) {
            return array_values(array_map('intval', array_filter(array_map('trim', explode(',', $rawIds)))));
        }
        return [];
    }

    public function bulkDelete(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $ids = $this->resolveStudentIds($request);
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No students selected for deletion.');
        }

        User::where('role', 'user')->whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', count($ids) . ' students deleted successfully.');
    }

    /**
     * Helper to get subject category based on user's grade and stream
     */
    private function getSubjectCategoryForUser(User $user)
    {
        return $user->getSubjectCategory();
    }

    /**
     * Calculate the amount the user needs to pay based on selected subjects.
     */
    private function calculateUserAmount(User $user, ?float $fallbackAmount = null): float
    {
        return $user->calculateMonthlyFee($fallbackAmount);
    }

    public function promote(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $studentIds = array_unique($this->resolveStudentIds($request));
        if (empty($studentIds)) {
            return redirect()->back()->with('error', 'No students selected for promotion.');
        }
        $force = $request->boolean('force');

        try {
            $summary = $this->promotionService->promoteStudents(
                $studentIds, 
                $force, 
                auth()->id(), 
                $request->ip(), 
                $request->userAgent()
            );

            $msg = "Promotion complete. {$summary['promoted']} promoted.";
            if ($summary['flagged'] > 0) $msg .= " {$summary['flagged']} need subject review.";
            if ($summary['graduated'] > 0) $msg .= " {$summary['graduated']} marked as graduated.";
            if ($summary['skipped'] > 0) $msg .= " {$summary['skipped']} skipped (already promoted recently).";

            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred during promotion: ' . $e->getMessage());
        }
    }

    public function pendingMediumChanges()
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'], true)) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $user = auth()->user();
        $isSuperAdmin = $user && $user->role === 'super_admin';

        $totalPending = \App\Models\MediumChangeRequest::withoutGlobalScopes()->where('status', 'pending')->count();

        $query = \App\Models\MediumChangeRequest::where('status', 'pending');
        if (!$isSuperAdmin) {
            $instituteId = $user ? $user->institute_id : null;
            $query->whereHas('user', function ($q) use ($instituteId) {
                if ($instituteId) {
                    $q->where('users.institute_id', $instituteId);
                }
            });
        }

        $visibleCount = (clone $query)->count();
        $hiddenCount = max(0, $totalPending - $visibleCount);

        $requests = $query->with(['user.parent'])->latest()->paginate(15);
        return view('admin.students.medium-changes', compact('requests', 'hiddenCount'));
    }

    public function approveMediumChange($id)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'], true)) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $isSuperAdmin = auth()->user()->role === 'super_admin';
        $query = \App\Models\MediumChangeRequest::query();
        if (!$isSuperAdmin) {
            $instituteId = auth()->user()->institute_id;
            $query->whereHas('user', function ($q) use ($instituteId) {
                if ($instituteId) {
                    $q->where('users.institute_id', $instituteId);
                }
            });
        }

        $request = $query->findOrFail($id);

        if (!$request->user) {
            return redirect()->back()->with('error', 'Student not found – request not approved');
        }

        $request->status = 'approved';
        $request->save();

        $request->user->medium = $request->requested_medium;
        $request->user->save();

        // Notify user
        $this->notifier->notifyUser(
            $request->user, 'general_update', 'tit_general_update',
            [($request->user->full_name ?? $request->user->name) ?: 'Student', 'admin'],
            ['update_details' => 'Your request to change medium to ' . $request->requested_medium . ' has been approved.']
        );

        return redirect()->back()->with('success', 'Medium change approved.');
    }

    public function rejectMediumChange($id)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'], true)) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $isSuperAdmin = auth()->user()->role === 'super_admin';
        $query = \App\Models\MediumChangeRequest::query();
        if (!$isSuperAdmin) {
            $instituteId = auth()->user()->institute_id;
            $query->whereHas('user', function ($q) use ($instituteId) {
                if ($instituteId) {
                    $q->where('users.institute_id', $instituteId);
                }
            });
        }

        $request = $query->findOrFail($id);
        $request->status = 'rejected';
        $request->save();

        if ($request->user) {
            // Notify user
            $this->notifier->notifyUser(
                $request->user, 'general_update', 'tit_general_update',
                [($request->user->full_name ?? $request->user->name) ?: 'Student', 'admin'],
                ['update_details' => 'Your request to change medium to ' . $request->requested_medium . ' has been rejected.']
            );
        }

        return redirect()->back()->with('success', 'Medium change rejected.');
    }

    public function pendingAdvancePayments()
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'], true)) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $user = auth()->user();
        $isSuperAdmin = $user && $user->role === 'super_admin';

        $totalPending = \App\Models\AdvancePaymentRequest::withoutGlobalScopes()
            ->where('status', 'pending')
            ->count();

        $query = \App\Models\AdvancePaymentRequest::where('status', 'pending');
        if (!$isSuperAdmin) {
            $instituteId = $user ? $user->institute_id : null;
            $query->whereHas('user', function ($q) use ($instituteId) {
                if ($instituteId) {
                    $q->where('users.institute_id', $instituteId);
                }
            });
        }

        $visibleCount = (clone $query)->count();
        $hiddenCount = max(0, $totalPending - $visibleCount);

        $requests = $query->with(['user.parent'])->latest()->paginate(15);
        return view('admin.students.advance-payments', compact('requests', 'hiddenCount'));
    }

    public function approveAdvancePayment(Request $httpRequest, $id)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'], true)) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $isSuperAdmin = auth()->user()->role === 'super_admin';
        $query = \App\Models\AdvancePaymentRequest::query();
        if (!$isSuperAdmin) {
            $instituteId = auth()->user()->institute_id;
            $query->whereHas('user', function ($q) use ($instituteId) {
                if ($instituteId) {
                    $q->where('users.institute_id', $instituteId);
                }
            });
        }

        $request = $query->findOrFail($id);
        
        if ($request->status !== 'pending') {
            return redirect()->back()->with('error', 'Request is already processed.');
        }

        if (!$request->user) {
            return redirect()->back()->with('error', 'Student not found – request not approved');
        }

        $monthsCount = (int) ($request->months_count ?? 1);
        $amount = (float) ($request->amount ?? 0);
        $monthlyAmount = $monthsCount > 0 ? ($amount / $monthsCount) : $amount;

        $approvedPayments = [];
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $httpRequest, $monthsCount, $monthlyAmount, &$approvedPayments) {
            // Find the last paid month, or use current month if none
            $lastPaid = \App\Models\Payment::monthly()
                ->where('user_id', $request->user->id)
                ->where('status', 'paid')
                ->orderBy('year_month', 'desc')
                ->first();

            if ($lastPaid) {
                $startMonth = \Carbon\Carbon::parse($lastPaid->year_month . '-01')->addMonthNoOverflow();
            } else {
                $startMonth = now()->startOfMonth();
            }

            for ($i = 0; $i < $monthsCount; $i++) {
                $yearMonth = $startMonth->copy()->addMonthsNoOverflow($i)->format('Y-m');
                
                $advanceMeta = array_filter([
                    'advance_request_id' => $request->id,
                    'received_by' => in_array($httpRequest->input('received_by'), array_keys(Payment::RECEIVED_BY_OPTIONS), true)
                        ? $httpRequest->input('received_by') : null,
                    'marked_paid_by' => auth()->id(),
                    'marked_paid_at' => now()->toDateTimeString(),
                ], fn ($v) => $v !== null);

                $payment = \App\Models\Payment::updateOrCreate(
                    [
                        'user_id' => $request->user->id,
                        'year_month' => $yearMonth,
                        'kind' => 'advance',
                    ],
                    [
                        'amount' => $monthlyAmount,
                        'status' => 'paid',
                        'payment_method' => 'offline',
                        'paid_at' => now(),
                        'kind' => 'advance',
                        'institute_id' => $request->user->institute_id ?? 1,
                        'gateway_ref' => 'ADVANCE_APP_' . $request->id . '_' . $i,
                        'meta' => $advanceMeta,
                    ]
                );

                \App\Models\Payment::monthly()
                    ->where('user_id', $request->user->id)
                    ->where('year_month', $yearMonth)
                    ->where('status', 'pending')
                    ->where('id', '!=', $payment->id)
                    ->update(['status' => 'cancelled']);

                $approvedPayments[] = $payment;
            }

            // Status flips to approved only after the payments exist
            $request->status = 'approved';
            $request->save();

            // If student was deactivated, automatically reactivate them now that payment is approved
            if ($request->user && $request->user->deactivated_at) {
                $request->user->update(['deactivated_at' => null]);
            }
        });

        // Assign receipts to approved payments and send ONE receipt notification for the batch
        $preflightWarning = null;
        try {
            $receiptService = app(\App\Services\PaymentReceiptService::class);
            foreach ($approvedPayments as $p) {
                $receiptService->ensureReceipt($p);
            }
            if (!empty($approvedPayments)) {
                $firstPayment = $approvedPayments[0];
                if ($preflightWarning === null) {
                    $preflightWarning = $receiptService->preflightCheck($firstPayment);
                }
                $receiptService->sendReceiptNotifications($firstPayment);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Receipt trigger failed in approveAdvancePayment: ' . $e->getMessage());
            $preflightWarning = $e->getMessage();
        }

        // Notify user
        if ($request->user) {
            $this->notifier->notifyUser(
                $request->user, 'general_update', 'tit_general_update',
                [($request->user->full_name ?? $request->user->name) ?: 'Student', 'admin'],
                ['update_details' => 'Your advance payment request for ' . $monthsCount . ' months has been approved.']
            );
        }

        $response = redirect()->back()->with('success', 'Advance payment approved for ' . $monthsCount . ' months.');
        if ($preflightWarning) {
            $response->with('warning', "Payment saved, but the receipt could not be sent: {$preflightWarning}. Use Resend on the student's page.");
        }
        return $response;
    }

    public function rejectAdvancePayment($id)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'], true)) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $isSuperAdmin = auth()->user()->role === 'super_admin';
        $query = \App\Models\AdvancePaymentRequest::query();
        if (!$isSuperAdmin) {
            $instituteId = auth()->user()->institute_id;
            $query->whereHas('user', function ($q) use ($instituteId) {
                if ($instituteId) {
                    $q->where('users.institute_id', $instituteId);
                }
            });
        }

        $request = $query->findOrFail($id);
        $request->status = 'rejected';
        $request->save();

        if ($request->user) {
            // Notify user
            $this->notifier->notifyUser(
                $request->user, 'general_update', 'tit_general_update',
                [($request->user->full_name ?? $request->user->name) ?: 'Student', 'admin'],
                ['update_details' => 'Your advance payment request for ' . ($request->months_count ?? 1) . ' months has been rejected. Please contact the administrator.']
            );
        }

        return redirect()->back()->with('success', 'Advance payment rejected.');
    }

    /**
     * Assign student index number manually if not assigned yet.
     */
    public function assignIndexNumber(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        try {
            $student = User::where('role', 'user')->findOrFail($id);
            if (!empty($student->index_number)) {
                return back()->with('warning', "This student already has {$student->index_number} – use Re-assign to change it");
            }
            $institute = $student->institute ?: \App\Models\Institute::find($student->institute_id ?: 1);
            $format = $institute?->getIndexFormat() ?? 'grade';

            $exactSeq = $request->filled('running_number') ? (int) $request->input('running_number') : ($request->filled('index_seq') ? (int) $request->input('index_seq') : null);

            if ($format === 'grade') {
                $series = $request->filled('series') ? $request->input('series') : ($request->filled('batch_year') ? $request->input('batch_year') : null);
                $indexNumber = $this->indexService->assign($student, $series, $exactSeq);
            } else {
                $batchYear = $request->filled('batch_year')
                    ? $this->indexService->normalizeBatchYear($request->batch_year)
                    : ($request->filled('series') ? $this->indexService->normalizeBatchYear($request->series) : ($this->indexService->calculateDefaultBatchYear($student) ?? (int) date('y')));
                $indexNumber = $this->indexService->assign($student, $batchYear, $exactSeq);
            }

            return back()->with('success', "Assigned index number {$indexNumber} to " . ($student->full_name ?? $student->name) . ".");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Re-assign index number to a student (creates activity log).
     */
    public function reassignIndexNumber(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        try {
            $student = User::where('role', 'user')->findOrFail($id);
            $oldIndex = $student->index_number;
            $institute = $student->institute ?: \App\Models\Institute::find($student->institute_id ?: 1);
            $format = $institute?->getIndexFormat() ?? 'grade';

            $exactSeq = $request->filled('running_number') ? (int) $request->input('running_number') : ($request->filled('index_seq') ? (int) $request->input('index_seq') : null);

            if ($format === 'grade') {
                $newSeries = $request->filled('series') ? $request->input('series') : ($request->filled('batch_year') ? $request->input('batch_year') : null);
            } else {
                $newSeries = $request->filled('batch_year') ? $request->input('batch_year') : ($request->filled('series') ? $request->input('series') : null);
            }
            if (!$newSeries && $format !== 'grade') {
                throw new \InvalidArgumentException('Batch year is required');
            }

            $newIndex = $this->indexService->reassign($student, $newSeries, auth()->user(), $request->input('reason'), $exactSeq);

            return back()->with('success', "Reassigned index number from {$oldIndex} to {$newIndex}.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk assign index numbers or preview bulk assignments.
     */
    public function bulkAssignIndex(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Admin access required'], 403);
            }
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $studentIds = $this->resolveStudentIds($request);
        if (empty($studentIds)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No students selected.'], 422);
            }
            return back()->with('error', 'No students selected.');
        }

        $students = User::whereIn('id', $studentIds)
            ->where('role', 'user')
            ->orderBy('id', 'asc')
            ->get();

        if ($request->boolean('preview')) {
            $instituteId = (int) ($students->first()?->institute_id ?: (auth()->user()->institute_id ?: 1));
            $batchMode = $request->filled('batch_year') ? 'single' : 'mapping';
            $singleBatch = $request->filled('batch_year') ? $this->indexService->normalizeBatchYear($request->batch_year) : null;
            $mapping = $this->indexService->getGradeBatchMapping($instituteId);

            $bulkPreview = $this->indexService->previewBulkAssign($students, $instituteId, $batchMode, $singleBatch, $mapping);
            return response()->json([
                'preview' => $bulkPreview['preview_rows'],
                'grade_summaries' => $bulkPreview['grade_summaries'],
                'skipped' => $bulkPreview['skipped'],
            ]);
        }

        $instituteId = (int) ($students->first()?->institute_id ?: (auth()->user()->institute_id ?: 1));
        $firstInst = \App\Models\Institute::find($instituteId);
        $format = $firstInst?->getIndexFormat() ?? 'grade';

        $unassignedStudents = $students->whereNull('index_number');
        if ($unassignedStudents->isNotEmpty()) {
            $canDetermineAny = false;
            foreach ($unassignedStudents as $student) {
                if ($format === 'grade') {
                    if ($request->filled('series')) {
                        $canDetermineAny = true;
                        break;
                    }
                    try {
                        $this->indexService->deriveGradeSeries($student->current_grade);
                        $canDetermineAny = true;
                        break;
                    } catch (\Throwable $e) {
                        // cannot derive
                    }
                } else {
                    $batchYear = $request->filled('batch_year')
                        ? $this->indexService->normalizeBatchYear($request->batch_year)
                        : $this->indexService->calculateDefaultBatchYear($student);
                    if ($batchYear !== null) {
                        $canDetermineAny = true;
                        break;
                    }
                }
            }

            if (!$canDetermineAny) {
                $errKey = $format === 'grade' ? 'series' : 'batch_year';
                $errMsg = $format === 'grade'
                    ? 'No valid grade found for selected students. Set a grade or pass a series.'
                    : 'No batch year found for selected students. Set the grade mapping or pass a batch year.';
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            $errKey => [$errMsg]
                        ]
                    ], 422);
                }
                return back()->withErrors([$errKey => $errMsg]);
            }
        }

        $assignedCount = 0;
        foreach ($students as $student) {
            if (empty($student->index_number)) {
                $inst = $student->institute ?: \App\Models\Institute::find($student->institute_id ?: 1);
                $stFormat = $inst?->getIndexFormat() ?? 'grade';

                if ($stFormat === 'grade') {
                    $series = $request->filled('series') ? $request->input('series') : null;
                    try {
                        $this->indexService->assign($student, $series);
                        $assignedCount++;
                    } catch (\Throwable $e) {
                        \Log::warning("Bulk assign skipping student {$student->id}: " . $e->getMessage());
                    }
                } else {
                    $batchYear = $request->filled('batch_year')
                        ? $this->indexService->normalizeBatchYear($request->batch_year)
                        : $this->indexService->calculateDefaultBatchYear($student);

                    if ($batchYear === null) {
                        continue;
                    }

                    $this->indexService->assign($student, $batchYear);
                    $assignedCount++;
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'assigned_count' => $assignedCount]);
        }

        return back()->with('success', "Assigned index numbers to {$assignedCount} student(s).");
    }

    /**
     * Dedicated page for pending payments (monthly and subject top-ups).
     * GET /admin/students/pending-payments
     */
    public function pendingPayments(Request $request)
    {
        $type = $request->get('type', 'all'); // 'all', 'monthly', 'topup', 'advance'
        $grade = $request->get('grade');
        $search = trim((string) $request->get('search', ''));

        if ($type === 'advance') {
            return redirect()->route('admin.students.advance-payments');
        }

        // Auto-link any pending SubjectTopup that does not have a payment yet
        $orphanTopups = \App\Models\SubjectTopup::where('status', 'pending')->whereNull('payment_id')->get();
        foreach ($orphanTopups as $ot) {
            $p = Payment::firstOrCreate(
                [
                    'user_id' => $ot->user_id,
                    'year_month' => $ot->year_month,
                    'kind' => 'topup',
                    'status' => 'pending',
                ],
                [
                    'institute_id' => $ot->institute_id,
                    'amount' => $ot->amount,
                    'payment_method' => $ot->payment_method ?: 'offline',
                    'meta' => [
                        'topup_id' => $ot->id,
                        'added_subjects' => $ot->added_subjects,
                    ],
                ]
            );
            $ot->update(['payment_id' => $p->id]);
        }

        $query = Payment::with(['user', 'user.parent', 'subjectTopup'])
            ->where('status', 'pending');

        if ($type === 'monthly') {
            $query->where(function ($q) {
                $q->whereNull('kind')->orWhereIn('kind', ['monthly', 'advance']);
            });
        } elseif ($type === 'topup') {
            $query->where('kind', 'topup');
        }

        if ($grade) {
            $query->whereHas('user', function ($q) use ($grade) {
                $q->where('current_grade', $grade)
                  ->orWhere('current_grade', 'Grade ' . $grade);
            });
        }

        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('index_number', 'like', "%{$search}%");
            });
        }

        $payments = $query->latest('id')->paginate(15)->appends($request->query());

        // Pending counts
        $totalPendingCount = Payment::where('status', 'pending')->count();
        $monthlyPendingCount = Payment::where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('kind')->orWhereIn('kind', ['monthly', 'advance']);
            })->count();
        $topupPendingCount = Payment::where('status', 'pending')->where('kind', 'topup')->count();
        $advanceRequestsCount = \App\Models\AdvancePaymentRequest::where('status', 'pending')->count();

        return view('admin.students.pending-payments', compact(
            'payments',
            'type',
            'grade',
            'search',
            'totalPendingCount',
            'monthlyPendingCount',
            'topupPendingCount',
            'advanceRequestsCount'
        ));
    }

    /**
     * Cancel a pending payment.
     * POST /admin/students/pending-payments/{payment}/cancel
     */
    public function cancelPendingPayment(Request $request, $paymentId)
    {
        $payment = Payment::where('id', $paymentId)
            ->where('status', 'pending')
            ->firstOrFail();

        \DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'cancelled',
            ]);

            if ($payment->kind === 'topup') {
                $topup = \App\Models\SubjectTopup::where('payment_id', $payment->id)->first()
                    ?? (!empty($payment->meta['topup_id']) ? \App\Models\SubjectTopup::find($payment->meta['topup_id']) : null);
                if ($topup && $topup->status === 'pending') {
                    $topup->update(['status' => 'cancelled']);
                    if ($topup->user) {
                        $current = $topup->user->selected_subjects ?: [];
                        if (is_string($current)) {
                            $current = json_decode($current, true) ?: [];
                        }
                        $revert = $topup->previous_subjects ?: array_values(array_diff($current, $topup->added_subjects ?: []));
                        $topup->user->selected_subjects = json_encode(array_values($revert));
                        $topup->user->save();
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'Pending payment #' . $payment->id . ' for ' . $payment->year_month . ' has been cancelled.');
    }
}

