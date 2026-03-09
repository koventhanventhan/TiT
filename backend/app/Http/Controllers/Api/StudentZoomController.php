<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZoomSchedule;
use App\Models\Attendance;
use App\Models\Payment;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentZoomController extends Controller
{
    protected WhatsAppService $whatsApp;

    public function __construct(WhatsAppService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
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
                'zoom_classes' => [],
                'message' => 'Complete payment to access classes.',
            ]);
        }

        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();

        $schedules = ZoomSchedule::whereDate('scheduled_at', '>=', $startOfDay)
            ->whereDate('scheduled_at', '<=', $endOfDay)
            ->where('grade', $user->current_grade) // Only show classes for student's grade
            ->orderBy('scheduled_at')
            ->get();

        $items = $schedules->map(function ($s) use ($user) {
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
                'attendance_status' => $att ? $att->status : 'absent',
            ];
        });

        // Get payment info
        $lastPayment = Payment::where('user_id', $user->id)->where('status', 'paid')->latest('paid_at')->first();
        $nextPaymentDate = Carbon::now()->addMonth()->startOfMonth()->toDateString();

        return response()->json([
            'data' => $items, // Changed from 'zoom_classes' to 'data' for easier frontend consumption
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

        // Send WhatsApp Attendance Message
        $schedule = ZoomSchedule::find($scheduleId);
        $phone = $user->phone_number;
        if ($phone) {
            $message = "Hello " . ($user->full_name ?? $user->name) . ",\n\n" .
                "You have successfully joined today's class: \"" . ($schedule->title ?? 'Zoom Class') . "\".\n" .
                "Your attendance has been marked as PRESENT. ✅\n\n" .
                "Happy learning!";
            $this->whatsApp->send($phone, $message);
        }

        return response()->json(['message' => 'Attendance recorded and WhatsApp notification sent.']);
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

        $schedules = ZoomSchedule::where('scheduled_at', '>=', now())
            ->where('grade', $user->current_grade)
            ->orderBy('scheduled_at')
            ->get();

        return response()->json($schedules);
    }
}
