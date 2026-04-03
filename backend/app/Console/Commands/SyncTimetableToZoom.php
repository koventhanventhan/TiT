<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Timetable;
use App\Models\ZoomSchedule;
use App\Services\ZoomService;
use Carbon\Carbon;

class SyncTimetableToZoom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoom:sync-timetable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Zoom schedules from the recurring timetable for the next 7 days';

    /**
     * Execute the console command.
     */
    public function handle(ZoomService $zoom)
    {
        $this->info('Starting Timetable to Zoom sync...');

        $timetables = Timetable::where('is_active', true)->get();

        foreach ($timetables as $timetable) {
            // Find the next occurrence of the day_of_week in application timezone (Asia/Colombo)
            $nextDate = Carbon::parse("next {$timetable->day_of_week}")->setTimeFromTimeString($timetable->start_time);
            
            // If today is the day and time hasn't passed, use today
            if (Carbon::now()->isDayOfWeek(Carbon::parse($timetable->day_of_week)->dayOfWeek) && Carbon::now()->lt($nextDate->copy()->subWeek())) {
                 // skip
            }
            
            // Actually, Carbon 'next Monday' works well. 
            // We want to check for the next 90 days (approx 3 months).
            for ($i = 0; $i < 90; $i++) {
                $checkDate = Carbon::today()->addDays($i);
                if ($checkDate->format('l') === $timetable->day_of_week) {
                    // Use Application Timezone (Asia/Colombo)
                    $scheduledAt = Carbon::createFromFormat('Y-m-d H:i:s', $checkDate->format('Y-m-d') . ' ' . $timetable->start_time);
                    
                    // Skip if time has passed
                    if ($scheduledAt->isPast()) continue;

                    // Check if schedule already exists for this grade at this time (to prevent overlap)
                    $exists = ZoomSchedule::where('grade', $timetable->grade)
                        ->where('scheduled_at', $scheduledAt->format('Y-m-d H:i:s'))
                        ->exists();

                    if (!$exists) {
                        $this->info("Checking for available Zoom account for {$timetable->title} at {$scheduledAt}");

                        // Find an available Zoom account
                        $account = $zoom->getAvailableAccount($scheduledAt, $timetable->duration);

                        if (!$account) {
                            $this->error("No available Zoom account found for {$timetable->title} at {$scheduledAt}");
                            continue;
                        }

                        $this->info("Using Zoom account: {$account->email}");
                        $zoom->useAccount($account);

                        // Create Zoom Meeting - use the local time ISO string for Zoom
                        $meeting = $zoom->createMeeting(
                            $timetable->title, 
                            $scheduledAt->toIso8601String(), 
                            $timetable->duration
                        );

                        if ($meeting) {
                            $schedule = ZoomSchedule::create([
                                'title' => $timetable->title,
                                'scheduled_at' => $scheduledAt->toDateTimeString(),
                                'zoom_link' => $meeting['join_url'],
                                'meeting_id' => $meeting['id'],
                                'start_url' => $meeting['start_url'],
                                'join_url' => $meeting['join_url'],
                                'password' => $meeting['password'] ?? null,
                                'duration' => $timetable->duration,
                                'grade' => $timetable->grade,
                                'subject' => $timetable->subject->name ?? 'General',
                                'zoom_account_id' => $account->id,
                                'created_by' => $timetable->teacher_id,
                                'institute_id' => $timetable->institute_id,
                            ]);

                            $schedule->teachers()->sync([$timetable->teacher_id]);
                            $this->info("Successfully created Zoom schedule ID: {$schedule->id}");
                        } else {
                            $this->error("Failed to create Zoom meeting for {$timetable->title} via {$account->email}");
                        }
                    } else {
                        $this->line("Schedule already exists for {$timetable->title} at {$scheduledAt}");
                    }
                }
            }
        }

        $this->info('Sync completed.');
    }
}
