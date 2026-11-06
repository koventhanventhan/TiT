<?php

namespace App\Support\Credentials;

use App\Support\Redact;

final class WhatsAppCredentials
{
    /**
     * @param array<string, string> $templates
     */
    public function __construct(
        public readonly int $instituteId,
        public readonly string $accessToken,
        public readonly string $phoneNumberId,
        public readonly string $apiVersion = 'v21.0',
        public readonly array $templates = [],
        public readonly string $source = 'db', // db | env
        public readonly string $status = 'configured',
    ) {}

    /**
     * Resolve template name for a logical event key or legacy default template name.
     */
    public function templateFor(string $eventOrTemplate): string
    {
        $defaults = (array) config('integrations.whatsapp.templates', []);

        // Direct event key lookup in institute overrides (e.g. 'welcome' => 'inst_b_welcome')
        if (!empty($this->templates[$eventOrTemplate]) && is_string($this->templates[$eventOrTemplate])) {
            return trim($this->templates[$eventOrTemplate]);
        }

        // Reverse lookup: caller passed default template name e.g. 'tit_welcome'
        $matchedEvent = array_search($eventOrTemplate, $defaults, true);
        if (is_string($matchedEvent) && !empty($this->templates[$matchedEvent]) && is_string($this->templates[$matchedEvent])) {
            return trim($this->templates[$matchedEvent]);
        }

        // Fallback to configured default for logical event key
        if (isset($defaults[$eventOrTemplate]) && is_string($defaults[$eventOrTemplate])) {
            return $defaults[$eventOrTemplate];
        }

        return $eventOrTemplate;
    }

    public function maskedAccessToken(): string
    {
        return Redact::maskValue($this->accessToken);
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'instituteId' => $this->instituteId,
            'accessToken' => $this->maskedAccessToken(),
            'phoneNumberId' => $this->phoneNumberId,
            'apiVersion' => $this->apiVersion,
            'templates' => $this->templates,
            'source' => $this->source,
            'status' => $this->status,
        ];
    }
}
