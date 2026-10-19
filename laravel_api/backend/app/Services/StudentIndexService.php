<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\StudentIndexCounter;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentIndexService
{
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
     * Return student's existing batch_year if set, otherwise null (admin must choose).
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
}
