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
        'fallback_view' => 'front.sections._missing-template',
        'templates' => [
            'portfolio_slider' => [
                'label' => 'Portfolio Slider',
                'view' => 'front.sections.portfolio.portfolio_slider',
                'category' => 'portfolio',
            ],
        ],
    ],
];
