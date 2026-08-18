<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name', 'name_ta', 'name_si', 'category', 'medium',
        'package_price', 'original_price', 'addon_price',
        'type', 'applicable_grades', 'is_active', 'description'
    ];

    protected $casts = [
        'package_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'addon_price' => 'decimal:2',
        'applicable_grades' => 'array',
        'is_active' => 'boolean',
    ];
}
