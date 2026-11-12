<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Institute ID
    |--------------------------------------------------------------------------
    |
    | Used in local development, testing, and console contexts when no host
    | is supplied and no institute context is set.
    |
    */
    'default_institute_id' => (int) env('TENANT_DEFAULT_INSTITUTE_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Fallback Hosts
    |--------------------------------------------------------------------------
    |
    | Additional hosts that always resolve to Institute 1 as a safety net
    | even if the institute_domains table row is missing.
    |
    */
    'fallback_hosts' => array_values(array_filter(array_map('trim', explode(',', env('TENANT_FALLBACK_HOSTS', ''))))),

    /*
    |--------------------------------------------------------------------------
    | Platform Hosts
    |--------------------------------------------------------------------------
    |
    | Hosts where super-admin users have unrestricted access across all
    | institutes. Defaults to the host in config('app.url'), localhost, and 127.0.0.1.
    |
    */
    'platform_hosts' => array_values(array_filter(array_unique(array_map('trim', explode(',', env('TENANT_PLATFORM_HOSTS', sprintf(
        '%s,localhost,127.0.0.1',
        parse_url(env('APP_URL', 'http://127.0.0.1:8000'), PHP_URL_HOST) ?: 'localhost'
    ))))))),

    /*
    |--------------------------------------------------------------------------
    | Server IP Addresses
    |--------------------------------------------------------------------------
    |
    | Authoritative server IP addresses for DNS verification when adding or
    | checking tenant domains.
    |
    */
    'server_ips' => array_values(array_filter(array_map('trim', explode(',', env('TENANT_SERVER_IPS', ''))))),

    /*
    |--------------------------------------------------------------------------
    | Expiry Grace Days
    |--------------------------------------------------------------------------
    |
    | Number of days an institute subscription remains functional after expires_at.
    | During this grace period, admin sees a warning banner.
    |
    */
    'expiry_grace_days' => (int) env('TENANT_EXPIRY_GRACE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Platform Contact Information
    |--------------------------------------------------------------------------
    |
    | Contact details shown on blocked, expired, and subscription upgrade pages.
    | Empty values will cause corresponding contact lines to be hidden.
    |
    */
    'platform_contact_email' => env('TENANT_PLATFORM_CONTACT_EMAIL'),
    'platform_contact_phone' => env('TENANT_PLATFORM_CONTACT_PHONE'),
    'platform_contact_whatsapp' => env('TENANT_PLATFORM_CONTACT_WHATSAPP'),

    /*
    |--------------------------------------------------------------------------
    | Unscoped Models
    |--------------------------------------------------------------------------
    |
    | List of Eloquent models that are intentionally platform-wide or queried
    | before tenant resolution, with documented justification.
    |
    */
    'unscoped_models' => [
        \App\Models\Institute::class => 'Root tenant entity; represents institutes themselves.',
        \App\Models\InstituteDomain::class => 'Platform domain-to-tenant routing table; queried before tenant context is resolved.',
        \App\Models\SubscriptionPlan::class => 'Platform-wide subscription tier definitions available to all institutes.',
        \App\Models\InstituteSubscriptionNotice::class => 'Platform-wide subscription reminder notice history; managed by console daemon across tenants.',
    ],

];

