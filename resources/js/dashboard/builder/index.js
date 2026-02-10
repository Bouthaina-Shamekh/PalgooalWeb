let TinyPromise;
const loadTiny = () => {
    if (!TinyPromise) {
        TinyPromise = import('tinymce/tinymce').then(async (m) => {
            await import('tinymce/icons/default');
            await import('tinymce/themes/silver');
            await import('tinymce/models/dom');
            await import('tinymce/plugins/link');
            await import('tinymce/plugins/lists');
            await import('tinymce/plugins/code');
            return m.default;
        });
    }
    return TinyPromise;
};

import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';
import './builder'

import { q, qa } from './helpers/dom';
import { initSidebarTabs, initWidgetsToggle, initWidgetsSearch, simplifyBlocksPalette, bindEditorSidebarTabs, initBoxSpacingInputs } from './ui/sidebar';
import { initCanvas } from './grapes/canvas';
import { initPreviewDropdown } from './actions/devices';
import { registerBlocks, registerBlocksInBox } from './grapes/blocks';
import { registerFeaturesSection } from './grapes/features';
import { createProjectStorage } from './storage/project';
import { resetPage } from './actions/reset';
import { registerStyleManager } from './grapes/style-manager';
import { fetchJson } from './helpers/http';



function initPageBuilder() {
    const root = document.getElementById('page-builder-root');
    if (!root) return;

    const locale = (root.dataset.locale || document.documentElement.lang || 'ar').trim().toLowerCase();

    const loadUrl = root.dataset.loadUrl;
    const saveUrl = root.dataset.saveUrl;
    const publishUrl = root.dataset.publishUrl;
    const publishBtn = q('#builder-publish');

    const appDir = document.documentElement.getAttribute('dir') || 'ltr';
    const isRtl = appDir === 'rtl';
    const emptyHint = isRtl ? 'ابدأ بسحب بلوك من اليمين…' : 'Start by dragging a block from the right…';

    // DOM
    const emptyState = q('#builder-empty-state');
    const saveBtn = q('#pg-save-btn');
    const resetBtn = q('#builder-reset');

    const elBlocks = q('#gjs-blocks');
    const elLayers = q('#gjs-layers');
    const elTraits = q('#gjs-traits');
    const elStyles = q('#gjs-styles');

    // Canvas styles from blade link
    const canvasStyles = [];
    const appCssLink = document.getElementById('palgoals-app-css');
    if (appCssLink?.href) canvasStyles.push(appCssLink.href);

    // Init UI
    initSidebarTabs();


    // Grapes init
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

        // blockManager: elBlocks ? { appendTo: '#gjs-blocks', custom: true } : { custom: true },
        blockManager: { custom: true },
        layerManager: elLayers ? { appendTo: '#gjs-layers' } : {},
        traitManager: elTraits ? { appendTo: '#gjs-traits' } : {},
        styleManager: elStyles
            ? {
                appendTo: '#gjs-styles',
                // لا تضع sectors هنا (سنقوم بتسجيلها بعد init)
            }
            : {},


        selectorManager: { componentFirst: true },
        canvas: {
            styles: [
                canvasStyles,
                'https://unpkg.com/swiper/swiper-bundle.min.css'
            ],
            scripts: [
                'https://unpkg.com/swiper/swiper-bundle.min.js'
            ]
        },
    });
    editor.setCustomRte({
        enable(el, rteInst = {}) {
            el.contentEditable = true;

            // لازم tinymce يكون موجود (أنت محمّله من blade)
            if (!window.tinymce) return rteInst;

            const id = el.id || `pg-rte-${Date.now()}`;
            el.id = id;

            // remove old editor if exists
            const old = window.tinymce.get(id);
            if (old) old.remove();

            const isRtl = document.documentElement.getAttribute('dir') === 'rtl';
            const docLang = (document.documentElement.lang || 'en').toLowerCase();
            const lang = docLang.startsWith('ar') ? 'ar' : 'en';

            window.tinymce.init({
                target: el,
                inline: true,
                menubar: false,
                branding: false,
                license_key: 'gpl',

                plugins: 'link lists code',
                toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | code',

                directionality: isRtl ? 'rtl' : 'ltr',

                // ✅ لا تضع language إلا إذا كان عندك ملف اللغة فعلياً
                ...(lang === 'ar' ? {} : {}),

                setup: (ed) => {
                    rteInst.tiny = ed;

                    ed.on('init', () => {
                        ed.formatter.apply(isRtl ? 'alignright' : 'alignleft');
                    });

                    ed.on('change keyup blur', () => {
                        // هذه أفضل من trigger change:changesCount لأنها بتسجّل تعديل حقيقي
                        editor.trigger('change');
                    });
                },
            });

            return rteInst;
        },

        disable(el, rteInst = {}) {
            // ✅ هذا يمنع crash
            el.contentEditable = false;

            try {
                const inst = rteInst?.tiny || (el.id ? window.tinymce?.get(el.id) : null);
                if (inst) inst.remove();
            } catch (e) {
                // ignore
            }

            rteInst.tiny = null;
            return rteInst;
        },
    });





    registerStyleManager(editor, { isRtl });
    bindEditorSidebarTabs(editor);
    initBoxSpacingInputs();

    // Canvas init (dir + inject css)
    initCanvas(editor, {
        appDir,
        emptyHint,
        cssUrl: '/assets/tamplate/css/app.css',
    });

    const btn = document.getElementById('btnSidebar');

    btn.addEventListener('click', () => {
        corePreview(editor)
    });

    function corePreview(editor) {
        const isActive = editor.Commands.isActive('core:preview');
        if (isActive) {
            editor.stopCommand('core:preview');
            toggleCanvasInteraction(true);
        } else {
            editor.runCommand('core:preview');
            toggleCanvasInteraction(false);
        }
    }
    function toggleCanvasInteraction(enable) {
        const frame = editor.Canvas.getFrameEl();
        if (!frame) return;

        const doc = frame.contentDocument;
        if (!doc) return;

        doc.body.style.pointerEvents = enable ? 'auto' : 'none';
    }


    editor.on('load', () => {
        // استرجاع الحالة
        let saved_collapsed = localStorage.getItem('pg_sidebar_collapsed');
        if (saved_collapsed == '1') {
            setTimeout(() => {
                corePreview(editor);
            }, 2000);
        };
    });


    // منع الخروج من preview عند الضغط داخل canvas
    editor.on('component:selected', component => {
        if (editor.Commands.isActive('core:preview')) {
            editor.select(null);
        }
    });

    // Register grapes features
    registerBlocks(editor);
    registerBlocksInBox(editor);
    initPreviewDropdown(editor);
    registerFeaturesSection(editor);

    // Sidebar widgets
    editor.on('load', () => {
        if (emptyState) emptyState.style.display = 'none';

        initWidgetsToggle();

        const refreshWidgets = () => {
            simplifyBlocksPalette();
            // لا تعيد bind للـ input كل مرة، فقط نفّذ الفلترة الحالية
            const input = q('#pg-widgets-search');
            if (input) input.dispatchEvent(new Event('input'));
        };
        editor.addStyle(`
            /* Desktop: >= 993px */
            @media (min-width: 993px){
                [data-pg-hide-desktop="1"]{ display:none !important; }
            }

            /* Tablet: 481px - 992px */
            @media (min-width: 481px) and (max-width: 992px){
                [data-pg-hide-tablet="1"]{ display:none !important; }
            }

            /* Mobile: <= 480px */
            @media (max-width: 480px){
                [data-pg-hide-mobile="1"]{ display:none !important; }
            }
            `);


        initWidgetsSearch();   // bind مرة واحدة
        refreshWidgets();      // تجهيز أول مرة

        editor.on('block:add', () => refreshWidgets());
    });


    // Storage (load/save/autosave)
    const storage = createProjectStorage(editor, { loadUrl, saveUrl, emptyHint, locale });

    // Dirty events
    editor.on('component:add', storage.markDirty);
    editor.on('component:update', storage.markDirty);
    editor.on('component:remove', storage.markDirty);
    editor.on('component:styleUpdate', storage.markDirty);

    // Buttons
    saveBtn?.addEventListener('click', (e) => { e.preventDefault(); storage.saveProject(false); });
    resetBtn?.addEventListener('click', (e) => { e.preventDefault(); resetPage(editor, storage.markDirty); });

    // Ctrl/Cmd+S
    document.addEventListener('keydown', (e) => {
        const isMac = navigator.platform.toUpperCase().includes('MAC');
        const cmdOrCtrl = isMac ? e.metaKey : e.ctrlKey;
        if (cmdOrCtrl && e.key.toLowerCase() === 's') {
            e.preventDefault();
            storage.saveProject(false);
        }
    });

    // Start
    storage.loadProject();

    publishBtn?.addEventListener('click', async (e) => {
        e.preventDefault();

        try {
            // 1) احفظ آخر تغييرات
            await storage.saveProject(false);

            // 2) انشر (خلّي السيرفر ينقل المسودة إلى published)
            await fetchJson(publishUrl, { method: 'POST', body: { locale } });

            console.log('[Builder] published');
        } catch (err) {
            console.error('[Builder] publish failed:', err);
        }
    });

}

initPageBuilder();



