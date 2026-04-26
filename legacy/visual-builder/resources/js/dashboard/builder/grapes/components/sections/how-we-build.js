const HOW_BUILD_SECTION_CLASS = 'pg-how-build-section';
const HOW_BUILD_SECTION_KEY = 'how-we-build';
const HOW_BUILD_LAYOUT_MARKER = 'data-pg-how-build-layout';
const HOW_BUILD_LAYOUT_VERSION = '3';
const HOW_BUILD_ROOT_MARKER = 'data-pg-how-build-root';
const HOW_BUILD_MAIN_MARKER = 'data-pg-how-build-main';

const HOW_BUILD_STEPS = [
    {
        title: 'Analysis',
        iconClass: 'ti ti-chart-bubble',
        accent: false,
    },
    {
        title: 'UX/UI',
        iconClass: 'ti ti-brush',
        accent: false,
    },
    {
        title: 'Development',
        iconClass: 'ti ti-code',
        accent: false,
    },
    {
        title: 'Testing And Review',
        iconClass: 'ti ti-search',
        accent: false,
    },
    {
        title: 'Launch',
        iconClass: 'ti ti-rocket',
        accent: true,
    },
];

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

function createIcon({ iconClass, className, ariaLabel, style = {}, align = 'center' }) {
    return {
        type: 'pg-icon',
        attributes: {
            class: mergeClasses('pg-icon', className),
            'data-gjs-name': 'Icon',
            'data-pg-icon-class': iconClass,
            'aria-label': ariaLabel,
        },
        style,
        pgIconPreset: 'custom',
        pgIconClass: iconClass,
        pgAlign: align,
        pgLinkType: 'none',
        pgHref: '',
        pgTarget: 'self',
        pgAriaLabel: ariaLabel,
    };
}

function createStepCard(step, index) {
    const isLast = index === HOW_BUILD_STEPS.length - 1;
    const accent = step.accent === true;

    const components = [
        createIcon({
            iconClass: step.iconClass,
            ariaLabel: step.title,
            className: accent ? 'text-3xl text-white' : 'text-3xl text-secondary',
            align: 'center',
        }),
        createText({
            tag: 'span',
            text: step.title,
            className: accent
                ? 'text-center text-base font-medium text-white'
                : 'text-center text-base font-medium text-primary',
        }),
    ];

    return createContainer({
        name: `Step ${index + 1}`,
        className: mergeClasses(
            'rounded-[28px] p-6 shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl',
            accent ? 'pg-how-build-step-accent' : 'pg-how-build-step'
        ),
        style: {
            position: 'relative',
        },
        layout: 'flex',
        items: 'center',
        justify: 'center',
        flexDir: 'col',
        wrap: 'nowrap',
        gapX: '14',
        gapY: '14',
        minHeight: '150',
        bgType: 'color',
        bgColor: accent ? '#ba112c' : '#ffffff',
        components,
    });
}

function createStepsGrid() {
    return createContainer({
        name: 'Steps Grid',
        layout: 'grid',
        style: { position: 'relative' },
        cols: '5',
        rows: '1',
        gapX: '28',
        gapY: '48',
        colsTablet: '3',
        colsMobile: '1',
        rowsTablet: '2',
        rowsMobile: '5',
        gapXTablet: '24',
        gapXMobile: '20',
        gapYTablet: '32',
        gapYMobile: '28',
        components: HOW_BUILD_STEPS.map((step, index) => createStepCard(step, index)),
    });
}

function createHeaderStack() {
    return createContainer({
        name: 'Header Stack',
        layout: 'flex',
        flexDir: 'col',
        wrap: 'nowrap',
        items: 'center',
        justify: 'start',
        gapX: '12',
        gapY: '12',
        components: [
            createHeading({
                tag: 'h2',
                text: 'How We Build',
                className: 'text-center text-2xl font-extrabold text-white md:text-3xl',
            }),
            createText({
                tag: 'p',
                text: 'We Build with precision, passion, and purpose',
                className: 'text-center text-lg font-light text-white/80 md:text-xl',
            }),
        ],
    });
}

function createHowBuildMainContainer() {
    return createContainer({
        name: 'How We Build Main',
        attributes: { [HOW_BUILD_MAIN_MARKER]: '1' },
        contentWidth: 'boxed',
        boxedWidth: '1280',
        boxedWidthTablet: '1280',
        boxedWidthMobile: '1280',
        layout: 'flex',
        flexDir: 'col',
        wrap: 'nowrap',
        items: 'stretch',
        justify: 'start',
        gapX: '32',
        gapY: '64',
        gapXTablet: '24',
        gapXMobile: '20',
        gapYTablet: '48',
        gapYMobile: '36',
        components: [createHeaderStack(), createStepsGrid()],
    });
}

function createHowBuildWrapperContainer() {
    return createContainer({
        name: 'Container',
        className: 'pg-how-build-root',
        attributes: {
            [HOW_BUILD_ROOT_MARKER]: '1',
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
        components: [{ type: 'pg-how-build-section' }],
    });
}

function isHowBuildMainContainer(component) {
    const attrs = component?.getAttributes?.() || {};
    return (
        component?.get?.('type') === 'pg-container' &&
        String(attrs[HOW_BUILD_MAIN_MARKER] || '').trim() === '1'
    );
}

function ensureHowBuildSectionStructure(section) {
    if (!section?.addAttributes || !section?.components?.()) return;

    const attrs = section.getAttributes?.() || {};
    section.addAttributes({
        ...attrs,
        id: 'how-we-build',
        class: mergeClasses(
            attrs.class,
            HOW_BUILD_SECTION_CLASS,
            'overflow-hidden bg-primary px-4 py-16 text-center text-white sm:px-6 lg:px-12 lg:py-24'
        ),
        'data-gjs-name': 'How We Build Section',
        'data-pg-section': HOW_BUILD_SECTION_KEY,
        [HOW_BUILD_LAYOUT_MARKER]: HOW_BUILD_LAYOUT_VERSION,
    });

    const children = getChildren(section);
    if (children.length === 1 && isHowBuildMainContainer(children[0])) {
        return;
    }

    section.components().reset([createHowBuildMainContainer()]);
}

export function createHowBuildBlockContent() {
    return createHowBuildWrapperContainer();
}

function installHowBuildStyles(editor) {
    if (editor.__pgHowBuildStylesInstalled) return;
    editor.__pgHowBuildStylesInstalled = true;

    const css = editor.Css;
    if (!css) return;

    css.setRule(
        '.pg-how-build-step',
        {
            'clip-path': 'polygon(0 0, calc(100% - 34px) 0, 100% 50%, calc(100% - 34px) 100%, 0 100%)',
            'padding-right': '56px',
        },
        { addStyles: false }
    );

    css.setRule(
        'html[dir="rtl"] .pg-how-build-step',
        {
            'clip-path': 'polygon(34px 0, 100% 0, 100% 100%, 34px 100%, 0 50%)',
            'padding-left': '56px',
            'padding-right': '24px',
        },
        { addStyles: false }
    );

    css.setRule(
        '.pg-how-build-step .pg-icon, .pg-how-build-step-accent .pg-icon',
        {
            'margin-left': 'auto',
            'margin-right': 'auto',
        },
        { addStyles: false }
    );

    css.setRule(
        '.pg-how-build-step .pg-text, .pg-how-build-step-accent .pg-text',
        {
            'text-align': 'center',
            width: '100%',
        },
        { addStyles: false }
    );

    css.setRule(
        '.pg-how-build-step',
        {
            'clip-path': 'none',
            'padding-right': '24px',
            'padding-left': '24px',
        },
        {
            atRuleType: 'media',
            atRuleParams: '(max-width: 992px)',
            addStyles: false,
        }
    );
}

export function registerHowBuildSection(editor) {
    const dc = editor.DomComponents;

    installHowBuildStyles(editor);

    if (dc.getType('pg-how-build-section')) return;

    dc.addType('pg-how-build-section', {
        isComponent: (el) => {
            if (!el?.tagName) return false;
            const sectionKey = String(el.getAttribute?.('data-pg-section') || '').trim().toLowerCase();
            return sectionKey === HOW_BUILD_SECTION_KEY || el.classList?.contains(HOW_BUILD_SECTION_CLASS);
        },

        model: {
            defaults: {
                tagName: 'section',
                name: 'How We Build Section',
                droppable: true,
                attributes: {
                    id: 'how-we-build',
                    class: 'pg-how-build-section overflow-hidden bg-primary px-4 py-16 text-center text-white sm:px-6 lg:px-12 lg:py-24',
                    'data-gjs-name': 'How We Build Section',
                    'data-pg-section': HOW_BUILD_SECTION_KEY,
                    [HOW_BUILD_LAYOUT_MARKER]: HOW_BUILD_LAYOUT_VERSION,
                },
                components: [createHowBuildMainContainer()],
            },

            init() {
                ensureHowBuildSectionStructure(this);
                deferFrame(() => ensureHowBuildSectionStructure(this));
            },
        },
    });
}
