<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'theme_settings',
        'status',
        'rag_enabled',
        'public_assistant_enabled',
        'subscription_plan_id',
        'expires_at',
        'index_prefix',
        'index_seq_start',
        'index_format',
    ];

    public function getIndexFormat(): string
    {
        if ($this->index_format === 'batch') {
            return 'batch';
        }
        if ($this->index_format === 'grade') {
            return 'grade';
        }
        if (($this->index_seq_start ?? 0) >= 100) {
            return 'batch';
        }
        return 'grade';
    }

    public function getIndexPrefix(): string
    {
        return $this->index_prefix ?: 'TIT';
    }

    public function getIndexSeqStart(): int
    {
        if ($this->getIndexFormat() === 'grade') {
            return $this->index_seq_start ?? 1;
        }
        return $this->index_seq_start ?? 100;
    }

    protected $casts = [
        'rag_enabled' => 'boolean',
        'public_assistant_enabled' => 'boolean',
        'theme_settings' => 'array',
        'expires_at' => 'datetime',
        'index_seq_start' => 'integer',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function domains()
    {
        return $this->hasMany(InstituteDomain::class);
    }

    public function primaryDomain()
    {
        return $this->hasOne(InstituteDomain::class)->where('is_primary', true);
    }

    public function primaryUrl(): string
    {
        $primary = $this->domains()->where('is_primary', true)->first();
        if (app()->environment(['local', 'testing'])) {
            if (!$primary || in_array($primary->domain, ['localhost', '127.0.0.1']) || str_ends_with($primary->domain, '.test')) {
                return rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
            }
        }
        if ($primary) {
            return 'https://' . $primary->domain;
        }
        $first = $this->domains()->first();
        if ($first) {
            return 'https://' . $first->domain;
        }
        return rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function students()
    {
        return $this->hasMany(User::class)->where('role', 'user');
    }

    public function teachers()
    {
        return $this->hasMany(User::class)->where('role', 'teacher');
    }

    public function admins()
    {
        return $this->hasMany(User::class)->where('role', 'admin');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function classes()
    {
        return $this->hasMany(ZoomSchedule::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function faqs()
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
