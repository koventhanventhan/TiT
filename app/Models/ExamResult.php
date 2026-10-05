<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    protected $fillable = [
        'student_name',
        'index_no',
        'term',
        'grade',
        'subject',
        'marks',
        'result_grade',
        'rank',
        'year',
        'institute_id',
    ];

    protected $casts = [
        'marks' => 'decimal:2',
        'rank' => 'integer',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
