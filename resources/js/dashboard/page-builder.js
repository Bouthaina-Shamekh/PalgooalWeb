// resources/js/dashboard/page-builder.js
// Lightweight drag & drop builder with inline edit/delete (no GrapesJS).
import Sortable from 'sortablejs';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('page-builder-root');
    const stage = document.getElementById('builder-stage');
    const emptyState = document.getElementById('builder-empty-state');
    const saveBtn = document.getElementById('builder-save');
    const statusDot = document.querySelector('[data-status-dot]');
    const statusText = document.querySelector('[data-status-text]');
    const statusTime = document.querySelector('[data-status-time]');
    const blockButtons = document.querySelectorAll('.builder-block-btn');
    const previewButtons = document.querySelectorAll('[data-preview]');
    const previewToggleBtn = document.getElementById('preview-toggle-btn');
    const previewMenu = document.getElementById('preview-menu');
    const previewLabel = document.querySelector('[data-preview-label]');
    const outlineList = document.getElementById('builder-outline-list');
    const outlineEmpty = document.getElementById('builder-outline-empty');
    const outlinePanel = document.getElementById('builder-outline');
    const outlineEditor = document.getElementById('outline-editor');
    const outlineEditorTitle = document.querySelector('[data-outline-editor-title]');
    const outlineEditorFieldsWrap = document.getElementById('outline-editor-fields');
    const outlineEditorBack = document.querySelectorAll('[data-outline-editor-back]');
    const outlineEditorSave = document.querySelector('[data-outline-editor-save]');
    const tabButtons = document.querySelectorAll('.builder-tab');
    const tabContents = document.querySelectorAll('.builder-tab-content');
    const tabHelpers = document.querySelectorAll('[data-tab-helper]');

    const storageKey = root?.dataset.pageId ? `builder-html-${root.dataset.pageId}` : null;
    const saveUrl = root?.dataset.saveUrl || root?.dataset.saveurl;
    const blockTypeLabels = {
        features: 'Features',
        services: 'Services',
        'support-hero': 'Support hero',
        'hero-template': 'Template hero',
    };
    const unsupportedTypes = ['text', 'image', 'button', 'section'];
    const defaultFeaturesItems = [
        { title: 'Feature 1', description: 'Describe feature 1.' },
        { title: 'Feature 2', description: 'Describe feature 2.' },
        { title: 'Feature 3', description: 'Describe feature 3.' },
    ];
    const defaultServicesItems = [
        { title: 'Service 1', description: 'Describe service 1.', icon: '', url: '#' },
        { title: 'Service 2', description: 'Describe service 2.', icon: '', url: '#' },
        { title: 'Service 3', description: 'Describe service 3.', icon: '', url: '#' },
    ];
    let blockIdCounter = 0;
    let outlineEditorSchema = [];

    const ensureBlockId = (block) => {
        if (!block) return null;
        if (!block.dataset.blockId) {
            blockIdCounter += 1;
            block.dataset.blockId = `block-${Date.now()}-${blockIdCounter}`;
        }
        return block.dataset.blockId;
    };

    const getBlockLabel = (block) => {
        if (!block) return 'Block';
        const type = block.dataset.type || block.dataset.blockType || '';
        const label = blockTypeLabels[type] || 'Block';
        const heading = block.dataset.heading || block.dataset.title || block.dataset.text || '';
        return heading ? `${label}: ${heading}` : label;
    };

    const setStatus = (text, color) => {
        if (statusText) statusText.textContent = text;
        if (statusDot) statusDot.className = `w-2 h-2 rounded-full ${color}`;
        if (statusTime) statusTime.textContent = new Date().toLocaleTimeString();
    };

    const updateEmptyState = () => {
        if (!emptyState || !stage) return;
        emptyState.classList.toggle('hidden', stage.children.length > 0);
    };

    const persistLocal = () => {
        if (!storageKey || !stage) return;
        localStorage.setItem(storageKey, stage.innerHTML);
    };

    const purgeUnsupportedBlocks = () => {
        if (!stage) return;
        Array.from(stage.querySelectorAll('.builder-block')).forEach((block) => {
            const type = block.dataset.type || block.dataset.blockType || '';
            if (unsupportedTypes.includes(type)) {
                block.remove();
            }
        });
    };

    const serializeBlocks = () => {
        if (!stage) return [];
        return Array.from(stage.children).map((block) => {
            const type = block.dataset.type || block.dataset.blockType || 'unknown';
            const base = { type, data: {} };
            switch (type) {
                case 'features': {
                    let features = [];
                    try {
                        features = JSON.parse(block.dataset.features || '[]');
                    } catch {
                        features = [];
                    }
                    base.data = {
                        title: block.dataset.title || '',
                        subtitle: block.dataset.subtitle || '',
                        bg: block.dataset.bg || '',
                        features,
                    };
                    break;
                }
                case 'hero-template':
                    base.data = {
                        heading: block.dataset.heading || '',
                        subtitle: block.dataset.subtitle || '',
                        primaryText: block.dataset.primaryText || '',
                        primaryUrl: block.dataset.primaryUrl || '#',
                        secondaryText: block.dataset.secondaryText || '',
                        secondaryUrl: block.dataset.secondaryUrl || '#',
                        bg: block.dataset.bg || '',
                    };
                    break;
                case 'support-hero':
                    base.data = {
                        heading: block.dataset.heading || '',
                        body: block.dataset.body || '',
                        lightImg: block.dataset.lightImg || '',
                        darkImg: block.dataset.darkImg || '',
                        colorFrom: block.dataset.colorFrom || '#ff4694',
                        colorTo: block.dataset.colorTo || '#776fff',
                    };
                    break;
                case 'services': {
                    let services = [];
                    try {
                        services = JSON.parse(block.dataset.services || '[]');
                    } catch {
                        services = [];
                    }
                    base.data = {
                        badge: block.dataset.badge || '',
                        title: block.dataset.title || '',
                        subtitle: block.dataset.subtitle || '',
                        bg: block.dataset.bg || '',
                        services,
                    };
                    break;
                }
                default:
                    base.data = block.dataset;
                    break;
            }
            return base;
        });
    };

    const saveRemote = async () => {
        if (!saveUrl) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const payload = {
            structure: {
                mode: 'lite-builder',
                blocks: serializeBlocks(),
            },
        };
        const res = await fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        if (!res.ok) {
            throw new Error(`Save failed (${res.status})`);
        }
        return res.json();
    };

    const getValue = (source, name, fallback = '') => {
        if (source instanceof FormData) {
            const value = source.get(name);
            if (value === null || value === undefined) return fallback;
            const trimmed = String(value).trim();
            return trimmed.length ? trimmed : fallback;
        }
        if (typeof source === 'object' && source !== null) {
            const value = source[name];
            if (value === null || value === undefined) return fallback;
            const trimmed = String(value).trim();
            return trimmed.length ? trimmed : fallback;
        }
        return fallback;
    };

    const requireValue = (value, message) => {
        if (!value) {
            window.alert(message);
            return false;
        }
        return true;
    };

    const setPreviewMode = (mode) => {
        if (!root) return;
        root.classList.remove('preview-desktop', 'preview-tablet', 'preview-mobile');
        root.classList.add(`preview-${mode}`);
        previewButtons.forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.preview === mode);
        });
    };

    const applyAlignment = (element, alignment) => {
        if (!element) return;
        element.style.textAlign = alignment || 'left';
    };

    let outlineSortable = null;
    let outlineEditingBlock = null;
    let outlineEditingType = null;

    const highlightBlock = (block) => {
        if (!block) return;
        block.classList.add('ring', 'ring-sky-300', 'ring-offset-2');
        setTimeout(() => {
            block.classList.remove('ring', 'ring-sky-300', 'ring-offset-2');
        }, 800);
    };

    const getEditorSchema = (type) => {
        switch (type) {
            case 'features':
                return [
                    { name: 'title', label: 'Title', type: 'text', placeholder: 'Features' },
                    { name: 'subtitle', label: 'Subtitle', type: 'textarea', placeholder: 'Short description' },
                    { name: 'illustration', label: 'Illustration URL', type: 'text', placeholder: '/assets/tamplate/images/Fu.svg' },
                    { name: 'bg', label: 'Background', type: 'color', placeholder: '#ffffff' },
                    { name: 'items', label: 'Items', type: 'features-list' },
                ];
            case 'services':
                return [
                    { name: 'badge', label: 'Badge', type: 'text', placeholder: 'Our Services' },
                    { name: 'title', label: 'Title', type: 'text', placeholder: 'What we offer' },
                    { name: 'subtitle', label: 'Subtitle', type: 'textarea', placeholder: 'Short description' },
                    { name: 'bg', label: 'Background', type: 'color', placeholder: '#f8fafc' },
                    { name: 'items', label: 'Items', type: 'services-list' },
                ];
            case 'hero-template':
                return [
                    { name: 'heading', label: 'Heading', type: 'text', placeholder: 'Hero title' },
                    { name: 'subtitle', label: 'Subtitle', type: 'textarea', placeholder: 'Short description' },
                    { name: 'primaryText', label: 'Primary text', type: 'text', placeholder: 'Get started' },
                    { name: 'primaryUrl', label: 'Primary link', type: 'url', placeholder: '#' },
                    { name: 'secondaryText', label: 'Secondary text', type: 'text', placeholder: 'Learn more' },
                    { name: 'secondaryUrl', label: 'Secondary link', type: 'url', placeholder: '#' },
                    { name: 'bg', label: 'Background image', type: 'url', placeholder: 'https://...' },
                ];
            case 'support-hero':
                return [
                    { name: 'heading', label: 'Heading', type: 'text', placeholder: 'Support center' },
                    { name: 'body', label: 'Body', type: 'textarea', placeholder: 'Description' },
                    { name: 'lightImg', label: 'Light image', type: 'url', placeholder: 'https://...' },
                    { name: 'darkImg', label: 'Dark image', type: 'url', placeholder: 'https://...' },
                    { name: 'colorFrom', label: 'Gradient from', type: 'color', placeholder: '#ff4694' },
                    { name: 'colorTo', label: 'Gradient to', type: 'color', placeholder: '#776fff' },
                ];
            default:
                return [];
        }
    };

    const syncStageFromOutline = () => {
        if (!stage || !outlineList) return;
        const orderedIds = Array.from(outlineList.children).map((item) => item.dataset.blockId);
        const blocks = Array.from(stage.children);
        orderedIds.forEach((id) => {
            const match = blocks.find((b) => b.dataset.blockId === id);
            if (match) stage.appendChild(match);
        });
        persistLocal();
        setStatus('Unsaved', 'bg-amber-400');
        refreshOutline();
    };

    const refreshOutline = () => {
        if (!outlineList || !stage) return;
        const blocks = Array.from(stage.children);

        if (outlineSortable) {
            outlineSortable.destroy();
            outlineSortable = null;
        }

        outlineList.innerHTML = '';

        blocks.forEach((block, index) => {
            const id = ensureBlockId(block);
            const item = document.createElement('div');
            item.className = 'builder-outline-item';
            item.dataset.blockId = id;

            const handle = document.createElement('span');
            handle.className = 'builder-outline-handle';
            handle.setAttribute('data-outline-drag', 'true');
            handle.title = 'Drag to reorder';
            handle.textContent = '::';

            const meta = document.createElement('div');
            meta.className = 'builder-outline-meta';

            const title = document.createElement('div');
            title.className = 'builder-outline-title';
            title.textContent = getBlockLabel(block);

            const type = document.createElement('div');
            type.className = 'builder-outline-type';
            type.textContent = `Block ${index + 1}`;

            meta.appendChild(title);
            meta.appendChild(type);

            item.appendChild(handle);
            item.appendChild(meta);

            const actions = document.createElement('div');
            actions.className = 'builder-outline-actions';

            const editAction = document.createElement('button');
            editAction.type = 'button';
            editAction.className = 'builder-outline-btn';
            editAction.textContent = 'Edit';

            const delAction = document.createElement('button');
            delAction.type = 'button';
            delAction.className = 'builder-outline-btn';
            delAction.textContent = 'Delete';

            actions.appendChild(editAction);
            actions.appendChild(delAction);
            item.appendChild(actions);

            item.addEventListener('click', (e) => {
                if (e.target.closest('[data-outline-drag]')) return;
                if (e.target === editAction || e.target === delAction) return;
                block.scrollIntoView({ behavior: 'smooth', block: 'center' });
                highlightBlock(block);
            });

            editAction.addEventListener('click', (e) => {
                e.stopPropagation();
                const type = block.dataset.type || 'unknown';
                if (['features', 'hero-template', 'support-hero'].includes(type)) {
                    openOutlineEditor(block, type);
                } else {
                    editBlock(block, type);
                    refreshOutline();
                }
            });

            delAction.addEventListener('click', (e) => {
                e.stopPropagation();
                block.remove();
                updateEmptyState();
                persistLocal();
                refreshOutline();
                if (outlineEditingBlock === block) {
                    closeOutlineEditor();
                }
                setStatus('Unsaved', 'bg-amber-400');
            });

            outlineList.appendChild(item);
        });

        if (outlineEmpty) {
            outlineEmpty.classList.toggle('hidden', blocks.length > 0);
        }

        if (blocks.length) {
            outlineSortable = Sortable.create(outlineList, {
                animation: 150,
                handle: '[data-outline-drag]',
                ghostClass: 'opacity-50',
                onEnd: () => {
                    syncStageFromOutline();
                },
            });
        }

        if (!outlineEditingBlock) {
            if (outlineList) outlineList.parentElement?.classList.remove('hidden');
            if (outlineEditor) outlineEditor.classList.add('hidden');
        }
    };

    const moveBlock = (block, direction) => {
        if (!stage || !block) return;
        if (direction === 'up') {
            const prev = block.previousElementSibling;
            if (prev) stage.insertBefore(block, prev);
        } else if (direction === 'down') {
            const next = block.nextElementSibling;
            if (next) stage.insertBefore(next, block);
        }
        persistLocal();
        refreshOutline();
        setStatus('Unsaved', 'bg-amber-400');
    };

    const closeOutlineEditor = () => {
        outlineEditingBlock = null;
        outlineEditingType = null;
        outlineEditorSchema = [];
        if (outlineEditor) outlineEditor.classList.add('hidden');
        if (outlinePanel) outlinePanel.classList.remove('hidden');
        if (outlineEditorFieldsWrap) outlineEditorFieldsWrap.innerHTML = '';
    };

    const renderOutlineEditorFields = (schema, block) => {
        if (!outlineEditorFieldsWrap) return;
        outlineEditorFieldsWrap.innerHTML = '';
        schema.forEach((field) => {
            const wrap = document.createElement('div');
            wrap.className = 'field';
            const label = document.createElement('label');
            label.textContent = field.label;
            wrap.appendChild(label);

            if (field.type === 'features-list') {
                const listWrap = document.createElement('div');
                listWrap.className = 'space-y-3';
                listWrap.dataset.featureList = field.name;

                const items = parseFeaturesItems(block?.dataset?.features);

                const addItemRow = (item = {}) => {
                    const row = document.createElement('div');
                    row.className = 'rounded-lg border border-slate-200 p-3 bg-white space-y-2';
                    row.dataset.featureRow = 'true';

                    const titleInput = document.createElement('input');
                    titleInput.type = 'text';
                    titleInput.placeholder = 'Title';
                    titleInput.value = item.title || '';
                    titleInput.dataset.field = 'title';
                    titleInput.className = 'w-full border border-slate-200 rounded px-2 py-1';

                    const descInput = document.createElement('textarea');
                    descInput.placeholder = 'Description';
                    descInput.value = item.description || '';
                    descInput.dataset.field = 'description';
                    descInput.className = 'w-full border border-slate-200 rounded px-2 py-1';

                    const iconInput = document.createElement('textarea');
                    iconInput.placeholder = 'Icon (SVG or text)';
                    iconInput.value = item.icon || '';
                    iconInput.dataset.field = 'icon';
                    iconInput.className = 'w-full border border-slate-200 rounded px-2 py-1';

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.textContent = 'Remove';
                    removeBtn.className = 'text-sm text-red-600 border border-red-200 rounded px-2 py-1';
                    removeBtn.addEventListener('click', () => row.remove());

                    row.appendChild(titleInput);
                    row.appendChild(descInput);
                    row.appendChild(iconInput);
                    row.appendChild(removeBtn);
                    listWrap.appendChild(row);
                };

                items.forEach((item) => addItemRow(item));

                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.textContent = 'Add item';
                addBtn.className = 'text-sm text-slate-700 border border-slate-200 rounded px-3 py-1';
                addBtn.addEventListener('click', () => addItemRow({ title: '', description: '', icon: '' }));

                wrap.appendChild(listWrap);
                wrap.appendChild(addBtn);
            } else if (field.type === 'services-list') {
                const listWrap = document.createElement('div');
                listWrap.className = 'space-y-3';
                listWrap.dataset.servicesList = field.name;

                const items = parseServicesItems(block?.dataset?.services);

                const addItemRow = (item = {}) => {
                    const row = document.createElement('div');
                    row.className = 'rounded-lg border border-slate-200 p-3 bg-white space-y-2';
                    row.dataset.serviceRow = 'true';

                    const titleInput = document.createElement('input');
                    titleInput.type = 'text';
                    titleInput.placeholder = 'Title';
                    titleInput.value = item.title || '';
                    titleInput.dataset.field = 'title';
                    titleInput.className = 'w-full border border-slate-200 rounded px-2 py-1';

                    const descInput = document.createElement('textarea');
                    descInput.placeholder = 'Description';
                    descInput.value = item.description || '';
                    descInput.dataset.field = 'description';
                    descInput.className = 'w-full border border-slate-200 rounded px-2 py-1';

                    const iconInput = document.createElement('textarea');
                    iconInput.placeholder = 'Icon URL (or SVG/text)';
                    iconInput.value = item.icon || '';
                    iconInput.dataset.field = 'icon';
                    iconInput.className = 'w-full border border-slate-200 rounded px-2 py-1';

                    const urlInput = document.createElement('input');
                    urlInput.type = 'text';
                    urlInput.placeholder = 'Link URL';
                    urlInput.value = item.url || '';
                    urlInput.dataset.field = 'url';
                    urlInput.className = 'w-full border border-slate-200 rounded px-2 py-1';

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.textContent = 'Remove';
                    removeBtn.className = 'text-sm text-red-600 border border-red-200 rounded px-2 py-1';
                    removeBtn.addEventListener('click', () => row.remove());

                    row.appendChild(titleInput);
                    row.appendChild(descInput);
                    row.appendChild(iconInput);
                    row.appendChild(urlInput);
                    row.appendChild(removeBtn);
                    listWrap.appendChild(row);
                };

                items.forEach((item) => addItemRow(item));

                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.textContent = 'Add service';
                addBtn.className = 'text-sm text-slate-700 border border-slate-200 rounded px-3 py-1';
                addBtn.addEventListener('click', () => addItemRow({ title: '', description: '', icon: '', url: '' }));

                wrap.appendChild(listWrap);
                wrap.appendChild(addBtn);
            } else {
                let input;
                if (field.type === 'textarea') {
                    input = document.createElement('textarea');
                } else if (field.type === 'select') {
                    input = document.createElement('select');
                    (field.options || []).forEach((opt) => {
                        const option = document.createElement('option');
                        option.value = opt.value;
                        option.textContent = opt.label;
                        input.appendChild(option);
                    });
                } else {
                    input = document.createElement('input');
                    input.type = field.type || 'text';
                }
                input.dataset.editorInput = field.name;
                input.placeholder = field.placeholder || '';
                const value = block?.dataset?.[field.name] ?? '';
                input.value = value;
                wrap.appendChild(input);
            }
            outlineEditorFieldsWrap.appendChild(wrap);
        });
    };

    const openOutlineEditor = (block, type) => {
        if (!outlineEditor) return;
        outlineEditingBlock = block;
        outlineEditingType = type;
        outlineEditorSchema = getEditorSchema(type);
        setAsideTab('outline');
        if (outlinePanel) outlinePanel.classList.add('hidden');
        outlineEditor.classList.remove('hidden');
        if (outlineEditorTitle) {
            const label = blockTypeLabels[type] || 'Block';
            outlineEditorTitle.textContent = `Edit ${label}`;
        }
        if (type === 'features') {
            const items = parseFeaturesItems(block.dataset.features);
            block.dataset.items = items
                .map((item) => `${item.title || ''}|${item.description || ''}|${item.icon || ''}`)
                .join('\n');
        } else if (type === 'services') {
            const items = parseServicesItems(block.dataset.services);
            block.dataset.items = JSON.stringify(items);
        }
        renderOutlineEditorFields(outlineEditorSchema, block);
    };

    const applyHeroTemplateValues = (block, values) => {
        block.dataset.heading = values.heading;
        block.dataset.subtitle = values.subtitle;
        block.dataset.primaryText = values.primaryText;
        block.dataset.primaryUrl = values.primaryUrl;
        block.dataset.secondaryText = values.secondaryText;
        block.dataset.secondaryUrl = values.secondaryUrl;
        block.dataset.bg = values.bg;
        const h2 = block.querySelector('h2');
        const p = block.querySelector('p');
        const bgImg = block.querySelector('[data-hero-bg]');
        const primary = block.querySelector('[data-hero-primary]');
        const secondary = block.querySelector('[data-hero-secondary]');
        if (h2) h2.textContent = block.dataset.heading || '';
        if (p) p.textContent = block.dataset.subtitle || '';
        if (bgImg && block.dataset.bg) bgImg.src = block.dataset.bg;
        if (primary) {
            primary.textContent = block.dataset.primaryText || '';
            primary.href = block.dataset.primaryUrl || '#';
        }
        if (secondary) {
            secondary.textContent = block.dataset.secondaryText || '';
            secondary.href = block.dataset.secondaryUrl || '#';
        }
    };

    const applySupportHeroValues = (block, values) => {
        block.dataset.heading = values.heading;
        block.dataset.body = values.body;
        block.dataset.lightImg = values.lightImg;
        block.dataset.darkImg = values.darkImg;
        block.dataset.colorFrom = values.colorFrom;
        block.dataset.colorTo = values.colorTo;
        const h2 = block.querySelector('h2');
        const p = block.querySelector('p');
        if (h2) h2.textContent = block.dataset.heading || '';
        if (p) p.textContent = block.dataset.body || '';
        const lightEl = block.querySelector('[data-support-light]');
        const darkEl = block.querySelector('[data-support-dark]');
        if (lightEl) lightEl.src = block.dataset.lightImg || '';
        if (darkEl) darkEl.src = block.dataset.darkImg || '';
        const blobs = block.querySelectorAll('[data-support-blob]');
        blobs.forEach((blob) => {
            blob.style.backgroundImage = `linear-gradient(45deg, ${block.dataset.colorFrom || '#ff4694'}, ${block.dataset.colorTo || '#776fff'})`;
        });
    };

    const setAsideTab = (tab) => {
        tabButtons.forEach((btn) => {
            const isActive = btn.dataset.tabTarget === tab;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        tabContents.forEach((content) => {
            content.classList.toggle('active', content.dataset.tabContent === tab);
        });
        tabHelpers.forEach((helper) => {
            helper.classList.toggle('hidden', helper.dataset.tabHelper !== tab);
        });
        if (tab === 'outline') {
            refreshOutline();
        }
    };

    const openFormModal = (title, fields, onSave) => {
        const backdrop = document.createElement('div');
        backdrop.className = 'builder-modal-backdrop';

        const modal = document.createElement('div');
        modal.className = 'builder-modal';

        const heading = document.createElement('h3');
        heading.textContent = title;
        modal.appendChild(heading);

        const form = document.createElement('div');
        fields.forEach((field) => {
            const wrap = document.createElement('div');
            wrap.className = 'field';
            const label = document.createElement('label');
            label.textContent = field.label;
            label.htmlFor = `fld-${field.name}`;
            wrap.appendChild(label);
            let input;
            if (field.type === 'textarea') {
                input = document.createElement('textarea');
                input.value = field.value || '';
            } else {
                input = document.createElement('input');
                input.type = field.type || 'text';
                input.value = field.value || '';
            }
            input.id = `fld-${field.name}`;
            input.name = field.name;
            input.placeholder = field.placeholder || '';
            wrap.appendChild(input);
            form.appendChild(wrap);
        });
        modal.appendChild(form);

        const actions = document.createElement('div');
        actions.className = 'actions';
        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'btn-secondary';
        cancelBtn.textContent = 'إلغاء';
        const saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.className = 'btn-primary';
        saveBtn.textContent = 'حفظ';
        actions.appendChild(cancelBtn);
        actions.appendChild(saveBtn);
        modal.appendChild(actions);

        backdrop.appendChild(modal);
        document.body.appendChild(backdrop);

        const close = () => backdrop.remove();

        cancelBtn.addEventListener('click', close);
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) close();
        });
        saveBtn.addEventListener('click', () => {
            const result = {};
            fields.forEach((field) => {
                const el = modal.querySelector(`#fld-${field.name}`);
                result[field.name] = el ? el.value : '';
            });
            onSave(result);
            close();
        });
    };

    const attachBlockControls = (block) => {
        if (!block) return;
        ensureBlockId(block);
        const type = block.dataset.type || block.dataset.blockType || 'unknown';
        const existing = block.querySelector('.block-actions');
        if (existing) existing.remove();

        const actions = document.createElement('div');
        actions.className = 'block-actions';

        const upBtn = document.createElement('button');
        upBtn.type = 'button';
        upBtn.className = 'muted';
        upBtn.textContent = 'Up';
        upBtn.title = 'Move up';

        const downBtn = document.createElement('button');
        downBtn.type = 'button';
        downBtn.className = 'muted';
        downBtn.textContent = 'Down';
        downBtn.title = 'Move down';

        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.textContent = 'Edit';
        editBtn.title = 'Edit';

        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.textContent = 'Delete';
        delBtn.title = 'Delete';

        actions.appendChild(upBtn);
        actions.appendChild(downBtn);
        actions.appendChild(editBtn);
        actions.appendChild(delBtn);
        block.appendChild(actions);

        upBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            moveBlock(block, 'up');
        });

        downBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            moveBlock(block, 'down');
        });

        editBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            editBlock(block, type);
            refreshOutline();
        });

        delBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            block.remove();
            updateEmptyState();
            persistLocal();
            refreshOutline();
            if (outlineEditingBlock === block) {
                closeOutlineEditor();
            }
            setStatus('Unsaved', 'bg-amber-400');
        });
    };

    const parseFeaturesItems = (raw) => {
        if (raw && Array.isArray(raw)) return raw.length ? raw : defaultFeaturesItems;
        if (typeof raw === 'string') {
            try {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) return parsed.length ? parsed : defaultFeaturesItems;
            } catch {
                return defaultFeaturesItems;
            }
        }
        return defaultFeaturesItems;
    };

    const parseFeaturesTextarea = (raw) => {
        if (!raw) return defaultFeaturesItems;
        return raw
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean)
            .map((line, idx) => {
                const [title, description, icon] = line.split('|');
                return {
                    title: (title || '').trim() || `Feature ${idx + 1}`,
                    description: (description || '').trim(),
                    icon: (icon || '').trim(),
                };
            });
    };

    const parseServicesItems = (raw) => {
        if (raw && Array.isArray(raw)) return raw.length ? raw : defaultServicesItems;
        if (typeof raw === 'string') {
            try {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) return parsed.length ? parsed : defaultServicesItems;
            } catch {
                return defaultServicesItems;
            }
        }
        return defaultServicesItems;
    };

    const renderFeaturesBlock = (block) => {
        if (!block) return;
        const title = block.dataset.title || 'Features';
        const subtitle = block.dataset.subtitle || '';
        const illustration = block.dataset.illustration || '/assets/tamplate/images/Fu.svg';
        const bg = block.dataset.bg || '';
        const items = parseFeaturesItems(block.dataset.features);

        block.className = 'builder-block';
        block.style.padding = '0';
        block.style.background = 'transparent';
        block.style.border = 'none';
        block.style.boxShadow = 'none';
        block.innerHTML = '';

        const container = document.createElement('section');
        container.className = 'w-full bg-gradient-to-br from-white via-slate-50 to-slate-100 border border-slate-200 rounded-2xl p-8 sm:p-10 lg:p-12 shadow-sm';
        container.setAttribute('dir', 'auto');
        if (bg) {
            container.style.background = bg;
            container.style.backgroundImage = 'none';
        }

        const header = document.createElement('div');
        header.className = 'text-center mb-10';

        const h2 = document.createElement('h2');
        h2.className = 'text-3xl sm:text-4xl font-extrabold text-primary mb-3 tracking-tight';
        h2.textContent = title;
        header.appendChild(h2);

        if (subtitle) {
            const subEl = document.createElement('p');
            subEl.className = 'text-slate-600 text-base sm:text-lg max-w-3xl mx-auto';
            subEl.textContent = subtitle;
            header.appendChild(subEl);
        }

        container.appendChild(header);

        const grid = document.createElement('div');
        grid.className = 'grid gap-12 lg:gap-10 lg:grid-cols-5 items-center';

        const illustrationWrap = document.createElement('div');
        illustrationWrap.className = 'lg:col-span-2 flex justify-center';
        const illustrationImg = document.createElement('img');
        illustrationImg.src = illustration;
        illustrationImg.alt = '';
        illustrationImg.className = 'max-w-[280px] sm:max-w-sm lg:max-w-[480px] w-full h-auto object-contain drop-shadow';
        illustrationImg.loading = 'lazy';
        illustrationWrap.appendChild(illustrationImg);
        grid.appendChild(illustrationWrap);

        const list = document.createElement('div');
        list.className = 'lg:col-span-3 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-y-10 gap-x-6 text-center sm:text-start';

        items.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'flex flex-col items-center sm:items-start gap-3';

            const iconWrap = document.createElement('div');
            iconWrap.className = 'w-12 h-12 flex items-center justify-center rounded-lg bg-primary/10 text-primary font-semibold shrink-0';
            if (item.icon && typeof item.icon === 'string' && item.icon.includes('<')) {
                iconWrap.innerHTML = item.icon;
            } else {
                const fallback = (item.icon || item.title || `${index + 1}`).toString().trim().charAt(0) || '•';
                iconWrap.textContent = fallback;
            }
            card.appendChild(iconWrap);

            const t = document.createElement('div');
            t.className = 'text-lg font-semibold text-slate-900';
            t.textContent = item.title || `Feature ${index + 1}`;
            card.appendChild(t);

            const d = document.createElement('p');
            d.className = 'text-sm text-slate-600 leading-relaxed';
            d.textContent = item.description || '';
            card.appendChild(d);

            list.appendChild(card);
        });
        grid.appendChild(list);

        container.appendChild(grid);
        block.appendChild(container);
        attachBlockControls(block);
    };

    
    const renderServicesBlock = (block) => {
        if (!block) return;
        const badge = block.dataset.badge || 'Our Services';
        const title = block.dataset.title || 'What we offer';
        const subtitle = block.dataset.subtitle || '';
        const bg = block.dataset.bg || '';
        const items = parseServicesItems(block.dataset.services);

        const cardHtml = (item, idx) => {
            const iconContent = (() => {
                if (item.icon && item.icon.includes('<')) return item.icon;
                if (item.icon) {
                    return `<img src="${item.icon}" alt="${item.title || ''}" class="w-10 h-10 object-contain" loading="lazy">`;
                }
                const fallback = (item.title || `S${idx + 1}`).trim().charAt(0) || '•';
                return `<span>${fallback}</span>`;
            })();

            const footer = item.url
                ? `<span>Learn more</span><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>`
                : `<span class="text-slate-500">No link provided</span>`;

            const wrapperTag = item.url ? 'a' : 'div';
            const hrefAttr = item.url ? `href="${item.url}"` : '';

            return `
                <${wrapperTag} ${hrefAttr} class="group bg-white rounded-3xl shadow-xl p-8 flex flex-col items-center text-center border border-primary/10 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 h-full">
                    <div class="bg-primary/10 group-hover:bg-primary/20 rounded-full p-4 mb-5 transition">
                        ${iconContent}
                    </div>
                    <h3 class="font-bold text-lg text-primary mb-2 group-hover:text-secondary transition">${item.title || `Service ${idx + 1}`}</h3>
                    <p class="text-tertiary text-sm mb-4">${item.description || ''}</p>
                    <div class="relative mt-auto flex items-center gap-2 text-sm font-semibold text-primary/80 group-hover:text-primary">
                        ${footer}
                    </div>
                </${wrapperTag}>
            `;
        };

        const cardsHtml = items.map(cardHtml).join('');

        block.className = 'builder-block builder-block-fluid';
        block.style.padding = '0';
        block.style.background = 'transparent';
        block.style.border = 'none';
        block.style.boxShadow = 'none';
        block.innerHTML = `
            <section class="relative py-20 px-4 sm:px-8 lg:px-24 bg-white rounded-2xl shadow-sm overflow-hidden" dir="auto">
                <div class="absolute -top-20 -left-20 w-52 h-52 rounded-full bg-primary/5 blur-3xl"></div>
                <div class="absolute -bottom-24 -right-16 w-60 h-60 rounded-full bg-secondary/10 blur-3xl"></div>
                <div class="relative max-w-7xl mx-auto space-y-10">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-primary mb-4 tracking-tight">
                            ${title}
                        </h2>
                        <p class="text-tertiary text-base sm:text-lg max-w-2xl mx-auto">
                            ${subtitle || ''}
                        </p>
                    </div>
                    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        ${cardsHtml}
                    </div>
                </div>
            </section>
        `;
        const container = block.querySelector('section');
        if (bg && container) {
            container.style.background = bg;
            container.style.backgroundImage = 'none';
        }
        attachBlockControls(block);
    };

const createFeaturesBlock = (data) => {
        const title = getValue(data, 'features_title', 'Features');
        const subtitle = getValue(data, 'features_subtitle', '');
        const items = parseFeaturesItems(data.features_items);

        const wrapper = document.createElement('section');
        wrapper.dataset.type = 'features';
        wrapper.dataset.title = title;
        wrapper.dataset.subtitle = subtitle;
        wrapper.dataset.features = JSON.stringify(items);
        wrapper.dataset.illustration = data.features_illustration || '/assets/tamplate/images/Fu.svg';
        wrapper.dataset.bg = data.features_bg || '';

        renderFeaturesBlock(wrapper);
        return wrapper;
    };

    const createServicesBlock = (data) => {
        const badge = getValue(data, 'services_badge', 'Our Services');
        const title = getValue(data, 'services_title', 'What we offer');
        const subtitle = getValue(data, 'services_subtitle', '');
        const bg = getValue(data, 'services_bg', '');
        const items = parseServicesItems(data.services_items);

        const wrapper = document.createElement('section');
        wrapper.dataset.type = 'services';
        wrapper.dataset.badge = badge;
        wrapper.dataset.title = title;
        wrapper.dataset.subtitle = subtitle;
        wrapper.dataset.bg = bg;
        wrapper.dataset.services = JSON.stringify(items);

        renderServicesBlock(wrapper);
        return wrapper;
    };

    const createSectionBlock = (data) => {
        const title = getValue(data, 'section_title', 'Section title');
        const body = getValue(data, 'section_body', 'Describe this section.');
        const bgColor = getValue(data, 'section_bg', '#ffffff');
        const paddingValue = parseInt(getValue(data, 'section_padding', '24'), 10);
        const alignment = getValue(data, 'section_align', 'left');

        const section = document.createElement('section');
        section.className = 'builder-block';
        section.dataset.type = 'section';
        section.dataset.title = title;
        section.dataset.body = body;
        section.dataset.bg = bgColor;
        section.dataset.padding = paddingValue;
        section.dataset.align = alignment;
        section.style.backgroundColor = bgColor;
        section.style.padding = Number.isFinite(paddingValue) ? `${paddingValue}px` : '24px';
        applyAlignment(section, alignment);

        const heading = document.createElement('h3');
        heading.textContent = title;
        section.appendChild(heading);

        const paragraph = document.createElement('p');
        paragraph.textContent = body;
        section.appendChild(paragraph);

        attachBlockControls(section);
        return section;
    };

    const createHeroTemplateBlock = () => {
        const wrapper = document.createElement('section');
        wrapper.className = 'builder-block builder-block-fluid';
        wrapper.dataset.type = 'hero-template';
        wrapper.dataset.heading = 'Hero title';
        wrapper.dataset.subtitle = 'Short description goes here.';
        wrapper.dataset.primaryText = 'Get started';
        wrapper.dataset.primaryUrl = '#';
        wrapper.dataset.secondaryText = 'Learn more';
        wrapper.dataset.secondaryUrl = '#';
        wrapper.dataset.bg = 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1600&q=80';

        wrapper.innerHTML = `
<div class="relative bg-gradient-to-tr from-primary to-primary shadow-2xl overflow-hidden rounded-2xl">
  <img data-hero-bg alt="" class="absolute inset-0 z-0 opacity-80 w-full h-full object-cover object-center ltr:scale-x-[-1] rtl:scale-x-100 transition-transform duration-500 ease-in-out" aria-hidden="true" decoding="async" loading="eager" />
  <div class="relative z-10 px-4 sm:px-8 lg:px-12 py-14 sm:py-16 lg:py-20 flex flex-col-reverse lg:flex-row items-center justify-between gap-10 min-h-[360px]">
    <div class="max-w-xl rtl:text-right ltr:text-left text-center lg:text-start space-y-6">
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight drop-shadow-lg"></h2>
      <p class="text-white/90 text-base sm:text-lg font-light"></p>
      <div class="flex flex-row flex-wrap gap-3 justify-center lg:justify-start">
        <a data-hero-primary href="#" class="bg-secondary hover:bg-primary text-white font-bold px-6 py-3 rounded-lg shadow transition text-sm sm:text-base"></a>
        <a data-hero-secondary href="#" class="bg-white/10 text-white font-bold px-6 py-3 rounded-lg shadow transition hover:bg-white/20 text-sm sm:text-base border border-white/30"></a>
      </div>
    </div>
  </div>
  <div class="absolute -bottom-20 -left-20 w-72 h-72 bg-white/10 rounded-full blur-3xl z-0"></div>
</div>
        `;

        const h = wrapper.querySelector('h2');
        const p = wrapper.querySelector('p');
        const bg = wrapper.querySelector('[data-hero-bg]');
        const primary = wrapper.querySelector('[data-hero-primary]');
        const secondary = wrapper.querySelector('[data-hero-secondary]');
        if (h) h.textContent = wrapper.dataset.heading;
        if (p) p.textContent = wrapper.dataset.subtitle;
        if (bg) bg.src = wrapper.dataset.bg;
        if (primary) {
            primary.textContent = wrapper.dataset.primaryText;
            primary.href = wrapper.dataset.primaryUrl;
        }
        if (secondary) {
            secondary.textContent = wrapper.dataset.secondaryText;
            secondary.href = wrapper.dataset.secondaryUrl;
        }

        attachBlockControls(wrapper);
        return wrapper;
    };

    const createSupportHeroBlock = () => {
        const wrapper = document.createElement('section');
        wrapper.className = 'builder-block builder-block-fluid';
        wrapper.dataset.type = 'support-hero';
        wrapper.dataset.heading = 'Support center';
        wrapper.dataset.body = 'Anim aute id magna aliqua ad ad non deserunt sunt. Qui irure qui lorem cupidatat commodo. Elit sunt amet fugiat veniam occaecat fugiat.';
        wrapper.dataset.lightImg = 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&crop=focalpoint&fp-y=.8&w=1600&q=80&sat=-100&exp=15&blend-mode=screen';
        wrapper.dataset.darkImg = 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&crop=focalpoint&fp-y=.8&w=1600&q=80&sat=-100&exp=15&blend-mode=multiply';
        wrapper.dataset.colorFrom = '#ff4694';
        wrapper.dataset.colorTo = '#776fff';
        wrapper.innerHTML = `
<div class="relative isolate overflow-hidden bg-white py-24 sm:py-32 dark:bg-gray-900 rounded-2xl">
  <img data-support-light alt="" class="absolute inset-0 -z-10 w-full h-full object-cover opacity-10 dark:hidden" />
  <img data-support-dark alt="" class="absolute inset-0 -z-10 w-full h-full object-cover hidden dark:block" />
  <div aria-hidden="true" class="hidden sm:absolute sm:-top-10 sm:right-1/2 sm:-z-10 sm:mr-10 sm:block sm:transform-gpu sm:blur-3xl">
    <div data-support-blob style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)" class="aspect-[1097/845] w-[274.25px] opacity-15 dark:opacity-20"></div>
  </div>
  <div aria-hidden="true" class="absolute -top-52 left-1/2 -z-10 -translate-x-1/2 transform-gpu blur-3xl sm:-top-28 sm:ml-16 sm:translate-x-0">
    <div data-support-blob style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)" class="aspect-[1097/845] w-[274.25px] opacity-15 dark:opacity-20"></div>
  </div>
  <div class="mx-auto max-w-7xl px-6 lg:px-8">
    <div class="mx-auto max-w-2xl lg:mx-0">
      <h2 class="text-5xl font-semibold tracking-tight text-gray-900 sm:text-7xl dark:text-white"></h2>
      <p class="mt-8 text-lg font-medium text-gray-700 sm:text-xl dark:text-gray-400"></p>
    </div>
  </div>
</div>
        `;
        const h2 = wrapper.querySelector('h2');
        const p = wrapper.querySelector('p');
        if (h2) h2.textContent = wrapper.dataset.heading;
        if (p) p.textContent = wrapper.dataset.body;
        const lightEl = wrapper.querySelector('[data-support-light]');
        const darkEl = wrapper.querySelector('[data-support-dark]');
        const blobs = wrapper.querySelectorAll('[data-support-blob]');
        if (lightEl) lightEl.src = wrapper.dataset.lightImg;
        if (darkEl) darkEl.src = wrapper.dataset.darkImg;
        blobs.forEach((blob) => {
            blob.style.backgroundImage = `linear-gradient(45deg, ${wrapper.dataset.colorFrom}, ${wrapper.dataset.colorTo})`;
        });
        attachBlockControls(wrapper);
        return wrapper;
    };

    const buildBlock = (type, payload = {}) => {
        switch (type) {
            case 'features':
                return createFeaturesBlock({
                    features_title: payload.title || 'Features',
                    features_subtitle: payload.subtitle || '',
                    features_bg: payload.bg || '',
                    features_items: payload.features || [],
                });
            case 'services':
                return createServicesBlock({
                    services_badge: payload.badge || 'Our Services',
                    services_title: payload.title || 'What we offer',
                    services_subtitle: payload.subtitle || '',
                    services_bg: payload.bg || '',
                    services_items: payload.services || [],
                });
            case 'support-hero':
                return createSupportHeroBlock();
            case 'hero-template':
                return createHeroTemplateBlock();
            default:
                return null;
        }
    };

    const handleDrop = (type) => {
        const block = buildBlock(type, {});
        if (!block || !stage) return;
        stage.appendChild(block);
        updateEmptyState();
        refreshOutline();
        persistLocal();
        setStatus('Unsaved', 'bg-amber-400');
    };

    // Drag from block palette
    blockButtons.forEach((button) => {
        const type = button.dataset.block;
        if (!type) return;

        // Drag to canvas
        button.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('text/plain', type);
            e.dataTransfer.setData('application/builder-block', type);
        });

        // Click to add (fallback if drag not working)
        button.addEventListener('click', () => {
            const block = buildBlock(type, {});
            if (!block || !stage) return;
            stage.appendChild(block);
            updateEmptyState();
            refreshOutline();
            persistLocal();
            setStatus('Unsaved', 'bg-amber-400');
        });
    });

    // Drop on stage
    if (stage) {
        stage.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });

        stage.addEventListener('drop', (e) => {
            e.preventDefault();
            const type = e.dataTransfer.getData('application/builder-block') || e.dataTransfer.getData('text/plain');
            if (!type) return;
            handleDrop(type);
        });
    }

    // Allow dropping directly on the empty state placeholder
    if (emptyState) {
        emptyState.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });
        emptyState.addEventListener('drop', (e) => {
            e.preventDefault();
            const type = e.dataTransfer.getData('application/builder-block') || e.dataTransfer.getData('text/plain');
            if (!type) return;
            handleDrop(type);
        });
    }

    // SortableJS for reordering
    if (stage) {
        Sortable.create(stage, {
            animation: 150,
            handle: '.builder-block',
            ghostClass: 'opacity-50',
            onEnd: () => {
                updateEmptyState();
                refreshOutline();
                persistLocal();
                setStatus('Unsaved', 'bg-amber-400');
            },
        });
    }

    const editBlock = (block, type) => {
        let handledWithModal = false;
        switch (type) {
            case 'features': {
                openOutlineEditor(block, 'features');
                handledWithModal = true;
                break;
            }
            case 'services': {
                openOutlineEditor(block, 'services');
                handledWithModal = true;
                break;
            }
            case 'hero-template': {
                openOutlineEditor(block, 'hero-template');
                handledWithModal = true;
                break;
            }
            case 'support-hero': {
                openOutlineEditor(block, 'support-hero');
                handledWithModal = true;
                break;
            }
            default:
                break;
        }
        if (!handledWithModal) {
            persistLocal();
            setStatus('Unsaved', 'bg-amber-400');
            refreshOutline();
        }
    };

    const rehydrateBlocks = () => {
        if (!stage) return;
        Array.from(stage.children).forEach((block) => {
            const type = block.dataset.type || block.dataset.blockType;
            if (type === 'features') {
                // Re-render to match the latest template layout.
                renderFeaturesBlock(block);
            } else if (type === 'services') {
                renderServicesBlock(block);
            } else {
                attachBlockControls(block);
            }
        });
        updateEmptyState();
        refreshOutline();
    };

    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            persistLocal();
            try {
                await saveRemote();
                setStatus('Saved', 'bg-emerald-400');
            } catch (err) {
                console.error(err);
                setStatus('Error saving', 'bg-red-400');
            }
        });
    }

    // Preview toggles (desktop/tablet/mobile)
    if (previewButtons.length && root) {
        previewButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const mode = btn.dataset.preview || 'desktop';
                setPreviewMode(mode);
                if (previewLabel) previewLabel.textContent = btn.textContent.trim();
                previewButtons.forEach((b) => b.classList.remove('active'));
                btn.classList.add('active');
                if (previewMenu) previewMenu.classList.remove('open');
            });
        });
        setPreviewMode('desktop');
    }

    // Preview dropdown toggle
    if (previewToggleBtn && previewMenu) {
        previewToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            previewMenu.classList.toggle('open');
        });
        document.addEventListener('click', (e) => {
            if (!previewMenu.contains(e.target) && e.target !== previewToggleBtn) {
                previewMenu.classList.remove('open');
            }
        });
    }

    // Aside tabs (Blocks / Outline)
    if (tabButtons.length) {
        tabButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tabTarget || 'palette';
                setAsideTab(tab);
            });
        });
        setAsideTab('outline');
    }

    // Language dropdown toggle (builder)
    const langWrapper = document.querySelector('.builder-lang');
    if (langWrapper) {
        const toggle = langWrapper.querySelector('.pc-head-link');
        const menu = langWrapper.querySelector('.dropdown-menu');

        if (toggle && menu) {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                langWrapper.classList.toggle('open');
                menu.style.direction = document.documentElement.getAttribute('dir') === 'rtl' ? 'rtl' : 'ltr';
                menu.style.flexWrap = 'nowrap';
            });

            document.addEventListener('click', (e) => {
                if (!langWrapper.contains(e.target)) {
                    langWrapper.classList.remove('open');
                }
            });
        }
    }

    const restoreLocal = () => {
        if (!storageKey || !stage) return;
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            stage.innerHTML = saved;
        }
        purgeUnsupportedBlocks();
        rehydrateBlocks();
    };

    restoreLocal();

    if (outlineEditorBack.length) {
        outlineEditorBack.forEach((btn) => {
            btn.addEventListener('click', () => {
                closeOutlineEditor();
            });
        });
    }

    const collectOutlineValues = () => {
        const values = {};
        if (!outlineEditorFieldsWrap) return values;
        const inputs = outlineEditorFieldsWrap.querySelectorAll('[data-editor-input]');
        inputs.forEach((field) => {
            const key = field.dataset.editorInput;
            if (!key) return;
            values[key] = field.value || '';
        });
        const featureList = outlineEditorFieldsWrap.querySelector('[data-feature-list]');
        if (featureList) {
            const rows = featureList.querySelectorAll('[data-feature-row]');
            values.items = Array.from(rows).map((row) => {
                const title = row.querySelector('[data-field="title"]')?.value?.trim() || '';
                const description = row.querySelector('[data-field="description"]')?.value?.trim() || '';
                const icon = row.querySelector('[data-field="icon"]')?.value || '';
                return { title, description, icon };
            }).filter((item) => item.title || item.description || item.icon);
        }
        const servicesList = outlineEditorFieldsWrap.querySelector('[data-services-list]');
        if (servicesList) {
            const rows = servicesList.querySelectorAll('[data-service-row]');
            values.items = Array.from(rows).map((row) => {
                const title = row.querySelector('[data-field="title"]')?.value?.trim() || '';
                const description = row.querySelector('[data-field="description"]')?.value?.trim() || '';
                const icon = row.querySelector('[data-field="icon"]')?.value || '';
                const url = row.querySelector('[data-field="url"]')?.value?.trim() || '';
                return { title, description, icon, url };
            }).filter((item) => item.title || item.description || item.icon || item.url);
        }
        return values;
    };

    if (outlineEditorSave) {
        outlineEditorSave.addEventListener('click', () => {
            if (!outlineEditingBlock || !outlineEditingType) return;
            const values = collectOutlineValues();
            if (outlineEditingType === 'features') {
                const items = Array.isArray(values.items) ? values.items : parseFeaturesTextarea(values.items || '');
                outlineEditingBlock.dataset.title = values.title || 'Features';
                outlineEditingBlock.dataset.subtitle = values.subtitle || '';
                outlineEditingBlock.dataset.features = JSON.stringify(items);
                outlineEditingBlock.dataset.illustration = values.illustration || '/assets/tamplate/images/Fu.svg';
                outlineEditingBlock.dataset.bg = values.bg || '';
                renderFeaturesBlock(outlineEditingBlock);
            } else if (outlineEditingType === 'hero-template') {
                applyHeroTemplateValues(outlineEditingBlock, {
                    heading: values.heading || 'Hero title',
                    subtitle: values.subtitle || 'Short description goes here.',
                    primaryText: values.primaryText || 'Get started',
                    primaryUrl: values.primaryUrl || '#',
                    secondaryText: values.secondaryText || 'Learn more',
                    secondaryUrl: values.secondaryUrl || '#',
                    bg: values.bg || '',
                });
            } else if (outlineEditingType === 'support-hero') {
                applySupportHeroValues(outlineEditingBlock, {
                    heading: values.heading || 'Support center',
                    body: values.body || '',
                    lightImg: values.lightImg || '',
                    darkImg: values.darkImg || '',
                    colorFrom: values.colorFrom || '#ff4694',
                    colorTo: values.colorTo || '#776fff',
                });
            }
            persistLocal();
            refreshOutline();
            setStatus('Unsaved', 'bg-amber-400');
            closeOutlineEditor();
        });
    }
    });
