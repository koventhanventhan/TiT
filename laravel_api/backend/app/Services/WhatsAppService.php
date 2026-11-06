<?php

namespace App\Services;

use App\Models\InstituteIntegration;
use App\Support\Credentials\WhatsAppCredentials;
use App\Support\Integrations;
use App\Support\Redact;
use App\Support\Tenant;
use App\Support\WhatsAppSendResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $driver;
    protected ?int $explicitInstituteId = null;

    public function __construct(?int $instituteId = null)
    {
        $this->driver = (string) (config('services.whatsapp.driver') ?: 'meta');
        $this->explicitInstituteId = $instituteId;
    }

    /**
     * Resolve a WhatsAppService scoped to a specific institute, while honouring
     * container-shared test mocks when registered via $app->instance().
     */
    public static function forInstitute(?int $instituteId = null): self
    {
        if (app()->isShared(self::class)) {
            return app(self::class);
        }

        return new self($instituteId);
    }

    protected function resolveInstituteId(): ?int
    {
        if ($this->explicitInstituteId !== null && $this->explicitInstituteId > 0) {
            return $this->explicitInstituteId;
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

    public function credentials(bool $logWhenUnconfigured = false): ?WhatsAppCredentials
    {
        return Integrations::for($this->resolveInstituteId())->whatsapp($logWhenUnconfigured);
    }

    public function isConfigured(): bool
    {
        return $this->credentials(logWhenUnconfigured: false) !== null;
    }

    /**
     * Send a WhatsApp message to the given phone number.
     * For Meta driver: uses template messages (required by Meta for business-initiated messages).
     * Phone should be in E.164 format (e.g. +94771234567).
     */
    public function send(string $phone, string $message): bool
    {
        if ($this->driver === 'meta') {
            $phone = $this->normalizePhone($phone, false);
            return $this->sendMetaTemplateResult($phone, 'titeducation', 'en')->isSent();
        }

        $phone = $this->normalizePhone($phone);

        if ($this->driver === 'twilio') {
            return $this->sendViaTwilio($phone, $message, true);
        }

        if ($this->driver === 'other' && config('services.whatsapp.other_url')) {
            return $this->sendViaOther($phone, $message);
        }

        Log::info('WhatsApp (no driver): would send to ' . $phone . ': ' . substr($message, 0, 50) . '...');
        return false;
    }

    /**
     * Send a WhatsApp Template message via Meta Cloud API and return a typed WhatsAppSendResult.
     * Unconfigured institutes receive a "skipped" result (never throws, never marks failing).
     */
    public function sendTemplateResult(
        string $phone,
        string $templateName,
        string $languageCode = 'en',
        array $variables = []
    ): WhatsAppSendResult {
        $normalizedPhone = $this->normalizePhone($phone, false);

        $components = [];
        if (!empty($variables)) {
            $parameters = [];
            foreach ($variables as $value) {
                $parameters[] = ['type' => 'text', 'text' => (string) $value];
            }
            $components = [
                [
                    'type' => 'body',
                    'parameters' => $parameters,
                ],
            ];
        }

        return $this->sendMetaTemplateResult($normalizedPhone, $templateName, $languageCode, $components);
    }

    /**
     * Send a WhatsApp Template message via Meta Cloud API.
     * Returns true only when sent; false when skipped or failed (never throws).
     *
     * @param string $phone         Phone number (with country code e.g. 94767206279)
     * @param string $templateName  Logical event key or approved template name (e.g. 'tit_welcome')
     * @param string $languageCode  Template language code (e.g. 'en', 'en_US')
     * @param array  $variables     Simple array of variable values e.g. ['Kavin', 'kavin123']
     */
    public function sendTemplate(string $phone, string $templateName, string $languageCode = 'en', array $variables = []): bool
    {
        return $this->sendTemplateResult($phone, $templateName, $languageCode, $variables)->isSent();
    }

    /**
     * Send a normal SMS to the given phone number.
     */
    public function sendSMS(string $phone, string $message): bool
    {
        $phone = $this->normalizePhone($phone);

        if ($this->driver === 'twilio') {
            return $this->sendViaTwilio($phone, $message, false);
        }

        Log::info('SMS (no driver): would send to ' . $phone . ': ' . substr($message, 0, 50) . '...');
        return false;
    }

    protected function normalizePhone(string $phone, bool $withPlus = true): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '94' . substr($phone, 1); // Sri Lanka
        }
        if (strlen($phone) === 9 && in_array(substr($phone, 0, 1), ['7', '6'])) {
            $phone = '94' . $phone;
        }
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '7') {
            $phone = '94' . $phone;
        }

        return ($withPlus ? '+' : '') . $phone;
    }

    /**
     * Backwards-compatible protected helper returning bool.
     */
    protected function sendMetaTemplate(string $to, string $templateName, string $languageCode = 'en_US', array $components = []): bool
    {
        return $this->sendMetaTemplateResult($to, $templateName, $languageCode, $components)->isSent();
    }

    /**
     * Send a template message via Meta WhatsApp Cloud API using per-institute credentials.
     */
    protected function sendMetaTemplateResult(
        string $to,
        string $templateName,
        string $languageCode = 'en_US',
        array $components = []
    ): WhatsAppSendResult {
        $instituteId = $this->resolveInstituteId();
        $creds = Integrations::for($instituteId)->whatsapp(logWhenUnconfigured: true);

        if ($creds === null) {
            return WhatsAppSendResult::skipped('unconfigured');
        }

        $resolvedTemplate = $creds->templateFor($templateName);
        $secretsToScrub = [$creds->accessToken];

        try {
            $url = "https://graph.facebook.com/{$creds->apiVersion}/{$creds->phoneNumberId}/messages";
            $recipientPhone = preg_replace('/\D/', '', $to);

            $template = [
                'name' => $resolvedTemplate,
                'language' => [
                    'code' => $languageCode,
                ],
            ];

            if (!empty($components)) {
                $template['components'] = $components;
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipientPhone,
                'type' => 'template',
                'template' => $template,
            ];

            Log::debug('Meta WhatsApp Template Payload', [
                'institute_id' => $creds->instituteId,
                'to' => $recipientPhone,
                'template' => $resolvedTemplate,
            ]);

            $response = Http::withToken($creds->accessToken)
                ->timeout(30)
                ->withoutVerifying()
                ->post($url, $payload);

            if ($response->successful()) {
                $json = is_array($response->json()) ? $response->json() : [];
                $messageId = isset($json['messages'][0]['id']) ? (string) $json['messages'][0]['id'] : null;

                if ($creds->source === 'db') {
                    $row = Integrations::for($creds->instituteId)->findRow(InstituteIntegration::PROVIDER_WHATSAPP);
                    if ($row !== null && ($row->status !== InstituteIntegration::STATUS_VERIFIED || $row->last_error !== null)) {
                        $row->markVerified();
                    }
                }

                Log::info('WhatsApp template sent successfully via Meta', [
                    'institute_id' => $creds->instituteId,
                    'to' => $to,
                    'template' => $resolvedTemplate,
                    'message_id' => $messageId ?? 'N/A',
                ]);

                return WhatsAppSendResult::sent($messageId, $resolvedTemplate, Redact::array($json, $secretsToScrub));
            }

            $apiErrorMsg = is_array($response->json())
                ? (string) ($response->json('error.message') ?? json_encode(Redact::array($response->json(), $secretsToScrub)))
                : (string) $response->body();

            $sanitised = Redact::sanitizeText(
                "Meta WhatsApp HTTP {$response->status()}: {$apiErrorMsg}",
                $secretsToScrub
            );

            $this->recordFailureIfDbRow($creds, $sanitised, $secretsToScrub);

            Log::error('Meta WhatsApp template failed to send', [
                'institute_id' => $creds->instituteId,
                'to' => $to,
                'template' => $resolvedTemplate,
                'status' => $response->status(),
                'error' => $sanitised,
            ]);

            return WhatsAppSendResult::failed($sanitised, $resolvedTemplate);
        } catch (\Throwable $e) {
            $sanitised = Redact::sanitizeText(
                'Meta WhatsApp transport error: ' . $e->getMessage(),
                $secretsToScrub
            );

            $this->recordFailureIfDbRow($creds, $sanitised, $secretsToScrub);

            Log::error('Meta WhatsApp template send exception', [
                'institute_id' => $creds->instituteId,
                'template' => $resolvedTemplate,
                'error' => $sanitised,
            ]);

            return WhatsAppSendResult::failed($sanitised, $resolvedTemplate);
        }
    }

    /**
     * @param array<int, string> $secretsToScrub
     */
    protected function recordFailureIfDbRow(WhatsAppCredentials $creds, string $sanitisedError, array $secretsToScrub = []): void
    {
        try {
            $row = Integrations::for($creds->instituteId)->findRow(InstituteIntegration::PROVIDER_WHATSAPP);
            if ($row !== null && $row->status !== InstituteIntegration::STATUS_UNCONFIGURED) {
                $row->recordFailure($sanitisedError, $secretsToScrub);
            }
        } catch (\Throwable $e) {
            Log::warning('Could not record WhatsApp integration failure: ' . Redact::sanitizeText($e->getMessage(), $secretsToScrub));
        }
    }

    protected function sendViaTwilio(string $to, string $body, bool $isWhatsApp = true): bool
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = $isWhatsApp
            ? config('services.twilio.whatsapp_from')
            : config('services.twilio.sms_from', env('TWILIO_SMS_FROM'));

        if (!$sid || !$token || !$from) {
            Log::warning('Twilio ' . ($isWhatsApp ? 'WhatsApp' : 'SMS') . ' not configured. Skipping send to ' . $to);
            return false;
        }

        try {
            $toFormatted = $isWhatsApp ? 'whatsapp:' . ltrim($to, '+') : $to;
            $fromFormatted = $isWhatsApp && !str_starts_with($from, 'whatsapp:') ? 'whatsapp:' . $from : $from;

            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post('https://api.twilio.com/2010-04-01/Accounts/' . $sid . '/Messages.json', [
                    'To' => $toFormatted,
                    'From' => $fromFormatted,
                    'Body' => $body,
                ]);

            if ($response->successful()) {
                Log::info(($isWhatsApp ? 'WhatsApp' : 'SMS') . ' sent successfully to ' . $to . ' via Twilio. SID: ' . ($response->json()['sid'] ?? 'N/A'));
                return true;
            }

            Log::error('Twilio ' . ($isWhatsApp ? 'WhatsApp' : 'SMS') . ' failed to send', [
                'to' => $to,
                'status' => $response->status(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error(($isWhatsApp ? 'WhatsApp' : 'SMS') . ' send exception: ' . Redact::sanitizeText($e->getMessage(), array_filter([(string) $token])));
            return false;
        }
    }

    protected function sendViaOther(string $to, string $body): bool
    {
        $url = config('services.whatsapp.other_url');
        $method = config('services.whatsapp.other_method', 'POST');
        $headers = config('services.whatsapp.other_headers', []);

        try {
            $payload = array_merge(
                config('services.whatsapp.other_payload', []),
                ['phone' => $to, 'message' => $body]
            );
            $response = Http::withHeaders($headers)->$method($url, $payload);
            if ($response->successful()) {
                return true;
            }
            Log::error('WhatsApp other driver failed', ['to' => $to, 'status' => $response->status()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp other send exception: ' . Redact::sanitizeText($e->getMessage()));
            return false;
        }
    }
}
