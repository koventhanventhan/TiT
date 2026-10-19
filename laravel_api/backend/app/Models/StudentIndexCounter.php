<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentIndexCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_id',
        'batch_year',
        'last_seq',
    ];

    protected $casts = [
        'institute_id' => 'integer',
        'batch_year' => 'integer',
        'last_seq' => 'integer',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
