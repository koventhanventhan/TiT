<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Models\Payment;
use App\Models\User;
use App\Support\Credentials\PayHereCredentials;
use App\Support\Integrations;
use App\Support\Redact;
use Illuminate\Support\Facades\Log;

class PayHereService
{
    protected ?int $explicitInstituteId;
    protected ?string $lastVerificationError = null;

    public function __construct(?int $instituteId = null)
    {
        $this->explicitInstituteId = $instituteId;
    }

    /**
     * Resolve a PayHereService instance scoped to a specific institute.
     * Respects shared container bindings so test mocks registered via app()->instance() continue working.
     */
    public static function forInstitute(int $instituteId): self
    {
        if (app()->isShared(self::class)) {
            return app(self::class);
        }

        return new self($instituteId);
    }

    /**
     * Resolve credentials dynamically from Integrations for the target or active tenant institute.
     */
    public function credentials(?int $fallbackInstituteId = null): ?PayHereCredentials
    {
        $targetId = $this->explicitInstituteId ?? $fallbackInstituteId;

        return Integrations::for($targetId)->payhere();
    }

    /**
     * Check if PayHere is properly configured for this institute.
     */
    public function isConfigured(): bool
    {
        return $this->credentials() !== null;
    }

    public function getCurrency(): string
    {
        return $this->credentials()?->currency ?? 'LKR';
    }

    public function getMerchantId(): ?string
    {
        return $this->credentials()?->merchantId;
    }

    /**
     * Get the PayHere checkout URL based on the institute's sandbox/live mode.
     */
    public function getCheckoutUrl(): string
    {
        $creds = $this->credentials();
        if ($creds !== null) {
            return $creds->checkoutUrl();
        }

        return (string) config('integrations.payhere.sandbox_url', 'https://sandbox.payhere.lk/pay/checkout');
    }

    /**
     * Generate the MD5 hash required by PayHere for checkout:
     * strtoupper(md5(merchant_id + order_id + number_format(amount,2) + currency + strtoupper(md5(merchant_secret))))
     */
    public function generateHash(string $orderId, float $amount, ?PayHereCredentials $creds = null): string
    {
        $resolved = $creds ?? $this->credentials();
        $merchantId = (string) ($resolved?->merchantId ?? '');
        $merchantSecret = (string) ($resolved?->merchantSecret ?? '');
        $currency = (string) ($resolved?->currency ?: 'LKR');

        $formattedAmount = number_format($amount, 2, '.', '');
        $secretHash = strtoupper(md5($merchantSecret));

        return strtoupper(md5(
            $merchantId .
            $orderId .
            $formattedAmount .
            $currency .
            $secretHash
        ));
    }

    /**
     * Validate format and build a local test checkout hash without contacting PayHere servers.
     *
     * @return array{valid: bool, sample_hash: ?string, checkout_url: string}
     */
    public function buildLocalConfigCheckHash(): array
    {
        $creds = $this->credentials();
        if ($creds === null || trim($creds->merchantId) === '' || trim($creds->merchantSecret) === '') {
            return [
                'valid' => false,
                'sample_hash' => null,
                'checkout_url' => $this->getCheckoutUrl(),
            ];
        }

        $sampleHash = $this->generateHash('CHECK_' . $creds->instituteId, 100.00, $creds);

        return [
            'valid' => strlen($sampleHash) === 32,
            'sample_hash' => $sampleHash,
            'checkout_url' => $creds->checkoutUrl(),
        ];
    }

    /**
     * Build the complete PayHere checkout params for the frontend JS SDK.
     */
    public function buildCheckoutParams(Payment $payment, User $user): array
    {
        $instituteId = (int) ($payment->institute_id ?: $user->institute_id ?: 0);
        $creds = $this->credentials($instituteId > 0 ? $instituteId : null);

        $orderId = (string) $payment->gateway_ref;
        $amount  = (float) $payment->amount;

        $institute = $payment->relationLoaded('institute') && $payment->institute
            ? $payment->institute
            : ($instituteId > 0 ? Institute::withoutGlobalScopes()->find($instituteId) : current_institute());

        $baseUrl = rtrim($institute?->primaryUrl() ?? config('app.url'), '/');
        $siteName = $institute?->name ?? 'Online Education';
        $currency = $creds?->currency ?: 'LKR';

        // Split user name into first and last
        $nameParts = explode(' ', $user->full_name ?? $user->name ?? 'Student', 2);
        $firstName = $nameParts[0] ?? 'Student';
        $lastName  = $nameParts[1] ?? '';

        $hash = $this->generateHash($orderId, $amount, $creds);

        return [
            'merchant_id'  => $creds?->merchantId,
            'return_url'   => $baseUrl . '/api/payhere/return',
            'cancel_url'   => $baseUrl . '/api/payhere/cancel',
            'notify_url'   => $baseUrl . '/api/payhere/notify',
            'order_id'     => $orderId,
            'items'        => $siteName . ' - Tuition Fee',
            'currency'     => $currency,
            'amount'       => number_format($amount, 2, '.', ''),
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'email'        => $user->email ?? '',
            'phone'        => $user->phone_number ?? '',
            'address'      => 'Sri Lanka',
            'city'         => 'Colombo',
            'country'      => 'Sri Lanka',
            'hash'         => $hash,
        ];
    }

    public function getLastVerificationError(): ?string
    {
        return $this->lastVerificationError;
    }

    /**
     * Verify PayHere server notification callback against THIS institute's merchant credentials
     * and (when provided) the Payment record's expected amount and currency.
     */
    public function verifyNotification(array $data, ?Payment $payment = null): bool
    {
        $this->lastVerificationError = null;

        $instituteId = $payment?->institute_id ? (int) $payment->institute_id : null;
        $creds = $this->credentials($instituteId);

        if ($creds === null) {
            $this->lastVerificationError = 'PayHere is not configured for this institute';
            Log::warning('PayHere notification rejected: credentials not configured for institute', [
                'institute_id' => $instituteId ?? $this->explicitInstituteId,
                'order_id' => $data['order_id'] ?? '',
            ]);
            return false;
        }

        $merchantId      = trim((string) ($data['merchant_id'] ?? ''));
        $orderId         = trim((string) ($data['order_id'] ?? ''));
        $payhereAmount   = trim((string) ($data['payhere_amount'] ?? ''));
        $payhereCurrency = strtoupper(trim((string) ($data['payhere_currency'] ?? '')));
        $statusCode      = (string) ($data['status_code'] ?? '');
        $md5sig          = strtoupper(trim((string) ($data['md5sig'] ?? '')));

        // (d) Require merchant_id in payload == this institute's merchant_id
        if ($merchantId === '' || !hash_equals($creds->merchantId, $merchantId)) {
            $this->lastVerificationError = 'Merchant ID does not match institute configuration';
            Log::warning('PayHere notification merchant_id mismatch', [
                'institute_id' => $creds->instituteId,
                'order_id' => $orderId,
                'payload' => Redact::array($data),
            ]);
            return false;
        }

        // (e) Require payhere_amount and payhere_currency to equal the payment's amount and currency
        if ($payment !== null) {
            $expectedAmount = number_format((float) $payment->amount, 2, '.', '');
            $normalizedPayloadAmount = is_numeric($payhereAmount)
                ? number_format((float) $payhereAmount, 2, '.', '')
                : '';

            if ($normalizedPayloadAmount === '' || $normalizedPayloadAmount !== $expectedAmount) {
                $this->lastVerificationError = 'Payment amount mismatch';
                Log::warning('PayHere notification amount mismatch', [
                    'institute_id' => $creds->instituteId,
                    'order_id' => $orderId,
                    'expected_amount' => $expectedAmount,
                    'received_amount' => $payhereAmount,
                ]);
                return false;
            }

            $expectedCurrency = strtoupper(trim((string) ($payment->meta['currency'] ?? $creds->currency ?: 'LKR')));
            if ($payhereCurrency === '' || $payhereCurrency !== $expectedCurrency) {
                $this->lastVerificationError = 'Payment currency mismatch';
                Log::warning('PayHere notification currency mismatch', [
                    'institute_id' => $creds->instituteId,
                    'order_id' => $orderId,
                    'expected_currency' => $expectedCurrency,
                    'received_currency' => $payhereCurrency,
                ]);
                return false;
            }
        }

        // (c) Verify md5sig with THIS institute's merchant secret
        $secretHash = strtoupper(md5($creds->merchantSecret));
        $localHash = strtoupper(md5(
            $merchantId .
            $orderId .
            $payhereAmount .
            $payhereCurrency .
            $statusCode .
            $secretHash
        ));

        if ($md5sig === '' || !hash_equals($localHash, $md5sig)) {
            $this->lastVerificationError = 'Invalid md5sig signature';
            Log::warning('PayHere notification hash mismatch', [
                'institute_id' => $creds->instituteId,
                'order_id' => $orderId,
                'status_code' => $statusCode,
            ]);
            return false;
        }

        // Automatically transition DB integration status from configured -> verified on first verified webhook
        if ($creds->source === 'db') {
            $row = Integrations::for($creds->instituteId)->findRow(InstituteIntegration::PROVIDER_PAYHERE);
            if ($row !== null && $row->status !== InstituteIntegration::STATUS_VERIFIED) {
                $row->markVerified();
            }
        }

        return true;
    }
}
