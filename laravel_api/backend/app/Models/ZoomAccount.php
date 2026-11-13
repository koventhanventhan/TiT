<?php

namespace App\Models;

use App\Casts\SafeEncryptedString;
use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;

class ZoomAccount extends Model
{
    use BelongsToInstitute;

    protected $fillable = [
        'email',
        'account_id',
        'client_id',
        'client_secret',
        'max_concurrent',
        'is_active',
        'institute_id',
    ];

    /**
     * Attributes that should be hidden for serialization to prevent leaking secrets.
     */
    protected $hidden = [
        'client_secret',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'client_secret' => SafeEncryptedString::class,
            'is_active' => 'boolean',
            'max_concurrent' => 'integer',
        ];
    }

    /**
     * Get masked client secret for safe display in UI (never full secret).
     */
    public function getMaskedSecretAttribute(): string
    {
        $raw = (string) ($this->client_secret ?? '');
        if ($raw === '') {
            return '••••';
        }
        $tail = substr($raw, -4);
        return '••••' . $tail;
    }

    public function schedules()
    {
        return $this->hasMany(ZoomSchedule::class);
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }
}
