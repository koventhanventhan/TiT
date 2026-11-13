<?php

namespace App\Support\Credentials;

use App\Support\Redact;

final class GoogleCredentials
{
    public function __construct(
        public readonly int $instituteId,
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly ?string $redirectUri = null,
        public readonly string $source = 'db', // db | env
        public readonly string $status = 'configured',
    ) {}

    public function isConfigured(): bool
    {
        return trim($this->clientId) !== '' && trim($this->clientSecret) !== '';
    }

    public function maskedClientSecret(): string
    {
        return Redact::maskValue($this->clientSecret);
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
            'client_id' => $this->clientId,
            'client_secret' => $this->maskedClientSecret(),
            'redirect_uri' => $this->redirectUri,
            'source' => $this->source,
            'status' => $this->status,
        ];
    }

    /**
     * Never expose plaintext secrets in var_dump / debug output.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'instituteId' => $this->instituteId,
            'clientId' => $this->clientId,
            'clientSecret' => $this->maskedClientSecret(),
            'redirectUri' => $this->redirectUri,
            'source' => $this->source,
            'status' => $this->status,
        ];
    }
}
