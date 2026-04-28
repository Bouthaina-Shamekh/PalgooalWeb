<?php

namespace App\Support\Sections;

class SectionEditorTypeCapabilities
{
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
        // Shell editor legacy compatibility only.
        'site_header' => [
            'usesInternalLabel' => true,
            'flags' => [
                'showPrimaryButtonFields' => true,
                'showSubtitleField' => false,
            ],
        ],
        // Shell editor legacy compatibility only.
        'site_footer' => [
            'usesInternalLabel' => true,
            'flags' => [
                'showSubtitleField' => false,
                'showMainTitleField' => false,
                'showSiteFooterLinksTextareaField' => true,
                'showSiteFooterSocialFields' => true,
            ],
        ],
    ];

    /**
     * Whether the shell compatibility editor has explicit type-specific support.
     */
    public function supports(string $type): bool
    {
        return array_key_exists($type, self::TYPE_CONFIG);
    }

    public function for(string $type): array
    {
        $config = self::TYPE_CONFIG[$type] ?? [];

        return [
            'usesInternalLabel' => (bool) ($config['usesInternalLabel'] ?? false),
            'flags' => array_replace(self::FIELD_FLAG_DEFAULTS, $config['flags'] ?? []),
        ];
    }
}
