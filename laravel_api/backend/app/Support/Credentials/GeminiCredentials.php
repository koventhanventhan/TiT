<?php

namespace App\Support\Credentials;

use App\Support\Redact;

final class GeminiCredentials
{
    public function __construct(
        public readonly int $instituteId,
        public readonly string $apiKey,
        public readonly string $source = 'db', // db | env
        public readonly string $status = 'configured',
    ) {}

    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '';
    }

    public function maskedApiKey(): string
    {
        return Redact::maskValue($this->apiKey);
    }

    /**
     * Non-secret array representation suitable for logging or admin previews.
     *
     * @return array<string, mixed>
     */
    public function redacted(): array
    {
        return [
            'institute_id' => $this->instituteId,
            'api_key' => $this->maskedApiKey(),
            'source' => $this->source,
            'status' => $this->status,
        ];
    }

    /**
     * Never expose plaintext API keys in var_dump / debug output.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'instituteId' => $this->instituteId,
            'apiKey' => $this->maskedApiKey(),
            'source' => $this->source,
            'status' => $this->status,
        ];
    }
}
