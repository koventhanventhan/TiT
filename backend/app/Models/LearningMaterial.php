<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToInstitute;

class LearningMaterial extends Model
{
    use HasFactory, BelongsToInstitute;

    protected $fillable = [
        'title',
        'description',
        'type',
        'file_path',
        'thumbnail_path',
        'grade',
        'institute_id',
        'group',
        'file_size',
        'url',
    ];
}
