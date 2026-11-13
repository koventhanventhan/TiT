<?php

namespace App\Services;

use App\Exceptions\PlanLimitReachedException;
use App\Models\Institute;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class SubscriptionService
{
    /**
     * Check if an institute can add more students.
     */
    public static function canAddStudent(Institute $institute, int $adding = 1): bool
    {
        try {
            PlanLimits::assertCanAddStudent($institute, $adding);
            return true;
        } catch (PlanLimitReachedException $e) {
            return false;
        }
    }

    /**
     * Check if an institute can add more teachers.
     */
    public static function canAddTeacher(Institute $institute, int $adding = 1): bool
    {
        try {
            PlanLimits::assertCanAddTeacher($institute, $adding);
            return true;
        } catch (PlanLimitReachedException $e) {
            return false;
        }
    }

    /**
     * Check if a specific feature is enabled for the institute.
     */
    public static function hasFeature(Institute $institute, string $feature): bool
    {
        return $institute->hasFeature($feature);
    }

    /**
     * Count active (non-soft-deleted) users of a given role belonging strictly to $instituteId.
     */
    public static function countRoleForInstitute(int $instituteId, string $role): int
    {
        $institute = Institute::find($instituteId);
        if (!$institute) {
            return 0;
        }

        if ($role === 'user') {
            return PlanUsage::students($institute);
        }

        if ($role === 'teacher') {
            return PlanUsage::teachers($institute);
        }

        return User::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('role', $role)
            ->count();
    }
}
