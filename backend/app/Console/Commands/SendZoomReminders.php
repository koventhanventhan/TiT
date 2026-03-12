<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ZoomSchedule;
use App\Models\User;
use App\Services\WhatsAppService;
use Carbon\Carbon;

class SendZoomReminders extends Command
{
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
    public function handle(WhatsAppService $whatsApp)
    {
        $this->info('Checking for Zoom schedules starting in 15 minutes...');

        // Find schedules starting in the next 15-20 minutes that haven't been reminded
        $startTime = Carbon::now()->addMinutes(14);
        $endTime = Carbon::now()->addMinutes(21);

        $schedules = ZoomSchedule::with(['teachers'])
            ->whereBetween('scheduled_at', [$startTime, $endTime])
            ->whereNull('reminded_at')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No schedules found for reminders.');
            return;
        }

        foreach ($schedules as $schedule) {
            $this->info("Sending reminders for: {$schedule->title}");
            $time = $schedule->scheduled_at->format('H:i');
            $baseUrl = config('app.url');

            // 1. Notify Teachers
            foreach ($schedule->teachers as $teacher) {
                if ($teacher->phone_number) {
                    $msg = "🔔 *Reminder: Zoom class starting in 15 mins!*\n\n" .
                           "📝 *Class:* {$schedule->title}\n" .
                           "⏰ *Time:* {$time}\n\n" .
                           "🚀 *Start Class:* {$baseUrl}/teacher/schedule\n" .
                           "🔗 *Link:* {$schedule->zoom_link}";
                    $whatsApp->send($teacher->phone_number, $msg);
                }
            }

            // 2. Notify Students in the same grade
            if ($schedule->grade) {
                $students = User::where('role', 'user')
                    ->where('current_grade', $schedule->grade)
                    ->whereNull('deactivated_at')
                    ->get();

                foreach ($students as $student) {
                    // Filter by selected subjects: Only send if the student has selected this schedule's subject
                    $selected = $student->selected_subjects;
                    $classSubject = trim($schedule->subject);
                    
                    if (!empty($classSubject)) {
                        $selectedArr = is_array($selected) ? $selected : (json_decode($selected, true) ?: explode(',', (string)$selected));
                        $selectedArr = array_map('trim', (array)$selectedArr);
                        
                        if (!in_array($classSubject, $selectedArr)) {
                            $this->line("Skipping student {$student->name} (Subject not selected: {$classSubject})");
                            continue;
                        }
                    }

                    if ($student->phone_number) {
                        $msg = "🔔 *Reminder: Your Zoom class starts in 15 mins!*\n\n" .
                               "📝 *Class:* {$schedule->title}\n" .
                               "⏰ *Time:* {$time}\n\n" .
                               "🎓 *Join Class:* {$baseUrl}/student/zoom\n" .
                               "🔗 *Link:* {$schedule->zoom_link}";
                        $whatsApp->send($student->phone_number, $msg);
                    }
                }
            }

            $schedule->update(['reminded_at' => Carbon::now()]);
            $this->info("Reminders sent for schedule ID: {$schedule->id}");
        }

        $this->info('Reminder process completed.');
    }
}
