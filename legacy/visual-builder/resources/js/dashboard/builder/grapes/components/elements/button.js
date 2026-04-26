function classListFromString(value) {
    return String(value || '')
        .split(/\s+/)
        .filter(Boolean);
}

const BUTTON_LINK_TYPES = ['none', 'custom'];
const BUTTON_TARGETS = ['self', 'blank'];
const BUTTON_STYLABLE_PROPS = [
    'width',
    'max-width',
    'height',
    'margin',
    'padding',
    'background-color',
    'opacity',
    'color',
    'font-family',
    'font-size',
    'font-weight',
    'text-transform',
    'line-height',
    'letter-spacing',
    'border-style',
    'border-width',
    'border-color',
    'border-radius',
    'text-shadow',
    '-webkit-text-stroke-width',
    '-webkit-text-stroke-color',
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

const ALIGN_ICON_STRETCH = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M3 6h18"></path>
    <path d="M3 18h18"></path>
    <path d="M8 12h8"></path>
    <path d="M11 9l-3 3 3 3"></path>
    <path d="M13 9l3 3-3 3"></path>
</svg>
`;

const BUTTON_ALIGN_OPTIONS = [
    { id: 'left', name: 'Left', icon: ALIGN_ICON_LEFT },
    { id: 'center', name: 'Center', icon: ALIGN_ICON_CENTER },
    { id: 'right', name: 'Right', icon: ALIGN_ICON_RIGHT },
    { id: 'stretch', name: 'Stretch', icon: ALIGN_ICON_STRETCH },
];

function cleanButtonClasses(classes) {
    return classes.filter((cls) => {
        if (cls === 'pg-button') return false;
        if (cls === 'flex' || cls === 'inline-flex') return false;
        if (cls === 'w-fit' || cls === 'w-full') return false;
        if (cls === 'items-center' || cls === 'justify-center') return false;
        if (cls === 'mx-auto' || cls === 'ml-auto' || cls === 'mr-auto') return false;
        return true;
    });
}

function normalizeLinkType(value) {
    const type = String(value || '').trim().toLowerCase();
    return BUTTON_LINK_TYPES.includes(type) ? type : 'none';
}

function normalizeTarget(value) {
    const target = String(value || '').trim().toLowerCase();
    return BUTTON_TARGETS.includes(target) ? target : 'self';
}

function normalizeAlign(value) {
    const align = String(value || '').trim().toLowerCase();
    return ['left', 'center', 'right', 'stretch'].includes(align) ? align : 'left';
}

function normalizeText(value) {
    const text = String(value || '').trim();
    return text || 'Button';
}

function normalizeUrl(value) {
    return String(value || '').trim();
}

function setModelAttributes(model, attrs) {
    if (typeof model?.setAttributes === 'function') {
        model.setAttributes(attrs);
        return;
    }

    model?.addAttributes?.(attrs);
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

    if (name === 'pgBtnUrl') {
        document
            .querySelectorAll('input[placeholder="https://example.com"]')
            .forEach((field) => {
                const row = field.closest('.gjs-trt-trait');
                if (row) rows.add(row);
            });
    }

    rows.forEach((row) => {
        row.style.display = visible ? '' : 'none';
    });
}

function syncButtonTraitRows(model) {
    const linkType = normalizeLinkType(model?.get?.('pgLinkType'));
    const isCustom = linkType === 'custom';
    const apply = () => {
        setTraitRowVisible('pgBtnUrl', isCustom);
        setTraitRowVisible('pgTarget', isCustom);
    };

    apply();
    if (typeof requestAnimationFrame === 'function') {
        requestAnimationFrame(apply);
    }
    setTimeout(apply, 0);
}

function ensureButtonTextNode(model, text) {
    const components = model?.components?.();
    if (!components) return;

    const first = components.at?.(0);
    if (first?.is?.('text')) {
        first.set('content', text);
        return;
    }

    components.reset([{ type: 'text', content: text }]);
}

function hydrateButtonProps(model) {
    const attrs = model?.getAttributes?.() || {};
    const classes = classListFromString(attrs.class || '');
    const tag = String(model?.get?.('tagName') || '').toLowerCase();
    const href = normalizeUrl(attrs.href);
    const first = model?.components?.()?.at?.(0);
    const text = first?.is?.('text') ? String(first.get('content') || '') : '';

    model.set('pgText', normalizeText(text), { silent: true });
    model.set('pgBtnUrl', href, { silent: true });
    model.set('pgTarget', attrs.target === '_blank' ? 'blank' : 'self', { silent: true });

    if (classes.includes('w-full')) model.set('pgAlign', 'stretch', { silent: true });
    else if (classes.includes('mx-auto')) model.set('pgAlign', 'center', { silent: true });
    else if (classes.includes('ml-auto')) model.set('pgAlign', 'right', { silent: true });
    else model.set('pgAlign', 'left', { silent: true });

    if (tag === 'a' && href && href !== '#') {
        model.set('pgLinkType', 'custom', { silent: true });
    } else {
        model.set('pgLinkType', 'none', { silent: true });
        if (!href || href === '#') model.set('pgBtnUrl', '', { silent: true });
    }
}

function applyButtonTraits(model) {
    const text = normalizeText(model.get('pgText'));
    const linkType = normalizeLinkType(model.get('pgLinkType'));
    const href = normalizeUrl(model.get('pgBtnUrl'));
    const target = normalizeTarget(model.get('pgTarget'));
    const align = normalizeAlign(model.get('pgAlign'));
    const isCustomLink = linkType === 'custom' && !!href;

    const attrs = model.getAttributes?.() || {};
    const cleaned = cleanButtonClasses(classListFromString(attrs.class || ''));
    const alignClass =
        align === 'stretch'
            ? ''
            : align === 'center'
              ? 'mx-auto'
              : align === 'right'
                ? 'ml-auto'
                : 'mr-auto';
    const widthClass = align === 'stretch' ? 'w-full' : 'w-fit';
    const nextClasses = [
        ...cleaned,
        'pg-button',
        'flex',
        widthClass,
        'items-center',
        'justify-center',
        alignClass,
    ];

    const preservedAttrs = { ...attrs };
    delete preservedAttrs.class;
    delete preservedAttrs.href;
    delete preservedAttrs.target;
    delete preservedAttrs.rel;
    delete preservedAttrs.type;
    delete preservedAttrs['data-gjs-name'];

    const nextAttrs = {
        ...preservedAttrs,
        class: Array.from(new Set(nextClasses)).join(' ').trim(),
        'data-gjs-name': 'Button',
    };

    if (isCustomLink) {
        if (model.get('tagName') !== 'a') model.set('tagName', 'a');
        nextAttrs.href = href;
        if (target === 'blank') {
            nextAttrs.target = '_blank';
            nextAttrs.rel = 'noopener noreferrer';
        }
    } else {
        if (model.get('tagName') !== 'button') model.set('tagName', 'button');
        nextAttrs.type = 'button';
    }

    setModelAttributes(model, nextAttrs);
    ensureButtonTextNode(model, text);
}

export function registerButtonElement(editor) {
    const dc = editor.DomComponents;

    editor.on('component:selected', (component) => {
        const type = String(component?.get?.('type') || '');
        if (type !== 'pg-button') return;
        syncButtonTraitRows(component);
    });

    dc.addType('pg-button', {
        isComponent: (el) => {
            if (!el || !el.tagName) return false;
            const tag = el.tagName.toLowerCase();
            const name = (el.getAttribute?.('data-gjs-name') || '').toLowerCase();
            if (!['button', 'a'].includes(tag)) return false;
            return name === 'button' || el.classList?.contains('pg-button');
        },

        model: {
            defaults: {
                tagName: 'button',
                name: 'Button',
                void: false,
                droppable: false,
                editable: false,
                stylable: BUTTON_STYLABLE_PROPS,
                attributes: {
                    type: 'button',
                    class:
                        'pg-button flex w-fit items-center justify-center mr-auto rounded-xl px-6 py-3 font-bold bg-primary text-white',
                    'data-gjs-name': 'Button',
                },
                components: [{ type: 'text', content: 'Button' }],
                pgText: 'Button',
                pgLinkType: 'none',
                pgBtnUrl: '',
                pgTarget: 'self',
                pgAlign: 'left',
                traits: [
                    {
                        type: 'text',
                        name: 'pgText',
                        label: 'Text',
                        changeProp: 1,
                        placeholder: 'Button text',
                    },
                    {
                        type: 'select',
                        name: 'pgLinkType',
                        label: 'Link',
                        changeProp: 1,
                        options: [
                            { id: 'none', name: 'None' },
                            { id: 'custom', name: 'Custom URL' },
                        ],
                    },
                    {
                        type: 'text',
                        name: 'pgBtnUrl',
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
                        options: BUTTON_ALIGN_OPTIONS,
                    },
                ],
            },

            init() {
                this.set('stylable', BUTTON_STYLABLE_PROPS, { silent: true });
                hydrateButtonProps(this);
                this.on(
                    'change:pgText change:pgLinkType change:pgBtnUrl change:pgTarget change:pgAlign',
                    () => {
                        applyButtonTraits(this);
                        syncButtonTraitRows(this);
                    }
                );
                applyButtonTraits(this);
                syncButtonTraitRows(this);
            },
        },
    });
}
