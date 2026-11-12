<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstituteSubscriptionNotice extends Model
{
    protected $fillable = [
        'institute_id',
        'kind',
        'expires_on',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function getExpiresOnAttribute($value): ?Carbon
    {
        return $value ? Carbon::parse($value)->startOfDay() : null;
    }

    public function setExpiresOnAttribute($value): void
    {
        $this->attributes['expires_on'] = $value instanceof CarbonInterface
            ? $value->format('Y-m-d')
            : (is_string($value) ? substr($value, 0, 10) : $value);
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }
}
