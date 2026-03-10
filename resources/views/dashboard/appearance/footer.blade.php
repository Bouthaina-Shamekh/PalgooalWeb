<x-dashboard-layout>
    @php
        $activeFooterKey = $settings->active_footer_variant;
        if (! array_key_exists($activeFooterKey, $footerVariants)) {
            $activeFooterKey = array_key_first($footerVariants);
        }

        $activeVariant = $activeFooterKey ? ($footerVariants[$activeFooterKey] ?? null) : null;
        $sortedFooterVariants = collect($footerVariants)->sortByDesc(
            fn (array $variant, string $key) => $key === $activeFooterKey
        );

        $activeFooterSettings = is_array($activeFooterSettings ?? null) ? $activeFooterSettings : [];
        $footerSettingsLanguages = $footerSettingsLanguages
            ?? ($languages instanceof \Illuminate\Support\Collection ? $languages : collect($languages ?? []));

        $palgoalsDefaults = [
            'description_text' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.',
            'pages_title' => 'PAGES',
            'payment_title' => 'PAYMENT',
            'help_title' => 'NEED HELP?',
            'follow_us_label' => 'FOLLOW US:',
            'copyright_text' => 'All rights reserved to PalGoals company © 2025',
            'show_social_icons' => true,
            'logo_override' => null,
            'payment_logos' => [],
            'logo_width' => 220,
            'logo_height' => 72,
            'payment_logo_width' => 64,
            'payment_logo_height' => 40,
        ];

        $palgoalsColorThemesConfig = config('front_layouts.color_libraries.palgoals_marketing.themes', []);
        if (! is_array($palgoalsColorThemesConfig)) {
            $palgoalsColorThemesConfig = [];
        }

        $extractThemeClass = static function ($classList, array $prefixes, string $fallback): string {
            $tokens = preg_split('/\s+/', trim((string) $classList)) ?: [];
            foreach ($tokens as $token) {
                $candidate = trim((string) $token);
                if ($candidate === '') {
                    continue;
                }

                if (str_contains($candidate, ':')) {
                    $segments = explode(':', $candidate);
                    $candidate = (string) end($segments);
                }

                foreach ($prefixes as $prefix) {
                    if (str_starts_with($candidate, (string) $prefix)) {
                        return $candidate;
                    }
                }
            }

            return $fallback;
        };

        $toPreviewBg = static function (string $className, string $fallback): string {
            $normalized = trim($className);
            if (str_starts_with($normalized, 'bg-')) {
                return $normalized;
            }

            if (str_starts_with($normalized, 'text-')) {
                return 'bg-' . substr($normalized, strlen('text-'));
            }

            return $fallback;
        };

        $palgoalsColorThemeOptions = [];
        foreach ($palgoalsColorThemesConfig as $themeKey => $themeConfig) {
            $normalizedThemeKey = strtolower(trim((string) $themeKey));
            if ($normalizedThemeKey === '') {
                continue;
            }

            $themeLabel = is_array($themeConfig)
                ? trim((string) ($themeConfig['label'] ?? ''))
                : '';

            if ($themeLabel === '') {
                $themeLabel = ucwords(str_replace(['_', '-'], ' ', $normalizedThemeKey));
            }

            $themeClasses = is_array($themeConfig)
                ? (is_array($themeConfig['classes'] ?? null) ? $themeConfig['classes'] : $themeConfig)
                : [];

            $previewShell = $extractThemeClass($themeClasses['shell'] ?? '', ['bg-'], 'bg-gray-100');
            $previewMuted = $extractThemeClass($themeClasses['muted_text'] ?? '', ['text-'], 'text-gray-500');
            $previewAccent = $extractThemeClass($themeClasses['hover_text'] ?? '', ['text-'], 'text-purple-brand');

            $palgoalsColorThemeOptions[$normalizedThemeKey] = [
                'label' => $themeLabel,
                'preview' => [
                    'shell' => $toPreviewBg($previewShell, 'bg-gray-100'),
                    'muted' => $toPreviewBg($previewMuted, 'bg-gray-500'),
                    'accent' => $toPreviewBg($previewAccent, 'bg-purple-brand'),
                ],
            ];
        }

        if ($palgoalsColorThemeOptions === []) {
            $palgoalsColorThemeOptions = [
                'classic' => [
                    'label' => 'Classic Purple',
                    'preview' => [
                        'shell' => 'bg-gray-100',
                        'muted' => 'bg-gray-500',
                        'accent' => 'bg-purple-brand',
                    ],
                ],
            ];
        }

        $palgoalsDefaultColorTheme = strtolower((string) config('front_layouts.color_libraries.palgoals_marketing.default', 'classic'));
        if (! array_key_exists($palgoalsDefaultColorTheme, $palgoalsColorThemeOptions)) {
            $palgoalsDefaultColorTheme = (string) (array_key_first($palgoalsColorThemeOptions) ?? 'classic');
        }

        $palgoalsCustomColorDefaults = [
            'shell_bg' => '#F3F4F6',
            'body_text' => '#8E8E8E',
            'heading_text' => '#111827',
            'accent' => '#240A37',
            'border' => '#D1D5DB',
            'payment_card_bg' => '#FFFFFF',
        ];
        $palgoalsCustomColorLabels = [
            'shell_bg' => t('dashboard.Footer_Background', 'Footer Background'),
            'body_text' => t('dashboard.Body_Text_Color', 'Body Text Color'),
            'heading_text' => t('dashboard.Heading_Color', 'Heading Color'),
            'accent' => t('dashboard.Accent_Color', 'Accent Color'),
            'border' => t('dashboard.Border_Color', 'Border Color'),
            'payment_card_bg' => t('dashboard.Payment_Card_Background', 'Payment Card Background'),
        ];
        $palgoalsHexPattern = '/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/';

        $palgoalsDefaults['color_theme'] = $palgoalsDefaultColorTheme;
        $palgoalsDefaults['custom_colors'] = $palgoalsCustomColorDefaults;
        $palgoalsSettings = array_replace($palgoalsDefaults, $activeFooterSettings);
        $palgoalsSelectedColorTheme = strtolower((string) old('fm_color_theme', $palgoalsSettings['color_theme'] ?? $palgoalsDefaultColorTheme));
        if (! array_key_exists($palgoalsSelectedColorTheme, $palgoalsColorThemeOptions)) {
            $palgoalsSelectedColorTheme = $palgoalsDefaultColorTheme;
        }

        $palgoalsStoredCustomColors = is_array($palgoalsSettings['custom_colors'] ?? null)
            ? $palgoalsSettings['custom_colors']
            : [];
        $palgoalsCustomColorInputs = [];
        foreach ($palgoalsCustomColorDefaults as $colorKey => $defaultValue) {
            $candidate = trim((string) old("fm_custom_colors.$colorKey", $palgoalsStoredCustomColors[$colorKey] ?? $defaultValue));
            if (preg_match($palgoalsHexPattern, $candidate) !== 1) {
                $candidate = (string) $defaultValue;
            }
            $palgoalsCustomColorInputs[$colorKey] = strtoupper($candidate);
        }

        $palgoalsLogoPath = old('fm_logo_override', $palgoalsSettings['logo_override'] ?? '');
        $palgoalsLogoWidth = (int) old('fm_logo_width', $palgoalsSettings['logo_width'] ?? 220);
        $palgoalsLogoHeight = (int) old('fm_logo_height', $palgoalsSettings['logo_height'] ?? 72);
        $palgoalsPaymentLogoWidth = (int) old('fm_payment_logo_width', $palgoalsSettings['payment_logo_width'] ?? 64);
        $palgoalsPaymentLogoHeight = (int) old('fm_payment_logo_height', $palgoalsSettings['payment_logo_height'] ?? 40);
        $palgoalsLogoPreview = '';
        if (! empty($palgoalsLogoPath)) {
            $palgoalsLogoPreview = \Illuminate\Support\Str::startsWith($palgoalsLogoPath, ['http://', 'https://', '//'])
                ? $palgoalsLogoPath
                : asset('storage/' . ltrim($palgoalsLogoPath, '/'));
        }

        $palgoalsPaymentLogoPaths = old('fm_payment_logos');
        if (is_string($palgoalsPaymentLogoPaths)) {
            $palgoalsPaymentLogoPaths = array_values(array_filter(array_map('trim', explode(',', $palgoalsPaymentLogoPaths))));
        } elseif (is_array($palgoalsSettings['payment_logos'] ?? null)) {
            $palgoalsPaymentLogoPaths = array_values(array_filter($palgoalsSettings['payment_logos']));
        } else {
            $palgoalsPaymentLogoPaths = [];
        }

        $palgoalsPaymentLogoPreviewUrls = collect($palgoalsPaymentLogoPaths)
            ->map(function ($path) {
                $normalizedPath = trim((string) $path);
                if ($normalizedPath === '') {
                    return null;
                }

                return \Illuminate\Support\Str::startsWith($normalizedPath, ['http://', 'https://', '//'])
                    ? $normalizedPath
                    : asset('storage/' . ltrim($normalizedPath, '/'));
            })
            ->filter()
            ->values()
            ->all();

        $defaultLocaleCode = strtolower((string) (
            optional($footerSettingsLanguages->firstWhere('id', $settings->default_language))->code
            ?? config('app.locale', 'en')
        ));
        $fallbackLocaleCode = strtolower((string) config('app.fallback_locale', 'en'));
        $normalizeLocalizedScalar = static function ($value): string {
            if (is_array($value)) {
                $normalized = '';

                array_walk_recursive($value, static function ($item) use (&$normalized): void {
                    if ($normalized !== '') {
                        return;
                    }

                    if (is_scalar($item) || $item instanceof \Stringable) {
                        $candidate = trim((string) $item);
                        if ($candidate !== '') {
                            $normalized = $candidate;
                        }
                    }
                });

                return $normalized;
            }

            if (is_scalar($value) || $value instanceof \Stringable) {
                return trim((string) $value);
            }

            return '';
        };

        $resolveLocalizedSettingForForm = static function ($value, string $locale) use ($defaultLocaleCode, $fallbackLocaleCode, $normalizeLocalizedScalar): string {
            $locale = strtolower($locale);

            if (is_array($value)) {
                $normalizedValues = [];
                foreach ($value as $langKey => $langValue) {
                    $normalizedValues[strtolower((string) $langKey)] = $normalizeLocalizedScalar($langValue);
                }

                $localizedValue = $normalizeLocalizedScalar(
                    $normalizedValues[$locale]
                    ?? $normalizedValues[$defaultLocaleCode]
                    ?? $normalizedValues[$fallbackLocaleCode]
                    ?? ''
                );

                if ($localizedValue !== '') {
                    return $localizedValue;
                }

                foreach ($normalizedValues as $candidate) {
                    $candidate = $normalizeLocalizedScalar($candidate);
                    if ($candidate !== '') {
                        return $candidate;
                    }
                }

                return '';
            }

            $scalar = $normalizeLocalizedScalar($value);
            if ($scalar === '') {
                return '';
            }

            return $locale === $defaultLocaleCode ? $scalar : '';
        };

        $palgoalsLocalizedTextInputs = [];
        foreach ($footerSettingsLanguages as $language) {
            $code = strtolower((string) ($language->code ?? ''));
            if ($code === '') {
                continue;
            }

            $palgoalsLocalizedTextInputs[$code] = [
                'description_text' => (string) old(
                    "fm_texts.$code.description_text",
                    $resolveLocalizedSettingForForm($palgoalsSettings['description_text'] ?? '', $code),
                ),
                'pages_title' => (string) old(
                    "fm_texts.$code.pages_title",
                    $resolveLocalizedSettingForForm($palgoalsSettings['pages_title'] ?? '', $code),
                ),
                'payment_title' => (string) old(
                    "fm_texts.$code.payment_title",
                    $resolveLocalizedSettingForForm($palgoalsSettings['payment_title'] ?? '', $code),
                ),
                'help_title' => (string) old(
                    "fm_texts.$code.help_title",
                    $resolveLocalizedSettingForForm($palgoalsSettings['help_title'] ?? '', $code),
                ),
                'follow_us_label' => (string) old(
                    "fm_texts.$code.follow_us_label",
                    $resolveLocalizedSettingForForm($palgoalsSettings['follow_us_label'] ?? '', $code),
                ),
                'copyright_text' => (string) old(
                    "fm_texts.$code.copyright_text",
                    $resolveLocalizedSettingForForm($palgoalsSettings['copyright_text'] ?? '', $code),
                ),
            ];
        }

        $footerSettingsLocalizedBaseline = [];
        foreach ($palgoalsLocalizedTextInputs as $code => $fields) {
            $footerSettingsLocalizedBaseline["fm_texts[$code][description_text]"] = (string) ($fields['description_text'] ?? '');
            $footerSettingsLocalizedBaseline["fm_texts[$code][pages_title]"] = (string) ($fields['pages_title'] ?? '');
            $footerSettingsLocalizedBaseline["fm_texts[$code][payment_title]"] = (string) ($fields['payment_title'] ?? '');
            $footerSettingsLocalizedBaseline["fm_texts[$code][help_title]"] = (string) ($fields['help_title'] ?? '');
            $footerSettingsLocalizedBaseline["fm_texts[$code][follow_us_label]"] = (string) ($fields['follow_us_label'] ?? '');
            $footerSettingsLocalizedBaseline["fm_texts[$code][copyright_text]"] = (string) ($fields['copyright_text'] ?? '');
        }

        $palgoalsFirstErrorLang = null;
        foreach ($footerSettingsLanguages as $language) {
            $code = strtolower((string) ($language->code ?? ''));
            if ($code === '') {
                continue;
            }

            if (
                $errors->has("fm_texts.$code.description_text")
                || $errors->has("fm_texts.$code.pages_title")
                || $errors->has("fm_texts.$code.payment_title")
                || $errors->has("fm_texts.$code.help_title")
                || $errors->has("fm_texts.$code.follow_us_label")
                || $errors->has("fm_texts.$code.copyright_text")
            ) {
                $palgoalsFirstErrorLang = $code;
                break;
            }
        }

        $palgoalsInitialLangCode = $palgoalsFirstErrorLang
            ?? strtolower((string) ($footerSettingsLanguages->first()?->code ?? ''));

        $footerSettingsBaseline = [
            'footer_show_contact_banner' => (bool) $settings->footer_show_contact_banner,
            'footer_show_payment_methods' => (bool) $settings->footer_show_payment_methods,
            'fm_show_social_icons' => (bool) ($palgoalsSettings['show_social_icons'] ?? true),
            'fm_logo_override' => (string) ($palgoalsLogoPath ?? ''),
            'fm_payment_logos' => implode(',', $palgoalsPaymentLogoPaths),
            'fm_logo_width' => (int) $palgoalsLogoWidth,
            'fm_logo_height' => (int) $palgoalsLogoHeight,
            'fm_payment_logo_width' => (int) $palgoalsPaymentLogoWidth,
            'fm_payment_logo_height' => (int) $palgoalsPaymentLogoHeight,
            'fm_color_theme' => (string) ($palgoalsSettings['color_theme'] ?? $palgoalsDefaultColorTheme),
        ];
        foreach ($palgoalsCustomColorInputs as $colorKey => $colorValue) {
            $footerSettingsBaseline["fm_custom_colors[$colorKey]"] = $colorValue;
        }
    @endphp

    <div class="space-y-6">
        <div class="page-header">
            <div class="page-block">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0)">{{ t('dashboard.Appearance', 'Appearance') }}</a></li>
                    <li class="breadcrumb-item">{{ t('dashboard.Footer_Layout', 'Footer Layout') }}</li>
                </ul>
                <div class="page-header-title">
                    <h2 class="mb-0">{{ t('dashboard.Footer_Layout', 'Footer Layout') }}</h2>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 md:p-5">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <img
                            src="{{ asset($activeVariant['preview'] ?? 'assets/front-layouts/previews/footers/default.svg') }}"
                            alt="{{ $activeVariant['label'] ?? t('dashboard.Footer_Layout', 'Footer Layout') }}"
                            class="w-28 h-20 object-cover rounded-xl border border-gray-200 bg-slate-100 shrink-0"
                        />
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge bg-light-success text-success">
                                    {{ t('dashboard.Active_Footer', 'Active Footer') }}
                                </span>
                                <span class="text-sm text-muted">{{ t('dashboard.Live_On_Website', 'Live on website') }}</span>
                            </div>
                            <h3 class="text-lg font-semibold mb-0">{{ $activeVariant['label'] ?? ($activeFooterKey ?? '-') }}</h3>
                            <p class="text-sm text-muted mb-0">{{ $activeVariant['description'] ?? '' }}</p>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <span class="badge {{ $settings->footer_show_contact_banner ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">
                                    {{ t('dashboard.Contact_Banner', 'Contact Banner') }}:
                                    {{ $settings->footer_show_contact_banner ? t('dashboard.On', 'On') : t('dashboard.Off', 'Off') }}
                                </span>
                                <span class="badge {{ $settings->footer_show_payment_methods ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">
                                    {{ t('dashboard.Payment_Methods', 'Payment Methods') }}:
                                    {{ $settings->footer_show_payment_methods ? t('dashboard.On', 'On') : t('dashboard.Off', 'Off') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('frontend.home') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                            {{ t('dashboard.Preview_Homepage', 'Preview Homepage') }}
                        </a>
                        <a href="{{ route('dashboard.general_settings') }}" class="btn btn-primary btn-sm">
                            {{ t('dashboard.Open_General_Settings', 'Open General Settings') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6 items-start">
            <div class="col-span-12 xl:col-span-8">
                <div class="card">
                    <div class="card-header">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h5 class="mb-1">{{ t('dashboard.Footer_Layouts', 'Footer Layouts') }}</h5>
                                <p class="text-sm text-muted mb-0">{{ t('dashboard.Select_Footer_Layout_Desc', 'Choose and activate the footer style used in your frontend pages.') }}</p>
                            </div>
                            <div class="w-full md:w-72">
                                <input
                                    type="text"
                                    data-footer-search
                                    class="form-control"
                                    placeholder="{{ t('dashboard.Search_Footer_Layouts', 'Search footer layouts...') }}"
                                >
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-3">
                            <button type="button" data-footer-filter="all" class="btn btn-sm btn-primary">
                                {{ t('dashboard.All', 'All') }}
                            </button>
                            <button type="button" data-footer-filter="active" class="btn btn-sm btn-outline-secondary">
                                {{ t('dashboard.Active', 'Active') }}
                            </button>
                            <button type="button" data-footer-filter="inactive" class="btn btn-sm btn-outline-secondary">
                                {{ t('dashboard.Not_Active', 'Not Active') }}
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        @if ($sortedFooterVariants->isEmpty())
                            <div class="text-center py-5 text-muted">
                                {{ t('dashboard.No_Footer_Layouts_Found', 'No footer layouts found.') }}
                            </div>
                        @else
                            <div id="footer-variant-grid" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($sortedFooterVariants as $key => $variant)
                                    @php
                                        $isActive = $activeFooterKey === $key;
                                        $variantLabel = $variant['label'] ?? $key;
                                        $variantDescription = $variant['description'] ?? '';
                                        $variantPreview = $variant['preview'] ?? 'assets/front-layouts/previews/footers/default.svg';
                                    @endphp

                                    <div
                                        data-footer-card
                                        data-state="{{ $isActive ? 'active' : 'inactive' }}"
                                        data-key="{{ strtolower($key) }}"
                                        data-label="{{ strtolower($variantLabel) }}"
                                        data-description="{{ strtolower($variantDescription) }}"
                                        class="rounded-2xl border {{ $isActive ? 'border-primary shadow-lg ring-2 ring-primary/20' : 'border-gray-200' }} bg-white overflow-hidden"
                                    >
                                        <form action="{{ route('dashboard.appearance.footer.variant') }}" method="POST" class="h-full flex flex-col">
                                            @csrf
                                            <input type="hidden" name="active_footer_variant" value="{{ $key }}">

                                            <button type="submit" class="w-full text-start">
                                                <img
                                                    src="{{ asset($variantPreview) }}"
                                                    alt="{{ $variantLabel }}"
                                                    class="w-full h-48 object-cover bg-slate-100"
                                                />
                                            </button>

                                            <div class="p-4 space-y-3 flex-1 flex flex-col">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <h3 class="text-base font-semibold mb-1">{{ $variantLabel }}</h3>
                                                        <p class="text-sm text-muted mb-0">{{ $variantDescription }}</p>
                                                    </div>
                                                    @if ($isActive)
                                                        <span class="badge bg-light-success text-success shrink-0">{{ t('dashboard.Active', 'Active') }}</span>
                                                    @endif
                                                </div>

                                                <div class="mt-auto pt-2">
                                                    <button type="submit" class="btn {{ $isActive ? 'btn-outline-secondary' : 'btn-primary' }} w-full">
                                                        {{ $isActive ? t('dashboard.Active_Now', 'Active Now') : t('dashboard.Activate', 'Activate') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>

                            <div id="footer-variant-empty" class="hidden text-center py-5 text-muted">
                                {{ t('dashboard.No_Footer_Layout_Match', 'No layout matches your search/filter.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-4 space-y-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ t('dashboard.Footer_Settings', 'Footer Settings') }}</h5>
                    </div>
                    <div class="card-body">
                        <form id="footer-settings-form" action="{{ route('dashboard.appearance.footer.settings') }}" method="POST" class="space-y-4">
                            @csrf

                            <div class="rounded-xl border border-gray-200 p-3">
                                <div class="form-check">
                                    <input
                                        id="footer_show_contact_banner"
                                        type="checkbox"
                                        class="form-check-input"
                                        name="footer_show_contact_banner"
                                        value="1"
                                        @checked(old('footer_show_contact_banner', $settings->footer_show_contact_banner))
                                    >
                                    <label class="form-check-label" for="footer_show_contact_banner">
                                        {{ t('dashboard.Show_Contact_Banner', 'Show contact banner') }}
                                    </label>
                                </div>
                                <p class="text-xs text-muted mb-0 mt-2 ps-6">{{ t('dashboard.Show_Contact_Banner_Help', 'Show the top contact section in supported footer variants.') }}</p>
                            </div>

                            <div class="rounded-xl border border-gray-200 p-3">
                                <div class="form-check">
                                    <input
                                        id="footer_show_payment_methods"
                                        type="checkbox"
                                        class="form-check-input"
                                        name="footer_show_payment_methods"
                                        value="1"
                                        @checked(old('footer_show_payment_methods', $settings->footer_show_payment_methods))
                                    >
                                    <label class="form-check-label" for="footer_show_payment_methods">
                                        {{ t('dashboard.Show_Payment_Methods', 'Show payment methods') }}
                                    </label>
                                </div>
                                <p class="text-xs text-muted mb-0 mt-2 ps-6">{{ t('dashboard.Show_Payment_Methods_Help', 'Display supported payment icons in the footer.') }}</p>
                            </div>

                            @if ($activeFooterKey === 'palgoals_marketing')
                                <div class="rounded-xl border border-gray-200 p-3 space-y-4">
                                    <div>
                                        <h6 class="text-sm font-semibold mb-1">{{ t('dashboard.PalGoals_Marketing_Settings', 'PalGoals Marketing Settings') }}</h6>
                                        <p class="text-xs text-muted mb-0">{{ t('dashboard.PalGoals_Marketing_Settings_Help', 'Multilingual text, logo override, social toggle, and footer color theme for this variant.') }}</p>
                                    </div>

                                    @if ($footerSettingsLanguages->isNotEmpty())
                                        <div class="space-y-3">
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($footerSettingsLanguages as $language)
                                                    @php
                                                        $code = strtolower((string) ($language->code ?? ''));
                                                        if ($code === '') {
                                                            continue;
                                                        }
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        data-fm-lang-tab="{{ $code }}"
                                                        class="btn btn-sm {{ $code === $palgoalsInitialLangCode ? 'btn-primary' : 'btn-outline-secondary' }}"
                                                    >
                                                        {{ $language->name ?? strtoupper($code) }}
                                                    </button>
                                                @endforeach
                                            </div>

                                            @foreach ($footerSettingsLanguages as $language)
                                                @php
                                                    $code = strtolower((string) ($language->code ?? ''));
                                                    if ($code === '') {
                                                        continue;
                                                    }
                                                    $fields = $palgoalsLocalizedTextInputs[$code] ?? [];
                                                    $isVisible = $code === $palgoalsInitialLangCode;
                                                @endphp
                                                <div data-fm-lang-panel="{{ $code }}" class="space-y-3 {{ $isVisible ? '' : 'hidden' }}">
                                                    <div>
                                                        <label class="form-label mb-1" for="fm_texts_{{ $code }}_description_text">
                                                            {{ t('dashboard.Footer_Description', 'Footer Description') }}
                                                        </label>
                                                        <textarea
                                                            id="fm_texts_{{ $code }}_description_text"
                                                            name="fm_texts[{{ $code }}][description_text]"
                                                            rows="4"
                                                            class="form-control"
                                                            placeholder="{{ t('dashboard.Footer_Description', 'Footer Description') }}"
                                                        >{{ $fields['description_text'] ?? '' }}</textarea>
                                                    </div>

                                                    <div class="grid grid-cols-1 gap-3">
                                                        <div>
                                                            <label class="form-label mb-1" for="fm_texts_{{ $code }}_pages_title">
                                                                {{ t('dashboard.Pages_Title', 'Pages Title') }}
                                                            </label>
                                                            <input
                                                                id="fm_texts_{{ $code }}_pages_title"
                                                                name="fm_texts[{{ $code }}][pages_title]"
                                                                type="text"
                                                                class="form-control"
                                                                value="{{ $fields['pages_title'] ?? '' }}"
                                                                placeholder="PAGES"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="form-label mb-1" for="fm_texts_{{ $code }}_payment_title">
                                                                {{ t('dashboard.Payment_Title', 'Payment Title') }}
                                                            </label>
                                                            <input
                                                                id="fm_texts_{{ $code }}_payment_title"
                                                                name="fm_texts[{{ $code }}][payment_title]"
                                                                type="text"
                                                                class="form-control"
                                                                value="{{ $fields['payment_title'] ?? '' }}"
                                                                placeholder="PAYMENT"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="form-label mb-1" for="fm_texts_{{ $code }}_help_title">
                                                                {{ t('dashboard.Help_Title', 'Help Title') }}
                                                            </label>
                                                            <input
                                                                id="fm_texts_{{ $code }}_help_title"
                                                                name="fm_texts[{{ $code }}][help_title]"
                                                                type="text"
                                                                class="form-control"
                                                                value="{{ $fields['help_title'] ?? '' }}"
                                                                placeholder="NEED HELP?"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="form-label mb-1" for="fm_texts_{{ $code }}_follow_us_label">
                                                                {{ t('dashboard.Follow_Us_Label', 'Follow Us Label') }}
                                                            </label>
                                                            <input
                                                                id="fm_texts_{{ $code }}_follow_us_label"
                                                                name="fm_texts[{{ $code }}][follow_us_label]"
                                                                type="text"
                                                                class="form-control"
                                                                value="{{ $fields['follow_us_label'] ?? '' }}"
                                                                placeholder="FOLLOW US:"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="form-label mb-1" for="fm_texts_{{ $code }}_copyright_text">
                                                                {{ t('dashboard.Copyright_Text', 'Copyright Text') }}
                                                            </label>
                                                            <input
                                                                id="fm_texts_{{ $code }}_copyright_text"
                                                                name="fm_texts[{{ $code }}][copyright_text]"
                                                                type="text"
                                                                class="form-control"
                                                                value="{{ $fields['copyright_text'] ?? '' }}"
                                                                placeholder="{{ t('dashboard.Copyright_Text', 'Copyright Text') }}"
                                                            >
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="rounded-xl border border-gray-200 p-3 space-y-3">
                                        <div class="form-check">
                                            <input
                                                id="fm_show_social_icons"
                                                type="checkbox"
                                                class="form-check-input"
                                                name="fm_show_social_icons"
                                                value="1"
                                                @checked(old('fm_show_social_icons', $palgoalsSettings['show_social_icons'] ?? true))
                                            >
                                            <label class="form-check-label" for="fm_show_social_icons">
                                                {{ t('dashboard.Show_Social_Icons', 'Show social icons') }}
                                            </label>
                                        </div>

                                        <div class="space-y-2">
                                            <label for="fm_logo_override" class="form-label mb-0">
                                                {{ t('dashboard.Footer_Logo_Override', 'Footer Logo Override') }}
                                            </label>
                                            <input id="fm_logo_override" name="fm_logo_override" type="hidden" value="{{ $palgoalsLogoPath }}">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary btn-sm btn-open-media-picker"
                                                    data-target-input="fm_logo_override"
                                                    data-target-preview="fm_logo_override_preview"
                                                    data-multiple="false"
                                                    data-store-value="path"
                                                >
                                                    {{ t('dashboard.Choose_From_Media', 'Choose From Media Library') }}
                                                </button>
                                                <button type="button" id="fm_logo_override_clear" class="btn btn-outline-secondary btn-sm">
                                                    {{ t('dashboard.Clear_Override', 'Clear override') }}
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <label for="fm_logo_width" class="form-label mb-1">
                                                        {{ t('dashboard.Logo_Width_PX', 'Logo Width (px)') }}
                                                    </label>
                                                    <input
                                                        id="fm_logo_width"
                                                        name="fm_logo_width"
                                                        type="number"
                                                        min="40"
                                                        max="480"
                                                        step="1"
                                                        class="form-control"
                                                        value="{{ $palgoalsLogoWidth }}"
                                                    >
                                                </div>
                                                <div>
                                                    <label for="fm_logo_height" class="form-label mb-1">
                                                        {{ t('dashboard.Logo_Height_PX', 'Logo Height (px)') }}
                                                    </label>
                                                    <input
                                                        id="fm_logo_height"
                                                        name="fm_logo_height"
                                                        type="number"
                                                        min="40"
                                                        max="240"
                                                        step="1"
                                                        class="form-control"
                                                        value="{{ $palgoalsLogoHeight }}"
                                                    >
                                                </div>
                                            </div>
                                            <div id="fm_logo_override_preview" class="rounded-lg border border-gray-200 bg-gray-50 p-2 min-h-[92px] flex items-center justify-center">
                                                @if (! empty($palgoalsLogoPreview))
                                                    <div class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 bg-white">
                                                        <img src="{{ $palgoalsLogoPreview }}" alt="Footer Logo Override" class="w-full h-full object-cover">
                                                    </div>
                                                @else
                                                    <span class="text-xs text-muted">{{ t('dashboard.Fallback_To_General_Logo', 'Fallback to General Setting logo') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-gray-200 p-3 space-y-3">
                                        <div>
                                            <label class="form-label mb-0">{{ t('dashboard.Payment_Logos', 'Payment Logos') }}</label>
                                            <p class="text-xs text-muted mb-0">{{ t('dashboard.Payment_Logos_Help', 'Upload or select one or more payment method logos from the media library.') }}</p>
                                        </div>
                                        <input
                                            id="fm_payment_logos"
                                            name="fm_payment_logos"
                                            type="hidden"
                                            value="{{ implode(',', $palgoalsPaymentLogoPaths) }}"
                                        >
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-sm btn-open-media-picker"
                                                data-target-input="fm_payment_logos"
                                                data-target-preview="fm_payment_logos_preview"
                                                data-multiple="true"
                                                data-store-value="path"
                                            >
                                                {{ t('dashboard.Choose_From_Media', 'Choose From Media Library') }}
                                            </button>
                                            <button type="button" id="fm_payment_logos_clear" class="btn btn-outline-secondary btn-sm">
                                                {{ t('dashboard.Clear_Selection', 'Clear selection') }}
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label for="fm_payment_logo_width" class="form-label mb-1">
                                                    {{ t('dashboard.Payment_Logo_Width_PX', 'Payment Logo Width (px)') }}
                                                </label>
                                                <input
                                                    id="fm_payment_logo_width"
                                                    name="fm_payment_logo_width"
                                                    type="number"
                                                    min="32"
                                                    max="220"
                                                    step="1"
                                                    class="form-control"
                                                    value="{{ $palgoalsPaymentLogoWidth }}"
                                                >
                                            </div>
                                            <div>
                                                <label for="fm_payment_logo_height" class="form-label mb-1">
                                                    {{ t('dashboard.Payment_Logo_Height_PX', 'Payment Logo Height (px)') }}
                                                </label>
                                                <input
                                                    id="fm_payment_logo_height"
                                                    name="fm_payment_logo_height"
                                                    type="number"
                                                    min="24"
                                                    max="160"
                                                    step="1"
                                                    class="form-control"
                                                    value="{{ $palgoalsPaymentLogoHeight }}"
                                                >
                                            </div>
                                        </div>
                                        <div id="fm_payment_logos_preview" class="flex flex-wrap gap-2 min-h-[48px]">
                                            @forelse ($palgoalsPaymentLogoPreviewUrls as $paymentLogoPreviewUrl)
                                                <div class="relative shrink-0 w-20 h-20 rounded-lg overflow-hidden border border-gray-200 bg-white">
                                                    <img src="{{ $paymentLogoPreviewUrl }}" alt="Payment Logo" class="w-full h-full object-contain p-2">
                                                </div>
                                            @empty
                                                <span class="text-xs text-muted">{{ t('dashboard.Fallback_To_Default_Payment_Logos', 'Fallback to the default payment logos when no custom images are selected.') }}</span>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <h6 class="text-sm font-semibold mb-0">{{ t('dashboard.Footer_Color_Theme', 'Footer Color Theme') }}</h6>
                                        <div class="space-y-2">
                                            @foreach ($palgoalsColorThemeOptions as $themeKey => $themeOption)
                                                @php
                                                    $isSelectedTheme = $palgoalsSelectedColorTheme === $themeKey;
                                                @endphp
                                                <label
                                                    data-fm-theme-card
                                                    data-fm-theme-key="{{ $themeKey }}"
                                                    class="cursor-pointer block rounded-xl border p-3 transition-all duration-200 hover:border-primary/60 hover:shadow-sm {{ $isSelectedTheme ? 'border-primary ring-2 ring-primary/20 shadow-sm' : 'border-gray-200' }}"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="fm_color_theme"
                                                        value="{{ $themeKey }}"
                                                        class="sr-only"
                                                        @checked($isSelectedTheme)
                                                    >
                                                    <div class="flex items-center justify-between gap-3">
                                                        <span class="text-sm font-semibold text-slate-700">{{ $themeOption['label'] }}</span>
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="w-7 h-4 rounded {{ $themeOption['preview']['shell'] }}"></span>
                                                            <span class="w-7 h-4 rounded {{ $themeOption['preview']['muted'] }}"></span>
                                                            <span class="w-7 h-4 rounded {{ $themeOption['preview']['accent'] }}"></span>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                        <p class="text-xs text-muted mb-0">{{ t('dashboard.Footer_Color_Theme_Help', 'Choose a predefined palette or use Custom (Manual) for full control.') }}</p>
                                    </div>

                                    <div id="fm-custom-colors-panel" class="space-y-3 {{ $palgoalsSelectedColorTheme === 'custom' ? '' : 'hidden' }}">
                                        <div class="rounded-xl border border-gray-200 p-3 space-y-3">
                                            <h6 class="text-sm font-semibold mb-0">{{ t('dashboard.Custom_Theme_Colors', 'Custom Theme Colors') }}</h6>
                                            @foreach ($palgoalsCustomColorLabels as $colorKey => $colorLabel)
                                                <div class="rounded-lg border border-gray-200 p-2">
                                                    <label class="form-label mb-2 text-xs uppercase tracking-wide text-muted" for="fm_custom_colors_{{ $colorKey }}">
                                                        {{ $colorLabel }}
                                                    </label>
                                                    <div class="flex items-center gap-2">
                                                        <input
                                                            id="fm_custom_colors_{{ $colorKey }}"
                                                            type="color"
                                                            class="form-control form-control-color !w-12 !h-10 p-1"
                                                            data-fm-custom-picker="{{ $colorKey }}"
                                                            value="{{ $palgoalsCustomColorInputs[$colorKey] ?? '#000000' }}"
                                                        >
                                                        <input
                                                            name="fm_custom_colors[{{ $colorKey }}]"
                                                            type="text"
                                                            class="form-control uppercase"
                                                            data-fm-custom-hex-input="{{ $colorKey }}"
                                                            value="{{ $palgoalsCustomColorInputs[$colorKey] ?? '#000000' }}"
                                                            placeholder="#000000"
                                                        >
                                                    </div>
                                                </div>
                                            @endforeach
                                            <button type="button" id="fm_custom_colors_reset" class="btn btn-outline-secondary btn-sm w-full">
                                                {{ t('dashboard.Reset_Custom_Colors', 'Reset Custom Colors') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div id="footer-settings-savehint" class="hidden alert alert-warning py-2 mb-0">
                                {{ t('dashboard.Unsaved_Changes', 'You have unsaved changes.') }}
                            </div>

                            <div class="flex gap-2">
                                <button type="button" id="footer-settings-reset" class="btn btn-outline-secondary w-full">
                                    {{ t('dashboard.Reset', 'Reset') }}
                                </button>
                                <button type="submit" class="btn btn-primary w-full">
                                    {{ t('dashboard.Save_Changes', 'Save Changes') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ t('dashboard.Footer_Note', 'Footer Note') }}</h5>
                    </div>
                    <div class="card-body space-y-3 text-sm text-muted">
                        <p class="mb-0">{{ t('dashboard.Footer_Note_Desc', 'Click any card to activate a footer layout. Contact and social data remain managed in General Settings.') }}</p>
                        <a href="{{ route('dashboard.general_settings') }}" class="btn btn-outline-primary w-full">
                            {{ t('dashboard.Open_General_Settings', 'Open General Settings') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.querySelector('[data-footer-search]');
                const filterButtons = document.querySelectorAll('[data-footer-filter]');
                const cards = document.querySelectorAll('[data-footer-card]');
                const emptyState = document.getElementById('footer-variant-empty');
                let activeFilter = 'all';

                const normalize = (value) => (value || '').toLowerCase();

                function applyCardFilters() {
                    const query = normalize(searchInput?.value);
                    let visibleCount = 0;

                    cards.forEach((card) => {
                        const haystack = [
                            normalize(card.dataset.key),
                            normalize(card.dataset.label),
                            normalize(card.dataset.description),
                        ].join(' ');

                        const matchesQuery = !query || haystack.includes(query);
                        const matchesFilter = activeFilter === 'all' || card.dataset.state === activeFilter;
                        const shouldShow = matchesQuery && matchesFilter;

                        card.classList.toggle('hidden', !shouldShow);
                        if (shouldShow) {
                            visibleCount += 1;
                        }
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', visibleCount > 0);
                    }
                }

                searchInput?.addEventListener('input', applyCardFilters);

                filterButtons.forEach((button) => {
                    button.addEventListener('click', function () {
                        activeFilter = this.dataset.footerFilter || 'all';

                        filterButtons.forEach((btn) => {
                            btn.classList.remove('btn-primary');
                            btn.classList.add('btn-outline-secondary');
                        });

                        this.classList.remove('btn-outline-secondary');
                        this.classList.add('btn-primary');

                        applyCardFilters();
                    });
                });

                applyCardFilters();

                const settingsForm = document.getElementById('footer-settings-form');
                const saveHint = document.getElementById('footer-settings-savehint');
                const resetButton = document.getElementById('footer-settings-reset');
                const logoOverrideInput = document.getElementById('fm_logo_override');
                const logoOverrideClearButton = document.getElementById('fm_logo_override_clear');
                const logoOverridePreview = document.getElementById('fm_logo_override_preview');
                const paymentLogosInput = document.getElementById('fm_payment_logos');
                const paymentLogosClearButton = document.getElementById('fm_payment_logos_clear');
                const paymentLogosPreview = document.getElementById('fm_payment_logos_preview');
                const customColorsPanel = document.getElementById('fm-custom-colors-panel');
                const customColorsResetButton = document.getElementById('fm_custom_colors_reset');
                const fallbackLogoText = @json(t('dashboard.Fallback_To_General_Logo', 'Fallback to General Setting logo'));
                const fallbackPaymentLogosText = @json(t('dashboard.Fallback_To_Default_Payment_Logos', 'Fallback to the default payment logos when no custom images are selected.'));
                const storageBaseUrl = @json(asset('storage'));
                const baselineValues = @json($footerSettingsBaseline);
                const localizedBaselineValues = @json($footerSettingsLocalizedBaseline);
                const palgoalsInitialLangCode = @json($palgoalsInitialLangCode);
                const customColorFallbacks = @json($palgoalsCustomColorDefaults);

                if (!settingsForm) {
                    return;
                }

                const customHexByKey = new Map();
                const customPickerByKey = new Map();
                settingsForm.querySelectorAll('[data-fm-custom-hex-input]').forEach((input) => {
                    customHexByKey.set(input.getAttribute('data-fm-custom-hex-input'), input);
                });
                settingsForm.querySelectorAll('[data-fm-custom-picker]').forEach((input) => {
                    customPickerByKey.set(input.getAttribute('data-fm-custom-picker'), input);
                });

                const colorThemeInputs = settingsForm.querySelectorAll('input[name="fm_color_theme"]');
                const themeCards = settingsForm.querySelectorAll('[data-fm-theme-card]');

                const serialize = (formData) => {
                    const values = {};
                    for (const [key, value] of formData.entries()) {
                        if (!Object.prototype.hasOwnProperty.call(values, key)) {
                            values[key] = [];
                        }
                        values[key].push(value);
                    }
                    return JSON.stringify(values);
                };

                let initialState = serialize(new FormData(settingsForm));

                function updateSaveHint() {
                    const currentState = serialize(new FormData(settingsForm));
                    const isDirty = currentState !== initialState;
                    saveHint?.classList.toggle('hidden', !isDirty);
                }

                function setFieldValue(name, value) {
                    const field = settingsForm.elements.namedItem(name)
                        || settingsForm.querySelector(`[name="${name}"]`);
                    if (!field) return;

                    if (typeof RadioNodeList !== 'undefined' && field instanceof RadioNodeList) {
                        field.forEach((radio) => {
                            radio.checked = radio.value === String(value ?? '');
                        });
                        return;
                    }

                    if (field.type === 'checkbox') {
                        field.checked = !!value;
                        return;
                    }

                    if (field.type === 'radio') {
                        const radio = settingsForm.querySelector(`[name="${name}"][value="${String(value)}"]`);
                        if (radio) radio.checked = true;
                        return;
                    }

                    field.value = value ?? '';
                }

                function normalizeHexColor(value, fallback = '#000000') {
                    const candidate = String(value || '').trim().toUpperCase();
                    if (/^#([A-F0-9]{3}|[A-F0-9]{6})$/.test(candidate)) {
                        return candidate;
                    }

                    return String(fallback || '#000000').trim().toUpperCase();
                }

                function syncCustomColorControls(colorKey, value) {
                    const fallback = customColorFallbacks[colorKey] || '#000000';
                    const normalized = normalizeHexColor(value, fallback);

                    const hexInput = customHexByKey.get(colorKey);
                    if (hexInput) {
                        hexInput.value = normalized;
                    }

                    const pickerInput = customPickerByKey.get(colorKey);
                    if (pickerInput) {
                        pickerInput.value = normalized;
                    }
                }

                function syncAllCustomColorControlsFromInputs() {
                    customHexByKey.forEach((hexInput, colorKey) => {
                        syncCustomColorControls(colorKey, hexInput?.value);
                    });
                }

                function selectedColorTheme() {
                    const checked = settingsForm.querySelector('input[name="fm_color_theme"]:checked');
                    return checked ? String(checked.value || '') : '';
                }

                function toggleCustomColorsPanel() {
                    if (!customColorsPanel) return;
                    customColorsPanel.classList.toggle('hidden', selectedColorTheme() !== 'custom');
                }

                function refreshThemeCards() {
                    const activeTheme = selectedColorTheme();
                    themeCards.forEach((card) => {
                        const isActive = card.getAttribute('data-fm-theme-key') === activeTheme;
                        card.classList.toggle('border-primary', isActive);
                        card.classList.toggle('ring-2', isActive);
                        card.classList.toggle('ring-primary/20', isActive);
                        card.classList.toggle('shadow-sm', isActive);
                        card.classList.toggle('border-gray-200', !isActive);
                    });
                }

                function applyBaselineValues() {
                    setFieldValue('footer_show_contact_banner', baselineValues.footer_show_contact_banner);
                    setFieldValue('footer_show_payment_methods', baselineValues.footer_show_payment_methods);
                    setFieldValue('fm_show_social_icons', baselineValues.fm_show_social_icons);
                    setFieldValue('fm_logo_override', baselineValues.fm_logo_override);
                    setFieldValue('fm_payment_logos', baselineValues.fm_payment_logos);
                    setFieldValue('fm_logo_width', baselineValues.fm_logo_width);
                    setFieldValue('fm_logo_height', baselineValues.fm_logo_height);
                    setFieldValue('fm_payment_logo_width', baselineValues.fm_payment_logo_width);
                    setFieldValue('fm_payment_logo_height', baselineValues.fm_payment_logo_height);
                    setFieldValue('fm_color_theme', baselineValues.fm_color_theme);

                    Object.entries(localizedBaselineValues).forEach(([name, fieldValue]) => {
                        setFieldValue(name, fieldValue);
                    });

                    Object.entries(baselineValues).forEach(([name, fieldValue]) => {
                        if (name.startsWith('fm_custom_colors[')) {
                            setFieldValue(name, fieldValue);
                        }
                    });

                    syncAllCustomColorControlsFromInputs();
                    toggleCustomColorsPanel();
                    refreshThemeCards();
                }

                function activatePalgoalsLanguage(langCode) {
                    if (!langCode) return;

                    const tabButtons = settingsForm.querySelectorAll('[data-fm-lang-tab]');
                    const panels = settingsForm.querySelectorAll('[data-fm-lang-panel]');

                    tabButtons.forEach((button) => {
                        const currentCode = button.getAttribute('data-fm-lang-tab');
                        const isActive = currentCode === langCode;
                        button.classList.toggle('btn-primary', isActive);
                        button.classList.toggle('btn-outline-secondary', !isActive);
                    });

                    panels.forEach((panel) => {
                        const currentCode = panel.getAttribute('data-fm-lang-panel');
                        panel.classList.toggle('hidden', currentCode !== langCode);
                    });
                }

                function renderLogoOverridePreview(path) {
                    if (!logoOverridePreview) return;

                    const normalizedPath = (path || '').trim();
                    if (!normalizedPath) {
                        logoOverridePreview.innerHTML = `<span class="text-xs text-muted">${fallbackLogoText}</span>`;
                        return;
                    }

                    const previewUrl = /^(https?:)?\/\//i.test(normalizedPath)
                        ? normalizedPath
                        : `${storageBaseUrl}/${normalizedPath.replace(/^\/+/, '').replace(/^storage\//, '')}`;

                    logoOverridePreview.innerHTML = `
                        <div class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 bg-white">
                            <img src="${previewUrl}" alt="Footer Logo Override" class="w-full h-full object-cover">
                        </div>
                    `;
                }

                function renderPaymentLogosPreview(value) {
                    if (!paymentLogosPreview) return;

                    const paths = String(value || '')
                        .split(',')
                        .map((item) => item.trim())
                        .filter(Boolean);

                    if (!paths.length) {
                        paymentLogosPreview.innerHTML = `<span class="text-xs text-muted">${fallbackPaymentLogosText}</span>`;
                        return;
                    }

                    paymentLogosPreview.innerHTML = paths.map((path) => {
                        const previewUrl = /^(https?:)?\/\//i.test(path)
                            ? path
                            : `${storageBaseUrl}/${path.replace(/^\/+/, '').replace(/^storage\//, '')}`;

                        return `
                            <div class="relative shrink-0 w-20 h-20 rounded-lg overflow-hidden border border-gray-200 bg-white">
                                <img src="${previewUrl}" alt="Payment Logo" class="w-full h-full object-contain p-2">
                            </div>
                        `;
                    }).join('');
                }

                settingsForm.addEventListener('change', updateSaveHint);
                settingsForm.addEventListener('input', updateSaveHint);

                settingsForm.querySelectorAll('[data-fm-lang-tab]').forEach((button) => {
                    button.addEventListener('click', () => {
                        activatePalgoalsLanguage(button.getAttribute('data-fm-lang-tab'));
                    });
                });

                customPickerByKey.forEach((pickerInput, colorKey) => {
                    pickerInput.addEventListener('input', () => {
                        syncCustomColorControls(colorKey, pickerInput.value);
                    });
                    pickerInput.addEventListener('change', () => {
                        syncCustomColorControls(colorKey, pickerInput.value);
                    });
                });

                customHexByKey.forEach((hexInput, colorKey) => {
                    hexInput.addEventListener('input', () => {
                        hexInput.value = String(hexInput.value || '').toUpperCase();
                        if (/^#([A-F0-9]{3}|[A-F0-9]{6})$/.test(hexInput.value)) {
                            syncCustomColorControls(colorKey, hexInput.value);
                        }
                    });
                    hexInput.addEventListener('blur', () => {
                        syncCustomColorControls(colorKey, hexInput.value);
                    });
                });

                colorThemeInputs.forEach((input) => {
                    input.addEventListener('change', () => {
                        toggleCustomColorsPanel();
                        refreshThemeCards();
                    });
                });

                customColorsResetButton?.addEventListener('click', function () {
                    Object.entries(customColorFallbacks).forEach(([colorKey, colorValue]) => {
                        syncCustomColorControls(colorKey, colorValue);
                    });
                    updateSaveHint();
                });

                logoOverrideInput?.addEventListener('input', function () {
                    renderLogoOverridePreview(this.value);
                });

                logoOverrideInput?.addEventListener('change', function () {
                    renderLogoOverridePreview(this.value);
                });

                paymentLogosInput?.addEventListener('input', function () {
                    renderPaymentLogosPreview(this.value);
                });

                paymentLogosInput?.addEventListener('change', function () {
                    renderPaymentLogosPreview(this.value);
                });

                logoOverrideClearButton?.addEventListener('click', function () {
                    if (!logoOverrideInput) return;
                    logoOverrideInput.value = '';
                    renderLogoOverridePreview('');
                    updateSaveHint();
                });

                paymentLogosClearButton?.addEventListener('click', function () {
                    if (!paymentLogosInput) return;
                    paymentLogosInput.value = '';
                    renderPaymentLogosPreview('');
                    updateSaveHint();
                });

                resetButton?.addEventListener('click', function () {
                    applyBaselineValues();
                    activatePalgoalsLanguage(palgoalsInitialLangCode);
                    if (logoOverrideInput) {
                        renderLogoOverridePreview(logoOverrideInput.value);
                    }
                    if (paymentLogosInput) {
                        renderPaymentLogosPreview(paymentLogosInput.value);
                    }
                    initialState = serialize(new FormData(settingsForm));
                    updateSaveHint();
                });

                if (logoOverrideInput) {
                    renderLogoOverridePreview(logoOverrideInput.value);
                }
                if (paymentLogosInput) {
                    renderPaymentLogosPreview(paymentLogosInput.value);
                }
                syncAllCustomColorControlsFromInputs();
                toggleCustomColorsPanel();
                refreshThemeCards();
                activatePalgoalsLanguage(palgoalsInitialLangCode);
                updateSaveHint();
            });
        </script>
    @endpush
</x-dashboard-layout>
