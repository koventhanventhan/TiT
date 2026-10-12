<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use App\Models\ZoomSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TeacherStudentController extends Controller
{
    public function index(Request $request)
    {
        $teacherId = $request->user()?->id;
        $now = now();

        $runningScheduleIds = $teacherId ? ZoomSchedule::whereHas('teachers', fn($q) => $q->where('users.id', $teacherId))
            ->where('scheduled_at', '>=', $now->copy()->subHours(12))
            ->get()
            ->filter(function ($s) use ($now) {
                $start = Carbon::parse($s->scheduled_at);
                $end = (clone $start)->addMinutes((int) ($s->duration ?: 60));
                return $start->lessThanOrEqualTo($now) && $end->greaterThan($now);
            })
            ->pluck('id') : collect();

        $presentUserIds = $runningScheduleIds->isEmpty()
            ? []
            : Attendance::whereIn('zoom_schedule_id', $runningScheduleIds)
                ->where('status', 'present')
                ->pluck('user_id')
                ->flip()
                ->all();

        $students = User::where('role', 'user')
            ->whereIn('registration_status', ['approved', 'payment_completed'])
            ->select('id', 'name', 'email', 'phone_number', 'current_grade', 'stream', 'registration_status as status')
            ->get()
            ->map(function ($student) use ($presentUserIds) {
                $student->is_online = isset($presentUserIds[$student->id]);
                return $student;
            })
            ->values();

        return response()->json($students);
    }

    public function show(User $student)
    {
        if ($student->role !== 'user') {
            abort(404);
        }

        $attendance = Attendance::where('user_id', $student->id)
            ->with(['zoomSchedule' => function($q) {
                $q->select('id', 'title', 'subject');
            }])
            ->orderBy('marked_at', 'desc')
            ->limit(20)
            ->get(['id', 'zoom_schedule_id', 'status', 'marked_at'])
            ->map(function ($att) {
                $sched = $att->zoomSchedule ? [
                    'id' => $att->zoomSchedule->id,
                    'title' => $att->zoomSchedule->title,
                    'subject' => $att->zoomSchedule->subject,
                ] : null;

                return [
                    'id' => $att->id,
                    'status' => $att->status,
                    'marked_at' => $att->marked_at,
                    'zoom_schedule' => $sched,
                    'zoomSchedule' => $sched,
                ];
            });

        return response()->json([
            'profile' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'phone_number' => $student->phone_number,
                'current_grade' => $student->current_grade,
                'stream' => $student->stream,
                'registration_status' => $student->registration_status,
            ],
            'attendance' => $attendance
        ]);
    }
}
