export function registerServicesSection(editor) {
    const bm = editor.BlockManager;
    const dc = editor.DomComponents;

    dc.addType('pg-services-section', {
        model: {
            defaults: {
                tagName: 'section',
                droppable: true,
                attributes: {
                    'data-pg-section': 'services',
                    'data-pg-title': 'خدمات رقمية متكاملة تنطلق بك نحو النجاح',
                    'data-pg-subtitle':
                        'كل ما تحتاجه لبناء مشروعك الرقمي بنجاح: تصميم احترافي، استضافة سريعة، تسويق فعّال، ودعم فني مستمر – حلول متكاملة من فريق واحد.',
                    dir: 'rtl',
                    'aria-label': 'خدمات رقمية متكاملة',
                },
                classes: ['py-20', 'px-4', 'sm:px-8', 'lg:px-24', 'bg-white'],

                // ✅ component tree واحد فقط
                components: [
                    {
                        tagName: 'div',
                        attributes: { class: 'relative' },
                        components: [
                            {
                                tagName: 'div',
                                attributes: { class: 'relative z-10 max-w-7xl mx-auto' },
                                components: [
                                    // Header
                                    {
                                        tagName: 'div',
                                        attributes: { class: 'text-center mb-16' },
                                        components: [
                                            {
                                                type: 'Heading',
                                                tagName: 'h2',
                                                attributes: {
                                                    class:
                                                        'text-3xl sm:text-4xl font-extrabold text-primary mb-4 tracking-tight',
                                                    'data-pg-field': 'title',
                                                },
                                                content: 'خدمات رقمية متكاملة تنطلق بك نحو النجاح',
                                            },
                                            {
                                                type: 'text',
                                                tagName: 'p',
                                                attributes: {
                                                    class:
                                                        'text-tertiary text-base sm:text-lg max-w-2xl mx-auto',
                                                    'data-pg-field': 'subtitle',
                                                },
                                                content:
                                                    'كل ما تحتاجه لبناء مشروعك الرقمي بنجاح: تصميم احترافي، استضافة سريعة، تسويق فعّال، ودعم فني مستمر – حلول متكاملة من فريق واحد.',
                                            },
                                        ],
                                    },

                                    // ✅ Dynamic placeholder (هذا الذي سيُستبدل في الـ frontend)
                                    {
                                        tagName: 'div',
                                        attributes: {
                                            'data-pg-dynamic': 'services',
                                            class:
                                                'pg-dynamic-placeholder rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-slate-500',
                                        },
                                        components: [
                                            {
                                                type: 'text',
                                                tagName: 'div',
                                                content:
                                                    'Dynamic: services (سيتم استبداله من قاعدة البيانات في الواجهة)',
                                            },
                                        ],
                                    },

                                    // OPTIONAL: Preview Slider داخل البيلدر (إذا بدك)
                                    // إذا بتحس أنه يسبب تشويش… احذفه وخلي placeholder فقط
                                    {
                                        tagName: 'div',
                                        attributes: {
                                            class: 'swiper mySwiper mt-10',
                                            role: 'region',
                                            'aria-label': 'قائمة الخدمات الرقمية (Preview)',
                                        },
                                        components: [
                                            {
                                                tagName: 'div',
                                                attributes: {
                                                    class:
                                                        'swiper-wrapper flex gap-6 overflow-x-auto pb-4 snap-x snap-mandatory',
                                                    style: 'scrollbar-width: thin;',
                                                },
                                                components: [
                                                    serviceSlide(
                                                        'الاستضافة المشتركة',
                                                        'استضافة قوية واقتصادية لموقعك، مع شهادة SSL مجانية وسرعة تشغيل عالية.'
                                                    ),
                                                    serviceSlide(
                                                        'استضافة ووردبريس',
                                                        'تمتع بأداء عالٍ وأمان كامل لموقعك على ووردبريس مع دعم فني دائم.'
                                                    ),
                                                    serviceSlide(
                                                        'حجز اسم نطاق (دومين)',
                                                        'احجز اسم موقعك بسهولة واختر من بين مجموعة واسعة من الامتدادات العالمية.'
                                                    ),
                                                ],
                                            },
                                        ],
                                    },
                                ],
                            },
                        ],
                    },
                ],

                traits: [
                    { type: 'text', name: 'data-pg-title', label: 'العنوان' },
                    { type: 'text', name: 'data-pg-subtitle', label: 'الوصف' },
                    {
                        type: 'number',
                        name: 'data-pg-limit',
                        label: 'عدد الخدمات (Limit)',
                        placeholder: '6',
                        min: 1,
                        max: 50,
                    },
                    {
                        type: 'select',
                        name: 'data-pg-order',
                        label: 'الترتيب',
                        options: [
                            { id: 'order', name: 'حسب الحقل order' },
                            { id: 'latest', name: 'الأحدث' },
                        ],
                    },
                    {
                        type: 'select',
                        name: 'dir',
                        label: 'الاتجاه',
                        options: [
                            { id: 'rtl', name: 'RTL' },
                            { id: 'ltr', name: 'LTR' },
                        ],
                    },
                ],
            },

            init() {
                const sync = () => {
                    const attrs = this.getAttributes() || {};
                    const title = attrs['data-pg-title'] || '';
                    const sub = attrs['data-pg-subtitle'] || '';

                    const titleEl = this.find('[data-pg-field="title"]')?.[0];
                    const subEl = this.find('[data-pg-field="subtitle"]')?.[0];

                    const setText = (cmp, value) => {
                        if (!cmp) return;
                        // ✅ آمن ومباشر
                        cmp.components(value);
                        cmp.view?.render?.();
                    };

                    setText(titleEl, title);
                    setText(subEl, sub);
                };

                sync();
                this.on('change:attributes', sync);
            },
        },
    });

    bm.add('pg-services', {
        label: 'الخدمات (Dynamic)',
        category: 'Sections',
        attributes: { class: 'pg-widget-tile' },
        content: { type: 'pg-services-section' },
    });
}

function serviceSlide(title, desc) {
    return {
        tagName: 'div',
        attributes: {
            class: 'swiper-slide min-w-[280px] sm:min-w-[320px] snap-start',
        },
        components: [
            {
                tagName: 'div',
                attributes: {
                    class:
                        'bg-white rounded-3xl shadow p-6 border border-primary/10 text-center',
                },
                components: [
                    { type: 'text', tagName: 'h3', content: title, attributes: { class: 'font-bold text-primary mb-2' } },
                    { type: 'text', tagName: 'p', content: desc, attributes: { class: 'text-tertiary text-sm' } },
                ],
            },
        ],
    };
}
