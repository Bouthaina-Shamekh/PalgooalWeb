<?php

namespace App\Support\Sections;

use App\Models\PlanCategory;
use App\Models\Section;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SectionEditorDataFactory
{
    protected const LEGACY_TYPE_ALIASES = [
        'templates-pages' => 'templates_listing_showcase',
    ];

    public function __construct(
        protected SectionEditorRepeaterFactory $repeaterFactory,
        protected SectionEditorLocaleViewDataFactory $localeViewDataFactory,
        protected SectionEditorTypeCapabilities $typeCapabilities,
        protected SectionEditorSchemaRegistry $schemaRegistry,
        protected SectionCustomPresetEditorRenderer $customPresetEditorRenderer,
        protected DynamicSectionEditorRenderer $dynamicSectionEditorRenderer,
    ) {}

    public function make(Section $section, iterable $languages, array $sectionTypes = []): array
    {
        $selectedType = $this->normalizeSelectedType(old('type', $section->type));
        $typeLabel = $selectedType === $section->type
            ? $section->resolvedTypeMeta($sectionTypes)['label']
            : ($sectionTypes[$selectedType]['label']
                ?? Str::headline(str_replace(['_', '-'], ' ', $selectedType)));
        $typeCapabilities = $this->typeCapabilities->for($selectedType);
        $typeFlags = $typeCapabilities['typeFlags'];
        $fieldFlags = $typeCapabilities['flags'];
        $customPresetEditor = $this->customPresetEditorRenderer->buildForSection($section, $languages);
        $dynamicEditor = is_array($customPresetEditor) && (bool) ($customPresetEditor['enabled'] ?? false)
            ? null
            : $this->dynamicSectionEditorRenderer->buildForSection($section, $languages);

        $editorState = [
            'selectedType' => $selectedType,
            'defaultLocale' => $this->resolveDefaultLocale($languages),
            'typeFlags' => $typeFlags,
            'usesInternalLabel' => (bool) ($typeCapabilities['usesInternalLabel'] ?? false),
            'flags' => $fieldFlags,
            'editorSchema' => $this->schemaRegistry->for($selectedType),
            'usesCustomPresetEditor' => is_array($customPresetEditor) && (bool) ($customPresetEditor['enabled'] ?? false),
            'customPresetEditor' => $customPresetEditor,
            'usesDynamicEditor' => is_array($dynamicEditor) && (bool) ($dynamicEditor['enabled'] ?? false),
            'dynamicEditor' => $dynamicEditor,
            'hostingPricingAvailableCategories' => ($typeFlags['isHostingPricingShowcase'] ?? false)
                ? PlanCategory::query()->active()->ordered()->with('translations')->get()
                : collect(),
            'localeScalarValues' => $this->buildLocaleScalarValues(
                $section,
                $languages,
                $typeLabel,
                (bool) ($typeFlags['isTemplatesListingShowcase'] ?? false),
            ),
        ];

        $editorState = array_merge($editorState, $this->buildRepeaterState($section, $languages, $typeFlags, $fieldFlags));

        $editorState['localeViewData'] = $this->localeViewDataFactory->make($section, $languages, $editorState);

        return $editorState;
    }

    protected function buildRepeaterState(
        Section $section,
        iterable $languages,
        array $typeFlags,
        array $fieldFlags,
    ): array {
        return [
            'localeCampaignFeatureItems' => ($typeFlags['isHeroCampaign'] ?? false)
                ? $this->repeaterFactory->buildLocaleCampaignFeatureItems($section, $languages)
                : [],
            'localeCampaignFeatureItems' => ($typeFlags['isHeroCampaign'] ?? false)
                ? $this->repeaterFactory->buildLocaleCampaignFeatureItems($section, $languages)
                : [],    
            'localeOutputItems' => ($typeFlags['isProgrammingShowcase'] ?? false)
                ? $this->repeaterFactory->buildLocaleOutputItems($section, $languages)
                : [],
            'localeServiceItems' => ($typeFlags['isDesignShowcase'] ?? false) || ($typeFlags['isDigitalMarketingShowcase'] ?? false)
                ? $this->repeaterFactory->buildLocaleServiceItems($section, $languages)
                : [],
            'localeBuildStepItems' => ($fieldFlags['showBuildStepsRepeaterField'] ?? false)
                ? $this->repeaterFactory->buildLocaleBuildStepItems($section, $languages)
                : [],
            'localePricingCategoryItems' => ($typeFlags['isHostingPricingShowcase'] ?? false)
                ? $this->repeaterFactory->buildLocalePricingCategoryItems($section, $languages)
                : [],
            'localePricingPlanItems' => ($typeFlags['isHostingPricingShowcase'] ?? false)
                ? $this->repeaterFactory->buildLocalePricingPlanItems($section, $languages)
                : [],
        ];
    }

    protected function buildLocaleScalarValues(
        Section $section,
        iterable $languages,
        string $typeLabel,
        bool $isTemplatesListingShowcase,
    ): array {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section, $typeLabel, $isTemplatesListingShowcase) {
                $code = $language->code;
                $translation = $section->translations->firstWhere('locale', $code);
                $content = is_array($translation?->content) ? $translation->content : [];
                $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];
                $secondaryButton = is_array($content['secondary_button'] ?? null) ? $content['secondary_button'] : [];

                return [
                    $code => array_merge(
                        $this->buildBaseScalarValues($code, $translation, $content, $typeLabel, $isTemplatesListingShowcase),
                        $this->buildBrandScalarValues($code, $content),
                        $this->buildButtonScalarValues($code, $section->type, $content, $primaryButton, $secondaryButton),
                        $this->buildTemplatesScalarValues($code, $content),
                        $this->buildFooterScalarValues($code, $content),
                        $this->buildSocialScalarValues($code, $content),
                        $this->buildMediaScalarValues($code, $content),
                        $this->buildDomainsScalarValues($code, $content),
                        $this->buildTechStackScalarValues($code, $content),
                    ),
                ];
            })
            ->all();
    }

    protected function buildBaseScalarValues(
        string $code,
        mixed $translation,
        array $content,
        string $typeLabel,
        bool $isTemplatesListingShowcase,
    ): array {
        return [
            'sectionTitleValue' => $this->stringValue($this->oldTranslationValue($code, 'title', $translation?->title ?? '')),
            'eyebrowValue' => $this->stringValue($this->oldContentValue($code, 'eyebrow', $content['eyebrow'] ?? '')),
            'heroTitleValue' => $this->stringValue(
                $this->oldContentValue(
                    $code,
                    'title',
                    $this->resolveHeroTitleValue($translation, $content, $typeLabel, $isTemplatesListingShowcase),
                ),
            ),
            'subtitleValue' => $this->stringValue($this->oldContentValue($code, 'subtitle', $content['subtitle'] ?? '')),
            'descriptionValue' => $this->stringValue($this->oldContentValue($code, 'description', $content['description'] ?? '')),
            'featuresHeadingValue' => $this->stringValue($this->oldContentValue($code, 'features_heading', $content['features_heading'] ?? '')),
            'outputsHeadingValue' => $this->stringValue($this->oldContentValue($code, 'outputs_heading', $content['outputs_heading'] ?? '')),
            'reviewsLimitValue' => $this->stringValue($this->oldContentValue($code, 'limit', $content['limit'] ?? '')),
            'ourWorkLimitValue' => $this->stringValue($this->oldContentValue($code, 'limit', $content['limit'] ?? '')),
            'ourWorkVisitLabelValue' => $this->stringValue($this->oldContentValue($code, 'visit_label', $content['visit_label'] ?? '')),
        ];
    }

    protected function buildBrandScalarValues(string $code, array $content): array
    {
        return [
            'brandPrefixValue' => $this->stringValue($this->oldContentValue($code, 'brand_prefix', $content['brand_prefix'] ?? '')),
            'brandSuffixValue' => $this->stringValue($this->oldContentValue($code, 'brand_suffix', $content['brand_suffix'] ?? '')),
        ];
    }

    protected function buildButtonScalarValues(
        string $code,
        string $sectionType,
        array $content,
        array $primaryButton,
        array $secondaryButton,
    ): array {
        $primaryButtonDefaultLabel = $this->defaultPrimaryButtonLabelForType($sectionType);
        $primaryButtonDefaultUrl = $this->defaultPrimaryButtonUrlForType($sectionType);
        $primaryButtonDefaultVisible = $this->defaultPrimaryButtonVisibleForType($sectionType);
        $primaryButtonDefaultNewTab = $this->defaultPrimaryButtonNewTabForType($sectionType);

        return [
            'hostingPricingButtonLabelValue' => $this->stringValue($this->oldContentValue($code, 'button_label', $content['button_label'] ?? __('Choose Now'))),
            'primaryButtonLabelValue' => $this->stringValue(
                $this->oldNestedContentValue(
                    $code,
                    'primary_button.label',
                    $primaryButton['label'] ?? $primaryButtonDefaultLabel,
                ),
            ),
            'primaryButtonUrlValue' => $this->stringValue(
                $this->oldNestedContentValue(
                    $code,
                    'primary_button.url',
                    $primaryButton['url'] ?? $primaryButtonDefaultUrl,
                ),
            ),
            'primaryButtonVisibleValue' => $this->oldBooleanContentValue(
                $code,
                'primary_button.visible',
                $primaryButton['visible'] ?? $primaryButtonDefaultVisible,
            ),
            'primaryButtonNewTabValue' => $this->oldBooleanContentValue(
                $code,
                'primary_button.new_tab',
                $primaryButton['new_tab'] ?? $primaryButtonDefaultNewTab,
            ),
            'secondaryButtonLabelValue' => $this->stringValue($this->oldNestedContentValue($code, 'secondary_button.label', $secondaryButton['label'] ?? '')),
            'secondaryButtonUrlValue' => $this->stringValue($this->oldNestedContentValue($code, 'secondary_button.url', $secondaryButton['url'] ?? '')),
        ];
    }

    protected function defaultPrimaryButtonLabelForType(string $sectionType): string
    {
        return $sectionType === 'how_we_build'
            ? 'ابدأ الآن — موقعك جاهز خلال دقائق'
            : '';
    }

    protected function defaultPrimaryButtonUrlForType(string $sectionType): string
    {
        return $sectionType === 'how_we_build' ? '#' : '';
    }

    protected function defaultPrimaryButtonVisibleForType(string $sectionType): bool
    {
        return $sectionType === 'how_we_build';
    }

    protected function defaultPrimaryButtonNewTabForType(string $sectionType): bool
    {
        return $sectionType === 'how_we_build';
    }

    protected function buildTemplatesScalarValues(string $code, array $content): array
    {
        return [
            'templatesSliderBuyLabelValue' => $this->stringValue($this->oldContentValue($code, 'buy_label', $content['buy_label'] ?? __('Buy Now'))),
            'templatesSliderPreviewLabelValue' => $this->stringValue(
                $this->oldContentValue(
                    $code,
                    'preview_label',
                    $content['preview_label'] ?? __('Live Preview'),
                ),
            ),
            'templatesSliderLimitValue' => $this->stringValue($this->oldContentValue($code, 'limit', $content['limit'] ?? '')),
            'templatesListingBreadcrumbLabelValue' => $this->stringValue(
                $this->oldContentValue(
                    $code,
                    'breadcrumb_label',
                    $content['breadcrumb_label'] ?? __('Templates'),
                ),
            ),
            'templatesListingAllCategoriesLabelValue' => $this->stringValue(
                $this->oldContentValue(
                    $code,
                    'all_categories_label',
                    $content['all_categories_label'] ?? __('All Hosting'),
                ),
            ),
            'templatesListingTypeLabelValue' => $this->stringValue($this->oldContentValue($code, 'type_label', $content['type_label'] ?? __('Type'))),
            'templatesListingBestSellersLabelValue' => $this->stringValue(
                $this->oldContentValue(
                    $code,
                    'best_sellers_label',
                    $content['best_sellers_label'] ?? __('Best Sellers'),
                ),
            ),
            'templatesListingPriceLabelValue' => $this->stringValue($this->oldContentValue($code, 'price_label', $content['price_label'] ?? __('Price'))),
            'templatesListingBuyLabelValue' => $this->stringValue($this->oldContentValue($code, 'buy_label', $content['buy_label'] ?? __('Buy Now'))),
            'templatesListingPreviewLabelValue' => $this->stringValue(
                $this->oldContentValue(
                    $code,
                    'preview_label',
                    $content['preview_label'] ?? __('Live Preview'),
                ),
            ),
            'templatesListingItemsPerPageValue' => $this->stringValue($this->oldContentValue($code, 'items_per_page', $content['items_per_page'] ?? '')),
        ];
    }

    protected function buildFooterScalarValues(string $code, array $content): array
    {
        return [
            'footerCopyrightValue' => $this->stringValue($this->oldContentValue($code, 'copyright', $content['copyright'] ?? '')),
            'footerLinkItems' => $this->resolveFooterLinkItems($code, $content),
        ];
    }

    protected function buildSocialScalarValues(string $code, array $content): array
    {
        return [
            'footerFacebookUrlValue' => $this->stringValue(
                $this->oldNestedContentValue(
                    $code,
                    'social_links.facebook',
                    data_get($content, 'social_links.facebook', ''),
                ),
            ),
            'footerInstagramUrlValue' => $this->stringValue(
                $this->oldNestedContentValue(
                    $code,
                    'social_links.instagram',
                    data_get($content, 'social_links.instagram', ''),
                ),
            ),
            'footerXUrlValue' => $this->stringValue(
                $this->oldNestedContentValue(
                    $code,
                    'social_links.x',
                    data_get($content, 'social_links.x', ''),
                ),
            ),
            'footerGithubUrlValue' => $this->stringValue(
                $this->oldNestedContentValue(
                    $code,
                    'social_links.github',
                    data_get($content, 'social_links.github', ''),
                ),
            ),
            'footerYoutubeUrlValue' => $this->stringValue(
                $this->oldNestedContentValue(
                    $code,
                    'social_links.youtube',
                    data_get($content, 'social_links.youtube', ''),
                ),
            ),
        ];
    }

    protected function buildMediaScalarValues(string $code, array $content): array
    {
        return [
            'headerLogoValue' => $this->oldContentValue($code, 'logo', $content['logo'] ?? null),
            'mediaUrlValue' => $this->stringValue($this->oldContentValue($code, 'media_url', $content['media_url'] ?? '')),
            'mediaTypeOld' => (string) $this->oldContentValue($code, 'media_type', $content['media_type'] ?? 'image'),
            'campaignIllustrationValue' => $this->oldContentValue($code, 'media_url', $content['media_url'] ?? null),
            'mobileAppImageOneValue' => $this->oldContentValue($code, 'image_one', $content['image_one'] ?? null),
            'mobileAppImageTwoValue' => $this->oldContentValue($code, 'image_two', $content['image_two'] ?? null),
            'mobileAppImageThreeValue' => $this->oldContentValue($code, 'image_three', $content['image_three'] ?? null),
            'designImageFourValue' => $this->oldContentValue($code, 'image_four', $content['image_four'] ?? null),
            'designImageFiveValue' => $this->oldContentValue($code, 'image_five', $content['image_five'] ?? null),
            'designImageSixValue' => $this->oldContentValue($code, 'image_six', $content['image_six'] ?? null),
        ];
    }

    protected function buildDomainsScalarValues(string $code, array $content): array
    {
        return [
            'domainsSearchHeadingValue' => $this->stringValue(
                $this->oldContentValue(
                    $code,
                    'search_heading',
                    $content['search_heading'] ?? __('Find your perfect Domain name'),
                ),
            ),
            'domainsInputPlaceholderValue' => $this->stringValue(
                $this->oldContentValue(
                    $code,
                    'input_placeholder',
                    $content['input_placeholder'] ?? __('enter your domain here...'),
                ),
            ),
        ];
    }

    protected function buildTechStackScalarValues(string $code, array $content): array
    {
        $techStackLogosValue = $this->oldContentValue($code, 'logos', $content['logos'] ?? []);

        return [
            'techStackLogosValue' => $techStackLogosValue,
            'techStackLogosValueForComponent' => is_array($techStackLogosValue)
                ? implode(',', array_filter(array_map('strval', $techStackLogosValue)))
                : (is_string($techStackLogosValue) ? $techStackLogosValue : ''),
        ];
    }

    protected function resolveHeroTitleValue(
        mixed $translation,
        array $content,
        string $typeLabel,
        bool $isTemplatesListingShowcase,
    ): string {
        $templatesListingLegacyTitle = trim((string) ($translation?->title ?? ''));
        $templatesListingInitialTitle = trim((string) ($content['title'] ?? ''));

        if ($isTemplatesListingShowcase && $templatesListingInitialTitle === '') {
            $templatesListingInitialTitle =
                $templatesListingLegacyTitle !== '' && $templatesListingLegacyTitle !== $typeLabel
                ? $templatesListingLegacyTitle
                : (string) __('TEMPLATE');
        }

        return $isTemplatesListingShowcase ? $templatesListingInitialTitle : (string) ($content['title'] ?? '');
    }

    protected function resolveFooterLinkItems(string $code, array $content): array
    {
        $footerLinks = $this->oldContentValue($code, 'footer_links');

        return collect(
            is_array($footerLinks)
                ? $footerLinks
                : (is_array($content['footer_links'] ?? null) ? $content['footer_links'] : [])
        )
            ->map(function ($item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $label = trim((string) ($item['label'] ?? ''));
                $url = trim((string) ($item['url'] ?? ''));

                if ($label === '' && $url === '') {
                    return null;
                }

                return [
                    'label' => $label,
                    'url' => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function oldTranslationValue(string $code, string $key, mixed $default = ''): mixed
    {
        return old("translations.$code.$key", $default);
    }

    protected function oldContentValue(string $code, string $key, mixed $default = ''): mixed
    {
        return $this->oldNestedContentValue($code, $key, $default);
    }

    protected function oldNestedContentValue(string $code, string $dotKey, mixed $default = ''): mixed
    {
        return old("translations.$code.content.$dotKey", $default);
    }

    protected function oldBooleanContentValue(string $code, string $dotKey, mixed $default = false): bool
    {
        return filter_var(
            $this->oldNestedContentValue($code, $dotKey, $default),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    protected function normalizeSelectedType(mixed $selectedType): string
    {
        if (! is_string($selectedType) || trim($selectedType) === '') {
            return 'hero_campaign';
        }

        return self::LEGACY_TYPE_ALIASES[$selectedType] ?? $selectedType;
    }

    protected function resolveDefaultLocale(iterable $languages): string
    {
        $localeCodes = Collection::make($languages)
            ->pluck('code')
            ->filter()
            ->values();

        return $localeCodes->contains(app()->getLocale())
            ? app()->getLocale()
            : ($localeCodes->first() ?? app()->getLocale());
    }

    protected function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
