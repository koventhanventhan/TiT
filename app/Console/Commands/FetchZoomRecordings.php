<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ZoomSchedule;
use App\Models\LearningMaterial;
use App\Models\SiteSetting;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FetchZoomRecordings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoom:fetch-recordings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch zoom cloud recordings and store them in learning_materials';

    /**
     * Execute the console command.
     */
    public function handle(ZoomService $zoomService)
    {
        // Get grades that are disabled for recordings
        $disabledGrades = json_decode(SiteSetting::get('zoom_recordings_disabled_grades', '[]'), true);
        if (!is_array($disabledGrades)) {
            $disabledGrades = [];
        }

        // Get past meetings within the last 48 hours
        $recentSchedules = ZoomSchedule::where('scheduled_at', '>=', now()->subHours(48))
            ->where('scheduled_at', '<=', now()->subMinutes(30))
            ->whereNotNull('meeting_id')
            ->whereNotIn('id', function($query) {
                $query->select('zoom_schedule_id')->from('learning_materials')->whereNotNull('zoom_schedule_id');
            })
            ->get();

        $fetchedCount = 0;

        foreach ($recentSchedules as $schedule) {
            // Skip disabled grades
            if (in_array($schedule->grade, $disabledGrades)) {
                continue;
            }

            // Optional: If multiple zoom accounts are used, set the active account
            if ($schedule->zoom_account_id) {
                $account = \App\Models\ZoomAccount::find($schedule->zoom_account_id);
                if ($account) {
                    $zoomService->useAccount($account);
                }
            }

            // Check if recording is ready on Zoom Cloud
            $recordingsData = $zoomService->getMeetingRecordings($schedule->meeting_id);
            
            if ($recordingsData && isset($recordingsData['recording_files']) && count($recordingsData['recording_files']) > 0) {
                // Find the best video recording (MP4)
                $videoFile = null;
                foreach ($recordingsData['recording_files'] as $file) {
                    if (isset($file['file_type']) && $file['file_type'] === 'MP4') {
                        $videoFile = $file;
                        break; // Grab the first MP4
                    }
                }

                if ($videoFile && isset($videoFile['play_url'])) {
                    // Create Learning Material
                    LearningMaterial::create([
                        'title' => $schedule->title . ' - Recording',
                        'description' => 'Zoom class recording for ' . $schedule->subject . ' - ' . $schedule->grade,
                        'type' => 'recording',
                        'url' => $videoFile['play_url'],
                        'grade' => $schedule->grade,
                        'medium' => $schedule->medium ?? 'english',
                        'institute_id' => $schedule->institute_id,
                        'teacher_id' => $schedule->created_by,
                        'zoom_schedule_id' => $schedule->id,
                        'file_size' => isset($videoFile['file_size']) ? round($videoFile['file_size'] / 1024 / 1024, 2) . ' MB' : null,
                    ]);
                    $fetchedCount++;
                    Log::info('Fetched Zoom Recording for Schedule: ' . $schedule->id);
                }
            }
        }

        $this->info("Successfully fetched $fetchedCount recordings.");
    }
}
