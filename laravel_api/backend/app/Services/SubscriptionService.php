<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class SubscriptionService
{
    /**
     * Check if an institute can add more students.
     */
    public static function canAddStudent(Institute $institute): bool
    {
        $plan = $institute->plan;
        if (!$plan || $plan->isUnlimited('max_students')) {
            return true;
        }

        $limit = $plan->limitFor('max_students');
        if ($limit === null) {
            return true;
        }

        return self::countRoleForInstitute((int) $institute->id, 'user') < $limit;
    }

    /**
     * Check if an institute can add more teachers.
     */
    public static function canAddTeacher(Institute $institute): bool
    {
        $plan = $institute->plan;
        if (!$plan || $plan->isUnlimited('max_teachers')) {
            return true;
        }

        $limit = $plan->limitFor('max_teachers');
        if ($limit === null) {
            return true;
        }

        return self::countRoleForInstitute((int) $institute->id, 'teacher') < $limit;
    }

    /**
     * Check if a specific feature is enabled for the institute.
     */
    public static function hasFeature(Institute $institute, string $feature): bool
    {
        $plan = $institute->plan;
        if (!$plan) {
            return false;
        }

        return in_array($feature, $plan->features ?? [], true);
    }

    /**
     * Count active (non-soft-deleted) users of a given role belonging strictly to $instituteId.
     */
    public static function countRoleForInstitute(int $instituteId, string $role): int
    {
        $query = User::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('role', $role);

        if (
            in_array(SoftDeletes::class, class_uses_recursive(User::class), true)
            || Schema::hasColumn('users', 'deleted_at')
        ) {
            $query->whereNull('deleted_at');
        }

        return $query->count();
    }
}
