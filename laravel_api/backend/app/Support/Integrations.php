<?php

namespace App\Support;

use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\ZoomAccount;
use App\Support\Credentials\GeminiCredentials;
use App\Support\Credentials\GoogleCredentials;
use App\Support\Credentials\MailCredentials;
use App\Support\Credentials\PayHereCredentials;
use App\Support\Credentials\RecaptchaCredentials;
use App\Support\Credentials\WhatsAppCredentials;
use App\Support\Credentials\ZoomCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class Integrations
{
    private ?int $instituteId;

    public function __construct(?int $instituteId = null)
    {
        $this->instituteId = $instituteId
            ?? Tenant::id()
            ?? (auth()->check() && auth()->user()?->institute_id ? (int) auth()->user()->institute_id : null);
    }

    /**
     * Create an integration resolver scoped to a specific institute (defaulting to Tenant::id()).
     */
    public static function for(?int $instituteId = null): self
    {
        return new self($instituteId);
    }

    public function instituteId(): ?int
    {
        return $this->instituteId;
    }

    /**
     * Whether `.env` / platform credential fallback is allowed for the given institute ID
     * (applies to PayHere and WhatsApp only).
     */
    public static function isEnvFallbackAllowed(?int $instituteId): bool
    {
        if ($instituteId === null || $instituteId <= 0) {
            return false;
        }

        $allowed = array_map('intval', (array) config('integrations.env_fallback_institutes', [1]));

        return in_array($instituteId, $allowed, true);
    }

    /**
     * Alias for isEnvFallbackAllowed.
     */
    public static function allowsEnvFallback(?int $instituteId): bool
    {
        return self::isEnvFallbackAllowed($instituteId);
    }

    /**
     * Update runtime verification status of a provider for this institute.
     */
    public function updateStatus(string $provider, string $status, ?string $errorMessage = null): InstituteIntegration
    {
        $integration = InstituteIntegration::withoutGlobalScopes()->firstOrCreate([
            'institute_id' => $this->instituteId,
            'provider' => $provider,
        ], [
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        if ($status === InstituteIntegration::STATUS_VERIFIED) {
            $integration->markVerified();
        } elseif ($status === InstituteIntegration::STATUS_FAILING) {
            $integration->recordFailure($errorMessage ?? 'Verification failed');
        } else {
            $integration->status = $status;
            $integration->saveQuietly();
        }

        return $integration;
    }

    /**
     * Env-controlled flag for trusting client-side PayHere return callbacks in local development.
     * Always forced FALSE in production regardless of configuration.
     */
    public static function trustPayHereClientCallback(): bool
    {
        if (app()->environment('production') || config('app.env') === 'production') {
            return false;
        }

        return (bool) config('payhere.trust_client_callback', false);
    }

    /**
     * Resolve PayHere credentials for the institute.
     *
     * Policy:
     * - DB row in `configured|verified` -> use DB row (`source = db`).
     * - DB row in `unconfigured|failing` -> return `null` (never fall back when explicitly disabled or failing).
     * - No DB row -> fall back to `.env`/platform ONLY if institute is in `INTEGRATIONS_ENV_FALLBACK_INSTITUTES`.
     * - Otherwise -> `null` (online payments unavailable).
     */
    public function payhere(): ?PayHereCredentials
    {
        if ($this->instituteId === null || $this->instituteId <= 0) {
            return null;
        }

        $row = $this->findRow(InstituteIntegration::PROVIDER_PAYHERE);

        if ($row !== null) {
            if (!in_array($row->status, [InstituteIntegration::STATUS_CONFIGURED, InstituteIntegration::STATUS_VERIFIED], true)) {
                return null;
            }

            $creds = is_array($row->credentials) ? $row->credentials : [];
            $settings = is_array($row->settings) ? $row->settings : [];

            $merchantId = trim((string) ($settings['merchant_id'] ?? $creds['merchant_id'] ?? ''));
            $merchantSecret = trim((string) ($creds['merchant_secret'] ?? ''));

            if ($merchantId === '' || $merchantSecret === '') {
                return null;
            }

            $appId = trim((string) ($settings['app_id'] ?? $creds['app_id'] ?? '')) ?: null;
            $appSecret = trim((string) ($creds['app_secret'] ?? '')) ?: null;
            $isSandbox = array_key_exists('is_sandbox', $settings)
                ? (bool) $settings['is_sandbox']
                : true;
            $currency = strtoupper(trim((string) ($settings['currency'] ?? config('integrations.payhere.default_currency', 'LKR')))) ?: 'LKR';

            return new PayHereCredentials(
                instituteId: $this->instituteId,
                merchantId: $merchantId,
                merchantSecret: $merchantSecret,
                appId: $appId,
                appSecret: $appSecret,
                isSandbox: $isSandbox,
                currency: $currency,
                source: 'db',
                status: $row->status,
            );
        }

        if (!self::isEnvFallbackAllowed($this->instituteId)) {
            return null;
        }

        return self::platformPayHereCredentials($this->instituteId);
    }

    /**
     * Resolve Meta WhatsApp Cloud API credentials for the institute.
     *
     * Policy:
     * - DB row with credentials (`status != unconfigured`) -> use DB row (`source = db`).
     * - No configured DB row -> fall back to `.env` ONLY if institute is in `INTEGRATIONS_ENV_FALLBACK_INSTITUTES`.
     * - Otherwise -> `null`, logged at most once per day as "WhatsApp not configured for institute N".
     */
    public function whatsapp(bool $logWhenUnconfigured = true): ?WhatsAppCredentials
    {
        if ($this->instituteId === null || $this->instituteId <= 0) {
            return null;
        }

        $row = $this->findRow(InstituteIntegration::PROVIDER_WHATSAPP);

        if ($row !== null && $row->status !== InstituteIntegration::STATUS_UNCONFIGURED) {
            $creds = is_array($row->credentials) ? $row->credentials : [];
            $settings = is_array($row->settings) ? $row->settings : [];

            $token = trim((string) ($creds['access_token'] ?? $creds['token'] ?? ''));
            $phoneNumberId = trim((string) ($settings['phone_number_id'] ?? $creds['phone_number_id'] ?? ''));

            if ($token !== '' && $phoneNumberId !== '') {
                $apiVersion = trim((string) ($settings['api_version'] ?? config('integrations.whatsapp.default_api_version', 'v21.0'))) ?: 'v21.0';
                $rawTemplates = is_array($settings['templates'] ?? null) ? $settings['templates'] : [];
                $templates = [];
                foreach ($rawTemplates as $k => $v) {
                    if (is_string($v) && trim($v) !== '') {
                        $templates[(string) $k] = trim($v);
                    }
                }

                return new WhatsAppCredentials(
                    instituteId: $this->instituteId,
                    accessToken: $token,
                    phoneNumberId: $phoneNumberId,
                    apiVersion: $apiVersion,
                    templates: $templates,
                    source: 'db',
                    status: $row->status,
                );
            }
        }

        if ($row === null && self::isEnvFallbackAllowed($this->instituteId)) {
            $fallback = self::platformWhatsAppCredentials($this->instituteId);
            if ($fallback !== null) {
                return $fallback;
            }
        }

        if ($logWhenUnconfigured) {
            $this->logWhatsAppUnconfiguredOncePerDay($this->instituteId);
        }

        return null;
    }

    /**
     * Resolve outgoing mail credentials for the institute.
     *
     * Policy:
     * - Institute DB row configured (`status != unconfigured` and host present) -> institute SMTP (`source = db`).
     * - Otherwise -> if `INTEGRATIONS_MAIL_PLATFORM_FALLBACK=true`, platform mailer (`source = env`)
     *   with `From` name = institute name and `Reply-To` = institute contact e-mail.
     * - Otherwise -> `null`.
     */
    public function mail(): ?MailCredentials
    {
        $instituteId = $this->instituteId ?? 0;
        $institute = $instituteId > 0 && Schema::hasTable('institutes')
            ? Institute::withoutGlobalScopes()->find($instituteId)
            : null;

        $defaultFromName = $institute?->name ?: (string) config('mail.from.name', 'TiT Education');
        $defaultReplyTo = $instituteId > 0 ? $this->resolveInstituteContactEmail($instituteId) : null;

        if ($instituteId > 0) {
            $row = $this->findRow(InstituteIntegration::PROVIDER_MAIL);
            if ($row !== null && $row->status !== InstituteIntegration::STATUS_UNCONFIGURED) {
                $creds = is_array($row->credentials) ? $row->credentials : [];
                $settings = is_array($row->settings) ? $row->settings : [];

                $host = trim((string) ($settings['host'] ?? $creds['host'] ?? ''));
                $fromAddress = trim((string) ($settings['from_address'] ?? config('mail.from.address', 'noreply@example.com')));

                if ($host !== '' && $fromAddress !== '') {
                    $port = isset($settings['port']) && (int) $settings['port'] > 0
                        ? (int) $settings['port']
                        : 587;
                    $encRaw = strtolower(trim((string) ($settings['encryption'] ?? 'tls')));
                    $encryption = in_array($encRaw, ['tls', 'ssl'], true) ? $encRaw : null;
                    $username = trim((string) ($settings['username'] ?? $creds['username'] ?? '')) ?: null;
                    $password = isset($creds['password']) && $creds['password'] !== '' ? (string) $creds['password'] : null;
                    $fromName = trim((string) ($settings['from_name'] ?? '')) ?: $defaultFromName;
                    $replyTo = trim((string) ($settings['reply_to'] ?? '')) ?: $defaultReplyTo;

                    return new MailCredentials(
                        instituteId: $instituteId,
                        mailer: 'smtp',
                        host: $host,
                        port: $port,
                        encryption: $encryption,
                        username: $username,
                        password: $password,
                        fromAddress: $fromAddress,
                        fromName: $fromName,
                        replyToAddress: $replyTo ?: null,
                        replyToName: $fromName,
                        source: 'db',
                        status: $row->status,
                    );
                }
            }
        }

        if (!config('integrations.mail_platform_fallback', true)) {
            return null;
        }

        return self::platformMailCredentials($instituteId, $defaultFromName, $defaultReplyTo);
    }

    /**
     * Resolve Google Gemini API credentials for the institute.
     */
    public function gemini(): ?GeminiCredentials
    {
        if ($this->instituteId === null || $this->instituteId <= 0) {
            return null;
        }

        $row = $this->findRow(InstituteIntegration::PROVIDER_GEMINI);

        if ($row !== null && $row->status !== InstituteIntegration::STATUS_UNCONFIGURED) {
            $creds = is_array($row->credentials) ? $row->credentials : [];
            $apiKey = trim((string) ($creds['api_key'] ?? ''));

            if ($apiKey !== '') {
                return new GeminiCredentials(
                    instituteId: $this->instituteId,
                    apiKey: $apiKey,
                    source: 'db',
                    status: $row->status,
                );
            }
        }

        if ($row === null && self::isEnvFallbackAllowed($this->instituteId)) {
            return self::platformGeminiCredentials($this->instituteId);
        }

        return null;
    }

    /**
     * Resolve Google reCAPTCHA v3 credentials for the institute.
     */
    public function recaptcha(): ?RecaptchaCredentials
    {
        if ($this->instituteId === null || $this->instituteId <= 0) {
            return null;
        }

        $row = $this->findRow(InstituteIntegration::PROVIDER_RECAPTCHA);

        if ($row !== null && $row->status !== InstituteIntegration::STATUS_UNCONFIGURED) {
            $creds = is_array($row->credentials) ? $row->credentials : [];
            $settings = is_array($row->settings) ? $row->settings : [];

            $secretKey = trim((string) ($creds['secret_key'] ?? ''));
            $siteKey = trim((string) ($settings['site_key'] ?? $creds['site_key'] ?? ''));
            $scoreThreshold = isset($settings['score_threshold']) && is_numeric($settings['score_threshold'])
                ? (float) $settings['score_threshold']
                : 0.5;

            if ($secretKey !== '' && $siteKey !== '') {
                return new RecaptchaCredentials(
                    instituteId: $this->instituteId,
                    siteKey: $siteKey,
                    secretKey: $secretKey,
                    scoreThreshold: $scoreThreshold,
                    source: 'db',
                    status: $row->status,
                );
            }
        }

        if ($row === null && self::isEnvFallbackAllowed($this->instituteId)) {
            return self::platformRecaptchaCredentials($this->instituteId);
        }

        return null;
    }

    /**
     * Resolve Google OAuth credentials for the institute.
     */
    public function google(): ?GoogleCredentials
    {
        if ($this->instituteId === null || $this->instituteId <= 0) {
            return null;
        }

        $row = $this->findRow(InstituteIntegration::PROVIDER_GOOGLE);

        if ($row !== null && $row->status !== InstituteIntegration::STATUS_UNCONFIGURED) {
            $creds = is_array($row->credentials) ? $row->credentials : [];
            $settings = is_array($row->settings) ? $row->settings : [];

            $clientSecret = trim((string) ($creds['client_secret'] ?? ''));
            $clientId = trim((string) ($settings['client_id'] ?? $creds['client_id'] ?? ''));
            $redirectUri = trim((string) ($settings['redirect_uri'] ?? '')) ?: null;

            if ($clientSecret !== '' && $clientId !== '') {
                return new GoogleCredentials(
                    instituteId: $this->instituteId,
                    clientId: $clientId,
                    clientSecret: $clientSecret,
                    redirectUri: $redirectUri,
                    source: 'db',
                    status: $row->status,
                );
            }
        }

        if ($row === null && self::isEnvFallbackAllowed($this->instituteId)) {
            return self::platformGoogleCredentials($this->instituteId);
        }

        return null;
    }

    /**
     * Resolve Zoom integration state for the institute.
     */
    public function zoom(): ?ZoomCredentials
    {
        if ($this->instituteId === null || $this->instituteId <= 0) {
            return null;
        }

        $row = $this->findRow(InstituteIntegration::PROVIDER_ZOOM);
        if ($row !== null && $row->status === InstituteIntegration::STATUS_UNCONFIGURED) {
            return null;
        }

        $accountsCount = 0;
        $activeAccountsCount = 0;
        if (Schema::hasTable('zoom_accounts')) {
            $accounts = ZoomAccount::withoutGlobalScopes()
                ->where('institute_id', $this->instituteId)
                ->get();
            $accountsCount = $accounts->count();
            $activeAccountsCount = $accounts->where('is_active', true)->count();
        }

        if ($accountsCount > 0) {
            return new ZoomCredentials(
                instituteId: $this->instituteId,
                accountsCount: $accountsCount,
                activeAccountsCount: $activeAccountsCount,
                isVirtual: false,
                source: 'db',
                status: $row?->status ?? ($activeAccountsCount > 0 ? InstituteIntegration::STATUS_CONFIGURED : InstituteIntegration::STATUS_UNCONFIGURED),
            );
        }

        if (self::isEnvFallbackAllowed($this->instituteId)) {
            return self::platformZoomCredentials($this->instituteId);
        }

        return null;
    }

    /**
     * Build a non-secret status summary for an institute across all providers (used by CLI & Super-Admin).
     *
     * @return array<string, array{provider: string, status: string, source: string, masked: array<string, string>, last_verified_at: ?string, last_error: ?string}>
     */
    public function statusSummary(): array
    {
        $summary = [];

        foreach (InstituteIntegration::PROVIDERS as $provider) {
            $row = $this->findRow($provider);
            $resolved = match ($provider) {
                InstituteIntegration::PROVIDER_PAYHERE => $this->payhere(),
                InstituteIntegration::PROVIDER_WHATSAPP => $this->whatsapp(logWhenUnconfigured: false),
                InstituteIntegration::PROVIDER_MAIL => $this->mail(),
                InstituteIntegration::PROVIDER_GEMINI => $this->gemini(),
                InstituteIntegration::PROVIDER_ZOOM => $this->zoom(),
                InstituteIntegration::PROVIDER_RECAPTCHA => $this->recaptcha(),
                InstituteIntegration::PROVIDER_GOOGLE => $this->google(),
            };

            if ($row !== null && $row->status !== InstituteIntegration::STATUS_UNCONFIGURED) {
                $masked = $row->maskedCredentials();
                if ($provider === InstituteIntegration::PROVIDER_ZOOM && empty($masked) && $resolved !== null) {
                    $masked = ['accounts' => "{$resolved->accountsCount} account(s)"];
                }

                $summary[$provider] = [
                    'provider' => $provider,
                    'status' => $row->status,
                    'source' => $resolved?->source ?? 'db',
                    'masked' => $masked,
                    'last_verified_at' => $row->last_verified_at?->toDateTimeString(),
                    'last_error' => $row->last_error,
                ];
                continue;
            }

            if ($row === null && $resolved !== null && $resolved->source === 'db') {
                $masked = [];
                if ($provider === InstituteIntegration::PROVIDER_ZOOM) {
                    $masked = ['accounts' => "{$resolved->accountsCount} account(s)"];
                }

                $summary[$provider] = [
                    'provider' => $provider,
                    'status' => $resolved->status,
                    'source' => 'db',
                    'masked' => $masked,
                    'last_verified_at' => null,
                    'last_error' => null,
                ];
                continue;
            }

            if ($resolved !== null && $resolved->source === 'env') {
                $masked = match ($provider) {
                    InstituteIntegration::PROVIDER_PAYHERE => array_filter([
                        'merchant_secret' => $resolved->maskedMerchantSecret(),
                        'app_secret' => $resolved->maskedAppSecret(),
                    ]),
                    InstituteIntegration::PROVIDER_WHATSAPP => array_filter([
                        'access_token' => $resolved->maskedAccessToken(),
                    ]),
                    InstituteIntegration::PROVIDER_MAIL => array_filter([
                        'password' => $resolved->maskedPassword(),
                    ]),
                    InstituteIntegration::PROVIDER_GEMINI => array_filter([
                        'api_key' => $resolved->maskedApiKey(),
                    ]),
                    InstituteIntegration::PROVIDER_ZOOM => [
                        'accounts' => 'virtual (env)',
                    ],
                    InstituteIntegration::PROVIDER_RECAPTCHA => array_filter([
                        'site_key' => $resolved->siteKey,
                        'secret_key' => $resolved->maskedSecretKey(),
                    ]),
                    InstituteIntegration::PROVIDER_GOOGLE => array_filter([
                        'client_id' => $resolved->clientId,
                        'client_secret' => $resolved->maskedClientSecret(),
                    ]),
                };

                $summary[$provider] = [
                    'provider' => $provider,
                    'status' => 'configured',
                    'source' => 'env',
                    'masked' => $masked,
                    'last_verified_at' => null,
                    'last_error' => null,
                ];
                continue;
            }

            $summary[$provider] = [
                'provider' => $provider,
                'status' => InstituteIntegration::STATUS_UNCONFIGURED,
                'source' => 'none',
                'masked' => [],
                'last_verified_at' => null,
                'last_error' => null,
            ];
        }

        return $summary;
    }

    public function findRow(string $provider): ?InstituteIntegration
    {
        if ($this->instituteId === null || $this->instituteId <= 0) {
            return null;
        }

        if (!Schema::hasTable('institute_integrations')) {
            return null;
        }

        return InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $this->instituteId)
            ->where('provider', $provider)
            ->first();
    }

    /**
     * Read platform/env PayHere configuration (ONLY called when fallback is permitted or importing env).
     */
    public static function platformPayHereCredentials(int $instituteId): ?PayHereCredentials
    {
        $merchantId = trim((string) config('payhere.merchant_id', ''));
        $merchantSecret = trim((string) config('payhere.merchant_secret', ''));
        $appId = trim((string) config('payhere.app_id', '')) ?: null;
        $appSecret = trim((string) config('payhere.app_secret', '')) ?: null;

        if (($merchantId === '' || $merchantSecret === '') && Schema::hasTable('site_settings')) {
            $platformSettings = SiteSetting::withoutGlobalScopes()
                ->whereNull('institute_id')
                ->whereIn('key', ['payhere_merchant_id', 'payhere_merchant_secret', 'payhere_app_id', 'payhere_app_secret'])
                ->pluck('value', 'key');

            $merchantId = $merchantId !== '' ? $merchantId : trim((string) ($platformSettings['payhere_merchant_id'] ?? ''));
            $merchantSecret = $merchantSecret !== '' ? $merchantSecret : trim((string) ($platformSettings['payhere_merchant_secret'] ?? ''));
            $appId = $appId ?: (trim((string) ($platformSettings['payhere_app_id'] ?? '')) ?: null);
            $appSecret = $appSecret ?: (trim((string) ($platformSettings['payhere_app_secret'] ?? '')) ?: null);
        }

        if ($merchantId === '' || $merchantSecret === '') {
            return null;
        }

        return new PayHereCredentials(
            instituteId: $instituteId,
            merchantId: $merchantId,
            merchantSecret: $merchantSecret,
            appId: $appId,
            appSecret: $appSecret,
            isSandbox: (bool) config('payhere.is_sandbox', false),
            currency: strtoupper(trim((string) config('payhere.currency', 'LKR'))) ?: 'LKR',
            source: 'env',
            status: 'configured',
        );
    }

    /**
     * Read platform/env Meta WhatsApp configuration (ONLY called when fallback is permitted or importing env).
     */
    public static function platformWhatsAppCredentials(int $instituteId): ?WhatsAppCredentials
    {
        $token = trim((string) config('services.meta_whatsapp.token', ''));
        $phoneNumberId = trim((string) config('services.meta_whatsapp.phone_number_id', ''));
        $apiVersion = trim((string) config('services.meta_whatsapp.api_version', 'v21.0')) ?: 'v21.0';

        if ($token === '' || $phoneNumberId === '') {
            return null;
        }

        return new WhatsAppCredentials(
            instituteId: $instituteId,
            accessToken: $token,
            phoneNumberId: $phoneNumberId,
            apiVersion: $apiVersion,
            templates: [],
            source: 'env',
            status: 'configured',
        );
    }

    /**
     * Read platform/env Mailer configuration with per-institute From Name and Reply-To.
     */
    public static function platformMailCredentials(int $instituteId, string $fromName, ?string $replyToAddress): ?MailCredentials
    {
        $defaultMailer = (string) config('mail.default', 'log');
        $smtpConfig = (array) config('mail.mailers.smtp', []);

        $fromAddress = trim((string) config('mail.from.address', ''));
        if ($fromAddress === '') {
            $fromAddress = 'noreply@example.com';
        }

        $host = isset($smtpConfig['host']) && $smtpConfig['host'] !== '' ? (string) $smtpConfig['host'] : null;
        $port = isset($smtpConfig['port']) && (int) $smtpConfig['port'] > 0 ? (int) $smtpConfig['port'] : null;
        $encRaw = strtolower(trim((string) ($smtpConfig['encryption'] ?? $smtpConfig['scheme'] ?? '')));
        $encryption = in_array($encRaw, ['tls', 'ssl', 'smtps'], true)
            ? ($encRaw === 'smtps' ? 'ssl' : $encRaw)
            : null;
        $username = isset($smtpConfig['username']) && $smtpConfig['username'] !== '' ? (string) $smtpConfig['username'] : null;
        $password = isset($smtpConfig['password']) && $smtpConfig['password'] !== '' ? (string) $smtpConfig['password'] : null;

        return new MailCredentials(
            instituteId: $instituteId,
            mailer: $defaultMailer,
            host: $host,
            port: $port,
            encryption: $encryption,
            username: $username,
            password: $password,
            fromAddress: $fromAddress,
            fromName: $fromName,
            replyToAddress: $replyToAddress ?: null,
            replyToName: $fromName,
            source: 'env',
            status: 'configured',
        );
    }

    /**
     * Read platform/env Gemini API key (ONLY called when fallback is permitted or importing env).
     */
    public static function platformGeminiCredentials(int $instituteId): ?GeminiCredentials
    {
        $apiKey = trim((string) config('services.gemini.api_key', ''));
        if ($apiKey === '') {
            return null;
        }

        return new GeminiCredentials(
            instituteId: $instituteId,
            apiKey: $apiKey,
            source: 'env',
            status: 'configured',
        );
    }

    /**
     * Read platform/env reCAPTCHA credentials (ONLY called when fallback is permitted or importing env).
     */
    public static function platformRecaptchaCredentials(int $instituteId): ?RecaptchaCredentials
    {
        $siteKey = trim((string) config('services.recaptcha.site_key', ''));
        $secretKey = trim((string) config('services.recaptcha.secret_key', ''));
        $scoreThreshold = (float) config('services.recaptcha.score_threshold', 0.5);

        if ($siteKey === '' || $secretKey === '') {
            return null;
        }

        return new RecaptchaCredentials(
            instituteId: $instituteId,
            siteKey: $siteKey,
            secretKey: $secretKey,
            scoreThreshold: $scoreThreshold,
            source: 'env',
            status: 'configured',
        );
    }

    /**
     * Read platform/env Google OAuth credentials (ONLY called when fallback is permitted or importing env).
     */
    public static function platformGoogleCredentials(int $instituteId): ?GoogleCredentials
    {
        $clientId = trim((string) config('services.google.client_id', ''));
        $clientSecret = trim((string) config('services.google.client_secret', ''));
        $redirect = trim((string) config('services.google.redirect', '')) ?: null;

        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        return new GoogleCredentials(
            instituteId: $instituteId,
            clientId: $clientId,
            clientSecret: $clientSecret,
            redirectUri: $redirect,
            source: 'env',
            status: 'configured',
        );
    }

    /**
     * Read platform/env Zoom credentials as a virtual account state (ONLY when fallback is permitted).
     */
    public static function platformZoomCredentials(int $instituteId): ?ZoomCredentials
    {
        $clientId = trim((string) config('services.zoom.client_id', ''));
        $clientSecret = trim((string) config('services.zoom.client_secret', ''));

        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        return new ZoomCredentials(
            instituteId: $instituteId,
            accountsCount: 1,
            activeAccountsCount: 1,
            isVirtual: true,
            source: 'env',
            status: 'configured',
        );
    }

    public function resolveInstituteContactEmail(int $instituteId): ?string
    {
        if (Schema::hasTable('site_settings')) {
            $settings = SiteSetting::withoutGlobalScopes()
                ->where('institute_id', $instituteId)
                ->whereIn('key', ['contact_email', 'footer_email', 'assistant_public_contact_email'])
                ->pluck('value', 'key');

            foreach (['contact_email', 'footer_email', 'assistant_public_contact_email'] as $key) {
                $candidate = trim((string) ($settings[$key] ?? ''));
                if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                    return $candidate;
                }
            }
        }

        if (Schema::hasTable('users')) {
            $adminEmail = User::withoutGlobalScopes()
                ->where('institute_id', $instituteId)
                ->where('role', 'admin')
                ->orderBy('id')
                ->value('email');

            if (is_string($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                return $adminEmail;
            }
        }

        return null;
    }

    private function logWhatsAppUnconfiguredOncePerDay(int $instituteId): void
    {
        $dateKey = now()->format('Y-m-d');
        $cacheKey = "integrations_whatsapp_unconfigured_{$instituteId}_{$dateKey}";

        try {
            if (Cache::add($cacheKey, 1, now()->endOfDay())) {
                Log::info("WhatsApp not configured for institute {$instituteId}");
            }
        } catch (\Throwable) {
            Log::info("WhatsApp not configured for institute {$instituteId}");
        }
    }
}
