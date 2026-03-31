@php
    use App\Support\Sections\SectionMediaPreviewBuilder;

    $workspaceRoutePrefix = $workspaceRoutePrefix ?? 'dashboard.pages.sections.';
    $workspaceRouteBaseParameters = $workspaceRouteBaseParameters ?? ['page' => $page];
    $workspaceRouteFor =
        $workspaceRouteFor ??
        fn(string $name, array $extra = [], bool $absolute = true) => route(
            $workspaceRoutePrefix . $name,
            array_merge($workspaceRouteBaseParameters, $extra),
            $absolute,
        );
    $formId = $formId ?? 'section-edit-form';
    $formAction = $formAction ?? $workspaceRouteFor('update', ['section' => $section], false);
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
    $editorState = $editorState ?? [];
    $editorDefaultLocale = $editorState['defaultLocale'] ?? app()->getLocale();
    $mediaPreviewBuilder = app(SectionMediaPreviewBuilder::class);
    $workspaceMode = $workspaceMode ?? 'admin';
    $isClientWorkspace = $workspaceMode === 'client';
    $displayOrderLabel = $isClientWorkspace ? __('Block Order') : __('Display Order');
    $displayOrderHelp = $isClientWorkspace ? __('Lower numbers appear earlier on the page.') : null;
    $activeToggleLabel = $isClientWorkspace ? __('Show this block on your website') : __('Active on frontend');
    $contentSectionLabel = $isClientWorkspace ? __('Block Content') : __('Section Content');
    $contentSectionHelp = $isClientWorkspace
        ? __('Update the text, media, and settings for this block in each language.')
        : __('Edit localized content for each language.');
@endphp

<form id="{{ $formId }}" method="{{ strtoupper($formMethod) }}" action="{{ $formAction }}"
    class="{{ $formClass }}" data-section-editor-form data-section-id="{{ $section->id }}"
    data-default-editor-tab="lang-{{ $editorDefaultLocale }}" data-save-action="{{ $saveAction }}"
    @if ($preventNativeSubmit) onsubmit="return false;" @endif>
    @csrf
    @if ($formMethodSpoof)
        @method($formMethodSpoof)
    @endif

    @php
        $feedbackVisible = $viewErrors->any() || filled($feedbackMessage);
        $feedbackClasses =
            $feedbackTone === 'error'
                ? 'border-red-200 bg-red-50 text-red-800'
                : 'border-emerald-200 bg-emerald-50 text-emerald-800';
        $selectedType = $editorState['selectedType'] ?? $section->type;
        $typeFlags = $editorState['typeFlags'] ?? [];
        $fieldFlags = $editorState['flags'] ?? [];
        $isHeroCampaign = (bool) ($typeFlags['isHeroCampaign'] ?? false);
        $isProgrammingShowcase = (bool) ($typeFlags['isProgrammingShowcase'] ?? false);
        $isMobileAppShowcase = (bool) ($typeFlags['isMobileAppShowcase'] ?? false);
        $isDesignShowcase = (bool) ($typeFlags['isDesignShowcase'] ?? false);
        $isDigitalMarketingShowcase = (bool) ($typeFlags['isDigitalMarketingShowcase'] ?? false);
        $isTechStackShowcase = (bool) ($typeFlags['isTechStackShowcase'] ?? false);
        $isReviewsShowcase = (bool) ($typeFlags['isReviewsShowcase'] ?? false);
        $isOurWorkShowcase = (bool) ($typeFlags['isOurWorkShowcase'] ?? false);
        $isHostingPricingShowcase = (bool) ($typeFlags['isHostingPricingShowcase'] ?? false);
        $isDomainsShowcase = (bool) ($typeFlags['isDomainsShowcase'] ?? false);
        $isTemplatesSliderShowcase = (bool) ($typeFlags['isTemplatesSliderShowcase'] ?? false);
        $isTemplatesListingShowcase = (bool) ($typeFlags['isTemplatesListingShowcase'] ?? false);
        $isSiteHeader = (bool) ($typeFlags['isSiteHeader'] ?? false);
        $isSiteFooter = (bool) ($typeFlags['isSiteFooter'] ?? false);
        $usesInternalLabel = (bool) ($editorState['usesInternalLabel'] ?? false);
        $showEyebrowField = (bool) ($fieldFlags['showEyebrowField'] ?? false);
        $showDescriptionField = (bool) ($fieldFlags['showDescriptionField'] ?? false);
        $showFeaturesHeadingField = (bool) ($fieldFlags['showFeaturesHeadingField'] ?? false);
        $showOutputsHeadingField = (bool) ($fieldFlags['showOutputsHeadingField'] ?? false);
        $showOutputsTextareaField = (bool) ($fieldFlags['showOutputsTextareaField'] ?? false);
        $showServicesTextareaField = (bool) ($fieldFlags['showServicesTextareaField'] ?? false);
        $showBrandFields = (bool) ($fieldFlags['showBrandFields'] ?? false);
        $showPrimaryButtonFields = (bool) ($fieldFlags['showPrimaryButtonFields'] ?? false);
        $showSecondaryButtonFields = (bool) ($fieldFlags['showSecondaryButtonFields'] ?? false);
        $showFeatureRepeaterField = (bool) ($fieldFlags['showFeatureRepeaterField'] ?? false);
        $showBuildStepsRepeaterField = (bool) ($fieldFlags['showBuildStepsRepeaterField'] ?? false);
        $showReviewsDatabaseField = (bool) ($fieldFlags['showReviewsDatabaseField'] ?? false);
        $showOurWorkDatabaseField = (bool) ($fieldFlags['showOurWorkDatabaseField'] ?? false);
        $showHostingPricingCategoriesField = (bool) ($fieldFlags['showHostingPricingCategoriesField'] ?? false);
        $showHostingPricingPlansField = (bool) ($fieldFlags['showHostingPricingPlansField'] ?? false);
        $showHostingPricingDatabaseField = (bool) ($fieldFlags['showHostingPricingDatabaseField'] ?? false);
        $showTemplatesSliderDatabaseField = (bool) ($fieldFlags['showTemplatesSliderDatabaseField'] ?? false);
        $showTemplatesListingDatabaseField = (bool) ($fieldFlags['showTemplatesListingDatabaseField'] ?? false);
        $showFeaturesTextareaField = (bool) ($fieldFlags['showFeaturesTextareaField'] ?? false);
        $showMobileAppGalleryField = (bool) ($fieldFlags['showMobileAppGalleryField'] ?? false);
        $showDesignGalleryField = (bool) ($fieldFlags['showDesignGalleryField'] ?? false);
        $showDigitalMarketingGalleryField = (bool) ($fieldFlags['showDigitalMarketingGalleryField'] ?? false);
        $showTechStackMediaField = (bool) ($fieldFlags['showTechStackMediaField'] ?? false);
        $showMediaTypeField = (bool) ($fieldFlags['showMediaTypeField'] ?? false);
        $showMediaUrlField = (bool) ($fieldFlags['showMediaUrlField'] ?? false);
        $showSubtitleField = (bool) ($fieldFlags['showSubtitleField'] ?? false);
        $showMainTitleField = (bool) ($fieldFlags['showMainTitleField'] ?? false);
        $showDomainsSearchHeadingField = (bool) ($fieldFlags['showDomainsSearchHeadingField'] ?? false);
        $showDomainsPlaceholderField = (bool) ($fieldFlags['showDomainsPlaceholderField'] ?? false);
        $showFaqItemsTextareaField = (bool) ($fieldFlags['showFaqItemsTextareaField'] ?? false);
        $showReviewRepeaterField = (bool) ($fieldFlags['showReviewRepeaterField'] ?? false);
        $showSiteFooterLinksTextareaField = (bool) ($fieldFlags['showSiteFooterLinksTextareaField'] ?? false);
        $showSiteFooterSocialFields = (bool) ($fieldFlags['showSiteFooterSocialFields'] ?? false);
        $selectedFooterVariant = old('variant', $section->variant ?: 'simple_social');
        $hostingPricingAvailableCategories = $editorState['hostingPricingAvailableCategories'] ?? collect();
    @endphp

    <div data-section-editor-feedback
        class="hidden rounded-2xl border px-4 py-3 text-sm {{ $feedbackVisible ? $feedbackClasses : 'border-slate-200 bg-slate-50 text-slate-600' }}">
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
            <input type="hidden" name="type" value="{{ $selectedType }}">

            <div class="{{ $settingsGridClass }}">
                @if ($isSiteFooter)
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('Footer Layout') }}</label>
                        <select name="variant"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            <option value="simple_social"
                                {{ $selectedFooterVariant === 'simple_social' ? 'selected' : '' }}>
                                {{ __('Social icons + copyright') }}
                            </option>
                            <option value="links_social"
                                {{ $selectedFooterVariant === 'links_social' ? 'selected' : '' }}>
                                {{ __('Links + social icons + copyright') }}
                            </option>
                        </select>
                        <p class="mt-2 text-xs text-slate-500">
                            {{ __('Choose the footer style the client wants. The links field below is used only in the links layout.') }}
                        </p>
                    </div>
                @else
                    <input type="hidden" name="variant" value="{{ old('variant', $section->variant) }}">
                @endif

                @if ($showOrderField)
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ $displayOrderLabel }}</label>
                        <input type="number" name="order" value="{{ old('order', $section->order ?? 1) }}"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                        @if (filled($displayOrderHelp))
                            <p class="mt-2 text-xs text-slate-500">{{ $displayOrderHelp }}</p>
                        @endif
                    </div>
                @endif

                <div class="flex items-center">
                    <label
                        class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
                            {{ old('is_active', $section->is_active) ? 'checked' : '' }}>
                        {{ $activeToggleLabel }}
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="{{ $surfaceClass }}">
        <div class="{{ $sectionHeaderClass }}">
            <h2 class="text-lg font-semibold text-slate-900">{{ $contentSectionLabel }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $contentSectionHelp }}</p>
        </div>

        <div class="{{ $sectionBodyClass }}">
            <div class="mb-5 border-b border-slate-200">
                <nav class="-mb-px flex flex-wrap gap-2" aria-label="Language tabs">
                    @foreach ($languages as $index => $language)
                        @php
                            $active = $language->code === $editorDefaultLocale;
                        @endphp
                        <button type="button"
                            class="rounded-t-2xl border-b-2 px-4 py-2 text-sm font-medium transition {{ $active ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' }}"
                            data-editor-tab-button data-tab="lang-{{ $language->code }}">
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
                    $localeScalarValues = $editorState['localeScalarValues'][$code] ?? [];
                    $localeViewData = $editorState['localeViewData'][$code] ?? [];

                    $campaignFeatureItems = $editorState['localeCampaignFeatureItems'][$code] ?? [];
                    $buildStepItems = $editorState['localeBuildStepItems'][$code] ?? [];
                    $outputItems = $editorState['localeOutputItems'][$code] ?? [];
                    $serviceItems = $editorState['localeServiceItems'][$code] ?? [];
                    $pricingCategoryItems = $editorState['localePricingCategoryItems'][$code] ?? [];
                    $pricingPlanItems = $editorState['localePricingPlanItems'][$code] ?? [];
                    $featuresTextarea = $localeViewData['featuresTextarea'] ?? '';
                    $outputsTextarea = $localeViewData['outputsTextarea'] ?? '';
                    $servicesTextarea = $localeViewData['servicesTextarea'] ?? '';
                    $faqItemsTextarea = $localeViewData['faqItemsTextarea'] ?? '';
                    $reviewItems = $localeViewData['reviewItems'] ?? [];

                    $sectionTitleValue = $localeScalarValues['sectionTitleValue'] ?? '';
                    $eyebrowValue = $localeScalarValues['eyebrowValue'] ?? '';
                    $heroTitleValue = $localeScalarValues['heroTitleValue'] ?? '';
                    $brandPrefixValue = $localeScalarValues['brandPrefixValue'] ?? '';
                    $brandSuffixValue = $localeScalarValues['brandSuffixValue'] ?? '';
                    $subtitleValue = $localeScalarValues['subtitleValue'] ?? '';
                    $descriptionValue = $localeScalarValues['descriptionValue'] ?? '';
                    $hostingPricingButtonLabelValue = $localeScalarValues['hostingPricingButtonLabelValue'] ?? '';
                    $domainsSearchHeadingValue = $localeScalarValues['domainsSearchHeadingValue'] ?? '';
                    $domainsInputPlaceholderValue = $localeScalarValues['domainsInputPlaceholderValue'] ?? '';
                    $templatesSliderBuyLabelValue = $localeScalarValues['templatesSliderBuyLabelValue'] ?? '';
                    $templatesSliderPreviewLabelValue = $localeScalarValues['templatesSliderPreviewLabelValue'] ?? '';
                    $templatesSliderLimitValue = $localeScalarValues['templatesSliderLimitValue'] ?? '';
                    $templatesListingBreadcrumbLabelValue =
                        $localeScalarValues['templatesListingBreadcrumbLabelValue'] ?? '';
                    $templatesListingAllCategoriesLabelValue =
                        $localeScalarValues['templatesListingAllCategoriesLabelValue'] ?? '';
                    $templatesListingTypeLabelValue = $localeScalarValues['templatesListingTypeLabelValue'] ?? '';
                    $templatesListingBestSellersLabelValue =
                        $localeScalarValues['templatesListingBestSellersLabelValue'] ?? '';
                    $templatesListingPriceLabelValue = $localeScalarValues['templatesListingPriceLabelValue'] ?? '';
                    $templatesListingBuyLabelValue = $localeScalarValues['templatesListingBuyLabelValue'] ?? '';
                    $templatesListingPreviewLabelValue = $localeScalarValues['templatesListingPreviewLabelValue'] ?? '';
                    $templatesListingItemsPerPageValue = $localeScalarValues['templatesListingItemsPerPageValue'] ?? '';
                    $hostingPricingVisibleCategoryIds = collect(
                        old("translations.$code.content.visible_category_ids", $content['visible_category_ids'] ?? []),
                    )
                        ->map(function ($id) {
                            if (is_array($id)) {
                                return null;
                            }

                            $id = is_string($id) ? trim($id) : $id;

                            return is_numeric($id) ? (int) $id : null;
                        })
                        ->filter(fn($id) => $id && $id > 0)
                        ->values()
                        ->all();
                    $featuresHeadingValue = $localeScalarValues['featuresHeadingValue'] ?? '';
                    $outputsHeadingValue = $localeScalarValues['outputsHeadingValue'] ?? '';
                    $primaryButtonLabelValue = $localeScalarValues['primaryButtonLabelValue'] ?? '';
                    $primaryButtonUrlValue = $localeScalarValues['primaryButtonUrlValue'] ?? '';
                    $primaryButtonNewTabValue = (bool) ($localeScalarValues['primaryButtonNewTabValue'] ?? false);
                    $secondaryButtonLabelValue = $localeScalarValues['secondaryButtonLabelValue'] ?? '';
                    $secondaryButtonUrlValue = $localeScalarValues['secondaryButtonUrlValue'] ?? '';
                    $reviewsLimitValue = $localeScalarValues['reviewsLimitValue'] ?? '';
                    $ourWorkLimitValue = $localeScalarValues['ourWorkLimitValue'] ?? '';
                    $ourWorkVisitLabelValue = $localeScalarValues['ourWorkVisitLabelValue'] ?? '';
                    $footerCopyrightValue = $localeScalarValues['footerCopyrightValue'] ?? '';
                    $footerLinkItems = $localeScalarValues['footerLinkItems'] ?? [];
                    $footerFacebookUrlValue = $localeScalarValues['footerFacebookUrlValue'] ?? '';
                    $footerInstagramUrlValue = $localeScalarValues['footerInstagramUrlValue'] ?? '';
                    $footerXUrlValue = $localeScalarValues['footerXUrlValue'] ?? '';
                    $footerGithubUrlValue = $localeScalarValues['footerGithubUrlValue'] ?? '';
                    $footerYoutubeUrlValue = $localeScalarValues['footerYoutubeUrlValue'] ?? '';
                    $headerLogoValue = $localeScalarValues['headerLogoValue'] ?? null;
                    $pricingCategoryDatalistId = 'pricing-category-keys-' . $section->id . '-' . $code;
                    $mediaUrlValue = $localeScalarValues['mediaUrlValue'] ?? '';
                    $mediaTypeOld = $localeScalarValues['mediaTypeOld'] ?? 'image';
                    $campaignIllustrationValue = $localeScalarValues['campaignIllustrationValue'] ?? null;
                    $mobileAppImageOneValue = $localeScalarValues['mobileAppImageOneValue'] ?? null;
                    $mobileAppImageTwoValue = $localeScalarValues['mobileAppImageTwoValue'] ?? null;
                    $mobileAppImageThreeValue = $localeScalarValues['mobileAppImageThreeValue'] ?? null;
                    $designImageFourValue = $localeScalarValues['designImageFourValue'] ?? null;
                    $designImageFiveValue = $localeScalarValues['designImageFiveValue'] ?? null;
                    $designImageSixValue = $localeScalarValues['designImageSixValue'] ?? null;
                    $techStackLogosValue = $localeScalarValues['techStackLogosValue'] ?? [];
                    $techStackLogosValueForComponent = $localeScalarValues['techStackLogosValueForComponent'] ?? '';
                    $campaignIllustrationPreviewUrls = $localeViewData['campaignIllustrationPreviewUrls'] ?? [];
                    $headerLogoPreviewUrls = $localeViewData['headerLogoPreviewUrls'] ?? [];
                    $mobileAppImageOnePreviewUrls = $localeViewData['mobileAppImageOnePreviewUrls'] ?? [];
                    $mobileAppImageTwoPreviewUrls = $localeViewData['mobileAppImageTwoPreviewUrls'] ?? [];
                    $mobileAppImageThreePreviewUrls = $localeViewData['mobileAppImageThreePreviewUrls'] ?? [];
                    $designImageFourPreviewUrls = $localeViewData['designImageFourPreviewUrls'] ?? [];
                    $designImageFivePreviewUrls = $localeViewData['designImageFivePreviewUrls'] ?? [];
                    $designImageSixPreviewUrls = $localeViewData['designImageSixPreviewUrls'] ?? [];
                    $techStackLogoPreviewUrls = $localeViewData['techStackLogoPreviewUrls'] ?? [];
                @endphp

                <div id="lang-{{ $code }}" data-editor-tab-panel
                    class="{{ $code === $editorDefaultLocale ? '' : 'hidden' }}">
                    <input type="hidden" name="translations[{{ $code }}][locale]"
                        value="{{ $code }}">

                    <div class="{{ $contentGridClass }}">
                        @if ($usesInternalLabel)
                            <input type="hidden" name="translations[{{ $code }}][title]"
                                value="{{ $sectionTitleValue }}">
                        @else
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ __('Section Title') }} ({{ $code }})
                                </label>
                                <input type="text" name="translations[{{ $code }}][title]"
                                    value="{{ $sectionTitleValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>
                        @endif

                        @if ($selectedType === 'how_we_build')
                            <div
                                class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                {{ __('This section uses a heading, a short subtitle, and a build-process timeline made of editable step cards.') }}
                            </div>
                        @endif

                        @if ($showEyebrowField)
                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Eyebrow') }}</label>
                                <input type="text" name="translations[{{ $code }}][content][eyebrow]"
                                    value="{{ $eyebrowValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>
                        @endif

                        @if ($showBrandFields)
                            <div
                                class="{{ $isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase || $isReviewsShowcase || $isOurWorkShowcase || $isDomainsShowcase || $isTemplatesSliderShowcase || $isTemplatesListingShowcase ? 'lg:col-span-2' : '' }}">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Brand Prefix') }}</label>
                                <input type="text" name="translations[{{ $code }}][content][brand_prefix]"
                                    value="{{ $brandPrefixValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="PAL">
                            </div>

                            <div
                                class="{{ $isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase || $isReviewsShowcase || $isOurWorkShowcase || $isDomainsShowcase || $isTemplatesSliderShowcase || $isTemplatesListingShowcase ? 'lg:col-span-2' : '' }}">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Brand Suffix') }}</label>
                                <input type="text" name="translations[{{ $code }}][content][brand_suffix]"
                                    value="{{ $brandSuffixValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="GOALS">
                            </div>
                        @endif

                        @if ($showMainTitleField)
                            <div
                                class="{{ $isHeroCampaign || $isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase || $isReviewsShowcase || $isOurWorkShowcase || $isDomainsShowcase || $isTemplatesSliderShowcase || $isTemplatesListingShowcase || $isSiteHeader ? 'lg:col-span-2' : '' }}">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $isSiteHeader || $isSiteFooter
                                        ? __('Brand Name')
                                        : ($isHeroCampaign
                                            ? __('Main Title - Line 1')
                                            : ($isProgrammingShowcase ||
                                            $isMobileAppShowcase ||
                                            $isDesignShowcase ||
                                            $isDigitalMarketingShowcase ||
                                            $isReviewsShowcase ||
                                            $isOurWorkShowcase ||
                                            $isDomainsShowcase ||
                                            $isTemplatesSliderShowcase ||
                                            $isTemplatesListingShowcase
                                                ? __('Section Title')
                                                : __('Main Title'))) }}
                                </label>
                                <input type="text" name="translations[{{ $code }}][content][title]"
                                    value="{{ $heroTitleValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    @if ($isReviewsShowcase) placeholder="{{ __('REVIEWS') }}" @endif
                                    @if ($isOurWorkShowcase) placeholder="{{ __('OUR WORK') }}" @endif>
                            </div>
                        @endif

                        @if ($isSiteHeader)
                            <div class="lg:col-span-2">
                                <x-dashboard.media-picker :name="'translations[' . $code . '][content][logo]'" :label="__('Brand Image')" :button-text="__('Choose From Media Library')"
                                    :value="$headerLogoValue" :preview-urls="$headerLogoPreviewUrls" :multiple="false" store-value="id" />
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ __('Upload a brand image from your media library. If you leave this empty, the header will use the first letter of the brand name.') }}
                                </p>
                            </div>
                        @endif

                        @if ($showDomainsSearchHeadingField)
                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Search Box Title') }}</label>
                                <input type="text" name="translations[{{ $code }}][content][search_heading]"
                                    value="{{ $domainsSearchHeadingValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="{{ __('Find your perfect Domain name') }}">
                            </div>
                        @endif

                        @if ($showSubtitleField)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $isHeroCampaign ? __('Main Title - Line 2') : __('Subtitle') }}
                                </label>
                                <textarea name="translations[{{ $code }}][content][subtitle]" rows="3"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">{{ $subtitleValue }}</textarea>
                            </div>
                        @endif

                        @if ($showDescriptionField)
                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ $isDomainsShowcase ? __('Search Box Description') : __('Description') }}</label>
                                <textarea name="translations[{{ $code }}][content][description]" rows="4"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">{{ $descriptionValue }}</textarea>
                            </div>
                        @endif

                        @if ($showDomainsPlaceholderField)
                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Input Placeholder') }}</label>
                                <input type="text"
                                    name="translations[{{ $code }}][content][input_placeholder]"
                                    value="{{ $domainsInputPlaceholderValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="{{ __('enter your domain here...') }}">
                            </div>
                        @endif

                        @if ($showHostingPricingDatabaseField)
                            <div
                                class="lg:col-span-2 rounded-[1.75rem] bg-slate-50/80 px-5 py-4 text-sm text-slate-600">
                                <p class="font-medium text-slate-900">{{ __('Plans Grid') }}</p>
                                <p class="mt-1 leading-6">
                                    {{ __('Tabs and plan cards are loaded automatically from the Plans and Plan Categories modules. Manage the actual plans there, and use this section only for the heading and shared CTA label.') }}
                                </p>
                            </div>

                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('CTA Button Label') }}</label>
                                <input type="text" name="translations[{{ $code }}][content][button_label]"
                                    value="{{ $hostingPricingButtonLabelValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="{{ __('Choose Now') }}">
                            </div>

                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Visible Categories') }}</label>
                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    {{ __('Choose only the plan categories you want to show in this section. Leave all unchecked to show every active category.') }}
                                </p>

                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @forelse ($hostingPricingAvailableCategories as $availableCategory)
                                        @php
                                            $availableCategoryTranslation =
                                                $availableCategory->translation($code) ??
                                                $availableCategory->translations->first();
                                            $availableCategoryLabel = trim(
                                                (string) ($availableCategoryTranslation?->title ??
                                                    __('Category') . ' #' . $availableCategory->id),
                                            );
                                            $availableCategoryKey = trim(
                                                (string) ($availableCategoryTranslation?->slug ??
                                                    'category-' . $availableCategory->id),
                                            );
                                            $isVisibleCategoryChecked = in_array(
                                                (int) $availableCategory->id,
                                                $hostingPricingVisibleCategoryIds,
                                                true,
                                            );
                                        @endphp

                                        <label
                                            class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 transition hover:border-slate-300">
                                            <input type="checkbox"
                                                name="translations[{{ $code }}][content][visible_category_ids][]"
                                                value="{{ $availableCategory->id }}" @checked($isVisibleCategoryChecked)
                                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                                            <span class="min-w-0 flex-1">
                                                <span dir="auto"
                                                    class="block font-medium text-slate-900 break-words">{{ $availableCategoryLabel }}</span>
                                                <span dir="ltr"
                                                    class="mt-1 block text-xs text-slate-500 break-all">{{ $availableCategoryKey }}</span>
                                            </span>
                                        </label>
                                    @empty
                                        <div
                                            class="sm:col-span-2 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                            {{ __('No active plan categories found.') }}
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endif

                        @if ($showHostingPricingCategoriesField)
                            @include(
                                'dashboard.pages.sections.partials.repeaters.pricing-categories-repeater',
                                [
                                    'code' => $code,
                                    'pricingCategoryItems' => $pricingCategoryItems,
                                    'pricingCategoryDatalistId' => $pricingCategoryDatalistId,
                                ]
                            )
                        @endif

                        @if ($showHostingPricingPlansField)
                            @include('dashboard.pages.sections.partials.repeaters.pricing-plans-repeater', [
                                'code' => $code,
                                'pricingPlanItems' => $pricingPlanItems,
                                'pricingCategoryDatalistId' => $pricingCategoryDatalistId,
                            ])
                        @endif

                        @if ($showOutputsHeadingField)
                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Outputs Heading') }}</label>
                                <input type="text"
                                    name="translations[{{ $code }}][content][outputs_heading]"
                                    value="{{ $outputsHeadingValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>
                        @endif

                        @if ($showFeaturesHeadingField)
                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Features Heading') }}</label>
                                <input type="text"
                                    name="translations[{{ $code }}][content][features_heading]"
                                    value="{{ $featuresHeadingValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>
                        @endif

                        @if ($showOutputsTextareaField)
                            @include('dashboard.pages.sections.partials.repeaters.outputs-repeater', [
                                'code' => $code,
                                'outputItems' => $outputItems,
                                'mediaPreviewBuilder' => $mediaPreviewBuilder,
                            ])
                        @endif

                        @if ($showServicesTextareaField)
                            @include('dashboard.pages.sections.partials.repeaters.services-repeater', [
                                'code' => $code,
                                'serviceItems' => $serviceItems,
                                'mediaPreviewBuilder' => $mediaPreviewBuilder,
                            ])
                        @endif

                        @if ($showBuildStepsRepeaterField)
                            @include('dashboard.pages.sections.partials.repeaters.build-steps-repeater', [
                                'code' => $code,
                                'buildStepItems' => $buildStepItems,
                                'mediaPreviewBuilder' => $mediaPreviewBuilder,
                            ])
                        @endif

                        @if ($showTemplatesSliderDatabaseField)
                            <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Templates Source') }}</label>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ __('This section loads template cards automatically from the Templates module. Use the fields below only to control the section heading, card button labels, and item limit.') }}
                                        </p>
                                    </div>
                                    <a href="{{ route('dashboard.templates.index') }}"
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                        <i class="ti ti-layout-grid text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Open Templates') }}</span>
                                    </a>
                                </div>

                                <div class="mt-5 grid grid-cols-1 gap-5">
                                    <div class="lg:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Buy Button Label') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][buy_label]"
                                            value="{{ $templatesSliderBuyLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Buy Now') }}">
                                        <p class="mt-2 text-xs text-slate-500">
                                            {{ __('This label appears on the main CTA button in every template card.') }}
                                        </p>
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Preview Button Label') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][preview_label]"
                                            value="{{ $templatesSliderPreviewLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Live Preview') }}">
                                        <p class="mt-2 text-xs text-slate-500">
                                            {{ __('This label appears on the secondary button in every template card.') }}
                                        </p>
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Items Limit') }}</label>
                                        <input type="number" min="1"
                                            name="translations[{{ $code }}][content][limit]"
                                            value="{{ $templatesSliderLimitValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="6">
                                        <p class="mt-2 text-xs text-slate-500">
                                            {{ __('Optional. Leave this empty to use the default number of template cards for the slider.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showTemplatesListingDatabaseField)
                            <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Templates Source') }}</label>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ __('This section builds the templates archive from the Templates module automatically. Use these fields only for the breadcrumb text, filter labels, card button labels, and items shown per page.') }}
                                        </p>
                                    </div>
                                    <a href="{{ route('dashboard.templates.index') }}"
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                        <i class="ti ti-layout-grid text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Open Templates') }}</span>
                                    </a>
                                </div>

                                <div class="mt-5 grid grid-cols-1 gap-5">
                                    <div class="lg:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Breadcrumb Label') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][breadcrumb_label]"
                                            value="{{ $templatesListingBreadcrumbLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Templates') }}">
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('All Categories Label') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][all_categories_label]"
                                            value="{{ $templatesListingAllCategoriesLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('All Hosting') }}">
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Type Filter Label') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][type_label]"
                                            value="{{ $templatesListingTypeLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Type') }}">
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Best Sellers Filter Label') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][best_sellers_label]"
                                            value="{{ $templatesListingBestSellersLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Best Sellers') }}">
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Price Filter Label') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][price_label]"
                                            value="{{ $templatesListingPriceLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Price') }}">
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Buy Button Label') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][buy_label]"
                                            value="{{ $templatesListingBuyLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Buy Now') }}">
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Preview Button Label') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][preview_label]"
                                            value="{{ $templatesListingPreviewLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Live Preview') }}">
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Items Per Page') }}</label>
                                        <input type="number" min="1"
                                            name="translations[{{ $code }}][content][items_per_page]"
                                            value="{{ $templatesListingItemsPerPageValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="12">
                                        <p class="mt-2 text-xs text-slate-500">
                                            {{ __('This controls how many template cards appear before the pagination switches to the next page.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showReviewsDatabaseField)
                            <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Testimonials Source') }}</label>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ __('This section now reads approved testimonial cards directly from the Testimonials module in the dashboard.') }}
                                        </p>
                                    </div>
                                    <a href="{{ route('dashboard.testimonials.index') }}"
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                        <i class="ti ti-message-star text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Open Testimonials') }}</span>
                                    </a>
                                </div>

                                <div class="mt-5">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Items Limit') }}</label>
                                        <input type="number" min="1"
                                            name="translations[{{ $code }}][content][limit]"
                                            value="{{ $reviewsLimitValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="6">
                                        <p class="mt-2 text-xs text-slate-500">
                                            {{ __('Optional. Leave this empty to show all approved testimonials from the Testimonials module.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showOurWorkDatabaseField)
                            <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Portfolios Source') }}</label>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ __('This section reads portfolio cards directly from the Portfolios module in the dashboard.') }}
                                        </p>
                                    </div>
                                    <a href="{{ route('dashboard.portfolios.index') }}"
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                        <i class="ti ti-briefcase text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Open Portfolios') }}</span>
                                    </a>
                                </div>

                                <div class="mt-5 space-y-4">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Items Limit') }}</label>
                                        <input type="number" min="1"
                                            name="translations[{{ $code }}][content][limit]"
                                            value="{{ $ourWorkLimitValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="6">
                                        <p class="mt-2 text-xs text-slate-500">
                                            {{ __('Optional. Use this to show only the first portfolio items ordered from the Portfolios module.') }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Visit Button Label') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][visit_label]"
                                            value="{{ $ourWorkVisitLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="{{ __('Visit') }}">
                                        <p class="mt-2 text-xs text-slate-500">
                                            {{ __('This text appears on the card button for every portfolio item in the slider.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showPrimaryButtonFields && !$isHeroCampaign)
                            <div
                                class="{{ $isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase || $isDomainsShowcase || $isSiteHeader ? 'lg:col-span-2' : '' }}">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $isSiteHeader
                                        ? __('Header Button Label')
                                        : ($isDomainsShowcase
                                            ? __('Search Button Label')
                                            : ($isHeroCampaign ||
                                            $isProgrammingShowcase ||
                                            $isMobileAppShowcase ||
                                            $isDesignShowcase ||
                                            $isDigitalMarketingShowcase
                                                ? __('CTA Button Label')
                                                : __('Primary Button Label'))) }}
                                </label>
                                <input type="text"
                                    name="translations[{{ $code }}][content][primary_button][label]"
                                    value="{{ $primaryButtonLabelValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>

                            <div
                                class="{{ $isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase || $isDomainsShowcase || $isSiteHeader ? 'lg:col-span-2' : '' }}">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $isSiteHeader
                                        ? __('Header Button URL')
                                        : ($isDomainsShowcase
                                            ? __('Search Page URL')
                                            : ($isHeroCampaign ||
                                            $isProgrammingShowcase ||
                                            $isMobileAppShowcase ||
                                            $isDesignShowcase ||
                                            $isDigitalMarketingShowcase
                                                ? __('CTA Button URL')
                                                : __('Primary Button URL'))) }}
                                </label>
                                <input type="text"
                                    name="translations[{{ $code }}][content][primary_button][url]"
                                    value="{{ $primaryButtonUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>

                            @if ($isProgrammingShowcase || $isMobileAppShowcase || $isDesignShowcase || $isDigitalMarketingShowcase || $isSiteHeader)
                                <div class="lg:col-span-2">
                                    <label
                                        class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                        <input type="checkbox"
                                            name="translations[{{ $code }}][content][primary_button][new_tab]"
                                            value="1" class="rounded border-slate-300"
                                            {{ $primaryButtonNewTabValue ? 'checked' : '' }}>
                                        {{ $isSiteHeader ? __('Open header button in a new tab') : __('Open CTA in a new tab') }}
                                    </label>
                                </div>
                            @endif
                        @endif

                        @if ($isSiteHeader)
                            <div
                                class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                {{ __('Navigation links are pulled automatically from your active site pages. Edit the button here if you want a highlighted action on the right side of the header.') }}
                            </div>
                        @endif

                        @if ($showSiteFooterLinksTextareaField)
                            <div class="lg:col-span-2" data-footer-link-repeater
                                data-footer-link-item-label="{{ __('Link') }}"
                                data-footer-link-item-hint="{{ __('Add the label and destination for this footer link.') }}">
                                <div class="mb-4 flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Footer Links') }}</label>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ __('This is used in the links footer layout. Add each link with a label and URL.') }}
                                        </p>
                                    </div>
                                    <button type="button" data-add-footer-link
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 rtl:flex-row-reverse">
                                        <span class="text-sm leading-none">+</span>
                                        <span>{{ __('Add link') }}</span>
                                    </button>
                                </div>

                                <div class="space-y-3" data-footer-link-items>
                                    @foreach ($footerLinkItems as $footerLinkIndex => $footerLinkItem)
                                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                                            data-footer-link-item>
                                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                                <div
                                                    class="inline-flex items-center gap-2 text-xs font-medium uppercase tracking-[0.2em] text-slate-400 rtl:flex-row-reverse">
                                                    <span data-footer-link-title>{{ __('Link') }}
                                                        {{ $footerLinkIndex + 1 }}</span>
                                                </div>

                                                <div class="flex items-center gap-2 rtl:flex-row-reverse">
                                                    <button type="button" data-duplicate-footer-link
                                                        class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                                        {{ __('Duplicate') }}
                                                    </button>
                                                    <button type="button" data-remove-footer-link
                                                        class="rounded-xl border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100">
                                                        {{ __('Remove') }}
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700">{{ __('Label') }}</label>
                                                    <input type="text" data-footer-link-field="label"
                                                        data-name-template="translations[{{ $code }}][content][footer_links][__INDEX__][label]"
                                                        value="{{ $footerLinkItem['label'] ?? '' }}"
                                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                                </div>

                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700">{{ __('URL') }}</label>
                                                    <input type="text" data-footer-link-field="url"
                                                        data-name-template="translations[{{ $code }}][content][footer_links][__INDEX__][url]"
                                                        value="{{ $footerLinkItem['url'] ?? '' }}"
                                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                        placeholder="https://example.com/page">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div data-footer-link-empty
                                    class="{{ count($footerLinkItems) > 0 ? 'hidden ' : '' }}rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                    {{ __('No footer links added yet. Add your first link to show it in the links footer layout.') }}
                                </div>

                                <template data-footer-link-item-template>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                                        data-footer-link-item>
                                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                            <div
                                                class="inline-flex items-center gap-2 text-xs font-medium uppercase tracking-[0.2em] text-slate-400 rtl:flex-row-reverse">
                                                <span data-footer-link-title>{{ __('Link') }}</span>
                                            </div>

                                            <div class="flex items-center gap-2 rtl:flex-row-reverse">
                                                <button type="button" data-duplicate-footer-link
                                                    class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                                    {{ __('Duplicate') }}
                                                </button>
                                                <button type="button" data-remove-footer-link
                                                    class="rounded-xl border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100">
                                                    {{ __('Remove') }}
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-slate-700">{{ __('Label') }}</label>
                                                <input type="text" data-footer-link-field="label"
                                                    data-name-template="translations[{{ $code }}][content][footer_links][__INDEX__][label]"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                            </div>

                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-slate-700">{{ __('URL') }}</label>
                                                <input type="text" data-footer-link-field="url"
                                                    data-name-template="translations[{{ $code }}][content][footer_links][__INDEX__][url]"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                                    placeholder="https://example.com/page">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        @endif

                        @if ($showSiteFooterSocialFields)
                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Copyright Line') }}</label>
                                <input type="text" name="translations[{{ $code }}][content][copyright]"
                                    value="{{ $footerCopyrightValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ __('Only the links you fill in will appear in the footer. Leave any network empty to hide it.') }}
                                </p>
                            </div>

                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Facebook URL') }}</label>
                                <input type="url"
                                    name="translations[{{ $code }}][content][social_links][facebook]"
                                    value="{{ $footerFacebookUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="https://facebook.com/your-page">
                            </div>

                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Instagram URL') }}</label>
                                <input type="url"
                                    name="translations[{{ $code }}][content][social_links][instagram]"
                                    value="{{ $footerInstagramUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="https://instagram.com/your-page">
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('X URL') }}</label>
                                <input type="url"
                                    name="translations[{{ $code }}][content][social_links][x]"
                                    value="{{ $footerXUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="https://x.com/your-page">
                            </div>

                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('GitHub URL') }}</label>
                                <input type="url"
                                    name="translations[{{ $code }}][content][social_links][github]"
                                    value="{{ $footerGithubUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="https://github.com/your-page">
                            </div>

                            <div class="lg:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('YouTube URL') }}</label>
                                <input type="url"
                                    name="translations[{{ $code }}][content][social_links][youtube]"
                                    value="{{ $footerYoutubeUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="https://youtube.com/@your-channel">
                            </div>
                        @endif

                        @if ($showSecondaryButtonFields)
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Secondary Button Label') }}</label>
                                <input type="text"
                                    name="translations[{{ $code }}][content][secondary_button][label]"
                                    value="{{ $secondaryButtonLabelValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Secondary Button URL') }}</label>
                                <input type="text"
                                    name="translations[{{ $code }}][content][secondary_button][url]"
                                    value="{{ $secondaryButtonUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>
                        @endif

                        @if ($showFeatureRepeaterField)
                            @include(
                                'dashboard.pages.sections.partials.repeaters.campaign-features-repeater',
                                [
                                    'code' => $code,
                                    'campaignFeatureItems' => $campaignFeatureItems,
                                    'mediaPreviewBuilder' => $mediaPreviewBuilder,
                                ]
                            )
                        @endif

                        @if ($showFeaturesTextareaField)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ __('Features (each line = one bullet)') }}
                                </label>
                                <textarea name="translations[{{ $code }}][content][features_textarea]" rows="5"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">{{ $featuresTextarea }}</textarea>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ __('Each line will be converted to a feature item.') }}
                                </p>
                            </div>
                        @endif

                        @if ($showFaqItemsTextareaField)
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ __('FAQ Items') }}
                                </label>
                                <textarea name="translations[{{ $code }}][content][faq_textarea]" rows="6"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">{{ $faqItemsTextarea }}</textarea>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ __('Use one line per item in this format: Question || Answer') }}
                                </p>
                            </div>
                        @endif

                        @if ($showReviewRepeaterField)
                            <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5"
                                data-review-repeater>
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Testimonials') }}</label>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ __('Add the customer quotes you want to show in this block.') }}
                                        </p>
                                    </div>

                                    <button type="button" data-add-review-item
                                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:text-slate-900">
                                        {{ __('Add testimonial') }}
                                    </button>
                                </div>

                                <div data-review-empty
                                    class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-5 text-sm text-slate-500 {{ count($reviewItems) > 0 ? 'hidden' : '' }}">
                                    {{ __('No testimonials added yet.') }}
                                </div>

                                <div class="mt-4 space-y-4" data-review-items>
                                    @foreach ($reviewItems as $reviewIndex => $reviewItem)
                                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                                            data-review-item>
                                            <div class="flex items-center justify-between gap-3">
                                                <div
                                                    class="inline-flex items-center gap-2 text-xs font-medium uppercase tracking-[0.2em] text-slate-400">
                                                    <button type="button"
                                                        class="sections-drag-handle rounded-xl border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500"
                                                        data-review-drag-handle>
                                                        {{ __('Move') }}
                                                    </button>
                                                    <span>{{ __('Testimonial') }} {{ $reviewIndex + 1 }}</span>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <button type="button" data-duplicate-review-item
                                                        class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                                        {{ __('Duplicate') }}
                                                    </button>
                                                    <button type="button" data-remove-review-item
                                                        class="rounded-xl border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100">
                                                        {{ __('Remove') }}
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
                                                    <input type="text" data-review-field="name"
                                                        data-name-template="translations[{{ $code }}][content][items][__INDEX__][name]"
                                                        value="{{ $reviewItem['name'] ?? '' }}"
                                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                                </div>

                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700">{{ __('Role') }}</label>
                                                    <input type="text"
                                                        data-name-template="translations[{{ $code }}][content][items][__INDEX__][role]"
                                                        value="{{ $reviewItem['role'] ?? '' }}"
                                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                                </div>

                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700">{{ __('Rating') }}</label>
                                                    <select data-review-field="rating"
                                                        data-name-template="translations[{{ $code }}][content][items][__INDEX__][rating]"
                                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                                        @for ($rating = 5; $rating >= 1; $rating--)
                                                            <option value="{{ $rating }}"
                                                                {{ (int) ($reviewItem['rating'] ?? 5) === $rating ? 'selected' : '' }}>
                                                                {{ $rating }}
                                                            </option>
                                                        @endfor
                                                    </select>
                                                </div>

                                                <div class="lg:col-span-2">
                                                    <label
                                                        class="block text-sm font-medium text-slate-700">{{ __('Quote') }}</label>
                                                    <textarea rows="4" data-review-field="text"
                                                        data-name-template="translations[{{ $code }}][content][items][__INDEX__][text]"
                                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">{{ $reviewItem['text'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <template data-review-item-template>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                                        data-review-item>
                                        <div class="flex items-center justify-between gap-3">
                                            <div
                                                class="inline-flex items-center gap-2 text-xs font-medium uppercase tracking-[0.2em] text-slate-400">
                                                <button type="button"
                                                    class="sections-drag-handle rounded-xl border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500"
                                                    data-review-drag-handle>
                                                    {{ __('Move') }}
                                                </button>
                                                <span>{{ __('Testimonial') }}</span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <button type="button" data-duplicate-review-item
                                                    class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                                    {{ __('Duplicate') }}
                                                </button>
                                                <button type="button" data-remove-review-item
                                                    class="rounded-xl border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100">
                                                    {{ __('Remove') }}
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
                                                <input type="text" data-review-field="name"
                                                    data-name-template="translations[{{ $code }}][content][items][__INDEX__][name]"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                            </div>

                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-slate-700">{{ __('Role') }}</label>
                                                <input type="text"
                                                    data-name-template="translations[{{ $code }}][content][items][__INDEX__][role]"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                            </div>

                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-slate-700">{{ __('Rating') }}</label>
                                                <select data-review-field="rating"
                                                    data-name-template="translations[{{ $code }}][content][items][__INDEX__][rating]"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                                    @for ($rating = 5; $rating >= 1; $rating--)
                                                        <option value="{{ $rating }}"
                                                            {{ $rating === 5 ? 'selected' : '' }}>
                                                            {{ $rating }}
                                                        </option>
                                                    @endfor
                                                </select>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <label
                                                    class="block text-sm font-medium text-slate-700">{{ __('Quote') }}</label>
                                                <textarea rows="4" data-review-field="text"
                                                    data-name-template="translations[{{ $code }}][content][items][__INDEX__][text]"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        @endif

                        @if ($showMobileAppGalleryField)
                            @include('dashboard.pages.sections.partials.media.mobile-app-gallery', [
                                'code' => $code,
                                'mobileAppImageOneValue' => $mobileAppImageOneValue,
                                'mobileAppImageOnePreviewUrls' => $mobileAppImageOnePreviewUrls,
                                'mobileAppImageTwoValue' => $mobileAppImageTwoValue,
                                'mobileAppImageTwoPreviewUrls' => $mobileAppImageTwoPreviewUrls,
                                'mobileAppImageThreeValue' => $mobileAppImageThreeValue,
                                'mobileAppImageThreePreviewUrls' => $mobileAppImageThreePreviewUrls,
                            ])
                        @endif

                        @if ($showDesignGalleryField)
                            @include('dashboard.pages.sections.partials.media.design-gallery', [
                                'code' => $code,
                                'mobileAppImageOneValue' => $mobileAppImageOneValue,
                                'mobileAppImageOnePreviewUrls' => $mobileAppImageOnePreviewUrls,
                                'mobileAppImageTwoValue' => $mobileAppImageTwoValue,
                                'mobileAppImageTwoPreviewUrls' => $mobileAppImageTwoPreviewUrls,
                                'mobileAppImageThreeValue' => $mobileAppImageThreeValue,
                                'mobileAppImageThreePreviewUrls' => $mobileAppImageThreePreviewUrls,
                                'designImageFourValue' => $designImageFourValue,
                                'designImageFourPreviewUrls' => $designImageFourPreviewUrls,
                                'designImageFiveValue' => $designImageFiveValue,
                                'designImageFivePreviewUrls' => $designImageFivePreviewUrls,
                                'designImageSixValue' => $designImageSixValue,
                                'designImageSixPreviewUrls' => $designImageSixPreviewUrls,
                            ])
                        @endif

                        @if ($showDigitalMarketingGalleryField)
                            @include('dashboard.pages.sections.partials.media.digital-marketing-gallery', [
                                'code' => $code,
                                'mobileAppImageOneValue' => $mobileAppImageOneValue,
                                'mobileAppImageOnePreviewUrls' => $mobileAppImageOnePreviewUrls,
                                'mobileAppImageTwoValue' => $mobileAppImageTwoValue,
                                'mobileAppImageTwoPreviewUrls' => $mobileAppImageTwoPreviewUrls,
                            ])
                        @endif

                        @if ($showTechStackMediaField)
                            @include('dashboard.pages.sections.partials.media.tech-stack-logos', [
                                'code' => $code,
                                'techStackLogosValueForComponent' => $techStackLogosValueForComponent,
                                'techStackLogoPreviewUrls' => $techStackLogoPreviewUrls,
                            ])
                        @endif

                        @if ($isHeroCampaign)
                            <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                                <div class="mb-4">
                                    <label
                                        class="block text-sm font-medium text-slate-700">{{ __('CTA Button') }}</label>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ __('This button appears below the campaign features and right before the illustration block.') }}
                                    </p>
                                </div>

                                <div class="space-y-5">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('CTA Button Label') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][primary_button][label]"
                                            value="{{ $primaryButtonLabelValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('CTA Button URL') }}</label>
                                        <input type="text"
                                            name="translations[{{ $code }}][content][primary_button][url]"
                                            value="{{ $primaryButtonUrlValue }}"
                                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                    </div>
                                </div>

                                <label
                                    class="mt-5 inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 rtl:flex-row-reverse">
                                    <input type="checkbox"
                                        name="translations[{{ $code }}][content][primary_button][new_tab]"
                                        value="1" class="rounded border-slate-300"
                                        {{ $primaryButtonNewTabValue ? 'checked' : '' }}>
                                    <span>{{ __('Open CTA in a new tab') }}</span>
                                </label>
                            </div>
                        @endif

                        @if ($showMediaTypeField)
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Media Type') }}</label>
                                <select name="translations[{{ $code }}][content][media_type]"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                    <option value="image" {{ $mediaTypeOld === 'image' ? 'selected' : '' }}>Image
                                    </option>
                                    <option value="video" {{ $mediaTypeOld === 'video' ? 'selected' : '' }}>Video
                                    </option>
                                </select>
                            </div>
                        @endif

                        @if ($showMediaUrlField)
                            @if ($isHeroCampaign || $isProgrammingShowcase)
                                @include(
                                    'dashboard.pages.sections.partials.media.campaign-programming-illustration',
                                    [
                                        'code' => $code,
                                        'isProgrammingShowcase' => $isProgrammingShowcase,
                                        'campaignIllustrationValue' => $campaignIllustrationValue,
                                        'campaignIllustrationPreviewUrls' => $campaignIllustrationPreviewUrls,
                                    ]
                                )
                            @else
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700">{{ __('Media URL') }}</label>
                                    <input type="text"
                                        name="translations[{{ $code }}][content][media_url]"
                                        value="{{ $mediaUrlValue }}"
                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</form>
