<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\ActivityLog;
use App\Notifications\AdminNotification;
use App\Services\NotificationService;
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

    public function __construct(NotificationService $notifier)
    {
        $this->notifier = $notifier;
    }
    /**
     * Display a listing of all students
     */
    public function index()
    {
        // Check if user is admin
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        // Get all students (users with role 'user' and have student fields); include deactivated
        $students = User::where('role', 'user')
            ->whereNotNull('full_name')
            ->with('parent')
            ->latest()
            ->paginate(15);
        
        return view('admin.students.index', compact('students'));
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
            ->orderBy('paid_at', 'desc')
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
            ->orderBy('paid_at', 'desc')
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
        
        return view('admin.students.edit', compact('student', 'payments', 'subjects'));
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
    public function confirm($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $student = User::where('role', 'user')
            ->where('id', $id)
            ->whereNotNull('full_name')
            ->firstOrFail();

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

        return redirect()->route('admin.students.index')
            ->with('success', 'Student confirmed and notification sent.');
    }

    public function create()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $subjects = Subject::orderBy('name')->get()->groupBy('category');
        return view('admin.students.create', compact('subjects'));
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
            $amount = $this->calculateUserAmount($user);
            Payment::create([
                'user_id' => $user->id,
                'year_month' => now()->format('Y-m'),
                'amount' => $amount,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        // Handle Custom Payment Date
        if (!empty($validated['custom_payment_month'])) {
            $amount = $this->calculateUserAmount($user);
            $customDate = $validated['custom_payment_month'];
            $yearMonth = substr($customDate, 0, 7); // Extract YYYY-MM from YYYY-MM-DD
            Payment::create([
                'user_id' => $user->id,
                'year_month' => $yearMonth,
                'amount' => $amount,
                'status' => 'paid',
                'paid_at' => $customDate,
            ]);
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
        
        return redirect()->route('admin.students.index')->with('success', 'Student account reactivated successfully.');
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
            $this->markEmailAsBounced($targetEmail);
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

        // Prevent duplicate payments for the same month
        $existingPayment = Payment::where('user_id', $student->id)
            ->where('year_month', $yearMonth)
            ->where('status', 'paid')
            ->first();

        if ($existingPayment) {
            return redirect()->back()->with('error', 'Payment for ' . $yearMonth . ' is already marked as paid!');
        }

        $amount = $request->amount ?? $this->calculateUserAmount($student);

        Payment::create([
            'user_id' => $student->id,
            'year_month' => $yearMonth,
            'amount' => $amount,
            'status' => 'paid',
            'paid_at' => $paidAt,
        ]);
        return redirect()->back()->with('success', 'Payment marked for ' . $yearMonth . '.');
    }

    public function bulkDelete(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $request->validate([
            'ids' => 'required|string'
        ]);

        $ids = explode(',', $request->ids);
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

        $request->validate([
            'ids' => 'required|string',
            'force' => 'boolean', // To override the 6-month safeguard
        ]);

        $ids = explode(',', $request->ids);
        $studentIds = array_unique($ids);
        $force = $request->input('force', false);

        $students = User::whereIn('id', $studentIds)
            ->where('role', 'user')
            ->get();

        $promotedCount = 0;
        $flaggedCount = 0;
        $graduatedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                // Safeguard against double promotion
                if (!$force && $student->last_promoted_at && Carbon::parse($student->last_promoted_at)->diffInMonths(now()) < 6) {
                    $skippedCount++;
                    continue;
                }

                if ($student->is_graduated) {
                    $skippedCount++;
                    continue;
                }

                $numericGrade = $student->getNumericGrade();
                if ($numericGrade === null) {
                    $skippedCount++;
                    continue;
                }

                $oldGrade = $student->current_grade;

                if ($numericGrade >= 13) {
                    $student->is_graduated = true;
                    $student->last_promoted_at = now();
                    $student->save();
                    
                    ActivityLog::create([
                        'institute_id' => $student->institute_id,
                        'user_id' => auth()->id(),
                        'action' => 'student_graduated',
                        'description' => "Student marked as graduated from {$oldGrade}",
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'metadata' => ['student_id' => $student->id, 'old_grade' => $oldGrade],
                    ]);
                    $graduatedCount++;
                    continue;
                }

                // Promote logic
                $newGradeNum = $numericGrade + 1;
                // Preserve the string format, e.g. "7" -> "8"
                $newGradeStr = str_replace((string)$numericGrade, (string)$newGradeNum, $oldGrade);

                $student->current_grade = $newGradeStr;

                // Carry forward subjects
                $currentSubjects = $student->selected_subjects;
                if (is_string($currentSubjects)) {
                    $currentSubjects = json_decode($currentSubjects, true) ?? [];
                }
                if (!is_array($currentSubjects)) {
                    $currentSubjects = [];
                }

                $newCategory = $student->getSubjectCategory();
                $medium = strtolower($student->medium ?? '');

                // Fetch available subjects for new grade + medium
                $availableSubjects = Subject::when($newCategory, function ($query) use ($newCategory) {
                        return $query->where('category', $newCategory);
                    })
                    ->when($medium, function ($query) use ($medium) {
                        return $query->where(function ($q) use ($medium) {
                            $q->where('medium', $medium)->orWhere('medium', 'both');
                        });
                    })
                    ->pluck('name')
                    ->toArray();

                $carriedSubjects = [];
                $droppedSubjects = [];

                foreach ($currentSubjects as $subject) {
                    if (in_array($subject, $availableSubjects)) {
                        $carriedSubjects[] = $subject;
                    } else {
                        $droppedSubjects[] = $subject;
                    }
                }

                $student->selected_subjects = array_values(array_unique($carriedSubjects));
                $needsReview = false;

                if (count($droppedSubjects) > 0 || (count($currentSubjects) > 0 && empty($carriedSubjects))) {
                    $student->needs_subject_review = true;
                    $needsReview = true;
                    $flaggedCount++;

                    $recipient = $student->parent_id ? User::find($student->parent_id) : $student;
                    if ($recipient) {
                        try {
                            app(NotificationService::class)->notifyUser(
                                $recipient,
                                'subject_review_required',
                                'subject_review_required',
                                ['student_name' => $student->full_name, 'new_grade' => $newGradeStr],
                                [
                                    'subject' => 'Action Required: Update Subjects for New Academic Year',
                                    'body' => "{$student->full_name} has been promoted to {$newGradeStr}. Some previous subjects are not available in this grade. Please review and update the subjects."
                                ]
                            );
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Failed to send promotion notification: ' . $e->getMessage());
                        }
                    }
                }

                $student->last_promoted_at = now();
                $student->save();

                ActivityLog::create([
                    'institute_id' => $student->institute_id,
                    'user_id' => auth()->id(),
                    'action' => 'student_promoted',
                    'description' => "Promoted student from {$oldGrade} to {$newGradeStr}",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'student_id' => $student->id,
                        'old_grade' => $oldGrade,
                        'new_grade' => $newGradeStr,
                        'dropped_subjects' => $droppedSubjects,
                        'needs_review' => $needsReview
                    ],
                ]);

                $promotedCount++;
            }

            DB::commit();

            $msg = "Promotion complete. {$promotedCount} promoted.";
            if ($flaggedCount > 0) $msg .= " {$flaggedCount} need subject review.";
            if ($graduatedCount > 0) $msg .= " {$graduatedCount} marked as graduated.";
            if ($skippedCount > 0) $msg .= " {$skippedCount} skipped (already promoted recently).";

            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'An error occurred during promotion: ' . $e->getMessage());
        }
    }
}

