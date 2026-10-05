<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayHere Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Merchant ID & Merchant Secret → found in PayHere Dashboard → Settings → Integrations
    | App ID & App Secret → found in PayHere Dashboard → Settings → Business App
    |
    */

    'merchant_id'     => env('PAYHERE_MERCHANT_ID', ''),
    'merchant_secret' => env('PAYHERE_MERCHANT_SECRET', ''),
    'app_id'          => env('PAYHERE_APP_ID', ''),
    'app_secret'      => env('PAYHERE_APP_SECRET', ''),
    'is_sandbox'      => env('PAYHERE_IS_SANDBOX', true),

    // Base URLs
    'sandbox_url'     => 'https://sandbox.payhere.lk/pay/checkout',
    'live_url'        => 'https://www.payhere.lk/pay/checkout',

    // Default currency (LKR for Sri Lanka)
    'currency'        => env('PAYHERE_CURRENCY', 'LKR'),
];
