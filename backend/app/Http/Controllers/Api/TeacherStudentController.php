<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;

class TeacherStudentController extends Controller
{
    public function index(Request $request)
    {
        $students = User::where('role', 'user')
            ->whereIn('registration_status', ['approved', 'payment_completed'])
            ->select('id', 'name', 'email', 'phone_number', 'current_grade', 'stream', 'registration_status as status')
            ->get();
        return response()->json($students);
    }

    public function show(User $student)
    {
        if ($student->role !== 'student') {
            abort(404);
        }

        $attendance = Attendance::where('user_id', $student->id)
            ->with('zoomSchedule')
            ->orderBy('marked_at', 'desc')
            ->get();

        return response()->json([
            'profile' => $student,
            'attendance' => $attendance
        ]);
    }
}
