<?php

return [
    'icon_library' => [
        ['label' => 'Template', 'value' => 'ti ti-layout-grid', 'keywords' => 'template layout grid blocks cards'],
        ['label' => 'Hosting', 'value' => 'ti ti-server', 'keywords' => 'hosting server infrastructure cloud'],
        ['label' => 'Settings', 'value' => 'ti ti-settings', 'keywords' => 'settings config options setup'],
        ['label' => 'Mail', 'value' => 'ti ti-mail', 'keywords' => 'mail email message inbox'],
        ['label' => 'Domain', 'value' => 'ti ti-world', 'keywords' => 'domain world globe website internet'],
        ['label' => 'Support', 'value' => 'ti ti-headset', 'keywords' => 'support help service call'],
        ['label' => 'Analysis', 'value' => 'ti ti-search', 'keywords' => 'analysis inspect research search audit'],
        ['label' => 'Design', 'value' => 'ti ti-palette', 'keywords' => 'design palette creative colors'],
        ['label' => 'Development', 'value' => 'ti ti-code', 'keywords' => 'development code programming engineering'],
        ['label' => 'Testing', 'value' => 'ti ti-test-pipe', 'keywords' => 'testing qa quality review bug'],
        ['label' => 'Launch', 'value' => 'ti ti-rocket', 'keywords' => 'launch publish release growth'],
        ['label' => 'Mobile', 'value' => 'ti ti-device-mobile', 'keywords' => 'mobile phone app smartphone'],
        ['label' => 'Desktop', 'value' => 'ti ti-device-desktop', 'keywords' => 'desktop web laptop monitor'],
        ['label' => 'Marketing', 'value' => 'ti ti-speakerphone', 'keywords' => 'marketing campaign ads announce'],
        ['label' => 'Store', 'value' => 'ti ti-shopping-cart', 'keywords' => 'store shop ecommerce cart'],
        ['label' => 'Business', 'value' => 'ti ti-briefcase', 'keywords' => 'business company service work'],
        ['label' => 'Team', 'value' => 'ti ti-users', 'keywords' => 'team users people clients'],
        ['label' => 'Client', 'value' => 'ti ti-user-star', 'keywords' => 'client customer testimonial review'],
        ['label' => 'Message', 'value' => 'ti ti-message-circle', 'keywords' => 'message comment chat feedback'],
        ['label' => 'Checklist', 'value' => 'ti ti-checklist', 'keywords' => 'checklist tasks process steps'],
        ['label' => 'Package', 'value' => 'ti ti-package', 'keywords' => 'package box shipping product'],
        ['label' => 'Box', 'value' => 'ti ti-box', 'keywords' => 'box package product item'],
        ['label' => 'Shield', 'value' => 'ti ti-shield-check', 'keywords' => 'shield security trust safe'],
        ['label' => 'Lightning', 'value' => 'ti ti-bolt', 'keywords' => 'fast speed bolt performance'],
        ['label' => 'Image', 'value' => 'ti ti-photo', 'keywords' => 'image photo gallery media'],
        ['label' => 'Brush', 'value' => 'ti ti-brush', 'keywords' => 'brush design art branding'],
        ['label' => 'Apps', 'value' => 'ti ti-apps', 'keywords' => 'apps modules collection tools'],
        ['label' => 'Building', 'value' => 'ti ti-building-store', 'keywords' => 'building store office branch'],
        ['label' => 'Chart', 'value' => 'ti ti-chart-bar', 'keywords' => 'chart analytics data metrics'],
        ['label' => 'Seo', 'value' => 'ti ti-chart-arrows-vertical', 'keywords' => 'seo rank growth analytics'],
    ],

    'template_registry' => [
        'fallback_view' => 'components.template.sections._missing-template',

        'templates' => [
            'banner' => [
                'label' => 'Banner',
                'view' => 'components.template.sections.banner',
                'category' => 'marketing',
            ],
            'blog' => [
                'label' => 'Blog',
                'view' => 'components.template.sections.blog',
                'category' => 'content',
            ],
            'cta' => [
                'label' => 'CTA',
                'view' => 'components.template.sections.cta',
                'category' => 'cta',
            ],
            'design_showcase' => [
                'label' => 'Design Showcase',
                'view' => 'components.template.sections.design_showcase',
                'category' => 'services',
            ],
            'digital_marketing_showcase' => [
                'label' => 'Digital Marketing Showcase',
                'view' => 'components.template.sections.digital_marketing_showcase',
                'category' => 'services',
            ],
            'domains_showcase' => [
                'label' => 'Domains Showcase',
                'view' => 'components.template.sections.domains_showcase',
                'category' => 'pricing',
            ],
            'faq' => [
                'label' => 'FAQ',
                'view' => 'components.template.sections.faq',
                'category' => 'content',
            ],
            'features' => [
                'label' => 'Features',
                'view' => 'components.template.sections.features',
                'category' => 'content',
            ],
            'features_2' => [
                'label' => 'Features 2',
                'view' => 'components.template.sections.features-2',
                'category' => 'content',
            ],
            'features_3' => [
                'label' => 'Features 3',
                'view' => 'components.template.sections.features-3',
                'category' => 'content',
            ],
            'hero' => [
                'label' => 'Hero',
                'view' => 'components.template.sections.hero',
                'category' => 'hero',
            ],
            'hero_campaign' => [
                'label' => 'Hero Campaign',
                'view' => 'components.template.sections.hero_campaign',
                'category' => 'hero',
            ],
            'hero_default' => [
                'label' => 'Hero Default',
                'view' => 'components.template.sections.hero_default',
                'category' => 'hero',
            ],
            'hosting_hero' => [
                'label' => 'Hosting Hero',
                'view' => 'front.sections.hero.hosting',
            ],
            'home_works' => [
                'label' => 'Home Works',
                'view' => 'components.template.sections.home-works',
                'category' => 'portfolio',
            ],
            'hosting_pricing_showcase' => [
                'label' => 'Hosting Pricing Showcase',
                'view' => 'components.template.sections.hosting_pricing_showcase',
                'category' => 'pricing',
            ],
            'how_we_build' => [
                'label' => 'How We Build',
                'view' => 'components.template.sections.how_we_build',
                'category' => 'content',
            ],
            'mobile_app_showcase' => [
                'label' => 'Mobile App Showcase',
                'view' => 'components.template.sections.mobile_app_showcase',
                'category' => 'services',
            ],
            'our_work_showcase' => [
                'label' => 'Our Work Showcase',
                'view' => 'components.template.sections.our_work_showcase',
                'category' => 'portfolio',
            ],
            'programming_showcase' => [
                'label' => 'Programming Showcase',
                'view' => 'components.template.sections.programming_showcase',
                'category' => 'services',
            ],
            'reviews_showcase' => [
                'label' => 'Reviews Showcase',
                'view' => 'components.template.sections.reviews_showcase',
                'category' => 'social',
            ],
            'search_domain' => [
                'label' => 'Search Domain',
                'view' => 'components.template.sections.search-domain',
                'category' => 'pricing',
            ],
            'services' => [
                'label' => 'Services',
                'view' => 'components.template.sections.services',
                'category' => 'content',
            ],
            'tech_stack_showcase' => [
                'label' => 'Tech Stack Showcase',
                'view' => 'components.template.sections.tech_stack_showcase',
                'category' => 'content',
            ],
            'templates' => [
                'label' => 'Templates',
                'view' => 'components.template.sections.templates',
                'category' => 'templates',
            ],
            'templates_listing_showcase' => [
                'label' => 'Templates Listing Showcase',
                'view' => 'components.template.sections.templates_listing_showcase',
                'category' => 'templates',
            ],
            'templates_slider_showcase' => [
                'label' => 'Templates Slider Showcase',
                'view' => 'components.template.sections.templates_slider_showcase',
                'category' => 'templates',
            ],
            'testimonials' => [
                'label' => 'Testimonials',
                'view' => 'components.template.sections.testimonials',
                'category' => 'social',
            ],
            'works' => [
                'label' => 'Works',
                'view' => 'components.template.sections.works',
                'category' => 'portfolio',
            ],

            'wordpress_ai_promo' => [
                'label' => 'WordPress AI Promo',
                'view' => 'front.sections.promo.wordpress_ai_promo',
            ],
        ],
    ],

    'custom_preset_registry' => [
        'presets' => [
            'hosting_hero' => [
                'label' => 'Hosting Hero',
                'view' => 'dashboard.pages.sections.partials.custom-presets.hosting-hero',
                'builder' => 'buildHostingHeroPreset',
            ],

            'wordpress_ai_promo' => [
                'label' => 'WordPress AI Promo',
                'view' => 'dashboard.pages.sections.partials.custom-presets.wordpress-ai-promo',
                'builder' => 'buildWordPressAIPromoPreset',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Temporary legacy bridges
        |--------------------------------------------------------------------------
        | These mappings allow already-linked definitions to keep using a
        | custom preset editor before their editor_mode/custom_editor_key
        | values are formally backfilled in the database.
        */
        'legacy_section_key_bridge' => [
            'hosting_hero' => 'hosting_hero',
        ],
    ],
];
