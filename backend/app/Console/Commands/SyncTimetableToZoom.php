<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Timetable;
use App\Models\ZoomSchedule;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Generate Zoom schedules from the recurring timetable for the next 90 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Timetable to Zoom sync...');

        // Try to resolve ZoomService, but don't fail if it's not available
        $zoom = null;
        try {
            $zoom = app(ZoomService::class);
        } catch (\Exception $e) {
            $this->warn('ZoomService not available: ' . $e->getMessage());
        }

        // Bypass FK checks for production data consistency
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // withoutGlobalScopes so cron can see all timetables (not scoped to logged-in user)
            $timetables = Timetable::withoutGlobalScopes()->with('subject')->where('is_active', true)->get();

            $this->info("Found {$timetables->count()} active timetable entries.");

            foreach ($timetables as $timetable) {
                // We want to check for the next 90 days
                for ($i = 0; $i < 90; $i++) {
                    $checkDate = Carbon::today()->addDays($i);
                    if ($checkDate->format('l') === $timetable->day_of_week) {
                        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i:s', $checkDate->format('Y-m-d') . ' ' . $timetable->start_time);
                        
                        if ($scheduledAt->isPast()) continue;

                        // Check if schedule already exists for THIS specific timetable entry on this date
                        $existingSchedule = ZoomSchedule::withoutGlobalScopes()
                            ->where('timetable_id', $timetable->id)
                            ->whereDate('scheduled_at', $scheduledAt->toDateString())
                            ->first();

                        // Also check for legacy records without timetable_id (old data)
                        if (!$existingSchedule) {
                            $existingSchedule = ZoomSchedule::withoutGlobalScopes()
                                ->whereNull('timetable_id')
                                ->where('grade', $timetable->grade)
                                ->where('institute_id', $timetable->institute_id)
                                ->whereDate('scheduled_at', $scheduledAt->toDateString())
                                ->where('subject', $timetable->subject->name ?? 'General')
                                ->where('title', $timetable->title)
                                ->first();
                            
                            // Claim this legacy record for this timetable
                            if ($existingSchedule) {
                                $existingSchedule->update(['timetable_id' => $timetable->id]);
                            }
                        }

                        if ($existingSchedule) {
                            // Update existing schedule if the time has changed in the timetable
                            $existingTime = Carbon::parse($existingSchedule->scheduled_at)->format('H:i:s');
                            $newTime = $scheduledAt->format('H:i:s');

                            $timetableSubject = $timetable->subject->name ?? 'General';
                            
                            $changed = ($existingTime !== $newTime) || 
                                       ($existingSchedule->title !== $timetable->title) || 
                                       ($existingSchedule->duration !== $timetable->duration) ||
                                       ($existingSchedule->grade !== $timetable->grade) ||
                                       ($existingSchedule->subject !== $timetableSubject);

                            if ($changed) {
                                $existingSchedule->update([
                                    'title' => $timetable->title,
                                    'scheduled_at' => $scheduledAt->toDateTimeString(),
                                    'duration' => $timetable->duration,
                                    'grade' => $timetable->grade,
                                    'subject' => $timetableSubject,
                                ]);
                                $this->info("Updated schedule #{$existingSchedule->id} (Time/Grade/Subject changes applied)");
                            }
                            continue;
                        }

                        // Create new schedule
                        $this->info("Creating schedule for {$timetable->title} at {$scheduledAt}");
                        
                        $meetingData = [
                            'title' => $timetable->title,
                            'scheduled_at' => $scheduledAt->toDateTimeString(),
                            'duration' => $timetable->duration,
                            'grade' => $timetable->grade,
                            'subject' => $timetable->subject->name ?? 'General',
                            'timetable_id' => $timetable->id,
                            'created_by' => $timetable->teacher_id,
                            'institute_id' => $timetable->institute_id,
                        ];

                        // Try to create Zoom meeting if service is available
                        if ($zoom) {
                            try {
                                $account = $zoom->getAvailableAccount($scheduledAt, $timetable->duration);

                                if ($account) {
                                    $this->info("Using Zoom account: {$account->email}");
                                    $zoom->useAccount($account);

                                    $meeting = $zoom->createMeeting(
                                        $timetable->title, 
                                        $scheduledAt->toIso8601String(), 
                                        $timetable->duration
                                    );

                                    if ($meeting) {
                                        $meetingData['zoom_link'] = $meeting['join_url'];
                                        $meetingData['meeting_id'] = $meeting['id'];
                                        $meetingData['start_url'] = $meeting['start_url'];
                                        $meetingData['join_url'] = $meeting['join_url'];
                                        $meetingData['password'] = $meeting['password'] ?? null;
                                        $meetingData['zoom_account_id'] = $account->id;
                                    } else {
                                        $this->warn("Zoom meeting creation returned null for {$timetable->title}");
                                    }
                                } else {
                                    $this->warn("No available Zoom account for {$timetable->title} at {$scheduledAt}");
                                }
                            } catch (\Exception $e) {
                                $this->warn("Zoom API error: " . $e->getMessage());
                            }
                        }

                        // Always create the schedule record (even without Zoom link)
                        $schedule = ZoomSchedule::create($meetingData);
                        $schedule->teachers()->sync([$timetable->teacher_id]);
                        $this->info("Created schedule #{$schedule->id}" . (isset($meetingData['zoom_link']) ? ' with Zoom link' : ' without Zoom link'));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error("SyncTimetableToZoom error: " . $e->getMessage());
        } finally {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info('Sync completed.');
    }
}
