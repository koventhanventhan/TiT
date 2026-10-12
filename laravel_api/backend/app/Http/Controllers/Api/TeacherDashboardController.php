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

        // Today's classes that are either upcoming or started in the last 2 hours
        $upcomingClasses = ZoomSchedule::whereDate('scheduled_at', $today)
            ->where('scheduled_at', '>=', now()->subHours(2))
            ->where(function ($q) use ($user) {
                $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id))
                    ->orWhereDoesntHave('teachers');
            })->count();

        // Classes from start to end of the current week for this teacher
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $weekClasses = ZoomSchedule::whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])
            ->where(function ($q) use ($user) {
                $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id))
                    ->orWhereDoesntHave('teachers');
            })->count();

        // Total approved students only: role='user' and registration_status IN ('approved','payment_completed')
        $totalStudents = User::where('role', 'user')
            ->whereIn('registration_status', ['approved', 'payment_completed'])
            ->count();

        // Pending ungraded submissions on this teacher's assignments
        $newSubmissions = AssignmentSubmission::whereHas('assignment', function($q) use ($user) {
            $q->where('teacher_id', $user->id);
        })->where('status', 'pending')->count();

        // Total assignments and total submissions for this teacher
        $totalAssignments = Assignment::where('teacher_id', $user->id)->count();
        $totalSubmissions = AssignmentSubmission::whereHas('assignment', function($q) use ($user) {
            $q->where('teacher_id', $user->id);
        })->count();

        // Latest 5 pending submissions: {id, assignment_id, assignment_title, student_name, submitted_at}
        $recentSubmissions = AssignmentSubmission::whereHas('assignment', function($q) use ($user) {
            $q->where('teacher_id', $user->id);
        })
        ->where('status', 'pending')
        ->with(['assignment', 'student'])
        ->orderByDesc('submitted_at')
        ->orderByDesc('id')
        ->limit(5)
        ->get()
        ->map(function ($s) {
            return [
                'id' => $s->id,
                'assignment_id' => $s->assignment_id,
                'assignment_title' => $s->assignment?->title ?? '',
                'student_name' => $s->student?->name ?? 'Unknown Student',
                'submitted_at' => $s->submitted_at ? $s->submitted_at->toIso8601String() : $s->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'today_classes' => $todayClasses,
            'upcoming_classes' => $upcomingClasses,
            'week_classes' => $weekClasses,
            'total_students' => $totalStudents,
            'new_submissions' => $newSubmissions,
            'pending_assignments' => $newSubmissions,
            'total_assignments' => $totalAssignments,
            'total_submissions' => $totalSubmissions,
            'recent_submissions' => $recentSubmissions,
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
        
        // Only show today's schedules (upcoming or started in the last 2 hours)
        $schedules = ZoomSchedule::whereDate('scheduled_at', now())
            ->where('scheduled_at', '>=', now()->subHours(2))
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
