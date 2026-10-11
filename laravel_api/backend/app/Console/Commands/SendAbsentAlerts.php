<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\User;
use App\Models\ZoomSchedule;
use App\Services\ClassNotifier;
use App\Services\EventNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendAbsentAlerts extends Command
{
    protected $signature = 'alerts:absent';

    protected $description = 'Email parents/students when a student missed a finished class, and warn about low attendance';

    /** Attendance below this % (over 30 days) triggers a warning */
    const LOW_ATTENDANCE_PERCENT = 60;
    /** Need at least this many classes before judging attendance */
    const MIN_CLASSES = 4;

    public function handle(ClassNotifier $classNotifier)
    {
        // Classes that started in the last 12h and have finished
        $schedules = ZoomSchedule::where('scheduled_at', '>=', now()->subHours(12))
            ->where('scheduled_at', '<=', now()->subMinutes(30))
            ->get()
            ->filter(fn($s) => $s->scheduled_at->copy()->addMinutes($s->duration ?: 60)->addMinutes(10)->lte(now()));

        $absentStudents = collect();
        $alerts = 0;

        foreach ($schedules as $schedule) {
            // Process each finished class only once
            if (!Cache::add('absent_alerts_done:' . $schedule->id, 1, now()->addDays(3))) {
                continue;
            }
            if (!$schedule->grade) {
                continue;
            }

            $attended = EventNotifier::attendedIds($schedule->id);
            $students = $classNotifier->getMatchingStudents($schedule->grade, $schedule->medium, $schedule->subject);

            foreach ($students as $student) {
                if (in_array($student->id, $attended)) {
                    continue;
                }
                if (EventNotifier::absent($student, $schedule)) {
                    $alerts++;
                    if ($alerts % 5 === 0) {
                        sleep(1);
                    }
                }
                $absentStudents->put($student->id, $student);
            }
        }

        $this->info("Absent alerts sent: {$alerts}");

        // ---- Low attendance warnings (only for students who were absent now) ----
        if ($absentStudents->isEmpty()) {
            return;
        }

        $recent = ZoomSchedule::where('scheduled_at', '>=', now()->subDays(30))
            ->where('scheduled_at', '<=', now()->subHour())
            ->whereNotNull('grade')
            ->get();

        // Which students belong to which class (cached per grade/medium/subject combo)
        $audience = [];
        $matchIds = [];
        foreach ($recent as $s) {
            $key = $s->grade . '|' . $s->medium . '|' . $s->subject;
            if (!isset($audience[$key])) {
                $audience[$key] = $classNotifier->getMatchingStudents($s->grade, $s->medium, $s->subject)->pluck('id')->all();
            }
            $matchIds[$s->id] = $audience[$key];
        }

        $warned = 0;
        foreach ($absentStudents as $student) {
            $classIds = [];
            foreach ($matchIds as $sid => $ids) {
                if (in_array($student->id, $ids)) {
                    $classIds[] = $sid;
                }
            }
            $total = count($classIds);
            if ($total < self::MIN_CLASSES) {
                continue;
            }

            $attended = Attendance::where('user_id', $student->id)
                ->where('status', 'present')
                ->whereIn('zoom_schedule_id', $classIds)
                ->count();

            $percent = (int) round(($attended / $total) * 100);

            if ($percent < self::LOW_ATTENDANCE_PERCENT && Cache::add('low_attendance:' . $student->id, 1, now()->addDays(7))) {
                if (EventNotifier::lowAttendance($student, $percent, $attended, $total)) {
                    $warned++;
                }
            }
        }

        $this->info("Low attendance warnings sent: {$warned}");
    }
}
