<?php

namespace App\Support\Tenancy;

use App\Models\Tenancy\Subscription;

final class TenantThemeFontLoader
{
    /**
     * Explicit Google Fonts mapping for the curated Brand Settings font list.
     *
     * @var array<string, string>
     */
    private const GOOGLE_FONT_FAMILIES = [
        'Almarai' => 'Almarai:wght@300;400;700;800',
        'Cairo' => 'Cairo:wght@200..1000',
        'IBM Plex Sans Arabic' => 'IBM Plex Sans Arabic:wght@300;400;500;600;700',
        'Inter' => 'Inter:wght@300;400;500;600;700;800;900',
        'Montserrat' => 'Montserrat:wght@300;400;500;600;700;800;900',
        'Noto Kufi Arabic' => 'Noto Kufi Arabic:wght@300;400;500;600;700;800;900',
        'Noto Sans Arabic' => 'Noto Sans Arabic:wght@300;400;500;600;700;800;900',
        'Poppins' => 'Poppins:wght@300;400;500;600;700;800;900',
        'Roboto' => 'Roboto:wght@300;400;500;700;900',
        'Tajawal' => 'Tajawal:wght@300;400;500;700;800;900',
    ];

    public static function googleFontsUrlForSubscription(?Subscription $subscription): ?string
    {
        if (! $subscription instanceof Subscription) {
            return null;
        }

        $settings = TenantThemeSettings::fromArray(
            is_array($subscription->theme_settings) ? $subscription->theme_settings : []
        );

        return self::googleFontsUrlForFamilies([
            $settings->fontPrimary,
            $settings->fontHeading,
        ]);
    }

    /**
     * @param  array<int, string>  $fontFamilies
     */
    private static function googleFontsUrlForFamilies(array $fontFamilies): ?string
    {
        $families = [];

        foreach ($fontFamilies as $fontFamily) {
            $name = self::familyName($fontFamily);

            if ($name !== null && isset(self::GOOGLE_FONT_FAMILIES[$name])) {
                $families[$name] = self::GOOGLE_FONT_FAMILIES[$name];
            }
        }

        if ($families === []) {
            return null;
        }

        return 'https://fonts.googleapis.com/css2?'
            . implode('&', array_map(
                fn (string $family): string => 'family=' . rawurlencode($family),
                array_values($families)
            ))
            . '&display=swap';
    }

    private static function familyName(string $fontFamily): ?string
    {
        $name = trim(explode(',', $fontFamily, 2)[0] ?? '');
        $name = trim($name, " \t\n\r\0\x0B\"'");

        return $name !== '' ? $name : null;
    }
}
