function classListFromString(value) {
    return String(value || '')
        .split(/\s+/)
        .filter(Boolean);
}

const ICON_LINK_TYPES = ['none', 'custom'];
const ICON_TARGETS = ['self', 'blank'];

const ICON_PRESETS = [
    { id: 'star', name: 'Star', className: 'ti ti-star' },
    { id: 'check', name: 'Check', className: 'ti ti-check' },
    { id: 'arrow-right', name: 'Arrow Right', className: 'ti ti-arrow-right' },
    { id: 'phone', name: 'Phone', className: 'ti ti-phone' },
    { id: 'mail', name: 'Mail', className: 'ti ti-mail' },
    { id: 'map-pin', name: 'Map Pin', className: 'ti ti-map-pin' },
    { id: 'heart', name: 'Heart', className: 'ti ti-heart' },
    { id: 'bolt', name: 'Bolt', className: 'ti ti-bolt' },
];

const DEFAULT_ICON_PRESET = ICON_PRESETS[0].id;
const DEFAULT_ICON_CLASS = ICON_PRESETS[0].className;

const ICON_STYLABLE_PROPS = [
    'width',
    'height',
    'margin',
    'padding',
    'background-color',
    'opacity',
    'color',
    'font-size',
    'line-height',
    'text-align',
    'border-style',
    'border-width',
    'border-color',
    'border-radius',
    'box-shadow',
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

const ICON_ALIGN_OPTIONS = [
    { id: 'left', name: 'Left', icon: ALIGN_ICON_LEFT },
    { id: 'center', name: 'Center', icon: ALIGN_ICON_CENTER },
    { id: 'right', name: 'Right', icon: ALIGN_ICON_RIGHT },
];

const ICON_PRESET_CLASS_MAP = ICON_PRESETS.reduce((acc, item) => {
    acc[item.id] = item.className;
    return acc;
}, {});

function normalizeSpaces(value) {
    return String(value || '')
        .trim()
        .replace(/\s+/g, ' ');
}

function normalizeIconPreset(value) {
    const preset = String(value || '').trim().toLowerCase();
    if (Object.prototype.hasOwnProperty.call(ICON_PRESET_CLASS_MAP, preset)) return preset;
    return 'custom';
}

function normalizeIconClass(value) {
    const next = normalizeSpaces(value);
    return next || DEFAULT_ICON_CLASS;
}

function normalizeLinkType(value) {
    const type = String(value || '').trim().toLowerCase();
    return ICON_LINK_TYPES.includes(type) ? type : 'none';
}

function normalizeTarget(value) {
    const target = String(value || '').trim().toLowerCase();
    return ICON_TARGETS.includes(target) ? target : 'self';
}

function normalizeUrl(value) {
    return String(value || '').trim();
}

function normalizeAlign(value) {
    const align = String(value || '').trim().toLowerCase();
    return ['left', 'center', 'right'].includes(align) ? align : 'left';
}

function normalizeLabel(value) {
    return String(value || '').trim();
}

function setModelAttributes(model, attrs) {
    if (typeof model?.setAttributes === 'function') {
        model.setAttributes(attrs);
        return;
    }

    model?.addAttributes?.(attrs);
}

function cleanIconClasses(classes) {
    return classes.filter((cls) => {
        if (cls === 'pg-icon') return false;
        if (cls === 'inline-flex' || cls === 'items-center' || cls === 'justify-center') return false;
        if (cls === 'leading-none') return false;
        if (cls === 'mx-auto' || cls === 'ml-auto' || cls === 'mr-auto') return false;
        return true;
    });
}

function resolvePresetByClass(iconClass) {
    const normalized = normalizeSpaces(iconClass);
    const found = ICON_PRESETS.find((item) => normalizeSpaces(item.className) === normalized);
    return found?.id || 'custom';
}

function resolveIconClass(model) {
    const preset = normalizeIconPreset(model.get('pgIconPreset'));
    const custom = normalizeSpaces(model.get('pgIconClass'));
    if (preset === 'custom') return custom || DEFAULT_ICON_CLASS;
    return ICON_PRESET_CLASS_MAP[preset] || DEFAULT_ICON_CLASS;
}

function setTraitRowVisible(name, visible) {
    const selectorByName =
        `input[name="${name}"], select[name="${name}"], textarea[name="${name}"], ` +
        `[data-pg-trait-name="${name}"], [data-trait-name="${name}"]`;

    const rows = new Set();
    document.querySelectorAll(selectorByName).forEach((field) => {
        const row = field.closest('.gjs-trt-trait');
        if (row) rows.add(row);
    });

    if (name === 'pgHref') {
        document
            .querySelectorAll('input[placeholder="https://example.com"]')
            .forEach((field) => {
                const row = field.closest('.gjs-trt-trait');
                if (row) rows.add(row);
            });
    }

    if (name === 'pgIconClass') {
        document
            .querySelectorAll('input[placeholder="ti ti-star"]')
            .forEach((field) => {
                const row = field.closest('.gjs-trt-trait');
                if (row) rows.add(row);
            });
    }

    rows.forEach((row) => {
        row.style.display = visible ? '' : 'none';
    });
}

function syncIconTraitRows(model) {
    const linkType = normalizeLinkType(model?.get?.('pgLinkType'));
    const preset = normalizeIconPreset(model?.get?.('pgIconPreset'));
    const isCustomLink = linkType === 'custom';
    const isCustomIcon = preset === 'custom';

    const apply = () => {
        setTraitRowVisible('pgHref', isCustomLink);
        setTraitRowVisible('pgTarget', isCustomLink);
        setTraitRowVisible('pgIconClass', isCustomIcon);
    };

    apply();
    if (typeof requestAnimationFrame === 'function') requestAnimationFrame(apply);
    setTimeout(apply, 0);
}

function hydrateIconProps(model) {
    const attrs = model?.getAttributes?.() || {};
    const tag = String(model?.get?.('tagName') || '').toLowerCase();
    const classes = classListFromString(attrs.class || '');
    const fromData = normalizeSpaces(attrs['data-pg-icon-class']);
    const iconClass = fromData || DEFAULT_ICON_CLASS;
    const preset = resolvePresetByClass(iconClass);

    model.set('pgIconPreset', preset, { silent: true });
    model.set('pgIconClass', preset === 'custom' ? iconClass : '', { silent: true });

    if (classes.includes('mx-auto')) model.set('pgAlign', 'center', { silent: true });
    else if (classes.includes('ml-auto')) model.set('pgAlign', 'right', { silent: true });
    else model.set('pgAlign', 'left', { silent: true });

    const href = normalizeUrl(attrs.href);
    if (tag === 'a' && href) {
        model.set('pgLinkType', 'custom', { silent: true });
        model.set('pgHref', href, { silent: true });
    } else {
        model.set('pgLinkType', 'none', { silent: true });
        model.set('pgHref', '', { silent: true });
    }

    model.set('pgTarget', attrs.target === '_blank' ? 'blank' : 'self', { silent: true });
    model.set('pgAriaLabel', normalizeLabel(attrs['aria-label']) || 'Icon', { silent: true });
}

function applyIconTraits(model) {
    const iconClass = resolveIconClass(model);
    const align = normalizeAlign(model.get('pgAlign'));
    const linkType = normalizeLinkType(model.get('pgLinkType'));
    const href = normalizeUrl(model.get('pgHref'));
    const target = normalizeTarget(model.get('pgTarget'));
    const ariaLabel = normalizeLabel(model.get('pgAriaLabel'));

    const attrs = model.getAttributes?.() || {};
    const previousIconTokens = classListFromString(attrs['data-pg-icon-class']);
    const cleaned = cleanIconClasses(classListFromString(attrs.class || '')).filter(
        (cls) => !previousIconTokens.includes(cls)
    );

    const alignClass = align === 'center' ? 'mx-auto' : align === 'right' ? 'ml-auto' : 'mr-auto';
    const nextClasses = [
        ...cleaned,
        'pg-icon',
        'inline-flex',
        'items-center',
        'justify-center',
        'leading-none',
        alignClass,
        ...classListFromString(iconClass),
    ];

    const preservedAttrs = { ...attrs };
    delete preservedAttrs.class;
    delete preservedAttrs.href;
    delete preservedAttrs.target;
    delete preservedAttrs.rel;
    delete preservedAttrs.type;
    delete preservedAttrs['aria-hidden'];
    delete preservedAttrs['data-gjs-name'];

    const nextAttrs = {
        ...preservedAttrs,
        class: Array.from(new Set(nextClasses)).join(' ').trim(),
        'data-pg-icon-class': iconClass,
        'data-gjs-name': 'Icon',
    };

    if (ariaLabel) nextAttrs['aria-label'] = ariaLabel;
    else delete nextAttrs['aria-label'];

    if (linkType === 'custom' && href) {
        if (model.get('tagName') !== 'a') model.set('tagName', 'a');
        nextAttrs.href = href;
        if (target === 'blank') {
            nextAttrs.target = '_blank';
            nextAttrs.rel = 'noopener noreferrer';
        }
    } else if (model.get('tagName') !== 'i') {
        model.set('tagName', 'i');
    }

    model.set('void', false, { silent: true });
    model.set('droppable', false, { silent: true });
    model.set('editable', false, { silent: true });

    const children = model.components?.();
    if (children?.length) children.reset([]);

    setModelAttributes(model, nextAttrs);
}

export function registerIconElement(editor) {
    const dc = editor.DomComponents;

    editor.on('component:selected', (component) => {
        const type = String(component?.get?.('type') || '');
        if (type !== 'pg-icon') return;
        syncIconTraitRows(component);
    });

    dc.addType('pg-icon', {
        isComponent: (el) => {
            if (!el || !el.tagName) return false;
            const tag = el.tagName.toLowerCase();
            if (!['i', 'a', 'span'].includes(tag)) return false;

            const name = (el.getAttribute?.('data-gjs-name') || '').toLowerCase();
            return name === 'icon' || el.classList?.contains('pg-icon');
        },

        model: {
            defaults: {
                tagName: 'i',
                name: 'Icon',
                droppable: false,
                editable: false,
                stylable: ICON_STYLABLE_PROPS,
                attributes: {
                    class:
                        'pg-icon inline-flex items-center justify-center leading-none mr-auto text-3xl ti ti-star',
                    'data-gjs-name': 'Icon',
                    'data-pg-icon-class': DEFAULT_ICON_CLASS,
                    'aria-label': 'Icon',
                },
                pgIconPreset: DEFAULT_ICON_PRESET,
                pgIconClass: '',
                pgAlign: 'left',
                pgLinkType: 'none',
                pgHref: '',
                pgTarget: 'self',
                pgAriaLabel: 'Icon',
                traits: [
                    {
                        type: 'pg-trait-heading',
                        name: 'pgIconMain',
                        label: 'Icon',
                    },
                    {
                        type: 'select',
                        name: 'pgIconPreset',
                        label: 'Preset',
                        changeProp: 1,
                        options: [
                            ...ICON_PRESETS.map((item) => ({ id: item.id, name: item.name })),
                            { id: 'custom', name: 'Custom Class' },
                        ],
                    },
                    {
                        type: 'text',
                        name: 'pgIconClass',
                        label: 'Custom Class',
                        changeProp: 1,
                        placeholder: 'ti ti-star',
                    },
                    {
                        type: 'text',
                        name: 'pgAriaLabel',
                        label: 'Aria Label',
                        changeProp: 1,
                        placeholder: 'Icon',
                    },
                    {
                        type: 'pg-trait-heading',
                        name: 'pgIconLink',
                        label: 'Link',
                    },
                    {
                        type: 'select',
                        name: 'pgLinkType',
                        label: 'Link Type',
                        changeProp: 1,
                        options: [
                            { id: 'none', name: 'None' },
                            { id: 'custom', name: 'Custom URL' },
                        ],
                    },
                    {
                        type: 'text',
                        name: 'pgHref',
                        label: 'Custom URL',
                        changeProp: 1,
                        placeholder: 'https://example.com',
                    },
                    {
                        type: 'select',
                        name: 'pgTarget',
                        label: 'Open In',
                        changeProp: 1,
                        options: [
                            { id: 'self', name: 'Same Tab' },
                            { id: 'blank', name: 'New Tab' },
                        ],
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgAlign',
                        label: 'Align',
                        changeProp: 1,
                        options: ICON_ALIGN_OPTIONS,
                    },
                ],
            },

            init() {
                this.set('stylable', ICON_STYLABLE_PROPS, { silent: true });
                hydrateIconProps(this);
                this.on(
                    'change:pgIconPreset change:pgIconClass change:pgAlign change:pgLinkType change:pgHref change:pgTarget change:pgAriaLabel',
                    () => {
                        applyIconTraits(this);
                        syncIconTraitRows(this);
                    }
                );
                applyIconTraits(this);
                syncIconTraitRows(this);
            },
        },
    });
}
