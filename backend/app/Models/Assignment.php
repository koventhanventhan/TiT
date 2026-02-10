<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\InstituteScope);
    }

    protected $fillable = [
        'title',
        'description',
        'due_date',
        'teacher_id',
        'grade',
        'subject',
        'institute_id',
        'file_path',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
