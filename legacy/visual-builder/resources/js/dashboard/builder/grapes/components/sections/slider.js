/**
 * تسجيل قسم السلايدر (pg-slider-section) في GrapesJS
 */
export function registerSliderSection(editor) {
    const dc = editor.DomComponents;

    dc.addType('pg-slider-section', {
        isComponent: el => el.classList?.contains('pg-slider-wrapper'),

        model: {
            defaults: {
                selectable: true,
                hoverable: true,
                draggable: true,
                droppable: false,
                tagName: 'section',
                name: 'Slider Section',
                attributes: { class: 'pg-slider-wrapper my-8 relative' },

                // الخصائص (لا تبدأ بـ pgAdv لتظهر في تبويب المحتوى)
                pgImages: '', // روابط الصور مفصولة بفاصلة
                pgAutoPlay: true,
                pgDelay: '3000',
                pgEffect: 'slide',

                // الهيكل الداخلي المستقر للسلايدر
                components: `
                    <div class="swiper pg-slider-container h-[500px] w-full rounded-2xl overflow-hidden bg-slate-100">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide bg-slate-300 flex items-center justify-center bg-cover bg-center" 
                                 style="background-image: url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=1200&q=80')">
                                 <h2 class="text-white text-3xl font-bold">Slide 1</h2>
                            </div>
                            <div class="swiper-slide bg-slate-400 flex items-center justify-center bg-cover bg-center" 
                                 style="background-image: url('https://images.unsplash.com/photo-1470770841072-f978cf4d019e?w=1200&q=80')">
                                 <h2 class="text-white text-3xl font-bold">Slide 2</h2>
                            </div>
                        </div>
                        <div class="swiper-pagination"></div>
                        <div class="swiper-button-next !text-white"></div>
                        <div class="swiper-button-prev !text-white"></div>
                    </div>
                `,

                traits: [
                    {
                        type: 'media-picker', // استخدام النوع الذي سجلناه في media.js
                        name: 'pgImages',
                        label: 'صور السلايدر',
                    },
                    {
                        type: 'select',
                        name: 'pgEffect',
                        label: 'تأثير الانتقال',
                        options: [
                            { id: 'slide', name: 'إنزلاق (Slide)' },
                            { id: 'fade', name: 'تلاشي (Fade)' },
                            { id: 'coverflow', name: 'Coverflow' },
                        ],
                    },
                    {
                        type: 'checkbox',
                        name: 'pgAutoPlay',
                        label: 'تشغيل تلقائي',
                    }
                ],
            },

            init() {
                // مراقبة التغييرات لتحديث السلايدر فوراً
                this.on('change:pgImages', this.renderSlides);
                this.on('change:pgEffect change:pgAutoPlay change:pgDelay', this.initSwiper);

                // تشغيل السلايدر عند التحميل لأول مرة
                this.on('component:mount', this.initSwiper);
            },

            /**
             * تحديث شرائح السلايدر بناءً على الصور المختارة
             */
            renderSlides() {
                const urls = (this.get('pgImages') || '').split(',').filter(Boolean);
                if (urls.length === 0) return;

                const slidesHTML = urls.map((url, index) => `
                    <div class="swiper-slide bg-cover bg-center h-full flex items-center justify-center" 
                         style="background-image: url('${url}')">
                         <h2 class="text-white text-3xl font-bold">Slide ${index + 1}</h2>
                    </div>
                `).join('');

                const view = this.getView();
                if (view) {
                    const wrapper = view.el.querySelector('.swiper-wrapper');
                    if (wrapper) {
                        wrapper.innerHTML = slidesHTML;
                        this.initSwiper(); // إعادة تشغيل السلايدر
                    }
                }
            },

            /**
             * تشغيل مكتبة Swiper داخل الـ Canvas
             */
            initSwiper() {
                try {
                    const view = this.getView();
                    if (!view || !view.el) return;

                    const el = view.el.querySelector('.swiper');
                    if (!el) return;

                    // تدمير النسخة السابقة إذا وجدت لمنع التكرار
                    if (el.swiper) el.swiper.destroy(true, true);

                    // الوصول لمكتبة Swiper من نافذة الـ Canvas
                    const swiperLib = view.el.ownerDocument.defaultView.Swiper || window.Swiper;

                    if (swiperLib) {
                        new swiperLib(el, {
                            effect: this.get('pgEffect') || 'slide',
                            autoplay: this.get('pgAutoPlay') ? { delay: 3000 } : false,
                            loop: true,
                            pagination: { el: '.swiper-pagination', clickable: true },
                            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                        });
                    }
                } catch (error) {
                    console.warn("Swiper initialization failed:", error);
                }
            }
        },
    });
}