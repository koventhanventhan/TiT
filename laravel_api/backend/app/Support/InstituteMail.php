<?php

namespace App\Support;

use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Support\Credentials\MailCredentials;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\Mailable as ConcreteMailable;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;

final class InstituteMail
{
    /**
     * Explicit institute ID currently dispatching a queued mailable (read by Queue::createPayloadUsing).
     */
    public static ?int $dispatchingInstituteId = null;

    private static ?string $lastError = null;
    private static ?\Throwable $lastException = null;

    public static function to(mixed $users, ?int $instituteId = null): InstitutePendingMail
    {
        return new InstitutePendingMail($users, $instituteId);
    }

    public static function resolveInstituteId(?int $instituteId = null): int
    {
        if ($instituteId !== null && $instituteId > 0) {
            return $instituteId;
        }

        if (self::$dispatchingInstituteId !== null && self::$dispatchingInstituteId > 0) {
            return self::$dispatchingInstituteId;
        }

        $tenantId = Tenant::id();
        if ($tenantId !== null && $tenantId > 0) {
            return $tenantId;
        }

        if (auth()->check() && !empty(auth()->user()?->institute_id)) {
            return (int) auth()->user()->institute_id;
        }

        return 1;
    }

    /**
     * Register and return the runtime per-institute mailer (`mail.mailers.institute_{id}`).
     *
     * @return array{mailer: ?string, creds: ?MailCredentials, institute_id: int}
     */
    public static function configureMailer(?int $instituteId = null): array
    {
        $resolvedId = self::resolveInstituteId($instituteId);
        $creds = Integrations::for($resolvedId)->mail();

        if ($creds === null) {
            return [
                'mailer' => null,
                'creds' => null,
                'institute_id' => $resolvedId,
            ];
        }

        $mailerName = 'institute_' . $creds->instituteId;

        $useArrayInTesting = app()->environment('testing')
            && config('mail.default') === 'array'
            && !config('integrations.testing_real_smtp_transport', false);

        if ($creds->source === 'db') {
            $transport = $useArrayInTesting ? 'array' : 'smtp';
            $scheme = match ($creds->encryption) {
                'ssl' => 'smtps',
                default => 'smtp',
            };

            $mailerConfig = [
                'transport' => $transport,
                'scheme' => $scheme,
                'host' => $creds->host,
                'port' => $creds->port ?? 587,
                'encryption' => $creds->encryption,
                'username' => $creds->username,
                'password' => $creds->password,
                'timeout' => 15,
                'auto_tls' => $creds->encryption !== null,
                'local_domain' => parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost',
                'from' => [
                    'address' => $creds->fromAddress,
                    'name' => $creds->fromName,
                ],
                'reply_to' => $creds->replyToAddress ? [
                    'address' => $creds->replyToAddress,
                    'name' => $creds->replyToName ?: $creds->fromName,
                ] : null,
                'source' => 'db',
                'institute_id' => $creds->instituteId,
            ];
        } else {
            $baseMailer = $useArrayInTesting ? 'array' : ($creds->mailer ?: 'log');
            $baseConfig = (array) config("mail.mailers.{$baseMailer}", ['transport' => $baseMailer]);

            $mailerConfig = array_merge($baseConfig, [
                'transport' => $baseConfig['transport'] ?? $baseMailer,
                'from' => [
                    'address' => $creds->fromAddress,
                    'name' => $creds->fromName,
                ],
                'reply_to' => $creds->replyToAddress ? [
                    'address' => $creds->replyToAddress,
                    'name' => $creds->replyToName ?: $creds->fromName,
                ] : null,
                'source' => 'env',
                'institute_id' => $creds->instituteId,
            ]);
        }

        $previousConfig = config("mail.mailers.{$mailerName}");
        config(["mail.mailers.{$mailerName}" => $mailerConfig]);

        // Purge cached mailer instance only when config changed and Mail::fake() is not active
        $facadeRoot = Mail::getFacadeRoot();
        if (!($facadeRoot instanceof MailFake) && $previousConfig !== $mailerConfig && method_exists($facadeRoot, 'purge')) {
            $facadeRoot->purge($mailerName);
        }

        return [
            'mailer' => $mailerName,
            'creds' => $creds,
            'institute_id' => $creds->instituteId,
        ];
    }

    /**
     * Apply per-institute mailer, From, and Reply-To defaults to a Mailable before sending/queueing.
     */
    public static function applyEnvelopeDefaults(Mailable $mailable, MailCredentials $creds, string $mailerName): void
    {
        if (method_exists($mailable, 'mailer')) {
            $mailable->mailer($mailerName);
        }

        if ($mailable instanceof ConcreteMailable) {
            if (empty($mailable->from)) {
                $mailable->from($creds->fromAddress, $creds->fromName);
            }
            if ($creds->replyToAddress && empty($mailable->replyTo)) {
                $mailable->replyTo($creds->replyToAddress, $creds->replyToName ?: $creds->fromName);
            }
        }
    }

    /**
     * Send a raw text email through the institute's resolved mailer.
     */
    public static function raw(string $text, callable $callback, ?int $instituteId = null, bool $rethrow = false): bool
    {
        self::clearLastError();
        $configured = self::configureMailer($instituteId);
        $mailerName = $configured['mailer'];
        $creds = $configured['creds'];
        $resolvedId = $configured['institute_id'];

        if ($mailerName === null || $creds === null) {
            $msg = "Mail is not configured for institute #{$resolvedId}.";
            self::setUnconfiguredReason($msg);
            if ($rethrow) {
                throw new \RuntimeException($msg);
            }
            return false;
        }

        try {
            // integration-ok: canonical single entry point for raw mail via resolved institute mailer
            Mail::mailer($mailerName)->raw($text, function (Message $message) use ($callback, $creds) {
                $message->from($creds->fromAddress, $creds->fromName);
                if ($creds->replyToAddress) {
                    $message->replyTo($creds->replyToAddress, $creds->replyToName ?: $creds->fromName);
                }
                $callback($message);
            });

            if (!(Mail::getFacadeRoot() instanceof MailFake) && $creds->source === 'db') {
                self::markVerifiedIfDbRow($resolvedId);
            }

            return true;
        } catch (\Throwable $e) {
            $sanitised = self::handleFailure($resolvedId, $e);
            if ($rethrow) {
                throw new \RuntimeException($sanitised);
            }
            return false;
        }
    }

    /**
     * Send a verification/test email to the specified admin address and update integration status.
     *
     * @return array{success: bool, message: string}
     */
    public static function sendTestEmail(int $instituteId, string $recipientEmail): array
    {
        $instituteName = Institute::withoutGlobalScopes()->find($instituteId)?->name ?? "Institute #{$instituteId}";
        $body = "This is a test e-mail sent from {$instituteName} to verify your outgoing e-mail settings.\n\nSent at: " . now()->toDateTimeString();

        try {
            self::raw($body, function (Message $message) use ($recipientEmail, $instituteName) {
                $message->to($recipientEmail)
                    ->subject("Test E-mail – {$instituteName}");
            }, instituteId: $instituteId, rethrow: true);

            return [
                'success' => true,
                'message' => "Test e-mail sent to {$recipientEmail}.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => Redact::sanitizeText($e->getMessage()),
            ];
        }
    }

    public static function handleFailure(?int $instituteId, \Throwable $e): string
    {
        $resolvedId = self::resolveInstituteId($instituteId);
        $creds = Integrations::for($resolvedId)->mail();

        $secretsToScrub = array_values(array_filter([
            $creds?->password,
        ]));

        $sanitised = Redact::sanitizeText('SMTP delivery error: ' . $e->getMessage(), $secretsToScrub);

        self::$lastError = $sanitised;
        self::$lastException = $e;

        try {
            $row = Integrations::for($resolvedId)->findRow(InstituteIntegration::PROVIDER_MAIL);
            if ($row !== null && $row->status !== InstituteIntegration::STATUS_UNCONFIGURED) {
                $row->recordFailure($sanitised, $secretsToScrub);
            }
        } catch (\Throwable $inner) {
            Log::warning('Could not record mail integration failure: ' . Redact::sanitizeText($inner->getMessage(), $secretsToScrub));
        }

        Log::error('InstituteMail delivery failed', [
            'institute_id' => $resolvedId,
            'error' => $sanitised,
        ]);

        return $sanitised;
    }

    public static function markVerifiedIfDbRow(int $instituteId): void
    {
        try {
            $row = Integrations::for($instituteId)->findRow(InstituteIntegration::PROVIDER_MAIL);
            if ($row !== null && ($row->status !== InstituteIntegration::STATUS_VERIFIED || $row->last_error !== null)) {
                $row->markVerified();
            }
        } catch (\Throwable $e) {
            Log::warning('Could not mark mail integration verified: ' . Redact::sanitizeText($e->getMessage()));
        }
    }

    public static function clearLastError(): void
    {
        self::$lastError = null;
        self::$lastException = null;
    }

    public static function setUnconfiguredReason(string $reason): void
    {
        self::$lastError = Redact::sanitizeText($reason);
    }

    public static function getLastError(): ?string
    {
        return self::$lastError;
    }

    public static function getLastException(): ?\Throwable
    {
        return self::$lastException;
    }
}
