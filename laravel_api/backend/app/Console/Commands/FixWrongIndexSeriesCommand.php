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
                            {--institute= : Optional institute ID filter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and repair students whose index series does not equal their grade series';

    public function handle(StudentIndexService $indexService): int
    {
        $isApply = (bool) $this->option('apply');
        $instituteId = $this->option('institute');

        $this->info("==================================================");
        $this->info("     Fix Wrong Student Index Series Tool          ");
        $this->info("     Mode: " . ($isApply ? "APPLY (writes to DB)" : "DRY-RUN (read-only)"));
        $this->info("==================================================");

        $query = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->whereNotNull('index_number');

        if ($instituteId) {
            $query->where('institute_id', $instituteId);
        }

        $students = $query->orderBy('id', 'asc')->get();

        $headers = ['ID', 'Name', 'Grade', 'Current Index', 'Expected Series', 'Status'];
        $rows = [];
        $checkedCount = 0;
        $mismatchCount = 0;
        $fixedCount = 0;
        $unresolvableCount = 0;

        foreach ($students as $student) {
            $checkedCount++;
            $institute = $student->institute ?: Institute::find($student->institute_id ?: 1);
            $format = $institute?->getIndexFormat() ?? 'grade';

            // Only grade-format institutes have grade series
            if ($format !== 'grade') {
                continue;
            }

            $parsed = $indexService->parseIndexNumber($student->index_number);
            if (!$parsed) {
                $rows[] = [$student->id, $student->full_name ?? $student->name, $student->current_grade, $student->index_number, 'N/A', 'INVALID FORMAT'];
                $mismatchCount++;
                continue;
            }

            $expectedSeries = null;
            $deriveError = null;
            try {
                $expectedSeries = $indexService->deriveGradeSeries($student->current_grade);
            } catch (\Throwable $e) {
                $deriveError = $e->getMessage();
            }

            if ($deriveError !== null) {
                // Cannot derive grade series (e.g. "A/L" or empty)
                $rows[] = [
                    $student->id,
                    $student->full_name ?? $student->name,
                    $student->current_grade ?: 'EMPTY',
                    $student->index_number,
                    'UNKNOWN',
                    'CANNOT DERIVE: ' . $deriveError,
                ];
                $unresolvableCount++;
                continue;
            }

            if ($parsed['series'] !== $expectedSeries) {
                $mismatchCount++;
                $oldIndex = $student->index_number;

                if ($isApply) {
                    try {
                        $newIndex = $indexService->reassign(
                            $student,
                            $expectedSeries,
                            null,
                            "Fix wrong index series (was {$oldIndex})"
                        );
                        $fixedCount++;
                        $rows[] = [
                            $student->id,
                            $student->full_name ?? $student->name,
                            $student->current_grade,
                            $oldIndex,
                            $expectedSeries,
                            "FIXED -> {$newIndex}",
                        ];
                    } catch (\Throwable $e) {
                        $rows[] = [
                            $student->id,
                            $student->full_name ?? $student->name,
                            $student->current_grade,
                            $oldIndex,
                            $expectedSeries,
                            "ERROR: " . $e->getMessage(),
                        ];
                    }
                } else {
                    $rows[] = [
                        $student->id,
                        $student->full_name ?? $student->name,
                        $student->current_grade,
                        $oldIndex,
                        $expectedSeries,
                        "MISMATCH (would reassign to series {$expectedSeries})",
                    ];
                }
            }
        }

        if (!empty($rows)) {
            $this->table($headers, $rows);
        } else {
            $this->info("No index series mismatches detected.");
        }

        $this->newLine();
        $this->line("Summary:");
        $this->line("- Total students checked: {$checkedCount}");
        $this->line("- Mismatches found      : {$mismatchCount}");
        if ($unresolvableCount > 0) {
            $this->line("- Unresolvable grades   : {$unresolvableCount}");
        }

        if ($isApply) {
            $this->info("- Successfully fixed    : {$fixedCount}");
        } else {
            $this->comment("Dry run complete. Run with --apply to repair mismatched index numbers.");
        }

        return 0;
    }
}
