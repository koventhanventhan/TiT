<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformGeminiUsage extends Model
{
    protected $table = 'platform_gemini_usages';

    protected $fillable = [
        'institute_id',
        'usage_date',
        'requests_count',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'requests_count' => 'integer',
    ];

    /**
     * Get today's request count for an institute.
     */
    public static function getTodayUsage(int $instituteId): int
    {
        $today = now()->toDateString();

        return (int) (self::where('institute_id', $instituteId)
            ->whereDate('usage_date', $today)
            ->value('requests_count') ?: 0);
    }

    /**
     * Increment today's request count and return the new total.
     */
    public static function incrementToday(int $instituteId): int
    {
        $today = now()->toDateString();

        $existing = self::where('institute_id', $instituteId)
            ->whereDate('usage_date', $today)
            ->first();

        if ($existing) {
            $existing->increment('requests_count');

            return (int) $existing->refresh()->requests_count;
        }

        try {
            $record = self::create([
                'institute_id' => $instituteId,
                'usage_date' => $today,
                'requests_count' => 1,
            ]);

            return (int) $record->requests_count;
        } catch (\Throwable) {
            $retry = self::where('institute_id', $instituteId)
                ->whereDate('usage_date', $today)
                ->first();
            $retry?->increment('requests_count');

            return (int) ($retry?->refresh()->requests_count ?? 1);
        }
    }
}

