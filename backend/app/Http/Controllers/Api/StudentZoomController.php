<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZoomSchedule;
use App\Models\Attendance;
use App\Models\Payment;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentZoomController extends Controller
{
    protected ?NotificationService $notifier = null;

    public function __construct()
    {
        try {
            $this->notifier = app(NotificationService::class);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('NotificationService init failed: ' . $e->getMessage());
        }
    }
    /**
     * Get zoom classes for today within time window (e.g. same day, 1 hour before/after).
     * Only for students who have paid for current month.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'user' || $user->deactivated_at) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $yearMonth = now()->format('Y-m');
        $hasPaid = $user->hasPaidForMonth($yearMonth);
        if (!$hasPaid) {
            return response()->json([
                'data' => [],
                'message' => 'Complete payment to access classes.',
            ]);
        }

        // Only show classes from the last 2 days up to 90 days ahead
        $startBuffer = Carbon::now()->subDays(2);
        $endBuffer = Carbon::now()->addDays(90);

        $schedules = ZoomSchedule::with('teachers')
            ->where('scheduled_at', '>=', $startBuffer)
            ->where('scheduled_at', '<=', $endBuffer)
            ->orderBy('scheduled_at')
            ->get();

        // 1. Filter by Grade (Normalize strings like "Grade 10" or "O/L" or "A/L")
        $schedules = $schedules->filter(function($s) use ($user) {
            $userGrade = $user->current_grade;
            $classGrade = $s->grade;
            
            if (!$userGrade || !$classGrade) return false;

            // Extract numeric values, fallback to normalized string for O/L, A/L etc.
            preg_match('/(\d+)/', $userGrade, $userMatch);
            $userRef = isset($userMatch[1]) ? $userMatch[1] : strtoupper(trim($userGrade));

            preg_match('/(\d+)/', $classGrade, $classMatch);
            $classRef = isset($classMatch[1]) ? $classMatch[1] : strtoupper(trim($classGrade));

            return $userRef === $classRef;
        });

        // Filter by student's selected subjects (Robust substring match)
        $selected = $user->selected_subjects;
        if (!empty($selected)) {
            $selectedArr = is_array($selected) ? $selected : (json_decode($selected, true) ?: explode(',', (string)$selected));
            $selectedArr = array_map('trim', (array)$selectedArr);
            
            $schedules = $schedules->filter(function($s) use ($selectedArr) {
                $classSub = trim($s->subject);
                if (empty($classSub)) return true; // Show general classes
                
                foreach ($selectedArr as $studentSub) {
                    $studentSub = trim($studentSub);
                    if ($studentSub === $classSub || 
                        stripos($studentSub, $classSub) !== false || 
                        stripos($classSub, $studentSub) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        $items = $schedules->values()->map(function ($s) use ($user) {
            $att = Attendance::where('zoom_schedule_id', $s->id)->where('user_id', $user->id)->first();
            return [
                'id' => $s->id,
                'title' => $s->title,
                'zoom_link' => $s->zoom_link,
                'join_url' => $s->join_url,
                'password' => $s->password,
                'scheduled_at' => $s->scheduled_at->toIso8601String(),
                'subject' => $s->subject,
                'grade' => $s->grade,
                'teacher' => $s->teachers->first() ? $s->teachers->first()->name : 'Unknown Teacher',
                'attendance_status' => $att ? $att->status : 'absent',
            ];
        });

        // Get payment info
        $lastPayment = Payment::where('user_id', $user->id)->where('status', 'paid')->latest('paid_at')->first();
        $nextPaymentDate = Carbon::now()->addMonth()->startOfMonth()->toDateString();

        return response()->json([
            'data' => $items,
            'user_profile' => [
                'full_name' => $user->full_name,
                'school_name' => $user->school_name,
                'current_grade' => $user->current_grade,
                'medium' => $user->medium,
                'stream' => $user->stream,
            ],
            'payment_info' => [
                'has_paid' => $hasPaid,
                'last_amount' => $lastPayment ? $lastPayment->amount : 0,
                'last_paid_at' => $lastPayment ? $lastPayment->paid_at->toDateTimeString() : null,
                'next_due_date' => $nextPaymentDate,
            ]
        ]);
    }

    /**
     * Record attendance when student clicks Join.
     */
    public function attend(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'user' || $user->deactivated_at) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate(['zoom_schedule_id' => 'required|exists:zoom_schedules,id']);

        $scheduleId = $request->zoom_schedule_id;
        Attendance::updateOrCreate(
            [
                'zoom_schedule_id' => $scheduleId,
                'user_id' => $user->id,
            ],
            [
                'role' => 'student',
                'status' => 'present',
                'marked_at' => now(),
                'source' => 'link_click',
            ]
        );

        // Send Attendance Message via NotificationService (WhatsApp with Email fallback)
        try {
            if ($this->notifier) {
                $schedule = ZoomSchedule::find($scheduleId);
                $this->notifier->notifyUser(
                    $user, 'zoom_reminder', 'tit_zoom_reminder',
                    [$schedule->title ?? 'Zoom Class', $schedule->scheduled_at->format('H:i')],
                    ['class_title' => $schedule->title ?? 'Zoom Class', 'class_time' => $schedule->scheduled_at->format('H:i')]
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Attendance notification failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Attendance recorded.']);
    }

    /**
     * Get all upcoming zoom classes for the student's grade.
     */
    public function upcomingSchedules(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'user' || $user->deactivated_at) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $yearMonth = now()->format('Y-m');
        $hasPaid = $user->hasPaidForMonth($yearMonth);
        if (!$hasPaid) {
            return response()->json([]);
        }

        // Only show schedules from the last 2 days up to 90 days ahead
        $schedules = ZoomSchedule::with('teachers')
            ->where('scheduled_at', '>=', now()->subDays(2))
            ->where('scheduled_at', '<=', now()->addDays(90))
            ->orderBy('scheduled_at')
            ->get();

        // 1. Filter by Grade (Normalize strings like "Grade 10" or "O/L" or "A/L")
        $schedules = $schedules->filter(function($s) use ($user) {
            $userGrade = $user->current_grade;
            $classGrade = $s->grade;
            
            if (!$userGrade || !$classGrade) return false;

            preg_match('/(\d+)/', $userGrade, $userMatch);
            $userRef = isset($userMatch[1]) ? $userMatch[1] : strtoupper(trim($userGrade));

            preg_match('/(\d+)/', $classGrade, $classMatch);
            $classRef = isset($classMatch[1]) ? $classMatch[1] : strtoupper(trim($classGrade));

            return $userRef === $classRef;
        });

        // Filter by student's selected subjects (Robust substring match)
        $selected = $user->selected_subjects;
        if (!empty($selected)) {
            $selectedArr = is_array($selected) ? $selected : (json_decode($selected, true) ?: explode(',', (string)$selected));
            $selectedArr = array_map('trim', (array)$selectedArr);
            
            $schedules = $schedules->filter(function($s) use ($selectedArr) {
                $classSub = trim($s->subject);
                if (empty($classSub)) return true;
                
                foreach ($selectedArr as $studentSub) {
                    $studentSub = trim($studentSub);
                    if ($studentSub === $classSub || 
                        stripos($studentSub, $classSub) !== false || 
                        stripos($classSub, $studentSub) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        $mapped = $schedules->values()->map(function($s) {
            $s->teacher = $s->teachers->first() ? $s->teachers->first()->name : 'Unknown Teacher';
            return $s;
        });

        return response()->json($mapped->all());
    }
}
