import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

import { q, qa } from './helpers/dom';
import { initSidebarTabs, initWidgetsToggle, initWidgetsSearch, simplifyBlocksPalette, bindEditorSidebarTabs } from './ui/sidebar';
import { initCanvas } from './grapes/canvas';
import { registerBlocks } from './grapes/blocks';
import { registerFeaturesSection } from './grapes/features';
import { createProjectStorage } from './storage/project';
import { resetPage } from './actions/reset';

function initPageBuilder() {
    const root = document.getElementById('page-builder-root');
    if (!root) return;

    // 👇 ضع كل كودك الحالي هنا
    const loadUrl = root.dataset.loadUrl;
    const saveUrl = root.dataset.saveUrl;

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
        styleManager: elStyles ? { appendTo: '#gjs-styles', sectors: [] } : {},

        selectorManager: { componentFirst: true },
        canvas: { styles: canvasStyles },
    });

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
}

initPageBuilder();



