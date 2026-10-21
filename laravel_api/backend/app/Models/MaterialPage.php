<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialPage extends Model
{
    use HasFactory;

    protected $table = 'material_pages';

    protected $fillable = [
        'material_id',
        'page_number',
        'text',
        'is_ocr',
        'ocr_uncertain',
        'file_hash',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'is_ocr' => 'boolean',
        'ocr_uncertain' => 'boolean',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class, 'material_id');
    }
}