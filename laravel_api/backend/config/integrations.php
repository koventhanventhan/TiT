<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Environment / Platform Credential Fallback Allow-List
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of institute IDs allowed to fall back to `.env` /
    | platform PayHere and Meta WhatsApp credentials when no per-institute
    | `institute_integrations` row exists.
    |
    | Default: "1" (TiT) so existing production behaviour for Institute 1 is
    | preserved unchanged until `php artisan integrations:import-env 1` is run.
    | Every other institute NEVER receives platform PayHere or WhatsApp keys.
    |
    */
    'env_fallback_institutes' => array_values(array_filter(
        array_map(
            static fn (string $v): int => (int) trim($v),
            explode(',', (string) env('INTEGRATIONS_ENV_FALLBACK_INSTITUTES', '1'))
        ),
        static fn (int $id): bool => $id > 0
    )),

    /*
    |--------------------------------------------------------------------------
    | Mail Platform SMTP Fallback
    |--------------------------------------------------------------------------
    |
    | When true (default), institutes without custom SMTP credentials may send
    | transactional e-mail via the platform mailer, while `From` name is set to
    | the institute's name and `Reply-To` is set to the institute's contact
    | e-mail address.
    |
    */
    'mail_platform_fallback' => filter_var(
        env('INTEGRATIONS_MAIL_PLATFORM_FALLBACK', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    /*
    |--------------------------------------------------------------------------
    | PayHere Defaults
    |--------------------------------------------------------------------------
    */
    'payhere' => [
        'sandbox_url' => 'https://sandbox.payhere.lk/pay/checkout',
        'live_url' => 'https://www.payhere.lk/pay/checkout',
        'default_currency' => 'LKR',
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API Defaults & Event Template Mapping
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        'default_api_version' => 'v21.0',
        'templates' => [
            'welcome' => 'tit_welcome',
            'payment_reminder' => 'tit_payment_reminder',
            'payment_request_received' => 'tit_payment_request_received',
            'payment_success' => 'tit_payment_success',
            'payment_receipt' => 'tit_payment_receipt',
            'admin_approved' => 'tit_admin_approved',
            'general_update' => 'tit_general_update',
            'otp_verification' => 'tit_otp_verification',
            'default' => 'titeducation',
        ],
    ],
];
