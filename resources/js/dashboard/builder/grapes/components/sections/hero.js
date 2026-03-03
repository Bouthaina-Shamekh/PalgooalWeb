const HERO_IMAGE_PLACEHOLDER = `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="900" viewBox="0 0 1200 900"><rect width="1200" height="900" rx="64" fill="#f8fafc"/><circle cx="875" cy="240" r="138" fill="#e9d5ff"/><circle cx="305" cy="705" r="160" fill="#fee2e2"/><rect x="170" y="148" width="860" height="560" rx="42" fill="#ffffff" stroke="#ddd6fe" stroke-width="8"/><rect x="240" y="230" width="320" height="24" rx="12" fill="#4f46e5"/><rect x="240" y="283" width="240" height="16" rx="8" fill="#c4b5fd"/><rect x="240" y="336" width="160" height="16" rx="8" fill="#e11d48"/><rect x="240" y="392" width="320" height="15" rx="7.5" fill="#cbd5e1"/><rect x="240" y="434" width="286" height="15" rx="7.5" fill="#cbd5e1"/><rect x="240" y="476" width="238" height="15" rx="7.5" fill="#cbd5e1"/><rect x="650" y="240" width="260" height="300" rx="28" fill="#f3f4f6"/><rect x="690" y="292" width="160" height="16" rx="8" fill="#7c3aed"/><rect x="690" y="334" width="126" height="12" rx="6" fill="#c4b5fd"/><rect x="708" y="392" width="126" height="126" rx="24" fill="#ffffff"/><path d="M744 456l28 28 66-78" fill="none" stroke="#e11d48" stroke-width="18" stroke-linecap="round" stroke-linejoin="round"/></svg>'
)}`;

const HERO_FEATURES = [
    'Choose Your Template',
    'Website Hosting',
    'Control Panel',
    'Email Addresses',
    'Private Domain',
    '24/7 technical support',
    'Private Domain',
    '24/7 technical support',
];

const HERO_SECTION_CLASS = 'pg-hero-section';
const HERO_SECTION_KEY = 'hero-launch';
const HERO_LAYOUT_MARKER = 'data-pg-hero-layout';
const HERO_LAYOUT_VERSION = '5';
const HERO_CONTAINER_MARKER = 'data-pg-hero-container';
const HERO_ROOT_MARKER = 'data-pg-hero-root';

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

function walkComponents(component, visitor) {
    getChildren(component).forEach((child) => {
        visitor(child);
        walkComponents(child, visitor);
    });
}

function findFirstByName(component, name) {
    let match = null;

    walkComponents(component, (child) => {
        if (match) return;
        const childName = String(child?.getAttributes?.()?.['data-gjs-name'] || '').trim();
        if (childName === name) {
            match = child;
        }
    });

    return match;
}

function mergeComponentStyle(component, nextStyle) {
    if (!component?.setStyle) return;

    component.setStyle({
        ...(component.getStyle?.() || {}),
        ...nextStyle,
    });
}

function deferFrame(callback) {
    if (typeof requestAnimationFrame === 'function') {
        requestAnimationFrame(callback);
        return;
    }

    setTimeout(callback, 0);
}

function createContainer({
    name,
    className = '',
    style = {},
    attributes = {},
    tag = 'div',
    contentWidth = 'full',
    fullWidth = '100',
    boxedWidth = '1200',
    minHeight = '0',
    minHeightUnit = 'px',
    paddingX = 'none',
    paddingY = 'none',
    layout = 'flex',
    cols = '1',
    rows = '1',
    gapX = '0',
    gapY = '0',
    gapUnit = 'px',
    items = 'stretch',
    justify = 'start',
    flexDir = 'row',
    wrap = 'wrap',
    fullWidthTablet = fullWidth,
    fullWidthMobile = fullWidthTablet,
    boxedWidthTablet = boxedWidth,
    boxedWidthMobile = boxedWidthTablet,
    minHeightTablet = minHeight,
    minHeightMobile = minHeightTablet,
    colsTablet = cols,
    colsMobile = colsTablet,
    rowsTablet = rows,
    rowsMobile = rowsTablet,
    gapXTablet = gapX,
    gapXMobile = gapXTablet,
    gapYTablet = gapY,
    gapYMobile = gapYTablet,
    flexDirTablet = flexDir,
    flexDirMobile = flexDirTablet,
    wrapTablet = wrap,
    wrapMobile = wrapTablet,
    justifyTablet = justify,
    justifyMobile = justifyTablet,
    itemsTablet = items,
    itemsMobile = itemsTablet,
    bgType = 'none',
    bgColor = '#ffffff',
    bgImage = '',
    bgPosition = 'center center',
    bgSize = 'cover',
    bgRepeat = 'no-repeat',
    components = [],
    autoSeedDone = false,
}) {
    return {
        type: 'pg-container',
        attributes: {
            class: mergeClasses(
                'pg-layout',
                'pg-container',
                contentWidth === 'boxed' ? 'pg-content-boxed' : 'pg-content-full',
                'w-full',
                className
            ),
            'data-gjs-name': name || 'Container',
            'data-pg-content-width': contentWidth,
            ...attributes,
        },
        style,
        pgTag: tag,
        pgContentWidth: contentWidth,
        pgFullWidth: String(fullWidth),
        pgBoxedWidth: String(boxedWidth),
        pgMinHeight: String(minHeight),
        pgMinHeightUnit: minHeightUnit,
        pgPaddingX: paddingX,
        pgPaddingY: paddingY,
        pgLayout: layout,
        pgCols: String(cols),
        pgRows: String(rows),
        pgGap: String(gapX),
        pgGapX: String(gapX),
        pgGapY: String(gapY),
        pgGapUnit: gapUnit,
        pgGapLinked: String(gapX) === String(gapY),
        pgItems: items,
        pgJustify: justify,
        pgFlexDir: flexDir,
        pgWrap: wrap,
        pgAutoSeedDone: autoSeedDone,
        pgDevice: 'desktop',
        pgFullWidthTablet: String(fullWidthTablet),
        pgFullWidthMobile: String(fullWidthMobile),
        pgBoxedWidthTablet: String(boxedWidthTablet),
        pgBoxedWidthMobile: String(boxedWidthMobile),
        pgMinHeightTablet: String(minHeightTablet),
        pgMinHeightMobile: String(minHeightMobile),
        pgColsTablet: String(colsTablet),
        pgColsMobile: String(colsMobile),
        pgRowsTablet: String(rowsTablet),
        pgRowsMobile: String(rowsMobile),
        pgGapXTablet: String(gapXTablet),
        pgGapXMobile: String(gapXMobile),
        pgGapYTablet: String(gapYTablet),
        pgGapYMobile: String(gapYMobile),
        pgFlexDirTablet: flexDirTablet,
        pgFlexDirMobile: flexDirMobile,
        pgWrapTablet: wrapTablet,
        pgWrapMobile: wrapMobile,
        pgJustifyTablet: justifyTablet,
        pgJustifyMobile: justifyMobile,
        pgItemsTablet: itemsTablet,
        pgItemsMobile: itemsMobile,
        pgBgType: bgType,
        pgBgColor: bgColor,
        pgBgImage: bgImage,
        pgBgPosition: bgPosition,
        pgBgSize: bgSize,
        pgBgRepeat: bgRepeat,
        components,
    };
}

function createHeading({ tag = 'h2', text, className }) {
    return {
        type: 'pg-heading',
        tagName: tag,
        attributes: {
            class: mergeClasses('pg-heading', className),
            'data-gjs-name': 'Heading',
            dir: 'auto',
        },
        components: [{ type: 'textnode', content: text }],
        pgText: text,
        pgTag: tag,
    };
}

function createText({ tag = 'p', text, className }) {
    return {
        type: 'pg-text',
        tagName: tag,
        attributes: {
            class: mergeClasses('pg-text', className),
            'data-gjs-name': 'Text',
            dir: 'auto',
        },
        components: [{ type: 'textnode', content: text }],
        pgText: text,
        pgTag: tag,
        pgLinkType: 'none',
        pgHref: '',
        pgTarget: 'self',
    };
}

function createFeatureIcon() {
    return {
        type: 'pg-icon',
        attributes: {
            class: 'pg-icon text-2xl text-secondary',
            'data-gjs-name': 'Icon',
            'data-pg-icon-class': 'ti ti-check',
            'aria-label': 'Feature included',
        },
        style: {
            'margin-left': '0',
            'margin-right': '0',
        },
        pgIconPreset: 'check',
        pgIconClass: '',
        pgAlign: 'left',
        pgLinkType: 'none',
        pgHref: '',
        pgTarget: 'self',
        pgAriaLabel: 'Feature included',
    };
}

function createFeatureItem(label) {
    return createContainer({
        name: 'Feature Item',
        layout: 'flex',
        className: 'feature-item',
        style: { direction: 'inherit' },
        gapX: '12',
        gapY: '12',
        items: 'center',
        justify: 'start',
        flexDir: 'row',
        wrap: 'nowrap',
        components: [
            createFeatureIcon(),
            createText({
                tag: 'span',
                text: label,
                className: 'text-base font-medium text-slate-800 md:text-lg text-start',
            }),
        ],
    });
}

function createFeaturesGrid() {
    return createContainer({
        name: 'Features Grid',
        layout: 'grid',
        style: { direction: 'inherit' },
        cols: '2',
        rows: '4',
        gapX: '24',
        gapY: '20',
        colsTablet: '1',
        colsMobile: '1',
        rowsTablet: '8',
        rowsMobile: '8',
        gapXTablet: '20',
        gapXMobile: '16',
        gapYTablet: '16',
        gapYMobile: '16',
        components: HERO_FEATURES.map((label) => createFeatureItem(label)),
    });
}

function createHeroButton() {
    return {
        type: 'pg-button',
        attributes: {
            class: 'pg-button rounded-xl bg-primary px-8 py-3 text-base font-bold text-white shadow transition hover:bg-primary/90 md:text-lg',
            'data-gjs-name': 'Button',
            dir: 'auto',
        },
        style: {
            'margin-left': '0',
            'margin-right': '0',
        },
        components: [{ type: 'text', content: 'Choose Your Template' }],
        pgText: 'Choose Your Template',
        pgLinkType: 'none',
        pgBtnUrl: '',
        pgTarget: 'self',
        pgAlign: 'left',
    };
}

function createHeroImage() {
    return {
        type: 'pg-image',
        attributes: {
            src: HERO_IMAGE_PLACEHOLDER,
            alt: 'Programming Services Team',
            loading: 'lazy',
            class: 'pg-image object-cover rounded-none',
            'data-gjs-name': 'Image',
        },
        style: {
            width: '100%',
            'max-width': 'none',
            height: 'auto',
            'margin-left': '0',
            'margin-right': '0',
        },
        pgSrc: HERO_IMAGE_PLACEHOLDER,
        pgAlt: 'Programming Services Team',
        pgFit: 'cover',
        pgAlign: 'left',
        pgRounded: 'none',
        pgLoading: 'lazy',
        pgLinkType: 'none',
        pgCustomUrl: '',
    };
}

function createCtaRow() {
    return createContainer({
        name: 'CTA Row',
        layout: 'flex',
        style: {
            direction: 'inherit',
            'margin-top': '28px',
        },
        items: 'center',
        justify: 'start',
        flexDir: 'row',
        wrap: 'nowrap',
        components: [createHeroButton()],
    });
}

function createCopyColumn() {
    return createContainer({
        name: 'Hero Copy',
        layout: 'flex',
        style: { direction: 'inherit' },
        flexDir: 'col',
        wrap: 'nowrap',
        items: 'stretch',
        justify: 'start',
        components: [
            createHeading({
                tag: 'h1',
                text: 'in 5 Minutes',
                className: 'mb-4 text-center text-4xl font-extrabold leading-tight text-primary sm:text-5xl md:text-start md:text-6xl',
            }),
            createHeading({
                tag: 'h2',
                text: 'Launch your website at Minimal Cost',
                className: 'mb-4 text-center text-2xl font-bold leading-tight text-primary md:text-start md:text-3xl',
            }),
            createText({
                tag: 'p',
                text: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam.',
                className: 'mb-6 text-center text-base leading-8 text-slate-600 md:text-start md:text-lg',
            }),
            createHeading({
                tag: 'h3',
                text: 'The campaign includes:',
                className: 'mb-5 text-center text-xl font-bold text-primary md:text-start md:text-2xl',
            }),
            createFeaturesGrid(),
            createCtaRow(),
        ],
    });
}

function createImageShell() {
    return createContainer({
        name: 'Image Shell',
        layout: 'flex',
        className: 'overflow-hidden rounded-[2rem] bg-white p-2 shadow-inner',
        items: 'stretch',
        justify: 'center',
        components: [createHeroImage()],
    });
}

function createMediaFrame() {
    return createContainer({
        name: 'Media Frame',
        layout: 'flex',
        className: 'rounded-3xl bg-primary/5 p-4 shadow-sm',
        items: 'stretch',
        justify: 'center',
        components: [createImageShell()],
    });
}

function createMediaLimit() {
    return createContainer({
        name: 'Media Limit',
        contentWidth: 'boxed',
        boxedWidth: '720',
        boxedWidthTablet: '720',
        boxedWidthMobile: '720',
        layout: 'flex',
        items: 'stretch',
        justify: 'center',
        components: [createMediaFrame()],
    });
}

function createMediaColumn() {
    return createContainer({
        name: 'Hero Media',
        layout: 'flex',
        style: { direction: 'inherit' },
        items: 'center',
        justify: 'end',
        components: [createMediaLimit()],
    });
}

function createHeroMainContainer() {
    return createContainer({
        name: 'Hero Main',
        attributes: { [HERO_CONTAINER_MARKER]: '1' },
        contentWidth: 'boxed',
        boxedWidth: '1200',
        layout: 'grid',
        cols: '2',
        rows: '1',
        gapX: '48',
        gapY: '32',
        colsTablet: '1',
        colsMobile: '1',
        rowsTablet: '2',
        rowsMobile: '2',
        gapXTablet: '32',
        gapXMobile: '24',
        gapYTablet: '24',
        gapYMobile: '20',
        components: [createCopyColumn(), createMediaColumn()],
    });
}

function createHeroWrapperContainer() {
    return createContainer({
        name: 'Container',
        tag: 'div',
        className: 'pg-hero-root',
        attributes: {
            [HERO_ROOT_MARKER]: '1',
        },
        contentWidth: 'full',
        fullWidth: '100',
        paddingX: 'none',
        paddingY: 'none',
        layout: 'flex',
        flexDir: 'col',
        wrap: 'nowrap',
        items: 'stretch',
        justify: 'start',
        bgType: 'color',
        bgColor: '#ffffff',
        components: [{ type: 'pg-hero-section' }],
    });
}

function isHeroMainContainer(component) {
    const attrs = component?.getAttributes?.() || {};
    return (
        component?.get?.('type') === 'pg-container' &&
        String(attrs[HERO_CONTAINER_MARKER] || '').trim() === '1'
    );
}

function ensureHeroSectionStructure(section) {
    if (!section?.addAttributes || !section?.components?.()) return;

    const attrs = section.getAttributes?.() || {};
    section.addAttributes({
        ...attrs,
        class: mergeClasses(attrs.class, HERO_SECTION_CLASS, 'overflow-hidden', 'bg-white', 'px-4', 'py-10', 'sm:px-6', 'lg:px-12'),
        'data-gjs-name': 'Hero Section',
        'data-pg-section': HERO_SECTION_KEY,
        [HERO_LAYOUT_MARKER]: HERO_LAYOUT_VERSION,
    });

    const currentCtaRow = findFirstByName(section, 'CTA Row');
    if (currentCtaRow) {
        mergeComponentStyle(currentCtaRow, { 'margin-top': '28px' });
    }

    const children = getChildren(section);
    if (children.length === 1 && isHeroMainContainer(children[0])) {
        return;
    }

    section.components().reset([createHeroMainContainer()]);
}

export function createHeroBlockContent() {
    return createHeroWrapperContainer();
}

export function registerHeroSection(editor) {
    const dc = editor.DomComponents;

    if (dc.getType('pg-hero-section')) return;

    dc.addType('pg-hero-section', {
        isComponent: (el) => {
            if (!el?.tagName) return false;
            const sectionKey = String(el.getAttribute?.('data-pg-section') || '').trim().toLowerCase();
            return sectionKey === HERO_SECTION_KEY || el.classList?.contains(HERO_SECTION_CLASS);
        },

        model: {
            defaults: {
                tagName: 'section',
                name: 'Hero Section',
                droppable: true,
                attributes: {
                    class: 'pg-hero-section overflow-hidden bg-white px-4 py-10 sm:px-6 lg:px-12',
                    'data-gjs-name': 'Hero Section',
                    'data-pg-section': HERO_SECTION_KEY,
                    [HERO_LAYOUT_MARKER]: HERO_LAYOUT_VERSION,
                },
                components: [createHeroMainContainer()],
            },

            init() {
                ensureHeroSectionStructure(this);
                deferFrame(() => ensureHeroSectionStructure(this));
            },
        },
    });
}
