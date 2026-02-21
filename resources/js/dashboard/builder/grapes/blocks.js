import { registerAllComponents } from './components';

const ensureClass = (cls) => (cls ? String(cls).trim() : '');
const splitClasses = (cls) => ensureClass(cls).split(/\s+/).filter(Boolean);

function removeByPrefixes(classList, prefixes = []) {
    return classList.filter((c) => !prefixes.some((p) => c.startsWith(p)));
}

function applyRowLayoutClasses(model) {
    // اقرأ القيم من Props (Traits)
    const mode = model.get('pgMode') || 'grid';
    const cols = model.get('pgCols') || '3';
    const gap = model.get('pgGap') || '6';
    const items = model.get('pgItems') || 'stretch';
    const justify = model.get('pgJustify') || 'start';
    const wrap = model.get('pgWrap') || 'wrap';

    // الكلاسات الأساسية
    const base = ['pg-layout'];

    // امسح أي كلاسات layout قديمة (حتى لا تتراكم)
    const old = (model.getAttributes()?.class || '').split(/\s+/).filter(Boolean);
    const cleaned = old.filter((c) => {
        // إزالة grid/flex related
        if (c === 'grid' || c === 'flex') return false;
        if (c.startsWith('grid-cols-')) return false;
        if (c.startsWith('gap-')) return false;
        if (c.startsWith('items-')) return false;
        if (c.startsWith('justify-')) return false;
        if (c === 'flex-wrap' || c === 'flex-nowrap') return false;
        return true;
    });

    // ابني كلاسات جديدة حسب الوضع
    if (mode === 'flex') {
        base.push('flex');
        base.push(wrap === 'nowrap' ? 'flex-nowrap' : 'flex-wrap');
        base.push(`gap-${gap}`);
        base.push(`items-${items}`);
        base.push(`justify-${justify}`);
    } else {
        base.push('grid');
        base.push(`grid-cols-${cols}`);
        base.push(`gap-${gap}`);
        // في grid: items- و justify- ينطبقوا أيضًا
        base.push(`items-${items}`);
        base.push(`justify-${justify}`);
    }

    // طبّق الكلاسات على attributes
    model.addAttributes({
        class: [...cleaned, ...base].join(' ').trim(),
    });
}


// export function registerBlocks(editor) {
//     registerAllComponents(editor);
//     registerHeadingElement(editor);
//     registerServicesSection(editor);

//     const bm = editor.BlockManager;
//     // bm.add('pg-container', { ... });
//     bm.add('pg-row', { label: 'Row (Grid/Flex)', category: 'Layout', content: { type: 'pg-row' } });
//     bm.add('pg-heading', { label: 'Heading', category: 'Basic', content: { type: 'pg-heading' } });

//     /**
//      * ------------------------------------------------------------------
//      * Component: PG Row (Grid/Flex)
//      * ------------------------------------------------------------------
//      */
// //     editor.DomComponents.addType('pg-row', {
// //         model: {
// //             defaults: {
// //                 tagName: 'div',
// //                 name: 'Row',
// //                 attributes: { class: 'pg-layout grid grid-cols-3 gap-6' },


// //                 // Starter content: 3 lightweight columns
// //                 components: [
// //                     { type: 'default', tagName: 'div', attributes: { class: 'pg-layout min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' }, components: [{ type: 'text', content: 'Column 1' }] },
// //                     { type: 'default', tagName: 'div', attributes: { class: 'pg-layout min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' }, components: [{ type: 'text', content: 'Column 2' }] },
// //                     { type: 'default', tagName: 'div', attributes: { class: 'pg-layout min-h-16 rounded-xl border border-slate-200 bg-white/80 p-4' }, components: [{ type: 'text', content: 'Column 3' }] },
// //                 ],

// //                 // Internal state (not HTML attributes)
// //                 pgMode: 'grid',
// //                 pgCols: '3',
// //                 pgGap: '6',
// //                 pgItems: 'stretch',
// //                 pgJustify: 'start',
// //                 pgWrap: 'wrap',

// //                 traits: [
// //                     { type: 'select', name: 'pgMode', label: 'Layout', options: [{ id: 'grid', name: 'Grid' }, { id: 'flex', name: 'Flex' }], changeProp: 1 },
// //                     { type: 'select', name: 'pgCols', label: 'Columns (Grid)', options: ['1', '2', '3', '4', '5', '6'].map(v => ({ id: v, name: v })), changeProp: 1 },
// //                     { type: 'select', name: 'pgGap', label: 'Gap', options: ['0', '2', '3', '4', '6', '8', '10', '12'].map(v => ({ id: v, name: v })), changeProp: 1 },
// //                     {
// //                         type: 'select', name: 'pgItems', label: 'Items',
// //                         options: [
// //                             { id: 'start', name: 'Start' }, { id: 'center', name: 'Center' },
// //                             { id: 'end', name: 'End' }, { id: 'stretch', name: 'Stretch' },
// //                         ],
// //                         changeProp: 1,
// //                     },
// //                     {
// //                         type: 'select', name: 'pgJustify', label: 'Justify',
// //                         options: [
// //                             { id: 'start', name: 'Start' }, { id: 'center', name: 'Center' },
// //                             { id: 'end', name: 'End' }, { id: 'between', name: 'Between' },
// //                             { id: 'around', name: 'Around' }, { id: 'evenly', name: 'Evenly' },
// //                         ],
// //                         changeProp: 1,
// //                     },
// //                     { type: 'select', name: 'pgWrap', label: 'Wrap (Flex)', options: [{ id: 'wrap', name: 'Wrap' }, { id: 'nowrap', name: 'No Wrap' }], changeProp: 1 },
// //                 ],
// //             },

// //             init() {
// //                 this.on('change:pgMode change:pgCols change:pgGap change:pgItems change:pgJustify change:pgWrap', () => {
// //                     applyRowLayoutClasses(this);
// //                 });
// //                 applyRowLayoutClasses(this);
// //             },

// //         },
// //     });

// //     /**
// //  * ------------------------------------------------------------------
// //  * Component: PG Heading (Elementor-like)
// //  * ------------------------------------------------------------------
// //  */
// //     editor.DomComponents.addType('pg-heading', {
// //         // ✅ مهم: تعرّف تلقائي على عناصر heading الموجودة
// //         isComponent: (el) => {
// //             if (!el || !el.tagName) return false;

// //             const tag = el.tagName.toLowerCase();
// //             const allowed = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p'];

// //             // تمييز إضافي: لو عليه data-gjs-name="Heading" أو class معينة
// //             const name = el.getAttribute?.('data-gjs-name') || '';
// //             const isMarked = name.toLowerCase() === 'heading' || el.classList?.contains('pg-heading');

// //             // اعتبره heading لو كان تاغ heading أو عليه علامة
// //             return allowed.includes(tag) && (tag.startsWith('h') || isMarked);
// //         },

// //         model: {
// //             defaults: {
// //                 tagName: 'h2',
// //                 name: 'Heading',
// //                 attributes: {
// //                     class: 'pg-heading text-3xl sm:text-4xl font-extrabold text-primary tracking-tight',
// //                     'data-gjs-name': 'Heading',
// //                 },

// //                 // نخلي النص child
// //                 components: [{ type: 'text', content: 'عنوان' }],

// //                 pgText: 'عنوان',
// //                 pgHref: '',
// //                 pgTag: 'h2',

// //                 traits: [
// //                     { type: 'text', name: 'pgText', label: 'العنوان', changeProp: 1, placeholder: 'اكتب العنوان هنا' },
// //                     { type: 'text', name: 'pgHref', label: 'رابط', changeProp: 1, placeholder: 'https://example.com' },
// //                     {
// //                         type: 'select',
// //                         name: 'pgTag',
// //                         label: 'وسم HTML',
// //                         changeProp: 1,
// //                         options: [
// //                             { id: 'h1', name: 'H1' }, { id: 'h2', name: 'H2' }, { id: 'h3', name: 'H3' },
// //                             { id: 'h4', name: 'H4' }, { id: 'h5', name: 'H5' }, { id: 'h6', name: 'H6' },
// //                             { id: 'div', name: 'DIV' }, { id: 'span', name: 'SPAN' }, { id: 'p', name: 'P' },
// //                         ],
// //                     },
// //                 ],
// //             },

// //             init() {
// //                 // ✅ لو العنصر جاء من HTML: اقرأ tag/text/href واملأ props
// //                 const tag = (this.get('tagName') || 'h2').toLowerCase();
// //                 this.set('pgTag', tag, { silent: true });

// //                 const attr = this.getAttributes?.() || {};
// //                 const href = attr.href || '';
// //                 if (href) this.set('pgHref', href, { silent: true });

// //                 // اقرأ النص الحالي
// //                 const txt = this.get('content') || this.toHTML?.() || '';
// //                 // أسهل: اقرأ أول text child
// //                 const first = this.components()?.at?.(0);
// //                 const childText = first?.is?.('text') ? (first.get?.('content') || '') : '';
// //                 if (childText) this.set('pgText', childText, { silent: true });

// //                 const apply = () => applyHeadingTraits(this);
// //                 this.on('change:pgText change:pgHref change:pgTag', apply);
// //                 apply();
// //             },
// //         },
// //     });

// //     function applyHeadingTraits(model) {
// //         const text = model.get('pgText') ?? '';
// //         const href = String(model.get('pgHref') ?? '').trim();
// //         const tag = String(model.get('pgTag') ?? 'h2').toLowerCase();

// //         if (tag && model.get('tagName') !== tag) model.set('tagName', tag);

// //         const comps = model.components();

// //         const isLink = (cmp) => cmp?.get?.('tagName')?.toLowerCase() === 'a';

// //         const setTextIn = (parent) => {
// //             const children = parent.components();
// //             const t = children.at(0);
// //             if (t && t.is && t.is('text')) t.set('content', text);
// //             else children.reset([{ type: 'text', content: text }]);
// //         };

// //         if (href) {
// //             const first = comps.at(0);
// //             if (!first || !isLink(first)) {
// //                 comps.reset([{
// //                     type: 'default',
// //                     tagName: 'a',
// //                     attributes: { href, class: 'hover:underline underline-offset-2' },
// //                     components: [{ type: 'text', content: text }],
// //                 }]);
// //             } else {
// //                 first.addAttributes({ href });
// //                 setTextIn(first);
// //             }
// //         } else {
// //             const first = comps.at(0);
// //             if (first && isLink(first)) {
// //                 comps.reset([{ type: 'text', content: text }]);
// //             } else {
// //                 setTextIn(model);
// //             }
// //         }
// //     }


// //     /**
// //      * تطبيق التغييرات على Heading:
// //      * - تحديث النص
// //      * - إضافة/إزالة رابط (wrap بـ <a>)
// //      * - تغيير tagName (h1..p..)
// //      */
// //     function applyHeadingTraits(model) {
// //         const text = model.get('pgText') ?? '';
// //         const href = String(model.get('pgHref') ?? '').trim();
// //         const tag = String(model.get('pgTag') ?? 'h2').toLowerCase();

// //         // 1) Update tagName safely
// //         if (tag && model.get('tagName') !== tag) {
// //             model.set('tagName', tag);
// //         }

// //         // 2) Get current children
// //         const comps = model.components();

// //         const isLinkWrapper = (cmp) => cmp?.get?.('tagName')?.toLowerCase() === 'a';

// //         // helper: set text into a child text node (inside link or directly)
// //         const setTextNode = (parentModel) => {
// //             const children = parentModel.components();
// //             const first = children.at(0);

// //             if (first && first.is && first.is('text')) {
// //                 first.set('content', text);
// //             } else {
// //                 children.reset([{ type: 'text', content: text }]);
// //             }
// //         };

// //         // 3) If href exists => ensure <a> wrapper
// //         if (href) {
// //             const first = comps.at(0);

// //             if (!first || !isLinkWrapper(first)) {
// //                 // create <a> and move text inside
// //                 comps.reset([
// //                     {
// //                         type: 'default',
// //                         tagName: 'a',
// //                         attributes: { href, class: 'underline-offset-2 hover:underline' },
// //                         components: [{ type: 'text', content: text }],
// //                     },
// //                 ]);
// //             } else {
// //                 // update href + inner text
// //                 first.addAttributes({ href });
// //                 setTextNode(first);
// //             }
// //         } else {
// //             // 4) No href => remove link wrapper if present
// //             const first = comps.at(0);

// //             if (first && isLinkWrapper(first)) {
// //                 comps.reset([{ type: 'text', content: text }]);
// //             } else {
// //                 setTextNode(model);
// //             }
// //         }
// //     }


// //     /**
// //      * ------------------------------------------------------------------
// //      * Blocks: Layout
// //      * ------------------------------------------------------------------
// //      */
// //     bm.add('pg-container', {
// //         label: 'Container',
// //         category: 'Layout',
// //         content: `
// //       <section class="pg-layout max-w-7xl mx-auto px-4 sm:px-8 lg:px-24 py-12" data-gjs-name="Container">
// //         <div class="pg-layout text-slate-600">Container content…</div>
// //       </section>
// //     `,
// //     });

// //     bm.add('pg-row', {
// //         label: 'Row (Grid/Flex)',
// //         category: 'Layout',
// //         content: { type: 'pg-row' },
// //     });

// //     bm.add('pg-card', {
// //         label: 'Card',
// //         category: 'Layout',
// //         content: `
// //       <div class="pg-layout rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-gjs-name="Card">
// //         <h3 class="pg-layout text-lg font-extrabold text-primary">Card title</h3>
// //         <p class="pg-layout mt-2 text-slate-600">Card content…</p>
// //       </div>
// //     `,
// //     });

// //     bm.add('pg-spacer', {
// //         label: 'Spacer',
// //         category: 'Layout',
// //         content: `<div class="pg-layout h-10" data-gjs-name="Spacer"></div>`,
// //     });

// //     bm.add('pg-divider', {
// //         label: 'Divider',
// //         category: 'Layout',
// //         content: `<hr class="pg-layout my-8 border-slate-200" data-gjs-name="Divider" />`,
// //     });

// //     /**
// //      * ------------------------------------------------------------------
// //      * Blocks: Basic
// //      * ------------------------------------------------------------------
// //      */
// //     bm.add('pg-heading', {
// //         label: 'Heading',
// //         category: 'Basic',
// //         content: { type: 'pg-heading', content: 'عنوان' },
// //         // content: `<h2 class="pg-layout text-3xl sm:text-4xl font-extrabold text-primary tracking-tight" data-gjs-name="Heading">عنوان</h2>`,
// //     });

// //     bm.add('pg-text', {
// //         label: 'Text',
// //         category: 'Basic',
// //         content: `<p class="pg-layout text-slate-700 leading-relaxed" data-gjs-name="Text">اكتب النص هنا…</p>`,
// //     });

// //     bm.add('pg-button', {
// //         label: 'Button',
// //         category: 'Basic',
// //         content: `<a class="pg-layout inline-flex items-center justify-center rounded-xl px-6 py-3 font-bold bg-primary text-white hover:bg-primary/90 transition" href="#" data-gjs-name="Button">زر</a>`,
// //     });

// //     bm.add('pg-image', {
// //         label: 'Image',
// //         category: 'Basic',
// //         content: `<img class="pg-layout w-full max-w-full rounded-2xl border border-slate-200" src="https://via.placeholder.com/1200x600" alt="Image" data-gjs-name="Image" />`,
// //     });
// }
export function registerBlocks(editor) {
    // 1) register all component types first
    registerAllComponents(editor);

    // 2) add blocks
    const bm = editor.BlockManager;

    bm.add('pg-heading', {
        label: 'Heading',
        category: 'Basic',
        content: { type: 'pg-heading' },
    });
    bm.add('pg-text', {
        label: 'Text',
        category: 'Basic',
        content: { type: 'pg-text' },
    });
    bm.add('pg-button', {
        label: 'Button',
        category: 'Basic',
        content: { type: 'pg-button' },
    });
    bm.add('pg-image', {
        label: 'Image',
        category: 'Basic',
        content: { type: 'pg-image' },
    });

    bm.add('pg-container', {
        label: 'Container Layout',
        category: 'Layout',
        content: { type: 'pg-container' },
    });

    bm.add('pg-grid', {
        label: 'Grid',
        category: 'Layout',
        content: { type: 'pg-grid' },
    });

    bm.add('pg-row', {
        label: 'Row (Grid/Flex)',
        category: 'Layout',
        content: { type: 'pg-row' },
    });

    bm.add('pg-services', {
        label: 'الخدمات (Swiper)',
        category: 'Sections',
        content: { type: 'pg-services-section' },
    });

    // bm.add('pg-slider', {
    //     label: 'slider',
    //     category: 'Sections',
    //     content: { type: 'pg-slider-section' },
    // });
    bm.add('pg-slider', {
        label: 'Slider',
        category: 'Sections',
        content: {
            type: 'pg-slider-section',
            // تأكد من إضافة الكلاس هنا أيضاً لضمان التعرف عليه فور الإسقاط
            classes: ['pg-slider-wrapper']
        },
    });

}


export function registerBlocksInBox(editor) {
    editor.on('block:custom', ({ blocks, dragStart, dragStop }) => {
        const map = {
            Basic: document.querySelector('#blocks-basic'),
            Layout: document.querySelector('#blocks-layout'),
            Sections: document.querySelector('#blocks-sections'),
        };

        // تنظيف الحاويات
        Object.values(map).forEach(el => el && (el.innerHTML = ''));

        blocks.forEach(block => {
            const cat = block.get('category');
            const catId = cat?.id || cat || 'Basic';
            const host = map[catId];
            if (!host) return;

            const label = block.get('label') || '';

            const el = document.createElement('div');
            el.className =
                'gjs-block gjs-one-bg gjs-four-color-h pg-widget-tile';

            el.setAttribute('title', label);
            el.setAttribute('draggable', true);
            el.setAttribute('tabindex', '0');

            // هذه التي يعتمد عليها البحث
            el.setAttribute('data-widget-item', '1');
            el.setAttribute('data-widget-name', label);
            el.setAttribute('aria-label', label);

            el.innerHTML = `
                    <div class="gjs-block-label">
                        <div class="pg-block-card">
                        <div class="pg-block-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                            <path d="M6 7h12M6 12h12M6 17h12"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="pg-block-title">${label}</div>
                        </div>
                    </div>
                    `;


            // drag & drop الرسمي
            el.addEventListener('mousedown', () => dragStart(block));
            el.addEventListener('mouseup', () => dragStop());

            host.appendChild(el);
        });
    });

}
