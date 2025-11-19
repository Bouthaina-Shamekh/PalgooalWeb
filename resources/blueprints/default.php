<?php

return [
    'pages' => [
        [
            'slug' => 'home',
            'is_home' => true,
            'translations' => [
                'ar' => [
                    'slug' => 'الرئيسية',
                    'title' => 'ابدأ متجرك خلال دقائق',
                    'content' => 'صفحة رئيسية افتراضية تحتوي على أقسام بطل وخدمات.',
                ],
                'en' => [
                    'slug' => 'home',
                    'title' => 'Launch Your Site in Minutes',
                    'content' => 'Default home page generated for new tenants.',
                ],
            ],
            'sections' => [
                [
                    'key' => 'hero',
                    'sort_order' => 1,
                    'translations' => [
                        'ar' => [
                            'title' => 'منصة Palgoals',
                            'content' => [
                                'subtitle' => 'قوالب احترافية متعددة اللغات',
                                'cta' => 'ابدأ الآن',
                            ],
                        ],
                        'en' => [
                            'title' => 'Palgoals Platform',
                            'content' => [
                                'subtitle' => 'Professional multilingual templates',
                                'cta' => 'Get Started',
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'features',
                    'sort_order' => 2,
                    'translations' => [
                        'ar' => [
                            'title' => 'مزايا رئيسية',
                            'content' => [
                                ['title' => 'متجاوب', 'description' => 'يعمل مع جميع الأجهزة'],
                                ['title' => 'متعدد اللغات', 'description' => 'يدعم العربية والإنجليزية'],
                            ],
                        ],
                        'en' => [
                            'title' => 'Key Features',
                            'content' => [
                                ['title' => 'Responsive', 'description' => 'Looks great on every device'],
                                ['title' => 'Multilingual', 'description' => 'Supports Arabic and English'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'slug' => 'about',
            'translations' => [
                'ar' => [
                    'slug' => 'من-نحن',
                    'title' => 'عن منصتك',
                    'content' => 'صفحة تعرّف الزوار على أعمالك وخدماتك.',
                ],
                'en' => [
                    'slug' => 'about',
                    'title' => 'About Your Business',
                    'content' => 'Tell your visitors about your values and services.',
                ],
            ],
            'sections' => [
                [
                    'key' => 'story',
                    'sort_order' => 1,
                    'translations' => [
                        'ar' => [
                            'title' => 'قصتنا',
                            'content' => [
                                'body' => 'نص افتراضي قابل للتخصيص من لوحة التحكم.',
                            ],
                        ],
                        'en' => [
                            'title' => 'Our Story',
                            'content' => [
                                'body' => 'Sample text that tenant can edit from dashboard.',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
