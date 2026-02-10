<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    protected WhatsAppService $whatsApp;

    public function __construct(WhatsAppService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
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
        
        return view('admin.students.show', compact('student'));
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
        
        return view('admin.students.edit', compact('student'));
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
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'school_name' => 'required|string|max:255',
            'medium' => 'required|in:english,tamil',
            'online_experience' => 'required|boolean',
            'device_used' => 'required|string|max:255',
            'current_grade' => 'required|string|max:50',
            'stream' => 'nullable|string|max:50|in:arts,bio_maths',
            'selected_subjects' => 'nullable|string',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($student->id)],
        ]);
        
        // Update student data
        $student->update([
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'] ?? null,
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'school_name' => $validated['school_name'],
            'medium' => $validated['medium'],
            'online_experience' => $validated['online_experience'],
            'device_used' => $validated['device_used'],
            'current_grade' => $validated['current_grade'],
            'stream' => $validated['stream'] ?? null,
            'selected_subjects' => $validated['selected_subjects'] ?? null,
            'email' => $validated['email'],
        ]);
        
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

        $phone = $student->phone_number;
        if ($phone) {
            $message = "Dear " . ($student->full_name ?? $student->name) . ",\n\n" .
                "Your registration for " . config('app.name') . " has been confirmed by the admin!\n" .
                "You can now log in to your dashboard to access your classes and materials.\n\n" .
                "Welcome to our learning family! 🎓";
            $this->whatsApp->send($phone, $message);
        }

        return redirect()->route('admin.students.index')
            ->with('success', 'Student confirmed and WhatsApp notification sent.');
    }

    public function create()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        return view('admin.students.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'full_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'school_name' => 'required|string|max:255',
            'medium' => 'required|in:english,tamil',
            'online_experience' => 'required|boolean',
            'device_used' => 'required|string|max:255',
            'current_grade' => 'required|string|max:50',
            'stream' => 'nullable|string|max:50|in:arts,bio_maths',
            'selected_subjects' => 'nullable|string',
            'registration_status' => 'nullable|string|in:pending_payment,paid_pending_confirm,confirmed',
            'admin_confirmed' => 'nullable|boolean',
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'full_name' => $request->full_name,
            'phone_number' => $request->phone_number,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'school_name' => $request->school_name,
            'medium' => $request->medium,
            'online_experience' => $request->boolean('online_experience'),
            'device_used' => $request->device_used,
            'current_grade' => $request->current_grade,
            'stream' => $request->stream,
            'selected_subjects' => $request->selected_subjects,
            'registration_status' => $request->registration_status ?? 'pending_payment',
            'admin_confirmed_at' => $request->boolean('admin_confirmed') ? now() : null,
        ]);
        return redirect()->route('admin.students.index')->with('success', 'Student added.');
    }

    public function deactivate($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $student = User::where('role', 'user')->where('id', $id)->whereNotNull('full_name')->firstOrFail();
        $student->update(['deactivated_at' => now()]);
        return redirect()->route('admin.students.index')->with('success', 'Student deactivated.');
    }

    public function markPaid(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $student = User::where('role', 'user')->where('id', $id)->whereNotNull('full_name')->firstOrFail();
        $request->validate(['year_month' => 'required|string|size:7']);
        Payment::create([
            'user_id' => $student->id,
            'year_month' => $request->year_month,
            'amount' => $request->amount ?? config('payment.monthly_amount', 500),
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Payment marked for ' . $request->year_month . '.');
    }
}

