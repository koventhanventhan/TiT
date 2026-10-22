<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\StudentIndexService;
use Illuminate\Console\Command;

class AssignStudentIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:assign-index
                            {--batch= : Batch year (2 digits e.g. 28) for all students}
                            {--map= : Inline grade-to-batch mapping, e.g. "4=28,5=27,10=28,11=27"}
                            {--map-file= : Path to JSON file containing grade-to-batch mapping}
                            {--use-saved-map : Use grade-to-batch mapping saved in admin settings}
                            {--order=registered : Numbering order inside sequence: "registered" (default) or "name"}
                            {--grades= : Comma-separated list of grades to filter (e.g. 10,11)}
                            {--ids= : Comma-separated list of student user IDs}
                            {--all-unassigned : Select all confirmed students without index numbers}
                            {--include-deactivated : Include deactivated students}
                            {--chunk=200 : Chunk size for processing}
                            {--institute= : Optional Institute ID to filter by}
                            {--dry-run : Simulate and preview without modifying the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign automatic index numbers to students safely in bulk or preview mode';

    /**
     * Execute the console command.
     */
    public function handle(StudentIndexService $indexService): int
    {
        $batchOption = $this->option('batch');
        $mapOption = $this->option('map');
        $mapFileOption = $this->option('map-file');
        $useSavedMap = $this->option('use-saved-map');
        $order = strtolower((string) ($this->option('order') ?: 'registered'));
        $gradesOption = $this->option('grades');
        $idsOption = $this->option('ids');
        $allUnassigned = $this->option('all-unassigned');
        $includeDeactivated = $this->option('include-deactivated');
        $chunkSize = max(1, (int) ($this->option('chunk') ?: 200));
        $instituteId = (int) ($this->option('institute') ?: 1);
        $isDryRun = $this->option('dry-run');

        // Resolve grade-batch mapping
        $mapping = [];
        if ($mapOption) {
            $pairs = explode(',', (string) $mapOption);
            foreach ($pairs as $pair) {
                if (str_contains($pair, '=')) {
                    [$g, $b] = explode('=', $pair, 2);
                    $g = trim($g);
                    $b = (int) trim($b);
                    if ($g !== '' && $b >= 20 && $b <= 99) {
                        $mapping[$g] = $b;
                    }
                }
            }
        } elseif ($mapFileOption) {
            if (!file_exists($mapFileOption)) {
                $this->error("Map file not found: {$mapFileOption}");
                return Command::FAILURE;
            }
            $fileData = json_decode(file_get_contents($mapFileOption), true);
            if (!is_array($fileData)) {
                $this->error("Invalid JSON content in map file: {$mapFileOption}");
                return Command::FAILURE;
            }
            $mapping = $fileData;
        } elseif ($useSavedMap || empty($batchOption)) {
            $mapping = $indexService->getGradeBatchMapping($instituteId);
        }

        if (!$idsOption && !$allUnassigned) {
            $allUnassigned = true;
        }

        $query = User::withoutGlobalScopes()
            ->where('role', 'user');

        if ($this->option('institute')) {
            $query->where('institute_id', (int) $this->option('institute'));
        }

        if ($idsOption) {
            $ids = array_filter(array_map('trim', explode(',', $idsOption)));
            $query->whereIn('id', $ids);
        } else {
            // all-unassigned: only confirmed / active students (pending/rejected registrations are NEVER included)
            $query->whereNull('index_number')
                  ->where(function ($q) {
                      $q->whereNull('registration_status')
                        ->orWhereNotIn('registration_status', ['pending', 'rejected']);
                  });

            if (!$includeDeactivated) {
                $query->whereNull('deactivated_at');
            }
        }

        if ($gradesOption) {
            $rawGrades = array_filter(array_map('trim', explode(',', $gradesOption)));
            if (!empty($rawGrades)) {
                $query->where(function ($q) use ($rawGrades) {
                    foreach ($rawGrades as $rg) {
                        $num = preg_replace('/[^0-9]/', '', $rg);
                        $q->orWhere('current_grade', $rg)
                          ->orWhere('current_grade', "Grade {$rg}")
                          ->orWhere('current_grade', $num)
                          ->orWhere('current_grade', "Grade {$num}");
                    }
                });
            }
        }

        if ($order === 'name') {
            $query->orderBy('full_name', 'asc')->orderBy('id', 'asc');
        } else {
            $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
        }

        $students = $query->get();

        if ($students->isEmpty()) {
            $this->info("No matching students found.");
            return Command::SUCCESS;
        }

        $this->info(($isDryRun ? "[DRY RUN] " : "") . "Found " . $students->count() . " student(s) to process.");

        // If Dry Run: run deterministic preview
        if ($isDryRun) {
            $batchMode = $batchOption ? 'single' : 'mapping';
            $singleBatch = $batchOption ? (int) $batchOption : null;

            $preview = $indexService->previewBulkAssign(
                $students,
                $instituteId,
                $batchMode,
                $singleBatch,
                $mapping
            );

            $headers = ['ID', 'Name', 'Grade', 'Batch Year', 'Index Number', 'Status'];
            $rows = [];

            foreach ($preview['preview_rows'] as $row) {
                $rows[] = [
                    $row['student_id'],
                    $row['name'],
                    $row['grade'],
                    $row['batch'],
                    $row['index_number'],
                    'PREVIEW (DRY RUN)',
                ];
            }

            foreach ($preview['skipped'] as $s) {
                $rows[] = [
                    $s['student_id'],
                    $s['name'],
                    $s['grade'],
                    'N/A',
                    'N/A',
                    'SKIPPED: ' . $s['reason'],
                ];
            }

            $this->table($headers, $rows);

            if (!empty($preview['grade_summaries'])) {
                $this->info("\nBatch breakdown:");
                foreach ($preview['grade_summaries'] as $gs) {
                    $this->line("  Grade {$gs['grade']} -> Batch '{$gs['batch_year']}: {$gs['count']} student(s) ({$gs['first_index']} ... {$gs['last_index']})");
                }
            }

            $this->warn("\nDry run completed. Total eligible: {$preview['total_students']}, Skipped: {$preview['skipped_count']}. No database changes were made.");
            return Command::SUCCESS;
        }

        // Real Execution (in chunks)
        $headers = ['ID', 'Name', 'Grade', 'Batch Year', 'Index Number', 'Status'];
        $rows = [];
        $assignedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $firstAssigned = null;
        $lastAssigned = null;

        $studentChunks = $students->chunk($chunkSize);

        foreach ($studentChunks as $chunk) {
            foreach ($chunk as $student) {
                if (!empty($student->index_number)) {
                    $rows[] = [$student->id, $student->full_name ?? $student->name, $student->current_grade, $student->batch_year, $student->index_number, 'ALREADY ASSIGNED'];
                    continue;
                }

                // Determine batch year (NO SILENT CURRENT YEAR FALLBACK)
                $batchYear = null;
                if ($batchOption) {
                    try {
                        $batchYear = $indexService->normalizeBatchYear($batchOption);
                    } catch (\InvalidArgumentException $e) {
                        $rows[] = [$student->id, $student->full_name ?? $student->name, $student->current_grade, $batchOption, 'N/A', 'ERROR: ' . $e->getMessage()];
                        $errorCount++;
                        continue;
                    }
                } elseif (!empty($mapping)) {
                    $batchYear = $indexService->resolveBatchForGrade($student->current_grade, $mapping);
                } elseif (!empty($student->batch_year)) {
                    try {
                        $batchYear = $indexService->normalizeBatchYear($student->batch_year);
                    } catch (\InvalidArgumentException $e) {
                        $batchYear = null;
                    }
                }

                if ($batchYear === null) {
                    $rows[] = [
                        $student->id,
                        $student->full_name ?? $student->name,
                        $student->current_grade ?: 'N/A',
                        'N/A',
                        'N/A',
                        'SKIPPED: no batch year (set the grade mapping or pass --batch)',
                    ];
                    $skippedCount++;
                    continue;
                }

                try {
                    $assigned = $indexService->assign($student, $batchYear);
                    $rows[] = [$student->id, $student->full_name ?? $student->name, $student->current_grade, $batchYear, $assigned, 'ASSIGNED'];
                    $assignedCount++;
                    if (!$firstAssigned) {
                        $firstAssigned = $assigned;
                    }
                    $lastAssigned = $assigned;
                } catch (\Throwable $e) {
                    $rows[] = [$student->id, $student->full_name ?? $student->name, $student->current_grade, $batchYear, 'N/A', 'FAILED: ' . $e->getMessage()];
                    $errorCount++;
                }
            }
        }

        $this->table($headers, $rows);

        ActivityLogService::log(
            'student_index_cli_assigned',
            "CLI bulk assigned index numbers: {$assignedCount} assigned, {$skippedCount} skipped, {$errorCount} errors",
            [
                'assigned_count' => $assignedCount,
                'skipped_count' => $skippedCount,
                'error_count' => $errorCount,
                'first_index' => $firstAssigned ?? 'N/A',
                'last_index' => $lastAssigned ?? 'N/A',
            ]
        );

        $this->info("\nIndex assignment completed: {$assignedCount} assigned, {$skippedCount} skipped, {$errorCount} error(s).");
        return Command::SUCCESS;
    }
}
