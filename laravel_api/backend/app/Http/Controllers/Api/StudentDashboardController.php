<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ZoomSchedule;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Announcement;
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();
        
        // Classes today (filtered by StudentClassVisibility)
        $todayClassesQuery = ZoomSchedule::whereDate('scheduled_at', $today);
        $todayClasses = \App\Support\StudentClassVisibility::forStudent($user, $todayClassesQuery)->count();
        
        // Upcoming classes (next 7 days, filtered by StudentClassVisibility)
        $upcomingQuery = ZoomSchedule::where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addDays(7));
        $upcomingClasses = \App\Support\StudentClassVisibility::forStudent($user, $upcomingQuery)->count();
            
        // Assignments (Total based on student's grade using GradeMatcher)
        $allAssignments = Assignment::with(['submissions' => function($q) use ($user) {
            $q->where('student_id', $user->id);
        }])->get();

        $visibleAssignments = $allAssignments->filter(function ($assignment) use ($user) {
            $assignGrade = $assignment->grade;
            if (empty($assignGrade)) {
                return true;
            }
            $userGrade = $user?->current_grade;
            if (empty($userGrade)) {
                return false;
            }
            return \App\Support\GradeMatcher::normalize($assignGrade) === \App\Support\GradeMatcher::normalize($userGrade);
        });

        $assignmentsTotal = $visibleAssignments->count();

        $pendingAssignments = $visibleAssignments->filter(function ($assignment) {
            return $assignment->submissions->isEmpty();
        })->count();
        
        $completedAssignments = AssignmentSubmission::where('student_id', $user->id)
            ->where('status', 'graded')
            ->count();
            
        // Attendance & Study Hours (A4)
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $now = Carbon::now();

        $pastSchedulesQuery = ZoomSchedule::where('scheduled_at', '>=', $thirtyDaysAgo)
            ->where('scheduled_at', '<=', $now);
        $pastVisibleSchedules = \App\Support\StudentClassVisibility::forStudent($user, $pastSchedulesQuery);

        $endedVisibleSchedules = $pastVisibleSchedules->filter(function ($s) use ($now) {
            $duration = $s->duration ?: 60;
            $endTime = $s->scheduled_at->copy()->addMinutes($duration);
            return $endTime->lte($now);
        });

        $heldClasses = $endedVisibleSchedules->count();
        $endedScheduleIds = $endedVisibleSchedules->pluck('id');

        $attendedClasses = Attendance::where('user_id', $user->id)
            ->whereIn('zoom_schedule_id', $endedScheduleIds)
            ->where('status', 'present')
            ->count();

        $attendanceRateHasData = $heldClasses > 0;
        $attendanceRate = $attendanceRateHasData ? round(($attendedClasses / $heldClasses) * 100) : 0;

        // Study Hours (sum of duration (minutes) of attended classes / 60, rounded to 1 decimal)
        $attendedRows = Attendance::with('zoomSchedule')
            ->where('user_id', $user->id)
            ->where('status', 'present')
            ->get();

        $totalAttendedMinutes = $attendedRows->sum(function ($att) {
            return (float) ($att->zoomSchedule?->duration ?? 60);
        });
        $studyHours = round($totalAttendedMinutes / 60, 1);

        // Subject Mastery & Average Grade
        $submissions = AssignmentSubmission::with('assignment')->where('student_id', $user->id)->where('status', 'graded')->get();
        $subjectScores = [];
        $totalMarks = 0;
        $gradedCount = 0;

        foreach ($submissions as $sub) {
            if ($sub->assignment && $sub->marks !== null) {
                $subj = $sub->assignment->subject ?: 'General';
                if (!isset($subjectScores[$subj])) {
                    $subjectScores[$subj] = ['total' => 0, 'count' => 0];
                }
                $subjectScores[$subj]['total'] += (float)$sub->marks;
                $subjectScores[$subj]['count'] += 1;

                $totalMarks += (float)$sub->marks;
                $gradedCount += 1;
            }
        }

        $colors = ['#0ea5e9', '#8b5cf6', '#10b981', '#f59e0b', '#ec4899'];
        $subjectMastery = [];
        $colorIndex = 0;
        foreach ($subjectScores as $subj => $data) {
            $avg = round($data['total'] / $data['count']);
            $subjectMastery[] = [
                'label' => $subj,
                'val' => $avg,
                'color' => $colors[$colorIndex % count($colors)]
            ];
            $colorIndex++;
        }

        $averageScore = $gradedCount > 0 ? round($totalMarks / $gradedCount) : 0;
        $averageGrade = 'N/A';
        if ($gradedCount > 0) {
            if ($averageScore >= 85) $averageGrade = 'A+';
            elseif ($averageScore >= 75) $averageGrade = 'A';
            elseif ($averageScore >= 65) $averageGrade = 'B';
            elseif ($averageScore >= 55) $averageGrade = 'C';
            elseif ($averageScore >= 35) $averageGrade = 'S';
            else $averageGrade = 'F';
        }

        // Recent Achievements
        $recentAchievements = [];
        if ($attendanceRate == 100 && $heldClasses >= 5 && $attendanceRateHasData) {
            $recentAchievements[] = [
                'title' => 'Perfect Attendance',
                'desc' => '100% attendance rate in your classes',
                'date' => now()->format('M d, Y'),
                'icon' => 'FiCheckCircle',
                'color' => '#10b981',
                'bg' => '#ecfdf5'
            ];
        }
        if ($averageScore >= 90 && $gradedCount > 0) {
            $recentAchievements[] = [
                'title' => 'Top Scorer',
                'desc' => 'Achieved above 90% average marks',
                'date' => now()->format('M d, Y'),
                'icon' => 'FiAward',
                'color' => '#f59e0b',
                'bg' => '#fffbeb'
            ];
        }
        if ($completedAssignments >= 5) {
            $recentAchievements[] = [
                'title' => 'Active Learner',
                'desc' => 'Completed ' . $completedAssignments . ' assignments',
                'date' => now()->format('M d, Y'),
                'icon' => 'FiTrendingUp',
                'color' => '#6366f1',
                'bg' => '#eef2ff'
            ];
        }

        // If no achievements, add a fallback one
        if (empty($recentAchievements) && $attendedClasses > 0) {
             $recentAchievements[] = [
                'title' => 'Good Start',
                'desc' => 'Attended your first learning sessions',
                'date' => now()->format('M d, Y'),
                'icon' => 'FiTrendingUp',
                'color' => '#6366f1',
                'bg' => '#eef2ff'
            ];
        }

        // Recent Announcements
        $announcements = Announcement::where('is_active', true)
            ->where(function($q) {
                $q->whereNull('target_role')
                  ->orWhere('target_role', 'all')
                  ->orWhere('target_role', 'student')
                  ->orWhere('target_role', '');
            })
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()->map(function($a) {
                $type = 'important';
                if ($a->type == 'info') $type = 'update';
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'message' => $a->message,
                    'date' => $a->created_at ? \Carbon\Carbon::parse($a->created_at)->diffForHumans() : 'Recently',
                    'type' => $type
                ];
            });

        return response()->json([
            'user' => [
                'name' => $user->name,
                'full_name' => $user->full_name,
                'needs_subject_review' => $user->needs_subject_review,
                'current_grade' => $user->current_grade,
                'medium' => $user->medium,
                'stream' => $user->stream,
                'pending_medium' => \App\Models\MediumChangeRequest::where('user_id', $user->id)
                    ->where('status', 'pending')->latest()->value('requested_medium'),
                'selected_subjects' => $user->selected_subjects,
            ],
            'today_classes' => $todayClasses,
            'upcoming_classes' => $upcomingClasses,
            'pending_assignments' => $pendingAssignments,
            'completed_assignments' => $completedAssignments,
            'assignments_total' => $assignmentsTotal,
            'attendance_rate' => $attendanceRate,
            'attendance_rate_has_data' => $attendanceRateHasData,
            'attended_classes' => $attendedClasses,
            'held_classes' => $heldClasses,
            'study_hours' => $studyHours,
            'average_grade' => $averageGrade,
            'subject_mastery' => $subjectMastery,
            'recent_achievements' => $recentAchievements,
            'recent_announcements' => $announcements
        ]);
    }
}
