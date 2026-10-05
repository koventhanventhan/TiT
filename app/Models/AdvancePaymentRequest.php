<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvancePaymentRequest extends Model
{
    protected $fillable = [
        'user_id',
        'months_count',
        'amount',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
