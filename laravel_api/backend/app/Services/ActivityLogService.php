<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an action.
     *
     * @param string $action
     * @param string|null $description
     * @param array|null $metadata
     * @param int|null $instituteId
     * @return ActivityLog
     */
    public static function log(string $action, ?string $description = null, ?array $metadata = null, ?int $instituteId = null)
    {
        $resolvedInstituteId = $instituteId
            ?? (auth()->check() ? auth()->user()->institute_id : null)
            ?? (function_exists('current_institute_id') ? current_institute_id() : null);

        return ActivityLog::create([
            'institute_id' => $resolvedInstituteId,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => app()->runningInConsole() && !app()->runningUnitTests() ? '127.0.0.1' : request()->ip(),
            'user_agent' => app()->runningInConsole() && !app()->runningUnitTests() ? 'artisan-cli' : request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
