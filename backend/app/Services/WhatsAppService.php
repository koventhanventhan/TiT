<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $driver;

    public function __construct()
    {
        $this->driver = config('services.whatsapp.driver', 'twilio');
    }

    /**
     * Send a WhatsApp message to the given phone number.
     * Phone should be in E.164 format (e.g. +94771234567).
     */
    public function send(string $phone, string $message): bool
    {
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

    protected function normalizePhone(string $phone): string
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
        return '+' . $phone;
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

            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post('https://api.twilio.com/2010-04-01/Accounts/' . $sid . '/Messages.json', [
                    'To' => $toFormatted,
                    'From' => $fromFormatted,
                    'Body' => $body,
                ]);

            if ($response->successful()) {
                Log::info(($isWhatsApp ? 'WhatsApp' : 'SMS') . ' sent to ' . $to . ' via Twilio');
                return true;
            }

            Log::error('Twilio ' . ($isWhatsApp ? 'WhatsApp' : 'SMS') . ' failed', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error(($isWhatsApp ? 'WhatsApp' : 'SMS') . ' send exception: ' . $e->getMessage());
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
