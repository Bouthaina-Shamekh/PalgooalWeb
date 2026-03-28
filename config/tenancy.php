<?php

$legacySubdomainRoots = array_values(array_unique(array_filter(array_map(
    static fn ($value) => ltrim(strtolower(trim((string) $value)), '.'),
    array_merge(
        explode(',', (string) env('TENANCY_LEGACY_SUBDOMAIN_ROOTS', '')),
        [env('TENANCY_SUBDOMAIN_ROOT', '')],
    )
))));

return [
    /*
    |--------------------------------------------------------------------------
    | Default subdomain root for tenant sites
    |--------------------------------------------------------------------------
    |
    | When a subscription uses the "subdomain" domain option we build the FQDN
    | by combining the generated slug with this root. Example result:
    | {subdomain}.palgoals.wpgoals.com
    |
    */
    'subdomain_root' => env('TENANT_DOMAIN', env('TENANCY_SUBDOMAIN_ROOT', 'palgoals.wpgoals.com')),

    /*
    |--------------------------------------------------------------------------
    | Legacy subdomain roots kept only for backwards-safe host recognition
    |--------------------------------------------------------------------------
    |
    | Existing tenants may still have older platform-hosted domains. We keep
    | those roots recognizable without using them for new provisioning.
    |
    */
    'legacy_subdomain_roots' => $legacySubdomainRoots,

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
