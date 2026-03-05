<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppearanceController extends Controller
{
    public function header(): View
    {
        $settings = $this->settings();

        $activeHeaderKey = $settings->active_header_variant;
        $availableHeaders = config('front_layouts.headers', []);
        if (!array_key_exists($activeHeaderKey, $availableHeaders)) {
            $activeHeaderKey = config('front_layouts.defaults.header', 'default');
        }

        return view('dashboard.appearance.header', [
            'settings' => $settings,
            'headerVariants' => $availableHeaders,
            'activeHeaderSettings' => $this->resolvedHeaderVariantSettings($settings, $activeHeaderKey),
        ]);
    }

    public function updateHeaderVariant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'active_header_variant' => [
                'required',
                'string',
                Rule::in(array_keys(config('front_layouts.headers', []))),
            ],
        ]);

        $this->settings()->update($validated);

        return back()->with('success', 'Header layout activated successfully.');
    }

    public function updateHeaderSettings(Request $request): RedirectResponse
    {
        $settings = $this->settings();
        $activeVariant = $settings->active_header_variant;

        $validated = $request->validate([
            'header_show_promo_bar' => ['nullable', 'boolean'],
            'header_is_sticky' => ['nullable', 'boolean'],
            'pv_announcement_text' => ['nullable', 'string', 'max:255'],
            'pv_show_social_icons' => ['nullable', 'boolean'],
            'pv_show_login_button' => ['nullable', 'boolean'],
            'pv_login_label' => ['nullable', 'string', 'max:60'],
            'pv_login_url' => ['nullable', 'string', 'max:2048'],
            'pv_show_language_switcher' => ['nullable', 'boolean'],
            'pv_language_label' => ['nullable', 'string', 'max:60'],
            'pv_contact_button_label' => ['nullable', 'string', 'max:60'],
            'pv_contact_button_url' => ['nullable', 'string', 'max:2048'],
            'pv_logo_override' => ['nullable', 'string', 'max:2048'],
        ]);

        $headerVariantSettings = is_array($settings->header_variant_settings ?? null)
            ? $settings->header_variant_settings
            : [];

        if ($activeVariant === 'purple_topbar') {
            $defaults = $this->headerVariantDefaults($settings, 'purple_topbar');

            $headerVariantSettings['purple_topbar'] = array_replace($defaults, [
                'announcement_text' => trim((string) ($validated['pv_announcement_text'] ?? '')),
                'show_social_icons' => $request->boolean('pv_show_social_icons'),
                'show_login_button' => $request->boolean('pv_show_login_button'),
                'login_label' => trim((string) ($validated['pv_login_label'] ?? '')),
                'login_url' => trim((string) ($validated['pv_login_url'] ?? '')),
                'show_language_switcher' => $request->boolean('pv_show_language_switcher'),
                'language_label' => trim((string) ($validated['pv_language_label'] ?? '')),
                'contact_button_label' => trim((string) ($validated['pv_contact_button_label'] ?? '')),
                'contact_button_url' => trim((string) ($validated['pv_contact_button_url'] ?? '')),
                'logo_override' => $this->normalizeMediaPath($validated['pv_logo_override'] ?? null),
            ]);
        }

        $settings->update([
            'header_show_promo_bar' => $request->boolean('header_show_promo_bar'),
            'header_is_sticky' => $request->boolean('header_is_sticky'),
            'header_variant_settings' => $headerVariantSettings,
        ]);

        return back()->with('success', 'Header settings saved successfully.');
    }

    public function footer(): View
    {
        return view('dashboard.appearance.footer', [
            'settings' => $this->settings(),
            'footerVariants' => config('front_layouts.footers', []),
        ]);
    }

    public function updateFooterVariant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'active_footer_variant' => [
                'required',
                'string',
                Rule::in(array_keys(config('front_layouts.footers', []))),
            ],
        ]);

        $this->settings()->update($validated);

        return back()->with('success', 'Footer layout activated successfully.');
    }

    public function updateFooterSettings(Request $request): RedirectResponse
    {
        $settings = $this->settings();

        $settings->update([
            'footer_show_contact_banner' => $request->boolean('footer_show_contact_banner'),
            'footer_show_payment_methods' => $request->boolean('footer_show_payment_methods'),
        ]);

        return back()->with('success', 'Footer settings saved successfully.');
    }

    protected function settings(): GeneralSetting
    {
        return GeneralSetting::query()->firstOrCreate([], [
            'active_header_variant' => config('front_layouts.defaults.header', 'default'),
            'active_footer_variant' => config('front_layouts.defaults.footer', 'default'),
            'header_show_promo_bar' => true,
            'header_is_sticky' => true,
            'header_variant_settings' => [],
            'footer_show_contact_banner' => true,
            'footer_show_payment_methods' => true,
        ]);
    }

    protected function resolvedHeaderVariantSettings(GeneralSetting $settings, string $variant): array
    {
        $defaults = $this->headerVariantDefaults($settings, $variant);
        if ($defaults === []) {
            return [];
        }

        $storedSettings = is_array($settings->header_variant_settings ?? null)
            ? ($settings->header_variant_settings[$variant] ?? [])
            : [];

        if (!is_array($storedSettings)) {
            $storedSettings = [];
        }

        return array_replace($defaults, $storedSettings);
    }

    protected function headerVariantDefaults(GeneralSetting $settings, string $variant): array
    {
        if ($variant !== 'purple_topbar') {
            return [];
        }

        return [
            'announcement_text' => 'Launch your own website in 5 minutes at minimal cost',
            'show_social_icons' => true,
            'show_login_button' => true,
            'login_label' => 'Login',
            'login_url' => '/client/login',
            'show_language_switcher' => true,
            'language_label' => 'Language',
            'contact_button_label' => 'Contact us',
            'contact_button_url' => '#contact',
            'logo_override' => null,
        ];
    }

    protected function extractStoragePathFromUrl(string $value): ?string
    {
        $path = parse_url($value, PHP_URL_PATH);
        if (!is_string($path) || trim($path) === '') {
            return null;
        }

        $normalized = ltrim($path, '/');
        $storagePrefix = 'storage/';
        $position = strpos($normalized, $storagePrefix);

        if ($position === false) {
            return null;
        }

        return ltrim(substr($normalized, $position + strlen($storagePrefix)), '/');
    }

    protected function normalizeMediaPath($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        if (ctype_digit($normalized)) {
            $media = Media::find((int) $normalized);
            if ($media && !empty($media->file_path)) {
                return ltrim((string) $media->file_path, '/');
            }
        }

        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://') || str_starts_with($normalized, '//')) {
            return $this->extractStoragePathFromUrl($normalized) ?? $normalized;
        }

        $normalized = ltrim($normalized, '/');
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        return $normalized;
    }
}
