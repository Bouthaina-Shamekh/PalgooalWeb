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

import 'tinymce/skins/ui/oxide/skin.min.css';
import 'tinymce/skins/ui/oxide/content.inline.min.css';
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
    const elLayersSidebarSlot = q('#gjs-layers-sidebar-slot');
    const elLayersModalSlot = q('#gjs-layers-modal-slot');
    const elTraits = q('#gjs-traits');
    const elStyles = q('#gjs-styles');
    const layersToggleBtn = q('#builder-layers-toggle');
    const layersWindow = q('#builder-layers-window');
    const layersPanel = q('#builder-layers-panel');
    const layersDragHandle = q('#builder-layers-drag-handle');
    const layersCloseBtn = q('#builder-layers-close');
    let isLayersWindowOpen = false;
    const layersDragState = {
        active: false,
        offsetX: 0,
        offsetY: 0,
    };

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
        layerManager: elLayers
            ? {
                appendTo: '#gjs-layers',
                showWrapper: false,
                scrollCanvas: { behavior: 'auto', block: 'nearest' },
                scrollLayers: { behavior: 'auto', block: 'nearest' },
            }
            : {},
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
                ...canvasStyles,
                'https://unpkg.com/swiper/swiper-bundle.min.css'
            ],
            scripts: [
                'https://unpkg.com/swiper/swiper-bundle.min.js'
            ]
        },
    });

    const moveLayersHost = (slot) => {
        if (!elLayers || !slot || elLayers.parentElement === slot) return;
        slot.appendChild(elLayers);
    };

    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const setLayersPanelPosition = (left, top) => {
        if (!layersPanel) return;
        const width = layersPanel.offsetWidth || 0;
        const height = layersPanel.offsetHeight || 0;
        const minLeft = 12;
        const minTop = 72;
        const maxLeft = Math.max(minLeft, window.innerWidth - width - 12);
        const maxTop = Math.max(minTop, window.innerHeight - height - 12);

        layersPanel.style.left = `${clamp(left, minLeft, maxLeft)}px`;
        layersPanel.style.top = `${clamp(top, minTop, maxTop)}px`;
        layersPanel.style.right = 'auto';
    };

    const stopLayersDrag = () => {
        layersDragState.active = false;
        if (layersPanel) layersPanel.dataset.dragging = 'false';
        document.removeEventListener('mousemove', handleLayersWindowDrag);
        document.removeEventListener('mouseup', stopLayersDrag);
    };

    function handleLayersWindowDrag(event) {
        if (!layersDragState.active || !layersPanel) return;
        setLayersPanelPosition(
            event.clientX - layersDragState.offsetX,
            event.clientY - layersDragState.offsetY,
        );
    }

    const startLayersDrag = (event) => {
        if (!layersPanel || event.button !== 0) return;
        if (event.target.closest('button, a, input, textarea, select, label')) return;

        const rect = layersPanel.getBoundingClientRect();
        setLayersPanelPosition(rect.left, rect.top);

        layersDragState.active = true;
        layersDragState.offsetX = event.clientX - rect.left;
        layersDragState.offsetY = event.clientY - rect.top;
        layersPanel.dataset.dragging = 'true';

        document.addEventListener('mousemove', handleLayersWindowDrag);
        document.addEventListener('mouseup', stopLayersDrag);
    };

    const clampLayersWindowToViewport = () => {
        if (!layersPanel) return;
        const rect = layersPanel.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        setLayersPanelPosition(rect.left, rect.top);
    };

    const setLayersWindowState = (open) => {
        isLayersWindowOpen = !!open;

        if (layersWindow) {
            layersWindow.hidden = !open;
            layersWindow.dataset.open = open ? 'true' : 'false';
            layersWindow.setAttribute('aria-hidden', open ? 'false' : 'true');
        }

        if (layersToggleBtn) {
            layersToggleBtn.dataset.active = open ? 'true' : 'false';
            layersToggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        document.body.classList.toggle('pg-layers-window-open', open);
    };

    // Keep Grapes selection tools/badges in sync with any active scroll container.
    const canvasScrollHost = q('#builder-canvas-scroll');
    let rafId = 0;
    const removeSyncListeners = [];
    const syncCanvasTools = () => {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
            try {
                editor.Canvas?.refresh?.({ tools: true });
            } catch (_) {
                // noop
            }
            editor.refresh({ tools: true });
        });
    };

    const bindScrollSync = (target, eventName = 'scroll', capture = false) => {
        if (!target?.addEventListener) return;
        target.addEventListener(eventName, syncCanvasTools, capture);
        removeSyncListeners.push(() => target.removeEventListener(eventName, syncCanvasTools, capture));
    };

    const bindFrameScrollSync = () => {
        const frameEl = editor.Canvas.getFrameEl();
        if (!frameEl) return;
        bindScrollSync(frameEl.contentWindow, 'scroll', false);
        bindScrollSync(frameEl.contentWindow, 'resize', false);
        bindScrollSync(frameEl.contentDocument, 'scroll', true);
        syncCanvasTools();
    };

    bindScrollSync(window, 'resize', false);
    bindScrollSync(canvasScrollHost, 'scroll', false);

    editor.on('load', bindFrameScrollSync);
    editor.on('canvas:frame:load', bindFrameScrollSync);
    editor.on('component:selected', syncCanvasTools);

    editor.on('destroy', () => {
        removeSyncListeners.forEach((unbind) => unbind());
        if (rafId) cancelAnimationFrame(rafId);
        stopLayersDrag();
        window.removeEventListener('resize', clampLayersWindowToViewport);
        document.removeEventListener('keydown', handleLayersWindowKeydown);
        document.body.classList.remove('pg-layers-window-open');
        editor.off('load', bindFrameScrollSync);
        editor.off('canvas:frame:load', bindFrameScrollSync);
        editor.off('component:selected', syncCanvasTools);
    });

    const hasClass = (component, className) => {
        const classAttr = String(component?.getAttributes?.()?.class || '');
        return classAttr.split(/\s+/).includes(className);
    };

    const getContainerInner = (containerComponent) => {
        const children = containerComponent?.components?.();
        if (!children) return null;
        let inner = null;
        children.each((child) => {
            if (!inner && hasClass(child, 'pg-container-inner')) inner = child;
        });
        return inner;
    };

    const getClosestContainer = (component) => {
        let current = component;
        while (current) {
            if (current.get?.('type') === 'pg-container') return current;
            current = current.parent?.();
        }
        return null;
    };

    const syncLayersRootToSelection = (component = null) => {
        const layers = editor?.Layers;
        const wrapper = editor?.getWrapper?.();
        if (!layers?.setRoot || !wrapper) return;

        const selectedContainer =
            component?.get?.('type') === 'pg-container'
                ? component
                : getClosestContainer(component);

        const nextRoot = isLayersWindowOpen
            ? wrapper
            : selectedContainer
                ? getContainerInner(selectedContainer) || selectedContainer
                : wrapper;

        const currentRoot = layers.getRoot?.();
        if (currentRoot !== nextRoot) {
            layers.setRoot(nextRoot);
        }
    };

    const openLayersWindow = () => {
        if (!layersWindow || !elLayersModalSlot) return;
        moveLayersHost(elLayersModalSlot);
        setLayersWindowState(true);
        clampLayersWindowToViewport();
        syncLayersRootToSelection(editor.getSelected?.() || null);
    };

    const closeLayersWindow = () => {
        if (!elLayersSidebarSlot) return;
        stopLayersDrag();
        moveLayersHost(elLayersSidebarSlot);
        setLayersWindowState(false);
        syncLayersRootToSelection(editor.getSelected?.() || null);
    };

    const handleLayersWindowKeydown = (event) => {
        if (event.key === 'Escape' && isLayersWindowOpen) {
            closeLayersWindow();
        }
    };

    if (layersToggleBtn && layersWindow && elLayersSidebarSlot && elLayersModalSlot) {
        layersToggleBtn.addEventListener('click', () => {
            if (isLayersWindowOpen) {
                closeLayersWindow();
            } else {
                openLayersWindow();
            }
        });

        layersCloseBtn?.addEventListener('click', closeLayersWindow);
        layersDragHandle?.addEventListener('mousedown', startLayersDrag);
        document.addEventListener('keydown', handleLayersWindowKeydown);
        window.addEventListener('resize', clampLayersWindowToViewport);
        setLayersWindowState(false);
    }

    editor.on('component:selected', (component) => syncLayersRootToSelection(component));
    editor.on('component:deselected', () => syncLayersRootToSelection(null));
    editor.on('load', () => syncLayersRootToSelection(editor.getSelected?.() || null));

    editor.setCustomRte({
        enable(el, rteInst = {}) {
            el.contentEditable = true;

            const id = el.id || `pg-rte-${Date.now()}`;
            el.id = id;

            rteInst.__bootPromise = loadTiny()
                .then((tiny) => {
                    if (!el.isConnected) return;

                    const old = tiny.get(id);
                    if (old) old.remove();

                    const isRtl = document.documentElement.getAttribute('dir') === 'rtl';

                    tiny.init({
                        target: el,
                        inline: true,
                        menubar: false,
                        branding: false,
                        skin: false,
                        content_css: false,
                        license_key: 'gpl',
                        plugins: 'link lists code',
                        toolbar:
                            'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | code',
                        directionality: isRtl ? 'rtl' : 'ltr',
                        setup: (ed) => {
                            rteInst.tiny = ed;

                            ed.on('init', () => {
                                ed.formatter.apply(isRtl ? 'alignright' : 'alignleft');
                            });

                            ed.on('change keyup blur', () => {
                                editor.trigger('change');
                            });
                        },
                    });
                })
                .catch((err) => {
                    console.error('[Builder] TinyMCE init failed:', err);
                });

            return rteInst;
        },

        disable(el, rteInst = {}) {
            el.contentEditable = false;

            try {
                const inst = rteInst?.tiny;
                if (inst) inst.remove();
            } catch (e) {
                // noop
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
        cssUrl: appCssLink?.href || null,
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




