function classListFromString(value) {
    return String(value || '')
        .split(/\s+/)
        .filter(Boolean);
}

const DEFAULT_IMAGE_PLACEHOLDER = `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="600" viewBox="0 0 1200 600"><rect width="1200" height="600" fill="#e2e8f0"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#64748b" font-family="Arial,sans-serif" font-size="48">Image</text></svg>'
)}`;
const LEGACY_PLACEHOLDER_PATTERN = /^(https?:\/\/via\.placeholder\.com\/1200x600\?text=Image|1200x600\?text=Image)$/i;

function cleanImageClasses(classes) {
    return classes.filter((cls) => {
        if (cls === 'pg-image') return false;
        if (cls === 'w-full' || cls === 'w-auto' || cls === 'max-w-full') return false;
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

function normalizeWidth(value) {
    const width = String(value || '').trim();
    return width === 'auto' ? 'auto' : 'full';
}

function roundedToClass(value) {
    if (value === 'none') return 'rounded-none';
    if (value === 'md') return 'rounded-md';
    if (value === 'full') return 'rounded-full';
    return 'rounded-xl';
}

function hydrateImageProps(model) {
    const attrs = model.getAttributes?.() || {};
    const classes = classListFromString(attrs.class);

    model.set('pgSrc', normalizeImageSrc(attrs.src), { silent: true });
    model.set('pgAlt', String(attrs.alt || 'Image'), { silent: true });

    if (classes.includes('w-auto')) model.set('pgWidth', 'auto', { silent: true });
    else model.set('pgWidth', 'full', { silent: true });

    if (classes.includes('object-contain')) model.set('pgFit', 'contain', { silent: true });
    else if (classes.includes('object-fill')) model.set('pgFit', 'fill', { silent: true });
    else if (classes.includes('object-none')) model.set('pgFit', 'none', { silent: true });
    else model.set('pgFit', 'cover', { silent: true });

    if (classes.includes('rounded-none')) model.set('pgRounded', 'none', { silent: true });
    else if (classes.includes('rounded-md')) model.set('pgRounded', 'md', { silent: true });
    else if (classes.includes('rounded-full')) model.set('pgRounded', 'full', { silent: true });
    else model.set('pgRounded', 'xl', { silent: true });

    const loading = String(attrs.loading || '').toLowerCase();
    model.set('pgLoading', loading === 'eager' ? 'eager' : 'lazy', { silent: true });
}

function applyImageTraits(model) {
    const src = normalizeImageSrc(model.get('pgSrc'));
    const alt = String(model.get('pgAlt') || 'Image');
    const width = normalizeWidth(model.get('pgWidth'));
    const fit = normalizeFit(model.get('pgFit'));
    const rounded = normalizeRounded(model.get('pgRounded'));
    const loading = String(model.get('pgLoading') || 'lazy') === 'eager' ? 'eager' : 'lazy';

    if (model.get('tagName') !== 'img') {
        model.set('tagName', 'img');
    }

    const attrs = model.getAttributes?.() || {};
    const cleaned = cleanImageClasses(classListFromString(attrs.class));
    const widthClasses = width === 'auto' ? ['w-auto'] : ['w-full', 'max-w-full'];
    const nextClasses = [
        ...cleaned,
        'pg-image',
        ...widthClasses,
        `object-${fit}`,
        roundedToClass(rounded),
    ];

    model.addAttributes({
        ...attrs,
        src,
        alt,
        loading,
        class: Array.from(new Set(nextClasses)).join(' ').trim(),
        'data-gjs-name': 'Image',
    });
}

export function registerImageElement(editor) {
    const dc = editor.DomComponents;

    dc.addType('pg-image', {
        isComponent: (el) => {
            if (!el || !el.tagName) return false;
            if (el.tagName.toLowerCase() !== 'img') return false;

            const name = (el.getAttribute?.('data-gjs-name') || '').toLowerCase();
            return name === 'image' || el.classList?.contains('pg-image');
        },

        model: {
            defaults: {
                tagName: 'img',
                name: 'Image',
                void: true,
                droppable: false,
                editable: false,
                attributes: {
                    src: DEFAULT_IMAGE_PLACEHOLDER,
                    alt: 'Image',
                    loading: 'lazy',
                    class: 'pg-image w-full max-w-full rounded-xl object-cover',
                    'data-gjs-name': 'Image',
                },
                pgSrc: DEFAULT_IMAGE_PLACEHOLDER,
                pgAlt: 'Image',
                pgWidth: 'full',
                pgFit: 'cover',
                pgRounded: 'xl',
                pgLoading: 'lazy',
                traits: [
                    {
                        type: 'media-picker',
                        name: 'pgSrc',
                        label: 'Image',
                        changeProp: 1,
                    },
                    {
                        type: 'text',
                        name: 'pgAlt',
                        label: 'Alt Text',
                        changeProp: 1,
                    },
                    {
                        type: 'select',
                        name: 'pgWidth',
                        label: 'Width',
                        changeProp: 1,
                        options: [
                            { id: 'full', name: 'Full' },
                            { id: 'auto', name: 'Auto' },
                        ],
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
                        type: 'select',
                        name: 'pgRounded',
                        label: 'Radius',
                        changeProp: 1,
                        options: [
                            { id: 'none', name: 'None' },
                            { id: 'md', name: 'Medium' },
                            { id: 'xl', name: 'Large' },
                            { id: 'full', name: 'Full' },
                        ],
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
                hydrateImageProps(this);
                this.on(
                    'change:pgSrc change:pgAlt change:pgWidth change:pgFit change:pgRounded change:pgLoading',
                    () => applyImageTraits(this)
                );
                applyImageTraits(this);
            },
        },
    });
}
