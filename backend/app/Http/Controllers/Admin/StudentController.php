<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subject;
use App\Notifications\AdminNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
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
            ->whereNotNull('full_name')
            ->firstOrFail();

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
            ->whereNotNull('full_name')
            ->firstOrFail();

        $payments = Payment::where('user_id', $student->id)
            ->orderBy('paid_at', 'desc')
            ->get();
        
        $subjects = Subject::orderBy('name')->get()->groupBy('category');
        
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
        
        // Validate the request
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|digits_between:10,15|unique:users,phone_number,' . $student->id,
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'school_name' => 'required|string|max:255',
            'medium' => 'required|in:english,tamil',
            'online_experience' => 'nullable|boolean',
            'device_used' => 'required|string|max:255',
            'current_grade' => 'required|string|max:100',
            'stream' => 'nullable|string|max:50|in:arts,bio_maths',
            'selected_subjects' => 'nullable|array',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($student->id)],
            'password' => 'nullable|string|min:8',
            'custom_fields' => 'nullable|array',
        ]);
        
        // Update student data
        $subjects = null;
        if (isset($validated['selected_subjects']) && is_array($validated['selected_subjects'])) {
            $subjects = json_encode(array_values(array_unique($validated['selected_subjects'])), JSON_UNESCAPED_UNICODE);
        }

        $updateData = [
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'school_name' => $validated['school_name'],
            'medium' => $validated['medium'],
            'online_experience' => $request->has('online_experience') ? (bool) $validated['online_experience'] : $student->online_experience,
            'device_used' => $validated['device_used'],
            'current_grade' => $validated['current_grade'],
            'stream' => $validated['stream'] ?? null,
            'selected_subjects' => $subjects,
            'email' => $validated['email'],
            'custom_fields' => $request->custom_fields,
        ];

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
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|digits_between:10,15|unique:users,phone_number',
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
            'registration_status' => 'pending_payment',
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
            Payment::create([
                'user_id' => $user->id,
                'year_month' => now()->format('Y-m'),
                'amount' => config('payment.monthly_amount', 500),
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        // Handle Custom Payment Date
        if (!empty($validated['custom_payment_month'])) {
            $customDate = $validated['custom_payment_month'];
            $yearMonth = substr($customDate, 0, 7); // Extract YYYY-MM from YYYY-MM-DD
            Payment::create([
                'user_id' => $user->id,
                'year_month' => $yearMonth,
                'amount' => config('payment.monthly_amount', 500),
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
        $student = User::where('role', 'user')->where('id', $id)->whereNotNull('full_name')->firstOrFail();
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
        $student = User::where('role', 'user')->where('id', $id)->whereNotNull('full_name')->firstOrFail();
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
        $student = User::where('role', 'user')->where('id', $id)->whereNotNull('full_name')->firstOrFail();
        
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
            \Illuminate\Support\Facades\Log::info('Initiating password reset email for ID: ' . $id . ' with Email: ' . $student->email);
            
            $mail = new \App\Mail\GoogleAutoPasswordMail($student, $newPassword, true);
            \Illuminate\Support\Facades\Log::info('Mail body preview', ['html_length' => strlen($mail->render())]);
            
            \Illuminate\Support\Facades\Mail::to($student->email)->send($mail);
            \Illuminate\Support\Facades\Log::info('Successfully sent password reset email to: ' . $student->email);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send password reset email to: ' . $student->email . '. Error: ' . $e->getMessage());
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

        Payment::create([
            'user_id' => $student->id,
            'year_month' => $yearMonth,
            'amount' => $request->amount ?? config('payment.monthly_amount', 500),
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
}

