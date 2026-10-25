<?php

namespace App\Console\Commands;

use App\Models\Institute;
use App\Models\User;
use App\Services\StudentIndexService;
use Illuminate\Console\Command;

class FixWrongIndexSeriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:fix-wrong-index-series
                            {--dry-run : Only report wrong index series without making changes}
                            {--apply : Apply re-assignment to fix wrong index series}
                            {--series-map= : Optional comma-separated mapping of old series/batch to new series (e.g. 28:08,26:06)}
                            {--institute= : Optional institute ID filter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and repair students with non-standard index numbers (does not match PREFIX/GG/NNN with GG 01-13 or case-sensitive prefix)';

    public function handle(StudentIndexService $indexService): int
    {
        $isApply = (bool) $this->option('apply');
        $instituteId = $this->option('institute');

        // Parse --series-map if supplied (e.g. "28:08,26:06")
        $seriesMap = [];
        if ($this->option('series-map')) {
            $pairs = explode(',', (string) $this->option('series-map'));
            foreach ($pairs as $pair) {
                $parts = explode(':', trim($pair));
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $val = str_pad(trim($parts[1]), 2, '0', STR_PAD_LEFT);
                    $seriesMap[$key] = $val;
                }
            }
        }

        $this->info("==================================================");
        $this->info("     Fix Wrong Student Index Series Tool          ");
        $this->info("     Mode: " . ($isApply ? "APPLY (writes to DB)" : "DRY-RUN (read-only)"));
        if (!empty($seriesMap)) {
            $this->line("     Series Map Overrides: " . json_encode($seriesMap));
        }
        $this->info("==================================================");

        $query = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->whereNotNull('index_number')
            ->where('index_number', '!=', '');

        if ($instituteId) {
            $query->where('institute_id', $instituteId);
        }

        $students = $query->orderBy('id', 'asc')->get();

        $headers = ['ID', 'Name', 'Current Grade', 'Old Index', 'Proposed Series', 'Status / Action'];
        $rows = [];
        $checkedCount = 0;
        $wrongCount = 0;
        $fixedCount = 0;
        $unresolvableCount = 0;

        foreach ($students as $student) {
            $checkedCount++;
            $institute = $student->institute ?: Institute::find($student->institute_id ?: 1);
            $format = $institute?->getIndexFormat() ?? 'grade';

            // Only grade-format institutes enforce TIT/GG/NNN with GG in 01-13
            if ($format !== 'grade') {
                continue;
            }

            $institutePrefix = $institute?->getIndexPrefix() ?? 'TIT';
            $oldIndex = trim((string) $student->index_number);

            // A number is "wrong" ONLY if:
            // (a) it does not parse as PREFIX/GG/NNN with GG in 01–13, OR
            // (b) the prefix differs from the institute prefix (case-sensitive 'TIT'), OR
            // (c) GG is outside 01–13 (e.g. TIT/26/001, TiT/28/100).
            // It must NEVER compare GG with the student's current grade.
            $isWrong = false;
            $oldExtractedSeries = null;

            if (preg_match('/^([^\/]+)\/([^\/]+)\/([^\/]+)$/', $oldIndex, $parts)) {
                $rawPrefix = $parts[1];
                $rawSeries = $parts[2];
                $rawNum = $parts[3];
                $oldExtractedSeries = $rawSeries;

                // Case-sensitive prefix check
                if ($rawPrefix !== $institutePrefix) {
                    $isWrong = true;
                } elseif (!preg_match('/^0[1-9]|1[0-3]$/', $rawSeries)) {
                    // GG is outside 01-13
                    $isWrong = true;
                } elseif (!preg_match('/^\d{3,}$/', $rawNum)) {
                    // Running number not at least 3 digits
                    $isWrong = true;
                }
            } else {
                $isWrong = true;
            }

            if (!$isWrong) {
                // Completely legitimate permanent index number (even if student was promoted)
                continue;
            }

            $wrongCount++;

            // Determine proposed new series
            $proposedSeries = null;
            if ($oldExtractedSeries !== null && isset($seriesMap[$oldExtractedSeries])) {
                $proposedSeries = $seriesMap[$oldExtractedSeries];
            } else {
                try {
                    $proposedSeries = $indexService->deriveGradeSeries($student->current_grade);
                } catch (\Throwable $e) {
                    $proposedSeries = null;
                }
            }

            if (!$proposedSeries) {
                $rows[] = [
                    $student->id,
                    $student->full_name ?? $student->name,
                    $student->current_grade ?: 'EMPTY',
                    $oldIndex,
                    'UNKNOWN',
                    'CANNOT DERIVE: Missing or unmapped grade',
                ];
                $unresolvableCount++;
                continue;
            }

            if ($isApply) {
                try {
                    $newIndex = $indexService->reassign(
                        $student,
                        $proposedSeries,
                        null,
                        "Fix wrong index format/series (was {$oldIndex})"
                    );
                    $fixedCount++;
                    $rows[] = [
                        $student->id,
                        $student->full_name ?? $student->name,
                        $student->current_grade,
                        $oldIndex,
                        $proposedSeries,
                        "FIXED -> {$newIndex}",
                    ];
                } catch (\Throwable $e) {
                    $rows[] = [
                        $student->id,
                        $student->full_name ?? $student->name,
                        $student->current_grade,
                        $oldIndex,
                        $proposedSeries,
                        "ERROR: " . $e->getMessage(),
                    ];
                }
            } else {
                $rows[] = [
                    $student->id,
                    $student->full_name ?? $student->name,
                    $student->current_grade,
                    $oldIndex,
                    $proposedSeries,
                    "MISMATCH (would reassign to series {$proposedSeries})",
                ];
            }
        }

        if (!empty($rows)) {
            $this->table($headers, $rows);
        } else {
            $this->info("No wrong index numbers detected. All assigned students follow standard format.");
        }

        $this->newLine();
        $this->line("Summary:");
        $this->line("- Total students checked: {$checkedCount}");
        $this->line("- Wrong index numbers   : {$wrongCount}");
        if ($isApply) {
            $this->line("- Successfully fixed    : {$fixedCount}");
        }
        if ($unresolvableCount > 0) {
            $this->line("- Unresolvable grades   : {$unresolvableCount}");
        }

        return 0;
    }
}
