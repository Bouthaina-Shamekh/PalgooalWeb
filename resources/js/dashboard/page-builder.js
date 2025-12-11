import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

/**
 * Helpers to keep GrapesJS project structure always valid.
 */

const isPlainObject = (val) =>
    Object.prototype.toString.call(val) === '[object Object]';

/**
 * Ensure the root wrapper component is valid.
 */
function ensureWrapperComponent(component) {
    if (!isPlainObject(component)) {
        component = {};
    }

    const type = component.type || 'wrapper';
    let components = component.components;

    if (!Array.isArray(components)) {
        components = [];
    }

    const attrs = isPlainObject(component.attributes)
        ? { ...component.attributes }
        : {};

    // Tailwind classes (لو حابب تخلي الخلفية من الثيم)
    const baseWrapperClasses =
        'min-h-[calc(100vh-72px)] w-full';

    attrs.class = attrs.class
        ? `${attrs.class} ${baseWrapperClasses}`
        : baseWrapperClasses;

    // inline style لضمان ملء الشاشة داخل iframe حتى لو Tailwind ما شاف الكلاسات
    const baseWrapperStyles =
        'min-height: calc(100vh - 72px); width: 100%;';

    attrs.style = attrs.style
        ? `${attrs.style}; ${baseWrapperStyles}`
        : baseWrapperStyles;

    return {
        ...component,
        type,
        components,
        attributes: attrs,
    };
}


/**
 * Ensure a single frame is valid.
 */
function ensureFrame(frame, index = 0) {
    if (!isPlainObject(frame)) {
        frame = {};
    }

    const id = frame.id || `frame-${index + 1}`;
    const component = ensureWrapperComponent(frame.component);

    return {
        ...frame,
        id,
        component,
    };
}

/**
 * Ensure a single page is valid.
 */
function ensurePage(page, pageIndex = 0) {
    if (!isPlainObject(page)) {
        page = {};
    }

    let frames = Array.isArray(page.frames) ? page.frames : [];

    if (!frames.length) {
        frames = [ensureFrame({ id: `frame-${pageIndex + 1}` }, 0)];
    } else {
        frames = frames.map((frame, frameIndex) =>
            ensureFrame(frame, frameIndex)
        );
    }

    const id = page.id || (pageIndex === 0 ? 'index' : `page-${pageIndex + 1}`);
    const name =
        page.name || (pageIndex === 0 ? 'Index' : `Page ${pageIndex + 1}`);

    return {
        ...page,
        id,
        name,
        frames,
    };
}

/**
 * Default project structure used when backend returns null/empty.
 */
function getDefaultStructure() {
    return {
        pages: [
            {
                id: 'index',
                name: 'Index',
                frames: [
                    {
                        id: 'frame-1',
                        component: {
                            type: 'wrapper',
                            components: [],
                        },
                    },
                ],
            },
        ],
        assets: [],
        styles: [],
    };
}

/**
 * Ensure the entire GrapesJS project structure is valid.
 */
function ensureStructure(raw) {
    if (!isPlainObject(raw)) {
        raw = {};
    }

    let pages = Array.isArray(raw.pages) ? raw.pages : [];

    if (!pages.length) {
        const def = getDefaultStructure();
        return {
            ...raw,
            ...def,
        };
    }

    pages = pages.map((page, pageIndex) => ensurePage(page, pageIndex));

    const assets = Array.isArray(raw.assets) ? raw.assets : [];
    const styles = Array.isArray(raw.styles) ? raw.styles : [];

    return {
        ...raw,
        pages,
        assets,
        styles,
    };
}

/**
 * Helper to safely make GrapesJS canvas/frame fill the available area.
 * Also sets a light background for the iframe canvas.
 */
function setCanvasFullSize(editor) {
    if (!editor || !editor.Canvas) return;

    const canvas = editor.Canvas;

    const canvasView =
        typeof canvas.getCanvasView === 'function'
            ? canvas.getCanvasView()
            : null;

    if (canvasView?.el) {
        canvasView.el.classList.add('h-full', 'w-full');
    }

    const frame =
        typeof canvas.getFrameEl === 'function' ? canvas.getFrameEl() : null;

    if (frame) {
        frame.classList.add('h-full', 'w-full');

        const doc = frame.contentDocument;
        if (doc?.documentElement) {
            doc.documentElement.style.backgroundColor = '#fff';
        }
        if (doc?.body) {
            doc.body.style.backgroundColor = '#f8fafc';
            doc.body.classList.add('bg-slate-50');
            doc.body.style.minHeight = '100vh';
            doc.body.style.margin = '0';
        }
    }
}

const root = document.getElementById('page-builder-root');

if (root) {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content || '';
    const loadUrl = root.dataset.loadUrl;
    const saveUrl = root.dataset.saveUrl;
    const previewUrl = root.dataset.previewUrl;
    const builderUrl = root.dataset.builderUrl;

    // Canvas styles coming from Blade (Tailwind v4 app.css)
    let canvasStyles = [];
    try {
        const raw = root.dataset.canvasStyles || '[]';
        canvasStyles = JSON.parse(raw);
        if (!Array.isArray(canvasStyles)) {
            canvasStyles = [];
        }
    } catch (e) {
        console.warn('Failed to parse canvas styles', e);
        canvasStyles = [];
    }

    const editor = grapesjs.init({
        container: '#gjs',
        height: '100%',
        fromElement: false,
        noticeOnUnload: true,
        storageManager: {
            type: 'remote',
            autosave: false,
            autoload: true,
            stepsBeforeSave: 1,
            options: {
                remote: {
                    urlLoad: loadUrl,
                    urlStore: saveUrl,
                    fetchOptions: { credentials: 'include' },
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    // Always return a safe project structure to GrapesJS
                    onLoad: (result) => {
                        const raw = result?.structure ?? result ?? null;
                        return ensureStructure(raw);
                    },
                    // Always store a safe project structure in the backend
                    onStore: (data, editorInstance) => {
                        const projectData = editorInstance?.getProjectData
                            ? editorInstance.getProjectData()
                            : data;

                        const safeProject = ensureStructure(projectData);

                        return { structure: safeProject };
                    },
                },
            },
            storeCss: false,
            storeHtml: false,
            storeStyles: false,
            storeComponents: true,
            storeAssets: true,
        },
        blockManager: {
            appendTo: '#gjs-blocks',
        },
        panels: { defaults: [] },
        deviceManager: {
            devices: [
                { name: 'Desktop', width: '' },
                { name: 'Tablet', width: '768px' },
                { name: 'Mobile', width: '375px' },
            ],
        },
        canvas: {
            styles: [
                '/assets/tamplate/css/app.css', // هذا يحتوي Tailwind
                '/assets/tamplate/css/custom.css', // لو عندك ملفاتك
            ],
            scripts: [],
        },
    });

    editor.on('load', () => {
        editor.Panels.getPanels().reset();
        editor.setDevice('Desktop');
        setCanvasFullSize(editor);
    });

    registerComponents(editor);
    registerBlocks(editor);
    wireControls(editor, previewUrl, builderUrl);
}

/**
 * Register logical section component types.
 */
function registerComponents(editor) {
    const domc = editor.DomComponents;

    // HERO SECTION مطابق تقريبًا للـ Blade
    domc.addType('hero-section', {
        isComponent: (el) => el?.dataset?.sectionType === 'hero',
        model: {
            defaults: {
                name: 'Hero',
                tagName: 'section',
                attributes: {
                    'data-section-type': 'hero',
                    class: 'relative bg-gradient-to-tr from-primary to-primary shadow-2xl overflow-hidden -mt-20 min-h-[calc(100vh-72px)]',
                },
                draggable: true,
                droppable: false,
                highlightable: true,
                // نقدر نضيف Traits لاحقًا لو حبينا نتحكم في خيارات الهيرو
                traits: [],
                components: [
                    // صورة الخلفية
                    {
                        type: 'image',
                        attributes: {
                            src: '/assets/tamplate/images/template.webp',
                            alt: '',
                            'aria-hidden': 'true',
                            decoding: 'async',
                            loading: 'eager',
                            fetchpriority: 'high',
                            class: 'absolute inset-0 z-0 opacity-80 w-full h-full object-cover object-center ltr:scale-x-[-1] rtl:scale-x-100 transition-transform duration-500 ease-in-out',
                        },
                    },

                    // المحتوى الرئيسي
                    {
                        tagName: 'div',
                        attributes: {
                            class: 'relative z-10 px-4 sm:px-8 lg:px-24 py-20 sm:py-28 lg:py-32 flex flex-col-reverse md:flex-row items-center justify-between gap-12 min-h-[600px] lg:min-h-[700px]',
                        },
                        components: [
                            {
                                tagName: 'div',
                                attributes: {
                                    class: 'max-w-xl rtl:text-right ltr:text-left text-center md:text-start',
                                },
                                components: [
                                    // العنوان
                                    {
                                        type: 'text',
                                        tagName: 'h1',
                                        attributes: {
                                            'data-field': 'title',
                                            class: 'text-3xl/20 sm:text-4xl/20 lg:text-5xl/20 font-extrabold text-white leading-tight drop-shadow-lg mb-6',
                                        },
                                        content: 'عنوان غير متوفر',
                                    },
                                    // الوصف
                                    {
                                        type: 'text',
                                        tagName: 'p',
                                        attributes: {
                                            'data-field': 'subtitle',
                                            class: 'text-white/90 text-base sm:text-lg font-light mb-8',
                                        },
                                        content: 'نص وصفي قصير يوضح الفكرة الرئيسية للخدمة أو المنصة.',
                                    },
                                    // الأزرار
                                    {
                                        tagName: 'div',
                                        attributes: {
                                            class: 'flex flex-row flex-wrap gap-3 justify-center md:justify-start',
                                        },
                                        components: [
                                            {
                                                type: 'link',
                                                attributes: {
                                                    'data-field': 'primary-button',
                                                    href: '#',
                                                    'aria-label': 'ابدأ الآن',
                                                    class: 'bg-secondary hover:bg-primary text-white font-bold px-6 py-3 rounded-lg shadow transition text-sm sm:text-base',
                                                },
                                                content: 'ابدأ الآن',
                                            },
                                            {
                                                type: 'link',
                                                attributes: {
                                                    'data-field': 'secondary-button',
                                                    href: '#',
                                                    class: 'bg-white/10 text-white font-bold px-6 py-3 rounded-lg shadow transition hover:bg-white/20 text-sm sm:text-base border border-white/30',
                                                },
                                                content: 'استعرض القوالب',
                                            },
                                        ],
                                    },
                                ],
                            },
                        ],
                    },

                    // الإضاءة السفلية
                    {
                        tagName: 'div',
                        attributes: {
                            class: 'absolute -bottom-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl z-0',
                        },
                    },
                ],
            },
        },
    });

    // 👇 اترك باقي الـ components كما هي (features-section و feature-item ...)
    domc.addType('features-section', {
        isComponent: (el) => el?.dataset?.sectionType === 'features',
        model: {
            defaults: {
                name: 'Features',
                tagName: 'section',
                attributes: {
                    'data-section-type': 'features',
                    'data-layout': 'grid',
                },
                draggable: true,
                droppable: true,
                highlightable: true,
                traits: [
                    {
                        name: 'data-layout',
                        label: 'Layout',
                        type: 'select',
                        options: [
                            { id: 'grid', name: 'Grid' },
                            { id: 'list', name: 'List' },
                        ],
                        changeProp: 1,
                    },
                ],
                components: [
                    // ... نفس اللي عندك الآن
                ],
            },
        },
    });

    domc.addType('feature-item', {
        isComponent: (el) => el?.dataset?.field === 'feature-item',
        model: {
            defaults: {
                name: 'Feature item',
                tagName: 'article',
                attributes: {
                    'data-field': 'feature-item',
                    'data-icon': '<i class="ti ti-check"></i>',
                },
                draggable: true,
                droppable: false,
                highlightable: true,
                traits: [
                    {
                        name: 'data-icon',
                        label: 'Icon (HTML or name)',
                        type: 'text',
                        changeProp: 1,
                        placeholder: '<i class="ti ti-check"></i>',
                    },
                ],
            },
        },
    });
}


/**
 * Helper to create a feature-item block with Tailwind layout.
 */
function createFeatureItemComponent(title, description, iconHtml) {
    return {
        type: 'feature-item',
        attributes: {
            'data-field': 'feature-item',
            'data-icon': iconHtml,
        },
        components: [
            {
                tagName: 'div',
                attributes: {
                    class:
                        'flex items-start gap-3 rtl:flex-row-reverse',
                },
                components: [
                    {
                        tagName: 'div',
                        attributes: {
                            class:
                                'mt-1 w-9 h-9 rounded-full flex items-center ' +
                                'justify-center bg-primary-50 text-primary-700 ' +
                                'dark:bg-primary-900/40 dark:text-primary-200 text-sm',
                            'data-field': 'item-icon',
                        },
                        content: iconHtml,
                    },
                    {
                        tagName: 'div',
                        components: [
                            {
                                type: 'text',
                                tagName: 'h3',
                                attributes: {
                                    'data-field': 'item-title',
                                    class:
                                        'text-sm font-semibold ' +
                                        'text-slate-900 dark:text-white',
                                },
                                content: title,
                            },
                            {
                                type: 'text',
                                tagName: 'p',
                                attributes: {
                                    'data-field': 'item-description',
                                    class:
                                        'mt-1 text-xs sm:text-sm ' +
                                        'text-slate-600 dark:text-slate-300',
                                },
                                content: description,
                            },
                        ],
                    },
                ],
            },
        ],
    };
}

/**
 * Register hero + features blocks.
 */
function registerBlocks(editor) {
    const bm = editor.BlockManager;

    bm.add('hero-block', {
        label: 'Hero',
        category: 'Sections',
        media:
            '<svg viewBox="0 0 24 24" width="24" height="24"><rect x="3" y="5" width="18" height="6" rx="1.5" fill="#111827"></rect><rect x="3" y="13" width="10" height="6" rx="1.5" fill="#9ca3af"></rect><rect x="15" y="13" width="6" height="6" rx="1.5" fill="#111827"></rect></svg>',
        content: {
            type: 'hero-section',
        },
    });

    bm.add('features-block', {
        label: 'Features',
        category: 'Sections',
        media:
            '<svg viewBox="0 0 24 24" width="24" height="24"><rect x="3" y="4" width="18" height="3" rx="1" fill="#111827"></rect><rect x="3" y="9" width="18" height="3" rx="1" fill="#6b7280"></rect><rect x="3" y="14" width="18" height="3" rx="1" fill="#9ca3af"></rect><rect x="3" y="19" width="18" height="3" rx="1" fill="#d1d5db"></rect></svg>',
        content: {
            type: 'features-section',
        },
    });
}

/**
 * Manage save state + autosave with a small UI indicator.
 */
function createSaveManager(editor, saveButton) {
    const statusRoot = document.getElementById('builder-save-status');
    const statusText = statusRoot?.querySelector('[data-status-text]');
    const statusTime = statusRoot?.querySelector('[data-status-time]');
    const statusDot = statusRoot?.querySelector('[data-status-dot]');
    const dotColors = [
        'bg-amber-400',
        'bg-emerald-400',
        'bg-blue-400',
        'bg-rose-400',
        'bg-slate-400',
    ];

    let lastSavedAt = null;
    let autoSaveTimer = null;
    let autoSavePaused = false;
    let isSaving = false;
    let hasPendingChanges = false;
    const AUTO_SAVE_DELAY = 1200;

    const formatTime = (date) => {
        const asDate = date instanceof Date ? date : new Date(date);
        if (!asDate || Number.isNaN(asDate.getTime())) return '--:--';
        return asDate.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const setStatus = (state, timeOverride = null) => {
        if (!statusRoot) return;
        const states = {
            dirty: { text: 'Unsaved', color: 'bg-amber-400', pulse: true },
            saving: { text: 'Saving...', color: 'bg-blue-400', pulse: true },
            saved: { text: 'Saved', color: 'bg-emerald-400', pulse: false },
            error: {
                text: 'Autosave paused',
                color: 'bg-rose-400',
                pulse: true,
            },
        };

        const config = states[state] || states.dirty;
        if (statusText) statusText.textContent = config.text;
        if (statusDot) {
            statusDot.classList.remove(...dotColors, 'animate-pulse');
            statusDot.classList.add(config.color);
            if (config.pulse) statusDot.classList.add('animate-pulse');
        }
        const timeValue = timeOverride || lastSavedAt;
        if (statusTime) statusTime.textContent = timeValue
            ? formatTime(timeValue)
            : '--:--';
    };

    const setButtonState = (saving) => {
        if (!saveButton) return;
        saveButton.disabled = saving;
        saveButton.classList.toggle('opacity-70', saving);
    };

    const scheduleAutoSave = () => {
        if (autoSavePaused) return;
        clearTimeout(autoSaveTimer);
        autoSaveTimer = window.setTimeout(() => runSave('auto'), AUTO_SAVE_DELAY);
    };

    const runSave = async (source = 'manual') => {
        if (isSaving) return;
        isSaving = true;
        clearTimeout(autoSaveTimer);
        const startedWithPending = hasPendingChanges;
        hasPendingChanges = false;

        setButtonState(true);
        setStatus('saving');

        try {
            await editor.store();
            lastSavedAt = new Date();
            setStatus('saved', lastSavedAt);
            autoSavePaused = false;
            if (source === 'manual') {
                notify('Page saved');
            }
        } catch (error) {
            console.error(error);
            setStatus('error');
            notify(
                source === 'auto'
                    ? 'Autosave failed. Autosave paused'
                    : 'Save failed',
                'error'
            );
            autoSavePaused = true;
            hasPendingChanges = startedWithPending || hasPendingChanges;
        } finally {
            isSaving = false;
            setButtonState(false);

            if (hasPendingChanges && !autoSavePaused) {
                scheduleAutoSave();
            }
        }
    };

    const markDirty = () => {
        hasPendingChanges = true;
        setStatus('dirty');
        if (!autoSavePaused) {
            scheduleAutoSave();
        }
    };

    // Initialize as "saved" (loaded state).
    setStatus('saved');

    return {
        markDirty,
        manualSave: () => runSave('manual'),
        setLoaded: () => {
            lastSavedAt = lastSavedAt || new Date();
            setStatus('saved', lastSavedAt);
        },
        isDirty: () => hasPendingChanges,
    };
}

/**
 * Wire top bar controls.
 */
function wireControls(editor, previewUrl, builderUrl) {
    const saveButton = document.getElementById('builder-save');
    const previewButton = document.getElementById('builder-preview');
    const backLink = document.getElementById('builder-back-link');
    const deviceMenuToggle = document.getElementById('device-menu-toggle');
    const deviceMenu = document.getElementById('device-menu');
    const deviceButtons = deviceMenu?.querySelectorAll('[data-device]');
    const deviceLabel = deviceMenuToggle?.querySelector('[data-device-label]');
    const deviceIcon = deviceMenuToggle?.querySelector('[data-device-icon]');
    const previewMenu = document.getElementById('preview-menu');
    const previewStatus = document.querySelector('[data-preview-status]');
    const previewInlineBtn = document.getElementById('preview-inline-btn');
    const previewNewTabBtn = document.getElementById('preview-newtab-btn');
    const blocksDrawer = document.getElementById('drawer-blocks');
    const settingsDrawer = document.getElementById('drawer-settings');
    const sectionsToggle = document.getElementById('builder-sections-toggle');
    const drawerCloseButtons =
        document.querySelectorAll('[data-close-drawer]');
    const sectionsIconOpen = document.querySelector(
        '[data-icon-sections-open]'
    );
    const sectionsIconClose = document.querySelector(
        '[data-icon-sections-close]'
    );
    const shortcutsToggle = document.getElementById('builder-shortcuts-toggle');
    const shortcutsModal = document.getElementById('shortcuts-modal');
    const shortcutsCloseButtons = document.querySelectorAll(
        '[data-close-shortcuts]'
    );
    const localeSwitch = document.getElementById('builder-locale-switch');
    const blocksTabButtons = document.querySelectorAll('[data-blocks-tab]');
    const blocksTabPanels = document.querySelectorAll('.blocks-tab-panel');
    const settingsLearnButton = document.getElementById('settings-empty-learn');
    const sectionsList = document.getElementById('gjs-sections');
    const unsavedModal = document.getElementById('unsaved-preview-modal');
    const unsavedSaveBtn = document.getElementById('unsaved-preview-save');
    const unsavedSkipBtn = document.getElementById('unsaved-preview-skip');
    const unsavedCloseButtons =
        document.querySelectorAll('[data-close-unsaved]');
    const snackbar = document.getElementById('builder-snackbar');
    const snackbarBody = snackbar?.querySelector('[data-snackbar-body]');
    const snackbarText = snackbar?.querySelector('[data-snackbar-text]');
    const snackbarDot = snackbar?.querySelector('[data-snackbar-dot]');
    const saveManager = createSaveManager(editor, saveButton);
    let setDeviceHandler = null;
    let setPreviewState = null;
    let pendingPreviewAction = null;

    if (saveButton) {
        saveButton.addEventListener('click', () => saveManager.manualSave());
    }

    const toggleUnsavedModal = (open) => {
        if (!unsavedModal) return;
        const shouldOpen = typeof open === 'boolean'
            ? open
            : unsavedModal.classList.contains('hidden');
        unsavedModal.classList.toggle('hidden', !shouldOpen);
        unsavedModal.classList.toggle('flex', shouldOpen);
    };

    const togglePreviewMenu = (open) => {
        if (!previewMenu) return;
        const shouldOpen = typeof open === 'boolean'
            ? open
            : previewMenu.classList.contains('hidden');
        previewMenu.classList.toggle('hidden', !shouldOpen);
    };

    const performPreviewAction = (action) => {
        if (action === 'newtab' && previewUrl) {
            window.open(previewUrl, '_blank');
            return;
        }
        if (action === 'inline') {
            const isActive = editor.Commands.isActive('core:preview');
            if (isActive) {
                editor.stopCommand('core:preview');
            } else {
                editor.runCommand('core:preview');
            }
        }
    };

    const requestPreviewAction = (action) => {
        if (saveManager.isDirty()) {
            pendingPreviewAction = action;
            toggleUnsavedModal(true);
            return;
        }
        performPreviewAction(action);
    };

    if (previewButton) {
        const previewIconOn = previewButton.querySelector(
            '[data-icon-preview-on]'
        );
        const previewIconOff = previewButton.querySelector(
            '[data-icon-preview-off]'
        );

        setPreviewState = (active) => {
            previewButton.classList.toggle('ring-2', active);
            previewButton.classList.toggle('ring-primary-200', active);
            previewButton.classList.toggle(
                'dark:ring-primary-800',
                active
            );
            previewButton.classList.toggle('bg-white', active);
            previewButton.classList.toggle('dark:bg-slate-800', active);
            previewButton.classList.toggle('shadow-sm', active);
            if (previewIconOn && previewIconOff) {
                previewIconOn.classList.toggle('hidden', active);
                previewIconOff.classList.toggle('hidden', !active);
            }
            if (previewStatus) {
                previewStatus.textContent = active
                    ? 'Inline on'
                    : 'Inline off';
            }
            previewButton.title = active
                ? 'Preview: inline on • Click to exit • Ctrl/Cmd+Click: New tab • Right-click: Options'
                : 'Preview: inline off • Click to enter • Ctrl/Cmd+Click: New tab • Right-click: Options';
        };

        previewButton.addEventListener('click', (event) => {
            if (previewUrl && (event.metaKey || event.ctrlKey)) {
                requestPreviewAction('newtab');
                return;
            }
            requestPreviewAction('inline');
        });

        previewButton.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            togglePreviewMenu(true);
        });

        editor.on('run:core:preview', () => setPreviewState?.(true));
        editor.on('stop:core:preview', () => setPreviewState?.(false));
    }

    if (deviceButtons && deviceButtons.length) {
        const setDevice = (device) => {
            editor.setDevice(device);

            const iconPaths = {
                Desktop:
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5v9.75H3.75zM9.75 18.75h4.5" />',
                Tablet:
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5h9A1.5 1.5 0 0118 6v12a1.5 1.5 0 01-1.5 1.5h-9A1.5 1.5 0 016 18V6A1.5 1.5 0 017.5 4.5zM10.5 18.75h3" />',
                Mobile:
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5h6A1.5 1.5 0 0116.5 6v12a1.5 1.5 0 01-1.5 1.5H9A1.5 1.5 0 017.5 18V6A1.5 1.5 0 019 4.5zM10.5 18.75h3" />',
            };

            deviceButtons.forEach((btn) => {
                const isActive = btn.dataset.device === device;
                btn.classList.toggle('bg-slate-100', isActive);
                btn.classList.toggle('dark:bg-slate-800', isActive);
                btn.classList.toggle('text-primary-700', isActive);
                btn.classList.toggle('dark:text-primary-200', isActive);
            });

            if (deviceLabel) deviceLabel.textContent = device;
            if (deviceIcon) {
                deviceIcon.innerHTML =
                    iconPaths[device] || iconPaths.Desktop;
            }
        };

        setDeviceHandler = setDevice;

        const closeMenu = () => {
            if (deviceMenu) deviceMenu.classList.add('hidden');
        };

        deviceButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                setDevice(btn.dataset.device);
                closeMenu();
            });
        });

        if (deviceMenuToggle && deviceMenu) {
            deviceMenuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                deviceMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (
                    !deviceMenu.contains(e.target) &&
                    !deviceMenuToggle.contains(e.target)
                ) {
                    closeMenu();
                }
            });
        }
    }

    const toggleShortcutsModal = (open) => {
        if (!shortcutsModal || !shortcutsToggle) return;
        const shouldOpen = typeof open === 'boolean'
            ? open
            : shortcutsModal.classList.contains('hidden');

        shortcutsModal.classList.toggle('hidden', !shouldOpen);
        shortcutsModal.classList.toggle('flex', shouldOpen);
        shortcutsToggle.setAttribute(
            'aria-expanded',
            shouldOpen ? 'true' : 'false'
        );
    };

    if (shortcutsToggle && shortcutsModal) {
        shortcutsToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleShortcutsModal();
        });

        shortcutsCloseButtons.forEach((btn) => {
            btn.addEventListener('click', () => toggleShortcutsModal(false));
        });

        shortcutsModal.addEventListener('click', (e) => {
            if (e.target === shortcutsModal) {
                toggleShortcutsModal(false);
            }
        });
    }

    if (localeSwitch && builderUrl) {
        localeSwitch.addEventListener('change', (event) => {
            const locale = event.target.value;
            if (!locale) return;

            const url = new URL(builderUrl, window.location.origin);
            url.searchParams.set('lang', locale);
            window.location.href = url.toString();
        });
    }

    if (settingsLearnButton) {
        settingsLearnButton.addEventListener('click', () => {
            window.open(
                'https://grapesjs.com/docs/getting-started',
                '_blank',
                'noopener'
            );
        });
    }

    if (unsavedModal) {
        unsavedCloseButtons.forEach((btn) =>
            btn.addEventListener('click', () => toggleUnsavedModal(false))
        );

        if (unsavedSkipBtn) {
            unsavedSkipBtn.addEventListener('click', () => {
                performPreviewAction(pendingPreviewAction || 'newtab');
                toggleUnsavedModal(false);
                pendingPreviewAction = null;
            });
        }

        if (unsavedSaveBtn) {
            unsavedSaveBtn.addEventListener('click', async () => {
                unsavedSaveBtn.disabled = true;
                unsavedSkipBtn && (unsavedSkipBtn.disabled = true);
                try {
                    await saveManager.manualSave();
                    performPreviewAction(pendingPreviewAction || 'newtab');
                    toggleUnsavedModal(false);
                } finally {
                    unsavedSaveBtn.disabled = false;
                    unsavedSkipBtn &&
                        (unsavedSkipBtn.disabled = false);
                    pendingPreviewAction = null;
                }
            });
        }
    }

    const toggleDrawer = (drawer, open) => {
        if (!drawer) return;
        const isRTL = document.documentElement.dir === 'rtl';
        const openClass = 'translate-x-0';

        const closedClass = drawer.id === 'drawer-blocks'
            ? isRTL
                ? 'translate-x-full'
                : '-translate-x-full'
            : isRTL
                ? '-translate-x-full'
                : 'translate-x-full';

        drawer.classList.remove(
            openClass,
            '-translate-x-full',
            'translate-x-full'
        );
        drawer.classList.add(open ? openClass : closedClass);

        editor.refresh();
        editor.trigger('change:canvasOffset');

        if (drawer === blocksDrawer && sectionsToggle) {
            sectionsToggle.classList.toggle('ring-2', open);
            sectionsToggle.classList.toggle('ring-primary-200', open);
            sectionsToggle.classList.toggle(
                'dark:ring-primary-800',
                open
            );
            sectionsToggle.classList.toggle('bg-white', open);
            sectionsToggle.classList.toggle('dark:bg-slate-800', open);
            sectionsToggle.classList.toggle('shadow-sm', open);
            if (sectionsIconOpen && sectionsIconClose) {
                sectionsIconOpen.classList.toggle('hidden', open);
                sectionsIconClose.classList.toggle('hidden', !open);
            }
        }
    };

    if (sectionsToggle && blocksDrawer) {
        sectionsToggle.addEventListener('click', () => {
            const isOpen = blocksDrawer.classList.contains('translate-x-0');
            toggleDrawer(blocksDrawer, !isOpen);
        });
    }

    drawerCloseButtons.forEach((btn) => {
        const target = btn.dataset.closeDrawer;
        btn.addEventListener('click', () => {
            if (target === 'blocks') {
                toggleDrawer(blocksDrawer, false);
            }
            if (target === 'settings') {
                toggleDrawer(settingsDrawer, false);
            }
        });
    });

    editor.on('component:selected', (cmp) => {
        const wrapper = editor.getWrapper();
        if (cmp && cmp !== wrapper) {
            toggleDrawer(settingsDrawer, true);
        }
    });

    editor.on('component:deselected', () => {
        const selected = editor.getSelected();
        if (!selected) {
            toggleDrawer(settingsDrawer, false);
        }
    });

    const setBlocksTab = (tab) => {
        blocksTabButtons.forEach((btn) => {
            const isActive = btn.dataset.blocksTab === tab;
            btn.classList.toggle('bg-primary-50', isActive);
            btn.classList.toggle('text-primary-700', isActive);
            btn.classList.toggle(
                'dark:bg-primary-900/30',
                isActive
            );
            btn.classList.toggle('dark:text-primary-200', isActive);
        });

        blocksTabPanels.forEach((panel) => {
            const panelId =
                panel.id === 'gjs-blocks'
                    ? 'blocks'
                    : panel.id === 'gjs-sections'
                        ? 'sections'
                        : '';
            panel.classList.toggle('hidden', panelId !== tab);
        });
    };

    if (blocksTabButtons.length && blocksTabPanels.length) {
        blocksTabButtons.forEach((btn) => {
            btn.addEventListener('click', () =>
                setBlocksTab(btn.dataset.blocksTab)
            );
        });
        setBlocksTab('sections');
    }

    const changeEvents = [
        'change:changesCount',
        'component:add',
        'component:remove',
        'component:update',
        'component:move',
        'styleManager:change',
        'asset:add',
        'asset:remove',
    ];
    changeEvents.forEach((eventName) =>
        editor.on(eventName, saveManager.markDirty)
    );
    editor.on('storage:load', () => saveManager.setLoaded());

    const isTyping = (event) => {
        const target = event.target;
        return (
            target?.closest('input, textarea') ||
            target?.isContentEditable
        );
    };

    const showSnackbar = (message, type = 'success') => {
        if (!snackbar || !snackbarBody || !snackbarText || !snackbarDot) {
            return;
        }
        const colors = {
            success: [
                'bg-emerald-500',
                'border-emerald-100',
                'text-emerald-900',
                'dark:text-emerald-100',
                'dark:border-emerald-800',
                'bg-white',
                'dark:bg-slate-800',
            ],
            error: [
                'bg-rose-500',
                'border-rose-100',
                'text-rose-900',
                'dark:text-rose-100',
                'dark:border-rose-800',
                'bg-white',
                'dark:bg-slate-800',
            ],
            info: [
                'bg-blue-500',
                'border-blue-100',
                'text-blue-900',
                'dark:text-blue-100',
                'dark:border-blue-800',
                'bg-white',
                'dark:bg-slate-800',
            ],
        };
        const selected = colors[type] || colors.success;

        snackbarBody.className =
            'flex items-center gap-2 px-4 py-3 rounded-xl shadow-2xl border text-sm font-medium transition';
        snackbarBody.classList.add(
            selected[5] || 'bg-white',
            selected[6] || 'dark:bg-slate-800',
            selected[1] || 'border-slate-200'
        );
        snackbarText.className = selected[2] || 'text-slate-900';
        snackbarDot.className = `w-2 h-2 rounded-full ${selected[0]}`;

        snackbarText.textContent = message;
        snackbar.classList.remove('hidden');
        snackbar.classList.add('opacity-100');

        window.clearTimeout(snackbar._timer);
        snackbar._timer = window.setTimeout(() => {
            snackbar.classList.add('hidden');
        }, 2500);
    };

    document.addEventListener('keydown', (event) => {
        if (isTyping(event)) return;

        const meta = event.metaKey || event.ctrlKey;
        const shift = event.shiftKey;
        const key = event.key.toLowerCase();

        if (event.key === 'Escape') {
            toggleShortcutsModal(false);
            toggleUnsavedModal(false);
            return;
        }

        if (!meta) return;

        // Save: Ctrl/Cmd + S
        if (key === 's') {
            event.preventDefault();
            saveManager.manualSave();
            return;
        }

        // Toggle shortcuts: Ctrl/Cmd + /
        if (event.key === '/') {
            event.preventDefault();
            toggleShortcutsModal();
            return;
        }

        // Device switches: Ctrl/Cmd + Shift + 1/2/3
        if (shift && setDeviceHandler) {
            if (event.key === '1') {
                event.preventDefault();
                setDeviceHandler('Desktop');
                return;
            }
            if (event.key === '2') {
                event.preventDefault();
                setDeviceHandler('Tablet');
                return;
            }
            if (event.key === '3') {
                event.preventDefault();
                setDeviceHandler('Mobile');
                return;
            }
            if (event.key === 'b' && backLink?.href) {
                event.preventDefault();
                window.location.href = backLink.href;
                return;
            }
        }
    });

    if (previewMenu) {
        const closeOnClickOutside = (event) => {
            if (
                !previewMenu.contains(event.target) &&
                !previewButton?.contains(event.target)
            ) {
                togglePreviewMenu(false);
            }
        };
        document.addEventListener('click', closeOnClickOutside);

        if (previewInlineBtn) {
            previewInlineBtn.addEventListener('click', () => {
                togglePreviewMenu(false);
                requestPreviewAction('inline');
            });
        }
        if (previewNewTabBtn) {
            previewNewTabBtn.addEventListener('click', () => {
                togglePreviewMenu(false);
                requestPreviewAction('newtab');
            });
        }
    }

    window.addEventListener('beforeunload', (event) => {
        if (saveManager.isDirty()) {
            event.preventDefault();
            event.returnValue = '';
            return '';
        }
        return undefined;
    });

    // Override notify helper locally to use snackbar
    window._builderNotify = (message, type = 'success') =>
        showSnackbar(message, type);

    let dragSectionCid = null;

    const renderSectionsList = () => {
        if (!sectionsList || !editor.getWrapper) return;
        const wrapper = editor.getWrapper();
        if (!wrapper || typeof wrapper.components !== 'function') return;
        const collection = wrapper.components();
        if (!collection) return;
        const children = collection.models || [];

        sectionsList.innerHTML = '';

        if (!children.length) {
            const empty = document.createElement('p');
            empty.className =
                'text-xs text-slate-500 dark:text-slate-400';
            empty.textContent = 'No sections yet.';
            sectionsList.appendChild(empty);
            return;
        }

        children.forEach((cmp, index) => {
            const item = document.createElement('div');
            item.className =
                'flex items-center justify-between gap-2 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-800 dark:text-slate-100 cursor-pointer select-none';
            item.draggable = true;
            const cid = cmp.getId();
            item.dataset.cid = cid;

            const name = cmp.getName ? cmp.getName() : cmp.get('name');
            const label =
                name || cmp.get('type') || `Section ${index + 1}`;

            const left = document.createElement('div');
            left.className = 'flex items-center gap-2';

            const badge = document.createElement('span');
            badge.className =
                'w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-100 flex items-center justify-center';
            badge.textContent = index + 1;

            const text = document.createElement('span');
            text.textContent = label;

            left.appendChild(badge);
            left.appendChild(text);

            const handle = document.createElement('span');
            handle.className =
                'text-slate-400 dark:text-slate-500 text-base';
            handle.textContent = '⋮⋮';

            item.appendChild(left);
            item.appendChild(handle);

            item.addEventListener('click', () => {
                editor.select(cmp);
            });

            item.addEventListener('dragstart', (event) => {
                dragSectionCid = cid;
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', cid);
                item.classList.add(
                    'ring-2',
                    'ring-primary-200',
                    'dark:ring-primary-800'
                );
            });

            item.addEventListener('dragend', () => {
                dragSectionCid = null;
                item.classList.remove(
                    'ring-2',
                    'ring-primary-200',
                    'dark:ring-primary-800'
                );
            });

            item.addEventListener('dragover', (event) => {
                event.preventDefault();
                item.classList.add(
                    'bg-slate-50',
                    'dark:bg-slate-750'
                );
            });

            item.addEventListener('dragleave', () => {
                item.classList.remove(
                    'bg-slate-50',
                    'dark:bg-slate-750'
                );
            });

            item.addEventListener('drop', (event) => {
                event.preventDefault();
                item.classList.remove(
                    'bg-slate-50',
                    'dark:bg-slate-750'
                );
                const targetCid = item.dataset.cid;
                if (!dragSectionCid || dragSectionCid === targetCid) {
                    return;
                }

                const collection = wrapper.components();
                const models = collection?.models || [];
                const fromIndex = models.findIndex(
                    (m) => m.getId() === dragSectionCid
                );
                const toIndex = models.findIndex(
                    (m) => m.getId() === targetCid
                );
                if (fromIndex < 0 || toIndex < 0) return;

                const moving = models[fromIndex];
                if (moving && typeof moving.move === 'function') {
                    moving.move(wrapper, { at: toIndex });
                }

                editor.select(moving);
                saveManager.markDirty();
                renderSectionsList();
            });

            sectionsList.appendChild(item);
        });
    };

    let sectionsBound = false;
    const bindSectionEvents = () => {
        if (sectionsBound) return;
        sectionsBound = true;
        const rerenderEvents = [
            'component:add',
            'component:remove',
            'component:update',
            'component:move',
        ];
        rerenderEvents.forEach((eventName) =>
            editor.on(eventName, renderSectionsList)
        );
        editor.on('storage:load', renderSectionsList);
    };

    editor.on('canvas:frame:load', () => {
        editor.setDevice('Desktop');
        setCanvasFullSize(editor);

        if (previewButton) {
            previewButton.disabled = false;
            previewButton.classList.remove(
                'opacity-60',
                'cursor-not-allowed'
            );
        }
        renderSectionsList();
    });

    editor.on('load', () => {
        bindSectionEvents();
        // Slight delay to ensure frame ready
        window.setTimeout(renderSectionsList, 50);
    });
}

/**
 * Tiny helper to display feedback with SweetAlert (if present).
 */
function notify(message, type = 'success') {
    if (window.Swal) {
        window.Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            title: message,
            showConfirmButton: false,
            timer: 1800,
        });
    } else {
        // eslint-disable-next-line no-console
        console.log(`[${type}] ${message}`);
    }
}
