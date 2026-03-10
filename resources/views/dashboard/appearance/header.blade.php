<x-dashboard-layout>
    @php
        $activeHeaderKey = $settings->active_header_variant;
        if (! array_key_exists($activeHeaderKey, $headerVariants)) {
            $activeHeaderKey = array_key_first($headerVariants);
        }

        $activeVariant = $activeHeaderKey ? ($headerVariants[$activeHeaderKey] ?? null) : null;
        $sortedHeaderVariants = collect($headerVariants)->sortByDesc(
            fn (array $variant, string $key) => $key === $activeHeaderKey
        );

        $activeHeaderSettings = is_array($activeHeaderSettings ?? null) ? $activeHeaderSettings : [];
        $headerSettingsLanguages = $headerSettingsLanguages
            ?? ($languages instanceof \Illuminate\Support\Collection ? $languages : collect($languages ?? []));

        $purpleDefaults = [
            'announcement_text' => 'Launch your own website in 5 minutes at minimal cost',
            'show_social_icons' => true,
            'show_login_button' => true,
            'login_label' => 'Login',
            'login_url' => '/client/login',
            'show_language_switcher' => true,
            'contact_button_label' => 'Contact us',
            'contact_button_url' => '#contact',
            'logo_override' => null,
        ];

        $purpleColorThemesConfig = config('front_layouts.color_libraries.purple_topbar.themes', []);
        if (!is_array($purpleColorThemesConfig)) {
            $purpleColorThemesConfig = [];
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

        $purpleColorThemeOptions = [];
        foreach ($purpleColorThemesConfig as $themeKey => $themeConfig) {
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

            $previewPromo = $extractThemeClass($themeClasses['promo_bar'] ?? '', ['bg-'], 'bg-purple-brand');
            $previewNav = $extractThemeClass($themeClasses['nav_shell'] ?? '', ['bg-'], 'bg-white');
            $previewAccent = $extractThemeClass($themeClasses['hamburger_bar'] ?? '', ['bg-'], 'bg-red-brand');

            $purpleColorThemeOptions[$normalizedThemeKey] = [
                'label' => $themeLabel,
                'preview' => [
                    'promo' => $previewPromo,
                    'nav' => $previewNav,
                    'accent' => $previewAccent,
                ],
            ];
        }

        if ($purpleColorThemeOptions === []) {
            $purpleColorThemeOptions = [
                'classic' => [
                    'label' => 'Classic Purple',
                    'preview' => [
                        'promo' => 'bg-purple-brand',
                        'nav' => 'bg-white',
                        'accent' => 'bg-red-brand',
                    ],
                ],
            ];
        }

        $purpleDefaultColorTheme = strtolower((string) config('front_layouts.color_libraries.purple_topbar.default', 'classic'));
        if (!array_key_exists($purpleDefaultColorTheme, $purpleColorThemeOptions)) {
            $purpleDefaultColorTheme = (string) (array_key_first($purpleColorThemeOptions) ?? 'classic');
        }

        $purpleCustomColorDefaults = [
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
        $purpleCustomColorLabels = [
            'promo_bg' => t('dashboard.Promo_Background', 'Promo Background'),
            'promo_text' => t('dashboard.Promo_Text_Color', 'Promo Text Color'),
            'nav_bg' => t('dashboard.Nav_Background', 'Navigation Background'),
            'nav_text' => t('dashboard.Nav_Text_Color', 'Navigation Text Color'),
            'accent' => t('dashboard.Accent_Color', 'Accent Color'),
            'social_icon' => t('dashboard.Social_Icons_Color', 'Social Icons Color'),
            'border' => t('dashboard.Border_Color', 'Border Color'),
            'dropdown_hover_bg' => t('dashboard.Dropdown_Hover_Background', 'Dropdown Hover Background'),
            'subtext' => t('dashboard.Subtext_Color', 'Subtext Color'),
        ];
        $purpleHexPattern = '/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/';

        $purpleDefaults['color_theme'] = $purpleDefaultColorTheme;
        $purpleDefaults['custom_colors'] = $purpleCustomColorDefaults;
        $purpleTopbarSettings = array_replace($purpleDefaults, $activeHeaderSettings);
        $purpleSelectedColorTheme = strtolower((string) old('pv_color_theme', $purpleTopbarSettings['color_theme'] ?? $purpleDefaultColorTheme));
        if (!array_key_exists($purpleSelectedColorTheme, $purpleColorThemeOptions)) {
            $purpleSelectedColorTheme = $purpleDefaultColorTheme;
        }
        $purpleStoredCustomColors = is_array($purpleTopbarSettings['custom_colors'] ?? null)
            ? $purpleTopbarSettings['custom_colors']
            : [];
        $purpleCustomColorInputs = [];
        foreach ($purpleCustomColorDefaults as $colorKey => $defaultValue) {
            $candidate = trim((string) old("pv_custom_colors.$colorKey", $purpleStoredCustomColors[$colorKey] ?? $defaultValue));
            if (preg_match($purpleHexPattern, $candidate) !== 1) {
                $candidate = (string) $defaultValue;
            }
            $purpleCustomColorInputs[$colorKey] = strtoupper($candidate);
        }
        $purpleLogoPath = old('pv_logo_override', $purpleTopbarSettings['logo_override'] ?? '');
        $purpleLogoPreview = '';
        if (!empty($purpleLogoPath)) {
            $purpleLogoPreview = \Illuminate\Support\Str::startsWith($purpleLogoPath, ['http://', 'https://', '//'])
                ? $purpleLogoPath
                : asset('storage/' . ltrim($purpleLogoPath, '/'));
        }

        $defaultLocaleCode = strtolower((string) (
            optional($headerSettingsLanguages->firstWhere('id', $settings->default_language))->code
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

        $purpleLocalizedTextInputs = [];
        foreach ($headerSettingsLanguages as $language) {
            $code = strtolower((string) ($language->code ?? ''));
            if ($code === '') {
                continue;
            }

            $purpleLocalizedTextInputs[$code] = [
                'announcement_text' => (string) old(
                    "pv_texts.$code.announcement_text",
                    $resolveLocalizedSettingForForm($purpleTopbarSettings['announcement_text'] ?? '', $code),
                ),
                'login_label' => (string) old(
                    "pv_texts.$code.login_label",
                    $resolveLocalizedSettingForForm($purpleTopbarSettings['login_label'] ?? '', $code),
                ),
                'contact_button_label' => (string) old(
                    "pv_texts.$code.contact_button_label",
                    $resolveLocalizedSettingForForm($purpleTopbarSettings['contact_button_label'] ?? '', $code),
                ),
            ];
        }

        $headerSettingsLocalizedBaseline = [];
        foreach ($purpleLocalizedTextInputs as $code => $fields) {
            $headerSettingsLocalizedBaseline["pv_texts[$code][announcement_text]"] = (string) ($fields['announcement_text'] ?? '');
            $headerSettingsLocalizedBaseline["pv_texts[$code][login_label]"] = (string) ($fields['login_label'] ?? '');
            $headerSettingsLocalizedBaseline["pv_texts[$code][contact_button_label]"] = (string) ($fields['contact_button_label'] ?? '');
        }

        $purpleFirstErrorLang = null;
        foreach ($headerSettingsLanguages as $language) {
            $code = strtolower((string) ($language->code ?? ''));
            if ($code === '') {
                continue;
            }

            if (
                $errors->has("pv_texts.$code.announcement_text")
                || $errors->has("pv_texts.$code.login_label")
                || $errors->has("pv_texts.$code.contact_button_label")
            ) {
                $purpleFirstErrorLang = $code;
                break;
            }
        }

        $purpleInitialLangCode = $purpleFirstErrorLang
            ?? strtolower((string) ($headerSettingsLanguages->first()?->code ?? ''));

        $headerSettingsBaseline = [
            'header_show_promo_bar' => (bool) $settings->header_show_promo_bar,
            'header_is_sticky' => (bool) $settings->header_is_sticky,
            'pv_show_social_icons' => (bool) ($purpleTopbarSettings['show_social_icons'] ?? true),
            'pv_show_login_button' => (bool) ($purpleTopbarSettings['show_login_button'] ?? true),
            'pv_login_url' => (string) ($purpleTopbarSettings['login_url'] ?? ''),
            'pv_show_language_switcher' => (bool) ($purpleTopbarSettings['show_language_switcher'] ?? true),
            'pv_contact_button_url' => (string) ($purpleTopbarSettings['contact_button_url'] ?? ''),
            'pv_logo_override' => (string) ($purpleLogoPath ?? ''),
            'pv_color_theme' => (string) ($purpleTopbarSettings['color_theme'] ?? $purpleDefaultColorTheme),
        ];
        foreach ($purpleCustomColorInputs as $colorKey => $colorValue) {
            $headerSettingsBaseline["pv_custom_colors[$colorKey]"] = $colorValue;
        }
    @endphp

    <div class="space-y-6">
        <div class="page-header">
            <div class="page-block">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0)">{{ t('dashboard.Appearance', 'Appearance') }}</a></li>
                    <li class="breadcrumb-item">{{ t('dashboard.Header_Layout', 'Header Layout') }}</li>
                </ul>
                <div class="page-header-title">
                    <h2 class="mb-0">{{ t('dashboard.Header_Layout', 'Header Layout') }}</h2>
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
                            src="{{ asset($activeVariant['preview'] ?? 'assets/front-layouts/previews/headers/Classic.png') }}"
                            alt="{{ $activeVariant['label'] ?? t('dashboard.Header_Layout', 'Header Layout') }}"
                            class="w-28 h-20 object-cover rounded-xl border border-gray-200 bg-slate-100 shrink-0"
                        />
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge bg-light-success text-success">
                                    {{ t('dashboard.Active_Header', 'Active Header') }}
                                </span>
                                <span class="text-sm text-muted">{{ t('dashboard.Live_On_Website', 'Live on website') }}</span>
                            </div>
                            <h3 class="text-lg font-semibold mb-0">{{ $activeVariant['label'] ?? ($activeHeaderKey ?? '-') }}</h3>
                            <p class="text-sm text-muted mb-0">{{ $activeVariant['description'] ?? '' }}</p>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <span class="badge {{ $settings->header_show_promo_bar ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">
                                    {{ t('dashboard.Promo_Bar', 'Promo Bar') }}:
                                    {{ $settings->header_show_promo_bar ? t('dashboard.On', 'On') : t('dashboard.Off', 'Off') }}
                                </span>
                                <span class="badge {{ $settings->header_is_sticky ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">
                                    {{ t('dashboard.Sticky', 'Sticky') }}:
                                    {{ $settings->header_is_sticky ? t('dashboard.On', 'On') : t('dashboard.Off', 'Off') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('frontend.home') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                            {{ t('dashboard.Preview_Homepage', 'Preview Homepage') }}
                        </a>
                        <a href="{{ route('dashboard.menus') }}" class="btn btn-primary btn-sm">
                            {{ t('dashboard.Manage_Menus', 'Manage Menus') }}
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
                                <h5 class="mb-1">{{ t('dashboard.Header_Layouts', 'Header Layouts') }}</h5>
                                <p class="text-sm text-muted mb-0">{{ t('dashboard.Select_Header_Layout_Desc', 'Choose and activate the header style used in your frontend pages.') }}</p>
                            </div>
                            <div class="w-full md:w-72">
                                <input
                                    type="text"
                                    data-header-search
                                    class="form-control"
                                    placeholder="{{ t('dashboard.Search_Header_Layouts', 'Search header layouts...') }}"
                                >
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-3">
                            <button type="button" data-header-filter="all" class="btn btn-sm btn-primary">
                                {{ t('dashboard.All', 'All') }}
                            </button>
                            <button type="button" data-header-filter="active" class="btn btn-sm btn-outline-secondary">
                                {{ t('dashboard.Active', 'Active') }}
                            </button>
                            <button type="button" data-header-filter="inactive" class="btn btn-sm btn-outline-secondary">
                                {{ t('dashboard.Not_Active', 'Not Active') }}
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        @if ($sortedHeaderVariants->isEmpty())
                            <div class="text-center py-5 text-muted">
                                {{ t('dashboard.No_Header_Layouts_Found', 'No header layouts found.') }}
                            </div>
                        @else
                            <div id="header-variant-grid" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($sortedHeaderVariants as $key => $variant)
                                    @php
                                        $isActive = $activeHeaderKey === $key;
                                        $variantLabel = $variant['label'] ?? $key;
                                        $variantDescription = $variant['description'] ?? '';
                                        $variantPreview = $variant['preview'] ?? 'assets/front-layouts/previews/headers/Classic.png';
                                    @endphp

                                    <div
                                        data-header-card
                                        data-state="{{ $isActive ? 'active' : 'inactive' }}"
                                        data-key="{{ strtolower($key) }}"
                                        data-label="{{ strtolower($variantLabel) }}"
                                        data-description="{{ strtolower($variantDescription) }}"
                                        class="rounded-2xl border {{ $isActive ? 'border-primary shadow-lg ring-2 ring-primary/20' : 'border-gray-200' }} bg-white overflow-hidden"
                                    >
                                        <form action="{{ route('dashboard.appearance.header.variant') }}" method="POST" class="h-full flex flex-col">
                                            @csrf
                                            <input type="hidden" name="active_header_variant" value="{{ $key }}">

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

                            <div id="header-variant-empty" class="hidden text-center py-5 text-muted">
                                {{ t('dashboard.No_Header_Layout_Match', 'No layout matches your search/filter.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-4 space-y-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ t('dashboard.Header_Settings', 'Header Settings') }}</h5>
                    </div>
                    <div class="card-body">
                        <form id="header-settings-form" action="{{ route('dashboard.appearance.header.settings') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="active_header_variant" value="{{ $activeHeaderKey }}">

                            <div class="rounded-xl border border-gray-200 p-3">
                                <div class="form-check">
                                    <input
                                        id="header_show_promo_bar"
                                        type="checkbox"
                                        class="form-check-input"
                                        name="header_show_promo_bar"
                                        value="1"
                                        @checked(old('header_show_promo_bar', $settings->header_show_promo_bar))
                                    >
                                    <label class="form-check-label" for="header_show_promo_bar">
                                        {{ t('dashboard.Show_Promo_Bar', 'Show promo bar') }}
                                    </label>
                                </div>
                                <p class="text-xs text-muted mb-0 mt-2 ps-6">{{ t('dashboard.Show_Promo_Bar_Help', 'Display the top announcement strip on frontend pages.') }}</p>
                            </div>

                            <div class="rounded-xl border border-gray-200 p-3">
                                <div class="form-check">
                                    <input
                                        id="header_is_sticky"
                                        type="checkbox"
                                        class="form-check-input"
                                        name="header_is_sticky"
                                        value="1"
                                        @checked(old('header_is_sticky', $settings->header_is_sticky))
                                    >
                                    <label class="form-check-label" for="header_is_sticky">
                                        {{ t('dashboard.Sticky_Header', 'Sticky header') }}
                                    </label>
                                </div>
                                <p class="text-xs text-muted mb-0 mt-2 ps-6">{{ t('dashboard.Sticky_Header_Help', 'Keep the main navigation visible while scrolling.') }}</p>
                            </div>

                            @if ($activeHeaderKey === 'purple_topbar')
                                <div class="rounded-xl border border-gray-200 p-3 space-y-4">
                                    <div>
                                        <h6 class="mb-1">{{ t('dashboard.Purple_Topbar_Settings', 'Purple Topbar Settings') }}</h6>
                                        <p class="text-xs text-muted mb-0">
                                            {{ t('dashboard.Purple_Topbar_Settings_Help', 'These options customize this header only. Missing values will fallback to General Setting when possible.') }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/50">
                                        <label class="form-label mb-2">{{ t('dashboard.Multilingual_Texts', 'Multilingual Texts') }}</label>

                                        @if ($headerSettingsLanguages->isNotEmpty())
                                            <div class="flex flex-wrap gap-2 mb-3">
                                                @foreach ($headerSettingsLanguages as $index => $language)
                                                    @php
                                                        $langCode = strtolower((string) ($language->code ?? ''));
                                                    @endphp
                                                    @continue($langCode === '')

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm {{ $purpleInitialLangCode === $langCode ? 'btn-primary' : 'btn-outline-secondary' }}"
                                                        data-pv-lang-tab="{{ $langCode }}"
                                                    >
                                                        {{ $language->native ?? ($language->name ?? strtoupper($langCode)) }}
                                                    </button>
                                                @endforeach
                                            </div>

                                            @foreach ($headerSettingsLanguages as $index => $language)
                                                @php
                                                    $langCode = strtolower((string) ($language->code ?? ''));
                                                    $langValues = $purpleLocalizedTextInputs[$langCode] ?? [];
                                                @endphp
                                                @continue($langCode === '')

                                                <div
                                                    data-pv-lang-panel="{{ $langCode }}"
                                                    class="space-y-3 {{ $purpleInitialLangCode === $langCode ? '' : 'hidden' }}"
                                                >
                                                    <div>
                                                        <label for="pv_announcement_text_{{ $langCode }}" class="form-label mb-1">
                                                            {{ t('dashboard.Announcement_Text', 'Announcement Text') }}
                                                        </label>
                                                        <input
                                                            id="pv_announcement_text_{{ $langCode }}"
                                                            name="pv_texts[{{ $langCode }}][announcement_text]"
                                                            type="text"
                                                            class="form-control"
                                                            value="{{ $langValues['announcement_text'] ?? '' }}"
                                                            placeholder="Launch your own website in 5 minutes at minimal cost"
                                                        >
                                                        @error('pv_texts.' . $langCode . '.announcement_text')
                                                            <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                                                        @enderror
                                                    </div>

                                                    <div>
                                                        <label for="pv_login_label_{{ $langCode }}" class="form-label mb-1">
                                                            {{ t('dashboard.Login_Label', 'Login Label') }}
                                                        </label>
                                                        <input
                                                            id="pv_login_label_{{ $langCode }}"
                                                            name="pv_texts[{{ $langCode }}][login_label]"
                                                            type="text"
                                                            class="form-control"
                                                            value="{{ $langValues['login_label'] ?? '' }}"
                                                            placeholder="Login"
                                                        >
                                                        @error('pv_texts.' . $langCode . '.login_label')
                                                            <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                                                        @enderror
                                                    </div>

                                                    <div>
                                                        <label for="pv_contact_button_label_{{ $langCode }}" class="form-label mb-1">
                                                            {{ t('dashboard.Contact_Button_Label', 'Contact Button Label') }}
                                                        </label>
                                                        <input
                                                            id="pv_contact_button_label_{{ $langCode }}"
                                                            name="pv_texts[{{ $langCode }}][contact_button_label]"
                                                            type="text"
                                                            class="form-control"
                                                            value="{{ $langValues['contact_button_label'] ?? '' }}"
                                                            placeholder="Contact us"
                                                        >
                                                        @error('pv_texts.' . $langCode . '.contact_button_label')
                                                            <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="text-xs text-muted mb-0">
                                                {{ t('dashboard.No_Languages_Found', 'No languages found.') }}
                                            </p>
                                        @endif
                                    </div>

                                    <div>
                                        <label class="form-label mb-1">{{ t('dashboard.Header_Logo_Override', 'Header Logo Override') }}</label>
                                        <input id="pv_logo_override" name="pv_logo_override" type="hidden" value="{{ $purpleLogoPath }}">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-sm btn-open-media-picker"
                                                data-target-input="pv_logo_override"
                                                data-target-preview="pv_logo_override_preview"
                                                data-multiple="false"
                                                data-store-value="path"
                                            >
                                                {{ t('dashboard.Choose_From_Media', 'Choose From Media Library') }}
                                            </button>
                                            <button
                                                type="button"
                                                id="pv_logo_override_clear"
                                                class="btn btn-outline-secondary btn-sm"
                                            >
                                                {{ t('dashboard.Clear_Override', 'Clear override') }}
                                            </button>
                                        </div>
                                        <div id="pv_logo_override_preview" class="mt-2 flex items-center gap-2 min-h-[48px]">
                                            @if (!empty($purpleLogoPreview))
                                                <div class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
                                                    <img src="{{ $purpleLogoPreview }}" alt="Header Logo Override" class="w-full h-full object-cover">
                                                </div>
                                            @else
                                                <span class="text-xs text-muted">{{ t('dashboard.Fallback_To_General_Logo', 'Fallback to General Setting logo') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-2">
                                        <div>
                                            <label class="form-label mb-2">{{ t('dashboard.Topbar_Color_Theme', 'Topbar Color Theme') }}</label>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                @foreach ($purpleColorThemeOptions as $themeKey => $themeOption)
                                                    @php
                                                        $themeLabel = (string) ($themeOption['label'] ?? ucwords(str_replace(['_', '-'], ' ', (string) $themeKey)));
                                                        $themePreview = is_array($themeOption['preview'] ?? null) ? $themeOption['preview'] : [];
                                                        $previewPromo = (string) ($themePreview['promo'] ?? 'bg-purple-brand');
                                                        $previewNav = (string) ($themePreview['nav'] ?? 'bg-white');
                                                        $previewAccent = (string) ($themePreview['accent'] ?? 'bg-red-brand');
                                                        $themeInputId = 'pv_color_theme_' . preg_replace('/[^a-z0-9_-]/i', '_', (string) $themeKey);
                                                    @endphp

                                                    <div>
                                                        <input
                                                            id="{{ $themeInputId }}"
                                                            type="radio"
                                                            name="pv_color_theme"
                                                            value="{{ $themeKey }}"
                                                            class="peer sr-only topbar-theme-input"
                                                            @checked($purpleSelectedColorTheme === $themeKey)
                                                        >
                                                        <label
                                                            for="{{ $themeInputId }}"
                                                            class="topbar-theme-label group relative block rounded-xl border border-gray-200 bg-white p-3 cursor-pointer transition-all duration-200"
                                                        >
                                                            <span class="topbar-theme-dot absolute bottom-3 ltr:right-3 rtl:left-3 h-2.5 w-2.5 rounded-full bg-primary opacity-0 scale-50 transition-all duration-200"></span>
                                                            <span class="topbar-theme-check absolute top-3 ltr:right-3 rtl:left-3 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white text-[11px] opacity-0 scale-75 transition-all duration-200">
                                                                &#10003;
                                                            </span>
                                                            <div class="text-sm font-semibold text-slate-800 mb-2">{{ $themeLabel }}</div>
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="topbar-theme-swatch h-3 w-9 rounded transition-transform duration-200 {{ $previewPromo }}"></span>
                                                                <span class="topbar-theme-swatch h-3 w-9 rounded border border-gray-200 transition-transform duration-200 {{ $previewNav }}"></span>
                                                                <span class="topbar-theme-swatch h-3 w-9 rounded transition-transform duration-200 {{ $previewAccent }}"></span>
                                                            </div>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <p class="text-xs text-muted mb-0 mt-2">
                                                {{ t('dashboard.Topbar_Color_Theme_Help', 'Applies a predefined color palette to the purple topbar header.') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        id="pv-custom-colors-panel"
                                        class="rounded-xl border border-gray-200 p-3 bg-gray-50/40 space-y-3 {{ $purpleSelectedColorTheme === 'custom' ? '' : 'hidden' }}"
                                    >
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <label class="form-label mb-0">{{ t('dashboard.Custom_Theme_Colors', 'Custom Theme Colors') }}</label>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xs text-muted">{{ t('dashboard.Custom_Theme_Colors_Help', 'Used when Color Theme = Custom (Manual).') }}</span>
                                                <button
                                                    type="button"
                                                    id="pv_custom_colors_reset"
                                                    class="btn btn-outline-secondary btn-sm"
                                                >
                                                    {{ t('dashboard.Reset_Custom_Colors', 'Reset custom colors') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            @foreach ($purpleCustomColorInputs as $colorKey => $colorValue)
                                                @php
                                                    $colorInputId = 'pv_custom_color_' . preg_replace('/[^a-z0-9_-]/i', '_', (string) $colorKey);
                                                @endphp
                                                <div>
                                                    <label for="{{ $colorInputId }}" class="form-label mb-1">
                                                        {{ $purpleCustomColorLabels[$colorKey] ?? ucwords(str_replace('_', ' ', (string) $colorKey)) }}
                                                    </label>
                                                    <div class="flex items-center gap-2">
                                                        <input
                                                            id="{{ $colorInputId }}"
                                                            type="color"
                                                            class="form-control form-control-color p-1 h-10 w-14"
                                                            value="{{ $colorValue }}"
                                                            data-pv-custom-picker="{{ $colorKey }}"
                                                        >
                                                        <input
                                                            id="{{ $colorInputId }}_hex"
                                                            name="pv_custom_colors[{{ $colorKey }}]"
                                                            type="text"
                                                            class="form-control font-mono uppercase"
                                                            value="{{ $colorValue }}"
                                                            pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$"
                                                            maxlength="7"
                                                            placeholder="#000000"
                                                            data-pv-custom-hex-input="{{ $colorKey }}"
                                                        >
                                                    </div>
                                                    @error('pv_custom_colors.' . $colorKey)
                                                        <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3">
                                        <div class="form-check">
                                            <input
                                                id="pv_show_social_icons"
                                                type="checkbox"
                                                class="form-check-input"
                                                name="pv_show_social_icons"
                                                value="1"
                                                @checked(old('pv_show_social_icons', $purpleTopbarSettings['show_social_icons']))
                                            >
                                            <label class="form-check-label" for="pv_show_social_icons">
                                                {{ t('dashboard.Show_Social_Icons', 'Show social icons') }}
                                            </label>
                                        </div>
                                        <p class="text-xs text-muted mb-0 ps-6">{{ t('dashboard.Social_Icons_Fallback_Help', 'Links are automatically taken from General Setting > Social Links.') }}</p>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3">
                                        <div class="form-check">
                                            <input
                                                id="pv_show_login_button"
                                                type="checkbox"
                                                class="form-check-input"
                                                name="pv_show_login_button"
                                                value="1"
                                                @checked(old('pv_show_login_button', $purpleTopbarSettings['show_login_button']))
                                            >
                                            <label class="form-check-label" for="pv_show_login_button">
                                                {{ t('dashboard.Show_Login_Button', 'Show login button') }}
                                            </label>
                                        </div>

                                        <div>
                                            <label for="pv_login_url" class="form-label mb-1">{{ t('dashboard.Login_URL', 'Login URL') }}</label>
                                            <input
                                                id="pv_login_url"
                                                name="pv_login_url"
                                                type="text"
                                                class="form-control"
                                                value="{{ old('pv_login_url', $purpleTopbarSettings['login_url']) }}"
                                                placeholder="/client/login"
                                            >
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3">
                                        <div class="form-check">
                                            <input
                                                id="pv_show_language_switcher"
                                                type="checkbox"
                                                class="form-check-input"
                                                name="pv_show_language_switcher"
                                                value="1"
                                                @checked(old('pv_show_language_switcher', $purpleTopbarSettings['show_language_switcher']))
                                            >
                                            <label class="form-check-label" for="pv_show_language_switcher">
                                                {{ t('dashboard.Show_Language_Switcher', 'Show language switcher') }}
                                            </label>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3">
                                        <div>
                                            <label for="pv_contact_button_url" class="form-label mb-1">{{ t('dashboard.Contact_Button_URL', 'Contact Button URL') }}</label>
                                            <input
                                                id="pv_contact_button_url"
                                                name="pv_contact_button_url"
                                                type="text"
                                                class="form-control"
                                                value="{{ old('pv_contact_button_url', $purpleTopbarSettings['contact_button_url']) }}"
                                                placeholder="#contact"
                                            >
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div id="header-settings-savehint" class="hidden alert alert-warning py-2 mb-0">
                                {{ t('dashboard.Unsaved_Changes', 'You have unsaved changes.') }}
                            </div>

                            <div class="flex gap-2">
                                <button type="button" id="header-settings-reset" class="btn btn-outline-secondary w-full">
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
                        <h5 class="mb-0">{{ t('dashboard.Header_Note', 'Header Note') }}</h5>
                    </div>
                    <div class="card-body space-y-3 text-sm text-muted">
                        <p class="mb-0">{{ t('dashboard.Header_Note_Desc', 'Click any card to activate a header layout. Menu items and structure remain managed from the Menus page.') }}</p>
                        <a href="{{ route('dashboard.menus') }}" class="btn btn-outline-primary w-full">
                            {{ t('dashboard.Manage_Menus', 'Manage Menus') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.querySelector('[data-header-search]');
                const filterButtons = document.querySelectorAll('[data-header-filter]');
                const cards = document.querySelectorAll('[data-header-card]');
                const emptyState = document.getElementById('header-variant-empty');
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
                        activeFilter = this.dataset.headerFilter || 'all';

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

                const settingsForm = document.getElementById('header-settings-form');
                const saveHint = document.getElementById('header-settings-savehint');
                const resetButton = document.getElementById('header-settings-reset');
                const logoOverrideInput = document.getElementById('pv_logo_override');
                const logoOverrideClearButton = document.getElementById('pv_logo_override_clear');
                const logoOverridePreview = document.getElementById('pv_logo_override_preview');
                const customColorsPanel = document.getElementById('pv-custom-colors-panel');
                const customColorsResetButton = document.getElementById('pv_custom_colors_reset');
                const fallbackLogoText = @json(t('dashboard.Fallback_To_General_Logo', 'Fallback to General Setting logo'));
                const storageBaseUrl = @json(asset('storage'));
                const baselineValues = @json($headerSettingsBaseline);
                const localizedBaselineValues = @json($headerSettingsLocalizedBaseline);
                const purpleInitialLangCode = @json($purpleInitialLangCode);
                const customColorFallbacks = @json($purpleCustomColorDefaults);

                if (!settingsForm) {
                    return;
                }

                const customHexByKey = new Map();
                const customPickerByKey = new Map();
                settingsForm.querySelectorAll('[data-pv-custom-hex-input]').forEach((input) => {
                    customHexByKey.set(input.getAttribute('data-pv-custom-hex-input'), input);
                });
                settingsForm.querySelectorAll('[data-pv-custom-picker]').forEach((input) => {
                    customPickerByKey.set(input.getAttribute('data-pv-custom-picker'), input);
                });
                const colorThemeInputs = settingsForm.querySelectorAll('input[name="pv_color_theme"]');

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
                    const checked = settingsForm.querySelector('input[name="pv_color_theme"]:checked');
                    return checked ? String(checked.value || '') : '';
                }

                function toggleCustomColorsPanel() {
                    if (!customColorsPanel) return;
                    customColorsPanel.classList.toggle('hidden', selectedColorTheme() !== 'custom');
                }

                function applyBaselineValues() {
                    setFieldValue('header_show_promo_bar', baselineValues.header_show_promo_bar);
                    setFieldValue('header_is_sticky', baselineValues.header_is_sticky);

                    setFieldValue('pv_show_social_icons', baselineValues.pv_show_social_icons);
                    setFieldValue('pv_show_login_button', baselineValues.pv_show_login_button);
                    setFieldValue('pv_login_url', baselineValues.pv_login_url);
                    setFieldValue('pv_show_language_switcher', baselineValues.pv_show_language_switcher);
                    setFieldValue('pv_contact_button_url', baselineValues.pv_contact_button_url);
                    setFieldValue('pv_logo_override', baselineValues.pv_logo_override);
                    setFieldValue('pv_color_theme', baselineValues.pv_color_theme);

                    Object.entries(localizedBaselineValues).forEach(([name, fieldValue]) => {
                        setFieldValue(name, fieldValue);
                    });

                    Object.entries(baselineValues).forEach(([name, fieldValue]) => {
                        if (name.startsWith('pv_custom_colors[')) {
                            setFieldValue(name, fieldValue);
                        }
                    });

                    syncAllCustomColorControlsFromInputs();
                    toggleCustomColorsPanel();
                }

                function activatePurpleTopbarLanguage(langCode) {
                    if (!langCode) return;

                    const tabButtons = settingsForm.querySelectorAll('[data-pv-lang-tab]');
                    const panels = settingsForm.querySelectorAll('[data-pv-lang-panel]');

                    tabButtons.forEach((button) => {
                        const currentCode = button.getAttribute('data-pv-lang-tab');
                        const isActive = currentCode === langCode;
                        button.classList.toggle('btn-primary', isActive);
                        button.classList.toggle('btn-outline-secondary', !isActive);
                    });

                    panels.forEach((panel) => {
                        const currentCode = panel.getAttribute('data-pv-lang-panel');
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
                        <div class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
                            <img src="${previewUrl}" alt="Header Logo Override" class="w-full h-full object-cover">
                        </div>
                    `;
                }

                settingsForm.addEventListener('change', updateSaveHint);
                settingsForm.addEventListener('input', updateSaveHint);

                settingsForm.querySelectorAll('[data-pv-lang-tab]').forEach((button) => {
                    button.addEventListener('click', () => {
                        activatePurpleTopbarLanguage(button.getAttribute('data-pv-lang-tab'));
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
                    input.addEventListener('change', toggleCustomColorsPanel);
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

                logoOverrideClearButton?.addEventListener('click', function () {
                    if (!logoOverrideInput) return;
                    logoOverrideInput.value = '';
                    renderLogoOverridePreview('');
                    updateSaveHint();
                });

                resetButton?.addEventListener('click', function () {
                    applyBaselineValues();
                    activatePurpleTopbarLanguage(purpleInitialLangCode);
                    if (logoOverrideInput) {
                        renderLogoOverridePreview(logoOverrideInput.value);
                    }
                    initialState = serialize(new FormData(settingsForm));
                    updateSaveHint();
                });

                if (logoOverrideInput) {
                    renderLogoOverridePreview(logoOverrideInput.value);
                }
                syncAllCustomColorControlsFromInputs();
                toggleCustomColorsPanel();

                activatePurpleTopbarLanguage(purpleInitialLangCode);

                updateSaveHint();
            });
        </script>
    @endpush
</x-dashboard-layout>
