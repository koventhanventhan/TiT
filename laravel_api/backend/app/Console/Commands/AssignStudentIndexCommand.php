<?php

namespace App\Console\Commands;

use App\Models\User;
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
                            {--batch= : Batch year (2 digits e.g. 28)}
                            {--ids= : Comma-separated list of student user IDs}
                            {--all-unassigned : Select all confirmed students without index numbers}
                            {--dry-run : Simulate and preview without modifying the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign automatic index numbers to students';

    /**
     * Execute the console command.
     */
    public function handle(StudentIndexService $indexService): int
    {
        $batchOption = $this->option('batch');
        $idsOption = $this->option('ids');
        $allUnassigned = $this->option('all-unassigned');
        $isDryRun = $this->option('dry-run');

        if (!$idsOption && !$allUnassigned) {
            $allUnassigned = true;
        }

        $query = User::where('role', 'user')->orderBy('id', 'asc');

        if ($idsOption) {
            $ids = array_filter(array_map('trim', explode(',', $idsOption)));
            $query->whereIn('id', $ids);
        } else {
            // all-unassigned: students without index_number
            $query->whereNull('index_number');
        }

        $students = $query->get();

        if ($students->isEmpty()) {
            $this->info("No matching students found.");
            return Command::SUCCESS;
        }

        $this->info(($isDryRun ? "[DRY RUN] " : "") . "Found " . $students->count() . " student(s) to process.");

        $headers = ['ID', 'Name', 'Grade', 'Batch Year', 'Index Number', 'Status'];
        $rows = [];

        foreach ($students as $student) {
            // Determine batch year
            try {
                $batchYear = $batchOption
                    ? $indexService->normalizeBatchYear($batchOption)
                    : $indexService->calculateDefaultBatchYear($student);
            } catch (\InvalidArgumentException $e) {
                $rows[] = [$student->id, $student->full_name ?? $student->name, $student->current_grade, $batchOption ?? '?', 'N/A', 'ERROR: ' . $e->getMessage()];
                continue;
            }

            if (!empty($student->index_number)) {
                $rows[] = [$student->id, $student->full_name ?? $student->name, $student->current_grade, $student->batch_year, $student->index_number, 'ALREADY ASSIGNED'];
                continue;
            }

            if ($isDryRun) {
                $preview = $indexService->previewNext((int) ($student->institute_id ?: 1), $batchYear, 1)[0] ?? 'N/A';
                $rows[] = [$student->id, $student->full_name ?? $student->name, $student->current_grade, $batchYear, $preview, 'PREVIEW (DRY RUN)'];
            } else {
                try {
                    $assigned = $indexService->assign($student, $batchYear);
                    $rows[] = [$student->id, $student->full_name ?? $student->name, $student->current_grade, $batchYear, $assigned, 'ASSIGNED'];
                } catch (\Throwable $e) {
                    $rows[] = [$student->id, $student->full_name ?? $student->name, $student->current_grade, $batchYear, 'N/A', 'FAILED: ' . $e->getMessage()];
                }
            }
        }

        $this->table($headers, $rows);

        if ($isDryRun) {
            $this->warn("Dry run completed. No database changes were made.");
        } else {
            $this->info("Index number assignment completed.");
        }

        return Command::SUCCESS;
    }
}
