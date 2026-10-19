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
            ->with(['parent:id,name,full_name,phone_number'])
            ->select('id', 'name', 'full_name', 'email', 'phone_number', 'current_grade', 'stream', 'registration_status as status', 'parent_id')
            ->get()
            ->map(function ($student) use ($presentUserIds) {
                $isSibling = !empty($student->parent_id);
                $parent = $student->parent;
                $phone = $student->phone_number;
                $phoneIsParent = false;
                if (empty($phone) && $parent && !empty($parent->phone_number)) {
                    $phone = $parent->phone_number;
                    $phoneIsParent = true;
                }

                $student->is_online = isset($presentUserIds[$student->id]);
                $student->is_sibling = $isSibling;
                $student->parent_name = $parent ? ($parent->full_name ?: $parent->name) : null;
                $student->phone_number = $phone;
                $student->phone_is_parent = $phoneIsParent;
                $student->email = $student->displayEmail();
                $student->display_name = $student->full_name ?: $student->name;
                $student->index_number = $student->getAttribute('index_number') ?: ("STU-" . $student->id);
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

        $student->load(['parent:id,name,full_name,phone_number']);

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

        $isSibling = !empty($student->parent_id);
        $parent = $student->parent;
        $phone = $student->phone_number;
        $phoneIsParent = false;
        if (empty($phone) && $parent && !empty($parent->phone_number)) {
            $phone = $parent->phone_number;
            $phoneIsParent = true;
        }

        return response()->json([
            'profile' => [
                'id' => $student->id,
                'name' => $student->full_name ?: $student->name,
                'full_name' => $student->full_name ?: $student->name,
                'username' => $student->name,
                'email' => $student->displayEmail(),
                'phone_number' => $phone,
                'phone_is_parent' => $phoneIsParent,
                'is_sibling' => $isSibling,
                'parent_name' => $parent ? ($parent->full_name ?: $parent->name) : null,
                'parent_id' => $student->parent_id,
                'index_number' => $student->getAttribute('index_number') ?: ("STU-" . $student->id),
                'current_grade' => $student->current_grade,
                'stream' => $student->stream,
                'registration_status' => $student->registration_status,
            ],
            'attendance' => $attendance
        ]);
    }
}
