<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\SiteSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InstituteProvisioner
{
    public const DUPLICATE_EMAIL_MESSAGE = 'This e-mail is already used by another account on the platform – use a different address';

    /**
     * Optional hook invoked after each step inside the DB transaction (used by tests to simulate mid-flight failures).
     *
     * @var (Closure(string): void)|null
     */
    protected ?Closure $stepObserver = null;

    public function observeSteps(?Closure $observer): self
    {
        $this->stepObserver = $observer;

        return $this;
    }

    /**
     * Validate all inputs and return normalized provisioning payload without writing to the database.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateData(ProvisionInstituteData $d): array
    {
        $errors = [];

        $name = trim($d->name);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            $errors['name'][] = 'Institute name must be between 2 and 80 characters.';
        }

        $shortBrandName = trim((string) ($d->shortBrandName !== null && trim($d->shortBrandName) !== '' ? $d->shortBrandName : $name));
        if ($shortBrandName !== '' && mb_strlen($shortBrandName) > 80) {
            $errors['short_brand_name'][] = 'Short brand name must not exceed 80 characters.';
        }

        $slug = null;
        if ($d->slug !== null && trim($d->slug) !== '') {
            $normalizedSlug = Str::slug(trim($d->slug));
            if ($normalizedSlug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $normalizedSlug)) {
                $errors['slug'][] = 'Please enter a valid URL slug using lowercase letters, numbers, and hyphens.';
            } elseif (Institute::withTrashed()->where('slug', $normalizedSlug)->exists()) {
                $errors['slug'][] = 'This slug is already used by another institute (including deleted ones).';
            } else {
                $slug = $normalizedSlug;
            }
        } elseif (!isset($errors['name'])) {
            $slug = self::generateUniqueSlug($name);
        }

        $plan = null;
        if ($d->subscriptionPlanId !== null) {
            $plan = SubscriptionPlan::find($d->subscriptionPlanId);
            if (!$plan) {
                $errors['subscription_plan_id'][] = 'The selected subscription plan does not exist.';
            }
        }

        $expiresAt = $d->expiresAt ? Carbon::instance($d->expiresAt) : now()->addDays(30);

        if (!in_array($d->indexFormat, ['grade', 'batch'], true)) {
            $errors['index_format'][] = 'Index format must be either grade or batch.';
        }

        $indexPrefix = null;
        if ($d->indexPrefix !== null && trim($d->indexPrefix) !== '') {
            $candidatePrefix = strtoupper(trim($d->indexPrefix));
            if (!preg_match('/^[A-Z0-9]{2,6}$/', $candidatePrefix)) {
                $errors['index_prefix'][] = 'Index prefix must be 2 to 6 letters or digits.';
            } else {
                $indexPrefix = $candidatePrefix;
            }
        } else {
            $indexPrefix = self::deriveIndexPrefix($name !== '' ? $name : 'Institute');
        }

        $enabledPresets = array_values(array_filter(
            (array) config('appearance.enabled_presets', ['royal', 'ocean', 'emerald']),
            static fn ($p): bool => is_string($p) && $p !== '' && $p !== 'midnight'
        ));
        if (empty($enabledPresets)) {
            $enabledPresets = ['royal', 'ocean', 'emerald'];
        }
        $themePreset = strtolower(trim($d->themePreset ?: 'royal'));
        if (!in_array($themePreset, $enabledPresets, true)) {
            $errors['theme_preset'][] = 'Please choose one of the available look & feel presets (' . implode(', ', $enabledPresets) . ').';
        }

        $normalizedDomain = null;
        if ($d->domain !== null && trim($d->domain) !== '') {
            $domainError = self::validateAndNormalizeDomain($d->domain, $normalizedDomain);
            if ($domainError !== null) {
                $errors['domain'][] = $domainError;
            }
        }

        $shouldCreateAdmin = $d->createAdmin || ($d->adminEmail !== null && trim($d->adminEmail) !== '');
        $adminName = null;
        $adminEmail = null;
        $adminPhone = $d->adminPhone !== null && trim($d->adminPhone) !== '' ? trim($d->adminPhone) : null;

        if ($shouldCreateAdmin) {
            $adminName = trim((string) ($d->adminName ?? ''));
            if ($adminName === '') {
                $errors['admin_name'][] = 'Please enter the administrator full name.';
            } elseif (mb_strlen($adminName) > 120) {
                $errors['admin_name'][] = 'Administrator name must not exceed 120 characters.';
            }

            $rawEmail = strtolower(trim((string) ($d->adminEmail ?? '')));
            if ($rawEmail === '' || !filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['admin_email'][] = 'Please enter a valid administrator e-mail address.';
            } elseif (self::emailExists($rawEmail)) {
                $errors['admin_email'][] = self::DUPLICATE_EMAIL_MESSAGE;
            } else {
                $adminEmail = $rawEmail;
            }
        }

        $contactEmail = $d->contactEmail !== null && trim($d->contactEmail) !== '' ? strtolower(trim($d->contactEmail)) : null;
        if ($contactEmail !== null && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'][] = 'Please enter a valid public contact e-mail address.';
        }

        $contactPhone = $d->contactPhone !== null && trim($d->contactPhone) !== '' ? trim($d->contactPhone) : null;
        if ($contactPhone !== null && mb_strlen($contactPhone) > 30) {
            $errors['contact_phone'][] = 'Public contact phone number must not exceed 30 characters.';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'name' => $name,
            'short_brand_name' => $shortBrandName !== '' ? $shortBrandName : $name,
            'slug' => $slug,
            'logo_path' => $d->logoPath !== null && trim($d->logoPath) !== '' ? trim($d->logoPath) : null,
            'subscription_plan_id' => $plan?->id,
            'plan_name' => $plan?->name,
            'expires_at' => $expiresAt,
            'index_prefix' => $indexPrefix,
            'index_format' => $d->indexFormat,
            'theme_preset' => $themePreset,
            'domain' => $normalizedDomain,
            'create_admin' => $shouldCreateAdmin,
            'admin_name' => $adminName,
            'admin_email' => $adminEmail,
            'admin_phone' => $adminPhone,
            'contact_email' => $contactEmail,
            'contact_phone' => $contactPhone,
        ];
    }

    /**
     * Provision a complete tenant (institute + optional domain + optional first admin + neutral settings)
     * inside a single atomic DB transaction.
     *
     * @throws ValidationException
     */
    public function provision(ProvisionInstituteData $d): ProvisionResult
    {
        $validated = $this->validateData($d);

        /** @var ProvisionResult $result */
        $result = DB::transaction(function () use ($validated): ProvisionResult {
            // 1. Institute row
            $institute = Institute::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'logo_path' => $validated['logo_path'],
                'status' => 'active',
                'subscription_plan_id' => $validated['subscription_plan_id'],
                'expires_at' => $validated['expires_at'],
                'index_prefix' => $validated['index_prefix'],
                'index_format' => $validated['index_format'],
                'theme_settings' => null,
            ]);

            $this->notifyStep('institute');

            // 2. Optional primary domain (unverified until DNS check passes)
            $domainModel = null;
            if ($validated['domain'] !== null) {
                $domainModel = InstituteDomain::create([
                    'institute_id' => $institute->id,
                    'domain' => $validated['domain'],
                    'is_primary' => true,
                    'verified_at' => null,
                    'verification_token' => bin2hex(random_bytes(16)),
                ]);

                InstituteDomain::clearDomainCache($domainModel);
                Cache::forget('tenant_domain:' . $domainModel->domain);

                $this->notifyStep('domain');
            }

            // 3. Optional first admin user (strong server-generated password, returned once)
            $adminUser = null;
            $plainPassword = null;
            if ($validated['create_admin']) {
                $plainPassword = self::generateStrongPassword(16);

                $adminUser = User::withoutGlobalScopes()->create([
                    'name' => $validated['admin_name'],
                    'full_name' => $validated['admin_name'],
                    'email' => $validated['admin_email'],
                    'phone_number' => $validated['admin_phone'],
                    'password' => Hash::make($plainPassword),
                    'role' => 'admin',
                    'institute_id' => $institute->id,
                ]);

                $this->notifyStep('admin');
            }

            // 4. Neutral SiteSetting defaults scoped strictly to THIS institute ID
            $settingsMap = self::buildNeutralSettings(
                displayName: $validated['short_brand_name'],
                themePreset: $validated['theme_preset'],
                logoPath: $validated['logo_path'],
                contactEmail: $validated['contact_email'],
                contactPhone: $validated['contact_phone'],
            );

            $flatSettings = [];
            foreach ($settingsMap as $key => [$value, $group]) {
                SiteSetting::setForInstitute($key, $value, $institute->id, $group);
                $flatSettings[$key] = $value;
            }
            Cache::forget("site_settings_{$institute->id}");

            $this->notifyStep('settings');

            // 5. Audit log (strictly secret-free)
            ActivityLogService::log(
                action: 'institute_provisioned',
                description: "Provisioned institute: {$institute->name}",
                metadata: [
                    'institute_id' => $institute->id,
                    'slug' => $institute->slug,
                    'plan_id' => $institute->subscription_plan_id,
                    'plan_name' => $validated['plan_name'],
                    'domain' => $domainModel?->domain,
                    'admin_id' => $adminUser?->id,
                    'theme_preset' => $validated['theme_preset'],
                ],
                instituteId: $institute->id,
            );

            $this->notifyStep('activity_log');

            return new ProvisionResult(
                institute: $institute->fresh(['plan', 'domains']) ?? $institute,
                domain: $domainModel,
                admin: $adminUser,
                plainAdminPassword: $plainPassword,
                settings: $flatSettings,
            );
        });

        return $result;
    }

    protected function notifyStep(string $step): void
    {
        if ($this->stepObserver !== null) {
            ($this->stepObserver)($step);
        }
    }

    public static function emailExists(string $email): bool
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '') {
            return false;
        }

        return User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->exists();
    }

    /**
     * Generate a unique slug from an institute name, appending -2, -3, ... on collision
     * across BOTH active and soft-deleted institutes.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'institute';
        }
        $base = Str::limit($base, 240, '');

        $candidate = $base;
        $suffix = 2;
        while (Institute::withTrashed()->where('slug', $candidate)->exists()) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Derive a 2-6 character uppercase alphanumeric index prefix from an institute name.
     * Guaranteed NEVER to return 'TIT' automatically (unless explicitly typed by the user).
     */
    public static function deriveIndexPrefix(string $name): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', strtoupper($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) >= 2) {
            $initials = '';
            foreach ($words as $word) {
                $initials .= substr($word, 0, 1);
                if (strlen($initials) >= 6) {
                    break;
                }
            }
            $prefix = $initials;
        } elseif (count($words) === 1) {
            $prefix = substr($words[0], 0, 3);
        } else {
            $prefix = 'INST';
        }

        if (strlen($prefix) < 2) {
            $prefix = str_pad($prefix, 2, 'X');
        }

        $prefix = substr($prefix, 0, 6);

        if ($prefix === 'TIT') {
            $firstWordFour = isset($words[0]) ? substr($words[0], 0, 4) : '';
            $prefix = (strlen($firstWordFour) >= 2 && $firstWordFour !== 'TIT')
                ? $firstWordFour
                : 'INST';
        }

        return $prefix;
    }

    /**
     * Normalize and validate a domain string.
     * If valid, writes normalized bare domain into $normalizedOut and returns null;
     * otherwise returns a human-readable error message.
     */
    public static function validateAndNormalizeDomain(string $rawDomain, ?string &$normalizedOut = null): ?string
    {
        if (class_exists(DomainChecker::class)) {
            $checker = app(DomainChecker::class);
            $normalized = $checker->normalize($rawDomain);
            $error = $checker->validate($normalized);
            if ($error !== null) {
                return $error;
            }
            $normalizedOut = $normalized;

            return null;
        }

        $domain = strtolower(trim($rawDomain));
        $domain = preg_replace('#^https?://#i', '', $domain) ?? $domain;
        $domain = explode('/', $domain, 2)[0];
        $domain = explode('?', $domain, 2)[0];
        $domain = explode('#', $domain, 2)[0];
        $domain = preg_replace('/:\d+$/', '', $domain) ?? $domain;
        $domain = preg_replace('/^www\./i', '', trim($domain)) ?? $domain;

        if ($domain === '') {
            return 'Please enter a valid domain name (for example, academy.lk).';
        }

        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return 'Please enter a domain name, not an IP address.';
        }

        if ($domain === 'localhost' || str_ends_with($domain, '.localhost') || str_ends_with($domain, '.test')) {
            return 'Local development addresses (localhost or .test) cannot be used as an institute domain.';
        }

        $platformHosts = array_map(
            static fn ($h): string => preg_replace('/^www\./', '', strtolower(trim((string) $h))) ?? '',
            (array) config('tenant.platform_hosts', [])
        );
        if (in_array($domain, $platformHosts, true)) {
            return 'This address belongs to the platform itself and cannot be assigned to an individual institute.';
        }

        if (!str_contains($domain, '.') || strlen($domain) > 253) {
            return 'Please enter a full domain name including its extension (for example, academy.lk).';
        }

        $labels = explode('.', $domain);
        foreach ($labels as $label) {
            if ($label === '' || strlen($label) > 63 || !preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $label)) {
                return 'Domain labels may only contain letters, numbers, and hyphens, and cannot start or end with a hyphen.';
            }
        }

        $tld = (string) end($labels);
        if (!preg_match('/^[a-z]{2,63}$/', $tld)) {
            return 'The domain extension must be at least 2 letters (for example, .lk or .com).';
        }

        $existing = InstituteDomain::with(['institute' => static fn ($q) => $q->withTrashed()])
            ->whereIn('domain', [$domain, 'www.' . $domain])
            ->first();

        if ($existing) {
            $ownerName = $existing->institute?->name ?? "Institute #{$existing->institute_id}";

            return "This domain is already connected to {$ownerName}.";
        }

        $normalizedOut = $domain;

        return null;
    }

    /**
     * Generate a cryptographically strong random password of at least 14 characters.
     */
    public static function generateStrongPassword(int $length = 16): string
    {
        $length = max(14, $length);

        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $symbols = '!@#%^&*-_=+';
        $all = $upper . $lower . $digits . $symbols;

        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        for ($i = count($chars); $i < $length; $i++) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    /**
     * Build neutral SiteSetting [value, group] entries for a brand-new institute.
     * Contains zero references to TiT, Jaffna, edulearns.lk, phone numbers (unless provided), or social links.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function buildNeutralSettings(
        string $displayName,
        string $themePreset,
        ?string $logoPath = null,
        ?string $contactEmail = null,
        ?string $contactPhone = null,
    ): array {
        $year = (string) now()->year;
        $email = $contactEmail ?? '';
        $phone = $contactPhone ?? '';

        $settings = [
            'site_name' => [$displayName, 'general'],
            'admin_company_name' => [$displayName, 'admin_identity'],
            'theme_preset' => [$themePreset, 'appearance'],
            'hero_title' => ['Welcome to', 'hero'],
            'hero_title_gradient' => [$displayName, 'hero'],
            'hero_description' => ["Structured classes, study materials, and progress tracking for students at {$displayName}.", 'hero'],
            'footer_logo_text' => [$displayName, 'footer'],
            'footer_tagline' => ["Official learning portal of {$displayName}.", 'footer'],
            'footer_description' => ["Online classes, learning resources, and student support provided by {$displayName}.", 'footer'],
            'footer_copyright' => ["© {$year} {$displayName}. All rights reserved.", 'footer'],
            'footer_developed_by' => ["Powered by {$displayName}", 'footer'],
            'footer_location' => ['', 'footer'],
            'footer_email' => [$email, 'footer'],
            'footer_phone' => [$phone, 'footer'],
            'social_facebook' => ['', 'social'],
            'social_instagram' => ['', 'social'],
            'social_youtube' => ['', 'social'],
            'social_twitter' => ['', 'social'],
            'social_linkedin' => ['', 'social'],
            'topbar_show_social' => ['no', 'general'],
            'topbar_show_email' => [$email !== '' ? 'yes' : 'no', 'general'],
            'stats_years' => ['0', 'sections'],
            'stats_students' => ['0', 'sections'],
            'stats_tutors' => ['0', 'sections'],
        ];

        if ($logoPath !== null && $logoPath !== '') {
            $logoUrl = str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://') || str_starts_with($logoPath, '/')
                ? $logoPath
                : Storage::disk('public')->url($logoPath);
            $settings['logo_url'] = [$logoUrl, 'general'];
        }

        return $settings;
    }
}
