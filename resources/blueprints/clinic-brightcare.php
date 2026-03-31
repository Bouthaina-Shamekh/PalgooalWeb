<?php

return [
    'pages' => [
        [
            'slug' => 'clinic-brightcare-home',
            'is_home' => true,
            'translations' => [
                'en' => ['slug' => 'home', 'title' => 'BrightCare Clinic'],
                'ar' => ['slug' => 'home', 'title' => 'عيادة برايت كير'],
            ],
            'sections' => [
                [
                    'key' => 'home-hero',
                    'type' => 'hero',
                    'variant' => 'landing',
                    'sort_order' => 1,
                    'translations' => [
                        'en' => [
                            'title' => 'A calm clinic website that builds trust from the first visit',
                            'content' => [
                                'eyebrow' => 'Medical clinic template',
                                'subtitle' => 'Introduce your clinic, highlight your core services, and guide patients toward booking or contacting your team.',
                                'primary_button' => ['label' => 'Explore services', 'url' => '/services', 'new_tab' => false],
                                'secondary_button' => ['label' => 'Book a visit', 'url' => '/book-visit', 'new_tab' => false],
                                'highlights' => ['Clear services', 'Patient-first tone', 'Easy booking path'],
                                'stats' => [
                                    ['value' => '6 days', 'label' => 'Weekly availability'],
                                    ['value' => '15 min', 'label' => 'Average response time'],
                                    ['value' => '4.9/5', 'label' => 'Patient satisfaction'],
                                ],
                                'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                        'ar' => [
                            'title' => 'موقع هادئ لعيادة يبني الثقة من الزيارة الأولى',
                            'content' => [
                                'eyebrow' => 'قالب عيادة طبية',
                                'subtitle' => 'قدّم العيادة بوضوح، أبرز الخدمات الأساسية، ووجّه المريض بسهولة نحو الحجز أو التواصل.',
                                'primary_button' => ['label' => 'استعرض الخدمات', 'url' => '/services', 'new_tab' => false],
                                'secondary_button' => ['label' => 'احجز زيارة', 'url' => '/book-visit', 'new_tab' => false],
                                'highlights' => ['خدمات واضحة', 'أسلوب يطمئن المريض', 'مسار حجز بسيط'],
                                'stats' => [
                                    ['value' => '6 أيام', 'label' => 'أيام العمل أسبوعياً'],
                                    ['value' => '15 دقيقة', 'label' => 'متوسط سرعة الرد'],
                                    ['value' => '4.9/5', 'label' => 'رضا المرضى'],
                                ],
                                'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=80',
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
                            'title' => 'Why patients feel comfortable with BrightCare',
                            'content' => [
                                'subtitle' => 'This section explains the experience and reassurance your clinic provides.',
                                'items' => [
                                    ['icon' => '01', 'title' => 'Clear medical communication', 'description' => 'Simple explanations that help patients understand their next step.'],
                                    ['icon' => '02', 'title' => 'Clean and calm environment', 'description' => 'Present your clinic as a safe and welcoming place from the first impression.'],
                                    ['icon' => '03', 'title' => 'Fast follow-up', 'description' => 'Give visitors confidence that their questions and appointments will be handled quickly.'],
                                    ['icon' => '04', 'title' => 'Flexible care journey', 'description' => 'Use this site for consultations, follow-up care, and first-time visits.'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'لماذا يشعر المرضى بالراحة مع عيادة برايت كير',
                            'content' => [
                                'subtitle' => 'هذا القسم يشرح التجربة والطمأنينة التي تقدمها العيادة.',
                                'items' => [
                                    ['icon' => '01', 'title' => 'شرح طبي واضح', 'description' => 'لغة بسيطة تساعد المريض على فهم الخطوة التالية بسهولة.'],
                                    ['icon' => '02', 'title' => 'بيئة نظيفة وهادئة', 'description' => 'اعرض العيادة كمكان آمن ومريح منذ أول انطباع.'],
                                    ['icon' => '03', 'title' => 'متابعة سريعة', 'description' => 'امنح الزائر ثقة بأن أسئلته ومواعيده ستتم متابعتها بسرعة.'],
                                    ['icon' => '04', 'title' => 'رحلة علاج مرنة', 'description' => 'يمكن استخدام الموقع للاستشارات والمتابعة والزيارات الأولى.'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'home-testimonials',
                    'type' => 'testimonials',
                    'sort_order' => 3,
                    'translations' => [
                        'en' => [
                            'title' => 'What patients say',
                            'content' => [
                                'eyebrow' => 'Patient stories',
                                'description' => 'Short, reassuring testimonials help first-time patients feel safe reaching out.',
                                'items' => [
                                    ['name' => 'Mariam', 'role' => 'Patient', 'text' => 'The team explained everything clearly and made the whole visit feel calm and organized.', 'rating' => 5],
                                    ['name' => 'Ahmad', 'role' => 'Parent', 'text' => 'Booking was easy, and the follow-up after the appointment was genuinely helpful.', 'rating' => 5],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'ماذا يقول المرضى',
                            'content' => [
                                'eyebrow' => 'تجارب المرضى',
                                'description' => 'الشهادات القصيرة والمطمئنة تساعد المريض الجديد على اتخاذ خطوة التواصل بثقة.',
                                'items' => [
                                    ['name' => 'مريم', 'role' => 'مريضة', 'text' => 'الفريق شرح كل شيء بوضوح وجعل الزيارة هادئة ومنظمة.', 'rating' => 5],
                                    ['name' => 'أحمد', 'role' => 'ولي أمر', 'text' => 'الحجز كان سهلاً والمتابعة بعد الموعد كانت مفيدة فعلاً.', 'rating' => 5],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'home-faq',
                    'type' => 'faq',
                    'sort_order' => 4,
                    'translations' => [
                        'en' => [
                            'title' => 'Questions before booking',
                            'content' => [
                                'subtitle' => 'Answer the questions that usually create hesitation before the first appointment.',
                                'items' => [
                                    ['question' => 'Can I update the services later?', 'answer' => 'Yes. Replace the starter content with your real clinic services from the editor any time.'],
                                    ['question' => 'Can this work for different specialties?', 'answer' => 'Yes. The structure works for general practice, dental, skin care, physiotherapy, and more.'],
                                    ['question' => 'Can I connect my own domain?', 'answer' => 'Yes. Start on the platform subdomain, then connect your custom domain later.'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'أسئلة قبل الحجز',
                            'content' => [
                                'subtitle' => 'أجب عن الأسئلة التي تسبب التردد عادة قبل الموعد الأول.',
                                'items' => [
                                    ['question' => 'هل يمكنني تعديل الخدمات لاحقاً؟', 'answer' => 'نعم. يمكنك استبدال المحتوى المبدئي بخدمات العيادة الحقيقية من المحرر في أي وقت.'],
                                    ['question' => 'هل يصلح القالب لتخصصات مختلفة؟', 'answer' => 'نعم. البنية مناسبة للطب العام والأسنان والجلدية والعلاج الطبيعي وغيرها.'],
                                    ['question' => 'هل يمكن ربط دومين خاص؟', 'answer' => 'نعم. ابدأ على سب دومين المنصة ثم اربط الدومين الخاص لاحقاً.'],
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
                            'title' => 'Turn this clinic template into your real patient website',
                            'content' => [
                                'badge' => 'Ready to customize',
                                'subtitle' => 'Replace the sample text, clinic name, doctors, and booking details to launch quickly.',
                                'primary_button_text' => 'Continue to booking page',
                                'primary_button_url' => '/book-visit',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                        'ar' => [
                            'title' => 'حوّل هذا القالب إلى موقع مرضاك الحقيقي',
                            'content' => [
                                'badge' => 'جاهز للتخصيص',
                                'subtitle' => 'استبدل النصوص واسم العيادة والتخصصات وبيانات الحجز للانطلاق بسرعة.',
                                'primary_button_text' => 'انتقل إلى صفحة الحجز',
                                'primary_button_url' => '/book-visit',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'slug' => 'clinic-brightcare-services',
            'is_home' => false,
            'translations' => [
                'en' => ['slug' => 'services', 'title' => 'Our Services'],
                'ar' => ['slug' => 'services', 'title' => 'الخدمات'],
            ],
            'sections' => [
                [
                    'key' => 'services-hero',
                    'type' => 'hero',
                    'variant' => 'landing',
                    'sort_order' => 1,
                    'translations' => [
                        'en' => [
                            'title' => 'A service page that helps patients understand their options',
                            'content' => [
                                'eyebrow' => 'Clinic Services',
                                'subtitle' => 'Use this page to explain your main specialties and make the next step clearer for every patient.',
                                'primary_button' => ['label' => 'Book a visit', 'url' => '/book-visit', 'new_tab' => false],
                                'secondary_button' => ['label' => 'Back home', 'url' => '/', 'new_tab' => false],
                                'highlights' => ['Primary care', 'Follow-up visits', 'Preventive care'],
                                'image' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                        'ar' => [
                            'title' => 'صفحة خدمات تساعد المريض على فهم الخيارات المتاحة',
                            'content' => [
                                'eyebrow' => 'خدمات العيادة',
                                'subtitle' => 'استخدم هذه الصفحة لشرح التخصصات الرئيسية وجعل الخطوة التالية أوضح لكل مريض.',
                                'primary_button' => ['label' => 'احجز زيارة', 'url' => '/book-visit', 'new_tab' => false],
                                'secondary_button' => ['label' => 'العودة للرئيسية', 'url' => '/', 'new_tab' => false],
                                'highlights' => ['رعاية أولية', 'زيارات متابعة', 'رعاية وقائية'],
                                'image' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'services-list',
                    'type' => 'features',
                    'sort_order' => 2,
                    'translations' => [
                        'en' => [
                            'title' => 'Starter clinic services',
                            'content' => [
                                'subtitle' => 'Replace these examples with the exact treatments and specialties your clinic provides.',
                                'items' => [
                                    ['icon' => '01', 'title' => 'General consultations', 'description' => 'A clear entry point for first-time concerns and routine health questions.'],
                                    ['icon' => '02', 'title' => 'Preventive checkups', 'description' => 'Use this block for annual visits, screening, or wellness packages.'],
                                    ['icon' => '03', 'title' => 'Follow-up appointments', 'description' => 'Reassure returning patients that their ongoing care is easy to manage.'],
                                    ['icon' => '04', 'title' => 'Lab and referrals', 'description' => 'Explain how tests, reports, and referrals are coordinated.'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'خدمات عيادة مبدئية',
                            'content' => [
                                'subtitle' => 'استبدل هذه الأمثلة بالخدمات والتخصصات الفعلية التي تقدمها العيادة.',
                                'items' => [
                                    ['icon' => '01', 'title' => 'استشارات عامة', 'description' => 'مدخل واضح للزيارة الأولى والاستفسارات الصحية اليومية.'],
                                    ['icon' => '02', 'title' => 'فحوصات وقائية', 'description' => 'يمكن استخدام هذا البلوك للفحوصات الدورية وبرامج المتابعة الوقائية.'],
                                    ['icon' => '03', 'title' => 'مواعيد متابعة', 'description' => 'طمئن المرضى بأن المتابعة المستمرة سهلة وواضحة.'],
                                    ['icon' => '04', 'title' => 'تحاليل وتحويلات', 'description' => 'اشرح كيف يتم تنسيق الفحوصات والتقارير والتحويلات الطبية.'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'services-faq',
                    'type' => 'faq',
                    'sort_order' => 3,
                    'translations' => [
                        'en' => [
                            'title' => 'Questions about services',
                            'content' => [
                                'subtitle' => 'A small FAQ helps patients decide whether this clinic fits their needs.',
                                'items' => [
                                    ['question' => 'Can I change these service names later?', 'answer' => 'Yes. Replace every example with your real specialty names and descriptions from the editor.'],
                                    ['question' => 'Can this page focus on one specialty only?', 'answer' => 'Yes. Many clinics use this page for one branch such as dental, dermatology, or physiotherapy.'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'أسئلة حول الخدمات',
                            'content' => [
                                'subtitle' => 'قسم FAQ صغير يساعد المريض على معرفة ما إذا كانت العيادة مناسبة لاحتياجه.',
                                'items' => [
                                    ['question' => 'هل يمكنني تعديل أسماء الخدمات لاحقاً؟', 'answer' => 'نعم. يمكنك استبدال جميع الأمثلة بأسماء التخصصات والأوصاف الحقيقية من المحرر.'],
                                    ['question' => 'هل يمكن تخصيص الصفحة لتخصص واحد فقط؟', 'answer' => 'نعم. كثير من العيادات تستخدمها لتخصص واحد مثل الأسنان أو الجلدية أو العلاج الطبيعي.'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'services-cta',
                    'type' => 'cta',
                    'sort_order' => 4,
                    'translations' => [
                        'en' => [
                            'title' => 'Ready to guide patients toward the right service?',
                            'content' => [
                                'badge' => 'Services page ready',
                                'subtitle' => 'Make the list accurate, then connect it with a simple booking or contact flow.',
                                'primary_button_text' => 'Go to booking page',
                                'primary_button_url' => '/book-visit',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                        'ar' => [
                            'title' => 'هل أنت جاهز لتوجيه المرضى نحو الخدمة المناسبة؟',
                            'content' => [
                                'badge' => 'صفحة الخدمات جاهزة',
                                'subtitle' => 'اجعل القائمة دقيقة ثم اربطها بمسار حجز أو تواصل بسيط.',
                                'primary_button_text' => 'انتقل إلى صفحة الحجز',
                                'primary_button_url' => '/book-visit',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'slug' => 'clinic-brightcare-book-visit',
            'is_home' => false,
            'translations' => [
                'en' => ['slug' => 'book-visit', 'title' => 'Book a Visit'],
                'ar' => ['slug' => 'book-visit', 'title' => 'احجز زيارة'],
            ],
            'sections' => [
                [
                    'key' => 'booking-hero',
                    'type' => 'hero',
                    'variant' => 'landing',
                    'sort_order' => 1,
                    'translations' => [
                        'en' => [
                            'title' => 'Make booking and contact feel simple and reassuring',
                            'content' => [
                                'eyebrow' => 'Booking Page',
                                'subtitle' => 'Use this page for phone numbers, WhatsApp, working hours, address, and the easiest contact path for new patients.',
                                'primary_button' => ['label' => 'See services', 'url' => '/services', 'new_tab' => false],
                                'secondary_button' => ['label' => 'Back home', 'url' => '/', 'new_tab' => false],
                                'highlights' => ['Phone', 'Working hours', 'Easy directions'],
                                'image' => 'https://images.unsplash.com/photo-1666214280557-f1b5022eb634?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                        'ar' => [
                            'title' => 'اجعل الحجز والتواصل بسيطين ومطمئنين',
                            'content' => [
                                'eyebrow' => 'صفحة الحجز',
                                'subtitle' => 'استخدم هذه الصفحة لعرض الهاتف والواتساب وساعات العمل والعنوان وأسهل طريقة للتواصل.',
                                'primary_button' => ['label' => 'استعرض الخدمات', 'url' => '/services', 'new_tab' => false],
                                'secondary_button' => ['label' => 'العودة للرئيسية', 'url' => '/', 'new_tab' => false],
                                'highlights' => ['الهاتف', 'ساعات العمل', 'الوصول السهل'],
                                'image' => 'https://images.unsplash.com/photo-1666214280557-f1b5022eb634?auto=format&fit=crop&w=1200&q=80',
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'booking-details',
                    'type' => 'features',
                    'sort_order' => 2,
                    'translations' => [
                        'en' => [
                            'title' => 'What to place on your booking page',
                            'content' => [
                                'subtitle' => 'Replace these sample details with your real clinic information.',
                                'items' => [
                                    ['icon' => '01', 'title' => 'Address', 'description' => '24 Health Street, Central District, Your City.'],
                                    ['icon' => '02', 'title' => 'Working hours', 'description' => 'Saturday to Thursday, 9:00 AM to 6:00 PM.'],
                                    ['icon' => '03', 'title' => 'Phone and WhatsApp', 'description' => '+970 599 000 000 for questions and appointment requests.'],
                                    ['icon' => '04', 'title' => 'Arrival guidance', 'description' => 'Nearby parking and a clear entrance note for first-time patients.'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'ماذا تضع في صفحة الحجز',
                            'content' => [
                                'subtitle' => 'استبدل هذه البيانات المبدئية بمعلومات العيادة الحقيقية.',
                                'items' => [
                                    ['icon' => '01', 'title' => 'العنوان', 'description' => 'شارع الصحة 24، الحي المركزي، مدينتك.'],
                                    ['icon' => '02', 'title' => 'ساعات العمل', 'description' => 'من السبت إلى الخميس، 9:00 صباحاً حتى 6:00 مساءً.'],
                                    ['icon' => '03', 'title' => 'الهاتف والواتساب', 'description' => '+970 599 000 000 للاستفسار وطلبات المواعيد.'],
                                    ['icon' => '04', 'title' => 'إرشادات الوصول', 'description' => 'مواقف قريبة وملاحظة واضحة للمدخل للمرضى الجدد.'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'booking-faq',
                    'type' => 'faq',
                    'sort_order' => 3,
                    'translations' => [
                        'en' => [
                            'title' => 'Questions before contacting the clinic',
                            'content' => [
                                'subtitle' => 'This helps patients know what to expect before they reach out.',
                                'items' => [
                                    ['question' => 'Can I edit these booking details later?', 'answer' => 'Yes. Change the address, hours, and contact details from the editor whenever needed.'],
                                    ['question' => 'Can this page work without online payment?', 'answer' => 'Yes. It can simply guide patients to phone, WhatsApp, or a manual booking workflow.'],
                                ],
                            ],
                        ],
                        'ar' => [
                            'title' => 'أسئلة قبل التواصل مع العيادة',
                            'content' => [
                                'subtitle' => 'هذا القسم يساعد المريض على معرفة ما ينتظره قبل التواصل.',
                                'items' => [
                                    ['question' => 'هل يمكنني تعديل بيانات الحجز لاحقاً؟', 'answer' => 'نعم. يمكنك تغيير العنوان وساعات العمل وبيانات التواصل من المحرر متى احتجت.'],
                                    ['question' => 'هل تصلح الصفحة بدون دفع إلكتروني؟', 'answer' => 'نعم. يمكن استخدامها فقط لتوجيه المرضى إلى الهاتف أو الواتساب أو الحجز اليدوي.'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'booking-cta',
                    'type' => 'cta',
                    'sort_order' => 4,
                    'translations' => [
                        'en' => [
                            'title' => 'Your clinic template is ready for real booking details',
                            'content' => [
                                'badge' => 'Last step',
                                'subtitle' => 'Replace the clinic name, services, location, and contact flow to make this launch-ready.',
                                'primary_button_text' => 'Go back home',
                                'primary_button_url' => '/',
                                'primary_button_new_tab' => false,
                            ],
                        ],
                        'ar' => [
                            'title' => 'قالب العيادة جاهز لبيانات الحجز الحقيقية',
                            'content' => [
                                'badge' => 'الخطوة الأخيرة',
                                'subtitle' => 'استبدل اسم العيادة والخدمات والموقع وطريقة التواصل ليصبح الموقع جاهزاً للنشر.',
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
