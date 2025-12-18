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

    const storageKey = root?.dataset.pageId ? `builder-html-${root.dataset.pageId}` : null;

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

    const applyAlignment = (element, alignment) => {
        if (!element) return;
        element.style.textAlign = alignment || 'left';
    };

    const attachBlockControls = (block) => {
        if (!block) return;
        const type = block.dataset.type || block.dataset.blockType || 'text';
        const existing = block.querySelector('.block-actions');
        if (existing) existing.remove();

        const actions = document.createElement('div');
        actions.className = 'block-actions';

        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.textContent = '✏️';
        editBtn.title = 'Edit';

        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.textContent = '🗑';
        delBtn.title = 'Delete';

        actions.appendChild(editBtn);
        actions.appendChild(delBtn);
        block.appendChild(actions);

        editBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            editBlock(block, type);
        });

        delBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            block.remove();
            updateEmptyState();
            persistLocal();
            setStatus('Unsaved', 'bg-amber-400');
        });
    };

    const createTextBlock = (data) => {
        const headingText = getValue(data, 'text_heading', 'Heading');
        const bodyText = getValue(data, 'text_body', 'Add your text here.');
        const alignment = getValue(data, 'text_align', 'left');

        const wrapper = document.createElement('div');
        wrapper.className = 'builder-block';
        wrapper.dataset.type = 'text';
        wrapper.dataset.heading = headingText;
        wrapper.dataset.body = bodyText;
        wrapper.dataset.align = alignment;
        applyAlignment(wrapper, alignment);

        if (headingText) {
            const heading = document.createElement('h3');
            heading.textContent = headingText;
            wrapper.appendChild(heading);
        }

        if (bodyText) {
            const body = document.createElement('p');
            body.textContent = bodyText;
            wrapper.appendChild(body);
        }

        attachBlockControls(wrapper);
        return wrapper;
    };

    const createImageBlock = (data) => {
        const url = getValue(data, 'image_url', '');
        if (!requireValue(url, 'Image URL is required.')) return null;

        const alt = getValue(data, 'image_alt', '');
        const widthValue = getValue(data, 'image_width', '100%');
        const alignment = getValue(data, 'image_align', 'center');

        const figure = document.createElement('figure');
        figure.className = 'builder-block';
        figure.dataset.type = 'image';
        figure.dataset.url = url;
        figure.dataset.alt = alt;
        figure.dataset.width = widthValue;
        figure.dataset.align = alignment;
        applyAlignment(figure, alignment);

        const img = document.createElement('img');
        img.src = url;
        img.alt = alt;

        if (widthValue) {
            img.style.width = /^\d+$/.test(widthValue) ? `${widthValue}px` : widthValue;
        }

        figure.appendChild(img);

        if (alt) {
            const caption = document.createElement('figcaption');
            caption.textContent = alt;
            caption.style.fontSize = '12px';
            caption.style.color = '#64748b';
            caption.style.marginTop = '8px';
            figure.appendChild(caption);
        }

        attachBlockControls(figure);
        return figure;
    };

    const createButtonBlock = (data) => {
        const text = getValue(data, 'button_text', 'Click here');
        if (!requireValue(text, 'Button text is required.')) return null;

        const url = getValue(data, 'button_url', '#');
        const style = getValue(data, 'button_style', 'primary');
        const alignment = getValue(data, 'button_align', 'center');

        const wrapper = document.createElement('div');
        wrapper.className = 'builder-block';
        wrapper.dataset.type = 'button';
        wrapper.dataset.text = text;
        wrapper.dataset.url = url;
        wrapper.dataset.style = style;
        wrapper.dataset.align = alignment;
        applyAlignment(wrapper, alignment);

        const link = document.createElement('a');
        link.href = url;
        link.textContent = text;
        link.className = `builder-button ${style}`;
        wrapper.appendChild(link);

        attachBlockControls(wrapper);
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

    const buildBlock = (type, payload = {}) => {
        switch (type) {
            case 'text':
                return createTextBlock({
                    text_heading: payload.heading || 'Heading',
                    text_body: payload.body || 'Add your text here.',
                    text_align: payload.align || 'left',
                });
            case 'image':
                return createImageBlock({
                    image_url: payload.url || 'https://via.placeholder.com/1200x600',
                    image_alt: payload.alt || '',
                    image_width: payload.width || '100%',
                    image_align: payload.align || 'center',
                });
            case 'button':
                return createButtonBlock({
                    button_text: payload.text || 'Click here',
                    button_url: payload.url || '#',
                    button_style: payload.style || 'primary',
                    button_align: payload.align || 'center',
                });
            case 'section':
                return createSectionBlock({
                    section_title: payload.title || 'Section title',
                    section_body: payload.body || 'Describe this section.',
                    section_bg: payload.bg || '#ffffff',
                    section_padding: payload.padding || '24',
                    section_align: payload.align || 'left',
                });
            default:
                return null;
        }
    };

    // Drag from block palette
    blockButtons.forEach((button) => {
        button.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('text/plain', button.dataset.block);
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
            const type = e.dataTransfer.getData('text/plain');
            if (!type) return;
            const block = buildBlock(type, {});
            if (!block) return;
            stage.appendChild(block);
            updateEmptyState();
            persistLocal();
            setStatus('Unsaved', 'bg-amber-400');
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
                persistLocal();
                setStatus('Unsaved', 'bg-amber-400');
            },
        });
    }

    const editBlock = (block, type) => {
        switch (type) {
            case 'text': {
                const heading = window.prompt('Heading', block.dataset.heading || 'Heading');
                const body = window.prompt('Body', block.dataset.body || 'Add your text here.');
                const align = window.prompt('Align (left/center/right)', block.dataset.align || 'left');
                if (heading !== null) block.dataset.heading = heading;
                if (body !== null) block.dataset.body = body;
                if (align !== null) block.dataset.align = align;
                const h3 = block.querySelector('h3');
                if (h3) h3.textContent = block.dataset.heading || '';
                const p = block.querySelector('p');
                if (p) p.textContent = block.dataset.body || '';
                applyAlignment(block, block.dataset.align || 'left');
                break;
            }
            case 'image': {
                const url = window.prompt('Image URL', block.dataset.url || '');
                const alt = window.prompt('Alt', block.dataset.alt || '');
                const width = window.prompt('Width (e.g. 100% or 640px)', block.dataset.width || '100%');
                const align = window.prompt('Align (left/center/right)', block.dataset.align || 'center');
                if (url !== null) block.dataset.url = url;
                if (alt !== null) block.dataset.alt = alt;
                if (width !== null) block.dataset.width = width;
                if (align !== null) block.dataset.align = align;
                const img = block.querySelector('img');
                if (img) {
                    img.src = block.dataset.url || '';
                    img.alt = block.dataset.alt || '';
                    img.style.width = /^\d+$/.test(block.dataset.width || '') ? `${block.dataset.width}px` : (block.dataset.width || '100%');
                }
                applyAlignment(block, block.dataset.align || 'center');
                const caption = block.querySelector('figcaption');
                if (caption) caption.textContent = block.dataset.alt || '';
                break;
            }
            case 'button': {
                const text = window.prompt('Button text', block.dataset.text || 'Click here');
                const url = window.prompt('Button URL', block.dataset.url || '#');
                const style = window.prompt('Style (primary/outline)', block.dataset.style || 'primary');
                const align = window.prompt('Align (left/center/right)', block.dataset.align || 'center');
                if (text !== null) block.dataset.text = text;
                if (url !== null) block.dataset.url = url;
                if (style !== null) block.dataset.style = style;
                if (align !== null) block.dataset.align = align;
                const link = block.querySelector('a');
                if (link) {
                    link.textContent = block.dataset.text || '';
                    link.href = block.dataset.url || '#';
                    link.className = `builder-button ${block.dataset.style || 'primary'}`;
                }
                applyAlignment(block, block.dataset.align || 'center');
                break;
            }
            case 'section': {
                const title = window.prompt('Title', block.dataset.title || 'Section title');
                const body = window.prompt('Body', block.dataset.body || 'Describe this section.');
                const bg = window.prompt('Background (hex)', block.dataset.bg || '#ffffff');
                const padding = window.prompt('Padding (px)', block.dataset.padding || '24');
                const align = window.prompt('Align (left/center/right)', block.dataset.align || 'left');
                if (title !== null) block.dataset.title = title;
                if (body !== null) block.dataset.body = body;
                if (bg !== null) block.dataset.bg = bg;
                if (padding !== null) block.dataset.padding = padding;
                if (align !== null) block.dataset.align = align;
                const h3 = block.querySelector('h3');
                if (h3) h3.textContent = block.dataset.title || '';
                const p = block.querySelector('p');
                if (p) p.textContent = block.dataset.body || '';
                block.style.backgroundColor = block.dataset.bg || '#ffffff';
                block.style.padding = `${parseInt(block.dataset.padding || '24', 10)}px`;
                applyAlignment(block, block.dataset.align || 'left');
                break;
            }
            default:
                break;
        }
        persistLocal();
        setStatus('Unsaved', 'bg-amber-400');
    };

    const rehydrateBlocks = () => {
        if (!stage) return;
        Array.from(stage.children).forEach((block) => {
            attachBlockControls(block);
        });
        updateEmptyState();
    };

    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            persistLocal();
            setStatus('Saved', 'bg-emerald-400');
        });
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
        rehydrateBlocks();
    };

    restoreLocal();
});
