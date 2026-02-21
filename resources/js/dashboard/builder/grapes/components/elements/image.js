function classListFromString(value) {
    return String(value || '')
        .split(/\s+/)
        .filter(Boolean);
}

const DEFAULT_IMAGE_PLACEHOLDER = `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="600" viewBox="0 0 1200 600"><rect width="1200" height="600" fill="#e2e8f0"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#64748b" font-family="Arial,sans-serif" font-size="48">Image</text></svg>'
)}`;
const LEGACY_PLACEHOLDER_PATTERN = /^(https?:\/\/via\.placeholder\.com\/1200x600\?text=Image|1200x600\?text=Image)$/i;
const IMAGE_LINK_TYPES = ['none', 'media', 'custom'];
const IMAGE_STYLABLE_PROPS = [
    'width',
    'max-width',
    'height',
    'margin',
    'padding',
    'opacity',
    'filter',
    'border-style',
    'border-width',
    'border-color',
    'border-radius',
];

const ALIGN_ICON_LEFT = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 6h16"></path>
    <path d="M4 12h10"></path>
    <path d="M4 18h16"></path>
</svg>
`;

const ALIGN_ICON_CENTER = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 6h16"></path>
    <path d="M7 12h10"></path>
    <path d="M4 18h16"></path>
</svg>
`;

const ALIGN_ICON_RIGHT = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 6h16"></path>
    <path d="M10 12h10"></path>
    <path d="M4 18h16"></path>
</svg>
`;

const IMAGE_ALIGN_OPTIONS = [
    { id: 'left', name: 'Left', icon: ALIGN_ICON_LEFT },
    { id: 'center', name: 'Center', icon: ALIGN_ICON_CENTER },
    { id: 'right', name: 'Right', icon: ALIGN_ICON_RIGHT },
];

function cleanImageClasses(classes) {
    return classes.filter((cls) => {
        if (cls === 'pg-image') return false;
        if (cls === 'w-full' || cls === 'w-auto' || cls === 'max-w-full') return false;
        if (cls === 'mx-auto' || cls === 'ml-auto' || cls === 'mr-auto') return false;
        if (cls === 'block' || cls === 'inline-block') return false;
        if (cls === 'object-cover' || cls === 'object-contain' || cls === 'object-fill' || cls === 'object-none') return false;
        if (cls === 'rounded-none' || cls === 'rounded-md' || cls === 'rounded-xl' || cls === 'rounded-full') return false;
        return true;
    });
}

function normalizeImageSrc(value) {
    const src = String(value || '').trim();
    if (!src) return DEFAULT_IMAGE_PLACEHOLDER;
    if (LEGACY_PLACEHOLDER_PATTERN.test(src)) return DEFAULT_IMAGE_PLACEHOLDER;
    return src;
}

function normalizeFit(value) {
    const fit = String(value || '').trim();
    return ['cover', 'contain', 'fill', 'none'].includes(fit) ? fit : 'cover';
}

function normalizeRounded(value) {
    const rounded = String(value || '').trim();
    return ['none', 'md', 'xl', 'full'].includes(rounded) ? rounded : 'xl';
}

function normalizeAlign(value) {
    const align = String(value || '').trim().toLowerCase();
    return ['left', 'center', 'right'].includes(align) ? align : 'left';
}

function normalizeLinkType(value) {
    const type = String(value || '').trim().toLowerCase();
    return IMAGE_LINK_TYPES.includes(type) ? type : 'none';
}

function normalizeCustomUrl(value) {
    return String(value || '').trim();
}

function parseCssDimension(value) {
    const source = String(value || '').trim();
    if (!source) return null;
    const match = source.match(/^(-?\d*\.?\d+)([a-z%]*)$/i);
    if (!match) return null;

    const next = Number(match[1]);
    if (!Number.isFinite(next)) return null;

    return {
        value: next,
        unit: String(match[2] || '').toLowerCase(),
    };
}

function sanitizeImageDimension(value, prop) {
    const parsed = parseCssDimension(value);
    if (!parsed) return value;

    const clamp = (num, min, max) => Math.min(Math.max(num, min), max);

    const bounds =
        prop === 'height'
            ? {
                  px: { min: 0, max: 2400 },
                  vh: { min: 0, max: 100 },
                  '%': { min: 0, max: 100 },
              }
            : {
                  px: { min: 0, max: 2400 },
                  vw: { min: 0, max: 100 },
                  '%': { min: 0, max: 100 },
              };

    const unit = parsed.unit || (prop === 'height' ? 'px' : '%');
    const current = bounds[unit];
    if (!current) return value;

    const next = clamp(parsed.value, current.min, current.max);
    return `${next}${unit}`;
}

function sanitizeImageStyles(model) {
    const current = { ...(model?.getStyle?.() || {}) };
    const next = { ...current };

    ['width', 'max-width', 'height'].forEach((prop) => {
        const raw = current[prop];
        if (raw == null || raw === '') return;
        const sanitized = sanitizeImageDimension(raw, prop);
        if (sanitized !== raw) next[prop] = sanitized;
    });

    const changed = JSON.stringify(next) !== JSON.stringify(current);
    if (changed) model?.setStyle?.(next);
}

function roundedToClass(value) {
    if (value === 'none') return 'rounded-none';
    if (value === 'md') return 'rounded-md';
    if (value === 'full') return 'rounded-full';
    return 'rounded-xl';
}

function setModelAttributes(model, attrs) {
    if (typeof model?.setAttributes === 'function') {
        model.setAttributes(attrs);
        return;
    }

    model?.addAttributes?.(attrs);
}

function findFirstImageChild(model) {
    const children = model?.components?.();
    if (!children?.each) return null;

    let image = null;
    children.each((child) => {
        if (image) return;
        const tag = String(child?.get?.('tagName') || '').toLowerCase();
        if (tag === 'img') image = child;
    });

    return image;
}

function imageAttrsFromModel(model) {
    const rootTag = String(model?.get?.('tagName') || '').toLowerCase();
    if (rootTag === 'img') return model.getAttributes?.() || {};

    const imageChild = findFirstImageChild(model);
    return imageChild?.getAttributes?.() || {};
}

function ensureImageChild(model) {
    const children = model?.components?.();
    if (!children) return null;

    let image = findFirstImageChild(model);
    if (image) return image;

    children.reset([
        {
            type: 'default',
            tagName: 'img',
            attributes: {
                'data-gjs-name': 'Image',
            },
            droppable: false,
            editable: false,
            selectable: false,
            draggable: false,
            hoverable: false,
            copyable: false,
            removable: false,
        },
    ]);

    image = findFirstImageChild(model);
    return image || children.at?.(0) || null;
}

function setTraitRowVisible(name, visible) {
    const selectorByName =
        `input[name="${name}"], select[name="${name}"], textarea[name="${name}"], ` +
        `[data-pg-trait-name="${name}"], [data-trait-name="${name}"]`;

    const matchedRows = new Set();
    document.querySelectorAll(selectorByName).forEach((field) => {
        const row = field.closest('.gjs-trt-trait');
        if (row) matchedRows.add(row);
    });

    // Fallback for current Grapes text trait rendering where name attr can be missing.
    if (name === 'pgCustomUrl') {
        document
            .querySelectorAll('input[placeholder="https://example.com"]')
            .forEach((field) => {
                const row = field.closest('.gjs-trt-trait');
                if (row) matchedRows.add(row);
            });
    }

    matchedRows.forEach((row) => {
        row.style.display = visible ? '' : 'none';
    });
}

function syncImageTraitRows(model) {
    const linkType = normalizeLinkType(model?.get?.('pgLinkType'));
    const visible = linkType === 'custom';
    const apply = () => setTraitRowVisible('pgCustomUrl', visible);

    apply();
    if (typeof requestAnimationFrame === 'function') {
        requestAnimationFrame(apply);
    }
    setTimeout(apply, 0);
}

function hydrateImageProps(model) {
    const attrs = imageAttrsFromModel(model);
    const classes = classListFromString(attrs.class);
    const rootTag = String(model?.get?.('tagName') || '').toLowerCase();
    const rootAttrs = model?.getAttributes?.() || {};
    const rootHref = normalizeCustomUrl(rootAttrs.href);

    model.set('pgSrc', normalizeImageSrc(attrs.src), { silent: true });
    model.set('pgAlt', String(attrs.alt || 'Image'), { silent: true });

    if (classes.includes('object-contain')) model.set('pgFit', 'contain', { silent: true });
    else if (classes.includes('object-fill')) model.set('pgFit', 'fill', { silent: true });
    else if (classes.includes('object-none')) model.set('pgFit', 'none', { silent: true });
    else model.set('pgFit', 'cover', { silent: true });

    if (classes.includes('rounded-none')) model.set('pgRounded', 'none', { silent: true });
    else if (classes.includes('rounded-md')) model.set('pgRounded', 'md', { silent: true });
    else if (classes.includes('rounded-full')) model.set('pgRounded', 'full', { silent: true });
    else model.set('pgRounded', 'xl', { silent: true });

    if (classes.includes('mx-auto')) model.set('pgAlign', 'center', { silent: true });
    else if (classes.includes('ml-auto')) model.set('pgAlign', 'right', { silent: true });
    else model.set('pgAlign', 'left', { silent: true });

    const loading = String(attrs.loading || '').toLowerCase();
    model.set('pgLoading', loading === 'eager' ? 'eager' : 'lazy', { silent: true });

    if (rootTag === 'a' && rootHref) {
        const src = normalizeImageSrc(attrs.src);
        if (rootHref === src) {
            model.set('pgLinkType', 'media', { silent: true });
            model.set('pgCustomUrl', '', { silent: true });
        } else {
            model.set('pgLinkType', 'custom', { silent: true });
            model.set('pgCustomUrl', rootHref, { silent: true });
        }
    } else {
        model.set('pgLinkType', 'none', { silent: true });
        model.set('pgCustomUrl', '', { silent: true });
    }
}

function applyImageTraits(model) {
    const src = normalizeImageSrc(model.get('pgSrc'));
    const alt = String(model.get('pgAlt') || 'Image');
    const fit = normalizeFit(model.get('pgFit'));
    const rounded = normalizeRounded(model.get('pgRounded'));
    const align = normalizeAlign(model.get('pgAlign'));
    const loading = String(model.get('pgLoading') || 'lazy') === 'eager' ? 'eager' : 'lazy';
    const linkType = normalizeLinkType(model.get('pgLinkType'));
    const customUrl = normalizeCustomUrl(model.get('pgCustomUrl'));

    const attrs = imageAttrsFromModel(model);
    const cleaned = cleanImageClasses(classListFromString(attrs.class || ''));
    const alignClass = align === 'center' ? 'mx-auto' : align === 'right' ? 'ml-auto' : 'mr-auto';
    const nextClasses = [
        ...cleaned,
        'pg-image',
        'block',
        'max-w-full',
        alignClass,
        `object-${fit}`,
        roundedToClass(rounded),
    ];
    const imageAttrs = {
        src,
        alt,
        loading,
        class: Array.from(new Set(nextClasses)).join(' ').trim(),
        'data-gjs-name': 'Image',
    };
    const hasLink = linkType === 'media' || (linkType === 'custom' && !!customUrl);

    if (!hasLink) {
        if (model.get('tagName') !== 'img') {
            model.set('tagName', 'img');
        }
        model.set('void', true, { silent: true });
        model.set('droppable', false, { silent: true });
        model.set('editable', false, { silent: true });

        const children = model.components?.();
        if (children?.length) children.reset([]);

        setModelAttributes(model, imageAttrs);
        return;
    }

    const href = linkType === 'media' ? src : customUrl;
    const rootAttrs = model.getAttributes?.() || {};
    const rootClasses = classListFromString(rootAttrs.class).filter(
        (cls) =>
            cls !== 'pg-image' &&
            cls !== 'pg-image-link' &&
            cls !== 'inline-block' &&
            cls !== 'block' &&
            cls !== 'w-fit' &&
            cls !== 'mx-auto' &&
            cls !== 'ml-auto' &&
            cls !== 'mr-auto'
    );
    const nextRootAttrs = {
        ...rootAttrs,
        href: href || '#',
        class: Array.from(new Set(['pg-image-link', 'block', 'w-fit', alignClass, ...rootClasses])).join(' ').trim(),
        'data-gjs-name': 'Image',
    };
    delete nextRootAttrs.src;
    delete nextRootAttrs.alt;
    delete nextRootAttrs.loading;

    if (model.get('tagName') !== 'a') {
        model.set('tagName', 'a');
    }
    model.set('void', false, { silent: true });
    model.set('droppable', false, { silent: true });
    model.set('editable', false, { silent: true });
    setModelAttributes(model, nextRootAttrs);

    const imageChild = ensureImageChild(model);
    if (!imageChild) return;

    imageChild.set('tagName', 'img', { silent: true });
    imageChild.set('void', true, { silent: true });
    imageChild.set('droppable', false, { silent: true });
    imageChild.set('editable', false, { silent: true });
    imageChild.set('selectable', false, { silent: true });
    imageChild.set('draggable', false, { silent: true });
    imageChild.set('hoverable', false, { silent: true });
    imageChild.set('copyable', false, { silent: true });
    imageChild.set('removable', false, { silent: true });
    setModelAttributes(imageChild, imageAttrs);
}

export function registerImageElement(editor) {
    const dc = editor.DomComponents;

    editor.on('component:selected', (component) => {
        const type = String(component?.get?.('type') || '');
        if (type !== 'pg-image') return;
        syncImageTraitRows(component);
    });

    dc.addType('pg-image', {
        isComponent: (el) => {
            if (!el || !el.tagName) return false;
            const tag = el.tagName.toLowerCase();
            const name = (el.getAttribute?.('data-gjs-name') || '').toLowerCase();

            if (tag === 'img') {
                return name === 'image' || el.classList?.contains('pg-image');
            }

            if (tag === 'a') {
                if (name === 'image') return true;
                return !!el.querySelector?.('img.pg-image, img[data-gjs-name="Image"]');
            }

            return false;
        },

        model: {
            defaults: {
                tagName: 'img',
                name: 'Image',
                void: true,
                droppable: false,
                editable: false,
                stylable: IMAGE_STYLABLE_PROPS,
                attributes: {
                    src: DEFAULT_IMAGE_PLACEHOLDER,
                    alt: 'Image',
                    loading: 'lazy',
                    class: 'pg-image max-w-full rounded-xl object-cover',
                    'data-gjs-name': 'Image',
                },
                pgSrc: DEFAULT_IMAGE_PLACEHOLDER,
                pgAlt: 'Image',
                pgFit: 'cover',
                pgAlign: 'left',
                pgRounded: 'xl',
                pgLoading: 'lazy',
                pgLinkType: 'none',
                pgCustomUrl: '',
                traits: [
                    {
                        type: 'media-picker',
                        name: 'pgSrc',
                        label: 'Image',
                        changeProp: 1,
                    },
                    {
                        type: 'select',
                        name: 'pgLinkType',
                        label: 'رابط',
                        changeProp: 1,
                        options: [
                            { id: 'none', name: 'بدون' },
                            { id: 'media', name: 'ملف الوسائط' },
                            { id: 'custom', name: 'رابط (URL) مخصص' },
                        ],
                    },
                    {
                        type: 'text',
                        name: 'pgCustomUrl',
                        label: 'رابط (URL) مخصص',
                        changeProp: 1,
                        placeholder: 'https://example.com',
                    },
                    {
                        type: 'text',
                        name: 'pgAlt',
                        label: 'Alt Text',
                        changeProp: 1,
                    },
                    {
                        type: 'select',
                        name: 'pgFit',
                        label: 'Object Fit',
                        changeProp: 1,
                        options: [
                            { id: 'cover', name: 'Cover' },
                            { id: 'contain', name: 'Contain' },
                            { id: 'fill', name: 'Fill' },
                            { id: 'none', name: 'None' },
                        ],
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgAlign',
                        label: 'Align',
                        changeProp: 1,
                        options: IMAGE_ALIGN_OPTIONS,
                    },
                    {
                        type: 'select',
                        name: 'pgLoading',
                        label: 'Loading',
                        changeProp: 1,
                        options: [
                            { id: 'lazy', name: 'Lazy' },
                            { id: 'eager', name: 'Eager' },
                        ],
                    },
                ],
            },

            init() {
                this.set('stylable', IMAGE_STYLABLE_PROPS, { silent: true });
                sanitizeImageStyles(this);
                hydrateImageProps(this);
                this.on(
                    'change:pgSrc change:pgAlt change:pgFit change:pgAlign change:pgRounded change:pgLoading change:pgLinkType change:pgCustomUrl',
                    () => {
                        applyImageTraits(this);
                        syncImageTraitRows(this);
                    }
                );
                applyImageTraits(this);
                syncImageTraitRows(this);
            },
        },
    });
}
