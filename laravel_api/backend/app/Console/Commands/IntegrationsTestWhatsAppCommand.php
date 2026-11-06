<?php

namespace App\Console\Commands;

use App\Models\Institute;
use App\Services\WhatsAppService;
use App\Support\Redact;
use Illuminate\Console\Command;

class IntegrationsTestWhatsAppCommand extends Command
{
    protected $signature = 'integrations:test-whatsapp
                            {institute : Institute ID or slug}
                            {to : Destination phone number in E.164 format (e.g. +94771234567)}';

    protected $description = 'Send an authenticated CLI test WhatsApp template message using an institute\'s resolved credentials';

    public function handle(): int
    {
        $arg = (string) $this->argument('institute');
        $to = trim((string) $this->argument('to'));

        if (!preg_match('/^\+[1-9]\d{7,14}$/', $to)) {
            $this->error('Destination phone number must be in E.164 format (for example +94771234567).');
            return self::FAILURE;
        }

        $institute = ctype_digit($arg)
            ? Institute::withoutGlobalScopes()->find((int) $arg)
            : Institute::withoutGlobalScopes()->where('slug', $arg)->first();

        if (!$institute) {
            $this->error("Institute [{$arg}] was not found.");
            return self::FAILURE;
        }

        $service = WhatsAppService::forInstitute((int) $institute->id);
        if (!$service->isConfigured()) {
            $this->warn("WhatsApp is not configured for institute #{$institute->id} ({$institute->name}).");
            return self::FAILURE;
        }

        $result = $service->sendTemplateResult(
            $to,
            'general_update',
            'en',
            [$institute->name, 'CLI integration verification test']
        );

        if ($result->isSent()) {
            $msgId = $result->messageId ?: 'n/a';
            $this->info("WhatsApp test message sent for institute #{$institute->id} ({$institute->name}) to {$to} [message_id={$msgId}].");
            return self::SUCCESS;
        }

        $sanitisedReason = Redact::sanitizeText($result->reason ?? 'Unknown failure') ?? 'Unknown failure';
        $this->error("WhatsApp test message {$result->status}: {$sanitisedReason}");

        return self::FAILURE;
    }
}
