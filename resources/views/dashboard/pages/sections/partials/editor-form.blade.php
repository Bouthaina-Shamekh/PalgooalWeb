@php
    use App\Models\Media;

    $formId = $formId ?? 'section-edit-form';
    $formAction = $formAction ?? route('dashboard.pages.sections.update', [$page, $section], false);
    $saveAction = $saveAction ?? $formAction;
    $formClass = $formClass ?? 'space-y-6';
    $formMethod = $formMethod ?? 'POST';
    $formMethodSpoof = $formMethodSpoof ?? 'PUT';
    $preventNativeSubmit = $preventNativeSubmit ?? false;
    $surfaceClass = $surfaceClass ?? 'rounded-3xl border border-slate-200 bg-white shadow-sm';
    $sectionHeaderClass = $sectionHeaderClass ?? 'border-b border-slate-200 px-5 py-4 lg:px-6';
    $sectionBodyClass = $sectionBodyClass ?? 'p-5 lg:p-6';
    $settingsGridClass = $settingsGridClass ?? 'grid grid-cols-1 gap-5 lg:grid-cols-2';
    $contentGridClass = $contentGridClass ?? 'grid grid-cols-1 gap-5 lg:grid-cols-2';
    $showOrderField = $showOrderField ?? true;
    $feedbackMessage = $feedbackMessage ?? null;
    $feedbackTone = $feedbackTone ?? 'success';
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $editorLocaleCodes = collect($languages ?? [])->pluck('code')->filter()->values();
    $editorDefaultLocale = $editorLocaleCodes->contains(app()->getLocale())
        ? app()->getLocale()
        : ($editorLocaleCodes->first() ?? app()->getLocale());
@endphp

<form
    id="{{ $formId }}"
    method="{{ strtoupper($formMethod) }}"
    action="{{ $formAction }}"
    class="{{ $formClass }}"
    data-section-editor-form
    data-section-id="{{ $section->id }}"
    data-default-editor-tab="lang-{{ $editorDefaultLocale }}"
    data-save-action="{{ $saveAction }}"
    @if ($preventNativeSubmit) onsubmit="return false;" @endif
>
    @csrf
    @if ($formMethodSpoof)
        @method($formMethodSpoof)
    @endif

    @php
        $feedbackVisible = $viewErrors->any() || filled($feedbackMessage);
        $feedbackClasses = $feedbackTone === 'error'
            ? 'border-red-200 bg-red-50 text-red-800'
            : 'border-emerald-200 bg-emerald-50 text-emerald-800';
        $selectedType = old('type', $section->type);
        $isHeroCampaign = $selectedType === 'hero_campaign';
        $isProgrammingShowcase = $selectedType === 'programming_showcase';
        $isMobileAppShowcase = $selectedType === 'mobile_app_showcase';
        $isDesignShowcase = $selectedType === 'design_showcase';
        $isDigitalMarketingShowcase = $selectedType === 'digital_marketing_showcase';
        $isTechStackShowcase = $selectedType === 'tech_stack_showcase';
        $isReviewsShowcase = $selectedType === 'reviews_showcase';
        $isOurWorkShowcase = $selectedType === 'our_work_showcase';
        $usesInternalLabel = $isHeroCampaign || $isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase || $isTechStackShowcase || $isReviewsShowcase || $isOurWorkShowcase;
        $showEyebrowField = $selectedType === 'hero_default';
        $showDescriptionField = $isHeroCampaign || $isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isReviewsShowcase || $isOurWorkShowcase;
        $showFeaturesHeadingField = $isHeroCampaign;
        $showOutputsHeadingField = $isProgrammingShowcase;
        $showOutputsTextareaField = $isProgrammingShowcase;
        $showServicesTextareaField = $isDesignShowcase || $isDigitalMarketingShowcase;
        $showBrandFields = $isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase || $isReviewsShowcase || $isOurWorkShowcase;
        $showPrimaryButtonFields = ! in_array($selectedType, ['how_we_build', 'tech_stack_showcase', 'reviews_showcase', 'our_work_showcase'], true);
        $showSecondaryButtonFields = $selectedType === 'hero_default';
        $showFeatureRepeaterField = $isHeroCampaign;
        $showBuildStepsRepeaterField = $selectedType === 'how_we_build';
        $showReviewsDatabaseField = $isReviewsShowcase;
        $showOurWorkDatabaseField = $isOurWorkShowcase;
        $showFeaturesTextareaField = in_array($selectedType, ['hero_default', 'features_grid'], true);
        $showMobileAppGalleryField = $isMobileAppShowcase;
        $showDesignGalleryField = $isDesignShowcase;
        $showDigitalMarketingGalleryField = $isDigitalMarketingShowcase;
        $showTechStackMediaField = $isTechStackShowcase;
        $showMediaTypeField = $selectedType === 'hero_default';
        $showMediaUrlField = in_array($selectedType, ['hero_default', 'hero_campaign', 'programming_showcase'], true);
        $showSubtitleField = ! in_array($selectedType, ['programming_showcase', 'mobile_app_showcase', 'design_showcase', 'digital_marketing_showcase', 'tech_stack_showcase', 'reviews_showcase', 'our_work_showcase'], true);
        $showMainTitleField = ! $isTechStackShowcase;
    @endphp

    <div
        data-section-editor-feedback
        class="hidden rounded-2xl border px-4 py-3 text-sm {{ $feedbackVisible ? $feedbackClasses : 'border-slate-200 bg-slate-50 text-slate-600' }}"
    >
        <ul class="{{ $feedbackVisible ? '' : 'hidden ' }}space-y-1" data-section-editor-feedback-list>
            @if (filled($feedbackMessage))
                <li>{{ $feedbackMessage }}</li>
            @endif

            @foreach ($viewErrors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>

    <div class="{{ $surfaceClass }}">
        <div class="{{ $sectionBodyClass }}">
            <input type="hidden" name="type" value="{{ old('type', $section->type) }}">
            <input type="hidden" name="variant" value="{{ old('variant', $section->variant) }}">

            <div class="{{ $settingsGridClass }}">
                @if ($showOrderField)
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('Display Order') }}</label>
                        <input
                            type="number"
                            name="order"
                            value="{{ old('order', $section->order ?? 1) }}"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                        >
                    </div>
                @endif

                <div class="flex items-center">
                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            class="rounded border-slate-300"
                            {{ old('is_active', $section->is_active) ? 'checked' : '' }}
                        >
                        {{ __('Active on frontend') }}
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="{{ $surfaceClass }}">
        <div class="{{ $sectionHeaderClass }}">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('Section Content') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Edit localized content for each language.') }}</p>
        </div>

        <div class="{{ $sectionBodyClass }}">
            <div class="mb-5 border-b border-slate-200">
                <nav class="-mb-px flex flex-wrap gap-2" aria-label="Language tabs">
                    @foreach ($languages as $index => $language)
                        @php
                            $active = $language->code === $editorDefaultLocale;
                        @endphp
                        <button
                            type="button"
                            class="rounded-t-2xl border-b-2 px-4 py-2 text-sm font-medium transition {{ $active ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' }}"
                            data-editor-tab-button
                            data-tab="lang-{{ $language->code }}"
                        >
                            {{ $language->name }} ({{ $language->code }})
                        </button>
                    @endforeach
                </nav>
            </div>

            @foreach ($languages as $index => $language)
                @php
                    $code = $language->code;
                    $translation = $section->translations->firstWhere('locale', $code);
                    $content = $translation?->content ?? [];
                    $stringifyValue = static fn ($value) => is_scalar($value) ? (string) $value : '';
                    $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];
                    $secondaryButton = is_array($content['secondary_button'] ?? null) ? $content['secondary_button'] : [];

                    $featuresTextarea = old("translations.$code.content.features_textarea");
                    $campaignFeatureItems = [];
                    $buildStepItems = [];
                    $outputsTextarea = old("translations.$code.content.outputs_textarea");
                    $outputItems = [];
                    $servicesTextarea = old("translations.$code.content.services_textarea");
                    $serviceItems = [];

                    if ($featuresTextarea === null) {
                        if (!empty($content['features']) && is_array($content['features'])) {
                            $featuresTextarea = collect($content['features'])
                                ->map(function ($item) {
                                    if (is_array($item)) {
                                        return trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
                                    }

                                    return is_scalar($item) ? trim((string) $item) : '';
                                })
                                ->filter()
                                ->implode("\n");
                        } else {
                            $featuresTextarea = '';
                        }
                    }

                    if ($outputsTextarea === null) {
                        if (! empty($content['outputs']) && is_array($content['outputs'])) {
                            $outputsTextarea = implode("\n", $content['outputs']);
                        } else {
                            $outputsTextarea = '';
                        }
                    }

                    if ($servicesTextarea === null) {
                        if (! empty($content['services']) && is_array($content['services'])) {
                            $servicesTextarea = collect($content['services'])
                                ->map(function ($item) {
                                    if (is_array($item)) {
                                        return trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
                                    }

                                    return is_scalar($item) ? trim((string) $item) : '';
                                })
                                ->filter()
                                ->implode("\n");
                        } else {
                            $servicesTextarea = '';
                        }
                    }

                    if ($isHeroCampaign) {
                        $oldCampaignFeatures = old("translations.$code.content.features");
                        $campaignFeaturesSource = is_array($oldCampaignFeatures)
                            ? $oldCampaignFeatures
                            : (is_array($content['features'] ?? null) ? $content['features'] : []);

                        $campaignFeatureItems = collect($campaignFeaturesSource)
                            ->map(function ($item) {
                                if (is_array($item)) {
                                    $text = trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
                                    $icon = trim((string) ($item['icon'] ?? ''));
                                    $iconSource = trim((string) ($item['icon_source'] ?? 'class'));
                                    $iconSvg = trim((string) ($item['icon_svg'] ?? ''));
                                    $iconMedia = is_scalar($item['icon_media'] ?? null) ? (string) $item['icon_media'] : '';
                                } elseif (is_scalar($item)) {
                                    $text = trim((string) $item);
                                    $icon = '';
                                    $iconSource = 'class';
                                    $iconSvg = '';
                                    $iconMedia = '';
                                } else {
                                    return null;
                                }

                                if ($text === '') {
                                    return null;
                                }

                                return [
                                    'text' => $text,
                                    'icon' => $icon,
                                    'icon_source' => in_array($iconSource, ['class', 'svg', 'media'], true) ? $iconSource : 'class',
                                    'icon_svg' => $iconSvg,
                                    'icon_media' => $iconMedia,
                                ];
                            })
                            ->filter()
                            ->values()
                            ->all();
                    }

                    if ($selectedType === 'how_we_build') {
                        $oldBuildSteps = old("translations.$code.content.steps");
                        $buildStepsSource = is_array($oldBuildSteps)
                            ? $oldBuildSteps
                            : (is_array($content['steps'] ?? null) ? $content['steps'] : []);

                        $buildStepItems = collect($buildStepsSource)
                            ->map(function ($item) {
                                if (! is_array($item)) {
                                    return null;
                                }

                                $title = trim((string) ($item['title'] ?? $item['label'] ?? ''));
                                if ($title === '') {
                                    return null;
                                }

                                return [
                                    'title' => $title,
                                    'icon' => trim((string) ($item['icon'] ?? '')),
                                    'icon_source' => in_array(($item['icon_source'] ?? 'class'), ['class', 'svg', 'media'], true) ? (string) ($item['icon_source'] ?? 'class') : 'class',
                                    'icon_svg' => trim((string) ($item['icon_svg'] ?? '')),
                                    'icon_media' => is_scalar($item['icon_media'] ?? null) ? (string) $item['icon_media'] : '',
                                    'is_accent' => filter_var($item['is_accent'] ?? false, FILTER_VALIDATE_BOOLEAN),
                                ];
                            })
                            ->filter()
                            ->values()
                            ->all();
                    }

                    if ($isProgrammingShowcase) {
                        $oldOutputs = old("translations.$code.content.outputs");
                        $outputsSource = is_array($oldOutputs)
                            ? $oldOutputs
                            : (! empty($content['outputs']) && is_array($content['outputs']) ? $content['outputs'] : []);

                        if (empty($outputsSource) && filled($outputsTextarea)) {
                            $outputsSource = preg_split("/\r\n|\r|\n/", (string) $outputsTextarea);
                        }

                        $outputItems = collect($outputsSource)
                            ->map(function ($item) {
                                if (is_array($item)) {
                                    $text = trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
                                    $icon = trim((string) ($item['icon'] ?? ''));
                                    $iconSource = trim((string) ($item['icon_source'] ?? 'class'));
                                    $iconMedia = is_scalar($item['icon_media'] ?? null) ? (string) $item['icon_media'] : '';
                                } elseif (is_scalar($item)) {
                                    $text = trim((string) $item);
                                    $icon = '';
                                    $iconSource = 'class';
                                    $iconMedia = '';
                                } else {
                                    return null;
                                }

                                if ($text === '') {
                                    return null;
                                }

                                return [
                                    'text' => $text,
                                    'icon' => $icon,
                                    'icon_source' => in_array($iconSource, ['class', 'media'], true) ? $iconSource : 'class',
                                    'icon_media' => $iconMedia,
                                ];
                            })
                            ->filter()
                            ->values()
                            ->all();
                    }

                    if ($showServicesTextareaField) {
                        $oldServices = old("translations.$code.content.services");
                        $servicesSource = is_array($oldServices)
                            ? $oldServices
                            : (! empty($content['services']) && is_array($content['services']) ? $content['services'] : []);

                        if (empty($servicesSource) && filled($servicesTextarea)) {
                            $servicesSource = preg_split("/\r\n|\r|\n/", (string) $servicesTextarea);
                        }

                        $serviceItems = collect($servicesSource)
                            ->map(function ($item) {
                                if (is_array($item)) {
                                    $text = trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
                                    $icon = trim((string) ($item['icon'] ?? ''));
                                    $iconSource = trim((string) ($item['icon_source'] ?? 'class'));
                                    $iconMedia = is_scalar($item['icon_media'] ?? null) ? (string) $item['icon_media'] : '';
                                } elseif (is_scalar($item)) {
                                    $text = trim((string) $item);
                                    $icon = '';
                                    $iconSource = 'class';
                                    $iconMedia = '';
                                } else {
                                    return null;
                                }

                                if ($text === '') {
                                    return null;
                                }

                                return [
                                    'text' => $text,
                                    'icon' => $icon,
                                    'icon_source' => in_array($iconSource, ['class', 'media'], true) ? $iconSource : 'class',
                                    'icon_media' => $iconMedia,
                                ];
                            })
                            ->filter()
                            ->values()
                            ->all();
                    }

                    $sectionTitleValue = $stringifyValue(old("translations.$code.title", $translation->title ?? ''));
                    $eyebrowValue = $stringifyValue(old("translations.$code.content.eyebrow", $content['eyebrow'] ?? ''));
                    $heroTitleValue = $stringifyValue(old("translations.$code.content.title", $content['title'] ?? ''));
                    $brandPrefixValue = $stringifyValue(old("translations.$code.content.brand_prefix", $content['brand_prefix'] ?? ''));
                    $brandSuffixValue = $stringifyValue(old("translations.$code.content.brand_suffix", $content['brand_suffix'] ?? ''));
                    $subtitleValue = $stringifyValue(old("translations.$code.content.subtitle", $content['subtitle'] ?? ''));
                    $descriptionValue = $stringifyValue(old("translations.$code.content.description", $content['description'] ?? ''));
                    $featuresHeadingValue = $stringifyValue(old("translations.$code.content.features_heading", $content['features_heading'] ?? ''));
                    $outputsHeadingValue = $stringifyValue(old("translations.$code.content.outputs_heading", $content['outputs_heading'] ?? ''));
                    $primaryButtonLabelValue = $stringifyValue(old("translations.$code.content.primary_button.label", $primaryButton['label'] ?? ''));
                    $primaryButtonUrlValue = $stringifyValue(old("translations.$code.content.primary_button.url", $primaryButton['url'] ?? ''));
                    $primaryButtonNewTabValue = filter_var(old("translations.$code.content.primary_button.new_tab", $primaryButton['new_tab'] ?? false), FILTER_VALIDATE_BOOLEAN);
                    $secondaryButtonLabelValue = $stringifyValue(old("translations.$code.content.secondary_button.label", $secondaryButton['label'] ?? ''));
                    $secondaryButtonUrlValue = $stringifyValue(old("translations.$code.content.secondary_button.url", $secondaryButton['url'] ?? ''));
                    $reviewsLimitValue = $stringifyValue(old("translations.$code.content.limit", $content['limit'] ?? ''));
                    $ourWorkLimitValue = $stringifyValue(old("translations.$code.content.limit", $content['limit'] ?? ''));
                    $ourWorkVisitLabelValue = $stringifyValue(old("translations.$code.content.visit_label", $content['visit_label'] ?? ''));
                    $mediaUrlValue = $stringifyValue(old("translations.$code.content.media_url", $content['media_url'] ?? ''));
                    $mediaTypeOld = old("translations.$code.content.media_type", $content['media_type'] ?? 'image');
                    $campaignIllustrationValue = old("translations.$code.content.media_url", $content['media_url'] ?? null);
                    $campaignIllustrationPreviewUrls = [];
                    $mobileAppImageOneValue = old("translations.$code.content.image_one", $content['image_one'] ?? null);
                    $mobileAppImageTwoValue = old("translations.$code.content.image_two", $content['image_two'] ?? null);
                    $mobileAppImageThreeValue = old("translations.$code.content.image_three", $content['image_three'] ?? null);
                    $designImageFourValue = old("translations.$code.content.image_four", $content['image_four'] ?? null);
                    $designImageFiveValue = old("translations.$code.content.image_five", $content['image_five'] ?? null);
                    $designImageSixValue = old("translations.$code.content.image_six", $content['image_six'] ?? null);
                    $mobileAppImageOnePreviewUrls = [];
                    $mobileAppImageTwoPreviewUrls = [];
                    $mobileAppImageThreePreviewUrls = [];
                    $designImageFourPreviewUrls = [];
                    $designImageFivePreviewUrls = [];
                    $designImageSixPreviewUrls = [];
                    $techStackLogosValue = old("translations.$code.content.logos", $content['logos'] ?? []);
                    $techStackLogoPreviewUrls = [];

                    $buildMediaPreviewUrls = static function ($value): array {
                        if (is_numeric($value)) {
                            $mediaItem = Media::find((int) $value);

                            return $mediaItem?->url ? [$mediaItem->url] : [];
                        }

                        if (is_string($value) && $value !== '') {
                            return [
                                \Illuminate\Support\Str::startsWith($value, ['http://', 'https://', '//', '/', 'data:'])
                                    ? $value
                                    : asset($value),
                            ];
                        }

                        return [];
                    };

                    if ($isHeroCampaign || $isProgrammingShowcase) {
                        $campaignIllustrationPreviewUrls = $buildMediaPreviewUrls($campaignIllustrationValue);
                    }

                    if ($isMobileAppShowcase) {
                        $mobileAppImageOnePreviewUrls = $buildMediaPreviewUrls($mobileAppImageOneValue);
                        $mobileAppImageTwoPreviewUrls = $buildMediaPreviewUrls($mobileAppImageTwoValue);
                        $mobileAppImageThreePreviewUrls = $buildMediaPreviewUrls($mobileAppImageThreeValue);
                    }

                    if ($isDesignShowcase) {
                        $mobileAppImageOnePreviewUrls = $buildMediaPreviewUrls($mobileAppImageOneValue);
                        $mobileAppImageTwoPreviewUrls = $buildMediaPreviewUrls($mobileAppImageTwoValue);
                        $mobileAppImageThreePreviewUrls = $buildMediaPreviewUrls($mobileAppImageThreeValue);
                        $designImageFourPreviewUrls = $buildMediaPreviewUrls($designImageFourValue);
                        $designImageFivePreviewUrls = $buildMediaPreviewUrls($designImageFiveValue);
                        $designImageSixPreviewUrls = $buildMediaPreviewUrls($designImageSixValue);
                    }

                    if ($isDigitalMarketingShowcase) {
                        $mobileAppImageOnePreviewUrls = $buildMediaPreviewUrls($mobileAppImageOneValue);
                        $mobileAppImageTwoPreviewUrls = $buildMediaPreviewUrls($mobileAppImageTwoValue);
                    }

                    if ($isTechStackShowcase) {
                        $logoValues = is_string($techStackLogosValue)
                            ? array_values(array_filter(array_map('trim', explode(',', $techStackLogosValue))))
                            : (is_array($techStackLogosValue) ? $techStackLogosValue : []);

                        $techStackLogoPreviewUrls = collect($logoValues)
                            ->flatMap(fn ($value) => $buildMediaPreviewUrls($value))
                            ->filter()
                            ->values()
                            ->all();
                    }

                @endphp

                <div
                    id="lang-{{ $code }}"
                    data-editor-tab-panel
                    class="{{ $code === $editorDefaultLocale ? '' : 'hidden' }}"
                >
                    <input type="hidden" name="translations[{{ $code }}][locale]" value="{{ $code }}">

                    <div class="{{ $contentGridClass }}">
                        @if ($usesInternalLabel)
                            <input
                                type="hidden"
                                name="translations[{{ $code }}][title]"
                                value="{{ $sectionTitleValue }}"
                            >
                        @else
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ __('Section Title') }} ({{ $code }})
                                </label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][title]"
                                    value="{{ $sectionTitleValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>
                        @endif

                        @if ($isTechStackShowcase)
                            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                {{ __('This section renders a horizontally scrollable strip of technology logos. Only the internal label and media library logos are needed.') }}
                            </div>
                        @endif

                        @if ($isReviewsShowcase)
                            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                {{ __('This section uses a brand label, one main heading, a short intro, and approved testimonial cards pulled automatically from the Testimonials module.') }}
                            </div>
                        @endif

                        @if ($isOurWorkShowcase)
                            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                {{ __('This section uses a brand label, one main heading, a short intro, and portfolio cards pulled automatically from the Portfolios module.') }}
                            </div>
                        @endif

                        @if ($selectedType === 'how_we_build')
                            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                {{ __('This section uses a heading, a short subtitle, and a build-process timeline made of editable step cards.') }}
                            </div>
                        @endif

                        @if ($showEyebrowField)
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Eyebrow') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][eyebrow]"
                                    value="{{ $eyebrowValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>
                        @endif

                        @if ($showBrandFields)
                            <div class="{{ ($isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase) ? 'lg:col-span-2' : '' }}">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Brand Prefix') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][brand_prefix]"
                                    value="{{ $brandPrefixValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="PAL"
                                >
                            </div>

                            <div class="{{ ($isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase) ? 'lg:col-span-2' : '' }}">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Brand Suffix') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][brand_suffix]"
                                    value="{{ $brandSuffixValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="GOALS"
                                >
                            </div>
                        @endif

                        @if ($showMainTitleField)
                            <div class="{{ ($isHeroCampaign || $isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase) ? 'lg:col-span-2' : '' }}">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $isHeroCampaign ? __('Main Title - Line 1') : (($isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase) ? __('Section Title') : __('Main Title')) }}
                                </label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][title]"
                                    value="{{ $heroTitleValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>
                        @endif

                        @if ($showSubtitleField)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $isHeroCampaign ? __('Main Title - Line 2') : __('Subtitle') }}
                                </label>
                                <textarea
                                    name="translations[{{ $code }}][content][subtitle]"
                                    rows="3"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >{{ $subtitleValue }}</textarea>
                            </div>
                        @endif

                        @if ($showDescriptionField)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                                <textarea
                                    name="translations[{{ $code }}][content][description]"
                                    rows="4"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >{{ $descriptionValue }}</textarea>
                            </div>
                        @endif

                        @if ($showOutputsHeadingField)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Outputs Heading') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][outputs_heading]"
                                    value="{{ $outputsHeadingValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>
                        @endif

                        @if ($showFeaturesHeadingField)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Features Heading') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][features_heading]"
                                    value="{{ $featuresHeadingValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>
                        @endif

                        @if ($showOutputsTextareaField)
                            <div class="lg:col-span-2">
                                <div
                                    data-output-repeater
                                    data-output-item-label="{{ __('Output') }}"
                                    data-output-item-hint="{{ __('Click to edit this output') }}"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">{{ __('Outputs List') }}</label>
                                            <p class="mt-1 text-xs text-slate-500">{{ __('Create the outputs as separate items to keep the section tidy.') }}</p>
                                        </div>
                                        <button
                                            type="button"
                                            data-add-output-item
                                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                        >
                                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                                            <span>{{ __('Add Output') }}</span>
                                        </button>
                                    </div>

                                    <div class="mt-3">
                                        <div class="space-y-3" data-output-items>
                                            @foreach ($outputItems as $outputIndex => $outputItem)
                                                <article data-output-item class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                                                    <div class="space-y-3">
                                                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                            <button
                                                                type="button"
                                                                data-output-drag-handle
                                                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                                                aria-label="{{ __('Reorder output') }}"
                                                            >
                                                                <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                                                            </button>

                                                            <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                                                <button
                                                                    type="button"
                                                                    data-duplicate-output-item
                                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                                                    aria-label="{{ __('Duplicate output') }}"
                                                                >
                                                                    <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    data-remove-output-item
                                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                                                    aria-label="{{ __('Remove output') }}"
                                                                >
                                                                    <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            data-output-toggle
                                                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                            class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right"
                                                        >
                                                            <div class="min-w-0 flex-1">
                                                                <p dir="auto" data-output-item-title class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                                                    {{ filled($outputItem['text'] ?? '') ? $outputItem['text'] : __('Output') . ' ' . ($outputIndex + 1) }}
                                                                </p>
                                                                <p dir="auto" data-output-item-summary class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                                                    {{
                                                                        ($outputItem['icon_source'] ?? 'class') === 'media'
                                                                            ? (! empty($outputItem['icon_media']) ? __('SVG from media library') : __('Click to edit this output'))
                                                                            : (filled($outputItem['icon'] ?? '') ? __('Tabler icon selected') : __('Visible in the outputs list'))
                                                                    }}
                                                                </p>
                                                            </div>

                                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                                                <i data-output-toggle-icon class="ti ti-chevron-down text-base leading-none {{ $loop->first ? 'rotate-180' : '' }}" aria-hidden="true"></i>
                                                            </span>
                                                        </button>
                                                    </div>

                                                    <div data-output-item-body class="mt-4 space-y-4 {{ $loop->first ? '' : 'hidden' }}">
                                                        <div>
                                                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                                <label class="block text-sm font-medium text-slate-700">{{ __('Output Text') }}</label>
                                                                <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                                                            </div>
                                                            <input
                                                                type="text"
                                                                name="translations[{{ $code }}][content][outputs][{{ $outputIndex }}][text]"
                                                                data-name-template="translations[{{ $code }}][content][outputs][__INDEX__][text]"
                                                                data-output-field="text"
                                                                value="{{ $outputItem['text'] ?? '' }}"
                                                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                placeholder="{{ __('Example: Landing Sites') }}"
                                                            >
                                                            <p class="mt-2 text-xs text-slate-500">{{ __('This text appears as one item in the outputs list.') }}</p>
                                                        </div>

                                                        @php
                                                            $outputMediaPreviewUrls = $buildMediaPreviewUrls($outputItem['icon_media'] ?? null);
                                                        @endphp
                                                        <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                                                            <div
                                                                data-output-icon-preview
                                                                class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand"
                                                            >
                                                                @if (! empty($outputItem['icon']))
                                                                    <i class="{{ $outputItem['icon'] }} text-xl leading-none" aria-hidden="true"></i>
                                                                @else
                                                                    <span class="h-0.5 w-5 rounded-full bg-red-brand"></span>
                                                                @endif
                                                            </div>

                                                            <div class="space-y-3">
                                                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                                    <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                                                    <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                                                </div>
                                                                <select
                                                                    name="translations[{{ $code }}][content][outputs][{{ $outputIndex }}][icon_source]"
                                                                    data-name-template="translations[{{ $code }}][content][outputs][__INDEX__][icon_source]"
                                                                    data-output-field="icon_source"
                                                                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                >
                                                                    <option value="class" @selected(($outputItem['icon_source'] ?? 'class') === 'class')>{{ __('Tabler Icon') }}</option>
                                                                    <option value="media" @selected(($outputItem['icon_source'] ?? 'class') === 'media')>{{ __('SVG From Media') }}</option>
                                                                </select>

                                                                <div data-output-icon-panel="class" class="space-y-3">
                                                                    <input
                                                                        type="text"
                                                                        name="translations[{{ $code }}][content][outputs][{{ $outputIndex }}][icon]"
                                                                        data-name-template="translations[{{ $code }}][content][outputs][__INDEX__][icon]"
                                                                        data-output-field="icon"
                                                                        value="{{ $outputItem['icon'] ?? '' }}"
                                                                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                        placeholder="ti ti-point"
                                                                    >
                                                                    <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                                                        <button
                                                                            type="button"
                                                                            data-open-section-icon-library
                                                                            data-icon-input-selector='[data-output-field="icon"]'
                                                                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                        >
                                                                            <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                                                            <span>{{ __('Choose From Icon Library') }}</span>
                                                                        </button>
                                                                    </div>
                                                                    <p class="text-xs text-slate-500">{{ __('Use the icon library or type a Tabler class manually.') }}</p>
                                                                </div>

                                                                <div data-output-icon-panel="media" class="space-y-2 hidden">
                                                                    <input
                                                                        type="hidden"
                                                                        name="translations[{{ $code }}][content][outputs][{{ $outputIndex }}][icon_media]"
                                                                        data-name-template="translations[{{ $code }}][content][outputs][__INDEX__][icon_media]"
                                                                        data-output-field="icon_media"
                                                                        value="{{ $outputItem['icon_media'] ?? '' }}"
                                                                    >
                                                                    <button
                                                                        type="button"
                                                                        data-output-icon-media-button
                                                                        class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                    >
                                                                        <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                                                        <span>{{ __('Choose SVG From Media') }}</span>
                                                                    </button>
                                                                    <div data-output-icon-media-preview class="flex flex-wrap gap-2">
                                                                        @foreach ($outputMediaPreviewUrls as $url)
                                                                            <div class="relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                                                                <img src="{{ $url }}" alt="" class="h-full w-full object-contain p-2">
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                    <p class="text-xs text-slate-500">{{ __('Upload or choose an SVG file from the media library when you need a branded output icon.') }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </article>
                                            @endforeach
                                        </div>

                                        <div data-output-empty class="{{ count($outputItems) ? 'hidden ' : '' }}mt-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-6 text-center text-sm text-slate-500">
                                            {{ __('No outputs yet. Add the first one to build the list.') }}
                                        </div>

                                        <template data-output-item-template>
                                            <article data-output-item class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                                                <div class="space-y-3">
                                                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                        <button
                                                            type="button"
                                                            data-output-drag-handle
                                                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                                            aria-label="{{ __('Reorder output') }}"
                                                        >
                                                            <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                                                        </button>

                                                        <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                                            <button
                                                                type="button"
                                                                data-duplicate-output-item
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                                                aria-label="{{ __('Duplicate output') }}"
                                                            >
                                                                <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                                            </button>
                                                            <button
                                                                type="button"
                                                                data-remove-output-item
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                                                aria-label="{{ __('Remove output') }}"
                                                            >
                                                                <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        data-output-toggle
                                                        aria-expanded="false"
                                                        class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right"
                                                    >
                                                        <div class="min-w-0 flex-1">
                                                            <p dir="auto" data-output-item-title class="text-sm font-semibold leading-5 text-slate-900 break-words">{{ __('Output') }}</p>
                                                            <p dir="auto" data-output-item-summary class="mt-1 text-xs leading-5 text-slate-500 break-words">{{ __('Click to edit this output') }}</p>
                                                        </div>

                                                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                                            <i data-output-toggle-icon class="ti ti-chevron-down text-base leading-none" aria-hidden="true"></i>
                                                        </span>
                                                    </button>
                                                </div>

                                                <div data-output-item-body class="mt-4 hidden space-y-4">
                                                    <div>
                                                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                            <label class="block text-sm font-medium text-slate-700">{{ __('Output Text') }}</label>
                                                            <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                                                        </div>
                                                        <input
                                                            type="text"
                                                            name="translations[{{ $code }}][content][outputs][__INDEX__][text]"
                                                            data-name-template="translations[{{ $code }}][content][outputs][__INDEX__][text]"
                                                            data-output-field="text"
                                                            value=""
                                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                            placeholder="{{ __('Example: Landing Sites') }}"
                                                        >
                                                        <p class="mt-2 text-xs text-slate-500">{{ __('This text appears as one item in the outputs list.') }}</p>
                                                    </div>

                                                    <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                                                        <div
                                                            data-output-icon-preview
                                                            class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand"
                                                        >
                                                            <span class="h-0.5 w-5 rounded-full bg-red-brand"></span>
                                                        </div>

                                                        <div class="space-y-3">
                                                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                                <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                                                <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                                            </div>
                                                            <select
                                                                name="translations[{{ $code }}][content][outputs][__INDEX__][icon_source]"
                                                                data-name-template="translations[{{ $code }}][content][outputs][__INDEX__][icon_source]"
                                                                data-output-field="icon_source"
                                                                class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                            >
                                                                <option value="class">{{ __('Tabler Icon') }}</option>
                                                                <option value="media">{{ __('SVG From Media') }}</option>
                                                            </select>

                                                            <div data-output-icon-panel="class" class="space-y-3">
                                                                <input
                                                                    type="text"
                                                                    name="translations[{{ $code }}][content][outputs][__INDEX__][icon]"
                                                                    data-name-template="translations[{{ $code }}][content][outputs][__INDEX__][icon]"
                                                                    data-output-field="icon"
                                                                    value=""
                                                                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                    placeholder="ti ti-point"
                                                                >
                                                                <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                                                    <button
                                                                        type="button"
                                                                        data-open-section-icon-library
                                                                        data-icon-input-selector='[data-output-field="icon"]'
                                                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                    >
                                                                        <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                                                        <span>{{ __('Choose From Icon Library') }}</span>
                                                                    </button>
                                                                </div>
                                                                <p class="text-xs text-slate-500">{{ __('Use the icon library or type a Tabler class manually.') }}</p>
                                                            </div>

                                                            <div data-output-icon-panel="media" class="space-y-2 hidden">
                                                                <input
                                                                    type="hidden"
                                                                    name="translations[{{ $code }}][content][outputs][__INDEX__][icon_media]"
                                                                    data-name-template="translations[{{ $code }}][content][outputs][__INDEX__][icon_media]"
                                                                    data-output-field="icon_media"
                                                                    value=""
                                                                >
                                                                <button
                                                                    type="button"
                                                                    data-output-icon-media-button
                                                                    class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                >
                                                                    <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                                                    <span>{{ __('Choose SVG From Media') }}</span>
                                                                </button>
                                                                <div data-output-icon-media-preview class="flex flex-wrap gap-2"></div>
                                                                <p class="text-xs text-slate-500">{{ __('Upload or choose an SVG file from the media library when you need a branded output icon.') }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showServicesTextareaField)
                            <div class="lg:col-span-2">
                                <div
                                    data-service-repeater
                                    data-service-item-label="{{ __('Service') }}"
                                    data-service-item-hint="{{ __('Click to edit this service') }}"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">{{ __('Services List') }}</label>
                                            <p class="mt-1 text-xs text-slate-500">{{ __('Create the services as individual items to keep the section organized.') }}</p>
                                        </div>
                                        <button
                                            type="button"
                                            data-add-service-item
                                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                        >
                                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                                            <span>{{ __('Add Service') }}</span>
                                        </button>
                                    </div>

                                    <div class="mt-3">
                                        <div class="space-y-3" data-service-items>
                                            @foreach ($serviceItems as $serviceIndex => $serviceItem)
                                                <article data-service-item class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                                                    <div class="space-y-3">
                                                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                            <button
                                                                type="button"
                                                                data-service-drag-handle
                                                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                                                aria-label="{{ __('Reorder service') }}"
                                                            >
                                                                <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                                                            </button>

                                                            <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                                                <button
                                                                    type="button"
                                                                    data-duplicate-service-item
                                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                                                    aria-label="{{ __('Duplicate service') }}"
                                                                >
                                                                    <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    data-remove-service-item
                                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                                                    aria-label="{{ __('Remove service') }}"
                                                                >
                                                                    <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            data-service-toggle
                                                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                            class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right"
                                                        >
                                                            <div class="min-w-0 flex-1">
                                                                <p dir="auto" data-service-item-title class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                                                    {{ filled($serviceItem['text'] ?? '') ? $serviceItem['text'] : __('Service') . ' ' . ($serviceIndex + 1) }}
                                                                </p>
                                                                <p dir="auto" data-service-item-summary class="mt-1 text-xs leading-5 text-slate-500 break-words">{{ __('Visible in the services list') }}</p>
                                                            </div>

                                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                                                <i data-service-toggle-icon class="ti ti-chevron-down text-base leading-none {{ $loop->first ? 'rotate-180' : '' }}" aria-hidden="true"></i>
                                                            </span>
                                                        </button>
                                                    </div>

                                                    @php
                                                        $serviceMediaPreviewUrls = $buildMediaPreviewUrls($serviceItem['icon_media'] ?? null);
                                                    @endphp
                                                    <div data-service-item-body class="mt-4 space-y-4 {{ $loop->first ? '' : 'hidden' }}">
                                                        <div>
                                                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                                <label class="block text-sm font-medium text-slate-700">{{ __('Service Text') }}</label>
                                                                <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                                                            </div>
                                                            <input
                                                                type="text"
                                                                name="translations[{{ $code }}][content][services][{{ $serviceIndex }}][text]"
                                                                data-name-template="translations[{{ $code }}][content][services][__INDEX__][text]"
                                                                data-service-field="text"
                                                                value="{{ $serviceItem['text'] ?? '' }}"
                                                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                placeholder="{{ __('Example: UI/UX') }}"
                                                            >
                                                            <p class="mt-2 text-xs text-slate-500">{{ __('This text appears as one item in the services list.') }}</p>
                                                        </div>

                                                        <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                                                            <div
                                                                data-service-icon-preview
                                                                class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand"
                                                            >
                                                                @if (($serviceItem['icon_source'] ?? 'class') === 'media' && ! empty($serviceMediaPreviewUrls))
                                                                    <img src="{{ $serviceMediaPreviewUrls[0] }}" alt="" class="h-full w-full object-contain">
                                                                @elseif (! empty($serviceItem['icon']))
                                                                    <i class="{{ $serviceItem['icon'] }} text-xl leading-none" aria-hidden="true"></i>
                                                                @else
                                                                    <svg width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                                        <path d="M9.75 6.49512L0 12.9903V-7.34329e-05L9.75 6.49512Z" fill="#BA112C" />
                                                                    </svg>
                                                                @endif
                                                            </div>

                                                            <div class="space-y-3">
                                                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                                    <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                                                    <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                                                </div>
                                                                <select
                                                                    name="translations[{{ $code }}][content][services][{{ $serviceIndex }}][icon_source]"
                                                                    data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon_source]"
                                                                    data-service-field="icon_source"
                                                                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                >
                                                                    <option value="class" @selected(($serviceItem['icon_source'] ?? 'class') === 'class')>{{ __('Tabler Icon') }}</option>
                                                                    <option value="media" @selected(($serviceItem['icon_source'] ?? 'class') === 'media')>{{ __('SVG From Media') }}</option>
                                                                </select>

                                                                <div data-service-icon-panel="class" class="space-y-3">
                                                                    <input
                                                                        type="text"
                                                                        name="translations[{{ $code }}][content][services][{{ $serviceIndex }}][icon]"
                                                                        data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon]"
                                                                        data-service-field="icon"
                                                                        value="{{ $serviceItem['icon'] ?? '' }}"
                                                                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                        placeholder="ti ti-layout-grid"
                                                                    >
                                                                    <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                                                        <button
                                                                            type="button"
                                                                            data-open-section-icon-library
                                                                            data-icon-input-selector='[data-service-field="icon"]'
                                                                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                        >
                                                                            <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                                                            <span>{{ __('Choose From Icon Library') }}</span>
                                                                        </button>
                                                                    </div>
                                                                    <p class="text-xs text-slate-500">{{ __('Use the icon library or type a Tabler class manually.') }}</p>
                                                                </div>

                                                                <div data-service-icon-panel="media" class="space-y-2 hidden">
                                                                    <input
                                                                        type="hidden"
                                                                        name="translations[{{ $code }}][content][services][{{ $serviceIndex }}][icon_media]"
                                                                        data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon_media]"
                                                                        data-service-field="icon_media"
                                                                        value="{{ $serviceItem['icon_media'] ?? '' }}"
                                                                    >
                                                                    <button
                                                                        type="button"
                                                                        data-service-icon-media-button
                                                                        class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                    >
                                                                        <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                                                        <span>{{ __('Choose SVG From Media') }}</span>
                                                                    </button>
                                                                    <div data-service-icon-media-preview class="flex flex-wrap gap-2">
                                                                        @foreach ($serviceMediaPreviewUrls as $url)
                                                                            <div class="relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                                                                <img src="{{ $url }}" alt="" class="h-full w-full object-contain p-2">
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                    <p class="text-xs text-slate-500">{{ __('Upload or choose an SVG file from the media library when you need a branded service icon.') }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </article>
                                            @endforeach
                                        </div>

                                        <div data-service-empty class="{{ count($serviceItems) ? 'hidden ' : '' }}mt-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-6 text-center text-sm text-slate-500">
                                            {{ __('No services yet. Add the first one to build the list.') }}
                                        </div>

                                        <template data-service-item-template>
                                            <article data-service-item class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                                                <div class="space-y-3">
                                                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                        <button
                                                            type="button"
                                                            data-service-drag-handle
                                                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                                            aria-label="{{ __('Reorder service') }}"
                                                        >
                                                            <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                                                        </button>

                                                        <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                                            <button
                                                                type="button"
                                                                data-duplicate-service-item
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                                                aria-label="{{ __('Duplicate service') }}"
                                                            >
                                                                <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                                            </button>
                                                            <button
                                                                type="button"
                                                                data-remove-service-item
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                                                aria-label="{{ __('Remove service') }}"
                                                            >
                                                                <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        data-service-toggle
                                                        aria-expanded="false"
                                                        class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right"
                                                    >
                                                        <div class="min-w-0 flex-1">
                                                            <p dir="auto" data-service-item-title class="text-sm font-semibold leading-5 text-slate-900 break-words">{{ __('New Service') }}</p>
                                                            <p dir="auto" data-service-item-summary class="mt-1 text-xs leading-5 text-slate-500 break-words">{{ __('Click to edit this service') }}</p>
                                                        </div>

                                                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                                            <i data-service-toggle-icon class="ti ti-chevron-down text-base leading-none" aria-hidden="true"></i>
                                                        </span>
                                                    </button>
                                                </div>

                                                <div data-service-item-body class="mt-4 hidden space-y-4">
                                                    <div>
                                                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                            <label class="block text-sm font-medium text-slate-700">{{ __('Service Text') }}</label>
                                                            <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                                                        </div>
                                                        <input
                                                            type="text"
                                                            name="translations[{{ $code }}][content][services][__INDEX__][text]"
                                                            data-name-template="translations[{{ $code }}][content][services][__INDEX__][text]"
                                                            data-service-field="text"
                                                            value=""
                                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                            placeholder="{{ __('Example: UI/UX') }}"
                                                        >
                                                        <p class="mt-2 text-xs text-slate-500">{{ __('This text appears as one item in the services list.') }}</p>
                                                    </div>

                                                    <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                                                        <div
                                                            data-service-icon-preview
                                                            class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand"
                                                        >
                                                            <svg width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                                <path d="M9.75 6.49512L0 12.9903V-7.34329e-05L9.75 6.49512Z" fill="#BA112C" />
                                                            </svg>
                                                        </div>

                                                        <div class="space-y-3">
                                                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                                <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                                                <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                                            </div>
                                                            <select
                                                                name="translations[{{ $code }}][content][services][__INDEX__][icon_source]"
                                                                data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon_source]"
                                                                data-service-field="icon_source"
                                                                class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                            >
                                                                <option value="class">{{ __('Tabler Icon') }}</option>
                                                                <option value="media">{{ __('SVG From Media') }}</option>
                                                            </select>

                                                            <div data-service-icon-panel="class" class="space-y-3">
                                                                <input
                                                                    type="text"
                                                                    name="translations[{{ $code }}][content][services][__INDEX__][icon]"
                                                                    data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon]"
                                                                    data-service-field="icon"
                                                                    value=""
                                                                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                    placeholder="ti ti-layout-grid"
                                                                >
                                                                <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                                                    <button
                                                                        type="button"
                                                                        data-open-section-icon-library
                                                                        data-icon-input-selector='[data-service-field="icon"]'
                                                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                    >
                                                                        <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                                                        <span>{{ __('Choose From Icon Library') }}</span>
                                                                    </button>
                                                                </div>
                                                                <p class="text-xs text-slate-500">{{ __('Use the icon library or type a Tabler class manually.') }}</p>
                                                            </div>

                                                            <div data-service-icon-panel="media" class="space-y-2 hidden">
                                                                <input
                                                                    type="hidden"
                                                                    name="translations[{{ $code }}][content][services][__INDEX__][icon_media]"
                                                                    data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon_media]"
                                                                    data-service-field="icon_media"
                                                                    value=""
                                                                >
                                                                <button
                                                                    type="button"
                                                                    data-service-icon-media-button
                                                                    class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                >
                                                                    <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                                                    <span>{{ __('Choose SVG From Media') }}</span>
                                                                </button>
                                                                <div data-service-icon-media-preview class="flex flex-wrap gap-2"></div>
                                                                <p class="text-xs text-slate-500">{{ __('Upload or choose an SVG file from the media library when you need a branded service icon.') }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        </template>

                                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-50/80 px-4 py-3 text-xs text-slate-500 rtl:flex-row-reverse">
                                            <span>{{ __('Each service stays as an individual list item. Drag items to reorder them.') }}</span>
                                            <button
                                                type="button"
                                                data-add-service-item
                                                class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                            >
                                                <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                                                <span>{{ __('Add Service') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showBuildStepsRepeaterField)
                            <div class="lg:col-span-2" data-build-step-repeater data-build-step-item-label="{{ __('Step') }}" data-build-step-item-hint="{{ __('Click to edit this step') }}">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">{{ __('Build Steps') }}</label>
                                        <p class="mt-1 text-xs text-slate-500">{{ __('Create the process steps as individual items with their own icon and highlight state.') }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        data-add-build-step
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Add Step') }}</span>
                                    </button>
                                </div>

                                <div class="mt-3">
                                    <div class="space-y-3" data-build-step-items>
                                        @foreach ($buildStepItems as $stepIndex => $stepItem)
                                            <article data-build-step-item class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                                                <div class="space-y-3">
                                                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                    <button
                                                        type="button"
                                                        data-build-step-drag-handle
                                                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                                        aria-label="{{ __('Reorder step') }}"
                                                    >
                                                        <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                                                    </button>

                                                    <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                                        <button
                                                            type="button"
                                                            data-duplicate-build-step
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                                            aria-label="{{ __('Duplicate step') }}"
                                                        >
                                                            <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            data-remove-build-step
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                                            aria-label="{{ __('Remove step') }}"
                                                        >
                                                            <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <button
                                                    type="button"
                                                    data-build-step-toggle
                                                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                    class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right"
                                                >
                                                    <div class="min-w-0 flex-1">
                                                        <p dir="auto" data-build-step-item-title class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                                            {{ filled($stepItem['title'] ?? '') ? $stepItem['title'] : __('Step') . ' ' . ($stepIndex + 1) }}
                                                        </p>
                                                        <p dir="auto" data-build-step-item-summary class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                                            {{
                                                                ! empty($stepItem['is_accent'])
                                                                    ? __('Highlighted in red')
                                                                    : (
                                                                        ($stepItem['icon_source'] ?? 'class') === 'media'
                                                                            ? (! empty($stepItem['icon_media']) ? __('SVG from media library') : __('Click to edit this step'))
                                                                            : (filled($stepItem['icon'] ?? '') ? __('Tabler icon selected') : __('Click to edit this step'))
                                                                    )
                                                            }}
                                                        </p>
                                                    </div>

                                                    <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                                        <i data-build-step-toggle-icon class="ti ti-chevron-down text-base leading-none {{ $loop->first ? 'rotate-180' : '' }}" aria-hidden="true"></i>
                                                    </span>
                                                </button>
                                                </div>

                                                <div data-build-step-item-body class="mt-4 space-y-4 {{ $loop->first ? '' : 'hidden' }}">
                                                    <div>
                                                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                            <label class="block text-sm font-medium text-slate-700">{{ __('Step Title') }}</label>
                                                            <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                                                        </div>
                                                        <input
                                                            type="text"
                                                            name="translations[{{ $code }}][content][steps][{{ $stepIndex }}][title]"
                                                            data-name-template="translations[{{ $code }}][content][steps][__INDEX__][title]"
                                                            data-build-step-field="title"
                                                            value="{{ $stepItem['title'] ?? '' }}"
                                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                            placeholder="{{ __('Example: Development') }}"
                                                        >
                                                        <p class="mt-2 text-xs text-slate-500">{{ __('This text appears inside the process card in the timeline.') }}</p>
                                                    </div>

                                                    @php
                                                        $stepMediaPreviewUrls = $buildMediaPreviewUrls($stepItem['icon_media'] ?? null);
                                                    @endphp
                                                    <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                                                        <div
                                                            data-build-step-icon-preview
                                                            class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand"
                                                        >
                                                            <i class="{{ $stepItem['icon'] ?: 'ti ti-search' }} text-2xl leading-none" aria-hidden="true"></i>
                                                        </div>

                                                        <div class="space-y-3">
                                                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                                <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                                                <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                                            </div>
                                                            <select
                                                                name="translations[{{ $code }}][content][steps][{{ $stepIndex }}][icon_source]"
                                                                data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon_source]"
                                                                data-build-step-field="icon_source"
                                                                class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                            >
                                                                <option value="class" @selected(($stepItem['icon_source'] ?? 'class') === 'class')>{{ __('Tabler Icon') }}</option>
                                                                <option value="media" @selected(($stepItem['icon_source'] ?? 'class') === 'media')>{{ __('SVG From Media') }}</option>
                                                            </select>

                                                            <div data-build-step-icon-panel="class" class="space-y-3">
                                                                <input
                                                                    type="text"
                                                                    name="translations[{{ $code }}][content][steps][{{ $stepIndex }}][icon]"
                                                                    data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon]"
                                                                    data-build-step-field="icon"
                                                                    value="{{ $stepItem['icon'] ?? '' }}"
                                                                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                    placeholder="ti ti-search"
                                                                >
                                                                <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                                                    <button
                                                                        type="button"
                                                                        data-open-section-icon-library
                                                                        data-icon-input-selector='[data-build-step-field="icon"]'
                                                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                    >
                                                                        <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                                                        <span>{{ __('Choose From Icon Library') }}</span>
                                                                    </button>
                                                                </div>
                                                                <p class="text-xs text-slate-500">{{ __('Use the icon library or type a Tabler class manually.') }}</p>
                                                            </div>

                                                            <div data-build-step-icon-panel="media" class="space-y-2 hidden">
                                                                <input
                                                                    type="hidden"
                                                                    name="translations[{{ $code }}][content][steps][{{ $stepIndex }}][icon_media]"
                                                                    data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon_media]"
                                                                    data-build-step-field="icon_media"
                                                                    value="{{ $stepItem['icon_media'] ?? '' }}"
                                                                >
                                                                <button
                                                                    type="button"
                                                                    data-build-step-icon-media-button
                                                                    class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                >
                                                                    <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                                                    <span>{{ __('Choose SVG From Media') }}</span>
                                                                </button>
                                                                <div data-build-step-icon-media-preview class="flex flex-wrap gap-2">
                                                                    @foreach ($stepMediaPreviewUrls as $url)
                                                                        <div class="relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                                                            <img src="{{ $url }}" alt="" class="h-full w-full object-contain p-2">
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                                <p class="text-xs text-slate-500">{{ __('Upload or choose an SVG file from the media library when you need a branded icon.') }}</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 rtl:flex-row-reverse">
                                                        <input
                                                            type="checkbox"
                                                            name="translations[{{ $code }}][content][steps][{{ $stepIndex }}][is_accent]"
                                                            value="1"
                                                            data-name-template="translations[{{ $code }}][content][steps][__INDEX__][is_accent]"
                                                            data-build-step-field="accent"
                                                            class="rounded border-slate-300"
                                                            {{ ! empty($stepItem['is_accent']) ? 'checked' : '' }}
                                                        >
                                                        <span>{{ __('Highlight this step in red') }}</span>
                                                    </label>
                                                </div>

                                            </article>
                                        @endforeach
                                    </div>

                                    <div data-build-step-empty class="{{ count($buildStepItems) ? 'hidden ' : '' }}mt-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-6 text-center text-sm text-slate-500">
                                        {{ __('No build steps yet. Add the first step to start the process timeline.') }}
                                    </div>

                                    <template data-build-step-item-template>
                                        <article data-build-step-item class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                                            <div class="space-y-3">
                                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                <button
                                                    type="button"
                                                    data-build-step-drag-handle
                                                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                                    aria-label="{{ __('Reorder step') }}"
                                                >
                                                    <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                                                </button>

                                                <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                                    <button
                                                        type="button"
                                                        data-duplicate-build-step
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                                        aria-label="{{ __('Duplicate step') }}"
                                                    >
                                                        <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        data-remove-build-step
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                                        aria-label="{{ __('Remove step') }}"
                                                    >
                                                        <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                data-build-step-toggle
                                                aria-expanded="false"
                                                class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right"
                                            >
                                                <div class="min-w-0 flex-1">
                                                    <p dir="auto" data-build-step-item-title class="text-sm font-semibold leading-5 text-slate-900 break-words">{{ __('New Step') }}</p>
                                                    <p dir="auto" data-build-step-item-summary class="mt-1 text-xs leading-5 text-slate-500 break-words">{{ __('Click to edit this step') }}</p>
                                                </div>

                                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                                    <i data-build-step-toggle-icon class="ti ti-chevron-down text-base leading-none" aria-hidden="true"></i>
                                                </span>
                                            </button>
                                            </div>

                                            <div data-build-step-item-body class="mt-4 hidden space-y-4">
                                                <div>
                                                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                        <label class="block text-sm font-medium text-slate-700">{{ __('Step Title') }}</label>
                                                        <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                                                    </div>
                                                    <input
                                                        type="text"
                                                        name="translations[{{ $code }}][content][steps][__INDEX__][title]"
                                                        data-name-template="translations[{{ $code }}][content][steps][__INDEX__][title]"
                                                        data-build-step-field="title"
                                                        value=""
                                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                        placeholder="{{ __('Example: Development') }}"
                                                    >
                                                    <p class="mt-2 text-xs text-slate-500">{{ __('This text appears inside the process card in the timeline.') }}</p>
                                                </div>

                                                <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                                                    <div
                                                        data-build-step-icon-preview
                                                        class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand"
                                                    >
                                                        <i class="ti ti-search text-2xl leading-none" aria-hidden="true"></i>
                                                    </div>

                                                    <div class="space-y-3">
                                                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                            <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                                            <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                                        </div>
                                                        <select
                                                            name="translations[{{ $code }}][content][steps][__INDEX__][icon_source]"
                                                            data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon_source]"
                                                            data-build-step-field="icon_source"
                                                            class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                        >
                                                            <option value="class">{{ __('Tabler Icon') }}</option>
                                                            <option value="media">{{ __('SVG From Media') }}</option>
                                                        </select>

                                                        <div data-build-step-icon-panel="class" class="space-y-3">
                                                            <input
                                                                type="text"
                                                                name="translations[{{ $code }}][content][steps][__INDEX__][icon]"
                                                                data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon]"
                                                                data-build-step-field="icon"
                                                                value=""
                                                                class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                placeholder="ti ti-search"
                                                            >
                                                            <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                                                <button
                                                                    type="button"
                                                                    data-open-section-icon-library
                                                                    data-icon-input-selector='[data-build-step-field="icon"]'
                                                                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                >
                                                                    <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                                                    <span>{{ __('Choose From Icon Library') }}</span>
                                                                </button>
                                                            </div>
                                                            <p class="text-xs text-slate-500">{{ __('Use the icon library or type a Tabler class manually.') }}</p>
                                                        </div>

                                                        <div data-build-step-icon-panel="media" class="space-y-2 hidden">
                                                            <input
                                                                type="hidden"
                                                                name="translations[{{ $code }}][content][steps][__INDEX__][icon_media]"
                                                                data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon_media]"
                                                                data-build-step-field="icon_media"
                                                                value=""
                                                            >
                                                            <button
                                                                type="button"
                                                                data-build-step-icon-media-button
                                                                class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                            >
                                                                <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                                                <span>{{ __('Choose SVG From Media') }}</span>
                                                            </button>
                                                            <div data-build-step-icon-media-preview class="flex flex-wrap gap-2"></div>
                                                            <p class="text-xs text-slate-500">{{ __('Upload or choose an SVG file from the media library when you need a branded icon.') }}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 rtl:flex-row-reverse">
                                                    <input
                                                        type="checkbox"
                                                        name="translations[{{ $code }}][content][steps][__INDEX__][is_accent]"
                                                        value="1"
                                                        data-name-template="translations[{{ $code }}][content][steps][__INDEX__][is_accent]"
                                                        data-build-step-field="accent"
                                                        class="rounded border-slate-300"
                                                    >
                                                    <span>{{ __('Highlight this step in red') }}</span>
                                                </label>
                                            </div>

                                        </article>
                                    </template>

                                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-50/80 px-4 py-3 text-xs text-slate-500 rtl:flex-row-reverse">
                                        <span>{{ __('Each step keeps its own icon and highlight state. Drag items to reorder them.') }}</span>
                                        <button
                                            type="button"
                                            data-add-build-step
                                            class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                        >
                                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                                            <span>{{ __('Add Step') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showReviewsDatabaseField)
                            <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">{{ __('Testimonials Source') }}</label>
                                        <p class="mt-1 text-sm text-slate-500">{{ __('This section now reads approved testimonial cards directly from the Testimonials module in the dashboard.') }}</p>
                                    </div>
                                    <a
                                        href="{{ route('dashboard.testimonials.index') }}"
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                    >
                                        <i class="ti ti-message-star text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Open Testimonials') }}</span>
                                    </a>
                                </div>

                                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">{{ __('Items Limit') }}</label>
                                        <input
                                            type="number"
                                            min="1"
                                            name="translations[{{ $code }}][content][limit]"
                                            value="{{ $reviewsLimitValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Leave empty to show all approved testimonials') }}"
                                        >
                                        <p class="mt-2 text-xs text-slate-500">{{ __('Optional. Use this to show only the first approved testimonials ordered from the Testimonials module.') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showOurWorkDatabaseField)
                            <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">{{ __('Portfolios Source') }}</label>
                                        <p class="mt-1 text-sm text-slate-500">{{ __('This section reads portfolio cards directly from the Portfolios module in the dashboard.') }}</p>
                                    </div>
                                    <a
                                        href="{{ route('dashboard.portfolios.index') }}"
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                    >
                                        <i class="ti ti-briefcase text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Open Portfolios') }}</span>
                                    </a>
                                </div>

                                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">{{ __('Items Limit') }}</label>
                                        <input
                                            type="number"
                                            min="1"
                                            name="translations[{{ $code }}][content][limit]"
                                            value="{{ $ourWorkLimitValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Leave empty to show all portfolio items') }}"
                                        >
                                        <p class="mt-2 text-xs text-slate-500">{{ __('Optional. Use this to show only the first portfolio items ordered from the Portfolios module.') }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">{{ __('Visit Button Label') }}</label>
                                        <input
                                            type="text"
                                            name="translations[{{ $code }}][content][visit_label]"
                                            value="{{ $ourWorkVisitLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Visit') }}"
                                        >
                                        <p class="mt-2 text-xs text-slate-500">{{ __('This text appears on the card button for every portfolio item in the slider.') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showPrimaryButtonFields && ! $isHeroCampaign)
                            <div class="{{ ($isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase) ? 'lg:col-span-2' : '' }}">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ ($isHeroCampaign || $isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase) ? __('CTA Button Label') : __('Primary Button Label') }}
                                </label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][primary_button][label]"
                                    value="{{ $primaryButtonLabelValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div class="{{ ($isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase) ? 'lg:col-span-2' : '' }}">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ ($isHeroCampaign || $isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase) ? __('CTA Button URL') : __('Primary Button URL') }}
                                </label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][primary_button][url]"
                                    value="{{ $primaryButtonUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            @if ($isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase)
                                <div class="lg:col-span-2">
                                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                        <input
                                            type="checkbox"
                                            name="translations[{{ $code }}][content][primary_button][new_tab]"
                                            value="1"
                                            class="rounded border-slate-300"
                                            {{ $primaryButtonNewTabValue ? 'checked' : '' }}
                                        >
                                        {{ __('Open CTA in a new tab') }}
                                    </label>
                                </div>
                            @endif
                        @endif

                        @if ($showSecondaryButtonFields)
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Secondary Button Label') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][secondary_button][label]"
                                    value="{{ $secondaryButtonLabelValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Secondary Button URL') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][secondary_button][url]"
                                    value="{{ $secondaryButtonUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>
                        @endif

                        @if ($showFeatureRepeaterField)
                            <div
                                class="lg:col-span-2"
                                data-feature-repeater
                                data-feature-item-label="{{ __('Feature') }}"
                                data-feature-item-hint="{{ __('Click to edit this feature') }}"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">{{ __('Campaign Features') }}</label>
                                        <p class="mt-1 text-xs text-slate-500">{{ __('Create structured feature items with their own icon and text.') }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        data-add-feature-item
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Add Feature') }}</span>
                                    </button>
                                </div>

                                <div class="mt-3">
                                    <div class="space-y-3" data-feature-items>
                                        @foreach ($campaignFeatureItems as $featureIndex => $featureItem)
                                            <article data-feature-item class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                                                <div class="space-y-3">
                                                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                        <button
                                                            type="button"
                                                            data-feature-drag-handle
                                                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                                            aria-label="{{ __('Reorder feature') }}"
                                                        >
                                                            <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                                                        </button>

                                                        <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                                            <button
                                                                type="button"
                                                                data-duplicate-feature-item
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                                                aria-label="{{ __('Duplicate feature') }}"
                                                            >
                                                                <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                                            </button>
                                                            <button
                                                                type="button"
                                                                data-remove-feature-item
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                                                aria-label="{{ __('Remove feature') }}"
                                                            >
                                                                <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        data-feature-toggle
                                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                        class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right"
                                                    >
                                                        <div class="min-w-0 flex-1">
                                                            <p dir="auto" data-feature-item-title class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                                                {{ filled($featureItem['text'] ?? '') ? $featureItem['text'] : __('Feature') . ' ' . ($featureIndex + 1) }}
                                                            </p>
                                                            <p dir="auto" data-feature-item-summary class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                                                {{
                                                                    ($featureItem['icon_source'] ?? 'class') === 'svg'
                                                                        ? __('Custom SVG icon')
                                                                        : (($featureItem['icon_source'] ?? 'class') === 'media'
                                                                            ? __('SVG from media library')
                                                                            : (filled($featureItem['icon'] ?? '') ? __('Tabler icon selected') : __('Click to edit this feature')))
                                                                }}
                                                            </p>
                                                        </div>

                                                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                                            <i data-feature-toggle-icon class="ti ti-chevron-down text-base leading-none {{ $loop->first ? 'rotate-180' : '' }}" aria-hidden="true"></i>
                                                        </span>
                                                    </button>
                                                </div>

                                                <div data-feature-item-body class="mt-4 space-y-4 {{ $loop->first ? '' : 'hidden' }}">
                                                    <div>
                                                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                            <label class="block text-sm font-medium text-slate-700">{{ __('Feature Text') }}</label>
                                                            <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                                                        </div>
                                                        <input
                                                            type="text"
                                                            name="translations[{{ $code }}][content][features][{{ $featureIndex }}][text]"
                                                            data-name-template="translations[{{ $code }}][content][features][__INDEX__][text]"
                                                            data-feature-field="text"
                                                            value="{{ $featureItem['text'] ?? '' }}"
                                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                            placeholder="{{ __('Example: 24/7 technical support') }}"
                                                        >
                                                        <p class="mt-2 text-xs text-slate-500">{{ __('This is the text shown next to the icon in the campaign grid.') }}</p>
                                                    </div>

                                                    @php
                                                        $featureMediaPreviewUrls = $buildMediaPreviewUrls($featureItem['icon_media'] ?? null);
                                                    @endphp
                                                    <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                                                        <div
                                                            data-feature-icon-preview
                    class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand"
                                                        >
                                                            @if (! empty($featureItem['icon']))
                                                                <i class="{{ $featureItem['icon'] }} text-2xl leading-none" aria-hidden="true"></i>
                                                            @else
                                                                <i class="ti ti-check text-2xl leading-none" aria-hidden="true"></i>
                                                            @endif
                                                        </div>

                                                        <div class="space-y-3">
                                                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                                <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                                                <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                                            </div>
                                                            <select
                                                                name="translations[{{ $code }}][content][features][{{ $featureIndex }}][icon_source]"
                                                                data-name-template="translations[{{ $code }}][content][features][__INDEX__][icon_source]"
                                                                data-feature-field="icon_source"
                                                                class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                            >
                                                                <option value="class" @selected(($featureItem['icon_source'] ?? 'class') === 'class')>{{ __('Tabler Icon') }}</option>
                                                                <option value="media" @selected(($featureItem['icon_source'] ?? 'class') === 'media')>{{ __('SVG From Media') }}</option>
                                                            </select>

                                                            <div data-feature-icon-panel="class" class="space-y-3">
                                                                <input
                                                                    type="text"
                                                                    name="translations[{{ $code }}][content][features][{{ $featureIndex }}][icon]"
                                                                    data-name-template="translations[{{ $code }}][content][features][__INDEX__][icon]"
                                                                    data-feature-field="icon"
                                                                    value="{{ $featureItem['icon'] ?? '' }}"
                                                                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                    placeholder="ti ti-layout-grid"
                                                                >
                                                                <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                                                    <button
                                                                        type="button"
                                                                        data-open-section-icon-library
                                                                        data-icon-input-selector='[data-feature-field="icon"]'
                                                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                    >
                                                                        <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                                                        <span>{{ __('Choose From Icon Library') }}</span>
                                                                    </button>
                                                                </div>
                                                                <p class="text-xs text-slate-500">{{ __('Use the icon library or type a Tabler class manually.') }}</p>
                                                            </div>

                                                            <div data-feature-icon-panel="media" class="space-y-2 hidden">
                                                                <input
                                                                    type="hidden"
                                                                    name="translations[{{ $code }}][content][features][{{ $featureIndex }}][icon_media]"
                                                                    data-name-template="translations[{{ $code }}][content][features][__INDEX__][icon_media]"
                                                                    data-feature-field="icon_media"
                                                                    value="{{ $featureItem['icon_media'] ?? '' }}"
                                                                >
                                                                <button
                                                                    type="button"
                                                                    data-feature-icon-media-button
                                                                    class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                >
                                                                    <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                                                    <span>{{ __('Choose SVG From Media') }}</span>
                                                                </button>
                                                                <div data-feature-icon-media-preview class="flex flex-wrap gap-2">
                                                                    @foreach ($featureMediaPreviewUrls as $url)
                                                                        <div class="relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                                                            <img src="{{ $url }}" alt="" class="h-full w-full object-contain p-2">
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                                <p class="text-xs text-slate-500">{{ __('Upload or choose an SVG file from the media library when you need a branded icon.') }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>

                                    <div data-feature-empty class="{{ count($campaignFeatureItems) ? 'hidden ' : '' }}mt-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-6 text-center text-sm text-slate-500">
                                        {{ __('No campaign features yet. Add the first one to build the grid.') }}
                                    </div>

                                    <template data-feature-item-template>
                                            <article data-feature-item class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                                                <div class="space-y-3">
                                                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                        <button
                                                            type="button"
                                                            data-feature-drag-handle
                                                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                                            aria-label="{{ __('Reorder feature') }}"
                                                        >
                                                            <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                                                        </button>

                                                        <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                                            <button
                                                                type="button"
                                                                data-duplicate-feature-item
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                                                aria-label="{{ __('Duplicate feature') }}"
                                                            >
                                                                <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                                            </button>
                                                            <button
                                                                type="button"
                                                                data-remove-feature-item
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                                                aria-label="{{ __('Remove feature') }}"
                                                            >
                                                                <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        data-feature-toggle
                                                        aria-expanded="false"
                                                        class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right"
                                                    >
                                                        <div class="min-w-0 flex-1">
                                                            <p dir="auto" data-feature-item-title class="text-sm font-semibold leading-5 text-slate-900 break-words">{{ __('New Feature') }}</p>
                                                            <p dir="auto" data-feature-item-summary class="mt-1 text-xs leading-5 text-slate-500 break-words">{{ __('Click to edit this feature') }}</p>
                                                        </div>

                                                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                                            <i data-feature-toggle-icon class="ti ti-chevron-down text-base leading-none" aria-hidden="true"></i>
                                                        </span>
                                                    </button>
                                                </div>

                                            <div data-feature-item-body class="mt-4 hidden space-y-4">
                                                <div>
                                                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                        <label class="block text-sm font-medium text-slate-700">{{ __('Feature Text') }}</label>
                                                        <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                                                    </div>
                                                    <input
                                                        type="text"
                                                        name="translations[{{ $code }}][content][features][__INDEX__][text]"
                                                        data-name-template="translations[{{ $code }}][content][features][__INDEX__][text]"
                                                        data-feature-field="text"
                                                        value=""
                                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                        placeholder="{{ __('Example: 24/7 technical support') }}"
                                                    >
                                                    <p class="mt-2 text-xs text-slate-500">{{ __('This is the text shown next to the icon in the campaign grid.') }}</p>
                                                </div>

                                                <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                                                    <div
                                                        data-feature-icon-preview
                class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand"
                                                    >
                                                        <i class="ti ti-check text-2xl leading-none" aria-hidden="true"></i>
                                                    </div>

                                                    <div class="space-y-3">
                                                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                            <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                                            <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                                        </div>
                                                        <select
                                                            name="translations[{{ $code }}][content][features][__INDEX__][icon_source]"
                                                            data-name-template="translations[{{ $code }}][content][features][__INDEX__][icon_source]"
                                                            data-feature-field="icon_source"
                                                            class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                        >
                                                            <option value="class">{{ __('Tabler Icon') }}</option>
                                                            <option value="media">{{ __('SVG From Media') }}</option>
                                                        </select>

                                                        <div data-feature-icon-panel="class" class="space-y-3">
                                                            <input
                                                                type="text"
                                                                name="translations[{{ $code }}][content][features][__INDEX__][icon]"
                                                                data-name-template="translations[{{ $code }}][content][features][__INDEX__][icon]"
                                                                data-feature-field="icon"
                                                                value=""
                                                                class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                                placeholder="ti ti-layout-grid"
                                                            >
                                                            <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                                                <button
                                                                    type="button"
                                                                    data-open-section-icon-library
                                                                    data-icon-input-selector='[data-feature-field="icon"]'
                                                                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                                >
                                                                    <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                                                    <span>{{ __('Choose From Icon Library') }}</span>
                                                                </button>
                                                            </div>
                                                            <p class="text-xs text-slate-500">{{ __('Use the icon library or type a Tabler class manually.') }}</p>
                                                        </div>

                                                        <div data-feature-icon-panel="media" class="space-y-2 hidden">
                                                            <input
                                                                type="hidden"
                                                                name="translations[{{ $code }}][content][features][__INDEX__][icon_media]"
                                                                data-name-template="translations[{{ $code }}][content][features][__INDEX__][icon_media]"
                                                                data-feature-field="icon_media"
                                                                value=""
                                                            >
                                                            <button
                                                                type="button"
                                                                data-feature-icon-media-button
                                                                class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse"
                                                            >
                                                                <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                                                <span>{{ __('Choose SVG From Media') }}</span>
                                                            </button>
                                                            <div data-feature-icon-media-preview class="flex flex-wrap gap-2"></div>
                                                            <p class="text-xs text-slate-500">{{ __('Upload or choose an SVG file from the media library when you need a branded icon.') }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    </template>

                                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-50/80 px-4 py-3 text-xs text-slate-500 rtl:flex-row-reverse">
                                        <span>{{ __('Each feature item keeps its own icon and text. Drag items to reorder them.') }}</span>
                                        <button
                                            type="button"
                                            data-add-feature-item
                                            class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                        >
                                            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                                            <span>{{ __('Add Feature') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showFeaturesTextareaField)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ __('Features (each line = one bullet)') }}
                                </label>
                                <textarea
                                    name="translations[{{ $code }}][content][features_textarea]"
                                    rows="5"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >{{ $featuresTextarea }}</textarea>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ __('Each line will be converted to a feature item.') }}
                                </p>
                            </div>
                        @endif

                        @if ($showMobileAppGalleryField)
                            <div class="lg:col-span-2">
                                <div class="rounded-[1.75rem] bg-slate-50/80 p-4 lg:p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">{{ __('Mobile App Gallery') }}</label>
                                            <p class="mt-1 text-xs text-slate-500">{{ __('Choose the three app screenshots exactly in the order they appear in the frontend grid.') }}</p>
                                        </div>

                                        <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 shadow-sm ring-1 ring-slate-200">
                                            {{ __('Shared across all languages') }}
                                        </span>
                                    </div>

                                    <div class="mt-4 space-y-4">
                                        <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">{{ __('Screen 01') }}</p>
                                                    <h4 class="mt-1 text-sm font-semibold text-slate-900">{{ __('First Screenshot') }}</h4>
                                                    <p class="mt-1 text-xs text-slate-500">{{ __('Appears in the first slot of the gallery.') }}</p>
                                                </div>

                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">1</span>
                                            </div>

                                            <x-dashboard.media-picker
                                                :name="'translations['.$code.'][content][image_one]'"
                                                :label="__('First Screenshot')"
                                                :button-text="__('Choose Screenshot')"
                                                :value="$mobileAppImageOneValue"
                                                :preview-urls="$mobileAppImageOnePreviewUrls"
                                                :multiple="false"
                                                store-value="id"
                                                data-shared-media-group="mobile-app-showcase-image-one"
                                            />
                                        </div>

                                        <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">{{ __('Screen 02') }}</p>
                                                    <h4 class="mt-1 text-sm font-semibold text-slate-900">{{ __('Second Screenshot') }}</h4>
                                                    <p class="mt-1 text-xs text-slate-500">{{ __('Appears in the middle slot of the gallery.') }}</p>
                                                </div>

                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">2</span>
                                            </div>

                                            <x-dashboard.media-picker
                                                :name="'translations['.$code.'][content][image_two]'"
                                                :label="__('Second Screenshot')"
                                                :button-text="__('Choose Screenshot')"
                                                :value="$mobileAppImageTwoValue"
                                                :preview-urls="$mobileAppImageTwoPreviewUrls"
                                                :multiple="false"
                                                store-value="id"
                                                data-shared-media-group="mobile-app-showcase-image-two"
                                            />
                                        </div>

                                        <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">{{ __('Screen 03') }}</p>
                                                    <h4 class="mt-1 text-sm font-semibold text-slate-900">{{ __('Third Screenshot') }}</h4>
                                                    <p class="mt-1 text-xs text-slate-500">{{ __('Appears in the third slot of the gallery.') }}</p>
                                                </div>

                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">3</span>
                                            </div>

                                            <x-dashboard.media-picker
                                                :name="'translations['.$code.'][content][image_three]'"
                                                :label="__('Third Screenshot')"
                                                :button-text="__('Choose Screenshot')"
                                                :value="$mobileAppImageThreeValue"
                                                :preview-urls="$mobileAppImageThreePreviewUrls"
                                                :multiple="false"
                                                store-value="id"
                                                data-shared-media-group="mobile-app-showcase-image-three"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showDesignGalleryField)
                            <div class="lg:col-span-2">
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Design Gallery') }}</label>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Choose the six images used in the two-row design portfolio grid.') }}</p>
                                </div>

                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                                    <div>
                                        <x-dashboard.media-picker
                                            :name="'translations['.$code.'][content][image_one]'"
                                            :label="__('Image 1')"
                                            :button-text="__('Choose From Media Library')"
                                            :value="$mobileAppImageOneValue"
                                            :preview-urls="$mobileAppImageOnePreviewUrls"
                                            :multiple="false"
                                            store-value="id"
                                            data-shared-media-group="design-showcase-image-one"
                                        />
                                    </div>

                                    <div>
                                        <x-dashboard.media-picker
                                            :name="'translations['.$code.'][content][image_two]'"
                                            :label="__('Image 2')"
                                            :button-text="__('Choose From Media Library')"
                                            :value="$mobileAppImageTwoValue"
                                            :preview-urls="$mobileAppImageTwoPreviewUrls"
                                            :multiple="false"
                                            store-value="id"
                                            data-shared-media-group="design-showcase-image-two"
                                        />
                                    </div>

                                    <div>
                                        <x-dashboard.media-picker
                                            :name="'translations['.$code.'][content][image_three]'"
                                            :label="__('Image 3')"
                                            :button-text="__('Choose From Media Library')"
                                            :value="$mobileAppImageThreeValue"
                                            :preview-urls="$mobileAppImageThreePreviewUrls"
                                            :multiple="false"
                                            store-value="id"
                                            data-shared-media-group="design-showcase-image-three"
                                        />
                                    </div>

                                    <div>
                                        <x-dashboard.media-picker
                                            :name="'translations['.$code.'][content][image_four]'"
                                            :label="__('Image 4')"
                                            :button-text="__('Choose From Media Library')"
                                            :value="$designImageFourValue"
                                            :preview-urls="$designImageFourPreviewUrls"
                                            :multiple="false"
                                            store-value="id"
                                            data-shared-media-group="design-showcase-image-four"
                                        />
                                    </div>

                                    <div>
                                        <x-dashboard.media-picker
                                            :name="'translations['.$code.'][content][image_five]'"
                                            :label="__('Image 5')"
                                            :button-text="__('Choose From Media Library')"
                                            :value="$designImageFiveValue"
                                            :preview-urls="$designImageFivePreviewUrls"
                                            :multiple="false"
                                            store-value="id"
                                            data-shared-media-group="design-showcase-image-five"
                                        />
                                    </div>

                                    <div>
                                        <x-dashboard.media-picker
                                            :name="'translations['.$code.'][content][image_six]'"
                                            :label="__('Image 6')"
                                            :button-text="__('Choose From Media Library')"
                                            :value="$designImageSixValue"
                                            :preview-urls="$designImageSixPreviewUrls"
                                            :multiple="false"
                                            store-value="id"
                                            data-shared-media-group="design-showcase-image-six"
                                        />
                                    </div>
                                </div>

                                <p class="mt-2 text-xs text-slate-500">{{ __('These gallery images are shared across all languages for this section.') }}</p>
                            </div>
                        @endif

                        @if ($showDigitalMarketingGalleryField)
                            <div class="lg:col-span-2">
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Marketing Gallery') }}</label>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Choose the two images shown in the digital marketing gallery.') }}</p>
                                </div>

                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                    <div>
                                        <x-dashboard.media-picker
                                            :name="'translations['.$code.'][content][image_one]'"
                                            :label="__('Image 1')"
                                            :button-text="__('Choose From Media Library')"
                                            :value="$mobileAppImageOneValue"
                                            :preview-urls="$mobileAppImageOnePreviewUrls"
                                            :multiple="false"
                                            store-value="id"
                                            data-shared-media-group="digital-marketing-showcase-image-one"
                                        />
                                    </div>

                                    <div>
                                        <x-dashboard.media-picker
                                            :name="'translations['.$code.'][content][image_two]'"
                                            :label="__('Image 2')"
                                            :button-text="__('Choose From Media Library')"
                                            :value="$mobileAppImageTwoValue"
                                            :preview-urls="$mobileAppImageTwoPreviewUrls"
                                            :multiple="false"
                                            store-value="id"
                                            data-shared-media-group="digital-marketing-showcase-image-two"
                                        />
                                    </div>
                                </div>

                                <p class="mt-2 text-xs text-slate-500">{{ __('These gallery images are shared across all languages for this section.') }}</p>
                            </div>
                        @endif

                        @if ($showTechStackMediaField)
                            <div class="lg:col-span-2">
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Technology Logos') }}</label>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Choose all stack logos from the media library. They will render in one horizontal strip and stay shared across all languages.') }}</p>
                                </div>

                                <x-dashboard.media-picker
                                    :name="'translations['.$code.'][content][logos]'"
                                    :label="__('Stack Logos')"
                                    :button-text="__('Choose From Media Library')"
                                    :value="$techStackLogosValue"
                                    :preview-urls="$techStackLogoPreviewUrls"
                                    :multiple="true"
                                    store-value="id"
                                    data-shared-media-group="tech-stack-showcase-logos"
                                />
                            </div>
                        @endif

                        @if ($isHeroCampaign)
                            <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700">{{ __('CTA Button') }}</label>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('This button appears below the campaign features and right before the illustration block.') }}</p>
                                </div>

                                <div class="space-y-5">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">{{ __('CTA Button Label') }}</label>
                                        <input
                                            type="text"
                                            name="translations[{{ $code }}][content][primary_button][label]"
                                            value="{{ $primaryButtonLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">{{ __('CTA Button URL') }}</label>
                                        <input
                                            type="text"
                                            name="translations[{{ $code }}][content][primary_button][url]"
                                            value="{{ $primaryButtonUrlValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                        >
                                    </div>
                                </div>

                                <label class="mt-5 inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 rtl:flex-row-reverse">
                                    <input
                                        type="checkbox"
                                        name="translations[{{ $code }}][content][primary_button][new_tab]"
                                        value="1"
                                        class="rounded border-slate-300"
                                        {{ $primaryButtonNewTabValue ? 'checked' : '' }}
                                    >
                                    <span>{{ __('Open CTA in a new tab') }}</span>
                                </label>
                            </div>
                        @endif

                        @if ($showMediaTypeField)
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Media Type') }}</label>
                                <select
                                    name="translations[{{ $code }}][content][media_type]"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                                    <option value="image" {{ $mediaTypeOld === 'image' ? 'selected' : '' }}>Image</option>
                                    <option value="video" {{ $mediaTypeOld === 'video' ? 'selected' : '' }}>Video</option>
                                </select>
                            </div>
                        @endif

                        @if ($showMediaUrlField)
                            @if ($isHeroCampaign || $isProgrammingShowcase)
                                <div class="lg:col-span-2">
                                    <x-dashboard.media-picker
                                        :name="'translations['.$code.'][content][media_url]'"
                                        :label="$isProgrammingShowcase ? __('Featured Image') : __('Illustration')"
                                        :button-text="__('Choose From Media Library')"
                                        :value="$campaignIllustrationValue"
                                        :preview-urls="$campaignIllustrationPreviewUrls"
                                        :multiple="false"
                                        store-value="id"
                                        data-shared-media-group="{{ $isProgrammingShowcase ? 'programming-showcase-image' : 'hero-campaign-illustration' }}"
                                    />
                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ $isProgrammingShowcase
                                            ? __('This featured image is shared across all languages for this section.')
                                            : __('This illustration is shared across all languages for this hero.') }}
                                    </p>
                                </div>
                            @else
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Media URL') }}</label>
                                    <input
                                        type="text"
                                        name="translations[{{ $code }}][content][media_url]"
                                        value="{{ $mediaUrlValue }}"
                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    >
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</form>
