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
const root = q('#page-builder-root');

if (root) {
    const loadUrl = root.dataset.loadUrl;
    const saveUrl = root.dataset.saveUrl;
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
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            tabContents.forEach((c) => {
                c.classList.toggle('active', c.dataset.tabContent === name);
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

        // صورة الهيرو - مسار نسبي يعمل على اللوكال والبرودكشن
        const heroImage = '/assets/tamplate/images/template.webp';

        // نصوص حسب اللغة
        const heroTitle = isRtl
            ? 'أطلق موقعك الاحترافي في دقائق'
            : 'Launch your professional website in minutes';

        const heroSubtitle = isRtl
            ? 'منصة متكاملة لتصميم واستضافة موقعك مع دومين جاهز وربط كامل خلال دقائق، بدون تعقيد تقني.'
            : 'All-in-one platform to design and host your website with a ready domain in minutes — no technical hassle.';

        const primaryText = isRtl ? 'ابدأ الآن' : 'Get Started';
        const secondaryText = isRtl ? 'استكشف المزايا' : 'Explore features';

        const heroDirectionClass = isRtl ? 'md:flex-row-reverse' : 'md:flex-row';

        // ----------------- Categories -----------------
        const heroCategory = {
            id: 'pg-hero-category',
            label: isRtl ? 'سكاشن الهيرو' : 'Hero sections',
            open: true,
        };

        const headerCategory = {
            id: 'pg-header-category',
            label: isRtl ? 'الهيدر' : 'Headers',
            open: false,
        };

        const servicesCategory = {
            id: 'pg-services-category',
            label: isRtl ? 'الخدمات' : 'Services',
            open: false,
        };

        const basicCategory = {
            id: 'pg-basic-category',
            label: isRtl ? 'عناصر أساسية' : 'Basic elements',
            open: true,
        };

        // ----------------- Hero (Main Palgoals Hero) -----------------
        const heroMainContent = `
<section data-section-type="hero"
         data-gjs-name="Hero – Main"
         class="relative bg-gradient-to-tr from-primary to-primary shadow-2xl overflow-hidden -mt-20">
  <img src="${heroImage}"
       alt="Palgoals templates preview"
       fetchpriority="high"
       class="absolute inset-0 z-0 opacity-80 w-full h-full object-cover object-center ltr:scale-x-[-1] rtl:scale-x-100 transition-transform duration-500 ease-in-out"
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

        bm.add('pg-hero-main', {
            label: 'Hero – Main',
            category: heroCategory,
            attributes: { title: 'Hero – Main' },
            content: heroMainContent,
        });

        // ----------------- Hero (Simple) -----------------
        const heroSimpleContent = `
<section data-section-type="hero"
         data-gjs-name="Hero – Simple"
         class="py-20 bg-background">
  <div class="max-w-5xl mx-auto px-4 text-center rtl:text-right ltr:text-left">
    <p class="mb-3 text-sm font-semibold tracking-[0.25em] uppercase text-secondary">
      ${isRtl ? 'منصتك لبناء المواقع' : 'YOUR WEBSITE PLATFORM'}
    </p>
    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-primary mb-4">
      ${heroTitle}
    </h1>
    <p class="text-slate-600 text-base sm:text-lg max-w-2xl mx-auto mb-8">
      ${heroSubtitle}
    </p>
    <div class="flex flex-wrap items-center justify-center gap-3">
      <a href="#"
         class="btn-primary text-sm sm:text-base">
        ${primaryText}
      </a>
      <a href="#"
         class="btn-outline text-sm sm:text-base">
        ${secondaryText}
      </a>
    </div>
  </div>
</section>`.trim();

        bm.add('pg-hero-simple', {
            label: 'Hero – Simple',
            category: heroCategory,
            attributes: { title: 'Hero – Simple' },
            content: heroSimpleContent,
        });

        // ----------------- Header -----------------
        const headerContent = `
<header data-section-type="header"
        data-gjs-name="Main Header"
        class="w-full border-b border-slate-100 bg-white/90 backdrop-blur-sm">
  <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
    <div class="flex items-center gap-2">
      <div class="w-9 h-9 rounded-xl bg-primary text-white flex items-center justify-center font-black text-xs">
        PG
      </div>
      <span class="font-extrabold text-primary text-sm sm:text-base">Palgoals</span>
    </div>

    <nav class="hidden md:flex items-center gap-4 text-sm font-medium text-slate-600 rtl:text-right ltr:text-left">
      <a href="#" class="hover:text-primary">${isRtl ? 'الرئيسية' : 'Home'}</a>
      <a href="#" class="hover:text-primary">${isRtl ? 'الخدمات' : 'Services'}</a>
      <a href="#" class="hover:text-primary">${isRtl ? 'الأسعار' : 'Pricing'}</a>
      <a href="#" class="hover:text-primary">${isRtl ? 'المدونة' : 'Blog'}</a>
    </nav>

    <div class="flex items-center gap-2">
      <a href="#"
         class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-primary border border-primary/20 hover:bg-primary/5">
         ${isRtl ? 'تسجيل الدخول' : 'Sign in'}
      </a>
      <a href="#"
         class="btn-primary px-4 py-2 text-xs sm:text-sm">
         ${isRtl ? 'أنشئ موقعك' : 'Create website'}
      </a>
    </div>
  </div>
</header>`.trim();

        bm.add('pg-header-main', {
            label: isRtl ? 'هيدر رئيسي' : 'Main header',
            category: headerCategory,
            attributes: { title: 'Header – Main' },
            content: headerContent,
        });

        // ----------------- Services -----------------
        const servicesContent = `
<section data-section-type="services"
         data-gjs-name="Services – 3 columns"
         class="py-16 bg-white">
  <div class="max-w-6xl mx-auto px-4">
    <div class="text-center mb-10 rtl:text-right ltr:text-left">
      <p class="badge mb-3">
        ${isRtl ? 'خدمات رقمية' : 'Digital Services'}
      </p>
      <h2 class="text-2xl sm:text-3xl font-extrabold text-primary mb-2">
        ${isRtl ? 'خدمات رقمية متكاملة لدعم نجاحك' : 'All-in-one digital services for your success'}
      </h2>
      <p class="text-slate-600 max-w-2xl mx-auto text-sm sm:text-base">
        ${isRtl
                ? 'اختر من مجموعة من الخدمات الجاهزة لتطوير حضورك الرقمي بسهولة وبدون تعقيد.'
                : 'Pick from a set of ready-made services to grow your online presence with no hassle.'}
      </p>
    </div>

    <div class="grid gap-5 md:grid-cols-3">
      <article class="rounded-2xl border border-slate-100 bg-slate-50/60 p-5 shadow-sm">
        <h3 class="font-bold text-primary mb-2 text-base">
          ${isRtl ? 'استضافة ودومين' : 'Hosting & Domain'}
        </h3>
        <p class="text-xs sm:text-sm text-slate-600">
          ${isRtl
                ? 'استضافة سريعة وآمنة مع تسجيل الدومين وربط كامل للموقع خلال دقائق.'
                : 'Fast, secure hosting with complete domain setup in minutes.'}
        </p>
      </article>

      <article class="rounded-2xl border border-slate-100 bg-slate-50/60 p-5 shadow-sm">
        <h3 class="font-bold text-primary mb-2 text-base">
          ${isRtl ? 'قوالب جاهزة' : 'Ready templates'}
        </h3>
        <p class="text-xs sm:text-sm text-slate-600">
          ${isRtl
                ? 'قوالب احترافية جاهزة للتخصيص تناسب مختلف أنواع الأعمال.'
                : 'Professional templates tailored for different business types.'}
        </p>
      </article>

      <article class="rounded-2xl border border-slate-100 bg-slate-50/60 p-5 shadow-sm">
        <h3 class="font-bold text-primary mb-2 text-base">
          ${isRtl ? 'دعم فني' : 'Technical support'}
        </h3>
        <p class="text-xs sm:text-sm text-slate-600">
          ${isRtl
                ? 'دعم فني لمساعدتك في تشغيل وتطوير موقعك دون حاجة لخبرة تقنية.'
                : 'Support team to help you run and evolve your website with no technical skills.'}
        </p>
      </article>
    </div>
  </div>
</section>`.trim();

        bm.add('pg-services-3cols', {
            label: isRtl ? 'الخدمات – 3 أعمدة' : 'Services – 3 columns',
            category: servicesCategory,
            attributes: { title: 'Services – 3 columns' },
            content: servicesContent,
        });

        // ----------------- Basic Elements -----------------
        bm.add('pg-text', {
            label: 'Text',
            category: basicCategory,
            attributes: { title: 'Text block' },
            content: `<p class="text-slate-700" data-gjs-name="Text">اكتب النص هنا…</p>`,
        });

        bm.add('pg-button', {
            label: 'Button',
            category: basicCategory,
            attributes: { title: 'Button' },
            content: `
<a href="#"
   data-gjs-name="Button"
   class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-sky-600 text-white font-semibold hover:bg-sky-700 transition">
   ${isRtl ? 'زر' : 'Button'}
</a>`.trim(),
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
        if (emptyState) emptyState.style.display = 'none';

        const doc = editor.Canvas.getDocument();
        const iframeHtml = doc.documentElement;
        const iframeBody = editor.Canvas.getBody();

        iframeHtml.setAttribute('dir', appDir);

        iframeBody.style.background = 'transparent';
        iframeBody.style.fontFamily =
            'system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif';
        iframeBody.style.color = '#0f172a';
        iframeBody.style.margin = '0';
        iframeBody.style.padding = '0';

        const wrapper = editor.getWrapper();
        const wrapperEl = wrapper.getEl();

        wrapper.set({ droppable: true });
        if (wrapperEl) {
            wrapperEl.style.width = '100%';
            wrapperEl.style.maxWidth = '100%';
            wrapperEl.style.margin = '0';
            wrapperEl.style.boxSizing = 'border-box';
        }

        const style = doc.createElement('style');
        style.innerHTML = `
      [data-pg-selected]{
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

      html, body {
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
        content: "${emptyHint}";
        display: block;
        text-align: center;
        color: #64748b;
        font-weight: 600;
        padding-top: 60px;
      }
    `;
        doc.head.appendChild(style);
    });

    /**
     * ---------------------------------------------------------
     * Selection toolbar
     * ---------------------------------------------------------
     */
    editor.on('component:selected', (cmp) => {
        const el = cmp?.view?.el;
        if (!el) return;

        const tag = (cmp.get('tagName') || '').toLowerCase();
        const type = cmp.get('type') || '';

        if (tag === 'body' || type === 'wrapper') return;

        cmp.set({
            toolbar: [
                { attributes: { title: 'Move' }, command: 'tlb-move' },
                { attributes: { title: 'Copy' }, command: 'tlb-clone' },
                {
                    attributes: { title: 'Delete', class: 'text-red-600' },
                    command: 'tlb-delete',
                },
            ],
        });
    });

    editor.on('component:deselected', (cmp) => {
        const el = cmp?.view?.el;
        if (el) el.removeAttribute('data-pg-selected');
    });

    // Blocks + preview
    registerBlocks(editor);
    initPreviewDropdown(editor);

    /**
     * ---------------------------------------------------------
     * Load / Save / Dirty state
     * ---------------------------------------------------------
     */
    let isDirty = false;
    let isSaving = false;

    const markDirty = () => {
        if (!isDirty) {
            isDirty = true;
            setStatus('Unsaved', 'dirty');
        }
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

    async function saveProject() {
        if (isSaving) return;

        try {
            isSaving = true;
            setStatus('Saving…', 'saving');

            const structure = editor.getProjectData();

            await fetchJson(saveUrl, {
                method: 'POST',
                body: { structure },
            });

            isDirty = false;
            setStatus('Saved', 'saved');
        } catch (e) {
            console.error('[Builder] save failed:', e);
            setStatus('Save failed', 'error');
        } finally {
            isSaving = false;
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
            saveProject();
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
            saveProject();
        }
    });

    // أخيرًا: تحميل المشروع
    loadProject();
}
