// resources/js/dashboard/page-builder.js
import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

/**
 * -------------------------------------------------------------
 * Helpers
 * -------------------------------------------------------------
 */
const q = (sel, root = document) => root.querySelector(sel);
const qa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

function setStatus(text, dotState = 'idle') {
    const root = q('#builder-save-status');
    if (!root) return;

    const textEl = root.querySelector('[data-status-text]');
    const timeEl = root.querySelector('[data-status-time]');
    const dotEl = root.querySelector('[data-status-dot]');

    if (textEl) textEl.textContent = text;

    if (timeEl) {
        const d = new Date();
        const hh = String(d.getHours()).padStart(2, '0');
        const mm = String(d.getMinutes()).padStart(2, '0');
        timeEl.textContent = `${hh}:${mm}`;
    }

    if (dotEl) {
        dotEl.classList.remove(
            'bg-amber-400',
            'bg-emerald-500',
            'bg-red-500',
            'bg-sky-500',
            'animate-pulse',
        );

        if (dotState === 'dirty') dotEl.classList.add('bg-amber-400', 'animate-pulse');
        else if (dotState === 'saving') dotEl.classList.add('bg-sky-500', 'animate-pulse');
        else if (dotState === 'saved') dotEl.classList.add('bg-emerald-500');
        else if (dotState === 'error') dotEl.classList.add('bg-red-500');
        else dotEl.classList.add('bg-amber-400');
    }
}

async function fetchJson(url, { method = 'GET', body = null, headers = {} } = {}) {
    const csrf = q('meta[name="csrf-token"]')?.content || '';

    const res = await fetch(url, {
        method,
        credentials: 'include',
        headers: {
            Accept: 'application/json',
            ...(method !== 'GET' ? { 'Content-Type': 'application/json' } : {}),
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            ...headers,
        },
        ...(body ? { body: JSON.stringify(body) } : {}),
    });

    const isJson = (res.headers.get('content-type') || '').includes('application/json');
    const data = isJson ? await res.json() : await res.text();

    if (!res.ok) {
        const msg = (data && data.message) ? data.message : `Request failed (${res.status})`;
        throw new Error(msg);
    }

    return data;
}

function isNonEmptyObject(v) {
    return v && typeof v === 'object' && !Array.isArray(v) && Object.keys(v).length > 0;
}

/**
 * -------------------------------------------------------------
 * Bootstrap
 * -------------------------------------------------------------
 */
const root = document.getElementById('page-builder-root');

if (root) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const loadUrl = root.dataset.loadUrl;
    const saveUrl = root.dataset.saveUrl;
    const previewUrl = root.dataset.previewUrl;
    const builderUrl = root.dataset.builderUrl;
    const resetBtn = q('#builder-reset');
    const langToggle = q('#builder-lang-toggle');
    const langMenu = q('#builder-lang-menu');

    // اتجاه لوحة التحكم (rtl / ltr)
    const appDir = document.documentElement.getAttribute('dir') || 'ltr';
    const isRtl = appDir === 'rtl';
    const emptyHint = isRtl
        ? 'ابدأ بسحب بلوك من اليمين…'
        : 'Start by dragging a block from the right…';

    // UI elements
    const emptyState = q('#builder-empty-state');
    const saveBtn = q('#builder-save');

    // Preview controls
    const previewToggleBtn = q('#preview-toggle-btn');
    const previewMenu = q('#preview-menu');
    const previewLabel = q('[data-preview-label]');
    const previewBtns = qa('.builder-preview-btn'); // Desktop / Tablet / Mobile

    // Sidebar containers
    const elBlocks = q('#gjs-blocks');
    const elLayers = q('#gjs-layers');
    const elTraits = q('#gjs-traits');
    const elStyles = q('#gjs-styles');

    /**
     * ---------------------------------------------------------
     * Tabs (Blocks / Outline)
     * ---------------------------------------------------------
     */
    function initTabs() {
        const tabBtns = qa('.builder-tab[data-tab-target]');
        const tabContents = qa('.builder-tab-content[data-tab-content]');
        const helpers = qa('[data-tab-helper]');

        const setActive = (name) => {
            tabBtns.forEach((btn) => {
                const active = btn.dataset.tabTarget === name;
                btn.classList.toggle('active', active);
                btn.dataset.selected = active ? 'true' : 'false';
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            tabContents.forEach((c) => {
                const active = c.dataset.tabContent === name;
                c.classList.toggle('active', active);
                c.classList.toggle('hidden', !active);
                c.setAttribute('aria-hidden', active ? 'false' : 'true');
            });

            helpers.forEach((h) => {
                h.classList.toggle('hidden', h.dataset.tabHelper !== name);
            });
        };

        tabBtns.forEach((btn) =>
            btn.addEventListener('click', () => setActive(btn.dataset.tabTarget)),
        );

        setActive('palette');
    }

    /**
     * ---------------------------------------------------------
     * Preview (devices)
     * ---------------------------------------------------------
     */
    function initPreviewDropdown(editor) {
        if (!previewBtns.length && !previewToggleBtn && !previewMenu) return;

        const deviceMap = {
            desktop: 'Desktop',
            tablet: 'Tablet',
            mobile: 'Mobile',
        };

        const deviceLabelMap = {
            desktop: 'Desktop',
            tablet: 'Tablet',
            mobile: 'Mobile',
        };

        function updateDeviceButtons(activeId) {
            previewBtns.forEach((btn) => {
                const isActive = btn.dataset.preview === activeId;

                btn.classList.toggle('bg-white', isActive);
                btn.classList.toggle('text-slate-900', isActive);
                btn.classList.toggle('shadow-sm', isActive);

                btn.classList.toggle('bg-transparent', !isActive);
                btn.classList.toggle('text-slate-500', !isActive);
            });

            if (previewLabel && deviceLabelMap[activeId]) {
                previewLabel.textContent = deviceLabelMap[activeId];
            }
        }

        function setBuilderDevice(id) {
            const deviceName = deviceMap[id] || 'Desktop';
            editor.setDevice(deviceName);
            updateDeviceButtons(id);
        }

        if (previewBtns.length) {
            previewBtns.forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const id = btn.dataset.preview;
                    setBuilderDevice(id);
                });
            });

            setBuilderDevice('desktop');
        }

        if (previewToggleBtn && previewMenu) {
            const close = () => previewMenu.classList.remove('open');
            const toggle = () => previewMenu.classList.toggle('open');

            previewToggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                toggle();
            });

            document.addEventListener('click', (e) => {
                const inside =
                    previewMenu.contains(e.target) || previewToggleBtn.contains(e.target);
                if (!inside) close();
            });
        }
    }

    /**
     * ---------------------------------------------------------
     * Blocks (Hero / Header / Services / Basic)
     * ---------------------------------------------------------
     */
    function registerBlocks(editor) {
        const bm = editor.BlockManager;

        // Global direction flag (rtl / ltr)
        const isRtl =
            document.documentElement.dir === 'rtl' ||
            document.body.dir === 'rtl';

        // Shared preview image (hero / templates)
        const heroImage = '/assets/tamplate/images/template.webp';

        // Hero content
        const heroTitle = isRtl
            ? 'أطلق موقعك الاحترافي في دقائق'
            : 'Launch your professional website in minutes';

        const heroSubtitle = isRtl
            ? 'منصة متكاملة لتصميم واستضافة موقعك مع دومين جاهز وربط كامل خلال دقائق، بدون تعقيد تقني.'
            : 'All-in-one platform to design and host your website with a ready domain in minutes — no technical hassle.';

        const primaryText = isRtl ? 'ابدأ الآن' : 'Get Started';
        const secondaryText = isRtl ? 'استكشف المزايا' : 'Explore features';

        const heroDirectionClass = isRtl ? 'md:flex-row-reverse' : 'md:flex-row';

        // Features content
        const featuresSectionTitle = isRtl
            ? 'خدمات رقمية متكاملة تدعم نجاحك'
            : 'All-in-one digital services for your success';

        const featuresSectionSubtitle = isRtl
            ? 'منصة واحدة تجمع بين الاستضافة، القوالب الجاهزة، وربط الدومين خلال دقائق.'
            : 'One platform that brings hosting, ready-made templates and domain connection in minutes.';

        const featuresConfig = isRtl
            ? [
                {
                    title: 'إطلاق سريع',
                    description: 'امتلك موقعك الجاهز خلال دقائق مع إعداد تلقائي كامل.',
                },
                {
                    title: 'تصاميم احترافية',
                    description: 'قوالب مصممة بعناية لتناسب مختلف الأنشطة والمتاجر.',
                },
                {
                    title: 'دعم فني مستمر',
                    description: 'فريق مختص لمساعدتك في أي وقت خلال رحلتك الرقمية.',
                },
                {
                    title: 'أداء عالي',
                    description: 'استضافة مستقرة وسريعة لتجربة استخدام مميزة.',
                },
                {
                    title: 'مرونة التخصيص',
                    description: 'تحكم في محتوى موقعك بسهولة بدون خبرة برمجية.',
                },
                {
                    title: 'تكاملات جاهزة',
                    description: 'ربط مع بوابات الدفع وأدوات التسويق بكل سهولة.',
                },
            ]
            : [
                {
                    title: 'Fast launch',
                    description: 'Get your website live in minutes with full automatic setup.',
                },
                {
                    title: 'Professional designs',
                    description: 'Carefully crafted templates for different niches and stores.',
                },
                {
                    title: 'Ongoing support',
                    description: 'A dedicated team ready to help you throughout your journey.',
                },
                {
                    title: 'High performance',
                    description: 'Stable and fast hosting for a great user experience.',
                },
                {
                    title: 'Flexible customization',
                    description: 'Easily manage your content without any technical background.',
                },
                {
                    title: 'Ready integrations',
                    description: 'Connect payment gateways and marketing tools in no time.',
                },
            ];

        const featuresItemsHtml = featuresConfig
            .map(
                (item, index) => `
<div class="group rounded-2xl bg-white/90 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-700
           p-5 sm:p-6 shadow-[0_10px_30px_rgba(15,23,42,0.06)]
           hover:shadow-[0_18px_40px_rgba(15,23,42,0.14)]
           transition-all duration-200"
     data-gjs-name="Feature Item"
     data-feature-index="${index}">
  <div class="flex flex-col items-center sm:items-start gap-4">
    <div class="w-12 h-12 flex items-center justify-center rounded-xl
                bg-primary/10 text-primary
                group-hover:bg-primary group-hover:text-white
                transition-colors duration-200 shrink-0">
      <!-- Placeholder icon circle (you can later replace with SVG via editor) -->
      <span class="w-2 h-2 rounded-full bg-current shadow-[0_0_0_3px_rgba(255,255,255,0.35)]"></span>
    </div>
    <span class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white text-center sm:text-start"
          data-field="feature-title">
      ${item.title}
    </span>
  </div>
  <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed text-center sm:text-start"
     data-field="feature-description">
    ${item.description}
  </p>
</div>`.trim()
            )
            .join('\n');

        const featuresSectionHtml = `
<section data-section-type="features"
         data-gjs-name="Features Section"
         class="py-20 sm:py-24 lg:py-28 px-4 sm:px-6 lg:px-8 bg-background" dir="auto">
  <div class="container-xx">
    <!-- Section heading -->
    <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-14 lg:mb-16">
      <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-primary tracking-tight mb-4"
          data-field="title">
        ${featuresSectionTitle}
      </h2>
      <p class="text-tertiary text-sm sm:text-base leading-relaxed"
         data-field="subtitle">
        ${featuresSectionSubtitle}
      </p>
    </div>

    <!-- Main grid: illustration + features cards -->
    <div class="grid gap-12 lg:gap-16 lg:grid-cols-5 items-center">
      <!-- Illustration (optional static preview image) -->
      <div class="lg:col-span-2 flex justify-center" data-gjs-name="Illustration">
        <img
          src="/assets/tamplate/images/Fu.svg"
          alt="Platform features"
          class="max-w-[260px] sm:max-w-sm lg:max-w-[420px] w-full h-auto object-contain mx-auto
                 animate-fade-in-up transition-transform duration-500 ease-out hover:scale-105"
          loading="lazy"
        />
      </div>

      <!-- Features list -->
      <div class="lg:col-span-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8"
             data-gjs-name="Features Grid">
          ${featuresItemsHtml}
        </div>
      </div>
    </div>
  </div>
</section>`.trim();

        const iconHero = `
<svg viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round">
  <rect x="3.5" y="5" width="17" height="14" rx="2.5"></rect>
  <path d="M8 9h8M7 13h4M7 16h3"></path>
</svg>`.trim();

        const iconFeatures = `
<svg viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round">
  <rect x="3" y="4" width="18" height="16" rx="2.5"></rect>
  <path d="M8 9h8M8 13h5M8 17h3"></path>
</svg>`.trim();

        const iconText = `
<svg viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round">
  <path d="M5 7h14M5 12h10M5 17h7"></path>
</svg>`.trim();

        const iconButton = `
<svg viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round">
  <rect x="4" y="9" width="16" height="6" rx="3"></rect>
  <path d="M9 12h6"></path>
</svg>`.trim();

        const makeLabel = (iconSvg, title) => `
<div class="pg-block-card">
  <div class="pg-block-icon">
    ${iconSvg}
  </div>
  <div class="pg-block-title">
    ${title}
  </div>
</div>
`.trim();

        const heroContent = `
<section data-section-type="hero"
         data-gjs-name="Hero"
         class="relative bg-gradient-to-tr from-primary to-primary shadow-2xl overflow-hidden -mt-20">
  <img src="${heroImage}"
       alt="Palgoals templates preview"
       fetchpriority="high"
       class="absolute inset 0 z-0 opacity-80 w-full h-full object-cover object-center ltr:scale-x-[-1] rtl:scale-x-100 transition-transform duration-500 ease-in-out"
       aria-hidden="true"
       decoding="async"
       loading="eager" />

  <div class="relative z-10 px-4 sm:px-8 lg:px-24 py-20 sm:py-28 lg:py-32 flex flex-col-reverse ${heroDirectionClass} items-center justify-between gap-12 min-h-[600px] lg:min-h-[700px]">
    <div class="max-w-xl rtl:text-right ltr:text-left text-center md:text-start"
         data-gjs-name="Hero Content">
      <h1 class="text-3xl/20 sm:text-4xl/20 lg:text-5xl/20 font-extrabold text-white leading-tight drop-shadow-lg mb-6"
          data-field="title">
        ${heroTitle}
      </h1>

      <p class="text-white/90 text-base sm:text-lg font-light mb-8"
         data-field="subtitle">
        ${heroSubtitle}
      </p>

      <div class="flex flex-row flex-wrap gap-3 justify-center md:justify-start"
           data-gjs-name="Hero Buttons">
        <a href="#"
           data-field="primary-button"
           class="bg-secondary hover:bg-primary text-white font-bold px-6 py-3 rounded-lg shadow transition text-sm sm:text-base">
          ${primaryText}
        </a>

        <a href="#"
           data-field="secondary-button"
           class="bg-white/10 text-white font-bold px-6 py-3 rounded-lg shadow transition hover:bg-white/20 text-sm sm:text-base border border-white/30">
          ${secondaryText}
        </a>
      </div>
    </div>
  </div>

  <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl z-0"></div>
</section>`.trim();

        bm.add('pg-hero', {
            id: 'pg-hero',
            label: makeLabel(iconHero, isRtl ? 'سكشن هيرو' : 'Hero Section'),
            category: {
                id: 'pg-hero-sections',
                label: isRtl ? 'سكاشن الهيرو' : 'Hero Sections',
                open: true,
            },
            content: heroContent,
        });

        bm.add('pg-features', {
            id: 'pg-features',
            label: makeLabel(iconFeatures, isRtl ? 'مميزات' : 'Features'),
            category: {
                id: 'pg-content-sections',
                label: isRtl ? 'سكاشن المحتوى' : 'Content Sections',
                open: true,
            },
            content: featuresSectionHtml,
        });

        bm.add('pg-text', {
            id: 'pg-text',
            label: makeLabel(iconText, isRtl ? 'نص' : 'Text'),
            category: {
                id: 'pg-basic-elements',
                label: isRtl ? 'عناصر أساسية' : 'Basic Elements',
                open: false,
            },
            content: `
<p class="text-slate-700" data-gjs-name="Text Block">
  ${isRtl ? 'اكتب النص هنا…' : 'Write your text here…'}
</p>`.trim(),
        });

        bm.add('pg-button', {
            id: 'pg-button',
            label: makeLabel(iconButton, isRtl ? 'زر' : 'Button'),
            category: {
                id: 'pg-basic-elements',
                label: isRtl ? 'عناصر أساسية' : 'Basic Elements',
                open: false,
            },
            content: `
<a href="#"
   data-gjs-name="Button"
   class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-sky-600 text-white font-semibold">
   ${isRtl ? 'زر' : 'Button'}
</a>`.trim(),
        });
    }

    /**
     * Register "Features" section block + component types
     */
    /**
     * Register "Features" section + feature item block
     */
    /**
     * Register "Features" section
     * - One block only: Features Section
     * - Feature items are managed من داخل السكشن نفسه (Clone)
     */
    /**
     * Register "Features" section
     * - Block واحد فقط: Features Section
     * - المميزات تُدار من داخل السكشن نفسه (Clone + زر إضافة من الـ Traits)
     */
    function registerFeaturesSection(editor) {
        const domc = editor.DomComponents;
        const bm = editor.BlockManager;
        const tm = editor.TraitManager;

        // نحدد اتجاه الواجهة (RTL / LTR) لاستخدامه في النصوص
        const isRtl =
            document.documentElement.dir === 'rtl' ||
            document.body.dir === 'rtl';

        /**
         * ------------------------------------------------------------------
         * 0) Trait مخصص لإضافة ميزة جديدة من داخل سكشن المميزات
         * ------------------------------------------------------------------
         */
        tm.addType('pg-add-feature', {
            // UI for the trait (button + helper text)
            createInput({ trait }) {
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
                        : 'You can also duplicate feature cards directly in the canvas.'
                    }
      </small>
    `;

                const btn = root.querySelector('.pg-add-feature-btn');
                // نربط الكليك يدويًا على نفس View (this)
                btn.addEventListener('click', (e) => this.onAddFeature(e));

                return root;
            },

            // لما المستخدم يضغط على الزر
            onAddFeature(event) {
                if (event?.preventDefault) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                // الكمبوننت الهدف هو سكشن المميزات نفسه
                const section = this.target || editor.getSelected();
                if (!section) return;

                // نبحث عن grid المميزات داخل السكشن
                let grid = section.find('[data-gjs-name="Features Grid"]')[0];
                if (!grid) {
                    grid = section;
                }

                const items = grid.find('[data-pg-feature="item"]');
                let newItem;

                if (items.length > 0) {
                    // نعمل Clone لآخر بطاقة موجودة
                    newItem = items[items.length - 1].clone();
                    grid.append(newItem);
                } else {
                    // لا يوجد عناصر → ننشئ واحدة جديدة من النوع pg-feature-item
                    newItem = grid.append({
                        type: 'pg-feature-item',
                    })[0];
                }

                if (!newItem) return;

                // ضبط النصوص الافتراضية
                const defaultTitle = isRtl ? 'عنوان الميزة' : 'Feature title';
                const defaultDesc = isRtl
                    ? 'وصف مختصر للميزة يوضح فائدتها للمستخدم.'
                    : 'Short description that explains the benefit.';

                newItem.addAttributes({
                    'data-pg-feature-title': defaultTitle,
                    'data-pg-feature-description': defaultDesc,
                });

                const titleEl = newItem.find('.pg-feature-title')[0];
                const descEl = newItem.find('.pg-feature-desc')[0];

                if (titleEl) {
                    titleEl.components(defaultTitle);
                }
                if (descEl) {
                    descEl.components(defaultDesc);
                }

                // اختيار العنصر الجديد في GrapesJS لتوضيح أنه أُضيف
                editor.select(newItem);
            },
        });


        /**
         * ------------------------------------------------------------------
         * 1) Section type: pg-features-section
         * ------------------------------------------------------------------
         */
        domc.addType('pg-features-section', {
            isComponent(el) {
                if (!el || !el.getAttribute) return false;
                return el.getAttribute('data-pg-section') === 'features';
            },

            model: {
                defaults: {
                    tagName: 'section',
                    attributes: {
                        'data-pg-section': 'features',
                        'data-pg-title': isRtl
                            ? 'خدمات رقمية متكاملة تدعم نجاحك'
                            : 'All-in-one digital services for your success',
                        'data-pg-subtitle': isRtl
                            ? 'منصة واحدة تجمع بين الاستضافة، القوالب الجاهزة، وربط الدومين خلال دقائق.'
                            : 'One platform that combines hosting, templates and domain connection in minutes.',
                    },
                    classes: [
                        'pg-features-section',
                        'py-20',
                        'px-4',
                        'sm:px-8',
                        'lg:px-24',
                        'bg-background',
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
                    const attrs = this.getAttributes();
                    const title = attrs['data-pg-title'] || '';
                    const header = this.find('.pg-features-title')[0];

                    if (header) {
                        header.components(
                            title ||
                            (isRtl
                                ? 'خدمات رقمية متكاملة تدعم نجاحك'
                                : 'All-in-one digital services for your success'),
                        );
                    }
                },

                updateSubtitleFromAttr() {
                    const attrs = this.getAttributes();
                    const subtitle = attrs['data-pg-subtitle'] || '';
                    const subEl = this.find('.pg-features-subtitle')[0];

                    if (subEl) {
                        subEl.components(subtitle || '');
                    }
                },
            },
        });

        /**
         * ------------------------------------------------------------------
         * 2) Feature item type: pg-feature-item
         *     (لا يوجد له Block منفصل في الـ BlockManager)
         * ------------------------------------------------------------------
         */
        domc.addType('pg-feature-item', {
            isComponent(el) {
                if (!el || !el.getAttribute) return false;
                return el.getAttribute('data-pg-feature') === 'item';
            },

            model: {
                defaults: {
                    tagName: 'div',
                    attributes: {
                        'data-pg-feature': 'item',
                        'data-pg-feature-title': isRtl ? 'عنوان الميزة' : 'Feature title',
                        'data-pg-feature-description': isRtl
                            ? 'وصف مختصر للميزة يوضح فائدتها للمستخدم.'
                            : 'Short description that explains the benefit.',
                    },
                    classes: [
                        'pg-feature-item',
                        'flex',
                        'flex-col',
                        'items-center',
                        'sm:items-start',
                        'gap-4',
                        'rounded-2xl',
                        'bg-white',
                        'p-5',
                        'shadow-sm',
                        'border',
                        'border-slate-100',
                        'hover:shadow-md',
                        'transition-all',
                        'duration-200',
                    ],
                    components: [
                        {
                            type: 'text',
                            attributes: {
                                class: 'pg-feature-icon w-12 h-12 flex items-center justify-center rounded-lg bg-primary/10 text-primary text-xl',
                            },
                            content: '★',
                        },
                        {
                            type: 'text',
                            attributes: {
                                class: 'pg-feature-title text-base font-semibold text-slate-900',
                            },
                            content: isRtl ? 'عنوان الميزة' : 'Feature title',
                        },
                        {
                            type: 'text',
                            attributes: {
                                class: 'pg-feature-desc text-sm text-slate-600 leading-relaxed',
                            },
                            content: isRtl
                                ? 'وصف مختصر للميزة يوضح فائدتها للمستخدم.'
                                : 'Short description that explains the benefit.',
                        },
                    ],
                    traits: [
                        {
                            type: 'text',
                            label: isRtl ? 'عنوان الميزة' : 'Feature title',
                            name: 'data-pg-feature-title',
                        },
                        {
                            type: 'textarea',
                            label: isRtl ? 'وصف الميزة' : 'Feature description',
                            name: 'data-pg-feature-description',
                            rows: 3,
                        },
                    ],
                },

                init() {
                    this.on('change:attributes:data-pg-feature-title', this.syncTitle);
                    this.on('change:attributes:data-pg-feature-description', this.syncDescription);
                },

                syncTitle() {
                    const attrs = this.getAttributes();
                    const title = attrs['data-pg-feature-title'] || '';
                    const titleEl = this.find('.pg-feature-title')[0];

                    if (titleEl) {
                        titleEl.components(
                            title || (isRtl ? 'عنوان الميزة' : 'Feature title'),
                        );
                    }
                },

                syncDescription() {
                    const attrs = this.getAttributes();
                    const desc = attrs['data-pg-feature-description'] || '';
                    const descEl = this.find('.pg-feature-desc')[0];

                    if (descEl) {
                        descEl.components(
                            desc ||
                            (isRtl
                                ? 'وصف مختصر للميزة يوضح فائدتها للمستخدم.'
                                : 'Short description that explains the benefit.'),
                        );
                    }
                },
            },
        });

        /**
         * ------------------------------------------------------------------
         * 3) Block: Features Section (block واحد في البلوك مانجر)
         * ------------------------------------------------------------------
         */
        bm.add('pg-features-section', {
            id: 'pg-features-section',
            label: 'Features Section',
            category: 'Sections',
            attributes: { class: 'gjs-fonts gjs-f-b1' },
            content: `
      <section class="py-24 px-4 sm:px-8 lg:px-20 bg-[#F9F6FB]" data-gjs-type="pg-features-section">
        <div class="max-w-6xl mx-auto">
          <!-- Head -->
          <div class="text-center mb-14">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-primary mb-3 tracking-tight">
              خدمات رقمية متكاملة تدعم نجاحك
            </h2>
            <p class="text-tertiary text-base sm:text-lg max-w-2xl mx-auto">
              خدمات قيمة متكاملة تساعدك على إطلاق مشروعك بثقة، واستضافة سريعة، وقوالب احترافية.
            </p>
          </div>

          <!-- Features Grid (المهم: data-pg-features-grid) -->
          <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3" data-pg-features-grid="1">
            
            <!-- Feature item 1 -->
            <article class="pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100">
              <div class="flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4">
                <span class="text-lg font-bold">★</span>
              </div>
              <h3 class="text-lg font-semibold text-slate-900 mb-2">
                إطلاق سريع
              </h3>
              <p class="text-sm text-slate-600 leading-relaxed">
                امتلك موقعك الجاهز خلال دقائق مع إعداد تلقائي كامل.
              </p>
            </article>

            <!-- Feature item 2 -->
            <article class="pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100">
              <div class="flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4">
                <span class="text-lg font-bold">★</span>
              </div>
              <h3 class="text-lg font-semibold text-slate-900 mb-2">
                تصاميم احترافية
              </h3>
              <p class="text-sm text-slate-600 leading-relaxed">
                قوالب مصممة بعناية لتناسب مختلف الأنشطة والمتاجر.
              </p>
            </article>

            <!-- Feature item 3 -->
            <article class="pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100">
              <div class="flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4">
                <span class="text-lg font-bold">★</span>
              </div>
              <h3 class="text-lg font-semibold text-slate-900 mb-2">
                دعم فني مستمر
              </h3>
              <p class="text-sm text-slate-600 leading-relaxed">
                فريق مختص لمساعدتك في أي وقت خلال رحلتك الرقمية.
              </p>
            </article>

          </div>
        </div>
      </section>
    `,
        });

    }




    // Tabs في الـ Sidebar
    initTabs();

    // تحميل CSS الرئيسي داخل الـ canvas
    const canvasStyles = [];
    const appCssLink = document.getElementById('palgoals-app-css');
    if (appCssLink && appCssLink.href) {
        canvasStyles.push(appCssLink.href);
    }

    /**
     * ---------------------------------------------------------
     * GrapesJS init
     * ---------------------------------------------------------
     */
    const editor = grapesjs.init({
        container: '#gjs',
        height: '100%',
        width: 'auto',
        fromElement: false,
        noticeOnUnload: true,
        storageManager: false,

        deviceManager: {
            devices: [
                { id: 'Desktop', name: 'Desktop', width: '' },
                { id: 'Tablet', name: 'Tablet', width: '768px', widthMedia: '992px' },
                { id: 'Mobile', name: 'Mobile', width: '375px', widthMedia: '480px' },
            ],
        },

        panels: { defaults: [] },

        blockManager: elBlocks ? { appendTo: '#gjs-blocks' } : {},
        layerManager: elLayers ? { appendTo: '#gjs-layers' } : {},
        traitManager: elTraits ? { appendTo: '#gjs-traits' } : {},
        styleManager: elStyles
            ? {
                appendTo: '#gjs-styles',
                sectors: [
                    {
                        name: 'Typography',
                        open: true,
                        buildProps: [
                            'font-family',
                            'font-size',
                            'font-weight',
                            'color',
                            'line-height',
                            'text-align',
                        ],
                    },
                    { name: 'Spacing', open: false, buildProps: ['margin', 'padding'] },
                    {
                        name: 'Size',
                        open: false,
                        buildProps: ['width', 'height', 'max-width', 'min-height'],
                    },
                    {
                        name: 'Borders',
                        open: false,
                        buildProps: ['border', 'border-radius', 'box-shadow'],
                    },
                    {
                        name: 'Background',
                        open: false,
                        buildProps: ['background-color', 'background', 'opacity'],
                    },
                ],
            }
            : {},

        selectorManager: { componentFirst: true },

        canvas: {
            styles: canvasStyles,
        },
    });


    /**
     * ---------------------------------------------------------
     * Canvas / RTL / empty state
     * ---------------------------------------------------------
     */
    editor.on('load', () => {
        // Hide empty state in the outer UI
        if (emptyState) emptyState.style.display = 'none';

        const doc = editor.Canvas.getDocument();
        if (!doc) return;

        const htmlEl = doc.documentElement;
        const bodyEl = editor.Canvas.getBody();
        const headEl = doc.head || doc.querySelector('head');

        // 1) Base direction + body styling inside the canvas iframe
        htmlEl.setAttribute('dir', appDir);

        Object.assign(bodyEl.style, {
            background: 'transparent',
            fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif',
            color: '#0f172a',
            margin: '0',
            padding: '0',
        });

        // 2) Ensure wrapper stretches full width
        const wrapper = editor.getWrapper();
        const wrapperEl = wrapper?.getEl?.();

        if (wrapper) {
            wrapper.set({ droppable: true });
        }

        if (wrapperEl) {
            Object.assign(wrapperEl.style, {
                width: '100%',
                maxWidth: '100%',
                margin: '0',
                boxSizing: 'border-box',
            });
        }

        // 3) Inject Tailwind CSS file into the iframe <head>
        if (headEl) {
            const twLink = doc.createElement('link');
            twLink.rel = 'stylesheet';
            twLink.href = '/assets/tamplate/css/app.css';
            headEl.appendChild(twLink);
        }

        // 4) Extra CSS (selection highlight, wrapper behaviour, empty hint)
        const style = doc.createElement('style');
        const emptyHintSafe = (emptyHint || '').replace(/"/g, '\\"');

        style.innerHTML = `
      [data-pg-selected] {
        outline: 2px dashed #2563eb;
        outline-offset: 4px;
        position: relative;
      }

      html[dir="rtl"] [data-pg-selected]::before,
      html[dir="ltr"] [data-pg-selected]::before {
        content: attr(data-pg-selected);
        position: absolute;
        top: -14px;
        background: #2563eb;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        pointer-events: none;
      }

      html[dir="rtl"] [data-pg-selected]::before {
        right: 0;
        left: auto;
      }

      html[dir="ltr"] [data-pg-selected]::before {
        left: 0;
        right: auto;
      }

      html,
      body {
        height: 100%;
      }

      html[dir="rtl"] body {
        margin: 0 !important;
        padding: 0 !important;
        text-align: right;
      }

      html[dir="ltr"] body {
        margin: 0 !important;
        padding: 0 !important;
        text-align: left;
      }

      .gjs-wrapper {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        min-height: 100vh;
        padding: 0;
        box-sizing: border-box;
      }

      .gjs-wrapper > :first-child {
        margin-top: 0 !important;
      }

      .gjs-wrapper:empty::before {
        content: "${emptyHintSafe}";
        display: block;
        text-align: center;
        color: #64748b;
        font-weight: 600;
        padding-top: 60px;
      }
    `;

        if (headEl) {
            headEl.appendChild(style);
        }
    });


    /**
     * ---------------------------------------------------------
     * Selection toolbar
     * ---------------------------------------------------------
     */
    editor.on('component:selected', cmp => {
        if (!cmp || cmp.get('type') !== 'pg-features-section') return;

        const traitsEl = editor.TraitManager.getTraitsViewer().el;
        if (!traitsEl) return;

        const addBtn = traitsEl.querySelector('[data-pg-feature-add]');
        if (!addBtn || addBtn.dataset.pgBound === '1') return;

        addBtn.dataset.pgBound = '1';

        addBtn.addEventListener('click', evt => {
            evt.preventDefault();

            const section = editor.getSelected();
            if (!section) return;

            // نجيب شبكة المميزات
            const gridCmp = section.find('[data-pg-features-grid="1"]')[0] || section;
            const children = gridCmp.components();

            // ننسخ آخر كرت أو ننشئ جديد لو ما في
            let sourceCard = children.length ? children.at(children.length - 1) : null;

            if (!sourceCard) {
                sourceCard = gridCmp.append({
                    tagName: 'article',
                    attributes: { class: 'pg-feature-card flex flex-col h-full rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow duration-200 px-6 py-6 border border-slate-100' },
                    components: [
                        {
                            tagName: 'div',
                            attributes: { class: 'flex items-center justify-center w-11 h-11 rounded-full bg-primary/10 text-primary mb-4' },
                            components: [{ type: 'text', content: '★' }],
                        },
                        {
                            tagName: 'h3',
                            attributes: { class: 'text-lg font-semibold text-slate-900 mb-2' },
                            components: [{ type: 'text', content: 'عنوان الميزة' }],
                        },
                        {
                            tagName: 'p',
                            attributes: { class: 'text-sm text-slate-600 leading-relaxed' },
                            components: [{ type: 'text', content: 'وصف مختصر للميزة يوضح فائدتها للمستخدم.' }],
                        },
                    ],
                });
            } else {
                const clone = sourceCard.clone();
                gridCmp.append(clone);
            }

            editor.trigger('change:canvasOffset');
        });
    });


    editor.on('component:deselected', (cmp) => {
        const el = cmp?.view?.el;
        if (el) el.removeAttribute('data-pg-selected');
    });

    // Blocks + preview
    registerBlocks(editor);
    initPreviewDropdown(editor);
    registerFeaturesSection(editor);


    /**
     * ---------------------------------------------------------
     * Load / Save / Dirty state + Autosave
     * ---------------------------------------------------------
     */
    let isDirty = false;
    let isSaving = false;

    const AUTOSAVE_DELAY = 3000;
    let autosaveTimer = null;

    const markDirty = () => {
        if (!isDirty) {
            isDirty = true;
            setStatus('Unsaved', 'dirty');
        }

        // Schedule autosave after delay
        if (autosaveTimer) {
            clearTimeout(autosaveTimer);
        }

        autosaveTimer = window.setTimeout(() => {
            if (!isSaving && isDirty) {
                // auto = true
                saveProject(true);
            }
        }, AUTOSAVE_DELAY);
    };

    async function loadProject() {
        try {
            setStatus('Loading…', 'saving');

            const data = await fetchJson(loadUrl, { method: 'GET' });
            const structure = data?.structure;

            if (
                isNonEmptyObject(structure) &&
                (structure.pages ||
                    structure.assets ||
                    structure.styles ||
                    structure.components)
            ) {
                editor.loadProjectData(structure);
            } else {
                editor.setComponents(
                    `<div class="p-10 text-slate-600">${emptyHint}</div>`,
                );
            }

            editor.getWrapper().set({ droppable: true });

            isDirty = false;
            setStatus('Loaded', 'saved');
        } catch (e) {
            console.error('[Builder] load failed:', e);
            setStatus('Load failed', 'error');
        }
    }

    /**
     * Save current project to the backend.
     *
     * - structure: full GrapesJS projectData (used later to reopen the builder)
     * - html     : compiled HTML output for frontend rendering
     * - css      : compiled CSS output for frontend rendering
     *
     * This matches PageBuilderController::saveData() validation.
     *
     * @param {boolean} isAuto - إذا كان الحفظ تلقائي (true) أو يدوي (false)
     */
    async function saveProject(isAuto = false) {
        if (isSaving) return;

        try {
            isSaving = true;
            setStatus(isAuto ? 'Auto saving…' : 'Saving…', 'saving');

            // Full project (components, styles, pages, assets...)
            const structure = editor.getProjectData();

            // Final rendered output
            const html = editor.getHtml();
            const css = editor.getCss();

            await fetchJson(saveUrl, {
                method: 'POST',
                body: {
                    structure,
                    html,
                    css,
                },
            });

            isDirty = false;
            setStatus(isAuto ? 'Auto saved' : 'Saved', 'saved');
        } catch (e) {
            console.error('[Builder] save failed:', e);
            setStatus('Save failed', 'error');
        } finally {
            isSaving = false;

            if (autosaveTimer) {
                clearTimeout(autosaveTimer);
                autosaveTimer = null;
            }
        }
    }

    function resetPage() {
        if (!window.confirm('سيتم مسح كل محتوى الصفحة الحالية، هل أنت متأكد؟')) {
            return;
        }

        editor.DomComponents.clear();
        editor.setComponents('');

        markDirty();
        setStatus('Page cleared', 'dirty');
    }

    // Dirty tracking
    editor.on('component:add', markDirty);
    editor.on('component:update', markDirty);
    editor.on('component:remove', markDirty);
    editor.on('component:styleUpdate', markDirty);

    if (saveBtn) {
        saveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            // manual save
            saveProject(false);
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', (e) => {
            e.preventDefault();
            resetPage();
        });
    }

    /**
     * ---------------------------------------------------------
     * Language dropdown (الهيدر)
     * ---------------------------------------------------------
     */
    if (langToggle && langMenu) {
        langToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            langMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (!langMenu.classList.contains('hidden')) {
                const clickedInside =
                    langMenu.contains(e.target) || langToggle.contains(e.target);
                if (!clickedInside) {
                    langMenu.classList.add('hidden');
                }
            }
        });
    }

    /**
     * ---------------------------------------------------------
     * Keyboard shortcuts (Ctrl+S / Cmd+S)
     * ---------------------------------------------------------
     */
    document.addEventListener('keydown', (e) => {
        const isMac = navigator.platform.toUpperCase().includes('MAC');
        const cmdOrCtrl = isMac ? e.metaKey : e.ctrlKey;
        if (cmdOrCtrl && e.key.toLowerCase() === 's') {
            e.preventDefault();
            // manual save
            saveProject(false);
        }
    });

    // أخيرًا: تحميل المشروع
    loadProject();
}
