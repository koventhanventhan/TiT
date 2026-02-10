<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZoomSchedule extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\InstituteScope);
    }

    protected $fillable = [
        'title',
        'scheduled_at',
        'zoom_link',
        'grade',
        'created_by',
        'institute_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
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
}
