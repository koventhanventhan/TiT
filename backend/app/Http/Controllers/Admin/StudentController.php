<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subject;
use App\Traits\ValidatesEmail;
use Illuminate\Support\Facades\Hash;
use App\Services\NotificationService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        // Include deactivated students and their parent user info
        $students = Student::with('user')->latest()->paginate(15);
        
        return view('admin.students.index', compact('students'));
    }

    /**
     * Display the specified student
     */
    public function show($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $student = Student::with('user')->findOrFail($id);

        $payments = Payment::where('user_id', $student->user_id)
            ->orderBy('paid_at', 'desc')
            ->get();
        
        return view('admin.students.edit', compact('student', 'payments'));
    }

    /**
     * Show the form for editing the specified student
     */
    public function edit($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $student = Student::with('user')->findOrFail($id);

        $payments = Payment::where('user_id', $student->user_id)
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
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $student = Student::with('user')->findOrFail($id);
        $user = $student->user;
        
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
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|digits_between:9,15|unique:users,phone_number,' . $user->id,
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'school_name' => 'required|string|max:255',
            'medium' => 'required|in:english,tamil',
            'online_experience' => 'nullable|boolean',
            'device_used' => 'required|string|max:255',
            'current_grade' => 'required|string|max:100',
            'stream' => 'nullable|string|max:50|in:arts,bio_maths',
            'selected_subjects' => 'nullable|array',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'custom_fields' => 'nullable|array',
        ]);
        
        // Update student data
        $subjects = null;
        if (isset($validated['selected_subjects']) && is_array($validated['selected_subjects'])) {
            $subjects = json_encode(array_values(array_unique($validated['selected_subjects'])), JSON_UNESCAPED_UNICODE);
        }

        $student->update([
            'full_name' => $validated['full_name'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'school_name' => $validated['school_name'],
            'medium' => $validated['medium'],
            'current_grade' => $validated['current_grade'],
            'stream' => $validated['stream'] ?? null,
            'selected_subjects' => $subjects,
            'custom_fields' => $request->custom_fields,
        ]);

        $userData = [
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'online_experience' => $request->has('online_experience') ? (bool) $validated['online_experience'] : $user->online_experience,
            'device_used' => $validated['device_used'],
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);
        
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

        $student = Student::with('user')->findOrFail($id);
        $user = $student->user;

        $user->update([
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
        ]);

        // Notify student (WhatsApp with email fallback)
        $this->notifier->notifyUser(
            $user, 'admin_approved', 'tit_admin_approved',
            [$student->full_name ?? $user->name, 'admin'],
            ['student_name' => $student->full_name ?? $user->name]
        );

        // Notify Admin
        $this->notifier->notifyAdmin(
            'admin_alert', 'tit_admin_approved',
            ["ADMIN ALERT: Approved student " . ($student->full_name ?? $user->name), "admin"],
            ['alert_title' => 'Student Approved', 'alert_message' => 'Approved student: ' . ($student->full_name ?? $user->name)]
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
            'phone_number' => 'required|digits_between:9,15',
            'email' => 'required|email',
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

        $subjects = null;
        if (isset($validated['selected_subjects']) && is_array($validated['selected_subjects'])) {
            $subjects = json_encode(array_values(array_unique($validated['selected_subjects'])), JSON_UNESCAPED_UNICODE);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            $emailPrefix = explode('@', $validated['email'])[0];
            $username = $emailPrefix;
            $counter = 1;
            while (User::where('name', $username)->exists()) {
                $username = $emailPrefix . $counter;
                $counter++;
            }

            $user = User::create([
                'name' => $username,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'user',
                'phone_number' => $validated['phone_number'],
                'registration_status' => 'confirmed',
                'admin_confirmed_at' => now(),
                'online_experience' => $validated['online_experience'],
                'device_used' => $validated['device_used'],
            ]);
        }

        $student = Student::create([
            'user_id' => $user->id,
            'full_name' => $validated['full_name'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'school_name' => $validated['school_name'],
            'medium' => $validated['medium'],
            'current_grade' => $validated['current_grade'],
            'stream' => $validated['stream'] ?? null,
            'selected_subjects' => $subjects,
        ]);

        if ($request->quick_payment) {
            $month = $request->custom_payment_month 
                ? \Carbon\Carbon::parse($request->custom_payment_month)->format('Y-m') 
                : now()->format('Y-m');
            Payment::create([
                'user_id' => $user->id,
                'year_month' => $month,
                'amount' => $student->calculateMonthlyFee(),
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        return redirect()->route('admin.students.index')->with('success', 'Student added successfully.');
    }

    public function deactivate($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $student = Student::with('user')->findOrFail($id);
        $user = $student->user;
        
        // Check if user has other active students before deactivating parent account
        $otherActiveStudents = Student::where('user_id', $user->id)->where('id', '!=', $id)->count();
        if ($otherActiveStudents === 0) {
            $user->update(['deactivated_at' => now()]);
        } else {
            // Can't deactivate parent account if they have other active children, 
            // So we just return success but explain this in the message.
            return redirect()->route('admin.students.index')->with('success', 'Student is linked to a parent with other active children. Parent account remains active.');
        }

        return redirect()->route('admin.students.index')->with('success', 'Student (and parent account) deactivated.');
    }

    public function activate($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $student = Student::with('user')->findOrFail($id);
        $user = $student->user;

        if ($user->deactivated_at) {
            $user->update(['deactivated_at' => null]);
        }

        return redirect()->route('admin.students.index')->with('success', 'Student activated successfully.');
    }

    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $student = Student::findOrFail($id);
        $userId = $student->user_id;
        
        // Delete the specific student record
        $student->delete();

        // If no more children, we could delete the user, but maybe safer to leave it.
        // The user specifically requested: "ஒரு Parent-க்கு 2+ Children இருக்கும்போது, ஒரு Child-ஐ Delete பண்ணா, மற்ற Children-ஓட Parent Account Affect ஆகக் கூடாது."
        $remainingStudents = Student::where('user_id', $userId)->count();
        if ($remainingStudents === 0) {
            User::where('id', $userId)->delete();
            return redirect()->route('admin.students.index')->with('success', 'Student and Parent account deleted permanently.');
        }

        return redirect()->route('admin.students.index')->with('success', 'Student deleted successfully. Parent account kept active for remaining children.');
    }

    public function resetPasswordAndNotify($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Admin access required'], 403);
        }

        $student = Student::with('user')->findOrFail($id);
        $user = $student->user;

        $newPassword = Str::random(10);
        $user->update(['password' => Hash::make($newPassword)]);

        try {
            $this->notifier->notifyUser(
                $user, 'admin_password_reset', 'tit_password_reset',
                [$student->full_name, $newPassword, 'https://titjaffna.com/login'],
                ['student_name' => $student->full_name, 'new_password' => $newPassword]
            );
            return response()->json(['success' => true, 'message' => 'Password reset successfully. Notification sent.']);
        } catch (\Exception $e) {
            return response()->json(['success' => true, 'message' => 'Password reset to: ' . $newPassword . ' but notification failed: ' . $e->getMessage()]);
        }
    }

    public function markPaid(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $student = Student::with('user')->findOrFail($id);

        $request->validate([
            'year_month' => 'required|date_format:Y-m',
            'amount' => 'nullable|numeric|min:0'
        ]);

        $yearMonth = $request->year_month;
        $paidAt = now();

        $existingPayment = Payment::where('user_id', $student->user_id)
            ->where('year_month', $yearMonth)
            ->where('status', 'paid')
            ->first();

        if ($existingPayment) {
            return redirect()->back()->with('error', 'Payment for ' . $yearMonth . ' is already marked as paid!');
        }

        $amount = $request->amount ?? $student->calculateMonthlyFee();

        Payment::create([
            'user_id' => $student->user_id,
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
        
        foreach ($ids as $id) {
            $student = Student::find($id);
            if ($student) {
                $userId = $student->user_id;
                $student->delete();
                
                $remainingStudents = Student::where('user_id', $userId)->count();
                if ($remainingStudents === 0) {
                    User::where('id', $userId)->delete();
                }
            }
        }

        return redirect()->back()->with('success', count($ids) . ' students deleted successfully.');
    }
}