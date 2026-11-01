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
    | Platform Hosts
    |--------------------------------------------------------------------------
    |
    | Hosts where super-admin users have unrestricted access across all
    | institutes. Defaults to the host in config('app.url') and titjaffna.lk.
    |
    */
    'platform_hosts' => array_values(array_filter(array_unique(array_map('trim', explode(',', env('TENANT_PLATFORM_HOSTS', sprintf(
        '%s,titjaffna.lk,localhost,127.0.0.1',
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
    | Unscoped Models
    |--------------------------------------------------------------------------
    |
    | List of Eloquent models with an institute_id column that are intentionally
    | not using the BelongsToInstitute trait, with documented justification.
    |
    */
    'unscoped_models' => [
        // Models intentionally unscoped:
        // Currently all tenant-specific models with institute_id use BelongsToInstitute.
    ],

];

