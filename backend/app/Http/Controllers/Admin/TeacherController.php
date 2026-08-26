<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ValidatesEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    use ValidatesEmail;

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

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'teacher',
            'phone_number' => $request->phone_number,
            'teacher_unique_id' => $newId,
            'teacher_class' => $request->teacher_class,
        ];

        if ($request->filled('avatar_base64')) {
            $base64 = $request->avatar_base64;
            $imageParts = explode(";base64,", $base64);
            $imageTypeAux = explode("image/", $imageParts[0]);
            $imageType = $imageTypeAux[1];
            $imageBase64 = base64_decode($imageParts[1]);
            $fileName = 'teacher_' . time() . '.' . $imageType;
            $folderPath = public_path('uploads/avatars');
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }
            file_put_contents($folderPath . '/' . $fileName, $imageBase64);
            $data['avatar'] = 'uploads/avatars/' . $fileName;
        }

        User::create($data);

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

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        if ($request->filled('avatar_base64')) {
            $base64 = $request->avatar_base64;
            $imageParts = explode(";base64,", $base64);
            $imageTypeAux = explode("image/", $imageParts[0]);
            $imageType = $imageTypeAux[1];
            $imageBase64 = base64_decode($imageParts[1]);
            $fileName = 'teacher_' . time() . '.' . $imageType;
            $folderPath = public_path('uploads/avatars');
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }
            file_put_contents($folderPath . '/' . $fileName, $imageBase64);
            
            // Delete old avatar if exists
            if ($teacher->avatar && file_exists(public_path($teacher->avatar))) {
                @unlink(public_path($teacher->avatar));
            }
            
            $data['avatar'] = 'uploads/avatars/' . $fileName;
        }

        $teacher->update($data);
        return redirect()->route('admin.teachers.index')->with('success', 'Teacher updated.');
    }

    public function resetPasswordAndNotify($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Admin access required'], 403);
        }
        $teacher = User::where('role', 'teacher')->findOrFail($id);
        
        $newPassword = \Illuminate\Support\Str::random(10);
        $teacher->update([
            'password' => Hash::make($newPassword)
        ]);
        
        if ($this->isValidEmailForSending($teacher->email)) {
            \Illuminate\Support\Facades\Mail::to($teacher->email)->send(new \App\Mail\GoogleAutoPasswordMail($teacher, $newPassword, true));
            return response()->json(['success' => true, 'message' => 'New password generated and emailed to the teacher.']);
        } else {
            \Illuminate\Support\Facades\Log::warning('TeacherController: Skipped password reset email — invalid address: ' . $teacher->email);
            return response()->json(['success' => true, 'message' => 'New password generated, but email could not be sent (invalid email address). Please share the password manually.', 'password' => $newPassword]);
        }
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
