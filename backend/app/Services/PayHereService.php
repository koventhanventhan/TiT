<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PayHereService
{
    protected string $merchantId;
    protected string $merchantSecret;
    protected bool $isSandbox;
    protected string $currency;

    public function __construct()
    {
        $this->merchantId     = config('payhere.merchant_id');
        $this->merchantSecret = config('payhere.merchant_secret');
        $this->isSandbox      = config('payhere.is_sandbox', true);
        $this->currency       = config('payhere.currency', 'LKR');
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
        $secretHash = strtoupper(md5($this->merchantSecret));

        return strtoupper(md5(
            $this->merchantId .
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

        // Split user name into first and last
        $nameParts = explode(' ', $user->full_name ?? $user->name ?? 'Student', 2);
        $firstName = $nameParts[0] ?? 'Student';
        $lastName  = $nameParts[1] ?? '';

        $hash = $this->generateHash($orderId, $amount);

        return [
            'merchant_id'  => $this->merchantId,
            'return_url'   => config('app.url') . '/api/payhere/return',
            'cancel_url'   => config('app.url') . '/api/payhere/cancel',
            'notify_url'   => route('payhere.notify'),
            'order_id'     => $orderId,
            'items'        => 'TiT Education - Tuition Fee',
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

        $secretHash = strtoupper(md5($this->merchantSecret));

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
