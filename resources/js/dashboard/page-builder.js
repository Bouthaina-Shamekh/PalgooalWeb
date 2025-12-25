// resources/js/dashboard/page-builder.js
import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

/**
 * Helpers
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
        dotEl.classList.remove('bg-amber-400', 'bg-emerald-500', 'bg-red-500', 'bg-sky-500', 'animate-pulse');

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
 * Bootstrap
 */
const root = q('#page-builder-root');

if (root) {
    const loadUrl = root.dataset.loadUrl;
    const saveUrl = root.dataset.saveUrl;

    // نقرأ اتجاه الموقع من الـ <html> في لوحة التحكم
    const appDir = document.documentElement.getAttribute('dir') || 'ltr';
    const isRtl = appDir === 'rtl';
    const emptyHint = isRtl
        ? 'ابدأ بسحب بلوك من اليمين…'
        : 'Start by dragging a block from the right…';

    // UI
    const emptyState = q('#builder-empty-state');
    const saveBtn = q('#builder-save');

    const previewToggleBtn = q('#preview-toggle-btn');
    const previewMenu = q('#preview-menu');
    const previewLabel = q('[data-preview-label]');
    const previewBtns = qa('.builder-preview-btn');

    // Sidebar containers
    const elBlocks = q('#gjs-blocks');
    const elLayers = q('#gjs-layers');
    const elTraits = q('#gjs-traits');
    const elStyles = q('#gjs-styles');

    // Tabs
    function initTabs() {
        const tabBtns = qa('.builder-tab[data-tab-target]');
        const tabContents = qa('.builder-tab-content[data-tab-content]');
        const helpers = qa('[data-tab-helper]');

        const setActive = (name) => {
            tabBtns.forEach(btn => {
                const active = btn.dataset.tabTarget === name;
                btn.classList.toggle('active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            tabContents.forEach(c => c.classList.toggle('active', c.dataset.tabContent === name));
            helpers.forEach(h => h.classList.toggle('hidden', h.dataset.tabHelper !== name));
        };

        tabBtns.forEach(btn => btn.addEventListener('click', () => setActive(btn.dataset.tabTarget)));
        setActive('palette');
    }

    function initPreviewDropdown(editor) {
        if (!previewToggleBtn || !previewMenu) return;

        const close = () => previewMenu.classList.remove('open');
        const toggle = () => previewMenu.classList.toggle('open');

        previewToggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            toggle();
        });

        document.addEventListener('click', (e) => {
            const inside = previewMenu.contains(e.target) || previewToggleBtn.contains(e.target);
            if (!inside) close();
        });

        const deviceMap = {
            desktop: { name: 'Desktop' },
            tablet: { name: 'Tablet' },
            mobile: { name: 'Mobile' },
        };

        function setDevice(key) {
            const d = deviceMap[key] || deviceMap.desktop;
            editor.setDevice(d.name);
            if (previewLabel) previewLabel.textContent = d.name;
            previewBtns.forEach(b => b.classList.toggle('active', b.dataset.preview === key));
        }

        previewBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                setDevice(btn.dataset.preview || 'desktop');
                close();
            });
        });

        setDevice('desktop');
    }

    function registerBlocks(editor) {
        const bm = editor.BlockManager;
        bm.add('pg-hero', {
            label: 'Hero',
            category: 'Palgoals',
            content: `<main>
            <section  data-section-type="hero" class="relative bg-gradient-to-tr from-primary to-primary shadow-2xl overflow-hidden -mt-20">
             <img src="http://127.0.0.1:8000/assets/tamplate/images/template.webp" alt="" fetchpriority="high"
             class="absolute inset-0 z-0 opacity-80 w-full h-full object-cover object-center ltr:scale-x-[-1] rtl:scale-x-100 transition-transform duration-500 ease-in-out"
             aria-hidden="true"
             decoding="async"
             loading="eager"
    />

    <div class="relative z-10 px-4 sm:px-8 lg:px-24 py-20 sm:py-28 lg:py-32 flex flex-col-reverse md:flex-row items-center justify-between gap-12 min-h-[600px] lg:min-h-[700px]">
      <div class="max-w-xl rtl:text-right ltr:text-left text-center md:text-start">
        <h1 class="text-3xl/20 sm:text-4xl/20 lg:text-5xl/20 font-extrabold text-white leading-tight drop-shadow-lg mb-6">
          {{ $data['title'] ?? 'عنوان غير متوفر' }}
        </h1>

        <p class="text-white/90 text-base sm:text-lg font-light mb-8">
          {{ $data['subtitle'] ?? '' }}
        </p>

        <div class="flex flex-row flex-wrap gap-3 justify-center md:justify-start">
          {{-- Primary button --}}
          <a href="{{ $primaryUrl }}"
             aria-label="{{ $primaryText }}"
             class="bg-secondary hover:bg-primary text-white font-bold px-6 py-3 rounded-lg shadow transition text-sm sm:text-base">
            {{ $primaryText }}
          </a>

          {{-- Secondary button (optional) --}}
          <a href="{{ $secondaryUrl }}"
             class="bg-white/10 text-white font-bold px-6 py-3 rounded-lg shadow transition hover:bg-white/20 text-sm sm:text-base border border-white/30">
            {{ $secondaryText }}
          </a>
        </div>
      </div>
    </div>

    <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl z-0"></div>
  </section>
</main>`,
        });

        bm.add('pg-text', {
            label: 'Text',
            category: 'Basic',
            content: `<p class="text-slate-700">اكتب النص هنا…</p>`,
        });

        bm.add('pg-button', {
            label: 'Button',
            category: 'Basic',
            content: `<a href="#" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-sky-600 text-white font-semibold">زر</a>`,
        });
    }

    // Start
    initTabs();
    // نقرأ رابط CSS من <link id="palgoals-app-css">
    const canvasStyles = [];
    const appCssLink = document.getElementById('palgoals-app-css');
    if (appCssLink && appCssLink.href) {
        canvasStyles.push(appCssLink.href);
    }

    const editor = grapesjs.init({
        container: '#gjs',
        fromElement: true,
        height: '100%',
        width: 'auto',
        fromElement: false,
        noticeOnUnload: true,

        // نحن نتولى الحفظ/التحميل
        storageManager: false,

        deviceManager: {
            devices: [
                { id: 'Desktop', name: 'Desktop', width: '' },
                { id: 'Tablet', name: 'Tablet', width: '768px', widthMedia: '992px' },
                { id: 'Mobile', name: 'Mobile', width: '375px', widthMedia: '480px' },
            ],
        },

        // نستخدم البانلز الخاصة بنا
        panels: { defaults: [] },

        blockManager: elBlocks ? { appendTo: '#gjs-blocks' } : {},
        layerManager: elLayers ? { appendTo: '#gjs-layers' } : {},
        traitManager: elTraits ? { appendTo: '#gjs-traits' } : {},
        styleManager: elStyles
            ? {
                appendTo: '#gjs-styles',
                sectors: [
                    { name: 'Typography', open: true, buildProps: ['font-family', 'font-size', 'font-weight', 'color', 'line-height', 'text-align'] },
                    { name: 'Spacing', open: false, buildProps: ['margin', 'padding'] },
                    { name: 'Size', open: false, buildProps: ['width', 'height', 'max-width', 'min-height'] },
                    { name: 'Borders', open: false, buildProps: ['border', 'border-radius', 'box-shadow'] },
                    { name: 'Background', open: false, buildProps: ['background-color', 'background', 'opacity'] },
                ],
            }
            : {},

        selectorManager: { componentFirst: true },

        canvas: {
            styles: canvasStyles,
        },
    });

    // دعم RTL / LTR داخل الـ iframe
    editor.on('load', () => {
        if (emptyState) emptyState.style.display = 'none';

        const doc = editor.Canvas.getDocument();
        const iframeHtml = doc.documentElement;
        const iframeBody = editor.Canvas.getBody();

        // نمرّر الاتجاه للـ iframe
        iframeHtml.setAttribute('dir', appDir);

        // ستايل أساسي
        iframeBody.style.background = 'transparent';
        iframeBody.style.fontFamily = 'system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif';
        iframeBody.style.color = '#0f172a';
        iframeBody.style.margin = '0';
        iframeBody.style.padding = '0';

        // الـ wrapper root
        const wrapper = editor.getWrapper();
        const wrapperEl = wrapper.getEl();

        wrapper.set({ droppable: true });
        if (wrapperEl) {
            wrapperEl.style.width = '100%';
            wrapperEl.style.maxWidth = '100%';
            wrapperEl.style.margin = '0';
            wrapperEl.style.boxSizing = 'border-box';
        }

        // CSS داخل الـ iframe مع دعم RTL/LTR
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

    // Selection label (من دون تغيير)
    editor.on('component:selected', (cmp) => {
        const el = cmp?.view?.el;
        if (!el) return;
        const name = cmp.getAttributes()?.['data-gjs-name'] || cmp.get('type') || 'element';
        el.setAttribute('data-pg-selected', name);
    });

    editor.on('component:deselected', (cmp) => {
        const el = cmp?.view?.el;
        if (el) el.removeAttribute('data-pg-selected');
    });

    // Blocks + preview
    registerBlocks(editor);
    initPreviewDropdown(editor);

    /**
     * Load / Save
     */
    let isDirty = false;
    let isSaving = false;

    async function loadProject() {
        try {
            setStatus('Loading…', 'saving');

            const data = await fetchJson(loadUrl, { method: 'GET' });
            const structure = data?.structure;

            if (isNonEmptyObject(structure) && (structure.pages || structure.assets || structure.styles || structure.components)) {
                editor.loadProjectData(structure);
            } else {
                // رسالة افتراضية حسب RTL / LTR
                editor.setComponents(`<div class="p-10 text-slate-600">${emptyHint}</div>`);
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

    // Dirty tracking
    const markDirty = () => {
        if (!isDirty) {
            isDirty = true;
            setStatus('Unsaved', 'dirty');
        }
    };

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

    document.addEventListener('keydown', (e) => {
        const isMac = navigator.platform.toUpperCase().includes('MAC');
        const cmdOrCtrl = isMac ? e.metaKey : e.ctrlKey;
        if (cmdOrCtrl && e.key.toLowerCase() === 's') {
            e.preventDefault();
            saveProject();
        }
    });

    // Load now
    loadProject();
}
