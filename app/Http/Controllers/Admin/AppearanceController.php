<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\Language;
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
            'headerSettingsLanguages' => Language::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get(),
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
        $hexColorRule = ['nullable', 'string', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'];

        $validated = $request->validate([
            'header_show_promo_bar' => ['nullable', 'boolean'],
            'header_is_sticky' => ['nullable', 'boolean'],
            'pv_texts' => ['nullable', 'array'],
            'pv_texts.*' => ['nullable', 'array'],
            'pv_texts.*.announcement_text' => ['nullable', 'string', 'max:255'],
            'pv_texts.*.login_label' => ['nullable', 'string', 'max:60'],
            'pv_texts.*.contact_button_label' => ['nullable', 'string', 'max:60'],
            'pv_announcement_text' => ['nullable', 'string', 'max:255'],
            'pv_show_social_icons' => ['nullable', 'boolean'],
            'pv_show_login_button' => ['nullable', 'boolean'],
            'pv_login_label' => ['nullable', 'string', 'max:60'],
            'pv_login_url' => ['nullable', 'string', 'max:2048'],
            'pv_show_language_switcher' => ['nullable', 'boolean'],
            'pv_contact_button_label' => ['nullable', 'string', 'max:60'],
            'pv_contact_button_url' => ['nullable', 'string', 'max:2048'],
            'pv_logo_override' => ['nullable', 'string', 'max:2048'],
            'pv_color_theme' => ['nullable', 'string', Rule::in($this->purpleTopbarColorThemeKeys())],
            'pv_custom_colors' => ['nullable', 'array'],
            'pv_custom_colors.promo_bg' => $hexColorRule,
            'pv_custom_colors.promo_text' => $hexColorRule,
            'pv_custom_colors.nav_bg' => $hexColorRule,
            'pv_custom_colors.nav_text' => $hexColorRule,
            'pv_custom_colors.accent' => $hexColorRule,
            'pv_custom_colors.social_icon' => $hexColorRule,
            'pv_custom_colors.border' => $hexColorRule,
            'pv_custom_colors.dropdown_hover_bg' => $hexColorRule,
            'pv_custom_colors.subtext' => $hexColorRule,
        ]);

        $headerVariantSettings = is_array($settings->header_variant_settings ?? null)
            ? $settings->header_variant_settings
            : [];

        if ($activeVariant === 'purple_topbar') {
            $defaults = $this->headerVariantDefaults($settings, 'purple_topbar');
            $allowedColorThemes = $this->purpleTopbarColorThemeKeys();
            $languageCodes = Language::query()
                ->where('is_active', true)
                ->pluck('code')
                ->map(fn ($code) => strtolower((string) $code))
                ->filter()
                ->values()
                ->all();

            $defaultLocale = strtolower((string) (
                Language::query()->find($settings->default_language)?->code
                ?? config('app.locale', 'en')
            ));

            $localizedTexts = is_array($request->input('pv_texts')) ? $request->input('pv_texts') : [];

            $announcementTexts = $this->normalizeLocalizedTextField(
                $localizedTexts,
                'announcement_text',
                $languageCodes,
                $defaultLocale,
                trim((string) ($validated['pv_announcement_text'] ?? '')),
            );

            $loginLabels = $this->normalizeLocalizedTextField(
                $localizedTexts,
                'login_label',
                $languageCodes,
                $defaultLocale,
                trim((string) ($validated['pv_login_label'] ?? '')),
            );

            $contactButtonLabels = $this->normalizeLocalizedTextField(
                $localizedTexts,
                'contact_button_label',
                $languageCodes,
                $defaultLocale,
                trim((string) ($validated['pv_contact_button_label'] ?? '')),
            );

            $contactButtonLegacyValue = trim((string) ($validated['pv_contact_button_label'] ?? ''));
            if ($contactButtonLegacyValue !== '') {
                $contactButtonLabels[$defaultLocale] = $contactButtonLegacyValue;
            }

            $selectedColorTheme = strtolower(trim((string) ($validated['pv_color_theme'] ?? '')));
            if (!in_array($selectedColorTheme, $allowedColorThemes, true)) {
                $selectedColorTheme = (string) ($defaults['color_theme'] ?? 'classic');
            }

            $normalizedCustomColors = $this->normalizePurpleTopbarCustomColors(
                is_array($request->input('pv_custom_colors')) ? $request->input('pv_custom_colors') : [],
                is_array($defaults['custom_colors'] ?? null)
                    ? $defaults['custom_colors']
                    : $this->purpleTopbarDefaultCustomColors(),
            );

            $headerVariantSettings['purple_topbar'] = array_replace($defaults, [
                'announcement_text' => $announcementTexts !== []
                    ? $announcementTexts
                    : trim((string) ($validated['pv_announcement_text'] ?? '')),
                'show_social_icons' => $request->boolean('pv_show_social_icons'),
                'show_login_button' => $request->boolean('pv_show_login_button'),
                'login_label' => $loginLabels !== []
                    ? $loginLabels
                    : trim((string) ($validated['pv_login_label'] ?? '')),
                'login_url' => trim((string) ($validated['pv_login_url'] ?? '')),
                'show_language_switcher' => $request->boolean('pv_show_language_switcher'),
                'contact_button_label' => $contactButtonLabels !== []
                    ? $contactButtonLabels
                    : trim((string) ($validated['pv_contact_button_label'] ?? '')),
                'contact_button_url' => trim((string) ($validated['pv_contact_button_url'] ?? '')),
                'logo_override' => $this->normalizeMediaPath($validated['pv_logo_override'] ?? null),
                'color_theme' => $selectedColorTheme,
                'custom_colors' => $normalizedCustomColors,
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
            'contact_button_label' => 'Contact us',
            'contact_button_url' => '#contact',
            'logo_override' => null,
            'color_theme' => $this->purpleTopbarDefaultColorThemeKey(),
            'custom_colors' => $this->purpleTopbarDefaultCustomColors(),
        ];
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

    protected function purpleTopbarColorThemes(): array
    {
        $configuredThemes = config('front_layouts.color_libraries.purple_topbar.themes', []);
        if (!is_array($configuredThemes)) {
            return [];
        }

        $normalizedThemes = [];
        foreach ($configuredThemes as $key => $theme) {
            $normalizedKey = strtolower(trim((string) $key));
            if ($normalizedKey === '' || !is_array($theme)) {
                continue;
            }

            $normalizedThemes[$normalizedKey] = $theme;
        }

        return $normalizedThemes;
    }

    protected function purpleTopbarColorThemeKeys(): array
    {
        return array_keys($this->purpleTopbarColorThemes());
    }

    protected function purpleTopbarDefaultColorThemeKey(): string
    {
        $themes = $this->purpleTopbarColorThemes();
        $configuredDefault = strtolower(trim((string) config('front_layouts.color_libraries.purple_topbar.default', 'classic')));

        if ($configuredDefault !== '' && array_key_exists($configuredDefault, $themes)) {
            return $configuredDefault;
        }

        $firstKey = array_key_first($themes);
        if (is_string($firstKey) && $firstKey !== '') {
            return $firstKey;
        }

        return 'classic';
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

    protected function normalizeLocalizedTextField(
        array $localizedTexts,
        string $field,
        array $languageCodes,
        string $defaultLocale,
        ?string $legacyValue = null,
    ): array {
        $normalized = [];

        foreach ($languageCodes as $languageCode) {
            $code = strtolower((string) $languageCode);
            if ($code === '') {
                continue;
            }

            $value = trim((string) data_get($localizedTexts, "{$code}.{$field}", ''));
            if ($value !== '') {
                $normalized[$code] = $value;
            }
        }

        $legacyValue = trim((string) $legacyValue);
        if ($normalized === [] && $legacyValue !== '') {
            $fallbackCode = $defaultLocale !== ''
                ? strtolower($defaultLocale)
                : strtolower((string) config('app.locale', 'en'));

            $normalized[$fallbackCode] = $legacyValue;
        }

        return $normalized;
    }

    protected function normalizePurpleTopbarCustomColors(array $inputColors, array $fallbackColors): array
    {
        $defaults = array_replace($this->purpleTopbarDefaultCustomColors(), $fallbackColors);
        $normalized = [];

        foreach ($defaults as $key => $fallbackValue) {
            $normalized[$key] = $this->normalizeHexColorValue($inputColors[$key] ?? null, (string) $fallbackValue);
        }

        return $normalized;
    }

    protected function normalizeHexColorValue($value, string $fallback): string
    {
        $candidate = strtoupper(trim((string) $value));
        if (preg_match('/^#(?:[A-F0-9]{3}|[A-F0-9]{6})$/', $candidate) !== 1) {
            return strtoupper(trim($fallback));
        }

        return $candidate;
    }
}
