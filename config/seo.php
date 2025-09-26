<?php

return [
    'site_name' => env('APP_NAME', 'Palgoals'),
    'default_title' => env('SEO_DEFAULT_TITLE', 'Palgoals | Hosting, Domains, and Digital Services'),
    'default_description' => env('SEO_DEFAULT_DESCRIPTION', 'Palgoals provides web hosting, domain registration, and tailored digital solutions for businesses in the MENA region.'),
    'default_keywords' => [
        'web hosting',
        'shared hosting',
        'wordpress hosting',
        'domain registration',
        'seo services',
        'it services',
    ],
    'default_image' => env('SEO_DEFAULT_IMAGE', 'assets/images/default-og.jpg'),
    'default_type' => 'website',
    'default_locale' => 'ar',
    'default_robots' => 'index, follow',
    'twitter' => [
        'card' => 'summary_large_image',
        'handle' => env('SEO_TWITTER_HANDLE', null),
    ],
];
