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
     * Invalidate cached domain mappings when domain changes and enforce removal guards.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            if ((int) $model->institute_id === 1) {
                $remainingCount = static::where('institute_id', 1)->count();
                if ($remainingCount <= 1) {
                    throw new \DomainException('Cannot remove the last website address of Institute #1.');
                }
            }

            if (app()->bound('request')) {
                $rawHost = (string) request()->getHost();
                if (!in_array(strtolower($rawHost), ['', 'localhost', '127.0.0.1'], true)) {
                    $currentHost = app(\App\Services\DomainChecker::class)->normalize($rawHost);
                    if ($currentHost !== '' && $currentHost === strtolower((string) $model->domain)) {
                        throw new \DomainException('Cannot remove the website address you are currently using to access this page.');
                    }
                }
            }
        });

        static::saved(function (self $model) {
            static::clearDomainCache($model);
        });

        static::deleted(function (self $model) {
            static::clearDomainCache($model);

            if ($model->is_primary) {
                $nextDomain = static::where('institute_id', $model->institute_id)->orderBy('id')->first();
                if ($nextDomain && !$nextDomain->is_primary) {
                    $nextDomain->update(['is_primary' => true]);
                }
            }
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

