export function registerRowElement(editor) {
    const dc = editor.DomComponents;

    function applyRowLayoutClasses(model) {
        const mode = model.get('pgMode') || 'grid';
        const cols = model.get('pgCols') || '3';
        const gap = model.get('pgGap') || '6';
        const items = model.get('pgItems') || 'stretch';
        const justify = model.get('pgJustify') || 'start';
        const wrap = model.get('pgWrap') || 'wrap';

        const old = (model.getAttributes()?.class || '').split(/\s+/).filter(Boolean);

        const cleaned = old.filter((c) => {
            if (c === 'grid' || c === 'flex') return false;
            if (c.startsWith('grid-cols-')) return false;
            if (c.startsWith('gap-')) return false;
            if (c.startsWith('items-')) return false;
            if (c.startsWith('justify-')) return false;
            if (c === 'flex-wrap' || c === 'flex-nowrap') return false;
            return true;
        });

        const base = ['pg-layout'];

        if (mode === 'flex') {
            base.push('flex', wrap === 'nowrap' ? 'flex-nowrap' : 'flex-wrap', `gap-${gap}`, `items-${items}`, `justify-${justify}`);
        } else {
            base.push('grid', `grid-cols-${cols}`, `gap-${gap}`, `items-${items}`, `justify-${justify}`);
        }

        model.addAttributes({ class: [...cleaned, ...base].join(' ').trim() });
    }

    dc.addType('pg-row', {
        model: {
            defaults: {
                tagName: 'div',
                name: 'Row',
                attributes: { class: 'pg-layout grid grid-cols-3 gap-6' },
                components: [
                    { type: 'default', tagName: 'div', attributes: { class: 'min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' }, components: [{ type: 'text', content: 'Column 1' }] },
                    { type: 'default', tagName: 'div', attributes: { class: 'min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' }, components: [{ type: 'text', content: 'Column 2' }] },
                    { type: 'default', tagName: 'div', attributes: { class: 'min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' }, components: [{ type: 'text', content: 'Column 3' }] },
                ],
                pgMode: 'grid',
                pgCols: '3',
                pgGap: '6',
                pgItems: 'stretch',
                pgJustify: 'start',
                pgWrap: 'wrap',
                traits: [
                    { type: 'select', name: 'pgMode', label: 'Layout', options: [{ id: 'grid', name: 'Grid' }, { id: 'flex', name: 'Flex' }], changeProp: 1 },
                    { type: 'select', name: 'pgCols', label: 'Columns (Grid)', options: ['1', '2', '3', '4', '5', '6'].map(v => ({ id: v, name: v })), changeProp: 1 },
                    { type: 'select', name: 'pgGap', label: 'Gap', options: ['0', '2', '3', '4', '6', '8', '10', '12'].map(v => ({ id: v, name: v })), changeProp: 1 },
                    { type: 'select', name: 'pgItems', label: 'Items', options: [{ id: 'start', name: 'Start' }, { id: 'center', name: 'Center' }, { id: 'end', name: 'End' }, { id: 'stretch', name: 'Stretch' }], changeProp: 1 },
                    { type: 'select', name: 'pgJustify', label: 'Justify', options: [{ id: 'start', name: 'Start' }, { id: 'center', name: 'Center' }, { id: 'end', name: 'End' }, { id: 'between', name: 'Between' }, { id: 'around', name: 'Around' }, { id: 'evenly', name: 'Evenly' }], changeProp: 1 },
                    { type: 'select', name: 'pgWrap', label: 'Wrap (Flex)', options: [{ id: 'wrap', name: 'Wrap' }, { id: 'nowrap', name: 'No Wrap' }], changeProp: 1 },
                ],
            },

            init() {
                this.on('change:pgMode change:pgCols change:pgGap change:pgItems change:pgJustify change:pgWrap', () => applyRowLayoutClasses(this));
                applyRowLayoutClasses(this);
            },
        },
    });
}
