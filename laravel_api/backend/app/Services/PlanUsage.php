<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class PlanUsage
{
    /**
     * Count active students belonging to the institute that count toward the plan limit.
     * Definition: role = 'user', institute_id = this institute, not soft-deleted,
     * and registration_status != 'rejected'.
     */
    public static function students(Institute $institute): int
    {
        $query = User::withoutGlobalScopes()
            ->where('institute_id', $institute->id)
            ->where('role', 'user');

        if (
            in_array(SoftDeletes::class, class_uses_recursive(User::class), true)
            || Schema::hasColumn('users', 'deleted_at')
        ) {
            $query->whereNull('deleted_at');
        }

        if (Schema::hasColumn('users', 'registration_status')) {
            $query->where(function ($q) {
                $q->whereNull('registration_status')
                    ->orWhere('registration_status', '!=', 'rejected');
            });
        }

        return $query->count();
    }

    /**
     * Count active teachers belonging to the institute that count toward the plan limit.
     * Definition: role = 'teacher', institute_id = this institute, not soft-deleted.
     */
    public static function teachers(Institute $institute): int
    {
        $query = User::withoutGlobalScopes()
            ->where('institute_id', $institute->id)
            ->where('role', 'teacher');

        if (
            in_array(SoftDeletes::class, class_uses_recursive(User::class), true)
            || Schema::hasColumn('users', 'deleted_at')
        ) {
            $query->whereNull('deleted_at');
        }

        return $query->count();
    }

    /**
     * Calculate usage percentage against a limit. Returns 0 for unlimited.
     */
    public static function percent(int $used, ?int $limit): int
    {
        if ($limit === null || $limit <= 0) {
            return 0;
        }

        return (int) min(100, round(($used / $limit) * 100));
    }

    /**
     * Determine usage state: ok, near (>= 80%), full (>= 100%), or unlimited.
     *
     * @return 'ok'|'near'|'full'|'unlimited'
     */
    public static function state(int $used, ?int $limit): string
    {
        if ($limit === null || $limit <= 0) {
            return 'unlimited';
        }

        $pct = ($used / $limit) * 100;
        if ($pct >= 100) {
            return 'full';
        }
        if ($pct >= 80) {
            return 'near';
        }

        return 'ok';
    }

    /**
     * Provide a consolidated summary of plan, validity, usage, and features.
     */
    public static function details(Institute $institute): array
    {
        $plan = $institute->plan;
        $studentLimit = $plan?->limitFor('max_students');
        $teacherLimit = $plan?->limitFor('max_teachers');

        $studentUsed = self::students($institute);
        $teacherUsed = self::teachers($institute);

        $daysLeft = null;
        if ((int) $institute->id !== 1 && $institute->expires_at) {
            $daysLeft = (int) ceil(now()->diffInDays($institute->expires_at, false));
        }

        $featureRegistry = config('plans.features', [
            'zoom' => 'Zoom Classes',
            'ai_assistant' => 'AI Learning Assistant',
            'whatsapp' => 'WhatsApp Messaging',
            'custom_domain' => 'Custom Domain',
            'online_payments' => 'Online Payments (PayHere)',
            'advanced_reports' => 'Advanced Reports',
        ]);

        $featuresList = [];
        $rawFeatures = $plan?->features ?? [];
        if (!is_array($rawFeatures)) {
            $rawFeatures = [];
        }

        foreach ($rawFeatures as $feat) {
            if (isset($featureRegistry[$feat])) {
                $featuresList[] = $featureRegistry[$feat];
            } else {
                $featuresList[] = (string) $feat;
            }
        }

        return [
            'institute' => $institute,
            'plan_name' => $plan?->name ?? 'Standard / Custom',
            'is_primary' => (int) $institute->id === 1,
            'status' => $institute->status,
            'expires_at' => $institute->expires_at,
            'days_left' => $daysLeft,
            'is_expired' => $institute->isExpired(),
            'in_grace' => $institute->isInGracePeriod(),
            'grace_ends_at' => $institute->graceEndsAt(),
            'student_used' => $studentUsed,
            'student_limit' => $studentLimit,
            'student_percent' => self::percent($studentUsed, $studentLimit),
            'student_state' => self::state($studentUsed, $studentLimit),
            'teacher_used' => $teacherUsed,
            'teacher_limit' => $teacherLimit,
            'teacher_percent' => self::percent($teacherUsed, $teacherLimit),
            'teacher_state' => self::state($teacherUsed, $teacherLimit),
            'features' => $featuresList,
            'contact' => [
                'email' => config('tenant.platform_contact_email'),
                'phone' => config('tenant.platform_contact_phone'),
                'whatsapp' => config('tenant.platform_contact_whatsapp'),
            ],
        ];
    }
}

