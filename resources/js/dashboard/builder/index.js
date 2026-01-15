import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

import { q, qa } from './helpers/dom';
import { initSidebarTabs, initWidgetsToggle, initWidgetsSearch, simplifyBlocksPalette, bindEditorSidebarTabs } from './ui/sidebar';
import { initCanvas } from './grapes/canvas';
import { registerBlocks } from './grapes/blocks';
import { registerFeaturesSection } from './grapes/features';
import { createProjectStorage } from './storage/project';
import { resetPage } from './actions/reset';
import { registerStyleManager } from './grapes/style-manager';
import { fetchJson } from './helpers/http';



function initPageBuilder() {
    const root = document.getElementById('page-builder-root');
    const locale = root.dataset.locale || document.documentElement.lang || 'ar';
    const withLocale = (url) => {
        if (!url) return url;
        const u = new URL(url, window.location.origin);
        u.searchParams.set('locale', locale);
        return u.toString();
    };


    if (!root) return;

    // 👇 ضع كل كودك الحالي هنا
    const loadUrl = withLocale(root.dataset.loadUrl);
    const saveUrl = withLocale(root.dataset.saveUrl);
    const publishUrl = withLocale(root.dataset.publishUrl);
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

        blockManager: elBlocks ? { appendTo: '#gjs-blocks' } : {},
        layerManager: elLayers ? { appendTo: '#gjs-layers' } : {},
        traitManager: elTraits ? { appendTo: '#gjs-traits' } : {},
        styleManager: elStyles
            ? {
                appendTo: '#gjs-styles',
                // لا تضع sectors هنا (سنقوم بتسجيلها بعد init)
            }
            : {},


        selectorManager: { componentFirst: true },
        canvas: { styles: canvasStyles },
    });

    registerStyleManager(editor, { isRtl });
    bindEditorSidebarTabs(editor);

    // Canvas init (dir + inject css)
    initCanvas(editor, {
        appDir,
        emptyHint,
        cssUrl: '/assets/tamplate/css/app.css',
    });

    // Register grapes features
    registerBlocks(editor);
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
    const storage = createProjectStorage(editor, { loadUrl, saveUrl, emptyHint });

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
            await fetchJson(publishUrl, { method: 'POST' });

            console.log('[Builder] published');
        } catch (err) {
            console.error('[Builder] publish failed:', err);
        }
    });

}

initPageBuilder();



