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

    public function __construct(protected SectionEditorRepeaterFactory $repeaterFactory)
    {
    }

    public function make(Section $section, iterable $languages, array $sectionTypes = []): array
    {
        $selectedType = $this->normalizeSelectedType(old('type', $section->type));
        $typeLabel = $sectionTypes[$selectedType]['label']
            ?? Str::headline(str_replace(['_', '-'], ' ', $selectedType));

        $isHeroCampaign = $selectedType === 'hero_campaign';
        $isProgrammingShowcase = $selectedType === 'programming_showcase';
        $isMobileAppShowcase = $selectedType === 'mobile_app_showcase';
        $isDesignShowcase = $selectedType === 'design_showcase';
        $isDigitalMarketingShowcase = $selectedType === 'digital_marketing_showcase';
        $isTechStackShowcase = $selectedType === 'tech_stack_showcase';
        $isReviewsShowcase = $selectedType === 'reviews_showcase';
        $isOurWorkShowcase = $selectedType === 'our_work_showcase';
        $isHostingPricingShowcase = $selectedType === 'hosting_pricing_showcase';
        $isDomainsShowcase = $selectedType === 'domains_showcase';
        $isTemplatesSliderShowcase = $selectedType === 'templates_slider_showcase';
        $isTemplatesListingShowcase = $selectedType === 'templates_listing_showcase';

        return [
            'selectedType' => $selectedType,
            'defaultLocale' => $this->resolveDefaultLocale($languages),
            'typeFlags' => [
                'isHeroCampaign' => $isHeroCampaign,
                'isProgrammingShowcase' => $isProgrammingShowcase,
                'isMobileAppShowcase' => $isMobileAppShowcase,
                'isDesignShowcase' => $isDesignShowcase,
                'isDigitalMarketingShowcase' => $isDigitalMarketingShowcase,
                'isTechStackShowcase' => $isTechStackShowcase,
                'isReviewsShowcase' => $isReviewsShowcase,
                'isOurWorkShowcase' => $isOurWorkShowcase,
                'isHostingPricingShowcase' => $isHostingPricingShowcase,
                'isDomainsShowcase' => $isDomainsShowcase,
                'isTemplatesSliderShowcase' => $isTemplatesSliderShowcase,
                'isTemplatesListingShowcase' => $isTemplatesListingShowcase,
            ],
            'usesInternalLabel' =>
            $isHeroCampaign ||
                $isProgrammingShowcase ||
                $isMobileAppShowcase ||
                $isDesignShowcase ||
                $isDigitalMarketingShowcase ||
                $isTechStackShowcase ||
                $isReviewsShowcase ||
                $isOurWorkShowcase ||
                $isHostingPricingShowcase ||
                $isDomainsShowcase ||
                $isTemplatesSliderShowcase ||
                $isTemplatesListingShowcase,
            'flags' => [
                'showEyebrowField' => $selectedType === 'hero_default',
                'showDescriptionField' =>
                $isHeroCampaign ||
                    $isProgrammingShowcase ||
                    $isMobileAppShowcase ||
                    $isDesignShowcase ||
                    $isReviewsShowcase ||
                    $isOurWorkShowcase ||
                    $isHostingPricingShowcase ||
                    $isDomainsShowcase ||
                    $isTemplatesSliderShowcase ||
                    $isTemplatesListingShowcase,
                'showFeaturesHeadingField' => $isHeroCampaign,
                'showOutputsHeadingField' => $isProgrammingShowcase,
                'showOutputsTextareaField' => $isProgrammingShowcase,
                'showServicesTextareaField' => $isDesignShowcase || $isDigitalMarketingShowcase,
                'showBrandFields' =>
                $isProgrammingShowcase ||
                    $isMobileAppShowcase ||
                    $isDesignShowcase ||
                    $isDigitalMarketingShowcase ||
                    $isReviewsShowcase ||
                    $isOurWorkShowcase ||
                    $isDomainsShowcase ||
                    $isTemplatesSliderShowcase,
                'showPrimaryButtonFields' => ! in_array(
                    $selectedType,
                    [
                        'how_we_build',
                        'tech_stack_showcase',
                        'reviews_showcase',
                        'our_work_showcase',
                        'hosting_pricing_showcase',
                        'templates_slider_showcase',
                        'templates_listing_showcase',
                    ],
                    true,
                ),
                'showSecondaryButtonFields' => $selectedType === 'hero_default',
                'showFeatureRepeaterField' => $isHeroCampaign,
                'showBuildStepsRepeaterField' => $selectedType === 'how_we_build',
                'showReviewsDatabaseField' => $isReviewsShowcase,
                'showOurWorkDatabaseField' => $isOurWorkShowcase,
                'showHostingPricingCategoriesField' => false,
                'showHostingPricingPlansField' => false,
                'showHostingPricingDatabaseField' => $isHostingPricingShowcase,
                'showTemplatesSliderDatabaseField' => $isTemplatesSliderShowcase,
                'showTemplatesListingDatabaseField' => $isTemplatesListingShowcase,
                'showFeaturesTextareaField' => in_array($selectedType, ['hero_default', 'features_grid'], true),
                'showMobileAppGalleryField' => $isMobileAppShowcase,
                'showDesignGalleryField' => $isDesignShowcase,
                'showDigitalMarketingGalleryField' => $isDigitalMarketingShowcase,
                'showTechStackMediaField' => $isTechStackShowcase,
                'showMediaTypeField' => $selectedType === 'hero_default',
                'showMediaUrlField' => in_array(
                    $selectedType,
                    ['hero_default', 'hero_campaign', 'programming_showcase'],
                    true,
                ),
                'showSubtitleField' => ! in_array(
                    $selectedType,
                    [
                        'programming_showcase',
                        'mobile_app_showcase',
                        'design_showcase',
                        'digital_marketing_showcase',
                        'tech_stack_showcase',
                        'reviews_showcase',
                        'our_work_showcase',
                        'hosting_pricing_showcase',
                        'domains_showcase',
                        'templates_slider_showcase',
                        'templates_listing_showcase',
                    ],
                    true,
                ),
                'showMainTitleField' => ! $isTechStackShowcase,
                'showDomainsSearchHeadingField' => $isDomainsShowcase,
                'showDomainsPlaceholderField' => $isDomainsShowcase,
            ],
            'hostingPricingAvailableCategories' => $isHostingPricingShowcase
                ? PlanCategory::query()->active()->ordered()->with('translations')->get()
                : collect(),
            'localeScalarValues' => $this->buildLocaleScalarValues(
                $section,
                $languages,
                $selectedType,
                $typeLabel,
                $isTemplatesListingShowcase,
            ),
            'localeCampaignFeatureItems' => $isHeroCampaign
                ? $this->repeaterFactory->buildLocaleCampaignFeatureItems($section, $languages)
                : [],
            'localeOutputItems' => $isProgrammingShowcase
                ? $this->repeaterFactory->buildLocaleOutputItems($section, $languages)
                : [],
            'localeServiceItems' => $isDesignShowcase || $isDigitalMarketingShowcase
                ? $this->repeaterFactory->buildLocaleServiceItems($section, $languages)
                : [],
            'localeBuildStepItems' => $selectedType === 'how_we_build'
                ? $this->repeaterFactory->buildLocaleBuildStepItems($section, $languages)
                : [],
            'localePricingCategoryItems' => $isHostingPricingShowcase
                ? $this->repeaterFactory->buildLocalePricingCategoryItems($section, $languages)
                : [],
            'localePricingPlanItems' => $isHostingPricingShowcase
                ? $this->repeaterFactory->buildLocalePricingPlanItems($section, $languages)
                : [],
        ];
    }

    protected function buildLocaleScalarValues(
        Section $section,
        iterable $languages,
        string $selectedType,
        string $typeLabel,
        bool $isTemplatesListingShowcase,
    ): array {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section, $selectedType, $typeLabel, $isTemplatesListingShowcase) {
                $code = $language->code;
                $translation = $section->translations->firstWhere('locale', $code);
                $content = is_array($translation?->content) ? $translation->content : [];
                $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];
                $secondaryButton = is_array($content['secondary_button'] ?? null) ? $content['secondary_button'] : [];

                $templatesListingLegacyTitle = trim((string) ($translation?->title ?? ''));
                $templatesListingInitialTitle = trim((string) ($content['title'] ?? ''));

                if ($isTemplatesListingShowcase && $templatesListingInitialTitle === '') {
                    $templatesListingInitialTitle =
                        $templatesListingLegacyTitle !== '' && $templatesListingLegacyTitle !== $typeLabel
                        ? $templatesListingLegacyTitle
                        : (string) __('TEMPLATE');
                }

                $techStackLogosValue = old("translations.$code.content.logos", $content['logos'] ?? []);

                return [
                    $code => [
                        'sectionTitleValue' => $this->stringValue(old("translations.$code.title", $translation?->title ?? '')),
                        'eyebrowValue' => $this->stringValue(
                            old("translations.$code.content.eyebrow", $content['eyebrow'] ?? ''),
                        ),
                        'heroTitleValue' => $this->stringValue(
                            old(
                                "translations.$code.content.title",
                                $isTemplatesListingShowcase ? $templatesListingInitialTitle : ($content['title'] ?? ''),
                            ),
                        ),
                        'brandPrefixValue' => $this->stringValue(
                            old("translations.$code.content.brand_prefix", $content['brand_prefix'] ?? ''),
                        ),
                        'brandSuffixValue' => $this->stringValue(
                            old("translations.$code.content.brand_suffix", $content['brand_suffix'] ?? ''),
                        ),
                        'subtitleValue' => $this->stringValue(
                            old("translations.$code.content.subtitle", $content['subtitle'] ?? ''),
                        ),
                        'descriptionValue' => $this->stringValue(
                            old("translations.$code.content.description", $content['description'] ?? ''),
                        ),
                        'hostingPricingButtonLabelValue' => $this->stringValue(
                            old("translations.$code.content.button_label", $content['button_label'] ?? __('Choose Now')),
                        ),
                        'domainsSearchHeadingValue' => $this->stringValue(
                            old(
                                "translations.$code.content.search_heading",
                                $content['search_heading'] ?? __('Find your perfect Domain name'),
                            ),
                        ),
                        'domainsInputPlaceholderValue' => $this->stringValue(
                            old(
                                "translations.$code.content.input_placeholder",
                                $content['input_placeholder'] ?? __('enter your domain here...'),
                            ),
                        ),
                        'templatesSliderBuyLabelValue' => $this->stringValue(
                            old("translations.$code.content.buy_label", $content['buy_label'] ?? __('Buy Now')),
                        ),
                        'templatesSliderPreviewLabelValue' => $this->stringValue(
                            old(
                                "translations.$code.content.preview_label",
                                $content['preview_label'] ?? __('Live Preview'),
                            ),
                        ),
                        'templatesSliderLimitValue' => $this->stringValue(
                            old("translations.$code.content.limit", $content['limit'] ?? ''),
                        ),
                        'templatesListingBreadcrumbLabelValue' => $this->stringValue(
                            old(
                                "translations.$code.content.breadcrumb_label",
                                $content['breadcrumb_label'] ?? __('Templates'),
                            ),
                        ),
                        'templatesListingAllCategoriesLabelValue' => $this->stringValue(
                            old(
                                "translations.$code.content.all_categories_label",
                                $content['all_categories_label'] ?? __('All Hosting'),
                            ),
                        ),
                        'templatesListingTypeLabelValue' => $this->stringValue(
                            old("translations.$code.content.type_label", $content['type_label'] ?? __('Type')),
                        ),
                        'templatesListingBestSellersLabelValue' => $this->stringValue(
                            old(
                                "translations.$code.content.best_sellers_label",
                                $content['best_sellers_label'] ?? __('Best Sellers'),
                            ),
                        ),
                        'templatesListingPriceLabelValue' => $this->stringValue(
                            old("translations.$code.content.price_label", $content['price_label'] ?? __('Price')),
                        ),
                        'templatesListingBuyLabelValue' => $this->stringValue(
                            old("translations.$code.content.buy_label", $content['buy_label'] ?? __('Buy Now')),
                        ),
                        'templatesListingPreviewLabelValue' => $this->stringValue(
                            old(
                                "translations.$code.content.preview_label",
                                $content['preview_label'] ?? __('Live Preview'),
                            ),
                        ),
                        'templatesListingItemsPerPageValue' => $this->stringValue(
                            old("translations.$code.content.items_per_page", $content['items_per_page'] ?? ''),
                        ),
                        'featuresHeadingValue' => $this->stringValue(
                            old("translations.$code.content.features_heading", $content['features_heading'] ?? ''),
                        ),
                        'outputsHeadingValue' => $this->stringValue(
                            old("translations.$code.content.outputs_heading", $content['outputs_heading'] ?? ''),
                        ),
                        'primaryButtonLabelValue' => $this->stringValue(
                            old("translations.$code.content.primary_button.label", $primaryButton['label'] ?? ''),
                        ),
                        'primaryButtonUrlValue' => $this->stringValue(
                            old("translations.$code.content.primary_button.url", $primaryButton['url'] ?? ''),
                        ),
                        'primaryButtonNewTabValue' => filter_var(
                            old(
                                "translations.$code.content.primary_button.new_tab",
                                $primaryButton['new_tab'] ?? false,
                            ),
                            FILTER_VALIDATE_BOOLEAN,
                        ),
                        'secondaryButtonLabelValue' => $this->stringValue(
                            old("translations.$code.content.secondary_button.label", $secondaryButton['label'] ?? ''),
                        ),
                        'secondaryButtonUrlValue' => $this->stringValue(
                            old("translations.$code.content.secondary_button.url", $secondaryButton['url'] ?? ''),
                        ),
                        'reviewsLimitValue' => $this->stringValue(
                            old("translations.$code.content.limit", $content['limit'] ?? ''),
                        ),
                        'ourWorkLimitValue' => $this->stringValue(
                            old("translations.$code.content.limit", $content['limit'] ?? ''),
                        ),
                        'ourWorkVisitLabelValue' => $this->stringValue(
                            old("translations.$code.content.visit_label", $content['visit_label'] ?? ''),
                        ),
                        'mediaUrlValue' => $this->stringValue(
                            old("translations.$code.content.media_url", $content['media_url'] ?? ''),
                        ),
                        'mediaTypeOld' => (string) old(
                            "translations.$code.content.media_type",
                            $content['media_type'] ?? 'image',
                        ),
                        'campaignIllustrationValue' => old(
                            "translations.$code.content.media_url",
                            $content['media_url'] ?? null,
                        ),
                        'mobileAppImageOneValue' => old(
                            "translations.$code.content.image_one",
                            $content['image_one'] ?? null,
                        ),
                        'mobileAppImageTwoValue' => old(
                            "translations.$code.content.image_two",
                            $content['image_two'] ?? null,
                        ),
                        'mobileAppImageThreeValue' => old(
                            "translations.$code.content.image_three",
                            $content['image_three'] ?? null,
                        ),
                        'designImageFourValue' => old(
                            "translations.$code.content.image_four",
                            $content['image_four'] ?? null,
                        ),
                        'designImageFiveValue' => old(
                            "translations.$code.content.image_five",
                            $content['image_five'] ?? null,
                        ),
                        'designImageSixValue' => old(
                            "translations.$code.content.image_six",
                            $content['image_six'] ?? null,
                        ),
                        'techStackLogosValue' => $techStackLogosValue,
                        'techStackLogosValueForComponent' => is_array($techStackLogosValue)
                            ? implode(',', array_filter(array_map('strval', $techStackLogosValue)))
                            : (is_string($techStackLogosValue) ? $techStackLogosValue : ''),
                    ],
                ];
            })
            ->all();
    }

    protected function normalizeSelectedType(mixed $selectedType): string
    {
        if (! is_string($selectedType) || trim($selectedType) === '') {
            return 'hero_default';
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
