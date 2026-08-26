<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailBounce extends Model
{
    /**
     * The number of bounces after which an email is permanently blocklisted.
     */
    public const BOUNCE_BLOCK_THRESHOLD = 3;

    protected $fillable = [
        'email',
        'bounce_count',
        'last_bounced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_bounced_at' => 'datetime',
        ];
    }
}
