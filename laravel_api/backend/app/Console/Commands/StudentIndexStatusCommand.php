<?php

namespace App\Console\Commands;

use App\Models\Institute;
use App\Models\StudentIndexCounter;
use App\Models\User;
use App\Services\StudentIndexService;
use Illuminate\Console\Command;

class StudentIndexStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:index-status
                            {--institute= : Optional institute ID filter (default 1)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display summary of student index numbers, counts per series, old-format count, unassigned students, and next sequence numbers';

    public function handle(StudentIndexService $indexService): int
    {
        $instituteId = (int) ($this->option('institute') ?: 1);
        $institute = Institute::find($instituteId);

        $format = $institute?->getIndexFormat() ?? 'grade';
        $prefix = $institute?->getIndexPrefix() ?? 'TIT';
        $seqStart = $institute?->getIndexSeqStart() ?? 1;

        $this->info("================================================================================");
        $this->info("                     STUDENT INDEX NUMBER STATUS (READ-ONLY)                    ");
        $this->info("================================================================================");
        $this->line("Institute : " . ($institute?->name ?? 'Default Institute') . " (ID: {$instituteId})");
        $this->line("Format    : {$format}");
        $this->line("Prefix    : {$prefix}");
        $this->line("Seq Start : {$seqStart}");
        $this->newLine();

        $studentsQuery = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId);

        $totalStudents = (clone $studentsQuery)->count();
        $unassignedStudents = (clone $studentsQuery)
            ->where(function ($q) {
                $q->whereNull('index_number')->orWhere('index_number', '');
            })->count();

        $assignedStudents = (clone $studentsQuery)
            ->whereNotNull('index_number')
            ->where('index_number', '!=', '')
            ->get();

        $oldFormatStudents = [];
        $seriesCounts = [];

        // Prepopulate series 01-13 for grade mode
        if ($format === 'grade') {
            for ($g = 1; $g <= 13; $g++) {
                $sKey = sprintf('%02d', $g);
                $seriesCounts[$sKey] = 0;
            }
        }

        foreach ($assignedStudents as $student) {
            $index = trim((string) $student->index_number);
            $isStandard = false;

            if ($format === 'grade') {
                if (preg_match('/^' . preg_quote($prefix, '/') . '\/(0[1-9]|1[0-3])\/(\d{3,})$/', $index, $m)) {
                    $isStandard = true;
                    $sKey = $m[1];
                    $seriesCounts[$sKey] = ($seriesCounts[$sKey] ?? 0) + 1;
                }
            } else {
                if (preg_match('/^' . preg_quote($prefix, '/') . '\/(\d{2})\/(\d{3,})$/', $index, $m)) {
                    $isStandard = true;
                    $sKey = $m[1];
                    $seriesCounts[$sKey] = ($seriesCounts[$sKey] ?? 0) + 1;
                }
            }

            if (!$isStandard) {
                $oldFormatStudents[] = [
                    'id' => $student->id,
                    'name' => $student->full_name ?? $student->name,
                    'grade' => $student->current_grade ?? 'N/A',
                    'index' => $index,
                ];
            }
        }

        // Table 1: Series Status & Next Sequence Numbers
        $this->info("--- Counts & Next Sequence per Series ---");
        $seriesHeaders = ['Series', 'Grade/Batch', 'Students Count', 'Counter Last Seq', 'Next Seq', 'Next Index Number'];
        $seriesRows = [];

        if ($format === 'grade') {
            for ($g = 1; $g <= 13; $g++) {
                $sKey = sprintf('%02d', $g);
                $count = $seriesCounts[$sKey] ?? 0;

                $seriesInt = (int) $sKey;
                $counter = StudentIndexCounter::where('institute_id', $instituteId)
                    ->where('batch_year', $seriesInt)
                    ->first();

                $lastSeq = $counter ? (int) $counter->last_seq : 0;
                $nextSeq = $counter ? max($lastSeq + 1, $seqStart) : $seqStart;
                $nextIndex = sprintf('%s/%s/%03d', $prefix, $sKey, $nextSeq);

                $seriesRows[] = [
                    $sKey,
                    "Grade " . (int) $sKey,
                    $count,
                    $lastSeq > 0 ? $lastSeq : '—',
                    $nextSeq,
                    $nextIndex,
                ];
            }
        } else {
            ksort($seriesCounts);
            foreach ($seriesCounts as $batchYear => $count) {
                $counter = StudentIndexCounter::where('institute_id', $instituteId)
                    ->where('batch_year', (int) $batchYear)
                    ->first();

                $lastSeq = $counter ? (int) $counter->last_seq : 0;
                $nextSeq = $counter ? max($lastSeq + 1, $seqStart) : $seqStart;
                $nextIndex = sprintf('%s/%s/%03d', $prefix, $batchYear, $nextSeq);

                $seriesRows[] = [
                    $batchYear,
                    "Batch '{$batchYear}",
                    $count,
                    $lastSeq > 0 ? $lastSeq : '—',
                    $nextSeq,
                    $nextIndex,
                ];
            }
        }

        $this->table($seriesHeaders, $seriesRows);
        $this->newLine();

        // Table 2: Old / Legacy Format Numbers
        $this->info("--- Old / Non-Standard Format Index Numbers (Count: " . count($oldFormatStudents) . ") ---");
        if (!empty($oldFormatStudents)) {
            $oldHeaders = ['ID', 'Student Name', 'Current Grade', 'Current Index Number'];
            $oldRows = array_slice(array_map(function ($s) {
                return [$s['id'], $s['name'], $s['grade'], $s['index']];
            }, $oldFormatStudents), 0, 15);

            $this->table($oldHeaders, $oldRows);
            if (count($oldFormatStudents) > 15) {
                $this->line("... and " . (count($oldFormatStudents) - 15) . " more old-format students.");
            }
            $this->line("Run 'php artisan students:fix-wrong-index-series' to preview and convert them.");
        } else {
            $this->info("✓ No old-format index numbers found. All assigned students follow standard format.");
        }
        $this->newLine();

        // Table 3: Unassigned Students Breakdown by Grade
        $this->info("--- Students Without Index Numbers (Total: {$unassignedStudents}) ---");
        if ($unassignedStudents > 0) {
            $unassignedByGrade = (clone $studentsQuery)
                ->where(function ($q) {
                    $q->whereNull('index_number')->orWhere('index_number', '');
                })
                ->selectRaw("COALESCE(NULLIF(current_grade, ''), 'Unspecified') as grade, count(*) as total")
                ->groupBy('grade')
                ->orderBy('total', 'desc')
                ->get();

            $unassignedHeaders = ['Grade', 'Unassigned Count'];
            $unassignedRows = [];
            foreach ($unassignedByGrade as $item) {
                $unassignedRows[] = [$item->grade, $item->total];
            }
            $this->table($unassignedHeaders, $unassignedRows);
        } else {
            $this->info("✓ All students currently have index numbers assigned.");
        }
        $this->newLine();

        // Summary
        $this->info("================================================================================");
        $this->line("Summary Totals:");
        $this->line("- Total Students       : {$totalStudents}");
        $this->line("- Assigned (Standard)  : " . ($assignedStudents->count() - count($oldFormatStudents)));
        $this->line("- Assigned (Old Format): " . count($oldFormatStudents));
        $this->line("- Without Numbers      : {$unassignedStudents}");
        $this->info("================================================================================");

        return 0;
    }
}
