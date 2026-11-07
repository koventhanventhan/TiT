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
        if (!empty($this->index_prefix)) {
            return (string) $this->index_prefix;
        }

        if ((int) $this->id === 1) {
            return 'TIT';
        }

        return \App\Services\InstituteProvisioner::deriveIndexPrefix((string) $this->name);
    }

    /**
     * Resolve the active institute ID from an explicit ID, current tenant binding, or authenticated non-super-admin user.
     */
    public static function resolveContextInstituteId(?int $explicitId = null): ?int
    {
        if ($explicitId !== null && $explicitId > 0) {
            return $explicitId;
        }

        if (function_exists('current_institute_id') && current_institute_id()) {
            return (int) current_institute_id();
        }

        $tenantId = \App\Support\Tenant::id();
        if ($tenantId) {
            return (int) $tenantId;
        }

        if (auth()->check() && auth()->user()?->role !== 'super_admin' && auth()->user()?->institute_id) {
            return (int) auth()->user()->institute_id;
        }

        return null;
    }

    /**
     * Resolve an institute's display name (`SiteSetting admin_company_name` -> `institutes.name` -> neutral fallback).
     * Never falls back to another institute's name.
     */
    public static function displayBrandName(?int $instituteId = null, string $fallback = 'Education'): string
    {
        $id = self::resolveContextInstituteId($instituteId);
        if ($id === null || $id <= 0) {
            return $fallback;
        }

        try {
            $setting = SiteSetting::withoutGlobalScopes()
                ->where('institute_id', $id)
                ->where('key', 'admin_company_name')
                ->value('value');
            $trimmedSetting = trim((string) ($setting ?? ''));
            if ($trimmedSetting !== '') {
                return $trimmedSetting;
            }
        } catch (\Throwable) {
            // Ignore when site_settings table is unavailable.
        }

        try {
            $name = self::withoutGlobalScopes()->whereKey($id)->value('name');
            $trimmedName = trim((string) ($name ?? ''));
            if ($trimmedName !== '') {
                return $trimmedName;
            }
        } catch (\Throwable) {
            // Ignore DB lookup errors.
        }

        return $fallback;
    }

    /**
     * Resolve the brand name used in outgoing e-mail subject lines and headers.
     * Preserves Institute #1's historical "TiT Education" subject lines unchanged while
     * using each other institute's own display name (or neutral "Education").
     */
    public static function mailBrandName(?int $instituteId = null): string
    {
        $id = self::resolveContextInstituteId($instituteId);

        if ($id === 1) {
            $brand = self::displayBrandName(1, 'TiT Education');
            $legacyTitAliases = [
                'tit',
                'tit institute',
                'tit education',
                'tit education institute',
                'tit online education',
            ];

            if ($brand === '' || in_array(strtolower($brand), $legacyTitAliases, true)) {
                return 'TiT Education';
            }

            return $brand;
        }

        if ($id === null || $id <= 0) {
            return 'Education';
        }

        return self::displayBrandName($id, 'Education');
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

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            if ($model->exists && (int) $model->id === 1 && $model->isDirty('status') && $model->status !== 'active') {
                throw new \DomainException('Institute #1 is the primary platform institute and cannot be suspended or deactivated.');
            }
        });

        static::deleting(function (self $model): void {
            if ((int) $model->id === 1) {
                throw new \DomainException('Institute #1 is the primary platform institute and cannot be deleted.');
            }

            $studentCount = $model->nonScopedStudentsCount();
            $paymentCount = $model->nonScopedPaymentsCount();

            if ($studentCount > 0 || $paymentCount > 0) {
                throw new \DomainException(
                    "Cannot delete {$model->name} because it has {$studentCount} student(s) and {$paymentCount} payment(s). Suspend it instead."
                );
            }
        });
    }

    public function nonScopedStudentsCount(): int
    {
        return User::withoutGlobalScopes()
            ->where('institute_id', $this->id)
            ->where('role', 'user')
            ->count();
    }

    public function nonScopedPaymentsCount(): int
    {
        return Payment::withoutGlobalScopes()
            ->where('institute_id', $this->id)
            ->count();
    }

    public function canBeDeletedSafely(): bool
    {
        return (int) $this->id !== 1
            && $this->nonScopedStudentsCount() === 0
            && $this->nonScopedPaymentsCount() === 0;
    }

    /**
     * Compute non-secret setup checklist state for the super-admin list & checklist page.
     *
     * @return array{
     *   domain_status: 'none'|'unverified'|'verified',
     *   primary_domain: ?string,
     *   domains_count: int,
     *   has_admin: bool,
     *   admin_email: ?string,
     *   integrations_configured: int,
     *   integrations_total: int,
     *   providers: array<string, bool>,
     *   has_plan: bool,
     *   plan_name: string
     * }
     */
    public function setupChecklist(): array
    {
        $domains = $this->relationLoaded('domains')
            ? $this->domains
            : $this->domains()->get();

        if ($domains->isEmpty()) {
            $domainStatus = 'none';
            $primaryDomain = null;
        } else {
            $hasVerified = $domains->contains(static fn (InstituteDomain $d): bool => $d->verified_at !== null);
            $domainStatus = $hasVerified ? 'verified' : 'unverified';
            $primary = $domains->firstWhere('is_primary', true) ?? $domains->first();
            $primaryDomain = $primary?->domain;
        }

        $admin = User::withoutGlobalScopes()
            ->where('institute_id', $this->id)
            ->where('role', 'admin')
            ->orderBy('id')
            ->first(['id', 'email', 'name']);

        $integrationSummary = \App\Support\Integrations::for((int) $this->id)->statusSummary();
        $providers = [];
        $configuredCount = 0;

        foreach (InstituteIntegration::PROVIDERS as $provider) {
            $item = $integrationSummary[$provider] ?? [];
            $isDbConfigured = ($item['source'] ?? 'none') === 'db'
                && ($item['status'] ?? InstituteIntegration::STATUS_UNCONFIGURED) !== InstituteIntegration::STATUS_UNCONFIGURED;

            $providers[$provider] = $isDbConfigured;
            if ($isDbConfigured) {
                $configuredCount++;
            }
        }

        $plan = $this->relationLoaded('plan') ? $this->plan : $this->plan()->first();

        return [
            'domain_status' => $domainStatus,
            'primary_domain' => $primaryDomain,
            'domains_count' => $domains->count(),
            'has_admin' => $admin !== null,
            'admin_email' => $admin?->email,
            'integrations_configured' => $configuredCount,
            'integrations_total' => count(InstituteIntegration::PROVIDERS),
            'providers' => $providers,
            'has_plan' => $plan !== null,
            'plan_name' => $plan?->name ?? 'No plan',
        ];
    }

    /**
     * Badges rendered in the InstituteResource Setup column.
     *
     * @return list<string>
     */
    public function setupBadges(): array
    {
        $summary = $this->setupChecklist();

        return [
            'Domain: ' . $summary['domain_status'],
            'Admin: ' . ($summary['has_admin'] ? 'yes' : 'no'),
            "Integrations: {$summary['integrations_configured']}/{$summary['integrations_total']}",
        ];
    }

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

    public function integrations()
    {
        return $this->hasMany(InstituteIntegration::class);
    }

    public function primaryDomainHost(): string
    {
        $primary = $this->domains()->where('is_primary', true)->first()
            ?: $this->domains()->first();
        if ($primary && !empty($primary->domain)) {
            return strtolower(trim($primary->domain));
        }

        $parsed = parse_url($this->primaryUrl(), PHP_URL_HOST);
        return $parsed ? strtolower($parsed) : '127.0.0.1';
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
