import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

const root = document.getElementById('page-builder-root');

if (root) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const loadUrl = root.dataset.loadUrl;
    const saveUrl = root.dataset.saveUrl;
    const previewUrl = root.dataset.previewUrl;

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
                    onLoad: (result) => result?.structure || {},
                    onStore: (data, editorInstance) => {
                        const projectData = editorInstance?.getProjectData
                            ? editorInstance.getProjectData()
                            : data;

                        return { structure: projectData };
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
        canvas: {
            styles: [],
            scripts: [],
        },
    });

    registerComponents(editor);
    registerBlocks(editor);
    wireControls(editor, previewUrl);
}

/**
 * Register logical section component types.
 */
function registerComponents(editor) {
    const domc = editor.DomComponents;

    domc.addType('hero-section', {
        isComponent: (el) => el?.dataset?.sectionType === 'hero',
        model: {
            defaults: {
                name: 'Hero',
                tagName: 'section',
                attributes: {
                    'data-section-type': 'hero',
                    'data-alignment': 'left',
                },
                draggable: true,
                droppable: false,
                highlightable: true,
                traits: [
                    {
                        name: 'data-alignment',
                        label: 'Alignment',
                        type: 'select',
                        options: [
                            { id: 'left', name: 'Left' },
                            { id: 'center', name: 'Center' },
                        ],
                        changeProp: 1,
                    },
                    {
                        name: 'data-background',
                        label: 'Background (variant)',
                        type: 'text',
                        placeholder: 'light | dark | gradient',
                        changeProp: 1,
                    },
                ],
                components: [
                    {
                        type: 'text',
                        tagName: 'h1',
                        attributes: { 'data-field': 'title' },
                        content: 'Hero title',
                    },
                    {
                        type: 'text',
                        tagName: 'p',
                        attributes: { 'data-field': 'subtitle' },
                        content: 'Short supporting copy.',
                    },
                    {
                        type: 'link',
                        attributes: {
                            'data-field': 'primary-button',
                            href: '#',
                            title: 'Primary action',
                        },
                        content: 'Primary action',
                    },
                    {
                        type: 'link',
                        attributes: {
                            'data-field': 'secondary-button',
                            href: '#',
                            title: 'Secondary action',
                        },
                        content: 'Secondary action',
                    },
                    {
                        type: 'image',
                        attributes: {
                            'data-field': 'image',
                            src: 'https://placehold.co/800x400',
                            alt: 'Hero image',
                        },
                    },
                ],
            },
        },
    });

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
                    {
                        type: 'text',
                        tagName: 'h2',
                        attributes: { 'data-field': 'title' },
                        content: 'Features title',
                    },
                    {
                        type: 'text',
                        tagName: 'p',
                        attributes: { 'data-field': 'subtitle' },
                        content: 'Why people choose us.',
                    },
                    {
                        type: 'feature-item',
                        attributes: {
                            'data-field': 'feature-item',
                            'data-icon': '<i class="ti ti-check"></i>',
                        },
                        components: [
                            {
                                type: 'text',
                                tagName: 'h3',
                                attributes: { 'data-field': 'item-title' },
                                content: 'Feature name',
                            },
                            {
                                type: 'text',
                                tagName: 'p',
                                attributes: { 'data-field': 'item-description' },
                                content: 'Explain the benefit and outcome.',
                            },
                        ],
                    },
                    {
                        type: 'feature-item',
                        attributes: {
                            'data-field': 'feature-item',
                            'data-icon': '<i class="ti ti-bolt"></i>',
                        },
                        components: [
                            {
                                type: 'text',
                                tagName: 'h3',
                                attributes: { 'data-field': 'item-title' },
                                content: 'Second feature',
                            },
                            {
                                type: 'text',
                                tagName: 'p',
                                attributes: { 'data-field': 'item-description' },
                                content: 'Add a short supporting line.',
                            },
                        ],
                    },
                    {
                        type: 'feature-item',
                        attributes: {
                            'data-field': 'feature-item',
                            'data-icon': '<i class="ti ti-rocket"></i>',
                        },
                        components: [
                            {
                                type: 'text',
                                tagName: 'h3',
                                attributes: { 'data-field': 'item-title' },
                                content: 'Third feature',
                            },
                            {
                                type: 'text',
                                tagName: 'p',
                                attributes: { 'data-field': 'item-description' },
                                content: 'What makes this special?',
                            },
                        ],
                    },
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
 * Register hero + features blocks.
 */
function registerBlocks(editor) {
    const bm = editor.BlockManager;

    bm.add('hero-block', {
        label: 'Hero',
        category: 'Sections',
        media: '<svg viewBox="0 0 24 24" width="24" height="24"><rect x="3" y="5" width="18" height="6" rx="1.5" fill="#111827"></rect><rect x="3" y="13" width="10" height="6" rx="1.5" fill="#9ca3af"></rect><rect x="15" y="13" width="6" height="6" rx="1.5" fill="#111827"></rect></svg>',
        content: {
            type: 'hero-section',
        },
    });

    bm.add('features-block', {
        label: 'Features',
        category: 'Sections',
        media: '<svg viewBox="0 0 24 24" width="24" height="24"><rect x="3" y="4" width="18" height="3" rx="1" fill="#111827"></rect><rect x="3" y="9" width="18" height="3" rx="1" fill="#6b7280"></rect><rect x="3" y="14" width="18" height="3" rx="1" fill="#9ca3af"></rect><rect x="3" y="19" width="18" height="3" rx="1" fill="#d1d5db"></rect></svg>',
        content: {
            type: 'features-section',
        },
    });
}

/**
 * Wire top bar controls.
 */
function wireControls(editor, previewUrl) {
    const saveButton = document.getElementById('builder-save');
    const previewButton = document.getElementById('builder-preview');

    if (saveButton) {
        saveButton.addEventListener('click', async () => {
            saveButton.disabled = true;
            saveButton.classList.add('opacity-70');

            try {
                await editor.store();
                notify('Page saved');
            } catch (error) {
                console.error(error);
                notify('Save failed', 'error');
            } finally {
                saveButton.disabled = false;
                saveButton.classList.remove('opacity-70');
            }
        });
    }

    if (previewButton) {
        previewButton.addEventListener('click', (event) => {
            if (previewUrl && (event.metaKey || event.ctrlKey)) {
                window.open(previewUrl, '_blank');
                return;
            }

            editor.runCommand('core:preview');
        });
    }
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
