<?php

namespace App\Services;

use App\Exceptions\PlanLimitReachedException;
use App\Models\Institute;
use Illuminate\Support\Facades\DB;

class PlanLimits
{
    /**
     * Check if an institute can add student(s).
     *
     * @throws PlanLimitReachedException
     */
    public static function assertCanAddStudent(Institute $institute, int $adding = 1): void
    {
        $limit = $institute->plan?->limitFor('max_students');

        // Unlimited (0, negative, or null) never blocks
        if ($limit === null || $limit <= 0) {
            return;
        }

        $current = PlanUsage::students($institute);

        if (($current + $adding) > $limit) {
            throw new PlanLimitReachedException(
                "Student limit ({$limit}) reached. Your plan allows {$limit} students. Contact the platform owner to upgrade.",
                $limit,
                $current,
                $adding,
                'student',
                403
            );
        }
    }

    /**
     * Check if an institute can add teacher(s).
     *
     * @throws PlanLimitReachedException
     */
    public static function assertCanAddTeacher(Institute $institute, int $adding = 1): void
    {
        $limit = $institute->plan?->limitFor('max_teachers');

        // Unlimited (0, negative, or null) never blocks
        if ($limit === null || $limit <= 0) {
            return;
        }

        $current = PlanUsage::teachers($institute);

        if (($current + $adding) > $limit) {
            throw new PlanLimitReachedException(
                "Teacher limit ({$limit}) reached. Your plan allows {$limit} teachers. Contact the platform owner to upgrade.",
                $limit,
                $current,
                $adding,
                'teacher',
                403
            );
        }
    }

    /**
     * Remaining free student slots (null = unlimited).
     */
    public static function remainingStudentSlots(Institute $institute): ?int
    {
        $limit = $institute->plan?->limitFor('max_students');
        if ($limit === null || $limit <= 0) {
            return null;
        }

        $current = PlanUsage::students($institute);
        return max(0, $limit - $current);
    }

    /**
     * Remaining free teacher slots (null = unlimited).
     */
    public static function remainingTeacherSlots(Institute $institute): ?int
    {
        $limit = $institute->plan?->limitFor('max_teachers');
        if ($limit === null || $limit <= 0) {
            return null;
        }

        $current = PlanUsage::teachers($institute);
        return max(0, $limit - $current);
    }

    /**
     * Execute a student creation callback inside a DB transaction with a row-lock on the institute.
     *
     * @template T
     * @param \Closure(): T $callback
     * @return T
     * @throws PlanLimitReachedException
     */
    public static function runWithStudentLock(Institute $institute, int $adding, \Closure $callback)
    {
        return DB::transaction(function () use ($institute, $adding, $callback) {
            $locked = Institute::with('plan')->where('id', $institute->id)->lockForUpdate()->first();
            $target = $locked ?: $institute;

            self::assertCanAddStudent($target, $adding);

            return $callback();
        });
    }

    /**
     * Execute a teacher creation callback inside a DB transaction with a row-lock on the institute.
     *
     * @template T
     * @param \Closure(): T $callback
     * @return T
     * @throws PlanLimitReachedException
     */
    public static function runWithTeacherLock(Institute $institute, int $adding, \Closure $callback)
    {
        return DB::transaction(function () use ($institute, $adding, $callback) {
            $locked = Institute::with('plan')->where('id', $institute->id)->lockForUpdate()->first();
            $target = $locked ?: $institute;

            self::assertCanAddTeacher($target, $adding);

            return $callback();
        });
    }
}

