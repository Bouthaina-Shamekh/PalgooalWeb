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

        $defaultColors = $this->purpleTopbarDefaultCustomColors();
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

    public function palgoalsMarketingFooter(): Response
    {
        $settings = GeneralSetting::query()->first();
        $footerVariantSettings = is_array($settings?->footer_variant_settings ?? null)
            ? $settings->footer_variant_settings
            : [];

        $marketingFooterSettings = is_array($footerVariantSettings['palgoals_marketing'] ?? null)
            ? $footerVariantSettings['palgoals_marketing']
            : [];

        $defaultColors = $this->palgoalsMarketingDefaultCustomColors();
        $storedCustomColors = is_array($marketingFooterSettings['custom_colors'] ?? null)
            ? $marketingFooterSettings['custom_colors']
            : [];
        $defaultLogoDimensions = $this->palgoalsMarketingDefaultLogoDimensions();
        $defaultPaymentLogoDimensions = $this->palgoalsMarketingDefaultPaymentLogoDimensions();

        $resolvedColors = [];
        foreach ($defaultColors as $key => $fallbackColor) {
            $resolvedColors[$key] = $this->normalizeHexColor(
                $storedCustomColors[$key] ?? null,
                $fallbackColor,
            );
        }

        $logoWidth = $this->normalizeDimensionValue(
            $marketingFooterSettings['logo_width'] ?? null,
            $defaultLogoDimensions['width'],
            40,
            480,
        );
        $logoHeight = $this->normalizeDimensionValue(
            $marketingFooterSettings['logo_height'] ?? null,
            $defaultLogoDimensions['height'],
            40,
            240,
        );
        $paymentLogoWidth = $this->normalizeDimensionValue(
            $marketingFooterSettings['payment_logo_width'] ?? null,
            $defaultPaymentLogoDimensions['width'],
            32,
            220,
        );
        $paymentLogoHeight = $this->normalizeDimensionValue(
            $marketingFooterSettings['payment_logo_height'] ?? null,
            $defaultPaymentLogoDimensions['height'],
            24,
            160,
        );

        $css = implode("\n", [
            ':root {',
            "  --pf-marketing-shell-bg: {$resolvedColors['shell_bg']};",
            "  --pf-marketing-body-text: {$resolvedColors['body_text']};",
            "  --pf-marketing-heading-text: {$resolvedColors['heading_text']};",
            "  --pf-marketing-accent: {$resolvedColors['accent']};",
            "  --pf-marketing-border: {$resolvedColors['border']};",
            "  --pf-marketing-payment-card-bg: {$resolvedColors['payment_card_bg']};",
            "  --pf-marketing-logo-width: {$logoWidth}px;",
            "  --pf-marketing-logo-height: {$logoHeight}px;",
            "  --pf-marketing-payment-logo-width: {$paymentLogoWidth}px;",
            "  --pf-marketing-payment-logo-height: {$paymentLogoHeight}px;",
            '}',
            '',
        ]);

        return response($css, 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    protected function purpleTopbarDefaultCustomColors(): array
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

    protected function palgoalsMarketingDefaultCustomColors(): array
    {
        return [
            'shell_bg' => '#F3F4F6',
            'body_text' => '#8E8E8E',
            'heading_text' => '#111827',
            'accent' => '#240A37',
            'border' => '#D1D5DB',
            'payment_card_bg' => '#FFFFFF',
        ];
    }

    protected function palgoalsMarketingDefaultLogoDimensions(): array
    {
        return [
            'width' => 220,
            'height' => 72,
        ];
    }

    protected function palgoalsMarketingDefaultPaymentLogoDimensions(): array
    {
        return [
            'width' => 64,
            'height' => 40,
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

    protected function normalizeDimensionValue($value, int $fallback, int $min, int $max): int
    {
        if (!is_numeric($value)) {
            return $fallback;
        }

        $normalized = (int) $value;
        if ($normalized < $min || $normalized > $max) {
            return $fallback;
        }

        return $normalized;
    }
}
