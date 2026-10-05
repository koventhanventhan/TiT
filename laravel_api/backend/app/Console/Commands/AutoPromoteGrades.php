<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\StudentPromotionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoPromoteGrades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotions:auto-run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically promote students for grades scheduled for today';

    public function handle(StudentPromotionService $promotionService, NotificationService $notifier): int
    {
        $today = now()->toDateString(); // e.g. "2027-01-05"
        $this->info("Auto-promotion check running for date: {$today}");

        $configStr = SiteSetting::get('grade_promotion_schedule', '{}');
        $config = json_decode($configStr, true);

        if (!is_array($config) || empty($config)) {
            $this->info('No grade promotion schedule configured. Nothing to do.');
            return self::SUCCESS;
        }

        $totalPromoted = 0;
        $totalFlagged = 0;
        $totalGraduated = 0;
        $totalSkipped = 0;
        $gradesProcessed = [];

        foreach ($config as $grade => $settings) {
            // Skip grades that are not enabled or not scheduled for today
            if (empty($settings['enabled']) || ($settings['date'] ?? null) !== $today) {
                continue;
            }

            $this->info("Processing Grade {$grade}...");

            try {
                // Fetch all eligible students for this grade
                $studentIds = User::where('role', 'user')
                    ->where('is_graduated', false)
                    ->where('current_grade', (string)$grade)
                    ->pluck('id')
                    ->toArray();

                if (empty($studentIds)) {
                    $this->info("  No students found in Grade {$grade}. Skipping.");
                    // Still disable the toggle so it doesn't fire again
                    $config[$grade]['enabled'] = false;
                    $gradesProcessed[] = "Grade {$grade}: 0 students found";
                    continue;
                }

                $this->info("  Found " . count($studentIds) . " students in Grade {$grade}.");

                // Call the shared promotion service (force=false to respect 6-month safeguard)
                $summary = $promotionService->promoteStudents(
                    $studentIds,
                    false,       // force = false
                    null,        // no acting admin — system triggered
                    '127.0.0.1', // system IP
                    'AutoPromoteGrades Command'
                );

                $totalPromoted += $summary['promoted'];
                $totalFlagged += $summary['flagged'];
                $totalGraduated += $summary['graduated'];
                $totalSkipped += $summary['skipped'];

                $gradeMsg = "Grade {$grade}: {$summary['promoted']} promoted, {$summary['flagged']} flagged, {$summary['graduated']} graduated, {$summary['skipped']} skipped";
                $this->info("  {$gradeMsg}");
                $gradesProcessed[] = $gradeMsg;

                // Log to ActivityLog as system-triggered
                ActivityLog::create([
                    'user_id' => null,
                    'action' => 'auto_grade_promotion',
                    'description' => "Auto-promotion for Grade {$grade}: {$summary['promoted']} promoted, {$summary['flagged']} flagged, {$summary['graduated']} graduated, {$summary['skipped']} skipped",
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'AutoPromoteGrades Command',
                    'metadata' => [
                        'grade' => $grade,
                        'date' => $today,
                        'summary' => $summary,
                        'student_count' => count($studentIds),
                    ],
                ]);

                // Disable the toggle so it doesn't fire again
                $config[$grade]['enabled'] = false;

            } catch (\Exception $e) {
                $errorMsg = "Auto-promotion failed for Grade {$grade}: " . $e->getMessage();
                $this->error("  {$errorMsg}");
                Log::error($errorMsg);

                // Still disable to prevent infinite retries on a broken grade
                $config[$grade]['enabled'] = false;
                $gradesProcessed[] = "Grade {$grade}: FAILED — " . $e->getMessage();

                // Continue processing other grades
                continue;
            }
        }

        // Save updated config (with disabled toggles)
        SiteSetting::set('grade_promotion_schedule', json_encode($config), 'general');

        // Summary
        if (empty($gradesProcessed)) {
            $this->info('No grades were scheduled for today.');
            return self::SUCCESS;
        }

        $summaryMsg = "Auto-promotion completed. Promoted: {$totalPromoted}, Flagged: {$totalFlagged}, Graduated: {$totalGraduated}, Skipped: {$totalSkipped}";
        $this->info($summaryMsg);
        Log::info("AutoPromoteGrades: {$summaryMsg}");

        // Notify admin with a summary
        try {
            $notifier->notifyAdmin(
                'admin_alert',
                'admin_alert',
                [$summaryMsg],
                [
                    'subject' => 'Auto-Promotion Summary — ' . $today,
                    'body' => "The scheduled auto-promotion ran today.\n\n"
                        . implode("\n", $gradesProcessed)
                        . "\n\nTotal: {$totalPromoted} promoted, {$totalFlagged} flagged for subject review, {$totalGraduated} graduated, {$totalSkipped} skipped."
                ]
            );
        } catch (\Exception $e) {
            Log::error('AutoPromoteGrades: Failed to send admin notification: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }
}
