<?php

namespace App\Support\Sections;

class SectionEditorTypeCapabilities
{
    protected const TYPE_FLAG_DEFAULTS = [
        'isHeroCampaign' => false,
        'isProgrammingShowcase' => false,
        'isMobileAppShowcase' => false,
        'isDesignShowcase' => false,
        'isDigitalMarketingShowcase' => false,
        'isTechStackShowcase' => false,
        'isReviewsShowcase' => false,
        'isOurWorkShowcase' => false,
        'isHostingPricingShowcase' => false,
        'isDomainsShowcase' => false,
        'isTemplatesSliderShowcase' => false,
        'isTemplatesListingShowcase' => false,
        'isSimpleHero' => false,
        'isSimpleFeatures' => false,
        'isSimpleCta' => false,
        'isSiteHeader' => false,
        'isSiteFooter' => false,
        'isSimpleTestimonials' => false,
        'isSimpleFaq' => false,
    ];

    protected const FIELD_FLAG_DEFAULTS = [
        'showEyebrowField' => false,
        'showDescriptionField' => false,
        'showFeaturesHeadingField' => false,
        'showOutputsHeadingField' => false,
        'showOutputsTextareaField' => false,
        'showServicesTextareaField' => false,
        'showBrandFields' => false,
        'showPrimaryButtonFields' => false,
        'showSecondaryButtonFields' => false,
        'showFeatureRepeaterField' => false,
        'showBuildStepsRepeaterField' => false,
        'showReviewsDatabaseField' => false,
        'showOurWorkDatabaseField' => false,
        'showHostingPricingCategoriesField' => false,
        'showHostingPricingPlansField' => false,
        'showHostingPricingDatabaseField' => false,
        'showTemplatesSliderDatabaseField' => false,
        'showTemplatesListingDatabaseField' => false,
        'showFeaturesTextareaField' => false,
        'showMobileAppGalleryField' => false,
        'showDesignGalleryField' => false,
        'showDigitalMarketingGalleryField' => false,
        'showTechStackMediaField' => false,
        'showMediaTypeField' => false,
        'showMediaUrlField' => false,
        'showSubtitleField' => true,
        'showMainTitleField' => true,
        'showDomainsSearchHeadingField' => false,
        'showDomainsPlaceholderField' => false,
        'showFaqItemsTextareaField' => false,
        'showReviewRepeaterField' => false,
        'showSiteFooterLinksTextareaField' => false,
        'showSiteFooterSocialFields' => false,
    ];

    protected const TYPE_CONFIG = [
        'hero' => [
            'typeFlags' => ['isSimpleHero' => true],
            'flags' => [
                'showEyebrowField' => true,
                'showPrimaryButtonFields' => true,
                'showSecondaryButtonFields' => true,
                'showFeaturesTextareaField' => true,
                'showMediaTypeField' => true,
                'showMediaUrlField' => true,
            ],
        ],
        'hero_default' => [
            'flags' => [
                'showEyebrowField' => true,
                'showPrimaryButtonFields' => true,
                'showSecondaryButtonFields' => true,
                'showFeaturesTextareaField' => true,
                'showMediaTypeField' => true,
                'showMediaUrlField' => true,
            ],
        ],
        'hero_minimal' => [
            'flags' => [
                'showPrimaryButtonFields' => true,
            ],
        ],
        'hero_campaign' => [
            'typeFlags' => ['isHeroCampaign' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showDescriptionField' => true,
                'showFeaturesHeadingField' => true,
                'showPrimaryButtonFields' => true,
                'showFeatureRepeaterField' => true,
                'showMediaUrlField' => true,
            ],
        ],
        'programming_showcase' => [
            'typeFlags' => ['isProgrammingShowcase' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showDescriptionField' => true,
                'showOutputsHeadingField' => true,
                'showOutputsTextareaField' => true,
                'showBrandFields' => true,
                'showPrimaryButtonFields' => true,
                'showMediaUrlField' => true,
                'showSubtitleField' => false,
            ],
        ],
        'mobile_app_showcase' => [
            'typeFlags' => ['isMobileAppShowcase' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showDescriptionField' => true,
                'showBrandFields' => true,
                'showPrimaryButtonFields' => true,
                'showMobileAppGalleryField' => true,
                'showSubtitleField' => false,
            ],
        ],
        'how_we_build' => [
            'flags' => [
                'showBuildStepsRepeaterField' => true,
            ],
        ],
        'design_showcase' => [
            'typeFlags' => ['isDesignShowcase' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showDescriptionField' => true,
                'showServicesTextareaField' => true,
                'showBrandFields' => true,
                'showPrimaryButtonFields' => true,
                'showDesignGalleryField' => true,
                'showSubtitleField' => false,
            ],
        ],
        'digital_marketing_showcase' => [
            'typeFlags' => ['isDigitalMarketingShowcase' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showServicesTextareaField' => true,
                'showBrandFields' => true,
                'showPrimaryButtonFields' => true,
                'showDigitalMarketingGalleryField' => true,
                'showSubtitleField' => false,
            ],
        ],
        'tech_stack_showcase' => [
            'typeFlags' => ['isTechStackShowcase' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showTechStackMediaField' => true,
                'showSubtitleField' => false,
                'showMainTitleField' => false,
            ],
        ],
        'reviews_showcase' => [
            'typeFlags' => ['isReviewsShowcase' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showDescriptionField' => true,
                'showBrandFields' => true,
                'showReviewsDatabaseField' => true,
                'showSubtitleField' => false,
            ],
        ],
        'our_work_showcase' => [
            'typeFlags' => ['isOurWorkShowcase' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showDescriptionField' => true,
                'showBrandFields' => true,
                'showOurWorkDatabaseField' => true,
                'showSubtitleField' => false,
            ],
        ],
        'hosting_pricing_showcase' => [
            'typeFlags' => ['isHostingPricingShowcase' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showDescriptionField' => true,
                'showHostingPricingDatabaseField' => true,
                'showSubtitleField' => false,
            ],
        ],
        'domains_showcase' => [
            'typeFlags' => ['isDomainsShowcase' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showDescriptionField' => true,
                'showBrandFields' => true,
                'showPrimaryButtonFields' => true,
                'showDomainsSearchHeadingField' => true,
                'showDomainsPlaceholderField' => true,
                'showSubtitleField' => false,
            ],
        ],
        'templates_slider_showcase' => [
            'typeFlags' => ['isTemplatesSliderShowcase' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showDescriptionField' => true,
                'showBrandFields' => true,
                'showTemplatesSliderDatabaseField' => true,
                'showSubtitleField' => false,
            ],
        ],
        'templates_listing_showcase' => [
            'typeFlags' => ['isTemplatesListingShowcase' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showDescriptionField' => true,
                'showTemplatesListingDatabaseField' => true,
                'showSubtitleField' => false,
            ],
        ],
        'features' => [
            'typeFlags' => ['isSimpleFeatures' => true],
            'flags' => [
                'showFeaturesTextareaField' => true,
            ],
        ],
        'features_grid' => [
            'flags' => [
                'showFeaturesTextareaField' => true,
            ],
        ],
        'cta' => [
            'typeFlags' => ['isSimpleCta' => true],
            'flags' => [
                'showEyebrowField' => true,
                'showPrimaryButtonFields' => true,
            ],
        ],
        'site_header' => [
            'typeFlags' => ['isSiteHeader' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showPrimaryButtonFields' => true,
                'showSubtitleField' => false,
            ],
        ],
        'site_footer' => [
            'typeFlags' => ['isSiteFooter' => true],
            'usesInternalLabel' => true,
            'flags' => [
                'showSubtitleField' => false,
                'showMainTitleField' => false,
                'showSiteFooterLinksTextareaField' => true,
                'showSiteFooterSocialFields' => true,
            ],
        ],
        'testimonials' => [
            'typeFlags' => ['isSimpleTestimonials' => true],
            'flags' => [
                'showEyebrowField' => true,
                'showDescriptionField' => true,
                'showSubtitleField' => false,
                'showReviewRepeaterField' => true,
            ],
        ],
        'faq' => [
            'typeFlags' => ['isSimpleFaq' => true],
            'flags' => [
                'showFaqItemsTextareaField' => true,
            ],
        ],
    ];

    public function for(string $type): array
    {
        $config = self::TYPE_CONFIG[$type] ?? [];

        return [
            'typeFlags' => array_replace(self::TYPE_FLAG_DEFAULTS, $config['typeFlags'] ?? []),
            'usesInternalLabel' => (bool) ($config['usesInternalLabel'] ?? false),
            'flags' => array_replace(self::FIELD_FLAG_DEFAULTS, $config['flags'] ?? []),
        ];
    }
}
