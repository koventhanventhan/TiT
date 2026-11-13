<?php

namespace App\Console\Commands;

use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Support\Integrations;
use App\Support\Redact;
use Illuminate\Console\Command;

class IntegrationsImportEnvCommand extends Command
{
    protected $signature = 'integrations:import-env
                            {institute : Institute ID or slug}
                            {--dry-run : Print masked values only without writing to the database}';

    protected $description = 'Import platform .env PayHere, WhatsApp, SMTP, Gemini, Zoom, reCAPTCHA, and Google settings into encrypted DB rows for a specific institute';

    public function handle(): int
    {
        $identifier = (string) $this->argument('institute');
        $isDryRun = (bool) $this->option('dry-run');

        $institute = ctype_digit($identifier)
            ? Institute::withoutGlobalScopes()->find((int) $identifier)
            : Institute::withoutGlobalScopes()->where('slug', $identifier)->first();

        if (!$institute) {
            $this->error("Institute [{$identifier}] was not found.");
            return self::FAILURE;
        }

        $instituteId = (int) $institute->id;
        $resolver = Integrations::for($instituteId);

        $candidates = $this->collectEnvCandidates($institute, $resolver);
        $tableRows = [];

        foreach ($candidates as $provider => $payload) {
            if ($payload === null) {
                $tableRows[] = [
                    $provider,
                    $isDryRun ? 'skip (not set in .env)' : 'skipped (not set in .env)',
                    '—',
                    '—',
                ];
                continue;
            }

            $maskedCreds = [];
            foreach ($payload['credentials'] as $key => $secret) {
                if ($secret !== null && $secret !== '') {
                    $maskedCreds[] = $key . '=' . Redact::maskValue((string) $secret);
                }
            }

            $settingsPreview = [];
            foreach ($payload['settings'] as $key => $val) {
                if (is_bool($val)) {
                    $settingsPreview[] = $key . '=' . ($val ? 'true' : 'false');
                } elseif (is_scalar($val) && (string) $val !== '') {
                    $settingsPreview[] = $key . '=' . $val;
                }
            }

            if (!$isDryRun) {
                InstituteIntegration::withoutGlobalScopes()->updateOrCreate(
                    [
                        'institute_id' => $instituteId,
                        'provider' => $provider,
                    ],
                    [
                        'credentials' => $payload['credentials'],
                        'settings' => $payload['settings'],
                        'status' => InstituteIntegration::STATUS_CONFIGURED,
                        'last_error' => null,
                    ]
                );
            }

            $tableRows[] = [
                $provider,
                $isDryRun ? 'dry-run (would import)' : 'imported (configured)',
                empty($maskedCreds) ? '—' : implode(', ', $maskedCreds),
                empty($settingsPreview) ? '—' : implode(', ', $settingsPreview),
            ];
        }

        $modeLabel = $isDryRun ? 'DRY RUN (no DB changes written)' : 'IMPORT COMPLETE';
        $this->info("{$modeLabel} — Institute #{$instituteId} ({$institute->name})");
        $this->table(
            ['Provider', 'Result', 'Masked Credentials', 'Non-Secret Settings'],
            $tableRows
        );

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{credentials: array<string, string>, settings: array<string, mixed>}|null>
     */
    private function collectEnvCandidates(Institute $institute, Integrations $resolver): array
    {
        $instituteId = (int) $institute->id;

        // 1. PayHere
        $payhere = Integrations::platformPayHereCredentials($instituteId);
        $payherePayload = null;
        if ($payhere !== null) {
            $creds = ['merchant_secret' => $payhere->merchantSecret];
            if ($payhere->appSecret) {
                $creds['app_secret'] = $payhere->appSecret;
            }
            $settings = [
                'merchant_id' => $payhere->merchantId,
                'is_sandbox' => $payhere->isSandbox,
                'currency' => $payhere->currency,
            ];
            if ($payhere->appId) {
                $settings['app_id'] = $payhere->appId;
            }
            $payherePayload = [
                'credentials' => $creds,
                'settings' => $settings,
            ];
        }

        // 2. WhatsApp
        $whatsapp = Integrations::platformWhatsAppCredentials($instituteId);
        $whatsappPayload = null;
        if ($whatsapp !== null) {
            $whatsappPayload = [
                'credentials' => [
                    'access_token' => $whatsapp->accessToken,
                ],
                'settings' => [
                    'phone_number_id' => $whatsapp->phoneNumberId,
                    'api_version' => $whatsapp->apiVersion,
                    'templates' => [],
                ],
            ];
        }

        // 3. Mail (SMTP)
        $smtpConfig = (array) config('mail.mailers.smtp', []);
        $host = trim((string) ($smtpConfig['host'] ?? ''));
        $username = trim((string) ($smtpConfig['username'] ?? ''));
        $password = (string) ($smtpConfig['password'] ?? '');
        $fromAddress = trim((string) config('mail.from.address', ''));
        $isPlaceholderHost = in_array($host, ['', '127.0.0.1', 'localhost', 'mailpit'], true) && $password === '';

        $mailPayload = null;
        if ($host !== '' && !$isPlaceholderHost && $fromAddress !== '') {
            $encRaw = strtolower(trim((string) ($smtpConfig['encryption'] ?? $smtpConfig['scheme'] ?? 'tls')));
            $encryption = in_array($encRaw, ['tls', 'ssl', 'smtps'], true)
                ? ($encRaw === 'smtps' ? 'ssl' : $encRaw)
                : 'none';

            $creds = [];
            if ($password !== '') {
                $creds['password'] = $password;
            }

            $mailPayload = [
                'credentials' => $creds,
                'settings' => array_filter([
                    'host' => $host,
                    'port' => (int) ($smtpConfig['port'] ?? 587),
                    'encryption' => $encryption,
                    'username' => $username ?: null,
                    'from_address' => $fromAddress,
                    'from_name' => $institute->name ?: (string) config('mail.from.name', 'TiT Education'),
                    'reply_to' => $resolver->resolveInstituteContactEmail($instituteId),
                ], static fn ($v) => $v !== null && $v !== ''),
            ];
        }

        // 4. Gemini (AI Assistant / OCR / Embeddings / Translation)
        $gemini = Integrations::platformGeminiCredentials($instituteId);
        $geminiPayload = null;
        if ($gemini !== null) {
            $geminiPayload = [
                'credentials' => [
                    'api_key' => $gemini->apiKey,
                ],
                'settings' => [],
            ];
        }

        // 5. Zoom (Provider entry records status/enabled)
        $zoom = Integrations::platformZoomCredentials($instituteId);
        $zoomPayload = null;
        if ($zoom !== null) {
            $zoomPayload = [
                'credentials' => [],
                'settings' => [
                    'enabled' => true,
                    'source' => 'env',
                ],
            ];
        }

        // 6. Google reCAPTCHA v3
        $recaptcha = Integrations::platformRecaptchaCredentials($instituteId);
        $recaptchaPayload = null;
        if ($recaptcha !== null) {
            $recaptchaPayload = [
                'credentials' => [
                    'secret_key' => $recaptcha->secretKey,
                ],
                'settings' => [
                    'site_key' => $recaptcha->siteKey,
                    'score_threshold' => $recaptcha->scoreThreshold,
                ],
            ];
        }

        // 7. Google OAuth Sign-In
        $google = Integrations::platformGoogleCredentials($instituteId);
        $googlePayload = null;
        if ($google !== null) {
            $googlePayload = [
                'credentials' => [
                    'client_secret' => $google->clientSecret,
                ],
                'settings' => array_filter([
                    'client_id' => $google->clientId,
                    'redirect_uri' => $google->redirectUri,
                ], static fn ($v) => $v !== null && $v !== ''),
            ];
        }

        return [
            InstituteIntegration::PROVIDER_PAYHERE => $payherePayload,
            InstituteIntegration::PROVIDER_WHATSAPP => $whatsappPayload,
            InstituteIntegration::PROVIDER_MAIL => $mailPayload,
            InstituteIntegration::PROVIDER_GEMINI => $geminiPayload,
            InstituteIntegration::PROVIDER_ZOOM => $zoomPayload,
            InstituteIntegration::PROVIDER_RECAPTCHA => $recaptchaPayload,
            InstituteIntegration::PROVIDER_GOOGLE => $googlePayload,
        ];
    }
}
