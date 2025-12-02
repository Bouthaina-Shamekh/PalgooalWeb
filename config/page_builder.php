<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page Builder Blocks Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines all available blocks (sections) that can be used
    | inside the Page Builder. Each block is identified by a unique "key"
    | and contains metadata such as:
    |
    | - label          : human readable name shown in the UI
    | - description    : short explanation for the admin/user
    | - contexts       : where this block can be used (marketing, tenant, etc.)
    | - template       : Blade view that will be used to render this block
    | - default_content: default JSON structure stored in section_translations
    |
    | For now, we focus on the "marketing" context (Palgoals main website).
    | Later, we can extend this to support "tenant" (client sites).
    |
    */

    'blocks' => [

        // =========================
        // HERO SECTION (MARKETING)
        // =========================
        'hero_default' => [
            'label'       => 'Default Hero',
            'description' => 'Main hero section with title, subtitle, button and image.',
            'contexts'    => ['marketing'], // later: ['marketing', 'tenant']
            'template'    => 'marketing.sections.hero_default', // Blade view path

            // This structure will be stored as JSON in `section_translations.content`
            'default_content' => [
                'title'        => 'أطلق موقعك الاحترافي في دقائق',
                'subtitle'     => 'قوالب ووردبريس جاهزة مع استضافة ودومين من Palgoals.',
                'button_text'  => 'ابدأ الآن',
                'button_url'   => '#',
                'image'        => null, // can be replaced with a media URL or ID
            ],
        ],

        // =========================
        // FEATURES GRID
        // =========================
        'features_grid' => [
            'label'       => 'Features Grid',
            'description' => 'Display a list of key features in a responsive grid.',
            'contexts'    => ['marketing'],
            'template'    => 'marketing.sections.features_grid',

            'default_content' => [
                'title'   => 'مميزات منصتنا',
                'subtitle' => 'نقدم لك حلاً متكاملاً لإطلاق موقعك بدون تعقيد.',
                'items'   => [
                    [
                        'icon'        => 'icons/rocket.svg',
                        'title'       => 'إطلاق سريع',
                        'description' => 'ابدأ موقعك خلال دقائق بدون إعدادات معقدة.',
                    ],
                    [
                        'icon'        => 'icons/shield.svg',
                        'title'       => 'استضافة آمنة',
                        'description' => 'سيرفرات مستقرة ومحمية لتضمن استمرارية عمل موقعك.',
                    ],
                    [
                        'icon'        => 'icons/support.svg',
                        'title'       => 'دعم فني مستمر',
                        'description' => 'فريقنا جاهز لمساعدتك في كل خطوة.',
                    ],
                ],
            ],
        ],

        // =========================
        // PRICING SECTION
        // =========================
        'pricing_simple' => [
            'label'       => 'Simple Pricing',
            'description' => 'Simple pricing table for 2–3 main plans.',
            'contexts'    => ['marketing'],
            'template'    => 'marketing.sections.pricing_simple',

            'default_content' => [
                'title'    => 'اختر الخطة المناسبة لعملك',
                'subtitle' => 'خطط مرنة تناسب مختلف مراحل مشروعك.',
                'plans'    => [
                    [
                        'name'        => 'Basic',
                        'price'       => '19',
                        'currency'    => 'USD',
                        'period'      => 'شهرياً',
                        'features'    => [
                            'قالب واحد',
                            'استضافة مشتركة',
                            'دعم عبر البريد',
                        ],
                        'button_text' => 'ابدأ مع Basic',
                        'button_url'  => '#',
                        'highlight'   => false,
                    ],
                    [
                        'name'        => 'Pro',
                        'price'       => '39',
                        'currency'    => 'USD',
                        'period'      => 'شهرياً',
                        'features'    => [
                            'كل قوالب Palgoals',
                            'استضافة أسرع',
                            'دعم أولوية',
                        ],
                        'button_text' => 'اشترك في Pro',
                        'button_url'  => '#',
                        'highlight'   => true,
                    ],
                ],
            ],
        ],

        // =========================
        // CTA BANNER
        // =========================
        'cta_banner' => [
            'label'       => 'Call To Action Banner',
            'description' => 'Full-width CTA to drive users to a main action.',
            'contexts'    => ['marketing'],
            'template'    => 'marketing.sections.cta_banner',

            'default_content' => [
                'title'       => 'جاهز تطلق موقعك اليوم؟',
                'subtitle'    => 'ابدأ رحلتك الرقمية مع Palgoals في أقل من 5 دقائق.',
                'button_text' => 'جرّب الآن',
                'button_url'  => '#',
            ],
        ],

        // =========================
        // TESTIMONIALS
        // =========================
        'testimonials_basic' => [
            'label'       => 'Basic Testimonials',
            'description' => 'Show customer testimonials in a clean layout.',
            'contexts'    => ['marketing'],
            'template'    => 'marketing.sections.testimonials_basic',

            'default_content' => [
                'title'    => 'ماذا يقول عملاؤنا؟',
                'subtitle' => 'ثقة عملائنا هي سر قوتنا.',
                'items'    => [
                    [
                        'name'    => 'عميل متجر إلكتروني',
                        'role'    => 'صاحب متجر',
                        'message' => 'منصة Palgoals ساعدتنا نطلق متجرنا خلال ساعات بدل أسابيع.',
                    ],
                    [
                        'name'    => 'عميل خدمات',
                        'role'    => 'مستشار',
                        'message' => 'القوالب الجاهزة وفّرت علينا تكلفة التصميم من الصفر.',
                    ],
                ],
            ],
        ],

    ],

];
