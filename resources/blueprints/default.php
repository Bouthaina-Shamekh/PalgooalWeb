<?php

return [
    'pages' => [
        [
            'slug' => 'restaurant-home',
            'is_home' => true,
            'translations' => [
                'en' => [
                    'slug' => 'home',
                    'title' => 'The Finest Taste Restaurant',
                ],
                'ar' => [
                    'slug' => 'الرئيسية',
                    'title' => 'مطعم المذاق الأفضل',
                ],
            ],
            'sections' => [
                [
                    'key' => 'hero-block',
                    'type' => 'hero',
                    'variant' => 'restaurant',
                    'sort_order' => 1,
                    'translations' => [
                        'en' => [
                            'title' => 'Where flavors meet stories',
                            'content' => [
                                'subtitle' => 'Seasonal ingredients, chef-crafted menus, and warm hospitality.',
                                'button_label' => 'Reserve a Table',
                                'button_url' => '#menu',
                                'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836',
                            ],
                        ],
                        'ar' => [
                            'title' => 'مذاقات شرقية بلمسة عصرية',
                            'content' => [
                                'subtitle' => 'أطباق موسمية بمنتجات طازجة وخدمة دافئة.',
                                'button_label' => 'احجز طاولتك',
                                'button_url' => '#menu',
                                'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836',
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'menu-block',
                    'type' => 'menu',
                    'variant' => 'two-column',
                    'sort_order' => 2,
                    'translations' => [
                        'en' => [
                            'title' => 'Chef selection',
                            'content' => [
                                'items' => [
                                    ['name' => 'Quinoa Garden Salad', 'description' => 'Herbs, citrus oil, roasted seeds.', 'price' => '$12'],
                                    ['name' => 'Charred Lamb Ribs', 'description' => 'House glaze, grilled vegetables.', 'price' => '$25'],
                                    ['name' => 'Vanilla Creme Brulee', 'description' => 'Torch caramel, Madagascar vanilla.', 'price' => '$10'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'قائمة المذاقات',
                            'content' => [
                                'items' => [
                                    ['name' => 'سلطة كينوا', 'description' => 'أعشاب مع زيت حمضي وبذور محمصة.', 'price' => '35₪'],
                                    ['name' => 'ريش مشوية', 'description' => 'تتبيلة خاصة وخضار موسمية مشوية.', 'price' => '75₪'],
                                    ['name' => 'كريم بروليه', 'description' => 'فانيلا مخملية مع طبقة كراميل.', 'price' => '28₪'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'testimonials-block',
                    'type' => 'testimonials',
                    'sort_order' => 3,
                    'translations' => [
                        'en' => [
                            'title' => 'Guests say',
                            'content' => [
                                'items' => [
                                    ['name' => 'Lina', 'text' => 'Flavors that feel like home.', 'rating' => 5],
                                    ['name' => 'Karim', 'text' => 'Fast service and cozy ambience.', 'rating' => 4],
                                    ['name' => 'Dana', 'text' => 'Our go-to place for celebrations.', 'rating' => 5],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'شهادات زوارنا',
                            'content' => [
                                'items' => [
                                    ['name' => 'ليان', 'text' => 'النكهات تعكس روح المكان.', 'rating' => 5],
                                    ['name' => 'كريم', 'text' => 'خدمة سريعة وأجواء مريحة.', 'rating' => 4],
                                    ['name' => 'دانا', 'text' => 'أفضل مكان لعشاء عائلي.', 'rating' => 5],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];