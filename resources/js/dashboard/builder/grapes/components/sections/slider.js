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
                pgImages: '',

                // استبدل الجزء الخاص بالـ components بهذا الكود المستقر:
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

                pgAutoPlay: true,
                pgDelay: '3000',
                pgEffect: 'slide',

                traits: [
                    // {
                    //     type: 'media-picker',
                    //     name: 'pgImages',
                    //     label: 'صور السلايدر',
                    //     multiple: true,
                    //     changeProp: 1,
                    // },
                    // {
                    //     type: 'select',
                    //     name: 'pgEffect',
                    //     label: 'تأثير الانتقال',
                    //     changeProp: 1,
                    //     options: [
                    //         { id: 'slide', name: 'إنزلاق (Slide)' },
                    //         { id: 'fade', name: 'تلاشي (Fade)' },
                    //         { id: 'coverflow', name: 'Coverflow' },
                    //     ],
                    // },
                    // {
                    //     type: 'select',
                    //     name: 'pgDelay',
                    //     label: 'سرعة التبديل',
                    //     changeProp: 1,
                    //     options: [
                    //         { id: '2000', name: 'ثانيتين' },
                    //         { id: '3000', name: '3 ثوانٍ' },
                    //         { id: '5000', name: '5 ثوانٍ' },
                    //     ],
                    // },
                    // {
                    //     type: 'checkbox',
                    //     name: 'pgAutoPlay',
                    //     label: 'تشغيل تلقائي',
                    //     changeProp: 1,
                    // },
                    {
                        type: 'text',
                        label: 'العنوان',
                        name: 'content'
                    },
                    {
                        type: 'media-picker', // هذا هو النوع الذي سجلناه
                        label: 'صورة الخلفية',
                        name: 'src', // أو 'background-image' حسب حاجتك
                    }
                ],
            },

            init() {
                this.on('change:pgImages', this.renderSlides);
                this.on('change:pgEffect change:pgAutoPlay change:pgDelay', this.initSwiper);

                // تشغيل السلايدر عند السحب والإفلات لأول مرة
                this.on('component:mount', this.initSwiper);
            },

            renderSlides() {
                const urls = (this.get('pgImages') || '').split(',').filter(Boolean);
                if (urls.length === 0) return;

                const slidesHTML = urls.map(url => `
                    <div class="swiper-slide bg-cover bg-center h-full" style="background-image: url('${url}')"></div>
                `).join('');

                const view = this.getView();
                if (view) {
                    const wrapper = view.el.querySelector('.swiper-wrapper');
                    if (wrapper) {
                        wrapper.innerHTML = slidesHTML;
                        this.initSwiper();
                    }
                }
            },

            initSwiper() {
                try {
                    const view = this.getView();
                    if (!view || !view.el) return;

                    const el = view.el.querySelector('.swiper');
                    if (!el) return;

                    if (el.swiper) el.swiper.destroy(true, true);

                    const swiperLib = view.el.ownerDocument.defaultView.Swiper || window.Swiper;

                    if (swiperLib) {
                        new swiperLib(el, {
                            // ... إعداداتك ...
                            loop: true,
                        });
                    } else {
                        console.warn("Swiper library not found inside canvas");
                    }
                } catch (error) {
                    console.error("Failed to initialize Swiper:", error);
                }
            }
        },
    });
}