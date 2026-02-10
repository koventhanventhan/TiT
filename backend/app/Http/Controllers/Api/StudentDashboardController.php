<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ZoomSchedule;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();
        
        // Classes today
        $todayClasses = ZoomSchedule::whereDate('scheduled_at', $today)->count();
        
        // Upcoming classes (next 7 days)
        $upcomingClasses = ZoomSchedule::where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->count();
            
        // Assignments
        $pendingAssignments = Assignment::whereDoesntHave('submissions', function($q) use ($user) {
            $q->where('student_id', $user->id);
        })->count();
        
        $completedAssignments = AssignmentSubmission::where('student_id', $user->id)
            ->where('status', 'graded')
            ->count();
            
        // Attendance
        $totalClasses = Attendance::where('user_id', $user->id)->count();
        $presentClasses = Attendance::where('user_id', $user->id)
            ->where('status', 'present')
            ->count();
            
        $attendanceRate = $totalClasses > 0 ? round(($presentClasses / $totalClasses) * 100) : 0;

        return response()->json([
            'today_classes' => $todayClasses,
            'upcoming_classes' => $upcomingClasses,
            'pending_assignments' => $pendingAssignments,
            'completed_assignments' => $completedAssignments,
            'attendance_rate' => $attendanceRate,
            'recent_announcements' => [
                ['id' => 1, 'title' => 'Term Exam Schedule', 'date' => now()->subDays(2), 'type' => 'important'],
                ['id' => 2, 'title' => 'New Science Material Uploaded', 'date' => now()->subDay(), 'type' => 'update'],
            ]
        ]);
    }
}
