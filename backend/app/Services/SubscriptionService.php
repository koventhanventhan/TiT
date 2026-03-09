<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\User;

class SubscriptionService
{
    /**
     * Check if an institute can add more students.
     */
    public static function canAddStudent(Institute $institute): bool
    {
        $plan = $institute->subscriptionPlan;
        if (!$plan || $plan->max_students === -1) return true;

        $studentCount = User::withoutGlobalScopes()
            ->where('institute_id', $institute->id)
            ->where('role', 'user')
            ->count();

        return $studentCount < $plan->max_students;
    }

    /**
     * Check if an institute can add more teachers.
     */
    public static function canAddTeacher(Institute $institute): bool
    {
        $plan = $institute->subscriptionPlan;
        if (!$plan || $plan->max_teachers === -1) return true;

        $teacherCount = User::withoutGlobalScopes()
            ->where('institute_id', $institute->id)
            ->where('role', 'teacher')
            ->count();

        return $teacherCount < $plan->max_teachers;
    }

    /**
     * Check if a specific feature is enabled for the institute.
     */
    public static function hasFeature(Institute $institute, string $feature): bool
    {
        $plan = $institute->subscriptionPlan;
        if (!$plan) return false;

        return in_array($feature, $plan->features ?? []);
    }
}
