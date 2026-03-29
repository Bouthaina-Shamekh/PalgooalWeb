<?php

return [
    'pages' => [
        [
            'slug' => 'landing-home',
            'is_home' => true,
            'translations' => [
                'en' => [
                    'slug' => 'home',
                    'title' => 'Clarity-first landing page',
                ],
                'ar' => [
                    'slug' => 'home',
                    'title' => 'صفحة هبوط تركّز على الوضوح',
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
                            'title' => 'Launch a landing page that explains your offer in seconds',
                            'content' => [
                                'eyebrow' => 'SaaS landing template',
                                'subtitle' => 'Use a clean structure for your headline, proof, and call to action so every visitor understands what you do and what to do next.',
                                'primary_button' => [
                                    'label' => 'Start your setup',
                                    'url' => '#cta',
                                    'new_tab' => false,
                                ],
                                'secondary_button' => [
                                    'label' => 'Explore features',
                                    'url' => '#features',
                                    'new_tab' => false,
                                ],
                                'highlights' => [
                                    'Fast setup',
                                    'Responsive layout',
                                    'RTL ready',
                                ],
                                'stats' => [
                                    ['value' => '3 steps', 'label' => 'Clear onboarding flow'],
                                    ['value' => '1 page', 'label' => 'Focused conversion path'],
                                    ['value' => 'RTL', 'label' => 'Arabic and English ready'],
                                ],
                                'image' => 'https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                        'ar' => [
                            'title' => 'أطلق صفحة هبوط تشرح عرضك خلال ثوانٍ',
                            'content' => [
                                'eyebrow' => 'قالب صفحة هبوط SaaS',
                                'subtitle' => 'استخدم بنية واضحة للعنوان والإثبات والدعوة لاتخاذ إجراء حتى يفهم الزائر بسرعة ما الذي تقدمه وما الخطوة التالية.',
                                'primary_button' => [
                                    'label' => 'ابدأ إعداد موقعك',
                                    'url' => '#cta',
                                    'new_tab' => false,
                                ],
                                'secondary_button' => [
                                    'label' => 'استعرض المميزات',
                                    'url' => '#features',
                                    'new_tab' => false,
                                ],
                                'highlights' => [
                                    'إعداد سريع',
                                    'تصميم متجاوب',
                                    'جاهز للعربية',
                                ],
                                'stats' => [
                                    ['value' => '3 خطوات', 'label' => 'مسار واضح للانطلاق'],
                                    ['value' => 'صفحة واحدة', 'label' => 'رحلة مركّزة نحو التحويل'],
                                    ['value' => 'RTL', 'label' => 'جاهز للعربية والإنجليزية'],
                                ],
                                'image' => 'https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&w=1200&q=80',
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
                            'title' => 'Everything your landing page needs to convert',
                            'content' => [
                                'subtitle' => 'Break your message into scannable blocks that move visitors from understanding to trust to action.',
                                'items' => [
                                    [
                                        'icon' => '01',
                                        'title' => 'Clear value proposition',
                                        'description' => 'Lead with one simple promise so visitors understand the outcome immediately.',
                                    ],
                                    [
                                        'icon' => '02',
                                        'title' => 'Built-in proof',
                                        'description' => 'Support your message with testimonials and helpful answers to common objections.',
                                    ],
                                    [
                                        'icon' => '03',
                                        'title' => 'Focused CTA',
                                        'description' => 'Guide attention toward one next action instead of overwhelming people with choices.',
                                    ],
                                    [
                                        'icon' => '04',
                                        'title' => 'Easy customization',
                                        'description' => 'Swap text, imagery, and sections quickly inside the existing builder workflow.',
                                    ],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'كل ما تحتاجه صفحة الهبوط لتحويل الزائر',
                            'content' => [
                                'subtitle' => 'قسّم رسالتك إلى كتل واضحة تنقل الزائر من الفهم إلى الثقة ثم إلى اتخاذ الإجراء.',
                                'items' => [
                                    [
                                        'icon' => '01',
                                        'title' => 'عرض قيمة واضح',
                                        'description' => 'ابدأ بوعد بسيط ومباشر حتى يفهم الزائر النتيجة فوراً.',
                                    ],
                                    [
                                        'icon' => '02',
                                        'title' => 'عناصر ثقة جاهزة',
                                        'description' => 'ادعم رسالتك بشهادات وإجابات واضحة على الاعتراضات الشائعة.',
                                    ],
                                    [
                                        'icon' => '03',
                                        'title' => 'دعوة مركّزة للإجراء',
                                        'description' => 'وجّه الانتباه إلى خطوة واحدة واضحة بدلاً من تشتيت الزائر بخيارات كثيرة.',
                                    ],
                                    [
                                        'icon' => '04',
                                        'title' => 'تخصيص سريع',
                                        'description' => 'بدّل النصوص والصور والسكاشن بسرعة داخل نفس مسار البناء الحالي.',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'cta-section',
                    'type' => 'cta',
                    'sort_order' => 3,
                    'translations' => [
                        'en' => [
                            'title' => 'Make your first impression count',
                            'content' => [
                                'badge' => 'Ready-to-customize',
                                'subtitle' => 'Start with this structure, then tailor the message, proof, and offer to your exact business.',
                                'primary_button_text' => 'Start editing this page',
                                'primary_button_url' => '#',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                        'ar' => [
                            'title' => 'اجعل انطباعك الأول أقوى',
                            'content' => [
                                'badge' => 'جاهز للتخصيص',
                                'subtitle' => 'ابدأ بهذه البنية ثم خصّص الرسالة والإثبات والعرض بما يناسب مشروعك بدقة.',
                                'primary_button_text' => 'ابدأ تعديل الصفحة',
                                'primary_button_url' => '#',
                                'primary_button_new_tab' => false,
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
                            'title' => 'Social proof that supports your promise',
                            'content' => [
                                'eyebrow' => 'Testimonials',
                                'description' => 'Short, specific proof points help new visitors trust your message faster.',
                                'items' => [
                                    [
                                        'name' => 'Amina',
                                        'role' => 'Studio founder',
                                        'text' => 'The structure made it easy to explain our offer without overloading the page.',
                                        'rating' => 5,
                                    ],
                                    [
                                        'name' => 'Omar',
                                        'role' => 'Consultant',
                                        'text' => 'We published quickly, then kept refining the copy as we learned what resonated.',
                                        'rating' => 5,
                                    ],
                                    [
                                        'name' => 'Lina',
                                        'role' => 'Ecommerce brand',
                                        'text' => 'The clear CTA and FAQ flow helped us remove friction from our launch page.',
                                        'rating' => 5,
                                    ],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'إثبات اجتماعي يدعم رسالتك',
                            'content' => [
                                'eyebrow' => 'آراء العملاء',
                                'description' => 'الإثباتات القصيرة والواضحة تساعد الزائر الجديد على الوثوق برسالتك بسرعة أكبر.',
                                'items' => [
                                    [
                                        'name' => 'أمينة',
                                        'role' => 'مؤسسة استوديو',
                                        'text' => 'البنية ساعدتنا على شرح العرض بشكل واضح من دون تحميل الصفحة أكثر من اللازم.',
                                        'rating' => 5,
                                    ],
                                    [
                                        'name' => 'عمر',
                                        'role' => 'مستشار أعمال',
                                        'text' => 'نشرنا الصفحة بسرعة ثم واصلنا تحسين الرسائل بناءً على ما تفاعل معه العملاء.',
                                        'rating' => 5,
                                    ],
                                    [
                                        'name' => 'لينا',
                                        'role' => 'متجر إلكتروني',
                                        'text' => 'وضوح الدعوة للإجراء ووجود قسم الأسئلة الشائعة خففا التردد قبل الإطلاق.',
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
                            'title' => 'Questions before you launch?',
                            'content' => [
                                'subtitle' => 'Use this section to answer the objections that normally slow down decisions.',
                                'items' => [
                                    [
                                        'question' => 'Can I edit every section later?',
                                        'answer' => 'Yes. The page is built from modular sections, so you can update the content, order, and visibility through the existing editor flow.',
                                    ],
                                    [
                                        'question' => 'Will this work on mobile and in Arabic?',
                                        'answer' => 'Yes. The layout is responsive and written to support both LTR and RTL directions.',
                                    ],
                                    [
                                        'question' => 'Can I connect my own domain later?',
                                        'answer' => 'Yes. You can publish on the platform subdomain first, then connect and verify your custom domain when you are ready.',
                                    ],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'هل لديك أسئلة قبل الإطلاق؟',
                            'content' => [
                                'subtitle' => 'استخدم هذا القسم للإجابة عن الاعتراضات التي تؤخر القرار عادةً.',
                                'items' => [
                                    [
                                        'question' => 'هل أستطيع تعديل كل قسم لاحقاً؟',
                                        'answer' => 'نعم. الصفحة مبنية من سكاشن منفصلة، لذلك يمكنك تعديل المحتوى والترتيب وحالة الظهور من خلال نفس مسار المحرر الحالي.',
                                    ],
                                    [
                                        'question' => 'هل ستعمل الصفحة على الجوال وبالعربية؟',
                                        'answer' => 'نعم. الواجهة متجاوبة ومكتوبة بحيث تدعم اتجاهي LTR وRTL.',
                                    ],
                                    [
                                        'question' => 'هل يمكنني ربط دوميني الخاص لاحقاً؟',
                                        'answer' => 'نعم. يمكنك النشر أولاً على السبدومين الخاص بالمنصة ثم ربط الدومين المخصص والتحقق منه لاحقاً عندما تصبح جاهزاً.',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
