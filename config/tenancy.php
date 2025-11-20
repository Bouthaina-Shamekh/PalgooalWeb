<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default subdomain root for tenant sites
    |--------------------------------------------------------------------------
    |
    | When a subscription uses the "subdomain" domain option we build the FQDN
    | by combining the generated slug with this root. Example result:
    | {subdomain}.wpgoals.com
    |
    */
    'subdomain_root' => env('TENANCY_SUBDOMAIN_ROOT', 'wpgoals.com'),

    /*
    |--------------------------------------------------------------------------
    | Maximum length for generated subdomain slug
    |--------------------------------------------------------------------------
    */
    'subdomain_max_length' => env('TENANCY_SUBDOMAIN_MAX_LENGTH', 24),

    /*
    |--------------------------------------------------------------------------
    | Primary marketing domain (requests here stay على المنصة الرئيسية)
    |--------------------------------------------------------------------------
    */
    'primary_domain' => env('TENANCY_PRIMARY_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?? 'localhost'),
];
