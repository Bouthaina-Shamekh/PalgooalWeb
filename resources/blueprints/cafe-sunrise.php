<?php

return [
    'pages' => [
        [
            'slug' => 'cafe-sunrise-home',
            'is_home' => true,
            'translations' => [
                'en' => ['slug' => 'home', 'title' => 'Cafe Sunrise'],
                'ar' => ['slug' => 'home', 'title' => 'كافيه صن رايز'],
            ],
            'sections' => [
                [
                    'key' => 'home-hero',
                    'type' => 'hero',
                    'variant' => 'landing',
                    'sort_order' => 1,
                    'translations' => [
                        'en' => [
                            'title' => 'A cozy cafe website for your first online launch',
                            'content' => [
                                'eyebrow' => 'Cafe template',
                                'subtitle' => 'Show your atmosphere, best drinks, and one clear next step for new visitors.',
                                'primary_button' => ['label' => 'See the menu', 'url' => '/menu', 'new_tab' => false],
                                'secondary_button' => ['label' => 'Visit us', 'url' => '/visit-us', 'new_tab' => false],
                                'highlights' => ['Craft coffee', 'Fresh bakery', 'Warm atmosphere'],
                                'stats' => [
                                    ['value' => '7 days', 'label' => 'Open weekly'],
                                    ['value' => '15 min', 'label' => 'Takeaway prep'],
                                    ['value' => '4.9/5', 'label' => 'Guest rating'],
                                ],
                                'image' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                        'ar' => [
                            'title' => 'موقع مقهى دافئ لانطلاقتك الأولى على الإنترنت',
                            'content' => [
                                'eyebrow' => 'قالب مقهى',
                                'subtitle' => 'اعرض أجواء المكان وأفضل المشروبات مع خطوة واضحة للزائر الجديد.',
                                'primary_button' => ['label' => 'استعرض القائمة', 'url' => '/menu', 'new_tab' => false],
                                'secondary_button' => ['label' => 'زرنا', 'url' => '/visit-us', 'new_tab' => false],
                                'highlights' => ['قهوة مختصة', 'مخبوزات طازجة', 'أجواء دافئة'],
                                'stats' => [
                                    ['value' => '7 أيام', 'label' => 'مفتوح أسبوعياً'],
                                    ['value' => '15 دقيقة', 'label' => 'تجهيز سريع'],
                                    ['value' => '4.9/5', 'label' => 'تقييم الزوار'],
                                ],
                                'image' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'home-features',
                    'type' => 'features',
                    'sort_order' => 2,
                    'translations' => [
                        'en' => [
                            'title' => 'Why guests remember Cafe Sunrise',
                            'content' => [
                                'subtitle' => 'A short section for the feeling and the experience.',
                                'items' => [
                                    ['icon' => '01', 'title' => 'Signature drinks', 'description' => 'Seasonal coffee and fresh specials.'],
                                    ['icon' => '02', 'title' => 'Quiet mornings', 'description' => 'A calm place for work and reading.'],
                                    ['icon' => '03', 'title' => 'Fast takeaway', 'description' => 'A simple flow for first orders.'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'لماذا يتذكر الزوار كافيه صن رايز',
                            'content' => [
                                'subtitle' => 'قسم مختصر يشرح الإحساس والتجربة.',
                                'items' => [
                                    ['icon' => '01', 'title' => 'مشروبات مميزة', 'description' => 'قهوة موسمية وعروض متجددة.'],
                                    ['icon' => '02', 'title' => 'صباحات هادئة', 'description' => 'مكان مناسب للعمل والقراءة.'],
                                    ['icon' => '03', 'title' => 'طلبات سريعة', 'description' => 'تجربة أسهل لأول طلب.'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'home-menu',
                    'type' => 'menu',
                    'variant' => 'two-column',
                    'sort_order' => 3,
                    'translations' => [
                        'en' => [
                            'title' => 'Guest favorites',
                            'content' => [
                                'eyebrow' => 'Signature Menu',
                                'description' => 'A short preview from the full menu.',
                                'items' => [
                                    ['name' => 'Sunrise Latte', 'description' => 'Orange zest and smooth milk foam.', 'price' => '$4.90'],
                                    ['name' => 'Cold Brew Honey', 'description' => 'Slow-steeped coffee with soft sweetness.', 'price' => '$5.40'],
                                    ['name' => 'Butter Croissant', 'description' => 'Freshly baked each morning.', 'price' => '$2.80'],
                                    ['name' => 'Berry Cheesecake', 'description' => 'Creamy slice with mixed berries.', 'price' => '$4.20'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'الأصناف المفضلة',
                            'content' => [
                                'eyebrow' => 'قائمة مختارة',
                                'description' => 'معاينة مختصرة من القائمة الكاملة.',
                                'items' => [
                                    ['name' => 'لاتيه صن رايز', 'description' => 'لمسة برتقال مع رغوة حليب ناعمة.', 'price' => '$4.90'],
                                    ['name' => 'كولد برو بالعسل', 'description' => 'قهوة باردة بنكهة لطيفة.', 'price' => '$5.40'],
                                    ['name' => 'كرواسون زبدة', 'description' => 'يخبز طازجاً كل صباح.', 'price' => '$2.80'],
                                    ['name' => 'تشيز كيك بالتوت', 'description' => 'قطعة كريمية مع توت مشكل.', 'price' => '$4.20'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'home-testimonials',
                    'type' => 'testimonials',
                    'sort_order' => 4,
                    'translations' => [
                        'en' => [
                            'title' => 'What regular guests say',
                            'content' => [
                                'eyebrow' => 'Testimonials',
                                'description' => 'Simple social proof for first-time visitors.',
                                'items' => [
                                    ['name' => 'Maya', 'role' => 'Designer', 'text' => 'The atmosphere is calm and the coffee is excellent.', 'rating' => 5],
                                    ['name' => 'Samer', 'role' => 'Customer', 'text' => 'The seasonal drinks always feel special.', 'rating' => 5],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'ماذا يقول الزوار الدائمون',
                            'content' => [
                                'eyebrow' => 'آراء العملاء',
                                'description' => 'إثبات اجتماعي بسيط للزائر الجديد.',
                                'items' => [
                                    ['name' => 'مايا', 'role' => 'مصممة', 'text' => 'الأجواء هادئة والقهوة ممتازة.', 'rating' => 5],
                                    ['name' => 'سامر', 'role' => 'عميل', 'text' => 'المشروبات الموسمية دائماً مميزة.', 'rating' => 5],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'home-cta',
                    'type' => 'cta',
                    'sort_order' => 5,
                    'translations' => [
                        'en' => [
                            'title' => 'Turn this cafe template into your real website',
                            'content' => [
                                'id' => 'cta',
                                'badge' => 'Ready to customize',
                                'subtitle' => 'Replace the starter content with your real brand, menu, and contact details.',
                                'primary_button_text' => 'Continue to visit page',
                                'primary_button_url' => '/visit-us',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                        'ar' => [
                            'title' => 'حوّل هذا القالب إلى موقعك الحقيقي',
                            'content' => [
                                'id' => 'cta',
                                'badge' => 'جاهز للتخصيص',
                                'subtitle' => 'استبدل المحتوى المبدئي بهوية المقهى الحقيقية وبياناته.',
                                'primary_button_text' => 'انتقل إلى صفحة الزيارة',
                                'primary_button_url' => '/visit-us',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'slug' => 'cafe-sunrise-menu',
            'is_home' => false,
            'translations' => [
                'en' => ['slug' => 'menu', 'title' => 'Our Menu'],
                'ar' => ['slug' => 'menu', 'title' => 'القائمة'],
            ],
            'sections' => [
                [
                    'key' => 'menu-hero',
                    'type' => 'hero',
                    'variant' => 'landing',
                    'sort_order' => 1,
                    'translations' => [
                        'en' => [
                            'title' => 'A menu page built for coffee lovers',
                            'content' => [
                                'eyebrow' => 'Full Menu',
                                'subtitle' => 'Use this page for your bestselling drinks, desserts, and seasonal items.',
                                'primary_button' => ['label' => 'Visit us', 'url' => '/visit-us', 'new_tab' => false],
                                'secondary_button' => ['label' => 'Back home', 'url' => '/', 'new_tab' => false],
                                'highlights' => ['Coffee', 'Desserts', 'Seasonal specials'],
                                'image' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                        'ar' => [
                            'title' => 'صفحة قائمة لعشاق القهوة',
                            'content' => [
                                'eyebrow' => 'القائمة الكاملة',
                                'subtitle' => 'استخدم هذه الصفحة لأفضل المشروبات والحلويات والعروض الموسمية.',
                                'primary_button' => ['label' => 'زرنا', 'url' => '/visit-us', 'new_tab' => false],
                                'secondary_button' => ['label' => 'العودة للرئيسية', 'url' => '/', 'new_tab' => false],
                                'highlights' => ['قهوة', 'حلويات', 'عروض موسمية'],
                                'image' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'menu-list',
                    'type' => 'menu',
                    'variant' => 'two-column',
                    'sort_order' => 2,
                    'translations' => [
                        'en' => [
                            'title' => 'Cafe Sunrise Menu',
                            'content' => [
                                'eyebrow' => 'Best Sellers',
                                'description' => 'Starter items you can replace later from the editor.',
                                'items' => [
                                    ['name' => 'Espresso', 'description' => 'Rich single shot.', 'price' => '$2.20'],
                                    ['name' => 'Flat White', 'description' => 'Velvety milk and espresso.', 'price' => '$4.10'],
                                    ['name' => 'Spanish Latte', 'description' => 'Sweet iced favorite.', 'price' => '$5.10'],
                                    ['name' => 'Pistachio Latte', 'description' => 'Nutty seasonal special.', 'price' => '$5.60'],
                                    ['name' => 'Chocolate Brownie', 'description' => 'Served warm.', 'price' => '$3.40'],
                                    ['name' => 'Classic Cheesecake', 'description' => 'Creamy and balanced.', 'price' => '$4.00'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'قائمة كافيه صن رايز',
                            'content' => [
                                'eyebrow' => 'الأكثر طلباً',
                                'description' => 'أصناف مبدئية يمكنك استبدالها لاحقاً من المحرر.',
                                'items' => [
                                    ['name' => 'إسبريسو', 'description' => 'شوت غني ومركز.', 'price' => '$2.20'],
                                    ['name' => 'فلات وايت', 'description' => 'حليب مخملي مع إسبريسو.', 'price' => '$4.10'],
                                    ['name' => 'سبانش لاتيه', 'description' => 'مشروب بارد محبب.', 'price' => '$5.10'],
                                    ['name' => 'لاتيه بالفستق', 'description' => 'مشروب موسمي بنكهة فستق.', 'price' => '$5.60'],
                                    ['name' => 'براوني شوكولاتة', 'description' => 'يقدم دافئاً.', 'price' => '$3.40'],
                                    ['name' => 'تشيز كيك كلاسيك', 'description' => 'قوام كريمي متوازن.', 'price' => '$4.00'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'menu-cta',
                    'type' => 'cta',
                    'sort_order' => 3,
                    'translations' => [
                        'en' => [
                            'title' => 'Ready to customize your real menu?',
                            'content' => [
                                'badge' => 'Menu page ready',
                                'subtitle' => 'Replace these items with your actual drinks, desserts, and prices.',
                                'primary_button_text' => 'Set visit details',
                                'primary_button_url' => '/visit-us',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                        'ar' => [
                            'title' => 'هل أنت جاهز لتخصيص القائمة الحقيقية؟',
                            'content' => [
                                'badge' => 'صفحة القائمة جاهزة',
                                'subtitle' => 'استبدل هذه الأصناف بالمشروبات والحلويات والأسعار الحقيقية.',
                                'primary_button_text' => 'أضف بيانات الزيارة',
                                'primary_button_url' => '/visit-us',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'slug' => 'cafe-sunrise-visit-us',
            'is_home' => false,
            'translations' => [
                'en' => ['slug' => 'visit-us', 'title' => 'Visit Us'],
                'ar' => ['slug' => 'visit-us', 'title' => 'زرنا'],
            ],
            'sections' => [
                [
                    'key' => 'visit-hero',
                    'type' => 'hero',
                    'variant' => 'landing',
                    'sort_order' => 1,
                    'translations' => [
                        'en' => [
                            'title' => 'Make it easy for guests to find you',
                            'content' => [
                                'eyebrow' => 'Visit Us',
                                'subtitle' => 'Use this page for your address, hours, phone number, and easy directions.',
                                'primary_button' => ['label' => 'See the menu', 'url' => '/menu', 'new_tab' => false],
                                'secondary_button' => ['label' => 'Back home', 'url' => '/', 'new_tab' => false],
                                'highlights' => ['Address', 'Hours', 'Phone'],
                                'image' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                        'ar' => [
                            'title' => 'اجعل الوصول إلى مقهاك سهلاً',
                            'content' => [
                                'eyebrow' => 'زرنا',
                                'subtitle' => 'استخدم هذه الصفحة لعرض العنوان وساعات العمل والهاتف وطريقة الوصول.',
                                'primary_button' => ['label' => 'استعرض القائمة', 'url' => '/menu', 'new_tab' => false],
                                'secondary_button' => ['label' => 'العودة للرئيسية', 'url' => '/', 'new_tab' => false],
                                'highlights' => ['العنوان', 'ساعات العمل', 'الهاتف'],
                                'image' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'visit-details',
                    'type' => 'features',
                    'sort_order' => 2,
                    'translations' => [
                        'en' => [
                            'title' => 'Everything a first-time guest needs',
                            'content' => [
                                'subtitle' => 'Replace these sample details with your real cafe info.',
                                'items' => [
                                    ['icon' => '01', 'title' => 'Address', 'description' => '24 Sunrise Street, Central District.'],
                                    ['icon' => '02', 'title' => 'Opening hours', 'description' => 'Daily from 8:00 AM to 11:00 PM.'],
                                    ['icon' => '03', 'title' => 'Phone and WhatsApp', 'description' => '+970 599 000 000 for quick questions and orders.'],
                                    ['icon' => '04', 'title' => 'Parking', 'description' => 'Nearby street parking and indoor seating.'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'كل ما يحتاجه الزائر لأول مرة',
                            'content' => [
                                'subtitle' => 'استبدل هذه البيانات المبدئية بمعلومات المقهى الحقيقية.',
                                'items' => [
                                    ['icon' => '01', 'title' => 'العنوان', 'description' => 'شارع صن رايز 24، الحي المركزي.'],
                                    ['icon' => '02', 'title' => 'ساعات العمل', 'description' => 'يومياً من 8:00 صباحاً حتى 11:00 مساءً.'],
                                    ['icon' => '03', 'title' => 'الهاتف والواتساب', 'description' => '+970 599 000 000 للاستفسار والطلبات السريعة.'],
                                    ['icon' => '04', 'title' => 'المواقف', 'description' => 'مواقف قريبة وجلسات داخلية مريحة.'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'visit-faq',
                    'type' => 'faq',
                    'sort_order' => 3,
                    'translations' => [
                        'en' => [
                            'title' => 'Common questions before visiting',
                            'content' => [
                                'subtitle' => 'A small FAQ reduces hesitation before the first visit.',
                                'items' => [
                                    ['question' => 'Can I update these hours later?', 'answer' => 'Yes. Replace the starter hours from the editor any time.'],
                                    ['question' => 'Can I connect my own domain?', 'answer' => 'Yes. Start on the platform subdomain, then connect your custom domain later.'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'أسئلة شائعة قبل الزيارة',
                            'content' => [
                                'subtitle' => 'قسم FAQ صغير يقلل تردد الزائر قبل أول زيارة.',
                                'items' => [
                                    ['question' => 'هل أستطيع تعديل ساعات العمل لاحقاً؟', 'answer' => 'نعم. يمكنك تعديلها من المحرر في أي وقت.'],
                                    ['question' => 'هل يمكن ربط الموقع بدومين خاص؟', 'answer' => 'نعم. ابدأ على سب دومين المنصة ثم اربط الدومين لاحقاً.'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'visit-cta',
                    'type' => 'cta',
                    'sort_order' => 4,
                    'translations' => [
                        'en' => [
                            'title' => 'Your cafe website is ready for real details',
                            'content' => [
                                'badge' => 'Last step',
                                'subtitle' => 'Replace the address, phone, and opening hours to make this launch-ready.',
                                'primary_button_text' => 'Go back home',
                                'primary_button_url' => '/',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                        'ar' => [
                            'title' => 'موقع المقهى جاهز لبياناتك الحقيقية',
                            'content' => [
                                'badge' => 'الخطوة الأخيرة',
                                'subtitle' => 'استبدل العنوان والهاتف وساعات العمل ليصبح الموقع جاهزاً للنشر.',
                                'primary_button_text' => 'العودة للرئيسية',
                                'primary_button_url' => '/',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
