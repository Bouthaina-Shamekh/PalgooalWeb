function classListFromString(value) {
    return String(value || '')
        .split(/\s+/)
        .filter(Boolean);
}

function cleanGridClasses(classes) {
    return classes.filter((c) => {
        if (c === 'grid' || c === 'flex') return false;
        if (c === 'pg-grid' || c === 'pg-layout') return false;
        if (c.startsWith('grid-cols-')) return false;
        if (c.startsWith('grid-rows-')) return false;
        if (c.startsWith('gap-')) return false;
        if (c.startsWith('gap-x-')) return false;
        if (c.startsWith('gap-y-')) return false;
        if (c.startsWith('items-')) return false;
        if (c.startsWith('justify-')) return false;
        return true;
    });
}

function hydrateGridProps(model) {
    const classes = classListFromString(model.getAttributes()?.class);

    const colsClass = classes.find((c) => c.startsWith('grid-cols-'));
    const rowsClass = classes.find((c) => c.startsWith('grid-rows-'));
    const gapClass = classes.find((c) => c.startsWith('gap-') && !c.startsWith('gap-x-') && !c.startsWith('gap-y-'));
    const gapXClass = classes.find((c) => c.startsWith('gap-x-'));
    const gapYClass = classes.find((c) => c.startsWith('gap-y-'));
    const itemsClass = classes.find((c) => c.startsWith('items-'));
    const justifyClass = classes.find((c) => c.startsWith('justify-'));

    if (colsClass) model.set('pgCols', colsClass.replace('grid-cols-', ''), { silent: true });
    if (rowsClass) model.set('pgRows', rowsClass.replace('grid-rows-', ''), { silent: true });
    else model.set('pgRows', 'none', { silent: true });

    if (gapXClass) model.set('pgGapX', gapXClass.replace('gap-x-', ''), { silent: true });
    else if (gapClass) model.set('pgGapX', gapClass.replace('gap-', ''), { silent: true });

    if (gapYClass) model.set('pgGapY', gapYClass.replace('gap-y-', ''), { silent: true });
    else if (gapClass) model.set('pgGapY', gapClass.replace('gap-', ''), { silent: true });

    if (itemsClass) model.set('pgItems', itemsClass.replace('items-', ''), { silent: true });
    if (justifyClass) model.set('pgJustify', justifyClass.replace('justify-', ''), { silent: true });
}

function applyGridLayoutClasses(model) {
    const cols = model.get('pgCols') || '3';
    const rows = model.get('pgRows') || 'none';
    const legacyGap = model.get('pgGap');
    const gapX = model.get('pgGapX') || legacyGap || '6';
    const gapY = model.get('pgGapY') || legacyGap || '6';
    const items = model.get('pgItems') || 'stretch';
    const justify = model.get('pgJustify') || 'start';

    const old = classListFromString(model.getAttributes()?.class);
    const cleaned = cleanGridClasses(old);

    const nextClasses = [
        ...cleaned,
        'pg-layout',
        'pg-grid',
        'grid',
        `grid-cols-${cols}`,
        rows !== 'none' ? `grid-rows-${rows}` : null,
        `gap-x-${gapX}`,
        `gap-y-${gapY}`,
        `items-${items}`,
        `justify-${justify}`,
    ].filter(Boolean);

    model.addAttributes({
        class: Array.from(new Set(nextClasses)).join(' ').trim(),
        'data-gjs-name': 'Grid',
    });
}

export function registerGridElement(editor) {
    const dc = editor.DomComponents;

    dc.addType('pg-grid', {
        isComponent: (el) => {
            if (!el || !el.tagName) return false;
            const name = (el.getAttribute?.('data-gjs-name') || '').toLowerCase();
            return name === 'grid' || el.classList?.contains('pg-grid');
        },

        model: {
            defaults: {
                tagName: 'div',
                name: 'Grid',
                attributes: {
                    class: 'pg-layout pg-grid grid grid-cols-3 gap-x-6 gap-y-6 items-stretch justify-start',
                    'data-gjs-name': 'Grid',
                },
                components: [
                    {
                        type: 'default',
                        tagName: 'div',
                        attributes: { class: 'min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' },
                        components: [{ type: 'text', content: 'Item 1' }],
                    },
                    {
                        type: 'default',
                        tagName: 'div',
                        attributes: { class: 'min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' },
                        components: [{ type: 'text', content: 'Item 2' }],
                    },
                    {
                        type: 'default',
                        tagName: 'div',
                        attributes: { class: 'min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' },
                        components: [{ type: 'text', content: 'Item 3' }],
                    },
                ],
                pgCols: '3',
                pgRows: 'none',
                pgGap: '6',
                pgGapX: '6',
                pgGapY: '6',
                pgItems: 'stretch',
                pgJustify: 'start',
                traits: [
                    {
                        type: 'select',
                        name: 'pgCols',
                        label: 'Columns',
                        options: ['1', '2', '3', '4', '5', '6'].map((v) => ({ id: v, name: v })),
                        changeProp: 1,
                    },
                    {
                        type: 'select',
                        name: 'pgRows',
                        label: 'Rows',
                        options: [{ id: 'none', name: 'Auto' }, '1', '2', '3', '4', '5', '6'].map((v) =>
                            typeof v === 'string' ? { id: v, name: v } : v
                        ),
                        changeProp: 1,
                    },
                    {
                        type: 'select',
                        name: 'pgGapX',
                        label: 'Gap X',
                        options: ['0', '2', '3', '4', '6', '8', '10', '12'].map((v) => ({ id: v, name: v })),
                        changeProp: 1,
                    },
                    {
                        type: 'select',
                        name: 'pgGapY',
                        label: 'Gap Y',
                        options: ['0', '2', '3', '4', '6', '8', '10', '12'].map((v) => ({ id: v, name: v })),
                        changeProp: 1,
                    },
                    {
                        type: 'select',
                        name: 'pgItems',
                        label: 'Items',
                        options: [
                            { id: 'start', name: 'Start' },
                            { id: 'center', name: 'Center' },
                            { id: 'end', name: 'End' },
                            { id: 'stretch', name: 'Stretch' },
                        ],
                        changeProp: 1,
                    },
                    {
                        type: 'select',
                        name: 'pgJustify',
                        label: 'Justify',
                        options: [
                            { id: 'start', name: 'Start' },
                            { id: 'center', name: 'Center' },
                            { id: 'end', name: 'End' },
                            { id: 'between', name: 'Between' },
                            { id: 'around', name: 'Around' },
                            { id: 'evenly', name: 'Evenly' },
                        ],
                        changeProp: 1,
                    },
                ],
            },

            init() {
                hydrateGridProps(this);
                this.on('change:pgCols change:pgRows change:pgGapX change:pgGapY change:pgItems change:pgJustify', () =>
                    applyGridLayoutClasses(this)
                );
                applyGridLayoutClasses(this);
            },
        },
    });
}
