<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Http\Response;

class ThemeCssController extends Controller
{
    public function purpleTopbar(): Response
    {
        $settings = GeneralSetting::query()->first();
        $headerVariantSettings = is_array($settings?->header_variant_settings ?? null)
            ? $settings->header_variant_settings
            : [];

        $purpleTopbarSettings = is_array($headerVariantSettings['purple_topbar'] ?? null)
            ? $headerVariantSettings['purple_topbar']
            : [];

        $defaultColors = $this->defaultCustomColors();
        $storedCustomColors = is_array($purpleTopbarSettings['custom_colors'] ?? null)
            ? $purpleTopbarSettings['custom_colors']
            : [];

        $resolvedColors = [];
        foreach ($defaultColors as $key => $fallbackColor) {
            $resolvedColors[$key] = $this->normalizeHexColor(
                $storedCustomColors[$key] ?? null,
                $fallbackColor,
            );
        }

        $css = implode("\n", [
            ':root {',
            "  --pv-topbar-promo-bg: {$resolvedColors['promo_bg']};",
            "  --pv-topbar-promo-text: {$resolvedColors['promo_text']};",
            "  --pv-topbar-nav-bg: {$resolvedColors['nav_bg']};",
            "  --pv-topbar-nav-text: {$resolvedColors['nav_text']};",
            "  --pv-topbar-accent: {$resolvedColors['accent']};",
            "  --pv-topbar-social-icon: {$resolvedColors['social_icon']};",
            "  --pv-topbar-border: {$resolvedColors['border']};",
            "  --pv-topbar-dropdown-hover-bg: {$resolvedColors['dropdown_hover_bg']};",
            "  --pv-topbar-subtext: {$resolvedColors['subtext']};",
            '}',
            '',
        ]);

        return response($css, 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    protected function defaultCustomColors(): array
    {
        return [
            'promo_bg' => '#240A37',
            'promo_text' => '#FFFFFF',
            'nav_bg' => '#FFFFFF',
            'nav_text' => '#111827',
            'accent' => '#BA112C',
            'social_icon' => '#7F6F8A',
            'border' => '#E5E7EB',
            'dropdown_hover_bg' => '#F3F4F6',
            'subtext' => '#626262',
        ];
    }

    protected function normalizeHexColor($value, string $fallback): string
    {
        $candidate = strtoupper(trim((string) $value));
        if (preg_match('/^#(?:[A-F0-9]{3}|[A-F0-9]{6})$/', $candidate) === 1) {
            return $candidate;
        }

        return strtoupper(trim($fallback));
    }
}
