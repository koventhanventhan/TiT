<?php

namespace App\Console\Commands;

use App\Services\StudentIndexService;
use Illuminate\Console\Command;

class ImportStudentIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:import-index
                            {file : Path to CSV file}
                            {--dry-run : Simulate and preview without modifying the database}
                            {--abort-on-error : Abort the entire import if any row fails validation}
                            {--institute=1 : Institute ID (default 1)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import existing student index numbers from CSV into database and advance sequences';

    /**
     * Execute the console command.
     */
    public function handle(StudentIndexService $indexService): int
    {
        $filePath = $this->argument('file');
        $isDryRun = $this->option('dry-run');
        $abortOnError = $this->option('abort-on-error');
        $instituteId = (int) ($this->option('institute') ?: 1);

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = null;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (!$headers) {
                    $headers = array_map(function ($h) {
                        return strtolower(trim(preg_replace('/[\xEF\xBB\xBF]/', '', (string) $h)));
                    }, $data);
                    continue;
                }

                if (empty(array_filter($data, fn($v) => trim((string)$v) !== ''))) {
                    continue;
                }

                $row = [];
                foreach ($headers as $idx => $header) {
                    $row[$header] = isset($data[$idx]) ? trim((string) $data[$idx]) : '';
                }
                $rows[] = $row;
            }
            fclose($handle);
        }

        if (empty($rows)) {
            $this->error("CSV file is empty or contains no data rows.");
            return Command::FAILURE;
        }

        $this->info(($isDryRun ? "[DRY RUN] " : "") . "Parsed " . count($rows) . " row(s) from CSV.");

        $validation = $indexService->previewAndValidateImport($rows, $instituteId);

        $tableHeaders = ['Row', 'Student ID', 'Name', 'Index Number', 'Status', 'Notes'];
        $tableRows = [];

        foreach ($validation['rows'] as $r) {
            $tableRows[] = [
                $r['row_number'],
                $r['student_id'] ?? 'N/A',
                $r['student_name'] ?? 'N/A',
                $r['index_number'] ?? 'N/A',
                strtoupper($r['status']),
                $r['status'] === 'valid' ? 'OK' : $r['error_message'],
            ];
        }

        $this->table($tableHeaders, $tableRows);

        $this->line("\nSummary: Total: {$validation['total']}, Valid: {$validation['valid_count']}, Errors: {$validation['error_count']}");

        if ($isDryRun) {
            $this->warn("\nDry run completed. No database changes were made.");
            if ($abortOnError && $validation['has_errors']) {
                $this->error("Validation failed: --abort-on-error specified and errors were found.");
                return Command::FAILURE;
            }
            return Command::SUCCESS;
        }

        if ($abortOnError && $validation['has_errors']) {
            $this->error("\nImport aborted: some rows have errors and --abort-on-error was specified.");
            return Command::FAILURE;
        }

        if ($validation['valid_count'] === 0) {
            $this->error("\nNo valid rows to import.");
            return Command::FAILURE;
        }

        try {
            $importResult = $indexService->executeImport($validation['rows'], $instituteId, (bool) $abortOnError);
            $this->info("\nImport successful: {$importResult['imported_count']} imported, {$importResult['skipped_count']} skipped.");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("\nImport failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
