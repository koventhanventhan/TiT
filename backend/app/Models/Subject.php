<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name', 'price', 'category'];

    protected $casts = [
        'price' => 'decimal:2',
    ];
}
