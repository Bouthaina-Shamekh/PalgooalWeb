<?php

return [
    'defaults' => [
        'header' => 'default',
        'footer' => 'default',
    ],

    'headers' => [
        'default' => [
            'label' => 'Classic',
            'description' => 'Logo on the left, menu in the middle, actions on the right.',
            'preview' => 'assets/front-layouts/previews/headers/default.svg',
        ],
        'centered' => [
            'label' => 'Centered',
            'description' => 'Brand and actions on top, centered navigation underneath.',
            'preview' => 'assets/front-layouts/previews/headers/centered.svg',
        ],
        'split' => [
            'label' => 'Split Brand',
            'description' => 'Desktop menu on the left, brand centered, actions on the right.',
            'preview' => 'assets/front-layouts/previews/headers/split.svg',
        ],
        'purple_topbar' => [
            'label' => 'Purple Topbar',
            'description' => 'Announcement strip with social icons and a two-level navigation.',
            'preview' => 'assets/front-layouts/previews/headers/purple_topbar.svg',
        ],
    ],

    'footers' => [
        'default' => [
            'label' => 'Classic',
            'description' => 'Large marketing footer with CTA band, columns, and payments.',
            'preview' => 'assets/front-layouts/previews/footers/default.svg',
        ],
        'compact' => [
            'label' => 'Compact',
            'description' => 'Dense three-column footer focused on essentials.',
            'preview' => 'assets/front-layouts/previews/footers/compact.svg',
        ],
        'stacked' => [
            'label' => 'Stacked',
            'description' => 'Contact banner on top followed by a centered minimal footer.',
            'preview' => 'assets/front-layouts/previews/footers/stacked.svg',
        ],
        'palgoals_marketing' => [
            'label' => 'PalGoals Marketing',
            'description' => 'Three-column gray footer with pages links, payments, and social follow bar.',
            'preview' => 'assets/front-layouts/previews/footers/palgoals_marketing.svg',
        ],
    ],
];
