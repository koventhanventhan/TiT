<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningMaterial extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\InstituteScope);
    }

    protected $fillable = [
        'title',
        'description',
        'type',
        'file_path',
        'thumbnail_path',
        'grade',
        'institute_id',
        'group',
    ];
}
