{{-- Main orchestrator setup and shared schema helpers --}}
@php
    use App\Support\Sections\SectionMediaPreviewBuilder;
    use App\Support\Sections\SectionEditorSchemaHelper;

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
    $schema = $editorState['editorSchema'] ?? [];
    $schemaFields = collect($schema['fields'] ?? []);
    $fieldsByGroup = $schemaFields->groupBy('group');
    $contentFields = $fieldsByGroup->get('content', collect());
    $ctaFields = $fieldsByGroup->get('cta', collect());
    $mediaFields = $fieldsByGroup->get('media', collect());
    $schemaGroupField = function (string $groupName, string $fieldName) use ($fieldsByGroup): array {
        return collect($fieldsByGroup->get($groupName, collect()))->firstWhere('name', $fieldName) ?? [];
    };
    $schemaGroupFieldLabel = function (string $groupName, string $fieldName, string $fallback) use ($schemaGroupField) {
        return $schemaGroupField($groupName, $fieldName)['label'] ?? $fallback;
    };
    $schemaGroupFieldMeta = function (string $groupName, string $fieldName) use ($schemaGroupField): array {
        return $schemaGroupField($groupName, $fieldName);
    };
    $schemaGroupFieldPlaceholder = function (string $groupName, string $fieldName, ?string $fallback = null) use (
        $schemaGroupFieldMeta,
    ) {
        return data_get($schemaGroupFieldMeta($groupName, $fieldName), 'ui.placeholder', $fallback);
    };
    /*
    |--------------------------------------------------------------------------
    | Shared schema helpers (available in partials)
    |--------------------------------------------------------------------------
    | These helpers are defined in the main Blade scope and are available
    | to all included partials via Blade scope inheritance.
    |
    | Do NOT redefine or duplicate them inside block partials.
    | Use them directly when extracting schema-driven fields.
    | Do NOT pass them manually unless a future extraction truly requires it.
    */
    /*
    |--------------------------------------------------------------------------
    | Schema-driven field rendering
    |--------------------------------------------------------------------------
    | Use this pipeline for simple localized text/url fields whose label,
    | placeholder, type, and schema metadata can be resolved from the editor
    | schema without custom layout behavior.
    |
    | Use:
    | - $schemaFieldContext(...) to resolve the schema-backed label,
    |   placeholder, and schema metadata with safe fallbacks.
    | - $schemaRenderableFieldConfig(...) to derive renderer-ready field
    |   config such as fieldType and rows for simple scalar fields.
    | - $schemaRendererPayload(...) to produce the shared include payload
    |   while preserving existing names, values, and schemaField keys.
    |
    | Do not use this pipeline for repeaters, media fields, complex grouped
    | layouts, or textarea fields with special/manual behavior. Those remain
    | manual until they have dedicated handling.
    */
    /*
    |--------------------------------------------------------------------------
    | Architecture rules for fields and block extraction
    |--------------------------------------------------------------------------
    | Full schema-driven rendering:
    | - Use for simple localized text/url fields and other stable scalar inputs.
    | - Prefer it when label, placeholder, and type should come from schema
    |   metadata through:
    |   schemaFieldContext -> schemaRenderableFieldConfig -> schemaRendererPayload
    |
    | Manual rendering:
    | - Keep repeaters, media fields, textarea fields with custom behavior,
    |   JS-heavy markup, special DOM contracts, and complex conditional layouts
    |   manual unless a dedicated pattern already exists for them.
    |
    | Hybrid rendering:
    | - Acceptable for very small manual blocks that should stay simple but
    |   still benefit from schema-based label/placeholder resolution.
    | - The reviews limit field is the reference pattern for this approach.
    |
    | Block extraction:
    | - Extract coherent field families with clear boundaries and a low or
    |   moderate dependency surface when doing so improves readability without
    |   breaking editor-form.blade.php orchestration ownership.
    |
    | Keep in the main orchestrator:
    | - Global setup, shared closures/helpers, the language loop, high-level
    |   orchestration, and complex cross-block conditional flow stay here.
    |
    | Governance:
    | - Do not introduce new rendering patterns casually.
    | - Consistency is more important than theoretical purity.
    | - Schema migration and block extraction must remain incremental and
    |   low-risk.
    */
    /*
    |--------------------------------------------------------------------------
    | Dependency policy for extracted block partials
    |--------------------------------------------------------------------------
    | Pass explicit dependencies when they are direct field values, small
    | block-specific scalars, or otherwise make the partial's required input
    | obvious at the include site.
    |
    | Shared inherited scope is acceptable for intentionally global editor
    | dependencies such as schema helpers, shared schema context/labels/groups,
    | and common infrastructure objects reused consistently across blocks.
    |
    | Do not pass helpers or values redundantly when they are already part of
    | that shared scope contract. Avoid large catch-all dependency bundles.
    |
    | Warning signs:
    | - too many hidden parent variables
    | - unclear required inputs at the include site
    | - fragile coupling to distant setup code
    |
    | Tighten dependencies and pass more explicitly when block complexity
    | grows, reuse expands beyond this orchestrator, or review/maintenance
    | becomes harder with inherited scope alone.
    */
    /*
    |--------------------------------------------------------------------------
    | Inventory: major editor rendering regions
    |--------------------------------------------------------------------------
    | A) Already extracted
    | - Templates slider config: extracted / manual / keep extracted; bounded
    |   database-config card with obvious scalar inputs.
    | - Templates listing config: extracted / mixed / keep extracted; coherent
    |   schema-first block with one small manual field.
    | - Reviews database config: extracted / hybrid / keep extracted; small
    |   manual card already aligned with shared field context.
    | - Our work database config: extracted / manual / keep extracted; simple
    |   self-contained portfolio config block.
    | - Hosting pricing config: extracted / mixed / keep extracted; schema-
    |   driven CTA label + manual category visibility controls, with repeaters
    |   intentionally kept in orchestrator.
    | - Hero campaign CTA fields: extracted / manual / keep extracted;
    |   dedicated CTA wording and toggles isolated for future reuse.
    | - Header branding fields: extracted / manual-hybrid / keep extracted;
    |   brand identity, title logic, logo picker, and navigation hint are
    |   isolated into a bounded partial.
    | - Domains search fields: extracted / mixed / keep extracted; bounded
    |   search-oriented region with consistent heading, description, and
    |   placeholder behavior.
    | - Secondary button fields: extracted / manual-hybrid / keep extracted;
    |   CTA-aware wording and layout isolated into a small reusable partial.
    | - Site footer social fields: extracted / mixed / keep extracted; cohesive
    |   footer-social family with shared helper usage.
    |
    | B) Inline and should stay inline for now
    | - Orchestrator scaffolding: inline / mixed / keep inline; shared setup,
    |   settings surface, language tabs, and locale loop own global flow.
    | - Repeater/media families: inline / mixed / keep inline; JS hooks,
    |   templates, and preview contracts are still tightly coupled here.
    | - Primary button logic: inline / manual-hybrid / keep inline; section-
    |   sensitive wording and toggles now stabilized, especially for
    |   how_we_build CTA behavior.
    | - Header button logic: inline / manual-hybrid / keep inline for now;
    |   header-specific CTA wording and new-tab behavior remain
    |   intentionally coupled to the shared primary_button system.
    |
    | D) Inline but better suited for hybrid alignment first
    | - Manual textarea/content scalar family: inline / hybrid-manual / keep
    |   aligned inline; core textarea wrappers, labels, and hint patterns are
    |   now more consistent, with future extraction still optional.
    |
    | E) Inline and already schema-driven enough
    | - Simple heading/search scalar fields: inline / schema-driven / keep
    |   inline unless extracted as part of a larger cohesive family.
    |
    | F) Inline/manual and likely not worth extracting soon
    | - One-off notices and tiny contextual hints: inline / manual / leave
    |   inline; too small and too tied to nearby conditions to justify partials.
    */
    $schemaFieldContext = function (
        string $groupName,
        string $fieldName,
        string $fallbackLabel,
        ?string $fallbackPlaceholder = null,
    ) use ($schemaGroupFieldLabel, $schemaGroupFieldPlaceholder, $schemaGroupFieldMeta): array {
        return [
            'label' => $schemaGroupFieldLabel($groupName, $fieldName, $fallbackLabel),
            'placeholder' => $schemaGroupFieldPlaceholder($groupName, $fieldName, $fallbackPlaceholder),
            'schemaMeta' => $schemaGroupFieldMeta($groupName, $fieldName),
        ];
    };
    $schemaFieldRows = function (array $schemaMeta, int $fallback = 4): int {
        return (int) data_get($schemaMeta, 'ui.rows', $fallback);
    };
    $schemaFieldType = function (array $schemaMeta, string $fallback = 'text'): string {
        $type = (string) data_get($schemaMeta, 'ui.type', $fallback);

        return in_array($type, ['text', 'textarea', 'url'], true) ? $type : $fallback;
    };
    $schemaRenderableFieldConfig = function (
        array $fieldContext,
        string $fallbackType = 'text',
        int $fallbackRows = 3
    ) use ($schemaFieldType, $schemaFieldRows): array {
        $resolvedType = $schemaFieldType($fieldContext['schemaMeta'], $fallbackType);

        return [
            'fieldType' => $resolvedType,
            'rows' => $resolvedType === 'textarea'
                ? $schemaFieldRows($fieldContext['schemaMeta'], $fallbackRows)
                : null,
            'label' => $fieldContext['label'],
            'placeholder' => $fieldContext['placeholder'],
            'schemaMeta' => $fieldContext['schemaMeta'],
        ];
    };
    $schemaRendererPayload = function (
        array $renderConfig,
        string $name,
        mixed $value,
        string $schemaField,
        string $wrapperClass = 'lg:col-span-2'
    ): array {
        return [
            'fieldType' => $renderConfig['fieldType'],
            'label' => $renderConfig['label'],
            'name' => $name,
            'value' => $value,
            'placeholder' => $renderConfig['placeholder'],
            'rows' => $renderConfig['rows'],
            'schemaField' => $schemaField,
            'schemaMeta' => $renderConfig['schemaMeta'],
            'wrapperClass' => $wrapperClass,
        ];
    };
    $schemaHelper = SectionEditorSchemaHelper::make($schema);
    $schemaGroups = collect($schema['groups'] ?? []);
    $schemaGroupLabel = fn(string $name, string $fallback) => $schemaGroups->firstWhere('name', $name)['label'] ??
        $fallback;
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
    $contentGroupLabel = $schemaGroupLabel('content', __('Content'));
    $footerLinksGroupLabel = $schemaGroupLabel('links', __('Footer Links'));
    $socialLinksGroupLabel = $schemaGroupLabel('social', __('Social Links'));
    $footerLinksFieldLabel = $schemaHelper->fieldLabel('footer_links', __('Footer Links'));
    $footerLinksFieldUi = $schemaHelper->fieldUi('footer_links');
    $footerLinksItemLabel = $footerLinksFieldUi['itemLabel'] ?? __('Link');
    $copyrightFieldLabel = $schemaHelper->fieldLabel('copyright', __('Copyright Line'));
@endphp

<form id="{{ $formId }}" method="{{ strtoupper($formMethod) }}" action="{{ $formAction }}"
    class="{{ $formClass }}" data-section-editor-form data-section-id="{{ $section->id }}"
    data-default-editor-tab="lang-{{ $editorDefaultLocale }}" data-save-action="{{ $saveAction }}"
    data-section-schema='@json($schema)' @if ($preventNativeSubmit) onsubmit="return false;" @endif>
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
        $isHowWeBuild = $selectedType === 'how_we_build';
        $isHeroCampaign = (bool) ($typeFlags['isHeroCampaign'] ?? false);
        $isProgrammingShowcase = (bool) ($typeFlags['isProgrammingShowcase'] ?? false);
        $isDesignShowcase = (bool) ($typeFlags['isDesignShowcase'] ?? false);
        $isMobileAppShowcase = (bool) ($typeFlags['isMobileAppShowcase'] ?? false);
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
        $usesCustomPresetEditor = (bool) ($editorState['usesCustomPresetEditor'] ?? false);
        $customPresetEditor = is_array($editorState['customPresetEditor'] ?? null) ? $editorState['customPresetEditor'] : null;
        $usesDynamicEditor = (bool) ($editorState['usesDynamicEditor'] ?? false);
        $dynamicEditor = is_array($editorState['dynamicEditor'] ?? null) ? $editorState['dynamicEditor'] : null;
        $selectedFooterVariant = old('variant', $section->variant ?: 'simple_social');
        $hostingPricingAvailableCategories = $editorState['hostingPricingAvailableCategories'] ?? collect();
    @endphp

    {{-- Editor feedback surface --}}
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

    {{-- Form settings surface --}}
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

    {{-- Localized content surface --}}
    <div class="{{ $surfaceClass }}">
        <div class="{{ $sectionHeaderClass }}">
            <h2 class="text-lg font-semibold text-slate-900">{{ $contentSectionLabel }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $contentSectionHelp }}</p>
        </div>

        <div class="{{ $sectionBodyClass }}">
            {{-- Per-language editor navigation --}}
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

            {{-- Per-language hydration and rendering loop --}}
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
                    $heroCampaignTrustItemsTextarea = $localeViewData['heroCampaignTrustItemsTextarea'] ?? '';
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
                    $primaryButtonVisibleValue = (bool) ($localeScalarValues['primaryButtonVisibleValue'] ?? false);
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

                    @if ($usesCustomPresetEditor && is_array($customPresetEditor))
                        {{-- Custom preset editor path: curated UI for specific definition-driven sections. --}}
                        @include($customPresetEditor['view'], [
                            'code' => $code,
                            'customPresetEditor' => $customPresetEditor,
                            'contentGridClass' => $contentGridClass,
                            'usesInternalLabel' => $usesInternalLabel,
                            'sectionTitleValue' => $sectionTitleValue,
                            'mediaPreviewBuilder' => $mediaPreviewBuilder,
                        ])
                    @elseif ($usesDynamicEditor && is_array($dynamicEditor))
                        {{-- Hybrid editor path: definition-driven fields render here while the legacy/custom editor remains unchanged below. --}}
                        @include('dashboard.pages.sections.partials.dynamic-editor.renderer', [
                            'code' => $code,
                            'dynamicEditor' => $dynamicEditor,
                            'contentGridClass' => $contentGridClass,
                            'usesInternalLabel' => $usesInternalLabel,
                            'sectionTitleValue' => $sectionTitleValue,
                        ])
                    @else
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

                        @include(
                            'dashboard.pages.sections.partials.blocks.header-branding-fields',
                            [
                                'code' => $code,
                                'brandPrefixValue' => $brandPrefixValue,
                                'brandSuffixValue' => $brandSuffixValue,
                                'heroTitleValue' => $heroTitleValue,
                                'headerLogoValue' => $headerLogoValue,
                                'headerLogoPreviewUrls' => $headerLogoPreviewUrls,
                                'showBrandFields' => $showBrandFields,
                                'showMainTitleField' => $showMainTitleField,
                                'isSiteHeader' => $isSiteHeader,
                                'isSiteFooter' => $isSiteFooter,
                                'isHeroCampaign' => $isHeroCampaign,
                                'isProgrammingShowcase' => $isProgrammingShowcase,
                                'isMobileAppShowcase' => $isMobileAppShowcase,
                                'isDesignShowcase' => $isDesignShowcase,
                                'isDigitalMarketingShowcase' => $isDigitalMarketingShowcase,
                                'isReviewsShowcase' => $isReviewsShowcase,
                                'isOurWorkShowcase' => $isOurWorkShowcase,
                                'isDomainsShowcase' => $isDomainsShowcase,
                                'isTemplatesSliderShowcase' => $isTemplatesSliderShowcase,
                                'isTemplatesListingShowcase' => $isTemplatesListingShowcase,
                            ]
                        )

                        @if ($showSubtitleField)
                            @php
                                $subtitleFieldContext = $schemaFieldContext(
                                    'content',
                                    'subtitle',
                                    $isHeroCampaign ? __('Main Title - Line 2') : __('Subtitle'),
                                    null,
                                );
                            @endphp

                            {{-- This field remains manual because textarea rendering can require special behavior and layout handling. Do not convert it without dedicated textarea handling. --}}
                            @include('dashboard.pages.sections.partials.fields.schema-field-renderer', [
                                'fieldType' => 'textarea',
                                'label' => $subtitleFieldContext['label'],
                                'name' => 'translations[' . $code . '][content][subtitle]',
                                'value' => $subtitleValue,
                                'placeholder' => null,
                                'rows' => $schemaFieldRows($subtitleFieldContext['schemaMeta'], 3),
                                'schemaField' => 'subtitle',
                                'schemaMeta' => $subtitleFieldContext['schemaMeta'],
                                'wrapperClass' => 'lg:col-span-2',
                            ])
                        @endif

                        @include(
                            'dashboard.pages.sections.partials.blocks.domains-search-fields',
                            [
                                'code' => $code,
                                'domainsSearchHeadingValue' => $domainsSearchHeadingValue,
                                'domainsInputPlaceholderValue' => $domainsInputPlaceholderValue,
                                'descriptionValue' => $descriptionValue,
                                'isDomainsShowcase' => $isDomainsShowcase,
                                'showDomainsSearchHeadingField' => $showDomainsSearchHeadingField,
                                'showDescriptionField' => $showDescriptionField,
                                'showDomainsPlaceholderField' => $showDomainsPlaceholderField,
                            ]
                        )

                        @if ($showPrimaryButtonFields && $isHeroCampaign)
                            {{-- Hero campaign CTA extracted into its own partial. --}}
                            @include(
                                'dashboard.pages.sections.partials.blocks.hero-campaign-cta-fields',
                                [
                                    'code' => $code,
                                    'primaryButtonLabelValue' => $primaryButtonLabelValue,
                                    'primaryButtonUrlValue' => $primaryButtonUrlValue,
                                    'primaryButtonVisibleValue' => $primaryButtonVisibleValue,
                                    'primaryButtonNewTabValue' => $primaryButtonNewTabValue,
                                ]
                            )
                        @endif

                        @if ($showHostingPricingDatabaseField)
                            {{-- Hosting pricing config extracted; repeaters remain in orchestrator --}}
                            @include(
                                'dashboard.pages.sections.partials.blocks.hosting-pricing-config-fields',
                                [
                                    'code' => $code,
                                    'hostingPricingButtonLabelValue' => $hostingPricingButtonLabelValue,
                                    'hostingPricingAvailableCategories' => $hostingPricingAvailableCategories,
                                    'hostingPricingVisibleCategoryIds' => $hostingPricingVisibleCategoryIds,
                                ]
                            )
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

                        @php
                            $outputsHeadingFieldContext = $schemaFieldContext(
                                'content',
                                'outputs_heading',
                                __('Outputs Heading'),
                                null,
                            );
                            $featuresHeadingFieldContext = $schemaFieldContext(
                                'content',
                                'features_heading',
                                __('Features Heading'),
                                null,
                            );
                            $outputsHeadingRenderConfig = $schemaRenderableFieldConfig(
                                $outputsHeadingFieldContext,
                                'text',
                                3,
                            );
                            $featuresHeadingRenderConfig = $schemaRenderableFieldConfig(
                                $featuresHeadingFieldContext,
                                'text',
                                3,
                            );
                        @endphp

                        @if ($showOutputsHeadingField)
                            @include(
                                'dashboard.pages.sections.partials.fields.schema-field-renderer',
                                $schemaRendererPayload(
                                    $outputsHeadingRenderConfig,
                                    'translations[' . $code . '][content][outputs_heading]',
                                    $outputsHeadingValue,
                                    'outputs_heading',
                                    'lg:col-span-2',
                                )
                            )
                        @endif

                        @if ($showFeaturesHeadingField)
                            @include(
                                'dashboard.pages.sections.partials.fields.schema-field-renderer',
                                $schemaRendererPayload(
                                    $featuresHeadingRenderConfig,
                                    'translations[' . $code . '][content][features_heading]',
                                    $featuresHeadingValue,
                                    'features_heading',
                                    'lg:col-span-2',
                                )
                            )
                        @endif

                        {{-- Repeaters with heavier DOM contracts remain inline in the orchestrator. --}}
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

                        {{-- Extracted configuration/database blocks: explicit block values + inherited shared helpers. --}}
                        @if ($showTemplatesSliderDatabaseField)
                            @include(
                                'dashboard.pages.sections.partials.blocks.templates-slider-fields',
                                [
                                    'code' => $code,
                                    'templatesSliderBuyLabelValue' => $templatesSliderBuyLabelValue,
                                    'templatesSliderPreviewLabelValue' => $templatesSliderPreviewLabelValue,
                                    'templatesSliderLimitValue' => $templatesSliderLimitValue,
                                ]
                            )
                        @endif

                        @if ($showTemplatesListingDatabaseField)
                            @include(
                                'dashboard.pages.sections.partials.blocks.templates-listing-fields',
                                [
                                    'code' => $code,
                                    'templatesListingBreadcrumbLabelValue' => $templatesListingBreadcrumbLabelValue,
                                    'templatesListingAllCategoriesLabelValue' =>
                                        $templatesListingAllCategoriesLabelValue,
                                    'templatesListingTypeLabelValue' => $templatesListingTypeLabelValue,
                                    'templatesListingBestSellersLabelValue' =>
                                        $templatesListingBestSellersLabelValue,
                                    'templatesListingPriceLabelValue' => $templatesListingPriceLabelValue,
                                    'templatesListingBuyLabelValue' => $templatesListingBuyLabelValue,
                                    'templatesListingPreviewLabelValue' => $templatesListingPreviewLabelValue,
                                    'templatesListingItemsPerPageValue' => $templatesListingItemsPerPageValue,
                                ]
                            )
                        @endif

                        @if ($showReviewsDatabaseField)
                            @include(
                                'dashboard.pages.sections.partials.blocks.reviews-database-fields',
                                [
                                    'code' => $code,
                                    'reviewsLimitValue' => $reviewsLimitValue,
                                ]
                            )
                        @endif

                        @if ($showOurWorkDatabaseField)
                            @include(
                                'dashboard.pages.sections.partials.blocks.our-work-database-fields',
                                [
                                    'code' => $code,
                                    'ourWorkLimitValue' => $ourWorkLimitValue,
                                    'ourWorkVisitLabelValue' => $ourWorkVisitLabelValue,
                                ]
                            )
                        @endif

                        {{-- Primary button wording and toggles are section-sensitive; how_we_build is intentionally treated as a CTA-driven variant. --}}
                        @if ($showPrimaryButtonFields && !$isHeroCampaign)
                            @php
                                $isHowWeBuildPrimaryButtonContext = $isHowWeBuild;
                                $isDomainsPrimaryButtonContext = $isDomainsShowcase;
                                $isHeaderPrimaryButtonContext = $isSiteHeader;
                                $usesCtaPrimaryButtonLabels =
                                    $isHowWeBuildPrimaryButtonContext ||
                                    $isProgrammingShowcase ||
                                    $isMobileAppShowcase ||
                                    $isDesignShowcase ||
                                    $isDigitalMarketingShowcase;
                                $usesWidePrimaryButtonFields =
                                    $usesCtaPrimaryButtonLabels ||
                                    $isDomainsPrimaryButtonContext ||
                                    $isHeaderPrimaryButtonContext;
                                $showsPrimaryButtonNewTabToggle =
                                    $usesCtaPrimaryButtonLabels || $isHeaderPrimaryButtonContext;
                                $primaryButtonFieldColumnClass = $usesWidePrimaryButtonFields ? 'lg:col-span-2' : '';
                                $primaryButtonLabelText = $isHeaderPrimaryButtonContext
                                    ? __('Header Button Label')
                                    : ($isDomainsPrimaryButtonContext
                                        ? __('Search Button Label')
                                        : ($usesCtaPrimaryButtonLabels
                                            ? __('CTA Button Label')
                                            : __('Primary Button Label')));
                                $primaryButtonUrlLabelText = $isHeaderPrimaryButtonContext
                                    ? __('Header Button URL')
                                    : ($isDomainsPrimaryButtonContext
                                        ? __('Search Page URL')
                                        : ($usesCtaPrimaryButtonLabels
                                            ? __('CTA Button URL')
                                            : __('Primary Button URL')));
                                $primaryButtonNewTabLabelText = $isHeaderPrimaryButtonContext
                                    ? __('Open header button in a new tab')
                                    : __('Open CTA in a new tab');
                            @endphp

                            <div class="{{ $primaryButtonFieldColumnClass }}">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $primaryButtonLabelText }}
                                </label>
                                <input type="text"
                                    name="translations[{{ $code }}][content][primary_button][label]"
                                    value="{{ $primaryButtonLabelValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>

                            <div class="{{ $primaryButtonFieldColumnClass }}">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $primaryButtonUrlLabelText }}
                                </label>
                                <input type="text"
                                    name="translations[{{ $code }}][content][primary_button][url]"
                                    value="{{ $primaryButtonUrlValue }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                            </div>

                            @if ($isHowWeBuildPrimaryButtonContext)
                                <div class="lg:col-span-2">
                                    <label
                                        class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                        <input type="hidden"
                                            name="translations[{{ $code }}][content][primary_button][visible]"
                                            value="0">
                                        <input type="checkbox"
                                            name="translations[{{ $code }}][content][primary_button][visible]"
                                            value="1" class="rounded border-slate-300"
                                            {{ $primaryButtonVisibleValue ? 'checked' : '' }}>
                                        {{ __('Show CTA button') }}
                                    </label>
                                </div>
                            @endif

                            @if ($showsPrimaryButtonNewTabToggle)
                                <div class="lg:col-span-2">
                                    <label
                                        class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                        <input type="hidden"
                                            name="translations[{{ $code }}][content][primary_button][new_tab]"
                                            value="0">
                                        <input type="checkbox"
                                            name="translations[{{ $code }}][content][primary_button][new_tab]"
                                            value="1" class="rounded border-slate-300"
                                            {{ $primaryButtonNewTabValue ? 'checked' : '' }}>
                                        {{ $primaryButtonNewTabLabelText }}
                                    </label>
                                </div>
                            @endif
                        @endif

                        {{-- Footer-specific repeater/layout logic remains inline here. --}}
                        @if ($showSiteFooterLinksTextareaField)
                            <div class="lg:col-span-2" data-footer-link-repeater
                                data-schema-group-label="{{ $footerLinksGroupLabel }}"
                                data-schema-field="footer_links"
                                data-schema-field-label="{{ $footerLinksFieldLabel }}"
                                data-footer-link-item-label="{{ $footerLinksItemLabel }}"
                                data-footer-link-item-hint="{{ __('Add the label and destination for this footer link.') }}">
                                <div class="mb-4 flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ $footerLinksFieldLabel }}</label>
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

                        {{-- Extracted footer/social configuration block --}}
                        @if ($showSiteFooterSocialFields)
                            @include(
                                'dashboard.pages.sections.partials.blocks.site-footer-social-fields',
                                [
                                    'code' => $code,
                                    'footerCopyrightValue' => $footerCopyrightValue,
                                    'footerFacebookUrlValue' => $footerFacebookUrlValue,
                                    'footerInstagramUrlValue' => $footerInstagramUrlValue,
                                    'footerXUrlValue' => $footerXUrlValue,
                                    'footerGithubUrlValue' => $footerGithubUrlValue,
                                    'footerYoutubeUrlValue' => $footerYoutubeUrlValue,
                                    'copyrightFieldLabel' => $copyrightFieldLabel,
                                    'socialLinksGroupLabel' => $socialLinksGroupLabel,
                                ]
                            )
                        @endif

                        @if ($showSecondaryButtonFields)
                            @include(
                                'dashboard.pages.sections.partials.blocks.secondary-button-fields',
                                [
                                    'code' => $code,
                                    'secondaryButtonLabelValue' => $secondaryButtonLabelValue,
                                    'secondaryButtonUrlValue' => $secondaryButtonUrlValue,
                                    'isHeroCampaign' => $isHeroCampaign,
                                    'isHowWeBuild' => $isHowWeBuild,
                                    'isProgrammingShowcase' => $isProgrammingShowcase,
                                    'isMobileAppShowcase' => $isMobileAppShowcase,
                                    'isDesignShowcase' => $isDesignShowcase,
                                    'isDigitalMarketingShowcase' => $isDigitalMarketingShowcase,
                                ]
                            )
                        @endif

                        {{-- Inline repeaters, textarea builders, and media areas continue below. --}}
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

                        {{-- Manual textarea/content scalar family: aligned for consistency before any future extraction or deeper schema migration. --}}
                        @php
                            $manualContentTextareaWrapperClass = 'lg:col-span-2';
                            $manualContentTextareaClass =
                                'mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900';
                            $manualContentTextareaHintClass = 'mt-2 text-xs text-slate-500';
                        @endphp

                        @if ($showFeaturesTextareaField)
                            <div class="{{ $manualContentTextareaWrapperClass }}">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ __('Feature Items') }}
                                </label>
                                <textarea name="translations[{{ $code }}][content][features_textarea]" rows="5"
                                    class="{{ $manualContentTextareaClass }}">{{ $featuresTextarea }}</textarea>
                                <p class="{{ $manualContentTextareaHintClass }}">
                                    {{ __('Use one line per item. Each line becomes one feature bullet.') }}
                                </p>
                            </div>
                        @endif

                        @if ($showFaqItemsTextareaField)
                            <div class="{{ $manualContentTextareaWrapperClass }}">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ __('FAQ Items') }}
                                </label>
                                <textarea name="translations[{{ $code }}][content][faq_textarea]" rows="6"
                                    class="{{ $manualContentTextareaClass }}">{{ $faqItemsTextarea }}</textarea>
                                <p class="{{ $manualContentTextareaHintClass }}">
                                    {{ __('Use one line per item in this format: Question || Answer.') }}
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
                            @php
                                $heroCampaignTrustItemsFieldContext = $schemaFieldContext(
                                    'cta',
                                    'trust_items',
                                    __('Trust Items'),
                                    __('One line per item shown below the CTA button'),
                                );
                            @endphp

                            <div class="{{ $manualContentTextareaWrapperClass }} rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                                <label class="block text-sm font-medium text-slate-700">
                                    {{ $heroCampaignTrustItemsFieldContext['label'] }}
                                </label>
                                <textarea name="translations[{{ $code }}][content][trust_items_textarea]"
                                    rows="{{ $schemaFieldRows($heroCampaignTrustItemsFieldContext['schemaMeta'], 3) }}"
                                    class="{{ $manualContentTextareaClass }}"
                                    placeholder="{{ $heroCampaignTrustItemsFieldContext['placeholder'] }}">{{ $heroCampaignTrustItemsTextarea }}</textarea>
                                <p class="{{ $manualContentTextareaHintClass }}">
                                    {{ __('Use one line per item. These appear below the primary CTA in the campaign hero.') }}
                                </p>
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
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</form>
