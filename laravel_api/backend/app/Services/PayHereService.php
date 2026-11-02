<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PayHereService
{
    protected ?string $merchantId = null;
    protected ?string $merchantSecret = null;
    protected bool $isSandbox;
    protected string $currency;

    public function __construct()
    {
        $mId = config('payhere.merchant_id') ?: env('PAYHERE_MERCHANT_ID');
        $this->merchantId     = ($mId !== null && trim((string) $mId) !== '') ? (string) $mId : null;
        $mSec = config('payhere.merchant_secret') ?: env('PAYHERE_MERCHANT_SECRET');
        $this->merchantSecret = ($mSec !== null && trim((string) $mSec) !== '') ? (string) $mSec : null;
        $this->isSandbox      = (bool) config('payhere.is_sandbox', true);
        $this->currency       = (string) (config('payhere.currency') ?: 'LKR');
    }

    /**
     * Get the PayHere checkout URL based on sandbox mode
     */
    public function getCheckoutUrl(): string
    {
        return $this->isSandbox
            ? config('payhere.sandbox_url')
            : config('payhere.live_url');
    }

    /**
     * Generate the MD5 hash required by PayHere for checkout
     *
     * Hash formula: strtoupper(md5(
     *   merchant_id + order_id + number_format(amount,2) + currency + strtoupper(md5(merchant_secret))
     * ))
     */
    public function generateHash(string $orderId, float $amount): string
    {
        $formattedAmount = number_format($amount, 2, '.', '');
        $secretHash = strtoupper(md5((string) $this->merchantSecret));

        return strtoupper(md5(
            ((string) $this->merchantId) .
            $orderId .
            $formattedAmount .
            $this->currency .
            $secretHash
        ));
    }

    /**
     * Build the complete PayHere checkout params for the frontend JS SDK
     */
    public function buildCheckoutParams(Payment $payment, User $user): array
    {
        $orderId = $payment->gateway_ref;
        $amount  = (float) $payment->amount;

        $institute = $payment->institute ?: ($payment->institute_id ? \App\Models\Institute::find($payment->institute_id) : current_institute());
        $baseUrl = rtrim($institute?->primaryUrl() ?? config('app.url'), '/');
        $siteName = $institute?->name ?? 'Online Education';

        // Split user name into first and last
        $nameParts = explode(' ', $user->full_name ?? $user->name ?? 'Student', 2);
        $firstName = $nameParts[0] ?? 'Student';
        $lastName  = $nameParts[1] ?? '';

        $hash = $this->generateHash($orderId, $amount);

        return [
            'merchant_id'  => $this->merchantId,
            'return_url'   => $baseUrl . '/api/payhere/return',
            'cancel_url'   => $baseUrl . '/api/payhere/cancel',
            'notify_url'   => $baseUrl . '/api/payhere/notify',
            'order_id'     => $orderId,
            'items'        => $siteName . ' - Tuition Fee',
            'currency'     => $this->currency,
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

    /**
     * Verify the notification hash from PayHere server callback
     *
     * Verify formula: strtoupper(md5(
     *   merchant_id + order_id + payhere_amount + payhere_currency + status_code + strtoupper(md5(merchant_secret))
     * ))
     */
    public function verifyNotification(array $data): bool
    {
        $merchantId     = $data['merchant_id'] ?? '';
        $orderId        = $data['order_id'] ?? '';
        $payhereAmount  = $data['payhere_amount'] ?? '';
        $payhereCurrency = $data['payhere_currency'] ?? '';
        $statusCode     = $data['status_code'] ?? '';
        $md5sig         = $data['md5sig'] ?? '';

        $secretHash = strtoupper(md5((string) $this->merchantSecret));

        $localHash = strtoupper(md5(
            $merchantId .
            $orderId .
            $payhereAmount .
            $payhereCurrency .
            $statusCode .
            $secretHash
        ));

        $isValid = ($localHash === $md5sig);

        if (!$isValid) {
            Log::warning('PayHere notification hash mismatch', [
                'order_id'    => $orderId,
                'expected'    => $localHash,
                'received'    => $md5sig,
                'status_code' => $statusCode,
            ]);
        }

        return $isValid;
    }

    /**
     * Check if PayHere is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->merchantId) && !empty($this->merchantSecret);
    }
}
