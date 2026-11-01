<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class InstituteDomain extends Model
{
    protected $fillable = [
        'institute_id',
        'domain',
        'is_primary',
        'verified_at',
        'verification_token',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Mutator to ensure domain is always lowercase and without port.
     */
    public function setDomainAttribute($value): void
    {
        $domain = strtolower(trim((string)$value));
        $domain = preg_replace('/:\d+$/', '', $domain);
        $this->attributes['domain'] = $domain;
    }

    /**
     * Relationship to the Institute.
     */
    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    /**
     * Invalidate cached domain mappings when domain changes.
     */
    protected static function booted(): void
    {
        static::saved(function (self $model) {
            static::clearDomainCache($model);
        });

        static::deleted(function (self $model) {
            static::clearDomainCache($model);
        });
    }

    public static function clearDomainCache(self $model): void
    {
        $domain = $model->domain;
        $bareDomain = preg_replace('/^www\./', '', $domain);
        Cache::forget("tenant_domain_{$domain}");
        Cache::forget("tenant_domain_{$bareDomain}");
        Cache::forget("tenant_domain_www.{$bareDomain}");
        Cache::forget("tenant_domains_inst_{$model->institute_id}");
        Cache::forget("site_settings_{$model->institute_id}");
    }
}

