<?php

return [
    'pages' => [
        [
            'slug' => 'cafe-sunrise-home',
            'is_home' => true,
            'translations' => [
                'en' => [
                    'slug' => 'home',
                    'title' => 'Cafe Sunrise',
                ],
                'ar' => [
                    'slug' => 'home',
                    'title' => 'كافيه صن رايز',
                ],
            ],
            'sections' => [
                [
                    'key' => 'hero-section',
                    'type' => 'hero',
                    'variant' => 'landing',
                    'sort_order' => 1,
                    'translations' => [
                        'en' => [
                            'title' => 'A warm cafe website that turns visitors into regulars',
                            'content' => [
                                'eyebrow' => 'Boutique coffee template',
                                'subtitle' => 'Introduce your cafe, show the atmosphere, highlight your best items, and guide visitors toward their first order or table visit.',
                                'primary_button' => [
                                    'label' => 'Order your first drink',
                                    'url' => '#cta',
                                    'new_tab' => false,
                                ],
                                'secondary_button' => [
                                    'label' => 'See what makes us special',
                                    'url' => '#features',
                                    'new_tab' => false,
                                ],
                                'highlights' => [
                                    'Craft coffee',
                                    'Fresh pastries daily',
                                    'Comfortable workspace',
                                ],
                                'stats' => [
                                    ['value' => '7 days', 'label' => 'Open every week'],
                                    ['value' => '15 min', 'label' => 'Average takeaway prep'],
                                    ['value' => '4.9/5', 'label' => 'Loved by regular guests'],
                                ],
                                'image' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                        'ar' => [
                            'title' => 'موقع دافئ لمقهى يحول الزوار إلى عملاء دائمين',
                            'content' => [
                                'eyebrow' => 'قالب مقهى بوتيكي',
                                'subtitle' => 'قدّم أجواء المقهى، أبرز أفضل الأصناف، ووجّه الزائر بسهولة نحو أول طلب أو أول زيارة للمكان.',
                                'primary_button' => [
                                    'label' => 'اطلب مشروبك الأول',
                                    'url' => '#cta',
                                    'new_tab' => false,
                                ],
                                'secondary_button' => [
                                    'label' => 'اكتشف ما يميزنا',
                                    'url' => '#features',
                                    'new_tab' => false,
                                ],
                                'highlights' => [
                                    'قهوة مختصة',
                                    'حلويات طازجة يومياً',
                                    'مكان مريح للعمل',
                                ],
                                'stats' => [
                                    ['value' => '7 أيام', 'label' => 'نفتح طوال الأسبوع'],
                                    ['value' => '15 دقيقة', 'label' => 'متوسط تجهيز الطلبات السريعة'],
                                    ['value' => '4.9/5', 'label' => 'تقييم عالٍ من الزوار'],
                                ],
                                'image' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'features-section',
                    'type' => 'features',
                    'sort_order' => 2,
                    'translations' => [
                        'en' => [
                            'title' => 'Why guests come back to Cafe Sunrise',
                            'content' => [
                                'id' => 'features',
                                'subtitle' => 'Use this section to communicate the experience, not just the menu.',
                                'items' => [
                                    [
                                        'icon' => '01',
                                        'title' => 'Signature seasonal drinks',
                                        'description' => 'Rotate fresh flavors across the year to keep the menu interesting and shareable.',
                                    ],
                                    [
                                        'icon' => '02',
                                        'title' => 'Quiet morning atmosphere',
                                        'description' => 'A calm, naturally lit setting for coffee dates, solo work sessions, and reading.',
                                    ],
                                    [
                                        'icon' => '03',
                                        'title' => 'Fast takeaway flow',
                                        'description' => 'Clear bestsellers and one obvious order action help new customers decide quickly.',
                                    ],
                                    [
                                        'icon' => '04',
                                        'title' => 'Instagram-friendly presentation',
                                        'description' => 'Beautiful drinks and desserts give people a reason to share your cafe online.',
                                    ],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'لماذا يعود الضيوف إلى كافيه صن رايز باستمرار',
                            'content' => [
                                'id' => 'features',
                                'subtitle' => 'استخدم هذا القسم لعرض التجربة التي تقدمها، وليس الأصناف فقط.',
                                'items' => [
                                    [
                                        'icon' => '01',
                                        'title' => 'مشروبات موسمية مميزة',
                                        'description' => 'نكهات متجددة على مدار العام تجعل القائمة ممتعة وقابلة للمشاركة.',
                                    ],
                                    [
                                        'icon' => '02',
                                        'title' => 'أجواء صباحية هادئة',
                                        'description' => 'مكان مضاء طبيعياً ومناسب للقاءات، العمل الفردي، والقراءة.',
                                    ],
                                    [
                                        'icon' => '03',
                                        'title' => 'طلبات سريعة وسلسة',
                                        'description' => 'أفضل الأصناف مع دعوة واضحة للطلب تساعد الزائر الجديد على اتخاذ القرار بسرعة.',
                                    ],
                                    [
                                        'icon' => '04',
                                        'title' => 'تقديم جذاب بصرياً',
                                        'description' => 'المشروبات والحلويات الجميلة تمنح العملاء سبباً لمشاركة المقهى على السوشيال ميديا.',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'menu-section',
                    'type' => 'menu',
                    'variant' => 'two-column',
                    'sort_order' => 3,
                    'translations' => [
                        'en' => [
                            'title' => 'Guest favorites',
                            'content' => [
                                'eyebrow' => 'Signature Menu',
                                'description' => 'Show a short curated menu instead of overwhelming people with every product.',
                                'note' => 'Menu preview only. Update prices and items any time.',
                                'items' => [
                                    [
                                        'name' => 'Sunrise Latte',
                                        'description' => 'Smooth espresso with orange zest syrup and velvety milk foam.',
                                        'price' => '$4.90',
                                    ],
                                    [
                                        'name' => 'Honey Cinnamon Cold Brew',
                                        'description' => 'Slow-steeped cold brew finished with honey cream and a touch of cinnamon.',
                                        'price' => '$5.40',
                                    ],
                                    [
                                        'name' => 'Butter Croissant',
                                        'description' => 'Freshly baked each morning with a light, flaky texture.',
                                        'price' => '$2.80',
                                    ],
                                    [
                                        'name' => 'Berry Cheesecake Slice',
                                        'description' => 'Creamy cheesecake topped with mixed berries and a crisp biscuit base.',
                                        'price' => '$4.20',
                                    ],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'الأصناف المفضلة لدى الضيوف',
                            'content' => [
                                'eyebrow' => 'قائمة مختارة',
                                'description' => 'اعرض مجموعة مختصرة وجذابة من الأصناف بدلاً من إرباك الزائر بقائمة طويلة.',
                                'note' => 'هذه معاينة للقائمة ويمكن تعديل الأسعار والأصناف في أي وقت.',
                                'items' => [
                                    [
                                        'name' => 'لاتيه صن رايز',
                                        'description' => 'إسبريسو ناعم مع سيرب البرتقال ورغوة حليب مخملية.',
                                        'price' => '$4.90',
                                    ],
                                    [
                                        'name' => 'كولد برو بالعسل والقرفة',
                                        'description' => 'قهوة باردة منقوعة ببطء مع كريمة عسل ولمسة قرفة.',
                                        'price' => '$5.40',
                                    ],
                                    [
                                        'name' => 'كرواسون زبدة',
                                        'description' => 'يخبز طازجاً كل صباح بقوام خفيف وهش.',
                                        'price' => '$2.80',
                                    ],
                                    [
                                        'name' => 'قطعة تشيز كيك بالتوت',
                                        'description' => 'تشيز كيك كريمي مع توت مشكل وقاعدة بسكويت مقرمشة.',
                                        'price' => '$4.20',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'testimonials-section',
                    'type' => 'testimonials',
                    'sort_order' => 4,
                    'translations' => [
                        'en' => [
                            'title' => 'What regular guests say',
                            'content' => [
                                'eyebrow' => 'Testimonials',
                                'description' => 'Short, believable quotes help new visitors trust the vibe before they arrive.',
                                'items' => [
                                    [
                                        'name' => 'Maya',
                                        'role' => 'Remote designer',
                                        'text' => 'The atmosphere is calm, the coffee is excellent, and it became my favorite weekday workspace.',
                                        'rating' => 5,
                                    ],
                                    [
                                        'name' => 'Samer',
                                        'role' => 'Local customer',
                                        'text' => 'The seasonal drinks always feel special, and the staff remember what I like.',
                                        'rating' => 5,
                                    ],
                                    [
                                        'name' => 'Lina',
                                        'role' => 'Weekend visitor',
                                        'text' => 'It feels warm and thoughtful from the first visit. The desserts are genuinely worth trying.',
                                        'rating' => 5,
                                    ],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'ماذا يقول الزوار الدائمون',
                            'content' => [
                                'eyebrow' => 'آراء العملاء',
                                'description' => 'الاقتباسات القصيرة والواضحة تبني الثقة لدى الزائر الجديد قبل أن يزور المقهى.',
                                'items' => [
                                    [
                                        'name' => 'مايا',
                                        'role' => 'مصممة تعمل عن بعد',
                                        'text' => 'الأجواء هادئة، والقهوة ممتازة، وأصبح هذا المكان خياري المفضل للعمل خلال الأسبوع.',
                                        'rating' => 5,
                                    ],
                                    [
                                        'name' => 'سامر',
                                        'role' => 'عميل دائم',
                                        'text' => 'المشروبات الموسمية دائماً مميزة، والفريق يتذكر ذوقي في كل زيارة.',
                                        'rating' => 5,
                                    ],
                                    [
                                        'name' => 'لينا',
                                        'role' => 'زائرة في عطلة نهاية الأسبوع',
                                        'text' => 'المكان دافئ ومدروس من أول زيارة، والحلويات فعلاً تستحق التجربة.',
                                        'rating' => 5,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'faq-section',
                    'type' => 'faq',
                    'sort_order' => 5,
                    'translations' => [
                        'en' => [
                            'title' => 'Questions guests ask before their first visit',
                            'content' => [
                                'subtitle' => 'Answer the practical questions that help people decide faster.',
                                'items' => [
                                    [
                                        'question' => 'Can I use this template for takeaway only?',
                                        'answer' => 'Yes. You can adapt the copy, buttons, and menu focus for takeaway, delivery, or dine-in service.',
                                    ],
                                    [
                                        'question' => 'Can I replace the menu items later?',
                                        'answer' => 'Yes. The block is fully editable and meant to be updated as your specials and prices change.',
                                    ],
                                    [
                                        'question' => 'Can I connect my own cafe domain?',
                                        'answer' => 'Yes. Start on the platform subdomain, then connect your custom domain whenever you are ready.',
                                    ],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'أسئلة يطرحها الزوار قبل أول زيارة',
                            'content' => [
                                'subtitle' => 'أجب عن الأسئلة العملية التي تساعد العميل على اتخاذ القرار بسرعة.',
                                'items' => [
                                    [
                                        'question' => 'هل يمكن استخدام هذا القالب لخدمة الطلبات الخارجية فقط؟',
                                        'answer' => 'نعم. يمكنك تعديل النصوص والأزرار وتركيز القائمة لتناسب الاستلام أو التوصيل أو الجلوس داخل المقهى.',
                                    ],
                                    [
                                        'question' => 'هل يمكنني تغيير أصناف القائمة لاحقاً؟',
                                        'answer' => 'نعم. هذا البلوك قابل للتعديل بالكامل ومصمم ليتحدث مع تغيّر الأصناف والأسعار.',
                                    ],
                                    [
                                        'question' => 'هل أستطيع ربط دومين خاص بالمقهى؟',
                                        'answer' => 'نعم. ابدأ أولاً على سب دومين المنصة، ثم اربط الدومين المخصص عندما تصبح جاهزاً.',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'cta-section',
                    'type' => 'cta',
                    'sort_order' => 6,
                    'translations' => [
                        'en' => [
                            'title' => 'Turn this cafe template into your live website',
                            'content' => [
                                'id' => 'cta',
                                'badge' => 'Ready to customize',
                                'subtitle' => 'Update the brand, menu, photos, and contact flow to match your real cafe in minutes.',
                                'primary_button_text' => 'Start building this cafe site',
                                'primary_button_url' => '#',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                        'ar' => [
                            'title' => 'حوّل قالب المقهى هذا إلى موقعك الفعلي',
                            'content' => [
                                'id' => 'cta',
                                'badge' => 'جاهز للتخصيص',
                                'subtitle' => 'عدّل الهوية والقائمة والصور وطريقة التواصل ليطابق الموقع مشروعك الحقيقي خلال دقائق.',
                                'primary_button_text' => 'ابدأ بناء موقع المقهى',
                                'primary_button_url' => '#',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
