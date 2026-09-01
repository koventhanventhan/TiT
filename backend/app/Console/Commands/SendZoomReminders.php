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
    public function handle(NotificationService $notifier)
    {
        $this->info('Checking for Zoom schedules starting in 15 minutes...');

        // Find schedules starting in the next 0-45 minutes (and up to 20 mins in the past) that haven't been reminded
        $startTime = Carbon::now()->subMinutes(20);
        $endTime = Carbon::now()->addMinutes(45);

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

            // 2. Notify Students in the same grade
            if ($schedule->grade) {
                // Get all students and load their parent user
                $students = \App\Models\Student::with('user')
                    ->whereHas('user', function($query) {
                        $query->whereNull('deactivated_at');
                    })
                    ->get();

                preg_match('/(\d+)/', $schedule->grade, $classMatch);
                $classRef = isset($classMatch[1]) ? $classMatch[1] : strtoupper(trim($schedule->grade));

                foreach ($students as $student) {
                    $parent = $student->user;
                    if (!$parent) continue;

                    // 1. Grade Match
                    $userGrade = $student->current_grade;
                    if (!$userGrade) {
                        $this->line("Skipping student {$student->full_name} (No grade set)");
                        continue;
                    }

                    preg_match('/(\d+)/', $userGrade, $userMatch);
                    $userRef = isset($userMatch[1]) ? $userMatch[1] : strtoupper(trim($userGrade));

                    if ($userRef !== $classRef) {
                        continue;
                    }

                    // 2. Filter by medium
                    $classMedium = $schedule->medium;
                    if ($classMedium && $classMedium !== 'both' && $student->medium && $student->medium !== $classMedium) {
                        continue;
                    }

                    // 3. Filter by selected subjects
                    $selected = $student->selected_subjects;
                    $classSubject = trim($schedule->subject);
                    
                    if (!empty($classSubject)) {
                        $selectedArr = is_array($selected) ? $selected : (json_decode($selected, true) ?: explode(',', (string)$selected));
                        $selectedArr = array_filter(array_map('trim', (array)$selectedArr));
                        
                        if (empty($selectedArr)) {
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
                            continue;
                        }
                    }

                    if ($parent->phone_number || ($parent->email && $this->isValidEmailForSending($parent->email))) {
                        $this->info("Sending message to parent of {$student->full_name}");
                        $studentFirstName = $student->first_name ?? $student->full_name ?? 'Student';
                        $customTitle = "{$studentFirstName} - {$schedule->title}";

                        $notifier->notifyUser(
                            $parent, 'zoom_reminder', 'tit_zoom_reminder',
                            [$customTitle, $time], // WhatsApp array
                            ['class_title' => $customTitle, 'class_time' => $time] // Email map
                        );
                        $emailsSent++;

                        if ($emailsSent % 5 === 0) {
                            sleep(2);
                        }
                    } else {
                        $this->warn("Skipping parent of {$student->full_name} (No valid contact)");
                    }
                }
            }

            $this->info("Reminders sent successfully for schedule ID: {$schedule->id}");
        }

        $this->info('Reminder process completed.');
    }
}
