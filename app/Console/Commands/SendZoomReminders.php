<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ZoomSchedule;
use App\Models\User;
use App\Services\NotificationService;
use App\Traits\ValidatesEmail;
use Carbon\Carbon;

class SendZoomReminders extends Command
{
    use ValidatesEmail;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoom:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp reminders 15 minutes before the Zoom class starts';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notifier, \App\Services\ClassNotifier $classNotifier)
    {
        $this->info('Checking for Zoom schedules starting in 15 minutes...');

        // Find schedules starting in the next 15 minutes (with grace period for cron jitter) that haven't been reminded
        $startTime = Carbon::now()->subMinutes(5);
        $endTime = Carbon::now()->addMinutes(16);

        $schedules = ZoomSchedule::with(['teachers'])
            ->whereBetween('scheduled_at', [$startTime, $endTime])
            ->whereNull('reminded_at')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No schedules found for reminders.');
            return;
        }

        foreach ($schedules as $schedule) {
            // Idempotency / Race Condition Check: Atomic Update
            $updated = \App\Models\ZoomSchedule::where('id', $schedule->id)
                ->whereNull('reminded_at')
                ->update(['reminded_at' => \Carbon\Carbon::now()]);

            if (!$updated) {
                $this->info("Reminder already sent for: {$schedule->title}, skipping");
                continue;
            }

            $this->info("Locked and sending reminders for: {$schedule->title}");
            $time = $schedule->scheduled_at->format('H:i');
            $baseUrl = config('app.url');
            $emailsSent = 0;

            // 1. Notify Teachers
            foreach ($schedule->teachers as $teacher) {
                $notifier->notifyUser(
                    $teacher, 'zoom_reminder', 'tit_zoom_reminder',
                    [$schedule->title, $time],
                    ['class_title' => $schedule->title, 'class_time' => $time]
                );
            }

            // 2. Notify Students using shared logic
            if ($schedule->grade) {
                $students = $classNotifier->getMatchingStudents(
                    $schedule->grade,
                    $schedule->medium,
                    $schedule->subject
                );

                foreach ($students as $student) {
                    $this->info("Sending message to {$student->name}");
                    $notifier->notifyUser(
                        $student, 'zoom_reminder', 'tit_zoom_reminder',
                        [$schedule->title, $time],
                        ['class_title' => $schedule->title, 'class_time' => $time]
                    );
                    $emailsSent++;

                    if ($emailsSent % 5 === 0) {
                        sleep(2);
                    }
                }
            }

            $this->info("Reminders sent successfully for schedule ID: {$schedule->id}");
        }

        $this->info('Reminder process completed.');
    }
}
