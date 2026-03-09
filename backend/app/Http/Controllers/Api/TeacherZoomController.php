<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZoomSchedule;
use App\Models\Attendance;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TeacherZoomController extends Controller
{
    protected WhatsAppService $whatsApp;

    public function __construct(WhatsAppService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
    }
    /**
     * Get zoom classes for teacher (assigned or all for today) within time window.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'teacher' || $user->deactivated_at) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();

        $schedules = ZoomSchedule::whereDate('scheduled_at', '>=', $startOfDay)
            ->whereDate('scheduled_at', '<=', $endOfDay)
            ->where(function ($q) use ($user) {
                $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id))
                    ->orWhereDoesntHave('teachers');
            })
            ->orderBy('scheduled_at')
            ->get();

        $items = $schedules->map(function ($s) use ($user) {
            $att = Attendance::where('zoom_schedule_id', $s->id)->where('user_id', $user->id)->first();
            return [
                'id' => $s->id,
                'title' => $s->title,
                'zoom_link' => $s->zoom_link,
                'start_url' => $s->start_url,
                'join_url' => $s->join_url,
                'password' => $s->password,
                'scheduled_at' => $s->scheduled_at->toIso8601String(),
                'subject' => $s->subject,
                'grade' => $s->grade,
                'attendance_status' => $att ? $att->status : 'absent',
            ];
        });

        return response()->json([
            'data' => $items,
            'user_profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
            ]
        ]);
    }

    /**
     * Record attendance when teacher clicks Join.
     */
    public function attend(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'teacher' || $user->deactivated_at) {
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
                'role' => 'teacher',
                'status' => 'present',
                'marked_at' => now(),
                'source' => 'link_click',
            ]
        );
        
        // Send WhatsApp Attendance Message
        $schedule = ZoomSchedule::find($scheduleId);
        $phone = $user->phone_number;
        if ($phone) {
            $message = "Hello Teacher " . ($user->name) . ",\n\n" .
                "You have successfully joined the class: \"" . ($schedule->title ?? 'Zoom Class') . "\".\n" .
                "Your attendance has been recorded. ✅";
            $this->whatsApp->send($phone, $message);
        }

        return response()->json(['message' => 'Attendance recorded.']);
    }
}
