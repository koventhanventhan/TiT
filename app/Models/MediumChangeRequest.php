<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediumChangeRequest extends Model
{
    protected $fillable = ['user_id', 'current_medium', 'requested_medium', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
