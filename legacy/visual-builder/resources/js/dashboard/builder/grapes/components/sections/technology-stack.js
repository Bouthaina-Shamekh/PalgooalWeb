const TECH_STACK_SECTION_CLASS = 'pg-tech-stack-section';
const TECH_STACK_SECTION_KEY = 'technology-stack';
const TECH_STACK_LAYOUT_MARKER = 'data-pg-tech-stack-layout';
const TECH_STACK_LAYOUT_VERSION = '2';

const TECH_STACK_ITEMS = [
    {
        key: 'bootstrap',
        label: 'Bootstrap',
        title: 'Bootstrap',
        bgStart: '#7C3AED',
        bgEnd: '#4C1D95',
        textColor: '#FFFFFF',
    },
    {
        key: 'react',
        label: 'React JS',
        title: 'React JS',
        bgStart: '#020617',
        bgEnd: '#000000',
        textColor: '#E2F8FF',
    },
    {
        key: 'livewire',
        label: 'Livewire 4',
        title: 'LIVEWIRE 4',
        bgStart: '#020617',
        bgEnd: '#111827',
        textColor: '#FFFFFF',
    },
    {
        key: 'mysql',
        label: 'MySQL',
        title: 'MySQL',
        bgStart: '#1D4ED8',
        bgEnd: '#0F172A',
        textColor: '#FFFFFF',
    },
    {
        key: 'figma',
        label: 'Figma',
        title: 'Figma',
        bgStart: '#020617',
        bgEnd: '#000000',
        textColor: '#FFFFFF',
    },
    {
        key: 'tailwind',
        label: 'Tailwind CSS',
        title: 'Tailwind CSS',
        bgStart: '#0F172A',
        bgEnd: '#172554',
        textColor: '#FFFFFF',
    },
    {
        key: 'wordpress',
        label: 'WordPress',
        title: 'WordPress',
        bgStart: '#06B6D4',
        bgEnd: '#0EA5E9',
        textColor: '#FFFFFF',
    },
    {
        key: 'laravel',
        label: 'Laravel',
        title: 'Laravel',
        bgStart: '#FF3B30',
        bgEnd: '#FF1F1F',
        textColor: '#FFFFFF',
    },
    {
        key: 'flutter',
        label: 'Flutter',
        title: 'Flutter',
        bgStart: '#1D4ED8',
        bgEnd: '#1E3A8A',
        textColor: '#FFFFFF',
    },
];

function getLogoTraitName(key) {
    return `pgLogo_${String(key || '').trim()}`;
}

function getBridgePickerButton() {
    return document.querySelector('#gjs_bridge_picker button');
}

function resolveSelectedUrl(detail) {
    if (!detail || typeof detail !== 'object') return '';
    const firstItem = Array.isArray(detail.items) ? detail.items[0] : null;

    return String(
        firstItem?.url ||
            firstItem?.path ||
            detail.url ||
            detail.file?.url ||
            detail.file?.path ||
            detail.path ||
            (Array.isArray(detail.files) && detail.files[0]?.url) ||
            (Array.isArray(detail.files) && detail.files[0]?.path) ||
            ''
    ).trim();
}

function resolveSelectedLabel(detail, fallbackUrl = '') {
    if (detail && typeof detail === 'object') {
        const firstItem = Array.isArray(detail.items) ? detail.items[0] : null;
        const label = String(
            firstItem?.name ||
                firstItem?.title ||
                detail.name ||
                detail.file?.name ||
                detail.file?.title ||
                ''
        ).trim();

        if (label) return label;
    }

    const url = String(fallbackUrl || '').trim();
    if (!url) return 'Custom Logo';

    const fileName = decodeURIComponent(url.split('#')[0].split('?')[0].split('/').pop() || '')
        .replace(/\.[^.]+$/i, '')
        .replace(/[-_]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    return fileName || 'Custom Logo';
}

function slugifyLogoKey(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function splitClasses(value) {
    return String(value || '')
        .split(/\s+/)
        .filter(Boolean);
}

function mergeClasses(...values) {
    return Array.from(
        new Set(
            values.flatMap((value) => {
                if (Array.isArray(value)) return value;
                return splitClasses(value);
            })
        )
    ).join(' ');
}

function getChildren(component) {
    const children = [];
    component?.components?.()?.each?.((child) => children.push(child));
    return children;
}

function getTrackComponent(section) {
    return section?.find?.('[data-pg-tech-stack-track="1"]')?.[0] || null;
}

function findImageInCard(card) {
    return (
        getChildren(card).find((child) => {
            const type = String(child?.get?.('type') || '').trim().toLowerCase();
            const tag = String(child?.get?.('tagName') || '').trim().toLowerCase();
            return type === 'pg-image' || tag === 'img';
        }) || null
    );
}

function getLogoImageComponent(section, item, index) {
    const keyed = section?.find?.(`[data-pg-tech-stack-image="${item.key}"]`)?.[0];
    if (keyed) return keyed;

    const track = getTrackComponent(section);
    const cards = getChildren(track);
    const card = cards[index] || null;
    return card ? findImageInCard(card) : null;
}

function buildTechnologyStackTraits() {
    return [
        {
            type: 'pg-trait-heading',
            name: 'pgTechStackLogosHeading',
            title: 'Logos',
        },
        ...TECH_STACK_ITEMS.map((item) => ({
            type: 'media-picker',
            name: getLogoTraitName(item.key),
            label: item.label,
            changeProp: 1,
        })),
        {
            type: 'pg-trait-heading',
            name: 'pgTechStackManageHeading',
            title: 'Manage',
        },
        {
            type: 'pg-tech-stack-add-logo',
            name: 'pgTechStackAddLogo',
            label: 'Add Logo',
        },
    ];
}

function syncTechnologyStackNodeMetadata(section) {
    const track = getTrackComponent(section);
    if (!track?.addAttributes) return;

    const cards = getChildren(track);
    TECH_STACK_ITEMS.forEach((item, index) => {
        const card = cards[index];
        if (!card?.addAttributes) return;

        card.addAttributes({
            ...(card.getAttributes?.() || {}),
            'data-pg-tech-stack-card': item.key,
            'data-gjs-name': `${item.label} Card`,
        });

        const image = findImageInCard(card);
        if (!image?.addAttributes) return;

        image.addAttributes({
            ...(image.getAttributes?.() || {}),
            'data-pg-tech-stack-image': item.key,
            'data-gjs-name': `${item.label} Logo`,
        });
    });
}

function hydrateTechnologyStackTraitValues(section) {
    TECH_STACK_ITEMS.forEach((item, index) => {
        const image = getLogoImageComponent(section, item, index);
        const attrs = image?.getAttributes?.() || {};
        const currentSrc = String(image?.get?.('pgSrc') || attrs.src || createLogoPlaceholder(item)).trim();

        section.set(getLogoTraitName(item.key), currentSrc, { silent: true });
    });
}

function applyTechnologyStackTraitValues(section) {
    TECH_STACK_ITEMS.forEach((item, index) => {
        const nextSrc = String(section.get(getLogoTraitName(item.key)) || '').trim();
        const image = getLogoImageComponent(section, item, index);
        if (!image || !nextSrc) return;

        if (String(image.get?.('pgSrc') || '').trim() !== nextSrc) {
            image.set('pgSrc', nextSrc);
        }

        if (String(image.get?.('pgAlt') || '').trim() !== item.label) {
            image.set('pgAlt', item.label);
        }
    });
}

function escapeXml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function renderBootstrapGlyph() {
    return `
        <g transform="translate(24 20)">
            <rect x="18" y="18" width="78" height="78" rx="18" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.22)"/>
            <rect x="10" y="10" width="78" height="78" rx="18" fill="rgba(255,255,255,0.18)" stroke="rgba(255,255,255,0.24)"/>
            <rect x="0" y="0" width="78" height="78" rx="18" fill="rgba(255,255,255,0.24)" stroke="rgba(255,255,255,0.42)"/>
            <text x="39" y="53" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="38" font-weight="700">B</text>
        </g>
    `;
}

function renderReactGlyph() {
    return `
        <g transform="translate(26 18)" fill="none" stroke="#67E8F9" stroke-width="4.5">
            <ellipse cx="42" cy="34" rx="34" ry="13"/>
            <ellipse cx="42" cy="34" rx="34" ry="13" transform="rotate(60 42 34)"/>
            <ellipse cx="42" cy="34" rx="34" ry="13" transform="rotate(120 42 34)"/>
            <circle cx="42" cy="34" r="6" fill="#67E8F9" stroke="none"/>
        </g>
    `;
}

function renderLivewireGlyph() {
    return `
        <g transform="translate(22 18)">
            <circle cx="28" cy="28" r="22" fill="#F472B6"/>
            <circle cx="22" cy="22" r="7" fill="#FFFFFF"/>
            <circle cx="35" cy="21" r="4" fill="#111827"/>
            <path d="M6 58c9-8 17-12 26-12s17 4 26 12" stroke="#F472B6" stroke-width="8" stroke-linecap="round"/>
            <circle cx="18" cy="72" r="5" fill="#60A5FA"/>
            <circle cx="31" cy="78" r="4" fill="#A855F7"/>
            <circle cx="44" cy="72" r="5" fill="#EF4444"/>
        </g>
    `;
}

function renderMySqlGlyph() {
    return `
        <g transform="translate(26 18)">
            <path d="M10 44c18-19 40-25 64-18 6 2 12 5 18 10" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="4" stroke-linecap="round"/>
            <path d="M63 14c7 4 11 8 15 16" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="4" stroke-linecap="round"/>
            <circle cx="74" cy="30" r="2.5" fill="#FFFFFF" opacity="0.7"/>
        </g>
    `;
}

function renderFigmaGlyph() {
    return `
        <g transform="translate(28 18)">
            <rect x="0" y="0" width="24" height="24" rx="12" fill="#F24E1E"/>
            <rect x="0" y="24" width="24" height="24" rx="12" fill="#A259FF"/>
            <rect x="0" y="48" width="24" height="24" rx="12" fill="#1ABCFE"/>
            <rect x="24" y="0" width="24" height="24" rx="12" fill="#FF7262"/>
            <rect x="24" y="24" width="24" height="24" rx="12" fill="#0ACF83"/>
        </g>
    `;
}

function renderTailwindGlyph() {
    return `
        <g transform="translate(24 20)" fill="#38BDF8">
            <path d="M32 18c9 0 14 5 16 14-3-4-7-6-12-6-8 0-10 10-18 10-4 0-7-2-10-6 2 9 7 14 16 14 9 0 14-5 16-14-3 4-7 6-12 6-8 0-10-10-18-10-4 0-7 2-10 6 2-9 7-14 16-14z"/>
        </g>
    `;
}

function renderWordPressGlyph() {
    return `
        <g transform="translate(28 18)">
            <circle cx="36" cy="36" r="31" fill="none" stroke="rgba(255,255,255,0.82)" stroke-width="5"/>
            <text x="36" y="47" text-anchor="middle" fill="#FFFFFF" font-family="Georgia, serif" font-size="34" font-weight="700">W</text>
        </g>
    `;
}

function renderLaravelGlyph() {
    return `
        <g transform="translate(24 18)" fill="none" stroke="#FFFFFF" stroke-width="4.5" stroke-linejoin="round" stroke-linecap="round">
            <path d="M18 18l18-10 18 10v22l-18 10-18-10z"/>
            <path d="M54 18l18-10 18 10v22l-18 10-18-10z"/>
            <path d="M36 40l18-10 18 10"/>
            <path d="M36 40v22"/>
            <path d="M54 30v22"/>
        </g>
    `;
}

function renderFlutterGlyph() {
    return `
        <g transform="translate(24 18)" fill="#FFFFFF">
            <path d="M16 14L46 14 70 38 54 38 30 14z"/>
            <path d="M30 46L54 22 70 22 46 46z"/>
            <path d="M30 46L46 62 70 62 54 46z" opacity="0.86"/>
        </g>
    `;
}

function renderBrandGlyph(item) {
    switch (item.key) {
        case 'bootstrap':
            return renderBootstrapGlyph();
        case 'react':
            return renderReactGlyph();
        case 'livewire':
            return renderLivewireGlyph();
        case 'mysql':
            return renderMySqlGlyph();
        case 'figma':
            return renderFigmaGlyph();
        case 'tailwind':
            return renderTailwindGlyph();
        case 'wordpress':
            return renderWordPressGlyph();
        case 'laravel':
            return renderLaravelGlyph();
        case 'flutter':
            return renderFlutterGlyph();
        default:
            return '';
    }
}

function createLogoPlaceholder(item) {
    const title = escapeXml(item.title || item.label || 'Logo');
    const titleSize = title.length > 11 ? 23 : 28;
    const titleX = item.key === 'tailwind' ? 70 : 96;
    const titleAnchor = item.key === 'mysql' ? 'middle' : 'start';
    const titleXValue = item.key === 'mysql' ? 120 : titleX;
    const footer = item.key === 'react'
        ? '<text x="120" y="174" text-anchor="middle" fill="#FFFFFF" opacity="0.95" font-family="Arial, sans-serif" font-size="22" font-weight="700">React JS</text>'
        : '';
    const noise = `
        <circle cx="164" cy="26" r="18" fill="rgba(255,255,255,0.04)"/>
        <circle cx="24" cy="96" r="12" fill="rgba(255,255,255,0.05)"/>
        <circle cx="182" cy="102" r="10" fill="rgba(255,255,255,0.04)"/>
        <path d="M18 12c18 10 32 18 50 16" stroke="rgba(255,255,255,0.06)" stroke-width="4" stroke-linecap="round"/>
    `;

    const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="368" height="220" viewBox="0 0 368 220" fill="none">
            <defs>
                <linearGradient id="bg-${item.key}" x1="0" y1="0" x2="368" y2="220" gradientUnits="userSpaceOnUse">
                    <stop stop-color="${item.bgStart}"/>
                    <stop offset="1" stop-color="${item.bgEnd}"/>
                </linearGradient>
            </defs>
            <rect width="368" height="220" rx="30" fill="url(#bg-${item.key})"/>
            ${noise}
            ${renderBrandGlyph(item)}
            <text x="${titleXValue}" y="112" text-anchor="${titleAnchor}" fill="${item.textColor}" font-family="Arial, sans-serif" font-size="${titleSize}" font-weight="700">${title}</text>
            ${footer}
        </svg>
    `;

    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
}

function createLogoImage(item, source = '') {
    const src = String(source || createLogoPlaceholder(item)).trim() || createLogoPlaceholder(item);

    return {
        type: 'pg-image',
        attributes: {
            src,
            alt: item.label,
            loading: 'lazy',
            class: 'pg-image h-full w-full rounded-[24px] object-cover',
            'data-gjs-name': `${item.label} Logo`,
            'data-pg-tech-stack-image': item.key,
        },
        style: {
            width: '100%',
            'max-width': 'none',
            height: '100%',
            'margin-left': '0',
            'margin-right': '0',
        },
        pgSrc: src,
        pgAlt: item.label,
        pgFit: 'cover',
        pgAlign: 'left',
        pgRounded: 'xl',
        pgLoading: 'lazy',
        pgLinkType: 'none',
        pgCustomUrl: '',
    };
}

function createLogoCard(item, source = '') {
    return {
        type: 'default',
        tagName: 'div',
        name: `${item.label} Card`,
        draggable: false,
        droppable: false,
        selectable: true,
        attributes: {
            class: 'group relative h-[108px] w-[168px] shrink-0 snap-start overflow-hidden rounded-[24px] shadow-[0_12px_30px_rgba(15,23,42,0.12)] transition-transform duration-300 hover:-translate-y-1 sm:h-[110px] sm:w-[176px] lg:w-[184px]',
            'data-gjs-name': `${item.label} Card`,
            'data-pg-tech-stack-card': item.key,
        },
        components: [createLogoImage(item, source)],
    };
}

function createCustomLogoKey(section, label) {
    const track = getTrackComponent(section);
    const usedKeys = new Set(TECH_STACK_ITEMS.map((item) => item.key));

    getChildren(track).forEach((card) => {
        const key = String(card?.getAttributes?.()?.['data-pg-tech-stack-card'] || '').trim();
        if (key) usedKeys.add(key);
    });

    const baseKey = slugifyLogoKey(label) || 'custom-logo';
    let nextKey = baseKey;
    let counter = 2;

    while (usedKeys.has(nextKey)) {
        nextKey = `${baseKey}-${counter}`;
        counter += 1;
    }

    return nextKey;
}

function appendCustomLogoCard(section, { src, label }) {
    const track = getTrackComponent(section);
    const normalizedSrc = String(src || '').trim();
    if (!track?.append || !normalizedSrc) return null;

    const normalizedLabel = String(label || '').trim() || 'Custom Logo';
    const item = {
        key: createCustomLogoKey(section, normalizedLabel),
        label: normalizedLabel,
        title: normalizedLabel,
        bgStart: '#0F172A',
        bgEnd: '#111827',
        textColor: '#FFFFFF',
    };

    const appended = track.append(createLogoCard(item, normalizedSrc));
    const card = Array.isArray(appended) ? appended[0] : appended;
    const image = card ? findImageInCard(card) : null;

    card?.addAttributes?.({
        ...(card.getAttributes?.() || {}),
        'data-pg-tech-stack-card': item.key,
        'data-pg-tech-stack-custom': '1',
        'data-gjs-name': `${normalizedLabel} Card`,
    });

    image?.addAttributes?.({
        ...(image.getAttributes?.() || {}),
        'data-pg-tech-stack-image': item.key,
        'data-pg-tech-stack-custom': '1',
        'data-gjs-name': `${normalizedLabel} Logo`,
    });

    return card || null;
}

function createTrack() {
    return {
        type: 'default',
        tagName: 'div',
        name: 'Technology Stack Track',
        draggable: false,
        droppable: true,
        attributes: {
            dir: 'ltr',
            'data-pg-tech-stack-track': '1',
            class: 'pg-drag-scroll flex items-center justify-start gap-5 overflow-x-auto px-1 py-2 pb-4 scrollbar-hide select-none lg:gap-6',
            'data-gjs-name': 'Technology Stack Track',
        },
        components: TECH_STACK_ITEMS.map((item) => createLogoCard(item)),
    };
}

function createTechStackContent() {
    return [
        {
            type: 'default',
            tagName: 'div',
            name: 'Technology Stack Container',
            attributes: {
                class: 'mx-auto w-full max-w-[1680px]',
                'data-gjs-name': 'Technology Stack Container',
            },
            components: [createTrack()],
        },
    ];
}

function ensureTechnologyStackStructure(section) {
    if (!section?.addAttributes || !section?.components?.()) return;

    const attrs = section.getAttributes?.() || {};
    section.addAttributes({
        ...attrs,
        class: mergeClasses(
            attrs.class,
            TECH_STACK_SECTION_CLASS,
            'relative overflow-hidden bg-[#f3f4f6] px-4 py-10 sm:px-6 lg:px-12'
        ),
        'data-gjs-name': 'Technology Stack Section',
        'data-pg-section': TECH_STACK_SECTION_KEY,
        [TECH_STACK_LAYOUT_MARKER]: TECH_STACK_LAYOUT_VERSION,
    });

    const children = getChildren(section);
    if (!children.length) {
        section.components().reset(createTechStackContent());
    }

    syncTechnologyStackNodeMetadata(section);
}

export function createTechnologyStackBlockContent() {
    return { type: 'pg-tech-stack-section' };
}

export function registerTechnologyStackSection(editor) {
    const dc = editor.DomComponents;
    const tm = editor.TraitManager;

    tm.addType('pg-tech-stack-add-logo', {
        createInput({ trait }) {
            const hasBridge = !!getBridgePickerButton();
            const el = document.createElement('div');

            el.className = 'pg-tech-stack-add-logo-trait';
            el.innerHTML = `
                <div class="flex flex-col gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-2">
                    <button
                        type="button"
                        data-role="add-logo"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-bold transition-all hover:bg-blue-50 hover:text-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
                        ${hasBridge ? '' : 'disabled'}
                        title="${hasBridge ? 'Choose an image from the media library' : 'Media bridge is not available on this page'}"
                    >
                        ${hasBridge ? 'Add New Logo' : 'Library Unavailable'}
                    </button>
                    <p class="text-[10px] leading-5 text-slate-500">
                        Adds a new logo card to the end of the stack.
                    </p>
                </div>
            `;

            const addButton = el.querySelector('[data-role="add-logo"]');
            addButton?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const section = trait?.target || editor.getSelected();
                const bridgeButton = getBridgePickerButton();
                if (!section || !bridgeButton) return;

                if (typeof trait._pgTechStackPickerCleanup === 'function') {
                    trait._pgTechStackPickerCleanup();
                }

                const cleanup = () => {
                    window.removeEventListener('media-picker-confirmed', handleSelection);
                    window.removeEventListener('media-selected', handleSelection);
                    trait._pgTechStackPickerCleanup = null;
                };

                const handleSelection = (selectionEvent) => {
                    const detail = selectionEvent?.detail || {};
                    const eventTargetInputId = String(detail.targetInputId || '').trim();
                    if (eventTargetInputId && eventTargetInputId !== 'gjs_bridge_media_input') {
                        return;
                    }

                    const selectedUrl = resolveSelectedUrl(detail);
                    if (!selectedUrl) return;

                    cleanup();

                    appendCustomLogoCard(section, {
                        src: selectedUrl,
                        label: resolveSelectedLabel(detail, selectedUrl),
                    });

                    editor.select(section);
                    editor.trigger('change:canvasOffset');
                };

                trait._pgTechStackPickerCleanup = cleanup;
                window.addEventListener('media-picker-confirmed', handleSelection);
                window.addEventListener('media-selected', handleSelection);
                bridgeButton.click();
            });

            return el;
        },
    });

    if (dc.getType('pg-tech-stack-section')) return;

    dc.addType('pg-tech-stack-section', {
        isComponent: (el) => {
            if (!el?.tagName) return false;
            const sectionKey = String(el.getAttribute?.('data-pg-section') || '').trim().toLowerCase();
            return sectionKey === TECH_STACK_SECTION_KEY || el.classList?.contains(TECH_STACK_SECTION_CLASS);
        },

        model: {
            defaults: {
                tagName: 'section',
                name: 'Technology Stack Section',
                droppable: true,
                attributes: {
                    class: 'pg-tech-stack-section relative overflow-hidden bg-[#f3f4f6] px-4 py-10 sm:px-6 lg:px-12',
                    'data-gjs-name': 'Technology Stack Section',
                    'data-pg-section': TECH_STACK_SECTION_KEY,
                    [TECH_STACK_LAYOUT_MARKER]: TECH_STACK_LAYOUT_VERSION,
                },
                ...Object.fromEntries(
                    TECH_STACK_ITEMS.map((item) => [getLogoTraitName(item.key), createLogoPlaceholder(item)])
                ),
                traits: buildTechnologyStackTraits(),
                components: createTechStackContent(),
            },

            init() {
                ensureTechnologyStackStructure(this);
                hydrateTechnologyStackTraitValues(this);
                applyTechnologyStackTraitValues(this);

                this.on(
                    TECH_STACK_ITEMS.map((item) => `change:${getLogoTraitName(item.key)}`).join(' '),
                    () => applyTechnologyStackTraitValues(this)
                );
            },
        },
    });
}
