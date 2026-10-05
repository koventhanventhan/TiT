<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailBounce extends Model
{
    /**
     * The number of bounces after which an email is permanently blocklisted.
     */
    /**
     * Block after the very first bounce to prevent repeated sends to
     * non-existent mailboxes (Ticket #370951 — shared-hosting IP reputation).
     */
    public const BOUNCE_BLOCK_THRESHOLD = 1;

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
