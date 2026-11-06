<?php

namespace App\Support\Credentials;

use App\Support\Redact;

final class PayHereCredentials
{
    public function __construct(
        public readonly int $instituteId,
        public readonly string $merchantId,
        public readonly string $merchantSecret,
        public readonly ?string $appId = null,
        public readonly ?string $appSecret = null,
        public readonly bool $isSandbox = true,
        public readonly string $currency = 'LKR',
        public readonly string $source = 'db', // db | env
        public readonly string $status = 'configured',
    ) {}

    public function checkoutUrl(): string
    {
        return $this->isSandbox
            ? (string) config('integrations.payhere.sandbox_url', 'https://sandbox.payhere.lk/pay/checkout')
            : (string) config('integrations.payhere.live_url', 'https://www.payhere.lk/pay/checkout');
    }

    public function maskedMerchantSecret(): string
    {
        return Redact::maskValue($this->merchantSecret);
    }

    public function maskedAppSecret(): string
    {
        return Redact::maskValue($this->appSecret);
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
            'merchantId' => $this->merchantId,
            'merchantSecret' => $this->maskedMerchantSecret(),
            'appId' => $this->appId,
            'appSecret' => $this->maskedAppSecret(),
            'isSandbox' => $this->isSandbox,
            'currency' => $this->currency,
            'source' => $this->source,
            'status' => $this->status,
        ];
    }
}
