<?php

return [
    'monthly_amount' => env('PAYMENT_MONTHLY_AMOUNT', 500),
    'payhere_merchant_id' => env('PAYHERE_MERCHANT_ID'),
    'payhere_merchant_secret' => env('PAYHERE_MERCHANT_SECRET'),
    'payhere_is_sandbox' => env('PAYHERE_IS_SANDBOX', true),
];
