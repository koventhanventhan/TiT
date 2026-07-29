<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\BelongsToInstitute;

class ZoomSchedule extends Model
{
    use HasFactory, BelongsToInstitute;

    protected $fillable = [
        'title',
        'scheduled_at',
        'reminded_at',
        'zoom_link',
        'meeting_id',
        'start_url',
        'join_url',
        'password',
        'duration',
        'grade',
        'subject',
        'zoom_account_id',
        'timetable_id',
        'created_by',
        'institute_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'zoom_schedule_teacher')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function zoomAccount(): BelongsTo
    {
        return $this->belongsTo(ZoomAccount::class);
    }
}
