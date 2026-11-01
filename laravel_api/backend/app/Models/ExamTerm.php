<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;

class ExamTerm extends Model
{
    use BelongsToInstitute;
    protected $fillable = [
        'name',
        'institute_id',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
