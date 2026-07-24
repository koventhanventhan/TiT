<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ZoomSchedule;
use App\Models\User;
use App\Services\NotificationService;
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
    public function handle(NotificationService $notifier)
    {
        $this->info('Checking for Zoom schedules starting in 15 minutes...');

        // Find schedules starting in the next 0-30 minutes that haven't been reminded
        $startTime = Carbon::now();
        $endTime = Carbon::now()->addMinutes(30);

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
                $notifier->notifyUser(
                    $teacher, 'zoom_reminder', 'tit_zoom_reminder',
                    [$schedule->title, $time],
                    ['class_title' => $schedule->title, 'class_time' => $time]
                );
            }

            // 2. Notify Students in the same grade
            if ($schedule->grade) {
                $students = User::where('role', 'user')
                    ->whereNull('deactivated_at')
                    ->get();

                preg_match('/(\d+)/', $schedule->grade, $classMatch);
                $classNum = $classMatch[1] ?? null;

                foreach ($students as $student) {
                    // 1. Grade Match
                    $userGrade = $student->current_grade;
                    if (!$userGrade) {
                        $this->line("Skipping student {$student->name} (No grade set)");
                        continue;
                    }

                    preg_match('/(\d+)/', $userGrade, $userMatch);
                    $userNum = $userMatch[1] ?? null;

                    if ($userNum === null || $classNum === null || $userNum !== $classNum) {
                        // Silent skip for grade mismatch is fine as there are many students
                        continue;
                    }

                    // 2. Filter by selected subjects (Robust substring match)
                    $selected = $student->selected_subjects;
                    $classSubject = trim($schedule->subject);
                    
                    if (!empty($classSubject)) {
                        $selectedArr = is_array($selected) ? $selected : (json_decode($selected, true) ?: explode(',', (string)$selected));
                        $selectedArr = array_filter(array_map('trim', (array)$selectedArr));
                        
                        if (empty($selectedArr)) {
                            $this->line("Skipping student {$student->name} (No subjects selected)");
                            continue;
                        }

                        $subjectMatch = false;
                        foreach ($selectedArr as $studentSub) {
                            $studentSub = trim($studentSub);
                            if ($studentSub === $classSubject || 
                                stripos($studentSub, $classSubject) !== false || 
                                stripos($classSubject, $studentSub) !== false) {
                                $subjectMatch = true;
                                break;
                            }
                        }
                        if (!$subjectMatch) {
                            $this->line("Skipping student {$student->name} (Subject mismatch: expected '{$classSubject}')");
                            continue;
                        }
                    }

                    if ($student->phone_number || ($student->email && !str_ends_with($student->email, '@student.local'))) {
                        $this->info("Sending message to {$student->name}");
                        $notifier->notifyUser(
                            $student, 'zoom_reminder', 'tit_zoom_reminder',
                            [$schedule->title, $time],
                            ['class_title' => $schedule->title, 'class_time' => $time]
                        );
                    } else {
                        $this->warn("Skipping student {$student->name} (No phone number or email)");
                    }
                }
            }

            $schedule->update(['reminded_at' => Carbon::now()]);
            $this->info("Reminders sent for schedule ID: {$schedule->id}");
        }

        $this->info('Reminder process completed.');
    }
}
