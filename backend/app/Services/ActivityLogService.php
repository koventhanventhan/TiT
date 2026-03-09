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
     * @return ActivityLog
     */
    public static function log(string $action, ?string $description = null, ?array $metadata = null)
    {
        return ActivityLog::create([
            'institute_id' => auth()->check() ? auth()->user()->institute_id : null,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
