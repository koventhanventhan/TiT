<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $driver;

    public function __construct()
    {
        $this->driver = config('services.whatsapp.driver', 'meta');
    }

    /**
     * Send a WhatsApp message to the given phone number.
     * For Meta driver: uses template messages (required by Meta for business-initiated messages).
     * Phone should be in E.164 format (e.g. +94771234567).
     */
    public function send(string $phone, string $message): bool
    {
        if ($this->driver === 'meta') {
            // Meta requires templates for business-initiated messages.
            // Use titeducation template for all automated notifications.
            $phone = $this->normalizePhone($phone, false);
            return $this->sendMetaTemplate($phone, 'titeducation', 'en');
        }

        $phone = $this->normalizePhone($phone);

        if ($this->driver === 'twilio') {
            return $this->sendViaTwilio($phone, $message, true);
        }

        if ($this->driver === 'other' && config('services.whatsapp.other_url')) {
            return $this->sendViaOther($phone, $message);
        }

        Log::info('WhatsApp (no driver): would send to ' . $phone . ': ' . substr($message, 0, 50) . '...');
        return true;
    }

    /**
     * Send a WhatsApp Template message via Meta Cloud API.
     * This is the ONLY way to send business-initiated messages via Meta.
     *
     * @param string $phone         Phone number (with country code e.g. 94767206279)
     * @param string $templateName  The approved template name (e.g. 'tit_welcome')
     * @param string $languageCode  Template language code (e.g. 'en', 'en_US')
     * @param array  $variables     Simple array of variable values e.g. ['Kavin', 'kavin123']
     */
    public function sendTemplate(string $phone, string $templateName, string $languageCode = 'en', array $variables = []): bool
    {
        $phone = $this->normalizePhone($phone, false);

        // Build Meta components from simple variables
        $components = [];
        if (!empty($variables)) {
            $parameters = [];
            foreach ($variables as $value) {
                $parameters[] = ['type' => 'text', 'text' => (string) $value];
            }
            $components = [
                [
                    'type' => 'body',
                    'parameters' => $parameters
                ]
            ];
        }

        return $this->sendMetaTemplate($phone, $templateName, $languageCode, $components);
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
        return true;
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
     * Send a template message via Meta WhatsApp Cloud API.
     */
    protected function sendMetaTemplate(string $to, string $templateName, string $languageCode = 'en_US', array $components = []): bool
    {
        $token = config('services.meta_whatsapp.token');
        $phoneNumberId = config('services.meta_whatsapp.phone_number_id');
        $apiVersion = config('services.meta_whatsapp.api_version', 'v21.0');

        if (!$token || !$phoneNumberId) {
            Log::warning('Meta WhatsApp not configured (missing token or ID). Skipping send to ' . $to);
            return true;
        }

        try {
            $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";
            $recipientPhone = preg_replace('/\D/', '', $to);

            $template = [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode
                ]
            ];

            // Add components if provided (for templates with variables)
            if (!empty($components)) {
                $template['components'] = $components;
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipientPhone,
                'type' => 'template',
                'template' => $template
            ];

            Log::debug('Meta WhatsApp Template Payload:', $payload);

            $response = Http::withToken($token)
                ->timeout(30)
                ->withoutVerifying()
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp template "' . $templateName . '" sent successfully to ' . $to . ' via Meta. ID: ' . ($response->json()['messages'][0]['id'] ?? 'N/A'));
                return true;
            }

            Log::error('Meta WhatsApp template failed to send', [
                'to' => $to,
                'template' => $templateName,
                'status' => $response->status(),
                'response_body' => $response->json() ?? $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Meta WhatsApp template send exception: ' . $e->getMessage(), [
                'template' => $templateName,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
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
            return true;
        }

        try {
            $toFormatted = $isWhatsApp ? 'whatsapp:' . ltrim($to, '+') : $to;
            $fromFormatted = $isWhatsApp && !str_starts_with($from, 'whatsapp:') ? 'whatsapp:' . $from : $from;

            Log::debug('Attempting to send Twilio ' . ($isWhatsApp ? 'WhatsApp' : 'SMS'), [
                'to' => $toFormatted,
                'from' => $fromFormatted,
                'body_snippet' => substr($body, 0, 50)
            ]);

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
                'response_body' => $response->json() ?? $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error(($isWhatsApp ? 'WhatsApp' : 'SMS') . ' send exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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
            Log::error('WhatsApp other send exception: ' . $e->getMessage());
            return false;
        }
    }
}
