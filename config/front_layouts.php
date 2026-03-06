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
            'preview' => 'assets/front-layouts/previews/headers/Classic.png',
        ],
        'centered' => [
            'label' => 'Centered',
            'description' => 'Brand and actions on top, centered navigation underneath.',
            'preview' => 'assets/front-layouts/previews/headers/Centered.png',
        ],
        'split' => [
            'label' => 'Split Brand',
            'description' => 'Desktop menu on the left, brand centered, actions on the right.',
            'preview' => 'assets/front-layouts/previews/headers/split.png',
        ],
        'purple_topbar' => [
            'label' => 'Purple Topbar',
            'description' => 'Announcement strip with social icons and a two-level navigation.',
            'preview' => 'assets/front-layouts/previews/headers/Purple-Topbar.png',
        ],
    ],

    'color_libraries' => [
        'purple_topbar' => [
            'default' => 'classic',
            'themes' => [
                'classic' => [
                    'label' => 'Classic Purple',
                    'classes' => [
                        'promo_bar' => 'bg-purple-brand text-white',
                        'promo_hover' => 'hover:text-red-brand',
                        'social_icon' => 'fill-[#7F6F8A] hover:fill-red-brand',
                        'nav_shell' => 'bg-white border-gray-100',
                        'nav_text' => 'text-black',
                        'nav_hover' => 'hover:text-red-brand',
                        'dropdown_shell' => 'bg-white border-gray-200',
                        'dropdown_item' => 'text-black hover:bg-gray-100 hover:text-red-brand',
                        'contact_btn' => 'text-purple-brand border-red-brand hover:bg-red-brand hover:text-white',
                        'hamburger_bar' => 'bg-purple-brand',
                        'mobile_panel' => 'bg-white',
                        'mobile_link_border' => 'border-gray-100',
                        'mobile_subtext' => 'text-gray-dark',
                    ],
                ],
                'slate' => [
                    'label' => 'Slate Cyan',
                    'classes' => [
                        'promo_bar' => 'bg-slate-900 text-slate-100',
                        'promo_hover' => 'hover:text-cyan-300',
                        'social_icon' => 'fill-slate-300 hover:fill-cyan-300',
                        'nav_shell' => 'bg-slate-50 border-slate-200',
                        'nav_text' => 'text-slate-800',
                        'nav_hover' => 'hover:text-cyan-700',
                        'dropdown_shell' => 'bg-white border-slate-200',
                        'dropdown_item' => 'text-slate-800 hover:bg-slate-100 hover:text-cyan-700',
                        'contact_btn' => 'text-cyan-700 border-cyan-700 hover:bg-cyan-700 hover:text-white',
                        'hamburger_bar' => 'bg-cyan-700',
                        'mobile_panel' => 'bg-slate-50',
                        'mobile_link_border' => 'border-slate-200',
                        'mobile_subtext' => 'text-slate-600',
                    ],
                ],
                'emerald' => [
                    'label' => 'Emerald Lime',
                    'classes' => [
                        'promo_bar' => 'bg-emerald-800 text-emerald-50',
                        'promo_hover' => 'hover:text-lime-300',
                        'social_icon' => 'fill-emerald-200 hover:fill-lime-300',
                        'nav_shell' => 'bg-emerald-50 border-emerald-200',
                        'nav_text' => 'text-emerald-900',
                        'nav_hover' => 'hover:text-lime-700',
                        'dropdown_shell' => 'bg-white border-emerald-200',
                        'dropdown_item' => 'text-emerald-900 hover:bg-emerald-100 hover:text-lime-700',
                        'contact_btn' => 'text-emerald-800 border-emerald-700 hover:bg-emerald-700 hover:text-white',
                        'hamburger_bar' => 'bg-emerald-700',
                        'mobile_panel' => 'bg-emerald-50',
                        'mobile_link_border' => 'border-emerald-200',
                        'mobile_subtext' => 'text-emerald-700',
                    ],
                ],
                'sunset' => [
                    'label' => 'Sunset Coral',
                    'classes' => [
                        'promo_bar' => 'bg-rose-900 text-rose-50',
                        'promo_hover' => 'hover:text-amber-300',
                        'social_icon' => 'fill-rose-200 hover:fill-amber-300',
                        'nav_shell' => 'bg-amber-50 border-amber-200',
                        'nav_text' => 'text-rose-900',
                        'nav_hover' => 'hover:text-rose-700',
                        'dropdown_shell' => 'bg-white border-amber-200',
                        'dropdown_item' => 'text-rose-900 hover:bg-amber-100 hover:text-rose-700',
                        'contact_btn' => 'text-rose-700 border-rose-700 hover:bg-rose-700 hover:text-white',
                        'hamburger_bar' => 'bg-rose-700',
                        'mobile_panel' => 'bg-amber-50',
                        'mobile_link_border' => 'border-amber-200',
                        'mobile_subtext' => 'text-rose-700',
                    ],
                ],
                'custom' => [
                    'label' => 'Custom (Manual)',
                    'classes' => [
                        'promo_bar' => 'pv-topbar-custom-promo',
                        'promo_hover' => 'pv-topbar-custom-hover',
                        'social_icon' => 'pv-topbar-custom-social',
                        'nav_shell' => 'pv-topbar-custom-nav',
                        'nav_text' => 'pv-topbar-custom-nav-text',
                        'nav_hover' => 'pv-topbar-custom-hover',
                        'dropdown_shell' => 'pv-topbar-custom-dropdown',
                        'dropdown_item' => 'pv-topbar-custom-dropdown-item',
                        'contact_btn' => 'pv-topbar-custom-contact',
                        'hamburger_bar' => 'pv-topbar-custom-accent-bg',
                        'mobile_panel' => 'pv-topbar-custom-mobile-panel',
                        'mobile_link_border' => 'pv-topbar-custom-border',
                        'mobile_subtext' => 'pv-topbar-custom-subtext',
                    ],
                ],
            ],
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
