<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\BelongsToInstitute;

class AdminMessage extends Model
{
    use HasFactory, BelongsToInstitute;

    protected $fillable = [
        'title',
        'body',
        'target_type',
        'target_user_id',
        'institute_id',
    ];

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AdminMessageRead::class, 'admin_message_id');
    }

    public function isBroadcast(): bool
    {
        return $this->target_type === 'broadcast';
    }
}
