export function registerTextElement(editor) {
    const dc = editor.DomComponents;

    dc.addType('pg-text', {
        isComponent: (el) => {
            if (!el || !el.tagName) return false;
            const tag = el.tagName.toLowerCase();

            const name = (el.getAttribute?.('data-gjs-name') || '').toLowerCase();
            const isMarked = el.classList?.contains('pg-text') || name === 'text';

            // ✅ p فقط (كما تريد)
            return tag === 'p' && isMarked;
        },

        model: {
            defaults: {
                tagName: 'p',
                name: 'Text',
                attributes: {
                    class: 'pg-text text-slate-700 leading-relaxed text-right',
                    'data-gjs-name': 'Text',
                },

                // ✅ default HTML content
                components: [{ type: 'text', content: 'اكتب النص هنا…' }],

                // ✅ props
                pgHtml: 'اكتب النص هنا…',
                pgAlign: 'right',
                pgSize: 'base',
                pgWeight: 'normal',

                traits: [
                    // ✅ هذا هو محرر المحتوى الحقيقي
                    { type: 'pg-wysiwyg', name: 'pgHtml', label: 'المحتوى', changeProp: 1 },

                    {
                        type: 'select', name: 'pgAlign', label: 'المحاذاة', changeProp: 1, options: [
                            { id: 'right', name: 'يمين' },
                            { id: 'center', name: 'وسط' },
                            { id: 'left', name: 'يسار' },
                        ]
                    },

                    {
                        type: 'select', name: 'pgSize', label: 'الحجم', changeProp: 1, options: [
                            { id: 'sm', name: 'Small' },
                            { id: 'base', name: 'Base' },
                            { id: 'lg', name: 'Large' },
                            { id: 'xl', name: 'XL' },
                        ]
                    },

                    {
                        type: 'select', name: 'pgWeight', label: 'الوزن', changeProp: 1, options: [
                            { id: 'normal', name: 'Normal' },
                            { id: 'medium', name: 'Medium' },
                            { id: 'bold', name: 'Bold' },
                        ]
                    },
                ],
            },

            init() {
                // ✅ اقرأ HTML الداخلي عند التحميل (لو جاي من paste/load)
                const innerHtml = this.components()?.toHTML?.() || '';
                if (innerHtml && !this.get('pgHtml')) {
                    this.set('pgHtml', innerHtml, { silent: true });
                }

                const apply = () => applyTextTraits(this);

                this.on('change:pgHtml change:pgAlign change:pgSize change:pgWeight', apply);
                apply();
            },
        },
    });

    function applyTextTraits(model) {
        const html = model.get('pgHtml') ?? '';
        const align = model.get('pgAlign') ?? 'right';
        const size = model.get('pgSize') ?? 'base';
        const weight = model.get('pgWeight') ?? 'normal';

        // ✅ Inject HTML
        model.components(html);

        // ✅ classes cleanup (لا تمسح text-slate-700)
        const attrs = model.getAttributes() || {};
        const cls = (attrs.class || '').split(/\s+/).filter(Boolean);

        const cleaned = cls.filter((c) => {
            // remove only alignment classes
            if (c === 'text-left' || c === 'text-center' || c === 'text-right') return false;

            // remove only size we control (text-sm/base/lg/xl)
            if (c === 'text-sm' || c === 'text-base' || c === 'text-lg' || c === 'text-xl') return false;

            // remove weight we control
            if (c === 'font-normal' || c === 'font-medium' || c === 'font-bold') return false;

            return true;
        });

        const sizeClass = size === 'sm' ? 'text-sm'
            : size === 'lg' ? 'text-lg'
                : size === 'xl' ? 'text-xl'
                    : 'text-base';

        const weightClass = weight === 'bold' ? 'font-bold'
            : weight === 'medium' ? 'font-medium'
                : 'font-normal';

        const alignClass = align === 'left' ? 'text-left'
            : align === 'center' ? 'text-center'
                : 'text-right';

        model.addAttributes({
            class: [...cleaned, sizeClass, weightClass, alignClass].join(' ').trim(),
        });
    }
}
