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
                persistLocal();
                setStatus('Unsaved', 'bg-amber-400');
            },
        });
    }

    const editBlock = (block, type) => {
        let handledWithModal = false;
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
            case 'hero-template': {
                handledWithModal = true;
                openFormModal('تعديل الـ Hero', [
                    { label: 'العنوان', name: 'heading', value: block.dataset.heading || '' },
                    { label: 'الوصف', name: 'subtitle', type: 'textarea', value: block.dataset.subtitle || '' },
                    { label: 'النص الأساسي', name: 'primaryText', value: block.dataset.primaryText || '' },
                    { label: 'رابط الأساسي', name: 'primaryUrl', type: 'url', value: block.dataset.primaryUrl || '#' },
                    { label: 'النص الثانوي', name: 'secondaryText', value: block.dataset.secondaryText || '' },
                    { label: 'رابط الثانوي', name: 'secondaryUrl', type: 'url', value: block.dataset.secondaryUrl || '#' },
                    { label: 'صورة الخلفية', name: 'bg', type: 'url', value: block.dataset.bg || '' },
                ], (values) => {
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
                    persistLocal();
                    setStatus('Unsaved', 'bg-amber-400');
                });
                break;
            }
            case 'support-hero': {
                handledWithModal = true;
                openFormModal('تعديل Support Hero', [
                    { label: 'العنوان', name: 'heading', value: block.dataset.heading || '' },
                    { label: 'الوصف', name: 'body', type: 'textarea', value: block.dataset.body || '' },
                    { label: 'صورة النور', name: 'lightImg', type: 'url', value: block.dataset.lightImg || '' },
                    { label: 'صورة الداكن', name: 'darkImg', type: 'url', value: block.dataset.darkImg || '' },
                    { label: 'لون التدرج (من)', name: 'colorFrom', type: 'color', value: block.dataset.colorFrom || '#ff4694' },
                    { label: 'لون التدرج (إلى)', name: 'colorTo', type: 'color', value: block.dataset.colorTo || '#776fff' },
                ], (values) => {
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
                    persistLocal();
                    setStatus('Unsaved', 'bg-amber-400');
                });
                break;
            }
            default:
                break;
        }
        if (!handledWithModal) {
            persistLocal();
            setStatus('Unsaved', 'bg-amber-400');
        }
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

    // Preview toggles (desktop/tablet/mobile)
    if (previewButtons.length && root) {
        previewButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const mode = btn.dataset.preview || 'desktop';
                setPreviewMode(mode);
            });
        });
        setPreviewMode('desktop');
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
