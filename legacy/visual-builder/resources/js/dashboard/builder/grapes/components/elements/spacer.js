const SPACER_STYLABLE_PROPS = [
    'height',
    'margin',
    'padding',
    'background-color',
    'border-style',
    'border-width',
    'border-color',
    'border-radius',
];

const SPACER_UNITS = ['px', '%', 'vh', 'rem'];
const HEIGHT_BOUNDS_BY_UNIT = {
    px: { min: 0, max: 1200, step: 1, label: 'px' },
    '%': { min: 0, max: 100, step: 1, label: '%' },
    vh: { min: 0, max: 100, step: 1, label: 'vh' },
    rem: { min: 0, max: 120, step: 0.5, label: 'rem' },
};

function toNumber(value, fallback = 0) {
    const next = Number(value);
    return Number.isFinite(next) ? next : fallback;
}

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

function normalizeUnit(value) {
    const unit = String(value || '').trim().toLowerCase();
    return SPACER_UNITS.includes(unit) ? unit : 'px';
}

function parseDimension(value) {
    const source = String(value || '').trim();
    if (!source) return null;

    const match = source.match(/^(-?\d*\.?\d+)([a-z%]*)$/i);
    if (!match) return null;

    const next = Number(match[1]);
    if (!Number.isFinite(next)) return null;

    return {
        value: next,
        unit: normalizeUnit(match[2] || 'px'),
    };
}

function normalizeHeightByUnit(value, unit) {
    const bounds = HEIGHT_BOUNDS_BY_UNIT[unit] || HEIGHT_BOUNDS_BY_UNIT.px;
    return clamp(value, bounds.min, bounds.max);
}

function syncHeightTraitMeta(model, unit) {
    const trait = model?.getTrait?.('pgHeight');
    if (!trait) return;

    const bounds = HEIGHT_BOUNDS_BY_UNIT[unit] || HEIGHT_BOUNDS_BY_UNIT.px;
    const nextMeta = {
        min: bounds.min,
        max: bounds.max,
        step: bounds.step,
        unitLabel: bounds.label,
    };

    const hasChange =
        toNumber(trait.get('min'), 0) !== nextMeta.min ||
        toNumber(trait.get('max'), 0) !== nextMeta.max ||
        toNumber(trait.get('step'), 0) !== nextMeta.step ||
        String(trait.get('unitLabel') || '') !== nextMeta.unitLabel;

    if (hasChange) {
        trait.set(nextMeta);
    }
}

function refreshComponentView(model) {
    const render = model?.view?.render;
    if (typeof render !== 'function') return;

    if (typeof requestAnimationFrame === 'function') {
        requestAnimationFrame(() => render.call(model.view));
        return;
    }

    render.call(model.view);
}

function hydrateSpacerProps(model) {
    const style = model?.getStyle?.() || {};
    const parsed = parseDimension(style.height);

    model.set('pgHeight', parsed ? String(parsed.value) : '40', { silent: true });
    model.set('pgHeightUnit', parsed?.unit || 'px', { silent: true });
}

function applySpacerTraits(model) {
    const unit = normalizeUnit(model.get('pgHeightUnit'));
    syncHeightTraitMeta(model, unit);

    const rawHeight = toNumber(model.get('pgHeight'), 40);
    const height = normalizeHeightByUnit(rawHeight, unit);

    const style = {
        ...(model?.getStyle?.() || {}),
        height: `${height}${unit}`,
        width: '100%',
    };

    model.setStyle(style);
    if (String(model.get('pgHeight') || '') !== String(height)) {
        model.set('pgHeight', String(height));
    }
    if (String(model.get('pgHeightUnit') || '') !== unit) {
        model.set('pgHeightUnit', unit);
    }
    refreshComponentView(model);
}

export function registerSpacerElement(editor) {
    const dc = editor.DomComponents;

    dc.addType('pg-spacer', {
        isComponent: (el) => {
            if (!el || !el.tagName) return false;
            if (el.tagName.toLowerCase() !== 'div') return false;

            const name = String(el.getAttribute?.('data-gjs-name') || '').toLowerCase();
            return name === 'spacer' || el.classList?.contains('pg-spacer');
        },

        model: {
            defaults: {
                tagName: 'div',
                name: 'Spacer',
                droppable: false,
                editable: false,
                attributes: {
                    class: 'pg-spacer w-full',
                    'data-gjs-name': 'Spacer',
                    'aria-hidden': 'true',
                },
                stylable: SPACER_STYLABLE_PROPS,
                style: {
                    width: '100%',
                    height: '40px',
                },
                pgHeight: '40',
                pgHeightUnit: 'px',
                traits: [
                    {
                        type: 'pg-trait-heading',
                        name: 'pgSpacerMain',
                        label: 'Spacer',
                    },
                    {
                        type: 'select',
                        name: 'pgHeightUnit',
                        label: 'Height Unit',
                        changeProp: 1,
                        options: [
                            { id: 'px', name: 'px' },
                            { id: '%', name: '%' },
                            { id: 'vh', name: 'vh' },
                            { id: 'rem', name: 'rem' },
                        ],
                    },
                    {
                        type: 'pg-range',
                        name: 'pgHeight',
                        label: 'Height',
                        changeProp: 1,
                        min: 0,
                        max: 1200,
                        step: 1,
                        unitLabel: 'px',
                        value: 40,
                    },
                ],
            },

            init() {
                this.set('stylable', SPACER_STYLABLE_PROPS, { silent: true });
                hydrateSpacerProps(this);
                syncHeightTraitMeta(this, normalizeUnit(this.get('pgHeightUnit')));
                this.on('change:pgHeight change:pgHeightUnit', () => {
                    applySpacerTraits(this);
                });
                applySpacerTraits(this);
            },
        },
    });
}
