<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\SiteSetting;
use App\Models\StudentIndexCounter;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentIndexService
{
    public const SETTING_GRADE_BATCH_MAP = 'grade_batch_mapping';

    /**
     * Retrieve grade-to-batch-year mapping for an institute.
     *
     * @param int|null $instituteId
     * @return array<string, int>
     */
    public function getGradeBatchMapping(?int $instituteId = null): array
    {
        $raw = SiteSetting::getForInstitute(self::SETTING_GRADE_BATCH_MAP, $instituteId, '{}');
        $decoded = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
        if (!is_array($decoded)) {
            return [];
        }

        $cleaned = [];
        foreach ($decoded as $grade => $batch) {
            if ($batch === null || $batch === '') {
                continue;
            }
            try {
                $cleaned[(string) $grade] = $this->normalizeBatchYear($batch);
            } catch (\InvalidArgumentException $e) {
                // Ignore invalid entries in storage
            }
        }

        return $cleaned;
    }

    /**
     * Save grade-to-batch-year mapping for an institute.
     *
     * @param array $mapping
     * @param int|null $instituteId
     * @return array<string, int>
     * @throws \InvalidArgumentException
     */
    public function setGradeBatchMapping(array $mapping, ?int $instituteId = null): array
    {
        $validated = [];
        foreach ($mapping as $grade => $batch) {
            $gradeKey = trim((string) $grade);
            if ($gradeKey === '') {
                continue;
            }
            if ($batch === null || $batch === '') {
                continue;
            }

            // Must be valid 20..99
            $validated[$gradeKey] = $this->normalizeBatchYear($batch);
        }

        SiteSetting::setForInstitute(
            self::SETTING_GRADE_BATCH_MAP,
            json_encode($validated),
            $instituteId,
            'students'
        );

        return $validated;
    }

    /**
     * Resolve a student's grade against a given mapping array.
     * Returns 2-digit batch year or null if unmapped.
     *
     * @param string|int|null $grade
     * @param array $mapping
     * @return int|null
     */
    public function resolveBatchForGrade($grade, array $mapping): ?int
    {
        if ($grade === null || $grade === '') {
            return null;
        }

        $gradeStr = trim((string) $grade);

        // 1. Direct match on raw string
        if (isset($mapping[$gradeStr])) {
            return $this->normalizeBatchYear($mapping[$gradeStr]);
        }

        // 2. Numeric extraction: e.g. "Grade 10", "Grade 04", "10", "grade-5"
        if (preg_match('/(\d+)/', $gradeStr, $m)) {
            $num = (string) ((int) $m[1]);
            if (isset($mapping[$num])) {
                return $this->normalizeBatchYear($mapping[$num]);
            }
            if (isset($mapping["Grade {$num}"])) {
                return $this->normalizeBatchYear($mapping["Grade {$num}"]);
            }
            if (isset($mapping["Grade " . str_pad($num, 2, '0', STR_PAD_LEFT)])) {
                return $this->normalizeBatchYear($mapping["Grade " . str_pad($num, 2, '0', STR_PAD_LEFT)]);
            }
        }

        // 3. Case-insensitive key scan
        foreach ($mapping as $mapKey => $mapBatch) {
            if (strcasecmp(trim((string) $mapKey), $gradeStr) === 0) {
                return $this->normalizeBatchYear($mapBatch);
            }
        }

        return null;
    }

    /**
     * Get batch year for a grade using the institute's saved mapping.
     *
     * @param string|int|null $grade
     * @param int|null $instituteId
     * @return int|null
     */
    public function getBatchForGrade($grade, ?int $instituteId = null): ?int
    {
        $mapping = $this->getGradeBatchMapping($instituteId);
        return $this->resolveBatchForGrade($grade, $mapping);
    }

    /**
     * Normalize and validate a batch year into a 2-digit integer (20..99).
     *
     * @param mixed $batchYear
     * @return int
     * @throws \InvalidArgumentException
     */
    public function normalizeBatchYear($batchYear): int
    {
        if (is_string($batchYear)) {
            $batchYear = trim($batchYear);
        }

        $intYear = (int) $batchYear;

        // If 4-digit year like 2028, convert to 28
        if ($intYear >= 2000 && $intYear <= 2099) {
            $intYear = $intYear % 100;
        }

        if ($intYear < 20 || $intYear > 99) {
            throw new \InvalidArgumentException("Batch year must be between 20 and 99, got: {$batchYear}");
        }

        return $intYear;
    }

    /**
     * Return student's existing batch_year if set, otherwise try grade mapping, otherwise null.
     * Never falls back to the current year.
     *
     * @param User|null $student
     * @return int|null
     */
    public function calculateDefaultBatchYear(?User $student = null): ?int
    {
        if ($student && !empty($student->batch_year)) {
            try {
                return $this->normalizeBatchYear($student->batch_year);
            } catch (\InvalidArgumentException $e) {
                // ignore
            }
        }

        if ($student && !empty($student->current_grade)) {
            $mapped = $this->getBatchForGrade($student->current_grade, $student->institute_id);
            if ($mapped !== null) {
                return $mapped;
            }
        }

        return null;
    }

    /**
     * Assign a unique student index number in format <PREFIX>/<BATCH YY>/<SEQ>.
     *
     * @param User $student
     * @param int|string $batchYear
     * @return string
     * @throws \InvalidArgumentException
     * @throws \Throwable
     */
    public function assign(User $student, $batchYear): string
    {
        // Idempotent: return unchanged if already assigned
        if (!empty($student->index_number)) {
            return $student->index_number;
        }

        $batchYear = $this->normalizeBatchYear($batchYear);
        $instituteId = (int) ($student->institute_id ?: 1);

        $institute = Institute::find($instituteId);
        $prefix = $institute?->index_prefix ?: 'TiT';
        $seqStart = (int) ($institute?->index_seq_start ?: 100);

        $attempts = 0;
        $maxAttempts = 2;

        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                return DB::transaction(function () use ($student, $instituteId, $batchYear, $prefix, $seqStart) {
                    // Lock student row to prevent concurrent assignment
                    $lockedStudent = User::where('id', $student->id)->lockForUpdate()->first();
                    if (!$lockedStudent) {
                        throw new \RuntimeException("Student record #{$student->id} not found.");
                    }

                    if (!empty($lockedStudent->index_number)) {
                        $student->batch_year = $lockedStudent->batch_year;
                        $student->index_seq = $lockedStudent->index_seq;
                        $student->index_number = $lockedStudent->index_number;
                        return $lockedStudent->index_number;
                    }

                    // Lock or create counter row for (institute_id, batch_year)
                    $counter = StudentIndexCounter::where('institute_id', $instituteId)
                        ->where('batch_year', $batchYear)
                        ->lockForUpdate()
                        ->first();

                    if (!$counter) {
                        try {
                            $counter = StudentIndexCounter::create([
                                'institute_id' => $instituteId,
                                'batch_year' => $batchYear,
                                'last_seq' => max(0, $seqStart - 1),
                            ]);
                        } catch (QueryException $qe) {
                            // In case of a concurrent race where another process created it
                            $counter = StudentIndexCounter::where('institute_id', $instituteId)
                                ->where('batch_year', $batchYear)
                                ->lockForUpdate()
                                ->firstOrFail();
                        }
                    }

                    $nextSeq = max((int) $counter->last_seq + 1, $seqStart);
                    $paddedSeq = str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
                    $indexNumber = "{$prefix}/{$batchYear}/{$paddedSeq}";

                    // Increment and persist counter
                    $counter->last_seq = $nextSeq;
                    $counter->save();

                    // Persist student
                    $lockedStudent->update([
                        'institute_id' => $instituteId,
                        'batch_year' => $batchYear,
                        'index_seq' => $nextSeq,
                        'index_number' => $indexNumber,
                    ]);

                    $student->institute_id = $instituteId;
                    $student->batch_year = $batchYear;
                    $student->index_seq = $nextSeq;
                    $student->index_number = $indexNumber;

                    return $indexNumber;
                }, 3);
            } catch (QueryException $e) {
                // If unique constraint collided, retry on the next attempt
                if ($attempts >= $maxAttempts) {
                    Log::error("Failed assigning student index number after {$attempts} attempts", [
                        'student_id' => $student->id,
                        'institute_id' => $instituteId,
                        'batch_year' => $batchYear,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        }

        throw new \RuntimeException("Unable to assign student index number.");
    }

    /**
     * Preview upcoming index numbers without incrementing the counter.
     *
     * @param int $instituteId
     * @param int|string $batchYear
     * @param int $count
     * @return array<string>
     */
    public function previewNext(int $instituteId, $batchYear, int $count = 1): array
    {
        $batchYear = $this->normalizeBatchYear($batchYear);
        $institute = Institute::find($instituteId);
        $prefix = $institute?->index_prefix ?: 'TiT';
        $seqStart = (int) ($institute?->index_seq_start ?: 100);

        $counter = StudentIndexCounter::where('institute_id', $instituteId)
            ->where('batch_year', $batchYear)
            ->first();

        $startSeq = $counter ? max((int) $counter->last_seq + 1, $seqStart) : $seqStart;

        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $seq = $startSeq + $i;
            $paddedSeq = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            $results[] = "{$prefix}/{$batchYear}/{$paddedSeq}";
        }

        return $results;
    }

    /**
     * Re-assign an index number to a student (creates activity log).
     *
     * @param User $student
     * @param int|string $newBatchYear
     * @param User|int|string|null $adminUser
     * @param string|null $reason
     * @return string
     * @throws \Throwable
     */
    public function reassign(User $student, $newBatchYear, $adminUser = null, ?string $reason = null): string
    {
        $oldIndex = $student->index_number;
        $oldBatch = $student->batch_year;

        // Temporarily reset to allow new assignment
        $student->index_number = null;
        $student->index_seq = null;
        $student->batch_year = null;
        $student->save();

        $newIndex = $this->assign($student, $newBatchYear);

        $adminUserId = null;
        if ($adminUser instanceof User) {
            $adminUserId = $adminUser->id;
        } elseif (is_numeric($adminUser)) {
            $adminUserId = (int) $adminUser;
        } else {
            $adminUserId = auth()->id();
        }

        ActivityLog::create([
            'institute_id' => $student->institute_id ?: 1,
            'user_id' => $adminUserId,
            'action' => 'student_index_reassigned',
            'description' => "Reassigned student index from {$oldIndex} (batch {$oldBatch}) to {$newIndex} for student #{$student->id} ({$student->full_name})",
            'metadata' => [
                'student_id' => $student->id,
                'old_index_number' => $oldIndex,
                'new_index_number' => $newIndex,
                'old_batch_year' => $oldBatch,
                'new_batch_year' => $student->batch_year,
                'reason' => $reason,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return $newIndex;
    }

    /**
     * Parse index number string into parts (prefix, batch_year, index_seq).
     * Format: ^([A-Za-z0-9]+)\/(\d{2})\/(\d{3,})$
     *
     * @param string|null $indexNumber
     * @return array{prefix: string, batch_year: int, index_seq: int, normalized: string}|null
     */
    public function parseIndexNumber(?string $indexNumber): ?array
    {
        if ($indexNumber === null) {
            return null;
        }
        $trimmed = trim($indexNumber);
        if (!preg_match('/^([A-Za-z0-9]+)\/(\d{2})\/(\d{3,})$/', $trimmed, $matches)) {
            return null;
        }

        return [
            'prefix' => $matches[1],
            'batch_year' => (int) $matches[2],
            'index_seq' => (int) $matches[3],
            'normalized' => "{$matches[1]}/{$matches[2]}/{$matches[3]}",
        ];
    }

    /**
     * Find a student in an institute by student_id, username, phone, or email (in priority order).
     *
     * @param array $row Associative array with lowercase header keys
     * @param int $instituteId
     * @return User|null
     */
    public function findStudentForImport(array $row, int $instituteId): ?User
    {
        // 1. student_id
        $studentId = $row['student_id'] ?? $row['id'] ?? null;
        if (!empty($studentId) && is_numeric($studentId)) {
            $student = User::withoutGlobalScopes()
                ->where('role', 'user')
                ->where('institute_id', $instituteId)
                ->where('id', (int) $studentId)
                ->first();
            if ($student) {
                return $student;
            }
        }

        // 2. username
        $username = $row['username'] ?? $row['user_name'] ?? null;
        if (!empty($username)) {
            $student = User::withoutGlobalScopes()
                ->where('role', 'user')
                ->where('institute_id', $instituteId)
                ->where('name', trim((string) $username))
                ->first();
            if ($student) {
                return $student;
            }
        }

        // 3. phone / email
        $phoneOrEmail = $row['phone'] ?? $row['phone_number'] ?? $row['email'] ?? null;
        if (!empty($phoneOrEmail)) {
            $val = trim((string) $phoneOrEmail);
            if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
                $student = User::withoutGlobalScopes()
                    ->where('role', 'user')
                    ->where('institute_id', $instituteId)
                    ->where('email', $val)
                    ->first();
                if ($student) {
                    return $student;
                }
            } else {
                $digits = preg_replace('/\D/', '', $val);
                if (strlen($digits) == 10 && str_starts_with($digits, '0')) {
                    $digits = '94' . substr($digits, 1);
                } elseif (strlen($digits) == 9 && str_starts_with($digits, '7')) {
                    $digits = '94' . $digits;
                }
                $student = User::withoutGlobalScopes()
                    ->where('role', 'user')
                    ->where('institute_id', $instituteId)
                    ->where(function ($q) use ($val, $digits) {
                        $q->where('phone_number', $val);
                        if (!empty($digits)) {
                            $q->orWhere('phone_number', $digits);
                        }
                    })
                    ->first();
                if ($student) {
                    return $student;
                }
            }
        }

        return null;
    }

    /**
     * Parse and validate CSV rows for index import.
     *
     * @param array $rawRows List of associative arrays with columns
     * @param int $instituteId
     * @return array
     */
    public function previewAndValidateImport(array $rawRows, int $instituteId): array
    {
        $seenIndexNumbers = [];
        $seenStudentIds = [];
        $rows = [];
        $validCount = 0;
        $errorCount = 0;

        foreach ($rawRows as $idx => $rawRow) {
            $rowNum = $idx + 1;
            $row = [];
            foreach ($rawRow as $k => $v) {
                $row[strtolower(trim((string) $k))] = is_string($v) ? trim($v) : $v;
            }

            $rawIndex = $row['index_number'] ?? $row['index'] ?? null;
            $student = $this->findStudentForImport($row, $instituteId);

            $status = 'valid';
            $errors = [];
            $parsedIndex = null;

            if (empty($rawIndex)) {
                $status = 'error';
                $errors[] = 'Index number is missing';
            } else {
                $parsedIndex = $this->parseIndexNumber($rawIndex);
                if (!$parsedIndex) {
                    $status = 'error';
                    $errors[] = "Invalid index number format '{$rawIndex}' (must match PREFIX/YY/SEQ e.g. TiT/28/105)";
                } else {
                    $canonicalIndex = $parsedIndex['normalized'];
                    if (isset($seenIndexNumbers[$canonicalIndex])) {
                        $status = 'error';
                        $errors[] = "Duplicate index number '{$rawIndex}' in CSV (also in row {$seenIndexNumbers[$canonicalIndex]})";
                    } else {
                        $seenIndexNumbers[$canonicalIndex] = $rowNum;
                    }

                    // Check if already assigned to ANOTHER student in DB
                    $existingUserWithIndex = User::withoutGlobalScopes()
                        ->where('institute_id', $instituteId)
                        ->where('index_number', $canonicalIndex)
                        ->first();
                    if ($existingUserWithIndex && (!$student || $existingUserWithIndex->id !== $student->id)) {
                        $status = 'error';
                        $errors[] = "Index number '{$canonicalIndex}' is already assigned to student #{$existingUserWithIndex->id} ({$existingUserWithIndex->full_name})";
                    }
                }
            }

            if (!$student) {
                $status = 'error';
                $errors[] = 'Student not found by ID, username, phone, or email';
            } else {
                if (isset($seenStudentIds[$student->id])) {
                    $status = 'error';
                    $errors[] = "Student #{$student->id} appears multiple times in CSV";
                } else {
                    $seenStudentIds[$student->id] = $rowNum;
                }

                if (!empty($student->index_number)) {
                    $status = 'error';
                    $errors[] = "Student already has index number: {$student->index_number}";
                }
            }

            if ($status === 'valid') {
                $validCount++;
            } else {
                $errorCount++;
            }

            $rows[] = [
                'row_number' => $rowNum,
                'student_id' => $student?->id,
                'student_name' => $student ? ($student->full_name ?? $student->name) : ($row['name'] ?? 'Unknown'),
                'current_grade' => $student?->current_grade,
                'index_number' => $parsedIndex['normalized'] ?? $rawIndex,
                'batch_year' => $parsedIndex['batch_year'] ?? null,
                'index_seq' => $parsedIndex['index_seq'] ?? null,
                'status' => $status,
                'errors' => $errors,
                'error_message' => implode('; ', $errors),
            ];
        }

        return [
            'total' => count($rawRows),
            'valid_count' => $validCount,
            'error_count' => $errorCount,
            'has_errors' => $errorCount > 0,
            'rows' => $rows,
        ];
    }

    /**
     * Execute the index import for validated rows.
     *
     * @param array $validatedRows Rows array from previewAndValidateImport
     * @param int $instituteId
     * @param bool $abortOnError If true, fails entire import if any error row exists
     * @param int|null $actingAdminId
     * @return array Summary of import
     * @throws \RuntimeException
     */
    public function executeImport(array $validatedRows, int $instituteId, bool $abortOnError = false, ?int $actingAdminId = null): array
    {
        $hasErrors = collect($validatedRows)->contains('status', 'error');
        if ($abortOnError && $hasErrors) {
            throw new \RuntimeException("Import aborted: some rows have validation errors.");
        }

        return DB::transaction(function () use ($validatedRows, $instituteId, $actingAdminId) {
            $importedCount = 0;
            $skippedCount = 0;
            $maxSeqPerBatch = [];
            $results = [];

            foreach ($validatedRows as $row) {
                if ($row['status'] !== 'valid') {
                    $skippedCount++;
                    $results[] = array_merge($row, [
                        'import_status' => 'skipped',
                        'result_message' => $row['error_message'] ?: 'Skipped',
                    ]);
                    continue;
                }

                $student = User::withoutGlobalScopes()
                    ->where('id', $row['student_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$student) {
                    $skippedCount++;
                    $results[] = array_merge($row, [
                        'import_status' => 'skipped',
                        'result_message' => 'Student record vanished',
                    ]);
                    continue;
                }

                if (!empty($student->index_number)) {
                    $skippedCount++;
                    $results[] = array_merge($row, [
                        'import_status' => 'skipped',
                        'result_message' => "Already assigned: {$student->index_number}",
                    ]);
                    continue;
                }

                $batchYear = (int) $row['batch_year'];
                $seq = (int) $row['index_seq'];
                $indexNumber = $row['index_number'];

                $student->update([
                    'batch_year' => $batchYear,
                    'index_seq' => $seq,
                    'index_number' => $indexNumber,
                ]);

                if (!isset($maxSeqPerBatch[$batchYear]) || $seq > $maxSeqPerBatch[$batchYear]) {
                    $maxSeqPerBatch[$batchYear] = $seq;
                }

                $importedCount++;
                $results[] = array_merge($row, [
                    'import_status' => 'imported',
                    'result_message' => 'Success',
                ]);
            }

            // CRITICAL: Advance the counter to max(existing last_seq, highest imported seq) for each batch
            $institute = Institute::find($instituteId);
            $seqStart = (int) ($institute?->index_seq_start ?: 100);

            foreach ($maxSeqPerBatch as $batch => $highestImportedSeq) {
                $counter = StudentIndexCounter::where('institute_id', $instituteId)
                    ->where('batch_year', $batch)
                    ->lockForUpdate()
                    ->first();

                if (!$counter) {
                    StudentIndexCounter::create([
                        'institute_id' => $instituteId,
                        'batch_year' => $batch,
                        'last_seq' => max($highestImportedSeq, $seqStart - 1),
                    ]);
                } else {
                    if ($highestImportedSeq > $counter->last_seq) {
                        $counter->last_seq = $highestImportedSeq;
                        $counter->save();
                    }
                }
            }

            // Activity Log
            ActivityLogService::log(
                'student_index_imported',
                "Imported {$importedCount} existing student index numbers from CSV",
                [
                    'imported_count' => $importedCount,
                    'skipped_count' => $skippedCount,
                    'batches_affected' => $maxSeqPerBatch,
                ]
            );

            return [
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'results' => $results,
            ];
        });
    }

    /**
     * Compute a deterministic preview of bulk index number assignment.
     * Guaranteed to match the sequence of numbers assigned in real run.
     *
     * @param iterable<User> $students
     * @param int $instituteId
     * @param string $batchMode 'mapping'|'single'
     * @param int|null $singleBatchYear
     * @param array $mapping
     * @return array
     */
    public function previewBulkAssign(
        iterable $students,
        int $instituteId,
        string $batchMode = 'mapping',
        ?int $singleBatchYear = null,
        array $mapping = []
    ): array {
        $institute = Institute::find($instituteId);
        $prefix = $institute?->index_prefix ?: 'TiT';
        $seqStart = (int) ($institute?->index_seq_start ?: 100);

        // Preload DB counters for institute
        $dbCounters = StudentIndexCounter::where('institute_id', $instituteId)
            ->pluck('last_seq', 'batch_year')
            ->toArray();

        // In-memory simulated counters per batch_year
        $simulatedSeqs = [];

        $previewRows = [];
        $skipped = [];
        $gradeGroupSummaries = [];
        $unmappedGradesCount = [];

        foreach ($students as $student) {
            // Already assigned?
            if (!empty($student->index_number)) {
                continue;
            }

            // Determine batch year
            $batchYear = null;
            if ($batchMode === 'single' && $singleBatchYear) {
                try {
                    $batchYear = $this->normalizeBatchYear($singleBatchYear);
                } catch (\InvalidArgumentException $e) {
                    $batchYear = null;
                }
            } else {
                $batchYear = $this->resolveBatchForGrade($student->current_grade, $mapping);
            }

            if ($batchYear === null) {
                $g = $student->current_grade ?: 'Unknown';
                $unmappedGradesCount[$g] = ($unmappedGradesCount[$g] ?? 0) + 1;
                $skipped[] = [
                    'student_id' => $student->id,
                    'name' => $student->full_name ?? $student->name,
                    'grade' => $g,
                    'reason' => "No batch year for Grade {$g}",
                ];
                continue;
            }

            // Initialize simulated counter if not set yet
            if (!isset($simulatedSeqs[$batchYear])) {
                $currentLastSeq = isset($dbCounters[$batchYear]) ? (int) $dbCounters[$batchYear] : 0;
                $simulatedSeqs[$batchYear] = max($currentLastSeq + 1, $seqStart);
            }

            $seq = $simulatedSeqs[$batchYear]++;
            $paddedSeq = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            $indexNumber = "{$prefix}/{$batchYear}/{$paddedSeq}";

            $g = $student->current_grade ?: 'Unknown';
            if (!isset($gradeGroupSummaries[$g])) {
                $gradeGroupSummaries[$g] = [
                    'grade' => $g,
                    'batch_year' => $batchYear,
                    'count' => 0,
                    'first_index' => $indexNumber,
                    'last_index' => $indexNumber,
                    'first_number' => $indexNumber,
                    'last_number' => $indexNumber,
                ];
            }
            $gradeGroupSummaries[$g]['count']++;
            $gradeGroupSummaries[$g]['last_index'] = $indexNumber;
            $gradeGroupSummaries[$g]['last_number'] = $indexNumber;

            $previewRows[] = [
                'student_id' => $student->id,
                'name' => $student->full_name ?? $student->name,
                'grade' => $g,
                'batch' => $batchYear,
                'index_number' => $indexNumber,
            ];
        }

        return [
            'total_students' => count($previewRows),
            'skipped_count' => count($skipped),
            'preview_rows' => $previewRows,
            'skipped' => $skipped,
            'unmapped_grades' => $unmappedGradesCount,
            'grade_summaries' => array_values($gradeGroupSummaries),
        ];
    }
}
