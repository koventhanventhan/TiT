<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name', 'name_ta', 'name_si', 'price', 'category', 'medium'];

    protected $casts = [
        'price' => 'decimal:2',
    ];
}
