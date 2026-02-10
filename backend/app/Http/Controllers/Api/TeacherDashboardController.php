<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZoomSchedule;
use App\Models\Attendance;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'teacher') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $today = Carbon::today();
        
        // Classes today
        $todayClasses = ZoomSchedule::whereDate('scheduled_at', $today)
            ->where(function ($q) use ($user) {
                $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id))
                    ->orWhereDoesntHave('teachers');
            })->count();

        // Upcoming classes (next 7 days)
        $upcomingClasses = ZoomSchedule::where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->where(function ($q) use ($user) {
                $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id))
                    ->orWhereDoesntHave('teachers');
            })->count();

        // Total students assigned to this teacher's classes
        // Note: Students are not explicitly linked to schedules in the current schema beyond attendance.
        // For now, we count unique students who are in any class this teacher is assigned to (hardcoded logic or based on grade/subject match)
        // Simplification: Count all students for now if teacher is generic, or filtering can be added.
        $totalStudents = User::where('role', 'student')->count();

        // Pending assignments (not graded)
        $pendingAssignments = AssignmentSubmission::whereHas('assignment', function($q) use ($user) {
            $q->where('teacher_id', $user->id);
        })->where('status', 'pending')->count();

        return response()->json([
            'today_classes' => $todayClasses,
            'upcoming_classes' => $upcomingClasses,
            'total_students' => $totalStudents,
            'pending_assignments' => $pendingAssignments,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'role' => $user->role
            ]
        ]);
    }

    public function upcomingSchedules(Request $request)
    {
        $user = $request->user();
        
        $schedules = ZoomSchedule::where('scheduled_at', '>=', now())
            ->where(function ($q) use ($user) {
                $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id))
                    ->orWhereDoesntHave('teachers');
            })
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        return response()->json($schedules);
    }
}
