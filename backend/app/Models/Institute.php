<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institute extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'theme_settings',
        'status',
        'subscription_plan_id',
        'expires_at',
    ];

    protected $casts = [
        'theme_settings' => 'array',
        'expires_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
