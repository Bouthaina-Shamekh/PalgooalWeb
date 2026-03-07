@php
    $languages = collect($languages ?? []);
    $selectedDefaultLanguageId = old('default_language', $generalSetting['default_language'] ?? null);
    $contentLanguages = collect($contentLanguages ?? [])->filter(fn ($language) => filled($language->code ?? null))->values();
    if ($contentLanguages->isEmpty()) {
        $contentLanguages = $languages->filter(fn ($language) => filled($language->code ?? null))->values();
    }

    $selectedDefaultLanguage = $languages->firstWhere('id', $selectedDefaultLanguageId);
    if ($selectedDefaultLanguage && $contentLanguages->doesntContain(fn ($language) => (int) $language->id === (int) $selectedDefaultLanguage->id)) {
        $contentLanguages->prepend($selectedDefaultLanguage);
    }

    $currentLanguage = $selectedDefaultLanguage;
    $defaultLocaleCode = strtolower((string) ($currentLanguage->code ?? config('app.locale', 'en')));
    $fallbackLocaleCode = strtolower((string) config('app.fallback_locale', 'en'));
    $storedLocalizedContent = is_array($generalSetting['localized_content'] ?? null)
        ? $generalSetting['localized_content']
        : [];
    $resolveLocalizedSettingForForm = static function ($value, string $locale) use ($defaultLocaleCode, $fallbackLocaleCode): string {
        $locale = strtolower($locale);

        if (is_array($value)) {
            $normalizedValues = [];
            foreach ($value as $langKey => $langValue) {
                $normalizedValues[strtolower((string) $langKey)] = $langValue;
            }

            $localizedValue = trim((string) (
                $normalizedValues[$locale]
                ?? $normalizedValues[$defaultLocaleCode]
                ?? $normalizedValues[$fallbackLocaleCode]
                ?? ''
            ));

            if ($localizedValue !== '') {
                return $localizedValue;
            }

            foreach ($normalizedValues as $candidate) {
                $candidate = trim((string) $candidate);
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return trim((string) $value);
    };
    $generalLocalizedTextInputs = [];
    foreach ($contentLanguages as $language) {
        $code = strtolower((string) ($language->code ?? ''));
        if ($code === '') {
            continue;
        }

        $generalLocalizedTextInputs[$code] = [
            'site_title' => (string) old(
                "gs_texts.$code.site_title",
                $resolveLocalizedSettingForForm($storedLocalizedContent['site_title'] ?? [], $code),
            ),
            'site_discretion' => (string) old(
                "gs_texts.$code.site_discretion",
                $resolveLocalizedSettingForForm($storedLocalizedContent['site_discretion'] ?? [], $code),
            ),
            'contact_address' => (string) old(
                "gs_texts.$code.contact_address",
                $resolveLocalizedSettingForForm($storedLocalizedContent['contact_address'] ?? [], $code),
            ),
        ];
    }
    $generalSettingsLocalizedBaseline = [];
    foreach ($generalLocalizedTextInputs as $code => $fields) {
        $generalSettingsLocalizedBaseline["gs_texts[$code][site_title]"] = (string) ($fields['site_title'] ?? '');
        $generalSettingsLocalizedBaseline["gs_texts[$code][site_discretion]"] = (string) ($fields['site_discretion'] ?? '');
        $generalSettingsLocalizedBaseline["gs_texts[$code][contact_address]"] = (string) ($fields['contact_address'] ?? '');
    }
    $generalFirstErrorLang = null;
    foreach ($contentLanguages as $language) {
        $code = strtolower((string) ($language->code ?? ''));
        if ($code === '') {
            continue;
        }

        if (
            $errors->has("gs_texts.$code.site_title")
            || $errors->has("gs_texts.$code.site_discretion")
            || $errors->has("gs_texts.$code.contact_address")
        ) {
            $generalFirstErrorLang = $code;
            break;
        }
    }

    $firstContentLocale = (string) (array_key_first($generalLocalizedTextInputs) ?? $defaultLocaleCode);
    $initialContentLocale = $generalFirstErrorLang
        ?: (array_key_exists($defaultLocaleCode, $generalLocalizedTextInputs) ? $defaultLocaleCode : $firstContentLocale);
    $initialPreviewTitle = trim((string) ($generalLocalizedTextInputs[$initialContentLocale]['site_title'] ?? ''));
    if ($initialPreviewTitle === '') {
        $initialPreviewTitle = trim((string) ($generalSetting['site_title'] ?? ''));
    }
    $initialPreviewDescription = trim((string) ($generalLocalizedTextInputs[$initialContentLocale]['site_discretion'] ?? ''));
    if ($initialPreviewDescription === '') {
        $initialPreviewDescription = trim((string) ($generalSetting['site_discretion'] ?? ''));
    }
    $logoPath = $generalSetting['logo_url'] ?? '';
    $previewLogo = asset('assets/tamplate/images/logo.svg');
    if (!empty($logoPath)) {
        $previewLogo = \Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', '//'])
            ? $logoPath
            : asset('storage/' . ltrim($logoPath, '/'));
    }
@endphp

<div id="general-settings-page">
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                <li class="breadcrumb-item"><a href="javascript:void(0)">{{ t('dashboard.General_Setting', 'General Setting') }}</a></li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.General_Setting', 'General Setting') }}</h2>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success mb-4" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger mb-4" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <form id="general-settings-form" action="{{ route('dashboard.general_settings.update') }}" method="POST" class="grid grid-cols-12 gap-6 items-start">
        @csrf
        <div class="col-span-12 xl:col-span-3">
            <div class="card sticky top-6">
                <div class="card-header">
                    <h5 class="mb-1">{{ t('dashboard.Settings_Sections', 'Settings Sections') }}</h5>
                    <p class="text-sm text-muted mb-0">{{ t('dashboard.Use_Sections_Desc', 'Navigate sections and save all changes together.') }}</p>
                </div>
                <div class="card-body p-3">
                    <div class="space-y-2">
                        <button type="button" class="btn btn-light w-full text-start flex justify-between items-center" data-section-btn="identity">
                            <span>{{ t('dashboard.Identity', 'Identity') }}</span>
                            <span class="badge bg-light-success text-success" data-section-status="identity">Saved</span>
                        </button>
                        <button type="button" class="btn btn-light w-full text-start flex justify-between items-center" data-section-btn="branding">
                            <span>{{ t('dashboard.Brand_Assets', 'Brand Assets') }}</span>
                            <span class="badge bg-light-success text-success" data-section-status="branding">Saved</span>
                        </button>
                        <button type="button" class="btn btn-light w-full text-start flex justify-between items-center" data-section-btn="contact">
                            <span>{{ t('dashboard.Contact_Info', 'Contact Info') }}</span>
                            <span class="badge bg-light-success text-success" data-section-status="contact">Saved</span>
                        </button>
                        <button type="button" class="btn btn-light w-full text-start flex justify-between items-center" data-section-btn="social">
                            <span>{{ t('dashboard.Social_Links', 'Social Links') }}</span>
                            <span class="badge bg-light-success text-success" data-section-status="social">Saved</span>
                        </button>
                    </div>

                    <div class="border-t mt-4 pt-4 space-y-2">
                        <a href="{{ route('frontend.home') }}" target="_blank" class="btn btn-outline-primary w-full">
                            {{ t('dashboard.Preview_Homepage', 'Preview Homepage') }}
                        </a>
                        <a href="{{ route('dashboard.appearance.header') }}" class="btn btn-outline-secondary w-full">
                            {{ t('dashboard.Header_Layout', 'Header Layout') }}
                        </a>
                        <a href="{{ route('dashboard.appearance.footer') }}" class="btn btn-outline-secondary w-full">
                            {{ t('dashboard.Footer_Layout', 'Footer Layout') }}
                        </a>
                    </div>

                    <div class="border-t mt-4 pt-4 space-y-2">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 space-y-3">
                            <div class="flex items-center justify-between">
                                <h6 class="mb-0 text-sm font-semibold text-body">
                                    {{ t('dashboard.Backup_Restore', 'Backup & Restore') }}
                                </h6>
                                <span class="badge bg-light-secondary text-secondary">JSON</span>
                            </div>

                            <a href="{{ route('dashboard.general_settings.export') }}" class="btn btn-outline-primary w-full">
                                {{ t('dashboard.Export_JSON', 'Export JSON') }}
                            </a>

                            <input id="general-settings-import-file" name="settings_file" form="general-settings-import-form" data-ignore-dirty="1" type="file" accept=".json,application/json,text/plain" class="hidden">

                            <label for="general-settings-import-file" class="cursor-pointer w-full rounded-lg border border-dashed border-gray-300 bg-white px-3 py-2 flex items-center justify-between gap-2">
                                <span id="general-settings-import-filename" class="text-sm text-muted truncate">
                                    {{ t('dashboard.No_File_Selected', 'No file selected') }}
                                </span>
                                <span class="btn btn-sm btn-light shrink-0">
                                    {{ t('dashboard.Choose_File', 'Choose file') }}
                                </span>
                            </label>

                            <button id="general-settings-import-btn" type="submit" form="general-settings-import-form" class="btn btn-primary w-full" disabled>
                                {{ t('dashboard.Import_JSON', 'Import JSON') }}
                            </button>

                            <p class="text-xs text-muted mb-0 leading-5">
                                {{ t('dashboard.Import_JSON_Help', 'Importing JSON will overwrite current general settings.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">{{ t('dashboard.General_Setting', 'General Setting') }}</h5>
                    <p class="text-sm text-muted mb-0">{{ t('dashboard.Update_Site_Branding_Desc', 'Update branding, locale, contact channels, and social links.') }}</p>
                </div>
                <div class="card-body">
                    <input type="hidden" name="active_header_variant" value="{{ old('active_header_variant', $generalSetting['active_header_variant'] ?? 'default') }}">
                    <input type="hidden" name="active_footer_variant" value="{{ old('active_footer_variant', $generalSetting['active_footer_variant'] ?? 'default') }}">

                    @if ($contentLanguages->isNotEmpty())
                        <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h6 class="mb-1">{{ t('dashboard.Content_Language', 'Content Language') }}</h6>
                                    <p class="text-sm text-muted mb-0">{{ t('dashboard.Content_Language_Help', 'Switch language to edit translated site title, description, and address.') }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($contentLanguages as $language)
                                        @php
                                            $langCode = strtolower((string) ($language->code ?? ''));
                                            $isActiveLang = $langCode === $initialContentLocale;
                                        @endphp
                                        <button
                                            type="button"
                                            class="btn btn-sm {{ $isActiveLang ? 'btn-primary' : 'btn-light' }}"
                                            data-general-lang-btn="{{ $langCode }}"
                                            data-general-lang-label="{{ $language->name ?? strtoupper($langCode) }}"
                                            aria-pressed="{{ $isActiveLang ? 'true' : 'false' }}"
                                        >
                                            {{ $language->name ?? strtoupper($langCode) }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <section data-section-panel="identity" class="grid grid-cols-12 gap-4">
                        <div class="col-span-12">
                            <h6 class="mb-1">{{ t('dashboard.Identity', 'Identity') }}</h6>
                            <p class="text-sm text-muted mb-0">{{ t('dashboard.Identity_Section_Help', 'These fields appear in key frontend and SEO areas.') }}</p>
                        </div>

                        @foreach ($contentLanguages as $language)
                            @php
                                $langCode = strtolower((string) ($language->code ?? ''));
                                $langFields = $generalLocalizedTextInputs[$langCode] ?? [];
                            @endphp
                            <div data-general-lang-panel="{{ $langCode }}" class="col-span-12 grid grid-cols-12 gap-4 {{ $langCode === $initialContentLocale ? '' : 'hidden' }}">
                                <div class="col-span-12 md:col-span-6">
                                    <label for="gs_texts_{{ $langCode }}_site_title" class="form-label">{{ t('dashboard.Site_Title', 'Site Title') }}</label>
                                    <input
                                        id="gs_texts_{{ $langCode }}_site_title"
                                        name="gs_texts[{{ $langCode }}][site_title]"
                                        type="text"
                                        value="{{ $langFields['site_title'] ?? '' }}"
                                        class="form-control"
                                        placeholder="Enter site title"
                                    >
                                    @error('gs_texts.' . $langCode . '.site_title')
                                        <span class="text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-span-12 md:col-span-6">
                                    <label for="gs_texts_{{ $langCode }}_site_discretion" class="form-label">{{ t('dashboard.Site_Discretion', 'Site Description') }}</label>
                                    <input
                                        id="gs_texts_{{ $langCode }}_site_discretion"
                                        name="gs_texts[{{ $langCode }}][site_discretion]"
                                        type="text"
                                        value="{{ $langFields['site_discretion'] ?? '' }}"
                                        class="form-control"
                                        placeholder="Enter site description"
                                    >
                                    @error('gs_texts.' . $langCode . '.site_discretion')
                                        <span class="text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        @endforeach

                        <div class="col-span-12 md:col-span-6">
                            <label for="default_language" class="form-label">{{ t('dashboard.Default_Language', 'Default Language') }}</label>
                            @php
                                $selectedLanguage = old('default_language', $generalSetting['default_language'] ?? '');
                            @endphp
                            <select id="default_language" name="default_language" class="form-control">
                                <option value="">{{ t('dashboard.Select_Language', 'Select Language') }}</option>
                                @foreach ($contentLanguages as $language)
                                    <option value="{{ $language['id'] }}" {{ (string) $selectedLanguage === (string) $language['id'] ? 'selected' : '' }}>
                                        {{ $language['name'] . ' - ' . $language['native'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('default_language')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">{{ t('dashboard.Appearance', 'Appearance') }}</label>
                            <div class="rounded-lg border p-3 text-sm text-muted space-y-1">
                                <div>{{ t('dashboard.Active_Header', 'Active Header') }}: <strong class="text-body">{{ $generalSetting['active_header_variant'] ?? 'default' }}</strong></div>
                                <div>{{ t('dashboard.Active_Footer', 'Active Footer') }}: <strong class="text-body">{{ $generalSetting['active_footer_variant'] ?? 'default' }}</strong></div>
                                <div class="pt-1">
                                    <a href="{{ route('dashboard.appearance.header') }}" class="text-primary">{{ t('dashboard.Manage_Header_Layout', 'Manage Header Layout') }}</a>
                                </div>
                                <div>
                                    <a href="{{ route('dashboard.appearance.footer') }}" class="text-primary">{{ t('dashboard.Manage_Footer_Layout', 'Manage Footer Layout') }}</a>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section data-section-panel="branding" class="grid grid-cols-12 gap-4 hidden">
                        <div class="col-span-12">
                            <h6 class="mb-1">{{ t('dashboard.Brand_Assets', 'Brand Assets') }}</h6>
                            <p class="text-sm text-muted mb-0">{{ t('dashboard.Brand_Assets_Help', 'Choose logos from media library (Media Picker).') }}</p>
                        </div>
                        @php
                            $brandAssetFields = [
                                ['field' => 'logo_url', 'label' => t('dashboard.Logo', 'Logo'), 'alt' => 'Logo'],
                                ['field' => 'dark_logo_url', 'label' => t('dashboard.Dark_Logo', 'Dark Logo'), 'alt' => 'Dark Logo'],
                                ['field' => 'sticky_logo_url', 'label' => t('dashboard.Sticky_Logo', 'Sticky Logo'), 'alt' => 'Sticky Logo'],
                                ['field' => 'dark_sticky_logo_url', 'label' => t('dashboard.Dark_Sticky_Logo', 'Dark Sticky Logo'), 'alt' => 'Dark Sticky Logo'],
                                ['field' => 'admin_logo_url', 'label' => t('dashboard.Admin_Logo', 'Admin Logo'), 'alt' => 'Admin Logo'],
                                ['field' => 'admin_dark_logo_url', 'label' => t('dashboard.Admin_Dark_Logo', 'Admin Dark Logo'), 'alt' => 'Admin Dark Logo'],
                                ['field' => 'favicon_url', 'label' => t('dashboard.Favicon', 'Favicon'), 'alt' => 'Favicon'],
                            ];
                        @endphp

                        @foreach ($brandAssetFields as $assetField)
                            @php
                                $fieldName = $assetField['field'];
                                $inputId = 'media_' . $fieldName;
                                $previewId = $inputId . '_preview';
                                $currentPath = $generalSetting[$fieldName] ?? '';
                                $previewUrl = '';

                                if (!empty($currentPath)) {
                                    if (\Illuminate\Support\Str::startsWith($currentPath, ['http://', 'https://', '//'])) {
                                        $previewUrl = $currentPath;
                                    } else {
                                        $previewUrl = asset('storage/' . ltrim($currentPath, '/'));
                                    }
                                }
                            @endphp

                            <div class="col-span-12 md:col-span-6">
                                <label class="form-label">{{ $assetField['label'] }}</label>

                                <input
                                    id="{{ $inputId }}"
                                    name="{{ $fieldName }}"
                                    type="hidden"
                                    value="{{ old($fieldName, $currentPath) }}"
                                >

                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm btn-open-media-picker"
                                    data-target-input="{{ $inputId }}"
                                    data-target-preview="{{ $previewId }}"
                                    data-multiple="false"
                                    data-store-value="path"
                                >
                                    {{ t('dashboard.Choose_From_Media', 'Choose From Media Library') }}
                                </button>

                                <div id="{{ $previewId }}" class="mt-2 flex flex-wrap gap-2 min-h-[64px] items-center">
                                    @if (!empty($previewUrl))
                                        <div class="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
                                            <img src="{{ $previewUrl }}" alt="{{ $assetField['alt'] }}" class="w-full h-full object-cover">
                                        </div>
                                    @else
                                        <span class="text-xs text-muted">{{ t('dashboard.No_Image_Selected', 'No image selected') }}</span>
                                    @endif
                                </div>

                                @error($fieldName)
                                    <span class="text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </section>

                    <section data-section-panel="contact" class="grid grid-cols-12 gap-4 hidden">
                        <div class="col-span-12">
                            <h6 class="mb-1">{{ t('dashboard.Contact_Info', 'Contact Info') }}</h6>
                            <p class="text-sm text-muted mb-0">{{ t('dashboard.Contact_Info_Help', 'Shown in footer and some public sections.') }}</p>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="contact_phone" class="form-label">{{ t('dashboard.Phone', 'Phone') }}</label>
                            <input id="contact_phone" name="contact_info[phone]" type="text" value="{{ old('contact_info.phone', $generalSetting['contact_info']['phone'] ?? '') }}" class="form-control" placeholder="Enter phone">
                            @error('contact_info.phone')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="contact_email" class="form-label">{{ t('dashboard.Email', 'Email') }}</label>
                            <input id="contact_email" name="contact_info[email]" type="email" value="{{ old('contact_info.email', $generalSetting['contact_info']['email'] ?? '') }}" class="form-control" placeholder="Enter email">
                            @error('contact_info.email')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-span-12">
                            <p class="text-sm text-muted mb-3">{{ t('dashboard.Address_Language_Help', 'Address follows the selected content language above.') }}</p>
                        </div>

                        @foreach ($contentLanguages as $language)
                            @php
                                $langCode = strtolower((string) ($language->code ?? ''));
                                $langFields = $generalLocalizedTextInputs[$langCode] ?? [];
                            @endphp
                            <div data-general-lang-panel="{{ $langCode }}" class="col-span-12 {{ $langCode === $initialContentLocale ? '' : 'hidden' }}">
                                <label for="gs_texts_{{ $langCode }}_contact_address" class="form-label">{{ t('dashboard.Address', 'Address') }}</label>
                                <input
                                    id="gs_texts_{{ $langCode }}_contact_address"
                                    name="gs_texts[{{ $langCode }}][contact_address]"
                                    type="text"
                                    value="{{ $langFields['contact_address'] ?? '' }}"
                                    class="form-control"
                                    placeholder="Enter address"
                                >
                                @error('gs_texts.' . $langCode . '.contact_address')
                                    <span class="text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </section>

                    <section data-section-panel="social" class="grid grid-cols-12 gap-4 hidden">
                        <div class="col-span-12">
                            <h6 class="mb-1">{{ t('dashboard.Social_Links', 'Social Links') }}</h6>
                            <p class="text-sm text-muted mb-0">{{ t('dashboard.Social_Links_Help', 'Provide full URLs for social profiles.') }}</p>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="social_links_facebook" class="form-label">Facebook</label>
                            <input id="social_links_facebook" name="social_links[facebook]" type="url" value="{{ old('social_links.facebook', $generalSetting['social_links']['facebook'] ?? '') }}" class="form-control" placeholder="https://facebook.com/your-page">
                            @error('social_links.facebook')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="social_links_twitter" class="form-label">Twitter/X</label>
                            <input id="social_links_twitter" name="social_links[twitter]" type="url" value="{{ old('social_links.twitter', $generalSetting['social_links']['twitter'] ?? '') }}" class="form-control" placeholder="https://x.com/your-account">
                            @error('social_links.twitter')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="social_links_linkedin" class="form-label">LinkedIn</label>
                            <input id="social_links_linkedin" name="social_links[linkedin]" type="url" value="{{ old('social_links.linkedin', $generalSetting['social_links']['linkedin'] ?? '') }}" class="form-control" placeholder="https://linkedin.com/company/your-company">
                            @error('social_links.linkedin')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="social_links_instagram" class="form-label">Instagram</label>
                            <input id="social_links_instagram" name="social_links[instagram]" type="url" value="{{ old('social_links.instagram', $generalSetting['social_links']['instagram'] ?? '') }}" class="form-control" placeholder="https://instagram.com/your-account">
                            @error('social_links.instagram')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="social_links_whatsapp" class="form-label">WhatsApp</label>
                            <input id="social_links_whatsapp" name="social_links[whatsapp]" type="url" value="{{ old('social_links.whatsapp', $generalSetting['social_links']['whatsapp'] ?? '') }}" class="form-control" placeholder="https://wa.me/1234567890">
                            @error('social_links.whatsapp')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-3">
            <div class="card sticky top-6">
                <div class="card-header">
                    <h5 class="mb-1">{{ t('dashboard.Live_Preview', 'Live Preview') }}</h5>
                    <p class="text-sm text-muted mb-0">{{ t('dashboard.Live_Preview_Help', 'A quick snapshot of your public branding.') }}</p>
                </div>
                <div class="card-body">
                    <div class="rounded-lg border p-4 space-y-3">
                        <img id="preview-logo" src="{{ $previewLogo }}" data-default-src="{{ $previewLogo }}" alt="Preview Logo" class="h-12 object-contain">
                        <div class="flex items-center justify-between gap-3">
                            <h6 id="preview-site-title" class="mb-0">{{ $initialPreviewTitle !== '' ? $initialPreviewTitle : 'Site Title' }}</h6>
                            <span id="preview-content-language" class="badge bg-light-primary text-primary">
                                {{ optional($contentLanguages->first(fn ($language) => strtolower((string) ($language->code ?? '')) === $initialContentLocale))->name ?? strtoupper($initialContentLocale) }}
                            </span>
                        </div>
                        <p id="preview-site-description" class="text-sm text-muted mb-0">
                            {{ $initialPreviewDescription !== '' ? $initialPreviewDescription : 'Site description will appear here.' }}
                        </p>
                        <div class="text-xs text-muted border-t pt-2">
                            <div>{{ t('dashboard.Default_Language', 'Default Language') }}:
                                <strong class="text-body">{{ $currentLanguage?->name ?? '-' }}</strong>
                            </div>
                            <div>{{ t('dashboard.Contact_Email', 'Contact Email') }}:
                                <strong class="text-body">{{ $generalSetting['contact_info']['email'] ?? '-' }}</strong>
                            </div>
                            <div>{{ t('dashboard.Contact_Phone', 'Contact Phone') }}:
                                <strong class="text-body">{{ $generalSetting['contact_info']['phone'] ?? '-' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form id="general-settings-import-form" action="{{ route('dashboard.general_settings.import') }}" method="POST" enctype="multipart/form-data" class="hidden">
        @csrf
    </form>

    <div id="general-settings-savebar" class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-[1000] w-[95%] max-w-3xl">
        <div class="rounded-xl border border-amber-200 bg-amber-50 shadow-lg px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm">
                <strong>{{ t('dashboard.Unsaved_Changes', 'Unsaved changes') }}</strong>
                <span class="text-muted">{{ t('dashboard.Unsaved_Changes_Help', 'You have pending updates. Save or discard them.') }}</span>
                <div id="general-settings-autosave-status" class="text-xs text-muted mt-1"></div>
            </div>
            <div class="flex gap-2">
                <button id="general-settings-discard" type="button" class="btn btn-outline-secondary btn-sm">
                    {{ t('dashboard.Discard', 'Discard') }}
                </button>
                @can('edit', 'App\\Models\\GeneralSetting')
                    <button type="submit" form="general-settings-form" class="btn btn-primary btn-sm">
                        {{ t('dashboard.Save_Changes', 'Save Changes') }}
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <script>
        (function () {
            const init = () => {
                const page = document.getElementById('general-settings-page');
                if (!page) return;

                const form = document.getElementById('general-settings-form');
                const saveBar = document.getElementById('general-settings-savebar');
                const discardBtn = document.getElementById('general-settings-discard');
                const autosaveStatus = document.getElementById('general-settings-autosave-status');
                const importFileInput = document.getElementById('general-settings-import-file');
                const importBtn = document.getElementById('general-settings-import-btn');
                const importFileName = document.getElementById('general-settings-import-filename');
                const sectionButtons = Array.from(page.querySelectorAll('[data-section-btn]'));
                const sectionPanels = Array.from(page.querySelectorAll('[data-section-panel]'));
                const statusBadges = Array.from(page.querySelectorAll('[data-section-status]'));
                const languageButtons = Array.from(page.querySelectorAll('[data-general-lang-btn]'));
                const languagePanels = Array.from(page.querySelectorAll('[data-general-lang-panel]'));
                const previewContentLanguage = document.getElementById('preview-content-language');

                if (!form) return;

                const autosaveUrl = @json(route('dashboard.general_settings.autosave'));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const localizedBaselineValues = @json($generalSettingsLocalizedBaseline);
                const localizedLanguageCodes = @json(array_values(array_keys($generalLocalizedTextInputs)));
                let activeContentLanguage = @json($initialContentLocale);

                const elementValue = (el) => {
                    if (el.type === 'file') {
                        if (!el.files || !el.files.length) return '';
                        return Array.from(el.files).map((file) => `${file.name}:${file.size}:${file.lastModified}`).join('|');
                    }
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        return el.checked ? '1' : '0';
                    }
                    return el.value ?? '';
                };

                const fields = Array.from(form.querySelectorAll('input, select, textarea')).filter((el) => {
                    return !el.disabled && !el.dataset.ignoreDirty;
                });

                const fieldByName = (name) => {
                    return Array.from(form.elements).find((el) => el.name === name) || null;
                };

                const fieldById = (id) => document.getElementById(id);

                const captureInitial = (skipFileInputs = false) => {
                    fields.forEach((el) => {
                        if (skipFileInputs && el.type === 'file') return;
                        el.dataset.initialValue = elementValue(el);
                    });
                };

                const isFieldDirty = (el) => elementValue(el) !== (el.dataset.initialValue || '');
                const hasDirtyNonFileFields = () => fields.some((el) => el.type !== 'file' && isFieldDirty(el));

                const activateSection = (sectionKey) => {
                    sectionPanels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.sectionPanel !== sectionKey);
                    });
                    sectionButtons.forEach((btn) => {
                        const active = btn.dataset.sectionBtn === sectionKey;
                        btn.classList.toggle('btn-primary', active);
                        btn.classList.toggle('btn-light', !active);
                    });
                };

                const activateContentLanguage = (languageCode) => {
                    activeContentLanguage = languageCode;

                    languageButtons.forEach((button) => {
                        const active = button.dataset.generalLangBtn === languageCode;
                        button.classList.toggle('btn-primary', active);
                        button.classList.toggle('btn-light', !active);
                        button.setAttribute('aria-pressed', active ? 'true' : 'false');
                    });

                    languagePanels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.generalLangPanel !== languageCode);
                    });

                    if (previewContentLanguage) {
                        const activeButton = languageButtons.find((button) => button.dataset.generalLangBtn === languageCode);
                        previewContentLanguage.textContent = activeButton?.dataset.generalLangLabel || languageCode.toUpperCase();
                    }

                    updatePreview();
                };

                const updatePreview = () => {
                    const siteTitleInput = fieldByName(`gs_texts[${activeContentLanguage}][site_title]`);
                    const siteDescriptionInput = fieldByName(`gs_texts[${activeContentLanguage}][site_discretion]`);
                    const logoPathInput = document.getElementById('media_logo_url');
                    const previewTitle = document.getElementById('preview-site-title');
                    const previewDescription = document.getElementById('preview-site-description');
                    const previewLogo = document.getElementById('preview-logo');
                    const storageBaseUrl = @json(asset('storage'));

                    if (previewTitle && siteTitleInput) {
                        previewTitle.textContent = siteTitleInput.value.trim() || 'Site Title';
                    }
                    if (previewDescription && siteDescriptionInput) {
                        previewDescription.textContent = siteDescriptionInput.value.trim() || 'Site description will appear here.';
                    }
                    if (previewLogo) {
                        const logoPath = (logoPathInput?.value || '').trim();
                        if (!logoPath) {
                            previewLogo.src = previewLogo.dataset.defaultSrc || previewLogo.src;
                        } else if (/^(https?:)?\/\//i.test(logoPath)) {
                            previewLogo.src = logoPath;
                        } else {
                            const normalized = logoPath.replace(/^\/+/, '').replace(/^storage\//, '');
                            previewLogo.src = `${storageBaseUrl}/${normalized}`;
                        }
                    }
                };

                const setBadgeState = (badge, dirty) => {
                    if (!badge) return;
                    badge.textContent = dirty ? 'Unsaved' : 'Saved';
                    badge.classList.toggle('bg-light-warning', dirty);
                    badge.classList.toggle('text-warning', dirty);
                    badge.classList.toggle('bg-light-success', !dirty);
                    badge.classList.toggle('text-success', !dirty);
                };

                const updateDirtyState = () => {
                    let formDirty = false;
                    const sectionDirtyMap = {};

                    sectionPanels.forEach((panel) => {
                        sectionDirtyMap[panel.dataset.sectionPanel] = false;
                    });

                    fields.forEach((el) => {
                        const dirty = isFieldDirty(el);
                        if (!dirty) return;

                        formDirty = true;
                        const panel = el.closest('[data-section-panel]');
                        if (panel) {
                            sectionDirtyMap[panel.dataset.sectionPanel] = true;
                        }
                    });

                    saveBar?.classList.toggle('hidden', !formDirty);
                    statusBadges.forEach((badge) => {
                        const sectionKey = badge.dataset.sectionStatus;
                        setBadgeState(badge, !!sectionDirtyMap[sectionKey]);
                    });
                };

                const syncImportFileUi = () => {
                    const hasFile = !!importFileInput?.files?.length;
                    const fileName = hasFile ? importFileInput.files[0].name : 'No file selected';

                    if (importFileName) {
                        importFileName.textContent = fileName;
                        importFileName.classList.toggle('text-body', hasFile);
                        importFileName.classList.toggle('text-muted', !hasFile);
                    }

                    if (importBtn) {
                        importBtn.disabled = !hasFile;
                        importBtn.classList.toggle('btn-primary', hasFile);
                        importBtn.classList.toggle('btn-light', !hasFile);
                    }
                };

                const buildAutosavePayload = () => {
                    const defaultLanguageRaw = fieldByName('default_language')?.value || '';
                    const localizedPayload = {};

                    localizedLanguageCodes.forEach((languageCode) => {
                        localizedPayload[languageCode] = {
                            site_title: fieldByName(`gs_texts[${languageCode}][site_title]`)?.value || '',
                            site_discretion: fieldByName(`gs_texts[${languageCode}][site_discretion]`)?.value || '',
                            contact_address: fieldByName(`gs_texts[${languageCode}][contact_address]`)?.value || '',
                        };
                    });

                    return {
                        logo_url: fieldById('media_logo_url')?.value || '',
                        dark_logo_url: fieldById('media_dark_logo_url')?.value || '',
                        sticky_logo_url: fieldById('media_sticky_logo_url')?.value || '',
                        dark_sticky_logo_url: fieldById('media_dark_sticky_logo_url')?.value || '',
                        admin_logo_url: fieldById('media_admin_logo_url')?.value || '',
                        admin_dark_logo_url: fieldById('media_admin_dark_logo_url')?.value || '',
                        favicon_url: fieldById('media_favicon_url')?.value || '',
                        default_language: defaultLanguageRaw === '' ? null : Number(defaultLanguageRaw),
                        active_header_variant: fieldByName('active_header_variant')?.value || 'default',
                        active_footer_variant: fieldByName('active_footer_variant')?.value || 'default',
                        contact_info: {
                            phone: fieldByName('contact_info[phone]')?.value || '',
                            email: fieldByName('contact_info[email]')?.value || '',
                        },
                        social_links: {
                            facebook: fieldByName('social_links[facebook]')?.value || '',
                            twitter: fieldByName('social_links[twitter]')?.value || '',
                            linkedin: fieldByName('social_links[linkedin]')?.value || '',
                            instagram: fieldByName('social_links[instagram]')?.value || '',
                            whatsapp: fieldByName('social_links[whatsapp]')?.value || '',
                        },
                        gs_texts: localizedPayload,
                    };
                };

                let autosaveTimer = null;
                let autosaveInFlight = false;
                let autosaveQueued = false;

                const queueAutosave = () => {
                    if (autosaveTimer) clearTimeout(autosaveTimer);
                    autosaveTimer = setTimeout(runAutosave, 1200);
                };

                const runAutosave = () => {
                    if (!hasDirtyNonFileFields()) return;
                    if (autosaveInFlight) {
                        autosaveQueued = true;
                        return;
                    }

                    autosaveInFlight = true;
                    if (autosaveStatus) autosaveStatus.textContent = 'Saving draft...';

                    fetch(autosaveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(buildAutosavePayload()),
                    })
                        .then(async (response) => {
                            const payload = await response.json().catch(() => ({}));
                            if (!response.ok || payload.saved === false) {
                                if (autosaveStatus) autosaveStatus.textContent = payload.message || 'Draft not saved (check field format)';
                                return;
                            }

                            captureInitial(true);
                            updateDirtyState();
                            if (autosaveStatus) {
                                autosaveStatus.textContent = payload.saved_at
                                    ? `Auto-saved at ${payload.saved_at}`
                                    : 'Auto-saved';
                            }
                        })
                        .catch(() => {
                            if (autosaveStatus) autosaveStatus.textContent = 'Auto-save failed';
                        })
                        .finally(() => {
                            autosaveInFlight = false;
                            if (autosaveQueued) {
                                autosaveQueued = false;
                                queueAutosave();
                            }
                        });
                };

                sectionButtons.forEach((btn) => {
                    btn.addEventListener('click', () => activateSection(btn.dataset.sectionBtn));
                });

                languageButtons.forEach((button) => {
                    button.addEventListener('click', () => activateContentLanguage(button.dataset.generalLangBtn));
                });

                form.addEventListener('input', () => {
                    updatePreview();
                    updateDirtyState();
                    queueAutosave();
                });

                form.addEventListener('change', () => {
                    updatePreview();
                    updateDirtyState();
                    queueAutosave();
                });

                discardBtn?.addEventListener('click', () => {
                    window.location.reload();
                });

                importBtn?.addEventListener('click', (event) => {
                    if (!importFileInput?.files?.length) {
                        event.preventDefault();
                        window.alert('Please select a JSON file first.');
                        return;
                    }

                    if (!window.confirm('Import will overwrite your current general settings. Continue?')) {
                        event.preventDefault();
                    }
                });

                importFileInput?.addEventListener('change', () => {
                    syncImportFileUi();
                });

                activateSection('identity');
                Object.entries(localizedBaselineValues).forEach(([name, fieldValue]) => {
                    const field = fieldByName(name);
                    if (field) {
                        field.dataset.initialValue = fieldValue;
                    }
                });
                if (languageButtons.length > 0) {
                    activateContentLanguage(activeContentLanguage);
                }
                captureInitial();
                updatePreview();
                updateDirtyState();
                syncImportFileUi();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init, { once: true });
            } else {
                init();
            }
        })();
    </script>
</div>
