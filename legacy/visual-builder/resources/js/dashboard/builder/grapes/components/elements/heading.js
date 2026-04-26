const HEADING_ALLOWED_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p'];
const HEADING_LINK_TYPES = ['none', 'custom'];
const HEADING_TARGETS = ['self', 'blank'];
const TEXT_NODE_TYPE = 'textnode';
const HEADING_STYLABLE_PROPS = [
    'width',
    'max-width',
    'margin',
    'padding',
    'background-color',
    'color',
    'font-family',
    'font-size',
    'font-weight',
    'text-transform',
    'line-height',
    'letter-spacing',
    'text-align',
    'border-style',
    'border-width',
    'border-color',
    'border-radius',
    'text-shadow',
    '-webkit-text-stroke-width',
    '-webkit-text-stroke-color',
];

function normalizeText(value) {
    const text = String(value || '').trim();
    return text || 'Heading';
}

function normalizeTag(value) {
    const tag = String(value || '').trim().toLowerCase();
    return HEADING_ALLOWED_TAGS.includes(tag) ? tag : 'h2';
}

function normalizeLinkType(value) {
    const type = String(value || '').trim().toLowerCase();
    return HEADING_LINK_TYPES.includes(type) ? type : 'none';
}

function normalizeTarget(value) {
    const target = String(value || '').trim().toLowerCase();
    return HEADING_TARGETS.includes(target) ? target : 'self';
}

function normalizeUrl(value) {
    return String(value || '').trim();
}

function textFromRawContent(value) {
    const raw = String(value || '');
    if (!raw.trim()) return '';

    const withoutScripts = raw
        .replace(/<script[\s\S]*?<\/script>/gi, ' ')
        .replace(/<style[\s\S]*?<\/style>/gi, ' ');
    const withoutTags = withoutScripts.replace(/<[^>]+>/g, ' ');
    return withoutTags.replace(/\s+/g, ' ').trim();
}

function isRawTextNode(component) {
    if (!component) return false;

    if (component?.is?.(TEXT_NODE_TYPE)) return true;
    const type = String(component.get?.('type') || '').toLowerCase();
    return type === TEXT_NODE_TYPE;
}

function isAnyTextContentNode(component) {
    if (!component) return false;
    if (isRawTextNode(component)) return true;
    if (component?.is?.('text')) return true;

    const type = String(component.get?.('type') || '').toLowerCase();
    return type === 'text';
}

function collectTextFromComponent(component) {
    if (!component) return '';

    if (isAnyTextContentNode(component)) {
        return String(component.get('content') || '');
    }

    const children = component.components?.();
    if (!children?.length) {
        return textFromRawContent(component?.get?.('content'));
    }

    const chunks = [];
    children.each?.((child) => {
        const next = collectTextFromComponent(child);
        if (next) chunks.push(next);
    });

    if (!chunks.length) {
        const direct = textFromRawContent(component?.get?.('content'));
        if (direct) chunks.push(direct);
    }

    return chunks.join(' ').replace(/\s+/g, ' ').trim();
}

function normalizeLegacyQuotedText(value) {
    const raw = String(value || '').trim();
    if (!raw) return raw;
    if (
        (raw.startsWith('"') && raw.endsWith('"')) ||
        (raw.startsWith("'") && raw.endsWith("'"))
    ) {
        return raw.slice(1, -1).trim();
    }
    return raw;
}

function findFirstAnchorModel(component) {
    if (!component) return null;

    const tag = String(component.get?.('tagName') || '').toLowerCase();
    if (tag === 'a') return component;

    const children = component.components?.();
    if (!children?.length) return null;

    let found = null;
    children.each?.((child) => {
        if (found) return;
        found = findFirstAnchorModel(child);
    });

    return found;
}

function setModelAttributes(model, attrs) {
    if (typeof model?.setAttributes === 'function') {
        model.setAttributes(attrs);
        return;
    }
    model?.addAttributes?.(attrs);
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

    rows.forEach((row) => {
        row.style.display = visible ? '' : 'none';
    });
}

function syncHeadingTraitRows(model) {
    const linkType = normalizeLinkType(model?.get?.('pgLinkType'));
    const isCustom = linkType === 'custom';
    const apply = () => {
        setTraitRowVisible('pgHref', isCustom);
        setTraitRowVisible('pgTarget', isCustom);
    };

    apply();
    if (typeof requestAnimationFrame === 'function') requestAnimationFrame(apply);
    setTimeout(apply, 0);
}

function readHeadingText(model) {
    const first = model?.components?.()?.at?.(0);
    if (!first) return '';

    if (isAnyTextContentNode(first)) {
        return String(first.get('content') || '');
    }

    const firstTag = String(first.get?.('tagName') || '').toLowerCase();
    if (firstTag === 'a') {
        const textNode = first.components?.()?.at?.(0);
        if (isAnyTextContentNode(textNode)) return String(textNode.get('content') || '');
    }

    return collectTextFromComponent(model);
}

function readHeadingLink(model) {
    const first = model?.components?.()?.at?.(0);
    const firstTag = String(first?.get?.('tagName') || '').toLowerCase();
    const anchor = firstTag === 'a' ? first : findFirstAnchorModel(model);
    if (!anchor) return { href: '', target: 'self' };

    const attrs = anchor.getAttributes?.() || {};
    return {
        href: normalizeUrl(attrs.href),
        target: attrs.target === '_blank' ? 'blank' : 'self',
    };
}

function hydrateHeadingProps(model) {
    const tag = normalizeTag(model?.get?.('tagName'));
    const text = normalizeText(normalizeLegacyQuotedText(readHeadingText(model)));
    const link = readHeadingLink(model);

    model.set('pgTag', tag, { silent: true });
    model.set('pgText', text, { silent: true });
    model.set('pgHref', link.href, { silent: true });
    model.set('pgTarget', link.target, { silent: true });
    model.set('pgLinkType', link.href ? 'custom' : 'none', { silent: true });
}

function repairLegacyHeadingContent(model) {
    const content = normalizeLegacyQuotedText(collectTextFromComponent(model)).trim();
    if (!content) return;
    model.set('pgText', content, { silent: true });
}

function ensureTextNode(parent, text) {
    const children = parent?.components?.();
    if (!children) return;

    const first = children.at?.(0);
    if (isRawTextNode(first)) {
        first.set('content', text);
        return;
    }

    children.reset([{ type: TEXT_NODE_TYPE, content: text }]);
}

function applyHeadingTraits(model) {
    const text = normalizeText(model.get('pgText'));
    const tag = normalizeTag(model.get('pgTag'));
    const linkType = normalizeLinkType(model.get('pgLinkType'));
    const href = normalizeUrl(model.get('pgHref'));
    const target = normalizeTarget(model.get('pgTarget'));
    const hasCustomLink = linkType === 'custom' && !!href;

    if (model.get('tagName') !== tag) {
        model.set('tagName', tag);
    }

    const comps = model.components?.();
    if (!comps) return;

    const first = comps.at?.(0);
    const firstTag = String(first?.get?.('tagName') || '').toLowerCase();

    if (hasCustomLink) {
        if (!first || firstTag !== 'a') {
            const linkAttrs = {
                href,
                class: 'hover:underline underline-offset-2',
            };
            if (target === 'blank') {
                linkAttrs.target = '_blank';
                linkAttrs.rel = 'noopener noreferrer';
            }

            comps.reset([
                {
                    type: 'default',
                    tagName: 'a',
                    attributes: linkAttrs,
                    components: [{ type: TEXT_NODE_TYPE, content: text }],
                },
            ]);
            refreshComponentView(model);
            return;
        }

        const currentAttrs = first.getAttributes?.() || {};
        const nextAttrs = { ...currentAttrs, href };
        if (target === 'blank') {
            nextAttrs.target = '_blank';
            nextAttrs.rel = 'noopener noreferrer';
        } else {
            delete nextAttrs.target;
            delete nextAttrs.rel;
        }
        setModelAttributes(first, nextAttrs);
        ensureTextNode(first, text);
        refreshComponentView(model);
        return;
    }

    if (first && firstTag === 'a') {
        comps.reset([{ type: TEXT_NODE_TYPE, content: text }]);
        refreshComponentView(model);
        return;
    }

    ensureTextNode(model, text);
    refreshComponentView(model);
}

export function registerHeadingElement(editor) {
    const dc = editor.DomComponents;

    editor.on('component:selected', (component) => {
        const type = String(component?.get?.('type') || '');
        if (type !== 'pg-heading') return;
        syncHeadingTraitRows(component);
    });

    dc.addType('pg-heading', {
        isComponent: (el) => {
            if (!el || !el.tagName) return false;

            const tag = el.tagName.toLowerCase();
            const name = (el.getAttribute?.('data-gjs-name') || '').toLowerCase();
            const marked = name === 'heading' || el.classList?.contains('pg-heading');

            return HEADING_ALLOWED_TAGS.includes(tag) && (tag.startsWith('h') || marked);
        },

        model: {
            defaults: {
                tagName: 'h2',
                name: 'Heading',
                droppable: false,
                editable: false,
                attributes: {
                    class: 'pg-heading text-3xl sm:text-4xl font-extrabold text-primary tracking-tight',
                    'data-gjs-name': 'Heading',
                },
                stylable: HEADING_STYLABLE_PROPS,
                components: [{ type: TEXT_NODE_TYPE, content: 'Heading' }],

                pgText: 'Heading',
                pgTag: 'h2',
                pgLinkType: 'none',
                pgHref: '',
                pgTarget: 'self',

                traits: [
                    {
                        type: 'pg-trait-heading',
                        name: 'pgHeadingMain',
                        label: 'Heading',
                    },
                    {
                        type: 'text',
                        name: 'pgText',
                        label: 'Text',
                        changeProp: 1,
                        placeholder: 'Type heading text',
                    },
                    {
                        type: 'select',
                        name: 'pgTag',
                        label: 'HTML Tag',
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
                    {
                        type: 'pg-trait-heading',
                        name: 'pgHeadingLink',
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
                ],
            },

            init() {
                this.set('stylable', HEADING_STYLABLE_PROPS, { silent: true });
                repairLegacyHeadingContent(this);
                hydrateHeadingProps(this);
                this.on(
                    'change:pgText change:pgTag change:pgLinkType change:pgHref change:pgTarget',
                    () => {
                        applyHeadingTraits(this);
                        syncHeadingTraitRows(this);
                    }
                );
                applyHeadingTraits(this);
                syncHeadingTraitRows(this);
            },
        },
    });
}
