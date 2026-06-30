<?php

namespace App\Support;

class SafeUrl
{
    private const FALLBACK = '#';

    private const ALLOWED_SCHEMES = [
        'http',
        'https',
        'mailto',
        'tel',
        'sms',
        'whatsapp',
    ];

    public static function toHref(mixed $value): string
    {
        $url = trim((string) $value);
        $url = preg_replace('/[\x00-\x1F\x7F]+/', '', $url) ?? '';

        if ($url === '') {
            return self::FALLBACK;
        }

        if (str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return str_starts_with($url, '//') ? self::FALLBACK : $url;
        }

        if (str_starts_with($url, './') || str_starts_with($url, '../')) {
            return $url;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!is_string($scheme) || $scheme === '') {
            return self::FALLBACK;
        }

        $scheme = strtolower($scheme);

        if (!in_array($scheme, self::ALLOWED_SCHEMES, true)) {
            return self::FALLBACK;
        }

        if ($scheme === 'whatsapp') {
            return str_starts_with(strtolower($url), 'whatsapp://') ? $url : self::FALLBACK;
        }

        return $url;
    }
}
