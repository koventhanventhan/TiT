<?php

namespace App\Support\Credentials;

final class ZoomCredentials
{
    public function __construct(
        public readonly int $instituteId,
        public readonly int $accountsCount = 0,
        public readonly int $activeAccountsCount = 0,
        public readonly bool $isVirtual = false,
        public readonly string $source = 'db', // db | env | none
        public readonly string $status = 'configured', // configured | unconfigured | verified | failing
        public readonly ?string $accountId = null,
        public readonly ?string $clientId = null,
        public readonly ?string $clientSecret = null,
    ) {}

    public function accountId(): ?string
    {
        return $this->accountId;
    }

    public function clientId(): ?string
    {
        return $this->clientId;
    }

    public function clientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function isConfigured(): bool
    {
        return $this->activeAccountsCount > 0 || $this->isVirtual;
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
            'accounts_count' => $this->accountsCount,
            'active_accounts_count' => $this->activeAccountsCount,
            'is_virtual' => $this->isVirtual,
            'source' => $this->source,
            'status' => $this->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return $this->redacted();
    }
}
