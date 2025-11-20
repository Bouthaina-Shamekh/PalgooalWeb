<?php

return [
    'restaurant' => [
        'hero_default' => [
            'type' => 'hero',
            'variant' => 'restaurant_default',
            'key' => 'hero-block',
            'translations' => [
                'en' => [
                    'title' => 'Taste that tells a story',
                    'content' => [
                        'subtitle' => 'Seasonal ingredients, chef-crafted menu, and warm hospitality.',
                        'button_label' => 'Reserve a Table',
                        'button_url' => '#menu',
                        'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836',
                    ],
                ],
                'ar' => [
                    'title' => 'نكهات تحكي قصتنا',
                    'content' => [
                        'subtitle' => 'مكونات موسمية وأطباق طازجة يقدمها شيفنا.',
                        'button_label' => 'احجز طاولتك',
                        'button_url' => '#menu',
                        'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836',
                    ],
                ],
            ],
        ],
        'menu_simple' => [
            'type' => 'menu',
            'variant' => 'restaurant_simple',
            'key' => 'menu-block',
            'translations' => [
                'en' => [
                    'title' => 'Chef Picks',
                    'content' => [
                        'items' => [
                            ['name' => 'Garden Salad', 'description' => 'Crunchy greens, citrus oil, toasted seeds.', 'price' => '$10'],
                            ['name' => 'Grilled Lamb', 'description' => 'House marinade, seasonal veggies.', 'price' => '$24'],
                        ],
                    ],
                ],
                'ar' => [
                    'title' => 'أطباق الشيف',
                    'content' => [
                        'items' => [
                            ['name' => 'سلطة طازجة', 'description' => 'خضار موسمية مع زيت حمضي وبذور محمصة.', 'price' => '38₪'],
                            ['name' => 'لحم مشوي', 'description' => 'تتبيلة خاصة وخضار مشوية.', 'price' => '85₪'],
                        ],
                    ],
                ],
            ],
        ],
        'testimonials_basic' => [
            'type' => 'testimonials',
            'variant' => 'basic',
            'key' => 'testimonials-block',
            'translations' => [
                'en' => [
                    'title' => 'What guests say',
                    'content' => [
                        'items' => [
                            ['name' => 'Sarah', 'text' => 'Amazing flavors and cozy ambience.', 'rating' => 5],
                            ['name' => 'Ali', 'text' => 'Fast service and friendly staff.', 'rating' => 4],
                        ],
                    ],
                ],
                'ar' => [
                    'title' => 'آراء زوارنا',
                    'content' => [
                        'items' => [
                            ['name' => 'سارة', 'text' => 'مذاق رائع وجلسة مريحة.', 'rating' => 5],
                            ['name' => 'علي', 'text' => 'خدمة سريعة وطاقم ودود.', 'rating' => 4],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
