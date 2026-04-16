<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $teachers = User::where('role', 'teacher')->latest()->paginate(15);
        return view('admin.teachers.index', compact('teachers'));
    }

    public function create()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        return view('admin.teachers.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'required|digits_between:10,15|unique:users,phone_number',
            'teacher_class' => 'nullable|string|max:255',
        ]);

        // Auto-generate simple Teacher ID (1, 2, 3...)
        $lastTeacher = User::where('role', 'teacher')
            ->whereNotNull('teacher_unique_id')
            ->orderByRaw("CAST(teacher_unique_id AS UNSIGNED) DESC")
            ->first();

        $newId = ($lastTeacher && $lastTeacher->teacher_unique_id)
            ? (string)((int)$lastTeacher->teacher_unique_id + 1)
            : '1';

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'plain_password' => $request->password,
            'role' => 'teacher',
            'phone_number' => $request->phone_number,
            'teacher_unique_id' => $newId,
            'teacher_class' => $request->teacher_class,
        ]);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher added successfully! ID: ' . $newId . ' | Password: ' . $request->password);
    }

    public function edit($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $teacher = User::where('role', 'teacher')->findOrFail($id);
        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $teacher = User::where('role', 'teacher')->findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $teacher->id,
            'password' => 'nullable|string|min:8',
            'phone_number' => 'required|digits_between:10,15|unique:users,phone_number,' . $teacher->id,
            'teacher_class' => 'nullable|string|max:255',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'teacher_class' => $request->teacher_class,
        ];

        // Only update password if changed
        if ($request->filled('password') && $request->password !== $teacher->plain_password) {
            $data['password'] = Hash::make($request->password);
            $data['plain_password'] = $request->password;
        }

        $teacher->update($data);
        return redirect()->route('admin.teachers.index')->with('success', 'Teacher updated.');
    }

    public function deactivate($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $teacher = User::where('role', 'teacher')->findOrFail($id);
        $teacher->update(['deactivated_at' => now()]);
        return redirect()->route('admin.teachers.index')->with('success', 'Teacher deactivated.');
    }

    public function activate($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $teacher = User::where('role', 'teacher')->findOrFail($id);
        $teacher->update(['deactivated_at' => null]);
        return redirect()->route('admin.teachers.index')->with('success', 'Teacher activated.');
    }

    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $teacher = User::where('role', 'teacher')->findOrFail($id);
        $teacher->delete();
        return redirect()->route('admin.teachers.index')->with('success', 'Teacher deleted permanently.');
    }

    public function bulkDelete(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $request->validate(['ids' => 'required|string']);
        $ids = explode(',', $request->ids);
        User::where('role', 'teacher')->whereIn('id', $ids)->delete();
        return redirect()->back()->with('success', count($ids) . ' teachers deleted successfully.');
    }
}
