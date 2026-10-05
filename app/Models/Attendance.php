<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\BelongsToInstitute;

class Attendance extends Model
{
    use HasFactory, BelongsToInstitute;

    protected $fillable = [
        'user_id',
        'zoom_schedule_id',
        'status',
        'attended_at',
        'institute_id',
        'marked_at',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'marked_at' => 'datetime',
        ];
    }

    public function zoomSchedule(): BelongsTo
    {
        return $this->belongsTo(ZoomSchedule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
