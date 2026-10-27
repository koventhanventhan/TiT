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

        if ($intYear < 1 || $intYear > 99) {
            throw new \InvalidArgumentException("Batch year must be between 01 and 99, got: {$batchYear}");
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
     * Derive 2-digit grade series (e.g. '08', '10') from grade string.
     * Parses "Grade 8", "Grade 08", "8", "Grade 10" -> "08", "10".
     * Unknown/non-numeric grades such as "A/L" throw a clear exception.
     *
     * @param string|int|null $grade
     * @return string
     * @throws \InvalidArgumentException
     */
    public function deriveGradeSeries($grade): string
    {
        if ($grade === null || trim((string) $grade) === '') {
            throw new \InvalidArgumentException("Cannot derive grade series from '' – set the series manually");
        }

        $gradeStr = trim((string) $grade);
        if (preg_match('/^(?:grade[\s\-]*)?0*([1-9]|1[0-3])$/i', $gradeStr, $m)) {
            return str_pad((string) ((int) $m[1]), 2, '0', STR_PAD_LEFT);
        }

        throw new \InvalidArgumentException("Cannot derive grade series from '{$gradeStr}' – set the series manually");
    }

    /**
     * Normalize an explicit grade series into 2 digits (e.g. 8 -> '08', '10' -> '10').
     *
     * @param mixed $series
     * @return string
     * @throws \InvalidArgumentException
     */
    public function normalizeGradeSeries($series): string
    {
        if ($series === null || trim((string) $series) === '') {
            throw new \InvalidArgumentException("Series must be a number 01–99");
        }

        $trimmed = trim((string) $series);
        if (preg_match('/^(?:grade\s*)?0*([1-9]|[1-9][0-9])$/i', $trimmed, $m)) {
            $num = (int) $m[1];
            if ($num >= 1 && $num <= 99) {
                return str_pad((string) $num, 2, '0', STR_PAD_LEFT);
            }
        }

        throw new \InvalidArgumentException("Series must be a number 01–99");
    }

    /**
     * Assign a unique student index number.
     * In 'grade' mode: <PREFIX>/<GG>/<NNN> e.g. TIT/08/001.
     * In 'batch' mode: <PREFIX>/<BATCH YY>/<SEQ> e.g. TiT/28/100.
     *
     * @param User $student
     * @param int|string|null $seriesOrBatch
     * @return string
     * @throws \InvalidArgumentException
     * @throws \Throwable
     */
    public function assign(User $student, $seriesOrBatch = null, ?int $exactSeq = null): string
    {
        // Idempotent: return unchanged if already assigned
        if (!empty($student->index_number)) {
            return $student->index_number;
        }

        $instituteId = (int) ($student->institute_id ?: 1);
        $institute = Institute::find($instituteId);
        $format = $institute?->getIndexFormat() ?? 'grade';

        if ($format === 'grade') {
            $prefix = $institute?->getIndexPrefix() ?? 'TIT';
            $seqStart = $institute?->getIndexSeqStart() ?? 1;

            if ($seriesOrBatch !== null && trim((string) $seriesOrBatch) !== '') {
                $seriesStr = $this->normalizeGradeSeries($seriesOrBatch);
            } else {
                $seriesStr = $this->deriveGradeSeries($student->current_grade);
            }
            $seriesInt = (int) $seriesStr;

            if ($seriesInt < 1 || $seriesInt > 99) {
                throw new \InvalidArgumentException("Series must be a number 01–99");
            }

            $attempts = 0;
            $maxAttempts = 2;

            while ($attempts < $maxAttempts) {
                $attempts++;
                try {
                    return DB::transaction(function () use ($student, $instituteId, $seriesInt, $seriesStr, $prefix, $seqStart, $exactSeq) {
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

                        $counter = StudentIndexCounter::where('institute_id', $instituteId)
                            ->where('batch_year', $seriesInt)
                            ->lockForUpdate()
                            ->first();

                        if ($exactSeq !== null && (int) $exactSeq > 0) {
                            $nextSeq = (int) $exactSeq;
                            $paddedSeq = str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
                            $indexNumber = "{$prefix}/{$seriesStr}/{$paddedSeq}";

                            // Exact number must be unique per institute
                            $conflict = User::withoutGlobalScopes()
                                ->where('institute_id', $instituteId)
                                ->where('index_number', $indexNumber)
                                ->where('id', '!=', $student->id)
                                ->first();

                            if ($conflict) {
                                $conflictName = $conflict->full_name ?: ($conflict->name ?: "Student #{$conflict->id}");
                                throw new \InvalidArgumentException("{$indexNumber} is already used by {$conflictName}");
                            }

                            if (!$counter) {
                                try {
                                    $counter = StudentIndexCounter::create([
                                        'institute_id' => $instituteId,
                                        'batch_year' => $seriesInt,
                                        'last_seq' => $nextSeq,
                                    ]);
                                } catch (QueryException $qe) {
                                    $counter = StudentIndexCounter::where('institute_id', $instituteId)
                                        ->where('batch_year', $seriesInt)
                                        ->lockForUpdate()
                                        ->firstOrFail();
                                    $counter->last_seq = max((int) $counter->last_seq, $nextSeq);
                                    $counter->save();
                                }
                            } else {
                                $counter->last_seq = max((int) $counter->last_seq, $nextSeq);
                                $counter->save();
                            }
                        } else {
                            if (!$counter) {
                                try {
                                    $counter = StudentIndexCounter::create([
                                        'institute_id' => $instituteId,
                                        'batch_year' => $seriesInt,
                                        'last_seq' => max(0, $seqStart - 1),
                                    ]);
                                } catch (QueryException $qe) {
                                    $counter = StudentIndexCounter::where('institute_id', $instituteId)
                                        ->where('batch_year', $seriesInt)
                                        ->lockForUpdate()
                                        ->firstOrFail();
                                }
                            }

                            $nextSeq = (int) $counter->last_seq + 1;
                            $paddedSeq = str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
                            $indexNumber = "{$prefix}/{$seriesStr}/{$paddedSeq}";

                            $counter->last_seq = $nextSeq;
                            $counter->save();
                        }

                        $lockedStudent->update([
                            'institute_id' => $instituteId,
                            'batch_year' => $seriesInt,
                            'index_seq' => $nextSeq,
                            'index_number' => $indexNumber,
                        ]);

                        $student->institute_id = $instituteId;
                        $student->batch_year = $seriesInt;
                        $student->index_seq = $nextSeq;
                        $student->index_number = $indexNumber;

                        return $indexNumber;
                    }, 3);
                } catch (QueryException $e) {
                    if ($attempts >= $maxAttempts) {
                        Log::error("Failed assigning student index number after {$attempts} attempts", [
                            'student_id' => $student->id,
                            'institute_id' => $instituteId,
                            'series' => $seriesStr,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e;
                    }
                }
            }

            throw new \RuntimeException("Unable to assign student index number.");
        }

        // Legacy batch mode
        $batchYear = $this->normalizeBatchYear($seriesOrBatch ?? $this->calculateDefaultBatchYear($student));
        $prefix = $institute?->index_prefix ?: 'TiT';
        $seqStart = (int) ($institute?->index_seq_start ?: 100);

        $attempts = 0;
        $maxAttempts = 2;

        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                return DB::transaction(function () use ($student, $instituteId, $batchYear, $prefix, $seqStart, $exactSeq) {
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

                    if ($exactSeq !== null && (int) $exactSeq > 0) {
                        $nextSeq = (int) $exactSeq;
                        $paddedSeq = str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
                        $seriesStr = str_pad((string) $batchYear, 2, '0', STR_PAD_LEFT);
                        $indexNumber = "{$prefix}/{$seriesStr}/{$paddedSeq}";

                        $conflict = User::withoutGlobalScopes()
                            ->where('institute_id', $instituteId)
                            ->where('index_number', $indexNumber)
                            ->where('id', '!=', $student->id)
                            ->first();

                        if ($conflict) {
                            $conflictName = $conflict->full_name ?: ($conflict->name ?: "Student #{$conflict->id}");
                            throw new \InvalidArgumentException("{$indexNumber} is already used by {$conflictName}");
                        }

                        if (!$counter) {
                            try {
                                $counter = StudentIndexCounter::create([
                                    'institute_id' => $instituteId,
                                    'batch_year' => $batchYear,
                                    'last_seq' => $nextSeq,
                                ]);
                            } catch (QueryException $qe) {
                                $counter = StudentIndexCounter::where('institute_id', $instituteId)
                                    ->where('batch_year', $batchYear)
                                    ->lockForUpdate()
                                    ->firstOrFail();
                                $counter->last_seq = max((int) $counter->last_seq, $nextSeq);
                                $counter->save();
                            }
                        } else {
                            $counter->last_seq = max((int) $counter->last_seq, $nextSeq);
                            $counter->save();
                        }
                    } else {
                        if (!$counter) {
                            try {
                                $counter = StudentIndexCounter::create([
                                    'institute_id' => $instituteId,
                                    'batch_year' => $batchYear,
                                    'last_seq' => max(0, $seqStart - 1),
                                ]);
                            } catch (QueryException $qe) {
                                $counter = StudentIndexCounter::where('institute_id', $instituteId)
                                    ->where('batch_year', $batchYear)
                                    ->lockForUpdate()
                                    ->firstOrFail();
                            }
                        }

                        $nextSeq = (int) $counter->last_seq + 1;
                        $paddedSeq = str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
                        $seriesStr = str_pad((string) $batchYear, 2, '0', STR_PAD_LEFT);
                        $indexNumber = "{$prefix}/{$seriesStr}/{$paddedSeq}";

                        $counter->last_seq = $nextSeq;
                        $counter->save();
                    }

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
     * @param int|string $seriesOrBatch
     * @param int $count
     * @return array<string>
     */
    public function previewNext(int $instituteId, $seriesOrBatch, int $count = 1): array
    {
        $institute = Institute::find($instituteId);
        $format = $institute?->getIndexFormat() ?? 'grade';

        if ($format === 'grade') {
            $seriesStr = $this->normalizeGradeSeries($seriesOrBatch);
            $seriesInt = (int) $seriesStr;
            $prefix = $institute?->getIndexPrefix() ?? 'TIT';
            $seqStart = $institute?->getIndexSeqStart() ?? 1;

            $counter = StudentIndexCounter::where('institute_id', $instituteId)
                ->where('batch_year', $seriesInt)
                ->first();

            $startSeq = $counter ? ((int) $counter->last_seq + 1) : $seqStart;

            $results = [];
            for ($i = 0; $i < $count; $i++) {
                $seq = $startSeq + $i;
                $paddedSeq = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                $results[] = "{$prefix}/{$seriesStr}/{$paddedSeq}";
            }

            return $results;
        }

        $batchYear = $this->normalizeBatchYear($seriesOrBatch);
        $prefix = $institute?->index_prefix ?: 'TiT';
        $seqStart = (int) ($institute?->index_seq_start ?: 100);

        $counter = StudentIndexCounter::where('institute_id', $instituteId)
            ->where('batch_year', $batchYear)
            ->first();

        $startSeq = $counter ? ((int) $counter->last_seq + 1) : $seqStart;

        $results = [];
        $batchYearStr = str_pad((string) $batchYear, 2, '0', STR_PAD_LEFT);
        for ($i = 0; $i < $count; $i++) {
            $seq = $startSeq + $i;
            $paddedSeq = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            $results[] = "{$prefix}/{$batchYearStr}/{$paddedSeq}";
        }

        return $results;
    }

    /**
     * Re-assign an index number to a student (creates activity log).
     *
     * @param User $student
     * @param int|string|null $newSeriesOrBatch
     * @param User|int|string|null $adminUser
     * @param string|null $reason
     * @param int|null $exactSeq
     * @return string
     * @throws \Throwable
     */
    public function reassign(User $student, $newSeriesOrBatch = null, $adminUser = null, ?string $reason = null, ?int $exactSeq = null): string
    {
        $oldIndex = $student->index_number;
        $oldSeq = $student->index_seq;
        $oldBatch = $student->batch_year;

        try {
            // Temporarily reset to allow new assignment
            $student->index_number = null;
            $student->index_seq = null;
            $student->batch_year = null;
            $student->save();

            $newIndex = $this->assign($student, $newSeriesOrBatch, $exactSeq);
        } catch (\Throwable $e) {
            $student->index_number = $oldIndex;
            $student->index_seq = $oldSeq;
            $student->batch_year = $oldBatch;
            $student->save();
            throw $e;
        }

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
            'description' => "Reassigned student index from {$oldIndex} to {$newIndex} for student #{$student->id} ({$student->full_name})",
            'metadata' => [
                'student_id' => $student->id,
                'old_index_number' => $oldIndex,
                'new_index_number' => $newIndex,
                'old_batch_year' => $oldBatch,
                'new_batch_year' => $student->batch_year,
                'exact_seq' => $exactSeq,
                'reason' => $reason,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return $newIndex;
    }

    /**
     * Check if an index number matches the standard format: ^<PREFIX>/(\d{2})/(\d{3,})$
     * with series 01–99. If $expectedPrefix is provided, matches case-sensitive prefix.
     *
     * @param string|null $indexNumber
     * @param string|null $expectedPrefix
     * @return bool
     */
    public static function isValidIndexNumber(?string $indexNumber, ?string $expectedPrefix = null): bool
    {
        if ($indexNumber === null || trim((string) $indexNumber) === '') {
            return false;
        }

        $trimmed = trim((string) $indexNumber);

        if ($expectedPrefix !== null && $expectedPrefix !== '') {
            $escaped = preg_quote($expectedPrefix, '/');
            if (!preg_match('/^' . $escaped . '\/(\d{2})\/(\d{3,})$/', $trimmed, $m)) {
                return false;
            }
            $seriesInt = (int) $m[1];
            return $seriesInt >= 1 && $seriesInt <= 99;
        }

        if (!preg_match('/^([A-Za-z0-9]+)\/(\d{2})\/(\d{3,})$/', $trimmed, $m)) {
            return false;
        }

        $seriesInt = (int) $m[2];
        return $seriesInt >= 1 && $seriesInt <= 99;
    }

    /**
     * Check if an index number is considered old/wrong format for an institute.
     * Returns true if not null/empty but fails the standard format or prefix match.
     *
     * @param string|null $indexNumber
     * @param Institute|null $institute
     * @return bool
     */
    public static function isOldOrWrongFormat(?string $indexNumber, ?Institute $institute = null): bool
    {
        if ($indexNumber === null || trim((string) $indexNumber) === '') {
            return false;
        }
        $prefix = $institute?->getIndexPrefix() ?? 'TIT';
        return !self::isValidIndexNumber($indexNumber, $prefix);
    }

    /**
     * Parse index number string into parts (prefix, series/batch_year, index_seq).
     * Format: ^([A-Za-z0-9]+)\/(\d{2})\/(\d{3,})$ with series 01-99.
     *
     * @param string|null $indexNumber
     * @return array{prefix: string, series: string, series_int: int, batch_year: int, index_seq: int, normalized: string}|null
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

        $seriesInt = (int) $matches[2];
        if ($seriesInt < 1 || $seriesInt > 99) {
            return null;
        }

        return [
            'prefix' => $matches[1],
            'series' => $matches[2],
            'series_int' => $seriesInt,
            'batch_year' => $seriesInt,
            'index_seq' => (int) $matches[3],
            'normalized' => "{$matches[1]}/{$matches[2]}/{$matches[3]}",
        ];
    }

    /**
     * Normalize name for matching by lowering case, removing punctuation, and collapsing whitespace.
     *
     * @param string|null $name
     * @return string
     */
    public function normalizeNameForMatching(?string $name): string
    {
        if ($name === null) {
            return '';
        }
        $str = mb_strtolower(trim($name), 'UTF-8');
        // Remove dots, dashes, underscores, commas, apostrophes, slashes, ampersands
        $str = preg_replace('/[.\-_,\'\/&]/u', '', $str);
        // Collapse all spaces
        $str = preg_replace('/\s+/u', '', $str);
        return $str;
    }

    /**
     * Find candidate students in an institute by full_name or name matching.
     *
     * @param string $name
     * @param string|null $grade
     * @param string|null $medium
     * @param int $instituteId
     * @return array{student: User|null, candidates: array<User>, confidence: string}
     */
    public function findStudentByName(string $name, ?string $grade, ?string $medium, int $instituteId): array
    {
        $normalizedInput = $this->normalizeNameForMatching($name);
        if ($normalizedInput === '' || trim($name) === '-' || trim($name) === '—') {
            return ['student' => null, 'candidates' => [], 'confidence' => 'none'];
        }

        $query = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId);

        // Grade series filter if available
        $gradeSeries = null;
        if (!empty($grade)) {
            try {
                $gradeSeries = $this->deriveGradeSeries($grade);
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        // Medium filter if available
        $cleanMedium = $medium ? strtolower(trim($medium)) : null;

        $allStudents = $query->get();
        $candidates = [];
        $exactMatches = [];

        foreach ($allStudents as $candidate) {
            // Check grade match if grade specified
            if ($gradeSeries !== null) {
                try {
                    $candGradeSeries = $this->deriveGradeSeries($candidate->current_grade);
                    if ($candGradeSeries !== $gradeSeries) {
                        continue;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            // Check medium match: medium equals or is 'both'
            if ($cleanMedium && !in_array($cleanMedium, ['all', 'both', ''])) {
                $candMedium = strtolower(trim((string) $candidate->medium));
                if ($candMedium !== '' && $candMedium !== 'both' && $candMedium !== $cleanMedium) {
                    continue;
                }
            }

            $normFull = $this->normalizeNameForMatching($candidate->full_name);
            $normName = $this->normalizeNameForMatching($candidate->name);

            if ($normalizedInput === $normFull || $normalizedInput === $normName) {
                $exactMatches[] = $candidate;
            } elseif (
                ($normFull !== '' && (str_contains($normalizedInput, $normFull) || str_contains($normFull, $normalizedInput))) ||
                (levenshtein($normalizedInput, $normFull) <= 2 && strlen($normalizedInput) >= 5)
            ) {
                $candidates[] = $candidate;
            }
        }

        if (count($exactMatches) === 1) {
            return ['student' => $exactMatches[0], 'candidates' => [$exactMatches[0]], 'confidence' => 'exact'];
        }

        if (count($exactMatches) === 0 && count($candidates) === 1) {
            return ['student' => $candidates[0], 'candidates' => [$candidates[0]], 'confidence' => 'high'];
        }

        $allMerged = array_merge($exactMatches, $candidates);
        return [
            'student' => null,
            'candidates' => $allMerged,
            'confidence' => count($allMerged) > 0 ? 'ambiguous' : 'none',
        ];
    }

    /**
     * Find a student in an institute by student_id, username, phone, email, or name.
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

        // 4. student_name / name
        $name = $row['student_name'] ?? $row['name'] ?? $row['full_name'] ?? null;
        if (!empty($name) && trim($name) !== '-' && trim($name) !== '—') {
            $grade = $row['grade'] ?? $row['current_grade'] ?? null;
            $medium = $row['medium'] ?? null;
            $match = $this->findStudentByName($name, $grade, $medium, $instituteId);
            if ($match['student']) {
                return $match['student'];
            }
        }

        return null;
    }

    /**
     * Parse and validate CSV rows for index import.
     * Supports owner's columns: grade, medium, student_name, index_number
     * as well as student_id|username|phone|email variants.
     * Merges duplicate entries with name variants (e.g. M.Nidhuran | M.Nidhursan).
     *
     * @param array $rawRows List of associative arrays with columns
     * @param int $instituteId
     * @return array
     */
    public function previewAndValidateImport(array $rawRows, int $instituteId): array
    {
        $seenIndexMap = [];
        $rows = [];
        $validCount = 0;
        $errorCount = 0;
        $institute = Institute::find($instituteId);
        $expectedPrefix = strtoupper($institute?->getIndexPrefix() ?? 'TIT');

        foreach ($rawRows as $idx => $rawRow) {
            $rowNum = $idx + 1;
            $row = [];
            foreach ($rawRow as $k => $v) {
                $row[strtolower(trim((string) $k))] = is_string($v) ? trim($v) : $v;
            }

            $rawIndex = $row['index_number'] ?? $row['index'] ?? null;
            $studentName = $row['student_name'] ?? $row['name'] ?? $row['full_name'] ?? null;
            $gradeCol = $row['grade'] ?? $row['current_grade'] ?? null;
            $mediumCol = $row['medium'] ?? null;

            $status = 'valid';
            $errors = [];
            $warnings = [];
            $isReserved = false;
            $parsedIndex = null;

            // 1. Index number validation
            if (empty($rawIndex)) {
                $status = 'error';
                $errors[] = 'Index number is missing';
            } else {
                $parsedIndex = $this->parseIndexNumber($rawIndex);
                if (!$parsedIndex) {
                    $status = 'error';
                    $errors[] = "Invalid index number format '{$rawIndex}' (must match PREFIX/GG/SEQ e.g. TIT/08/001)";
                } else {
                    $canonicalIndex = $parsedIndex['normalized'];

                    // Prefix equals institute prefix case-insensitively
                    if (strtoupper($parsedIndex['prefix']) !== $expectedPrefix) {
                        $status = 'error';
                        $errors[] = "Prefix '{$parsedIndex['prefix']}' does not match institute prefix '{$expectedPrefix}'";
                    }

                    // Series validation: accept any series 01-99
                    $seriesInt = $parsedIndex['series_int'];

                    if ($seriesInt < 1 || $seriesInt > 99) {
                        $status = 'error';
                        $errors[] = "Series '{$parsedIndex['series']}' must be between 01 and 99";
                    }

                    // Validate series equals grade column if grade column provided
                    if (!empty($gradeCol)) {
                        try {
                            $expectedSeries = null;
                            try {
                                $expectedSeries = $this->deriveGradeSeries($gradeCol);
                            } catch (\Throwable $e) {
                                $expectedSeries = $this->normalizeGradeSeries($gradeCol);
                            }
                            if ($expectedSeries !== null && $parsedIndex['series'] !== $expectedSeries) {
                                $warnings[] = "Grade series '{$parsedIndex['series']}' differs from grade column '{$gradeCol}'";
                            }
                        } catch (\Throwable $e) {
                            // Non-standard grade text, ignore equality check
                        }
                    }

                    // NNN >= 1
                    if ($parsedIndex['index_seq'] < 1) {
                        $status = 'error';
                        $errors[] = "Sequence number must be at least 1";
                    }
                }
            }

            // 2. Reserved number or student matching
            $student = null;
            $candidates = [];
            $trimmedName = is_string($studentName) ? trim($studentName) : '';
            $hasExplicitReserve = ($trimmedName === '-' || $trimmedName === '—');

            if ($hasExplicitReserve) {
                $isReserved = true;
                $resolvedName = '-';
            } else {
                // Try direct identifiers first (student_id, username, email, phone)
                $student = $this->findStudentForImport($row, $instituteId);

                // If not found and student_name is provided, run name matching
                if (!$student && !empty($trimmedName)) {
                    $matchResult = $this->findStudentByName($trimmedName, $gradeCol, $mediumCol, $instituteId);
                    $student = $matchResult['student'];
                    $candidates = $matchResult['candidates'];
                }

                if ($student) {
                    $resolvedName = $student->full_name ?? $student->name;
                } elseif (empty($trimmedName) && empty($row['student_id']) && empty($row['email']) && empty($row['phone']) && empty($row['phone_number']) && empty($row['username']) && empty($row['user_name'])) {
                    $isReserved = true;
                    $resolvedName = '-';
                } else {
                    $resolvedName = $studentName ?: 'Unknown';
                }
            }

            // 3. Duplicate handling within the CSV file
            if ($parsedIndex) {
                $canonicalIndex = $parsedIndex['normalized'];
                if (isset($seenIndexMap[$canonicalIndex])) {
                    $prevRowIndex = $seenIndexMap[$canonicalIndex];
                    $prevRow = &$rows[$prevRowIndex];

                    // Check if duplicate for same student or name variant (e.g. M.Nidhuran | M.Nidhursan)
                    $isSameStudent = ($student && $prevRow['student_id'] && $student->id === $prevRow['student_id']);
                    $isNameVariant = (!$isSameStudent && $studentName && $prevRow['raw_name'] && 
                        levenshtein($this->normalizeNameForMatching($studentName), $this->normalizeNameForMatching($prevRow['raw_name'])) <= 3);

                    if ($isSameStudent || $isNameVariant) {
                        // Merge duplicate into existing row with warning
                        $prevRow['warnings'][] = "Merged duplicate row with name variant: {$prevRow['raw_name']} | {$studentName}";
                        $prevRow['warning_message'] = implode('; ', $prevRow['warnings']);
                        if ($mediumCol && strtolower($mediumCol) !== strtolower((string)$prevRow['medium'])) {
                            $prevRow['medium'] = 'Both';
                        }
                        continue; // Do not add a separate duplicate row
                    } else {
                        $status = 'error';
                        $errors[] = "Duplicate index number '{$rawIndex}' in CSV (also in row {$prevRow['row_number']})";
                    }
                }
            }

            // If not reserved and no student matched
            if (!$isReserved && !$student) {
                $status = 'error';
                $errors[] = count($candidates) > 1 
                    ? "Multiple students match name '{$trimmedName}' (select student from dropdown)"
                    : (!empty($trimmedName) ? "Student '{$trimmedName}' not found" : "Student not found");
            }

            // Check if student already has a different index number
            if ($student && !empty($student->index_number) && $parsedIndex && $student->index_number !== $parsedIndex['normalized']) {
                $status = 'error';
                $errors[] = "Student already has a different index number: {$student->index_number}";
            }

            if ($status === 'valid') {
                $validCount++;
            } else {
                $errorCount++;
            }

            $rowEntry = [
                'row_number' => $rowNum,
                'student_id' => $student?->id,
                'student_name' => $isReserved ? 'Reserved Number' : $resolvedName,
                'raw_name' => $studentName,
                'grade' => $gradeCol ?: ($student?->current_grade ?? 'Grade ' . ($parsedIndex['series_int'] ?? '')),
                'medium' => $mediumCol ?: ($student?->medium ?? 'English'),
                'current_grade' => $student?->current_grade,
                'index_number' => $parsedIndex['normalized'] ?? $rawIndex,
                'batch_year' => $parsedIndex['series_int'] ?? null,
                'series' => $parsedIndex['series'] ?? null,
                'index_seq' => $parsedIndex['index_seq'] ?? null,
                'is_reserved' => $isReserved,
                'candidates' => array_map(function ($c) {
                    return [
                        'id' => $c->id,
                        'name' => $c->full_name ?? $c->name,
                        'current_grade' => $c->current_grade,
                        'medium' => $c->medium,
                        'username' => $c->name,
                    ];
                }, $candidates),
                'status' => $status,
                'errors' => $errors,
                'warnings' => $warnings,
                'error_message' => implode('; ', $errors),
                'warning_message' => implode('; ', $warnings),
            ];

            if ($parsedIndex && !isset($seenIndexMap[$parsedIndex['normalized']])) {
                $seenIndexMap[$parsedIndex['normalized']] = count($rows);
            }
            $rows[] = $rowEntry;
        }

        return [
            'total' => count($rows),
            'valid_count' => $validCount,
            'error_count' => $errorCount,
            'has_errors' => $errorCount > 0,
            'rows' => $rows,
        ];
    }

    /**
     * Execute the index import for validated rows.
     * Advances counter to max(existing last_seq, highest imported seq) in the same transaction.
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

                $seriesInt = (int) ($row['batch_year'] ?? 0);
                $seq = (int) ($row['index_seq'] ?? 0);
                $indexNumber = $row['index_number'];

                // Always track highest imported seq per series (even for reserved numbers)
                if (!isset($maxSeqPerBatch[$seriesInt]) || $seq > $maxSeqPerBatch[$seriesInt]) {
                    $maxSeqPerBatch[$seriesInt] = $seq;
                }

                // If reserved row: advance counter, no student record updated
                if (!empty($row['is_reserved'])) {
                    $importedCount++;
                    $results[] = array_merge($row, [
                        'import_status' => 'imported',
                        'result_message' => 'Reserved Number',
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

                if (!empty($student->index_number) && $student->index_number !== $indexNumber) {
                    $skippedCount++;
                    $results[] = array_merge($row, [
                        'import_status' => 'skipped',
                        'result_message' => "Already assigned: {$student->index_number}",
                    ]);
                    continue;
                }

                $student->update([
                    'batch_year' => $seriesInt,
                    'index_seq' => $seq,
                    'index_number' => $indexNumber,
                ]);

                $importedCount++;
                $results[] = array_merge($row, [
                    'import_status' => 'imported',
                    'result_message' => 'Success',
                ]);
            }

            // CRITICAL: Advance the counter to max(existing last_seq, highest imported seq) for each series/batch
            $institute = Institute::find($instituteId);
            $seqStart = $institute?->getIndexSeqStart() ?? 1;

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
                "Imported {$importedCount} student index numbers from CSV",
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
        $format = $institute?->getIndexFormat() ?? 'grade';

        // Preload DB counters for institute
        $dbCounters = StudentIndexCounter::where('institute_id', $instituteId)
            ->pluck('last_seq', 'batch_year')
            ->toArray();

        $simulatedSeqs = [];
        $previewRows = [];
        $skipped = [];
        $gradeGroupSummaries = [];
        $unmappedGradesCount = [];

        if ($format === 'grade') {
            $prefix = $institute?->getIndexPrefix() ?? 'TIT';
            $seqStart = $institute?->getIndexSeqStart() ?? 1;

            foreach ($students as $student) {
                if (!empty($student->index_number)) {
                    continue;
                }

                $g = $student->current_grade ?: 'Unknown';
                try {
                    $seriesStr = $this->deriveGradeSeries($student->current_grade);
                } catch (\InvalidArgumentException $e) {
                    $unmappedGradesCount[$g] = ($unmappedGradesCount[$g] ?? 0) + 1;
                    $skipped[] = [
                        'student_id' => $student->id,
                        'name' => $student->full_name ?? $student->name,
                        'grade' => $g,
                        'reason' => $e->getMessage(),
                    ];
                    continue;
                }
                $seriesInt = (int) $seriesStr;

                if (!isset($simulatedSeqs[$seriesInt])) {
                    $currentLastSeq = isset($dbCounters[$seriesInt]) ? (int) $dbCounters[$seriesInt] : 0;
                    $simulatedSeqs[$seriesInt] = max($currentLastSeq + 1, $seqStart);
                }

                $seq = $simulatedSeqs[$seriesInt]++;
                $paddedSeq = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                $indexNumber = "{$prefix}/{$seriesStr}/{$paddedSeq}";

                if (!isset($gradeGroupSummaries[$g])) {
                    $gradeGroupSummaries[$g] = [
                        'grade' => $g,
                        'batch_year' => $seriesInt,
                        'series' => $seriesStr,
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
                    'batch' => $seriesInt,
                    'series' => $seriesStr,
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

        // Legacy batch mode
        $prefix = $institute?->index_prefix ?: 'TiT';
        $seqStart = (int) ($institute?->index_seq_start ?: 100);

        foreach ($students as $student) {
            if (!empty($student->index_number)) {
                continue;
            }

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

