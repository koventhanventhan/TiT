<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'monthly_price',
        'yearly_price',
        'max_students',
        'max_teachers',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function institutes()
    {
        return $this->hasMany(Institute::class);
    }
}
