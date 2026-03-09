<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToInstitute;

class Timetable extends Model
{
    use HasFactory, BelongsToInstitute;

    protected $fillable = [
        'title',
        'day_of_week',
        'start_time',
        'duration',
        'grade',
        'subject_id',
        'teacher_id',
        'zoom_host_email',
        'is_active',
        'institute_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
