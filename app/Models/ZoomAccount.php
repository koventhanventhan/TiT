<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoomAccount extends Model
{
    protected $fillable = [
        'email',
        'account_id',
        'client_id',
        'client_secret',
        'max_concurrent',
        'is_active',
        'institute_id',
    ];

    public function schedules()
    {
        return $this->hasMany(ZoomSchedule::class);
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }
}
