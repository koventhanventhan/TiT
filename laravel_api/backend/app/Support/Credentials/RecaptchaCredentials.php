<?php

namespace App\Support\Credentials;

use App\Support\Redact;

final class RecaptchaCredentials
{
    public function __construct(
        public readonly int $instituteId,
        public readonly string $siteKey,
        public readonly string $secretKey,
        public readonly float $scoreThreshold = 0.5,
        public readonly string $source = 'db', // db | env
        public readonly string $status = 'configured',
    ) {}

    public function isConfigured(): bool
    {
        return trim($this->siteKey) !== '' && trim($this->secretKey) !== '';
    }

    public function siteKey(): string
    {
        return $this->siteKey;
    }

    public function secretKey(): string
    {
        return $this->secretKey;
    }

    public function scoreThreshold(): float
    {
        return $this->scoreThreshold;
    }

    public function maskedSecretKey(): string
    {
        return Redact::maskValue($this->secretKey);
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
            'site_key' => $this->siteKey,
            'secret_key' => $this->maskedSecretKey(),
            'score_threshold' => $this->scoreThreshold,
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
            'siteKey' => $this->siteKey,
            'secretKey' => $this->maskedSecretKey(),
            'scoreThreshold' => $this->scoreThreshold,
            'source' => $this->source,
            'status' => $this->status,
        ];
    }
}
