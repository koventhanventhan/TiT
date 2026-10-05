<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'priority',
        'event_date',
        'start_time',
        'duration',
        'location',
        'description',
        'attendees',
        'reminders',
        'is_recurring',
    ];

    protected $casts = [
        'reminders' => 'array',
        'is_recurring' => 'boolean',
        'event_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
