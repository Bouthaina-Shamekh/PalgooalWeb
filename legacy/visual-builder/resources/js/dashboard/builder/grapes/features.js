export function registerFeaturesSection(editor) {
    const domc = editor.DomComponents;
    const bm = editor.BlockManager;
    const tm = editor.TraitManager;

    const isRtl =
        document.documentElement.dir === 'rtl' ||
        document.body.dir === 'rtl';

    /**
     * ------------------------------------------------------------------
     * Trait: زر "إضافة ميزة جديدة" داخل سكشن المميزات نفسه
     * ------------------------------------------------------------------
     */
    tm.addType('pg-add-feature', {
        createInput() {
            const root = document.createElement('div');
            root.className = 'pg-features-controls flex flex-col gap-1.5';

            root.innerHTML = `
                <button type="button"
                    class="pg-add-feature-btn gjs-btn-prim w-full text-[11px] py-1.5 rounded-md !bg-primary !text-white hover:opacity-90">
                    ${isRtl ? '➕ إضافة ميزة جديدة' : '➕ Add feature'}
                </button>
                <small class="text-[10px] text-slate-500">
                    ${isRtl
                    ? 'يمكنك أيضًا نسخ بطاقات المميزات يدويًا من داخل السكشن.'
                    : 'You can also duplicate feature cards directly in the canvas.'}
                </small>
            `;

            const btn = root.querySelector('.pg-add-feature-btn');
            btn.addEventListener('click', (e) => this.onAddFeature(e));

            return root;
        },

        onAddFeature(e) {
            if (e?.preventDefault) {
                e.preventDefault();
                e.stopPropagation();
            }

            // السكشن الحالي
            const section = this.target || editor.getSelected();
            if (!section) return;

            // الجريد الذى يحتوى بطاقات المميزات
            const gridCmp = section.find('[data-pg-features-grid="1"]')[0] || section;
            const children = gridCmp.components();

            let newCard;

            if (children.length) {
                // استنساخ آخر كرت موجود
                const sourceCard = children.at(children.length - 1);
                newCard = sourceCard.clone();
                gridCmp.append(newCard);
            } else {
                // لا يوجد كروت → أنشئ كرت جديد مطابق للتصميم الافتراضى
                newCard = gridCmp.append({
                    tagName: 'article',
                    attributes: {
                        class: 'pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100',
                    },
                    components: [
                        {
                            tagName: 'div',
                            attributes: {
                                class: 'flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4',
                            },
                            components: [
                                {
                                    type: 'text',
                                    content: '★',
                                },
                            ],
                        },
                        {
                            tagName: 'h3',
                            attributes: {
                                class: 'text-lg font-semibold text-slate-900 mb-2',
                            },
                            components: [
                                {
                                    type: 'text',
                                    content: isRtl ? 'عنوان الميزة' : 'Feature title',
                                },
                            ],
                        },
                        {
                            tagName: 'p',
                            attributes: {
                                class: 'text-sm text-slate-600 leading-relaxed',
                            },
                            components: [
                                {
                                    type: 'text',
                                    content: isRtl
                                        ? 'وصف مختصر للميزة يوضح فائدتها للمستخدم.'
                                        : 'Short description that explains the benefit.',
                                },
                            ],
                        },
                    ],
                })[0];
            }

            if (newCard) {
                editor.select(newCard);
                editor.trigger('change:canvasOffset');
            }
        },
    });

    /**
     * ------------------------------------------------------------------
     * نوع الكمبوننت: سكشن المميزات pg-features-section
     * ------------------------------------------------------------------
     */
    domc.addType('pg-features-section', {
        isComponent(el) {
            // نسمح للتعرّف إما عن طريق data-gjs-type أو data-pg-section
            if (!el || !el.getAttribute) return false;
            return (
                el.getAttribute('data-gjs-type') === 'pg-features-section' ||
                el.getAttribute('data-pg-section') === 'features'
            );
        },

        model: {
            defaults: {
                tagName: 'section',
                attributes: {
                    'data-pg-section': 'features',
                },
                classes: [
                    'py-24',
                    'px-4',
                    'sm:px-8',
                    'lg:px-20',
                    'bg-[#F9F6FB]',
                ],
                traits: [
                    {
                        type: 'text',
                        label: isRtl ? 'العنوان الرئيسي' : 'Main title',
                        name: 'data-pg-title',
                    },
                    {
                        type: 'textarea',
                        label: isRtl ? 'الوصف (Subtitle)' : 'Subtitle',
                        name: 'data-pg-subtitle',
                        rows: 3,
                    },
                    {
                        type: 'pg-add-feature',
                        label: isRtl ? 'إدارة المميزات' : 'Features',
                        name: 'pg-add-feature',
                    },
                ],
            },

            init() {
                this.on('change:attributes:data-pg-title', this.updateTitleFromAttr);
                this.on('change:attributes:data-pg-subtitle', this.updateSubtitleFromAttr);
            },

            updateTitleFromAttr() {
                const title = this.getAttributes()['data-pg-title'] || '';
                const header = this.find('h2')[0];
                if (header && title) {
                    header.components(title);
                }
            },

            updateSubtitleFromAttr() {
                const subtitle = this.getAttributes()['data-pg-subtitle'] || '';
                const subEl = this.find('p')[0];
                if (subEl) {
                    subEl.components(subtitle || '');
                }
            },
        },
    });

    /**
     * ------------------------------------------------------------------
     * Block: Features Section (كما كان من قبل)
     * ------------------------------------------------------------------
     */
    bm.add('pg-features-section', {
        id: 'pg-features-section',
        label: isRtl ? 'سكشن المميزات' : 'Features Section',
        category: isRtl ? 'سكاشن المحتوى' : 'Sections',
        attributes: { class: 'gjs-fonts gjs-f-b1' },
        content: `
      <section class="py-24 px-4 sm:px-8 lg:px-20 bg-[#F9F6FB]" data-gjs-type="pg-features-section">
        <div class="max-w-6xl mx-auto">
          <!-- Head -->
          <div class="text-center mb-14">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-primary mb-3 tracking-tight">
              ${isRtl ? 'خدمات رقمية متكاملة تدعم نجاحك' : 'All-in-one digital services for your success'}
            </h2>
            <p class="text-tertiary text-base sm:text-lg max-w-2xl mx-auto">
              ${isRtl
                ? 'خدمات قيمة متكاملة تساعدك على إطلاق مشروعك بثقة، واستضافة سريعة، وقوالب احترافية.'
                : 'Valuable services that help you launch your project with confidence.'}
            </p>
          </div>

          <!-- Features Grid -->
          <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3" data-pg-features-grid="1">

            <!-- Feature item 1 -->
            <article class="pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100">
              <div class="flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4">
                <span class="text-lg font-bold">★</span>
              </div>
              <h3 class="text-lg font-semibold text-slate-900 mb-2">
                ${isRtl ? 'إطلاق سريع' : 'Fast launch'}
              </h3>
              <p class="text-sm text-slate-600 leading-relaxed">
                ${isRtl
                ? 'امتلك موقعك الجاهز خلال دقائق مع إعداد تلقائي كامل.'
                : 'Get your website live in minutes with full automatic setup.'}
              </p>
            </article>

            <!-- Feature item 2 -->
            <article class="pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100">
              <div class="flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4">
                <span class="text-lg font-bold">★</span>
              </div>
              <h3 class="text-lg font-semibold text-slate-900 mb-2">
                ${isRtl ? 'تصاميم احترافية' : 'Professional designs'}
              </h3>
              <p class="text-sm text-slate-600 leading-relaxed">
                ${isRtl
                ? 'قوالب مصممة بعناية لتناسب مختلف الأنشطة والمتاجر.'
                : 'Carefully crafted templates for different niches.'}
              </p>
            </article>

            <!-- Feature item 3 -->
            <article class="pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100">
              <div class="flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4">
                <span class="text-lg font-bold">★</span>
              </div>
              <h3 class="text-lg font-semibold text-slate-900 mb-2">
                ${isRtl ? 'دعم فني مستمر' : 'Ongoing support'}
              </h3>
              <p class="text-sm text-slate-600 leading-relaxed">
                ${isRtl
                ? 'فريق مختص لمساعدتك في أي وقت خلال رحلتك الرقمية.'
                : 'A dedicated team ready to help you anytime.'}
              </p>
            </article>

          </div>
        </div>
      </section>
    `,
    });
}