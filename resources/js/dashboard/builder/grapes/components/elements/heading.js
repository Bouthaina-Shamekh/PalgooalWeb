/**
 * ------------------------------------------------------------------
 * Component: PG Heading (Elementor-like)
 * File: components/elements/heading.js
 * ------------------------------------------------------------------
 * مسؤول عن:
 * - تعريف عنصر Heading
 * - Traits (Text / Link / HTML Tag)
 * - تطبيق التغييرات بدون تداخل
 * ------------------------------------------------------------------
 */

export function registerHeadingElement(editor) {
    const dc = editor.DomComponents;

    dc.addType('pg-heading', {
        /**
         * --------------------------------------------------------------
         * Detect existing HTML headings
         * --------------------------------------------------------------
         */
        isComponent: (el) => {
            if (!el || !el.tagName) return false;

            const tag = el.tagName.toLowerCase();
            const allowed = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p'];

            const name = el.getAttribute?.('data-gjs-name') || '';
            const marked =
                name.toLowerCase() === 'heading' ||
                el.classList?.contains('pg-heading');

            return allowed.includes(tag) && (tag.startsWith('h') || marked);
        },

        model: {
            defaults: {
                tagName: 'h2',
                name: 'Heading',

                attributes: {
                    class:
                        'pg-heading text-3xl sm:text-4xl font-extrabold text-primary tracking-tight',
                    'data-gjs-name': 'Heading',
                },

                // النص دائماً Child (أفضل تحكم)
                components: [{ type: 'text', content: 'عنوان' }],

                // Internal props (Traits state)
                pgText: 'عنوان',
                pgHref: '',
                pgTag: 'h2',

                traits: [
                    {
                        type: 'text',
                        name: 'pgText',
                        label: 'النص',
                        changeProp: 1,
                        placeholder: 'اكتب العنوان هنا',
                    },
                    {
                        type: 'text',
                        name: 'pgHref',
                        label: 'الرابط',
                        changeProp: 1,
                        placeholder: 'https://example.com',
                    },
                    {
                        type: 'select',
                        name: 'pgTag',
                        label: 'وسم HTML',
                        changeProp: 1,
                        options: [
                            { id: 'h1', name: 'H1' },
                            { id: 'h2', name: 'H2' },
                            { id: 'h3', name: 'H3' },
                            { id: 'h4', name: 'H4' },
                            { id: 'h5', name: 'H5' },
                            { id: 'h6', name: 'H6' },
                            { id: 'div', name: 'DIV' },
                            { id: 'span', name: 'SPAN' },
                            { id: 'p', name: 'P' },
                        ],
                    },
                ],
            },

            /**
             * --------------------------------------------------------------
             * Init
             * --------------------------------------------------------------
             */
            init() {
                // Sync initial HTML → traits
                this.syncFromDom();

                const apply = () => applyHeadingTraits(this);

                this.on(
                    'change:pgText change:pgHref change:pgTag',
                    apply
                );

                apply();
            },

            /**
             * --------------------------------------------------------------
             * Read existing DOM into traits (important!)
             * --------------------------------------------------------------
             */
            syncFromDom() {
                const tag = (this.get('tagName') || 'h2').toLowerCase();
                this.set('pgTag', tag, { silent: true });

                const attrs = this.getAttributes?.() || {};
                if (attrs.href) {
                    this.set('pgHref', attrs.href, { silent: true });
                }

                const first = this.components()?.at?.(0);
                const text =
                    first?.is?.('text') ? first.get('content') : '';

                if (text) {
                    this.set('pgText', text, { silent: true });
                }
            },
        },
    });
}

/**
 * ------------------------------------------------------------------
 * Apply Heading Traits (single source of truth)
 * ------------------------------------------------------------------
 */
function applyHeadingTraits(model) {
    const text = model.get('pgText') ?? '';
    const href = String(model.get('pgHref') ?? '').trim();
    const tag = String(model.get('pgTag') ?? 'h2').toLowerCase();

    /**
     * 1️⃣ Update tag safely
     */
    if (tag && model.get('tagName') !== tag) {
        model.set('tagName', tag);
    }

    const comps = model.components();

    const isLink = (cmp) =>
        cmp?.get?.('tagName')?.toLowerCase() === 'a';

    const setText = (parent) => {
        const children = parent.components();
        const first = children.at(0);

        if (first && first.is('text')) {
            first.set('content', text);
        } else {
            children.reset([{ type: 'text', content: text }]);
        }
    };

    /**
     * 2️⃣ With link → wrap with <a>
     */
    if (href) {
        const first = comps.at(0);

        if (!first || !isLink(first)) {
            comps.reset([
                {
                    type: 'default',
                    tagName: 'a',
                    attributes: {
                        href,
                        class: 'hover:underline underline-offset-2',
                    },
                    components: [{ type: 'text', content: text }],
                },
            ]);
        } else {
            first.addAttributes({ href });
            setText(first);
        }
    }

    /**
     * 3️⃣ Without link → plain text
     */
    else {
        const first = comps.at(0);

        if (first && isLink(first)) {
            comps.reset([{ type: 'text', content: text }]);
        } else {
            setText(model);
        }
    }
}
