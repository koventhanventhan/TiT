<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZoomSchedule;
use App\Models\Attendance;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TeacherZoomController extends Controller
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
     * Get zoom classes for teacher (assigned or all for today) within time window.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'teacher' || $user->deactivated_at) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $startBuffer = Carbon::now()->subDays(2);
        $endBuffer = Carbon::now()->addDays(90);

        $schedules = ZoomSchedule::where('scheduled_at', '>=', $startBuffer)
            ->where('scheduled_at', '<=', $endBuffer)
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
}
