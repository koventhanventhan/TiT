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

        if ($request->filled('grade')) {
            $query->where(function ($q) use ($request) {
                $q->where('current_grade', $request->grade)
                  ->orWhere('current_grade', 'Grade ' . $request->grade);
            });
        }

        if ($request->filled('batch_year')) {
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
            
        $students = $query->latest()->paginate(15)->appends($request->query());
        
        $instituteId = auth()->check() ? auth()->user()->institute_id : null;
        $countQuery = User::where('role', 'user')->whereNotNull('full_name');
        if ($instituteId) {
            $countQuery->where('institute_id', $instituteId);
        }
        $totalStudentsCount = (clone $countQuery)->count();
        $assignedIndexCount = (clone $countQuery)->whereNotNull('index_number')->count();
        $gradeMapping = $this->indexService->getGradeBatchMapping($instituteId);

        return view('admin.students.index', compact('students', 'totalStudentsCount', 'assignedIndexCount', 'gradeMapping'));
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

        $batchYear = null;
        if ($request->filled('batch_year')) {
            try {
                $batchYear = $this->indexService->normalizeBatchYear($request->batch_year);
            } catch (\InvalidArgumentException $e) {
                // Invalid batch year
            }
        } else {
            $batchYear = $this->indexService->getBatchForGrade($student->current_grade, $student->institute_id);
        }

        $indexAssigned = false;
        if ($batchYear !== null) {
            $this->indexService->assign($student, $batchYear);
            $indexAssigned = true;
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

        if (!$indexAssigned) {
            $gradeDisplay = $student->current_grade ?: 'unknown';
            return redirect()->route('admin.students.index')
                ->with('warning', "No batch year for Grade {$gradeDisplay} – set it in Index Number Batches, the number was not assigned");
        }

        return redirect()->route('admin.students.index')
            ->with('success', 'Student confirmed and index number assigned.');
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
            $batchYear = $request->filled('batch_year')
                ? $this->indexService->normalizeBatchYear($request->batch_year)
                : $this->indexService->getBatchForGrade($user->current_grade, $user->institute_id);
            if ($batchYear !== null) {
                $this->indexService->assign($user, $batchYear);
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
        if ($request->boolean('quick_payment')) {
            $this->markMonthPaid($user, now()->format('Y-m'), now()->format('Y-m-d H:i:s'), null);
        }

        // Handle Custom Payment Date
        if (!empty($validated['custom_payment_month'])) {
            $customDate = $validated['custom_payment_month'];
            $yearMonth = substr($customDate, 0, 7); // Extract YYYY-MM from YYYY-MM-DD
            $this->markMonthPaid($user, $yearMonth, $customDate, null);
        }

        return redirect()->route('admin.students.index')->with('success', 'Student added successfully! Username: ' . $username . ' | Password: ' . $validated['password']);
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
        $request->validate(['year_month' => 'required|string']);

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

        $payment = $this->markMonthPaid($student, $yearMonth, $paidAt, $amount);

        if (!$payment) {
            return redirect()->back()->with('error', 'Payment for ' . $yearMonth . ' is already marked as paid!');
        }

        // Trigger receipt generation and notifications
        try {
            $receiptService = app(\App\Services\PaymentReceiptService::class);
            $receiptService->ensureReceipt($payment);
            $receiptService->sendReceiptNotifications($payment);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Receipt trigger failed in markPaid: ' . $e->getMessage());
        }

        // If student was deactivated, automatically reactivate them now that payment is made
        if ($student->deactivated_at) {
            $student->update(['deactivated_at' => null]);
            return redirect()->back()->with('success', 'Payment marked for ' . $yearMonth . ' and student account reactivated.');
        }

        return redirect()->back()->with('success', 'Payment marked for ' . $yearMonth . '.');
    }

    /**
     * Mark a month as paid for a student, updating any existing pending row or creating a new row.
     * Prevents duplicate rows using transactions and row locking.
     */
    private function markMonthPaid(User $student, string $yearMonth, string $paidAt, ?float $amount = null): ?Payment
    {
        return DB::transaction(function () use ($student, $yearMonth, $paidAt, $amount) {
            $existingPayments = Payment::where('user_id', $student->id)
                ->where('year_month', $yearMonth)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->get();

            if ($existingPayments->where('status', 'paid')->isNotEmpty()) {
                return null;
            }

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
                ];

                if (empty($newestPending->payment_method)) {
                    $updateData['payment_method'] = 'offline';
                }

                $newestPending->update($updateData);

                return $newestPending;
            }

            $finalAmount = $amount !== null ? $amount : (float) $this->calculateUserAmount($student);

            return Payment::create([
                'user_id' => $student->id,
                'year_month' => $yearMonth,
                'amount' => $finalAmount,
                'status' => 'paid',
                'paid_at' => $paidAt,
                'payment_method' => 'offline',
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
            if ($request->filled('batch_year')) {
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
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $requests = \App\Models\MediumChangeRequest::with('user')->where('status', 'pending')->latest()->paginate(15);
        return view('admin.students.medium-changes', compact('requests'));
    }

    public function approveMediumChange($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request = \App\Models\MediumChangeRequest::findOrFail($id);
        $request->status = 'approved';
        $request->save();

        if ($request->user) {
            $request->user->medium = $request->requested_medium;
            $request->user->save();

            // Notify user
            $this->notifier->notifyUser(
                $request->user, 'general_update', 'tit_general_update',
                [$request->user->full_name ?? $request->user->name, 'admin'],
                ['update_details' => 'Your request to change medium to ' . $request->requested_medium . ' has been approved.']
            );
        }

        return redirect()->back()->with('success', 'Medium change approved.');
    }

    public function rejectMediumChange($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request = \App\Models\MediumChangeRequest::findOrFail($id);
        $request->status = 'rejected';
        $request->save();

        if ($request->user) {
            // Notify user
            $this->notifier->notifyUser(
                $request->user, 'general_update', 'tit_general_update',
                [$request->user->full_name ?? $request->user->name, 'admin'],
                ['update_details' => 'Your request to change medium to ' . $request->requested_medium . ' has been rejected.']
            );
        }

        return redirect()->back()->with('success', 'Medium change rejected.');
    }

    public function pendingAdvancePayments()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $requests = \App\Models\AdvancePaymentRequest::with('user')->where('status', 'pending')->latest()->paginate(15);
        return view('admin.students.advance-payments', compact('requests'));
    }

    public function approveAdvancePayment($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request = \App\Models\AdvancePaymentRequest::findOrFail($id);
        
        if ($request->status !== 'pending') {
            return redirect()->back()->with('error', 'Request is already processed.');
        }

        $request->status = 'approved';
        $request->save();

        if ($request->user) {
            $monthsCount = $request->months_count;
            $amount = $request->amount;
            $monthlyAmount = $amount / $monthsCount;
            
            // Find the last paid month, or use current month if none
            $lastPaid = \App\Models\Payment::where('user_id', $request->user->id)
                ->where('status', 'paid')
                ->orderBy('year_month', 'desc')
                ->first();

            if ($lastPaid) {
                $startMonth = \Carbon\Carbon::createFromFormat('Y-m', $lastPaid->year_month)->addMonth();
            } else {
                $startMonth = now()->startOfMonth();
            }

            $approvedPayments = [];
            for ($i = 0; $i < $monthsCount; $i++) {
                $yearMonth = $startMonth->copy()->addMonths($i)->format('Y-m');
                
                $payment = \App\Models\Payment::updateOrCreate(
                    [
                        'user_id' => $request->user->id,
                        'year_month' => $yearMonth,
                    ],
                    [
                        'amount' => $monthlyAmount,
                        'status' => 'paid',
                        'payment_method' => 'offline',
                        'paid_at' => now(),
                        'institute_id' => $request->user->institute_id ?? 1,
                        'gateway_ref' => 'ADVANCE_APP_' . $request->id . '_' . $i
                    ]
                );

                \App\Models\Payment::where('user_id', $request->user->id)
                    ->where('year_month', $yearMonth)
                    ->where('status', 'pending')
                    ->where('id', '!=', $payment->id)
                    ->update(['status' => 'cancelled']);

                $approvedPayments[] = $payment;
            }

            // Assign receipts to approved payments and send receipt notification
            try {
                $receiptService = app(\App\Services\PaymentReceiptService::class);
                foreach ($approvedPayments as $p) {
                    $receiptService->ensureReceipt($p);
                }
                if (!empty($approvedPayments[0])) {
                    $receiptService->sendReceiptNotifications($approvedPayments[0]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Receipt trigger failed in approveAdvancePayment: ' . $e->getMessage());
            }

            // If student was deactivated, automatically reactivate them now that payment is approved
            if ($request->user && $request->user->deactivated_at) {
                $request->user->update(['deactivated_at' => null]);
            }

            // Notify user
            $this->notifier->notifyUser(
                $request->user, 'general_update', 'tit_general_update',
                [$request->user->full_name ?? $request->user->name, 'admin'],
                ['update_details' => 'Your advance payment request for ' . $monthsCount . ' months has been approved.']
            );
        }

        return redirect()->back()->with('success', 'Advance payment approved for ' . $monthsCount . ' months.');
    }

    public function rejectAdvancePayment($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request = \App\Models\AdvancePaymentRequest::findOrFail($id);
        $request->status = 'rejected';
        $request->save();

        if ($request->user) {
            // Notify user
            $this->notifier->notifyUser(
                $request->user, 'general_update', 'tit_general_update',
                [$request->user->full_name ?? $request->user->name, 'admin'],
                ['update_details' => 'Your advance payment request for ' . $request->months_count . ' months has been rejected. Please contact the administrator.']
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

        $student = User::where('role', 'user')->findOrFail($id);

        $batchYear = $request->filled('batch_year')
            ? $this->indexService->normalizeBatchYear($request->batch_year)
            : ($this->indexService->calculateDefaultBatchYear($student) ?? (int) date('y'));

        $indexNumber = $this->indexService->assign($student, $batchYear);

        return back()->with('success', "Assigned index number {$indexNumber} to " . ($student->full_name ?? $student->name) . ".");
    }

    /**
     * Re-assign index number to a student (creates activity log).
     */
    public function reassignIndexNumber(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request->validate([
            'batch_year' => 'required|integer',
            'reason' => 'nullable|string|max:255',
        ]);

        $student = User::where('role', 'user')->findOrFail($id);
        $oldIndex = $student->index_number;
        $newIndex = $this->indexService->reassign($student, $request->batch_year, auth()->user(), $request->input('reason'));

        return back()->with('success', "Reassigned index number from {$oldIndex} to {$newIndex}.");
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

        $unassignedStudents = $students->whereNull('index_number');
        if ($unassignedStudents->isNotEmpty()) {
            $canDetermineAny = false;
            foreach ($unassignedStudents as $student) {
                $batchYear = $request->filled('batch_year')
                    ? $this->indexService->normalizeBatchYear($request->batch_year)
                    : $this->indexService->calculateDefaultBatchYear($student);
                if ($batchYear !== null) {
                    $canDetermineAny = true;
                    break;
                }
            }

            if (!$canDetermineAny) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'batch_year' => ['No batch year found for selected students. Set the grade mapping or pass a batch year.']
                        ]
                    ], 422);
                }
                return back()->withErrors(['batch_year' => 'No batch year found for selected students. Set the grade mapping or pass a batch year.']);
            }
        }

        $assignedCount = 0;
        foreach ($students as $student) {
            if (empty($student->index_number)) {
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

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'assigned_count' => $assignedCount]);
        }

        return back()->with('success', "Assigned index numbers to {$assignedCount} student(s).");
    }
}

