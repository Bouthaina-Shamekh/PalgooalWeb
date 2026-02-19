const CONTENT_WIDTH_CLASS_MAP = {
    boxed: 'w-full',
    full: 'w-full',
};

const PADDING_X_CLASS_MAP = {
    none: 'px-0',
    compact: 'px-4 sm:px-6 lg:px-8',
    comfortable: 'px-4 sm:px-8 lg:px-24',
};

const PADDING_Y_CLASS_MAP = {
    none: 'py-0',
    sm: 'py-8',
    md: 'py-12',
    lg: 'py-16',
};

const TAILWIND_GAP_TO_PX = {
    0: 0,
    1: 4,
    2: 8,
    3: 12,
    4: 16,
    5: 20,
    6: 24,
    7: 28,
    8: 32,
    9: 36,
    10: 40,
    11: 44,
    12: 48,
};

const MEDIA_QUERY_TABLET = '(max-width: 992px)';
const MEDIA_QUERY_MOBILE = '(max-width: 480px)';

const FLEX_ICON_ARROW_UP = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M12 19V5"></path>
    <path d="M7 10l5-5 5 5"></path>
</svg>
`;

const FLEX_ICON_ARROW_RIGHT = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M5 12h14"></path>
    <path d="M14 7l5 5-5 5"></path>
</svg>
`;

const FLEX_ICON_ARROW_DOWN = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M12 5v14"></path>
    <path d="M7 14l5 5 5-5"></path>
</svg>
`;

const FLEX_ICON_ARROW_LEFT = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M19 12H5"></path>
    <path d="M10 7l-5 5 5 5"></path>
</svg>
`;

const FLEX_ICON_JUSTIFY_START = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M6 5v14"></path>
    <path d="M10 8v8"></path>
    <path d="M14 6v12"></path>
</svg>
`;

const FLEX_ICON_JUSTIFY_CENTER = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M9 8v8"></path>
    <path d="M12 6v12"></path>
    <path d="M15 9v6"></path>
</svg>
`;

const FLEX_ICON_JUSTIFY_END = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M18 5v14"></path>
    <path d="M14 8v8"></path>
    <path d="M10 6v12"></path>
</svg>
`;

const FLEX_ICON_JUSTIFY_BETWEEN = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M6 5v14"></path>
    <path d="M18 5v14"></path>
    <path d="M12 8v8"></path>
</svg>
`;

const FLEX_ICON_JUSTIFY_AROUND = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 5v14"></path>
    <path d="M20 5v14"></path>
    <path d="M10 8v8"></path>
    <path d="M14 8v8"></path>
</svg>
`;

const FLEX_ICON_JUSTIFY_EVENLY = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 5v14"></path>
    <path d="M9 8v8"></path>
    <path d="M15 8v8"></path>
    <path d="M20 5v14"></path>
</svg>
`;

const FLEX_ICON_ALIGN_START = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 6h16"></path>
    <path d="M8 9v8"></path>
    <path d="M12 9v6"></path>
    <path d="M16 9v10"></path>
</svg>
`;

const FLEX_ICON_ALIGN_CENTER = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 12h16"></path>
    <path d="M8 8v8"></path>
    <path d="M12 9v6"></path>
    <path d="M16 7v10"></path>
</svg>
`;

const FLEX_ICON_ALIGN_END = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 18h16"></path>
    <path d="M8 9v8"></path>
    <path d="M12 11v6"></path>
    <path d="M16 7v10"></path>
</svg>
`;

const FLEX_ICON_ALIGN_STRETCH = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 6h16"></path>
    <path d="M4 18h16"></path>
    <path d="M8 7v10"></path>
    <path d="M12 7v10"></path>
    <path d="M16 7v10"></path>
</svg>
`;

const FLEX_ICON_WRAP = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M3 7h12a3 3 0 0 1 0 6H9"></path>
    <path d="M9 10l-3 3 3 3"></path>
    <path d="M3 17h6"></path>
</svg>
`;

const FLEX_ICON_NOWRAP = `
<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M3 7h18"></path>
    <path d="M3 17h18"></path>
    <path d="M9 10l-3 3 3 3"></path>
</svg>
`;

const FLEX_DIRECTION_OPTIONS = [
    { id: 'col-reverse', name: 'Up', icon: FLEX_ICON_ARROW_UP },
    { id: 'row', name: 'Right', icon: FLEX_ICON_ARROW_RIGHT },
    { id: 'col', name: 'Down', icon: FLEX_ICON_ARROW_DOWN },
    { id: 'row-reverse', name: 'Left', icon: FLEX_ICON_ARROW_LEFT },
];

const FLEX_JUSTIFY_OPTIONS = [
    { id: 'start', name: 'Start', icon: FLEX_ICON_JUSTIFY_START },
    { id: 'center', name: 'Center', icon: FLEX_ICON_JUSTIFY_CENTER },
    { id: 'end', name: 'End', icon: FLEX_ICON_JUSTIFY_END },
    { id: 'between', name: 'Between', icon: FLEX_ICON_JUSTIFY_BETWEEN },
    { id: 'around', name: 'Around', icon: FLEX_ICON_JUSTIFY_AROUND },
    { id: 'evenly', name: 'Evenly', icon: FLEX_ICON_JUSTIFY_EVENLY },
];

const FLEX_ITEMS_OPTIONS = [
    { id: 'start', name: 'Start', icon: FLEX_ICON_ALIGN_START },
    { id: 'center', name: 'Center', icon: FLEX_ICON_ALIGN_CENTER },
    { id: 'end', name: 'End', icon: FLEX_ICON_ALIGN_END },
    { id: 'stretch', name: 'Stretch', icon: FLEX_ICON_ALIGN_STRETCH },
];

const FLEX_WRAP_OPTIONS = [
    { id: 'wrap', name: 'Wrap', icon: FLEX_ICON_WRAP },
    { id: 'nowrap', name: 'No Wrap', icon: FLEX_ICON_NOWRAP },
];

function classListFromString(value) {
    return String(value || '')
        .split(/\s+/)
        .filter(Boolean);
}

function splitMappedClasses(map, key, fallbackKey) {
    return classListFromString(map[key] || map[fallbackKey]);
}

function toNumber(value, fallback = 0) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function parseCssDimension(raw) {
    const input = String(raw || '').trim();
    const match = input.match(/^(-?\d+(?:\.\d+)?)(px|%|vh|vw)?$/i);
    if (!match) return null;
    return {
        value: Number(match[1]),
        unit: (match[2] || 'px').toLowerCase(),
    };
}

function extractCssUrl(raw) {
    const input = String(raw || '').trim();
    if (!input || input === 'none') return '';
    const match = input.match(/^url\((['"]?)(.*?)\1\)$/i);
    return match ? String(match[2] || '').trim() : '';
}

function getEditorDir(model) {
    const canvasDir = model?.em?.Canvas?.getDocument?.()?.documentElement?.getAttribute?.('dir');
    const docDir = typeof document !== 'undefined' ? document.documentElement?.getAttribute?.('dir') : '';
    const bodyDir = typeof document !== 'undefined' ? document.body?.getAttribute?.('dir') : '';
    const dir = String(canvasDir || docDir || bodyDir || 'ltr').toLowerCase();
    return dir === 'rtl' ? 'rtl' : 'ltr';
}

function traitFlexDirToCssValue(flexDir, dir = 'ltr') {
    const value = String(flexDir || 'row');
    if (value === 'col') return 'column';
    if (value === 'col-reverse') return 'column-reverse';
    if (value === 'row-reverse') return dir === 'rtl' ? 'row' : 'row-reverse';
    return dir === 'rtl' ? 'row-reverse' : 'row';
}

function cssFlexDirToTraitValue(cssFlexDir, dir = 'ltr') {
    const value = String(cssFlexDir || '').toLowerCase();
    if (value === 'column') return 'col';
    if (value === 'column-reverse') return 'col-reverse';
    if (value === 'row-reverse') return dir === 'rtl' ? 'row' : 'row-reverse';
    if (value === 'row') return dir === 'rtl' ? 'row-reverse' : 'row';
    return 'row';
}

function cssFlexDirToClass(cssFlexDir) {
    if (cssFlexDir === 'column') return 'flex-col';
    if (cssFlexDir === 'column-reverse') return 'flex-col-reverse';
    if (cssFlexDir === 'row-reverse') return 'flex-row-reverse';
    return 'flex-row';
}

function traitItemsToCssValue(value) {
    if (value === 'start') return 'flex-start';
    if (value === 'end') return 'flex-end';
    if (value === 'center') return 'center';
    if (value === 'stretch') return 'stretch';
    return 'stretch';
}

function traitJustifyToCssValue(value) {
    if (value === 'start') return 'flex-start';
    if (value === 'end') return 'flex-end';
    if (value === 'center') return 'center';
    if (value === 'between') return 'space-between';
    if (value === 'around') return 'space-around';
    if (value === 'evenly') return 'space-evenly';
    return 'flex-start';
}

function cleanStylePayload(style) {
    const next = {};
    Object.entries(style || {}).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') return;
        next[key] = value;
    });
    return next;
}

function importantStylePayload(style) {
    const next = {};
    Object.entries(style || {}).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') return;

        const normalized = String(value).trim();
        if (!normalized) return;

        next[key] = /\!important\s*$/i.test(normalized)
            ? normalized
            : `${normalized} !important`;
    });
    return next;
}

function ensureContainerId(model) {
    const attrs = { ...(model.getAttributes?.() || {}) };
    let id = String(attrs.id || '').trim();

    if (!id) {
        const cid = String(model?.cid || Date.now()).replace(/[^a-zA-Z0-9_-]/g, '');
        id = `pg-container-${cid}`;
        model.addAttributes({ id });
    }

    return id;
}

function setResponsiveRule(model, selector, style, mediaQuery) {
    const em = model?.em;
    const css = em?.Css;
    if (!css) return;

    const payload = cleanStylePayload(style);
    const importantPayload = importantStylePayload(payload);
    const existing = css.getRule(selector, {
        atRuleType: 'media',
        atRuleParams: mediaQuery,
    });

    if (!Object.keys(importantPayload).length) {
        if (existing) css.remove(existing);
        return;
    }

    if (existing) css.remove(existing);

    css.setRule(selector, importantPayload, {
        atRuleType: 'media',
        atRuleParams: mediaQuery,
        addStyles: false,
    });
}

function normalizeColorValue(raw, fallback = '#ffffff') {
    const value = String(raw || '').trim().toLowerCase();
    if (!value) return fallback;

    if (/^#[0-9a-f]{3}$/i.test(value)) {
        return `#${value[1]}${value[1]}${value[2]}${value[2]}${value[3]}${value[3]}`.toLowerCase();
    }

    if (/^#[0-9a-f]{6}$/i.test(value)) {
        return value;
    }

    const rgb = value.match(/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i);
    if (rgb) {
        const toHex = (n) => clamp(Number(n), 0, 255).toString(16).padStart(2, '0');
        return `#${toHex(rgb[1])}${toHex(rgb[2])}${toHex(rgb[3])}`;
    }

    return fallback;
}

function makeBackgroundStyles({ bgType, bgColor, bgImage, bgPosition, bgSize, bgRepeat }) {
    const type = ['none', 'color', 'image'].includes(bgType) ? bgType : 'none';
    const imageUrl = extractCssUrl(bgImage) || String(bgImage || '').trim();
    const escapedImageUrl = imageUrl.replace(/"/g, '\\"');

    if (type === 'image' && escapedImageUrl) {
        return {
            'background-color': 'transparent',
            'background-image': `url("${escapedImageUrl}")`,
            'background-position': bgPosition || 'center center',
            'background-size': bgSize || 'cover',
            'background-repeat': bgRepeat || 'no-repeat',
        };
    }

    if (type === 'color') {
        return {
            'background-color': normalizeColorValue(bgColor, '#ffffff'),
            'background-image': 'none',
            'background-position': 'center center',
            'background-size': 'auto',
            'background-repeat': 'repeat',
        };
    }

    return {
        'background-color': 'transparent',
        'background-image': 'none',
        'background-position': 'center center',
        'background-size': 'auto',
        'background-repeat': 'repeat',
    };
}

function makeResponsiveOuterStyle({ contentWidth, fullWidth, minHeight, minHeightUnit }) {
    return {
        width: contentWidth === 'full' ? `${fullWidth}%` : '100%',
        'min-height': minHeight > 0 ? `${minHeight}${minHeightUnit}` : '0px',
    };
}

function makeResponsiveInnerStyle({
    contentWidth,
    boxedWidth,
    gapX,
    gapY,
    gapUnit,
    layout,
    cols,
    rows,
    flexDirection,
    wrap,
    items,
    justify,
}) {
    const base = {
        'max-width': contentWidth === 'boxed' ? `${boxedWidth}px` : 'none',
        'column-gap': `${gapX}${gapUnit}`,
        'row-gap': `${gapY}${gapUnit}`,
        'align-items': traitItemsToCssValue(items),
        'justify-content': traitJustifyToCssValue(justify),
    };

    if (layout === 'grid') {
        return {
            ...base,
            'grid-template-columns': `repeat(${cols}, minmax(0, 1fr))`,
            'grid-template-rows': `repeat(${rows}, minmax(0, 1fr))`,
        };
    }

    return {
        ...base,
        'flex-direction': flexDirection,
        'flex-wrap': wrap === 'nowrap' ? 'nowrap' : 'wrap',
    };
}

function extractRepeatCount(templateValue) {
    const text = String(templateValue || '').trim();
    const match = text.match(/repeat\(\s*(\d+)\s*,/i);
    if (!match) return null;
    return clamp(toNumber(match[1], 0), 0, 50);
}

function setTraitRowVisible(name, visible) {
    const rows = document.querySelectorAll('.gjs-trt-trait');
    rows.forEach((row) => {
        const field = row.querySelector(
            `input[name="${name}"], select[name="${name}"], textarea[name="${name}"], [data-pg-trait-name="${name}"]`
        );
        if (!field) return;
        row.style.display = visible ? '' : 'none';
    });
}

function syncContainerTraitRows(model) {
    const contentWidth = model?.get?.('pgContentWidth') === 'full' ? 'full' : 'boxed';
    const layout = model?.get?.('pgLayout') === 'flex' ? 'flex' : 'grid';
    const device = String(model?.get?.('pgDevice') || 'desktop');
    const bgType = String(model?.get?.('pgBgType') || 'none');
    const isFlex = layout === 'flex';
    const isGrid = layout === 'grid';
    const isTablet = device === 'tablet';
    const isMobile = device === 'mobile';

    setTraitRowVisible('pgSecLayout', true);
    setTraitRowVisible('pgSecWidth', true);
    setTraitRowVisible('pgSecFlexItems', isFlex);
    setTraitRowVisible('pgSecGridItems', isGrid);
    setTraitRowVisible('pgSecGaps', isFlex || isGrid);
    setTraitRowVisible('pgSecContainer', true);
    setTraitRowVisible('pgSecResponsive', true);
    setTraitRowVisible('pgSecBackground', true);

    setTraitRowVisible('pgContentWidth', true);
    setTraitRowVisible('pgMinHeightUnit', true);
    setTraitRowVisible('pgMinHeight', true);
    setTraitRowVisible('pgPaddingX', true);
    setTraitRowVisible('pgPaddingY', true);
    setTraitRowVisible('pgTag', true);

    setTraitRowVisible('pgFullWidth', contentWidth === 'full');
    setTraitRowVisible('pgBoxedWidth', contentWidth === 'boxed');
    setTraitRowVisible('pgGridOutline', isGrid);
    setTraitRowVisible('pgCols', isGrid);
    setTraitRowVisible('pgRows', isGrid);
    setTraitRowVisible('pgFlexDir', isFlex);
    setTraitRowVisible('pgWrap', isFlex);
    setTraitRowVisible('pgGapControl', isFlex || isGrid);
    setTraitRowVisible('pgJustify', isFlex);
    setTraitRowVisible('pgItems', isFlex);

    setTraitRowVisible('pgDevice', true);

    setTraitRowVisible('pgFullWidthTablet', isTablet && contentWidth === 'full');
    setTraitRowVisible('pgBoxedWidthTablet', isTablet && contentWidth === 'boxed');
    setTraitRowVisible('pgMinHeightTablet', isTablet);
    setTraitRowVisible('pgColsTablet', isTablet && isGrid);
    setTraitRowVisible('pgRowsTablet', isTablet && isGrid);
    setTraitRowVisible('pgGapXTablet', isTablet);
    setTraitRowVisible('pgGapYTablet', isTablet);
    setTraitRowVisible('pgFlexDirTablet', isTablet && isFlex);
    setTraitRowVisible('pgWrapTablet', isTablet && isFlex);
    setTraitRowVisible('pgJustifyTablet', isTablet && isFlex);
    setTraitRowVisible('pgItemsTablet', isTablet && isFlex);

    setTraitRowVisible('pgFullWidthMobile', isMobile && contentWidth === 'full');
    setTraitRowVisible('pgBoxedWidthMobile', isMobile && contentWidth === 'boxed');
    setTraitRowVisible('pgMinHeightMobile', isMobile);
    setTraitRowVisible('pgColsMobile', isMobile && isGrid);
    setTraitRowVisible('pgRowsMobile', isMobile && isGrid);
    setTraitRowVisible('pgGapXMobile', isMobile);
    setTraitRowVisible('pgGapYMobile', isMobile);
    setTraitRowVisible('pgFlexDirMobile', isMobile && isFlex);
    setTraitRowVisible('pgWrapMobile', isMobile && isFlex);
    setTraitRowVisible('pgJustifyMobile', isMobile && isFlex);
    setTraitRowVisible('pgItemsMobile', isMobile && isFlex);

    setTraitRowVisible('pgBgType', true);
    setTraitRowVisible('pgBgColor', bgType === 'color');
    setTraitRowVisible('pgBgImage', bgType === 'image');
    setTraitRowVisible('pgBgPosition', bgType === 'image');
    setTraitRowVisible('pgBgSize', bgType === 'image');
    setTraitRowVisible('pgBgRepeat', bgType === 'image');
}

function componentHasClass(component, className) {
    const classes = classListFromString(component?.getAttributes?.()?.class);
    return classes.includes(className);
}

function findInnerWrapper(model) {
    const comps = model.components?.();
    if (!comps) return null;

    let inner = null;
    comps.each((child) => {
        if (!inner && componentHasClass(child, 'pg-container-inner')) {
            inner = child;
        }
    });

    return inner;
}

const INNER_WRAPPER_PROPS = {
    selectable: false,
    hoverable: false,
    draggable: false,
    droppable: true,
    copyable: false,
    removable: false,
    layerable: false,
    highlightable: false,
};

function enforceInnerWrapperProps(inner) {
    if (!inner?.set) return;
    inner.set({ ...INNER_WRAPPER_PROPS }, { silent: true });
}

function ensureInnerWrapper(model) {
    const comps = model.components?.();
    if (!comps) return null;

    let inner = findInnerWrapper(model);

    if (!inner) {
        const previousChildren = [];
        comps.each((child) => previousChildren.push(child));

        comps.add(
            {
                type: 'default',
                tagName: 'div',
                attributes: { class: 'pg-layout pg-container-inner w-full' },
                ...INNER_WRAPPER_PROPS,
                components: [],
            },
            { at: 0 }
        );

        const created = findInnerWrapper(model) || comps.at(0);
        const target = created?.components?.();

        if (target) {
            previousChildren.forEach((child, index) => {
                if (!child || child === created || typeof child.move !== 'function') return;
                child.move(created, { at: index, temporary: 1 });
            });
        }

        enforceInnerWrapperProps(created);
        return created;
    }

    const outOfWrapperChildren = [];
    comps.each((child) => {
        if (child !== inner) outOfWrapperChildren.push(child);
    });

    if (outOfWrapperChildren.length) {
        outOfWrapperChildren.forEach((child) => {
            if (!child || typeof child.move !== 'function') return;
            const nextIndex = inner.components().length;
            child.move(inner, { at: nextIndex, temporary: 1 });
        });
    }

    enforceInnerWrapperProps(inner);
    return inner;
}

function isSeededGridColumn(component) {
    const attrs = component?.getAttributes?.() || {};
    const marker = String(attrs['data-pg-seeded-column'] || '').trim();
    const name = String(attrs['data-gjs-name'] || '').trim().toLowerCase();
    return marker === '1' || name === 'left column' || name === 'right column';
}

function normalizeSeededGridColumns(inner) {
    const children = inner?.components?.();
    if (!children?.each) return;

    children.each((child) => {
        if (!child?.addAttributes) return;

        const attrs = child.getAttributes?.() || {};
        const classes = classListFromString(attrs.class);
        const isGridCol = classes.includes('pg-grid-column');
        if (!isGridCol || !isSeededGridColumn(child)) return;

        const cleaned = classes.filter(
            (cls) =>
                cls !== 'rounded-xl' &&
                cls !== 'border' &&
                cls !== 'border-dashed' &&
                cls !== 'border-slate-300' &&
                cls !== 'p-3' &&
                cls !== 'min-h-24'
        );

        if (!cleaned.includes('pg-layout')) cleaned.push('pg-layout');
        if (!cleaned.includes('pg-grid-column')) cleaned.push('pg-grid-column');
        if (!cleaned.some((c) => c.startsWith('min-h-'))) cleaned.push('min-h-6');

        child.addAttributes({
            ...attrs,
            class: Array.from(new Set(cleaned)).join(' ').trim(),
            'data-pg-seeded-column': '1',
        });
    });
}

function autoSeedGridColumnsIfNeeded(model, inner, layout, cols) {
    if (!inner?.components) return;
    if (layout !== 'grid') return;
    if (cols !== 2) return;
    if (model.get('pgAutoSeedDone') === true) return;

    const children = inner.components();
    if (!children || children.length > 0) return;

    children.add([
        {
            type: 'default',
            tagName: 'div',
            attributes: {
                class: 'pg-layout pg-grid-column min-h-6',
                'data-gjs-name': 'Left Column',
                'data-pg-seeded-column': '1',
            },
            components: [],
        },
        {
            type: 'default',
            tagName: 'div',
            attributes: {
                class: 'pg-layout pg-grid-column min-h-6',
                'data-gjs-name': 'Right Column',
                'data-pg-seeded-column': '1',
            },
            components: [],
        },
    ]);

    model.set('pgAutoSeedDone', true, { silent: true });
}

function cleanOuterContainerClasses(classes) {
    return classes.filter((cls) => {
        if (cls === 'pg-container' || cls === 'pg-content-full' || cls === 'pg-content-boxed') return false;
        if (cls === 'mx-auto' || cls === 'w-full') return false;
        if (cls.startsWith('max-w-')) return false;
        if (cls.startsWith('px-') || cls.startsWith('sm:px-') || cls.startsWith('lg:px-')) return false;
        if (cls.startsWith('py-')) return false;
        if (cls === 'grid' || cls === 'flex') return false;
        if (cls.startsWith('grid-cols-') || cls.startsWith('grid-rows-')) return false;
        if (cls.startsWith('gap-') || cls.startsWith('gap-x-') || cls.startsWith('gap-y-')) return false;
        if (cls.startsWith('items-') || cls.startsWith('justify-')) return false;
        if (cls === 'flex-wrap' || cls === 'flex-nowrap') return false;
        if (cls === 'flex-row' || cls === 'flex-col') return false;
        if (cls === 'flex-row-reverse' || cls === 'flex-col-reverse') return false;
        return true;
    });
}

function cleanInnerContainerClasses(classes) {
    return classes.filter((cls) => {
        if (cls === 'pg-container-inner' || cls === 'w-full' || cls === 'mx-auto') return false;
        if (cls.startsWith('max-w-')) return false;
        if (cls === 'grid' || cls === 'flex') return false;
        if (cls.startsWith('grid-cols-') || cls.startsWith('grid-rows-')) return false;
        if (cls.startsWith('gap-') || cls.startsWith('gap-x-') || cls.startsWith('gap-y-')) return false;
        if (cls.startsWith('items-') || cls.startsWith('justify-')) return false;
        if (cls === 'flex-wrap' || cls === 'flex-nowrap') return false;
        if (cls === 'flex-row' || cls === 'flex-col') return false;
        if (cls === 'flex-row-reverse' || cls === 'flex-col-reverse') return false;
        return true;
    });
}

function hydrateContainerProps(model) {
    const outerClasses = classListFromString(model.getAttributes()?.class);
    const outerStyles = model.getStyle?.() || {};
    const inner = ensureInnerWrapper(model);
    const innerClasses = classListFromString(inner?.getAttributes?.()?.class);
    const innerStyles = inner?.getStyle?.() || {};
    const editorDir = getEditorDir(model);

    const explicitFull = outerClasses.includes('pg-content-full');
    const explicitBoxed = outerClasses.includes('pg-content-boxed');
    const parsedOuterWidth = parseCssDimension(outerStyles.width);
    const parsedOuterMax = parseCssDimension(outerStyles['max-width']);
    const parsedInnerMax = parseCssDimension(innerStyles['max-width']);
    const parsedMinHeight = parseCssDimension(outerStyles['min-height']);
    const bgImageValue = extractCssUrl(outerStyles['background-image']);
    const bgColorValue = String(outerStyles['background-color'] || '').trim();
    const bgPositionValue = String(outerStyles['background-position'] || '').trim();
    const bgSizeValue = String(outerStyles['background-size'] || '').trim();
    const bgRepeatValue = String(outerStyles['background-repeat'] || '').trim();

    if (explicitFull) {
        model.set('pgContentWidth', 'full', { silent: true });
    } else if (explicitBoxed) {
        model.set('pgContentWidth', 'boxed', { silent: true });
    } else if (parsedInnerMax && innerStyles['max-width'] !== 'none') {
        model.set('pgContentWidth', 'boxed', { silent: true });
    } else if (parsedOuterMax && outerStyles['max-width'] !== 'none') {
        model.set('pgContentWidth', 'boxed', { silent: true });
    } else {
        model.set('pgContentWidth', 'boxed', { silent: true });
    }

    if (parsedOuterWidth && parsedOuterWidth.unit === '%') {
        model.set('pgFullWidth', String(clamp(Math.round(parsedOuterWidth.value), 10, 100)), { silent: true });
    } else {
        model.set('pgFullWidth', '100', { silent: true });
    }

    if (parsedInnerMax && parsedInnerMax.unit === 'px') {
        model.set('pgBoxedWidth', String(clamp(Math.round(parsedInnerMax.value), 320, 2400)), { silent: true });
    } else if (parsedOuterMax && parsedOuterMax.unit === 'px') {
        model.set('pgBoxedWidth', String(clamp(Math.round(parsedOuterMax.value), 320, 2400)), { silent: true });
    } else if (outerClasses.includes('max-w-3xl')) {
        model.set('pgBoxedWidth', '768', { silent: true });
    } else if (outerClasses.includes('max-w-5xl')) {
        model.set('pgBoxedWidth', '1024', { silent: true });
    } else if (outerClasses.includes('max-w-7xl')) {
        model.set('pgBoxedWidth', '1280', { silent: true });
    } else {
        model.set('pgBoxedWidth', '1200', { silent: true });
    }

    if (parsedMinHeight) {
        model.set('pgMinHeight', String(clamp(Math.round(parsedMinHeight.value), 0, 2000)), { silent: true });
        model.set('pgMinHeightUnit', parsedMinHeight.unit === 'vh' ? 'vh' : 'px', { silent: true });
    } else {
        model.set('pgMinHeight', '0', { silent: true });
        model.set('pgMinHeightUnit', 'px', { silent: true });
    }

    if (bgImageValue) {
        model.set('pgBgType', 'image', { silent: true });
    } else if (
        bgColorValue &&
        bgColorValue !== 'transparent' &&
        bgColorValue !== 'rgba(0, 0, 0, 0)' &&
        bgColorValue !== 'rgba(0,0,0,0)'
    ) {
        model.set('pgBgType', 'color', { silent: true });
    } else {
        model.set('pgBgType', 'none', { silent: true });
    }

    model.set('pgBgColor', normalizeColorValue(bgColorValue, '#ffffff'), { silent: true });
    model.set('pgBgImage', bgImageValue || '', { silent: true });
    const allowedBgPositions = [
        'left top',
        'left center',
        'left bottom',
        'center top',
        'center center',
        'center bottom',
        'right top',
        'right center',
        'right bottom',
    ];
    const allowedBgSizes = ['cover', 'contain', 'auto'];
    const allowedBgRepeats = ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'];

    const normalizedBgPosition = String(bgPositionValue || '').trim().toLowerCase();
    const normalizedBgSize = String(bgSizeValue || '').trim().toLowerCase();
    const normalizedBgRepeat = String(bgRepeatValue || '').trim().toLowerCase();

    model.set('pgBgPosition', allowedBgPositions.includes(normalizedBgPosition) ? normalizedBgPosition : 'center center', { silent: true });
    model.set('pgBgSize', allowedBgSizes.includes(normalizedBgSize) ? normalizedBgSize : 'cover', { silent: true });
    model.set('pgBgRepeat', allowedBgRepeats.includes(normalizedBgRepeat) ? normalizedBgRepeat : 'no-repeat', { silent: true });

    if (outerClasses.includes('px-0')) {
        model.set('pgPaddingX', 'none', { silent: true });
    } else if (outerClasses.includes('sm:px-6') || outerClasses.includes('lg:px-8')) {
        model.set('pgPaddingX', 'compact', { silent: true });
    } else {
        model.set('pgPaddingX', 'comfortable', { silent: true });
    }

    if (outerClasses.includes('py-0')) {
        model.set('pgPaddingY', 'none', { silent: true });
    } else if (outerClasses.includes('py-8')) {
        model.set('pgPaddingY', 'sm', { silent: true });
    } else if (outerClasses.includes('py-16')) {
        model.set('pgPaddingY', 'lg', { silent: true });
    } else {
        model.set('pgPaddingY', 'md', { silent: true });
    }

    if (innerClasses.includes('flex')) {
        model.set('pgLayout', 'flex', { silent: true });
    } else if (innerClasses.includes('grid')) {
        model.set('pgLayout', 'grid', { silent: true });
    } else {
        model.set('pgLayout', 'grid', { silent: true });
    }

    const colsClass = innerClasses.find((c) => c.startsWith('grid-cols-'));
    const rowsClass = innerClasses.find((c) => c.startsWith('grid-rows-'));
    const styleCols = extractRepeatCount(innerStyles['grid-template-columns']);
    const styleRows = extractRepeatCount(innerStyles['grid-template-rows']);
    const gapClass = innerClasses.find((c) => c.startsWith('gap-') && !c.startsWith('gap-x-') && !c.startsWith('gap-y-'));
    const gapXClass = innerClasses.find((c) => c.startsWith('gap-x-'));
    const gapYClass = innerClasses.find((c) => c.startsWith('gap-y-'));
    const parsedRowGap = parseCssDimension(innerStyles['row-gap'] || innerStyles.gap);
    const parsedColGap = parseCssDimension(innerStyles['column-gap'] || innerStyles.gap);
    const itemsClass = innerClasses.find((c) => c.startsWith('items-'));
    const justifyClass = innerClasses.find((c) => c.startsWith('justify-'));

    if (styleCols) model.set('pgCols', String(clamp(styleCols, 1, 12)), { silent: true });
    else if (colsClass) model.set('pgCols', colsClass.replace('grid-cols-', ''), { silent: true });
    else model.set('pgCols', '3', { silent: true });

    if (styleRows) model.set('pgRows', String(clamp(styleRows, 1, 12)), { silent: true });
    else if (rowsClass) model.set('pgRows', rowsClass.replace('grid-rows-', ''), { silent: true });
    else model.set('pgRows', '2', { silent: true });

    let nextGapX = 20;
    let nextGapY = 20;
    let nextGapUnit = 'px';

    if (parsedColGap && parsedRowGap) {
        nextGapX = clamp(Math.round(parsedColGap.value), 0, 400);
        nextGapY = clamp(Math.round(parsedRowGap.value), 0, 400);
        nextGapUnit = parsedColGap.unit || parsedRowGap.unit || 'px';
    } else if (gapXClass || gapYClass || gapClass) {
        const rawX = gapXClass ? gapXClass.replace('gap-x-', '') : gapClass ? gapClass.replace('gap-', '') : '6';
        const rawY = gapYClass ? gapYClass.replace('gap-y-', '') : gapClass ? gapClass.replace('gap-', '') : '6';

        const pxX = TAILWIND_GAP_TO_PX[rawX] ?? toNumber(rawX, 20);
        const pxY = TAILWIND_GAP_TO_PX[rawY] ?? toNumber(rawY, 20);
        nextGapX = clamp(Math.round(pxX), 0, 400);
        nextGapY = clamp(Math.round(pxY), 0, 400);
        nextGapUnit = 'px';
    }

    model.set('pgGapX', String(nextGapX), { silent: true });
    model.set('pgGapY', String(nextGapY), { silent: true });
    model.set('pgGapUnit', nextGapUnit, { silent: true });
    model.set('pgGapLinked', nextGapX === nextGapY, { silent: true });
    model.set('pgGridOutline', inner?.getAttributes?.()?.['data-pg-grid-outline'] === '1', { silent: true });

    if (itemsClass) model.set('pgItems', itemsClass.replace('items-', ''), { silent: true });
    if (justifyClass) model.set('pgJustify', justifyClass.replace('justify-', ''), { silent: true });

    const styleFlexDir = String(innerStyles['flex-direction'] || '').trim().toLowerCase();
    if (styleFlexDir) {
        model.set('pgFlexDir', cssFlexDirToTraitValue(styleFlexDir, editorDir), { silent: true });
    } else if (innerClasses.includes('flex-row-reverse')) {
        model.set('pgFlexDir', cssFlexDirToTraitValue('row-reverse', editorDir), { silent: true });
    } else if (innerClasses.includes('flex-col-reverse')) {
        model.set('pgFlexDir', cssFlexDirToTraitValue('column-reverse', editorDir), { silent: true });
    } else if (innerClasses.includes('flex-col')) {
        model.set('pgFlexDir', cssFlexDirToTraitValue('column', editorDir), { silent: true });
    } else {
        model.set('pgFlexDir', cssFlexDirToTraitValue('row', editorDir), { silent: true });
    }

    if (innerClasses.includes('flex-nowrap')) {
        model.set('pgWrap', 'nowrap', { silent: true });
    } else {
        model.set('pgWrap', 'wrap', { silent: true });
    }

    const baseFullWidth = clamp(Math.round(toNumber(model.get('pgFullWidth'), 100)), 10, 100);
    const baseBoxedWidth = clamp(Math.round(toNumber(model.get('pgBoxedWidth'), 1200)), 320, 2400);
    const baseMinHeight = clamp(Math.round(toNumber(model.get('pgMinHeight'), 0)), 0, 2000);
    const baseCols = clamp(Math.round(toNumber(model.get('pgCols'), 3)), 1, 12);
    const baseRows = clamp(Math.round(toNumber(model.get('pgRows'), 2)), 1, 12);
    const baseGapX = clamp(Math.round(toNumber(model.get('pgGapX'), 20)), 0, 400);
    const baseGapY = clamp(Math.round(toNumber(model.get('pgGapY'), 20)), 0, 400);
    const baseFlexDir = String(model.get('pgFlexDir') || 'row');
    const baseWrap = String(model.get('pgWrap') || 'wrap');
    const baseJustify = String(model.get('pgJustify') || 'start');
    const baseItems = String(model.get('pgItems') || 'stretch');

    const currentDevice = String(model.get('pgDevice') || 'desktop');
    model.set('pgDevice', ['desktop', 'tablet', 'mobile'].includes(currentDevice) ? currentDevice : 'desktop', { silent: true });
    model.set('pgFullWidthTablet', String(clamp(Math.round(toNumber(model.get('pgFullWidthTablet'), baseFullWidth)), 10, 100)), { silent: true });
    model.set('pgFullWidthMobile', String(clamp(Math.round(toNumber(model.get('pgFullWidthMobile'), toNumber(model.get('pgFullWidthTablet'), baseFullWidth))), 10, 100)), { silent: true });
    model.set('pgBoxedWidthTablet', String(clamp(Math.round(toNumber(model.get('pgBoxedWidthTablet'), baseBoxedWidth)), 320, 2400)), { silent: true });
    model.set('pgBoxedWidthMobile', String(clamp(Math.round(toNumber(model.get('pgBoxedWidthMobile'), toNumber(model.get('pgBoxedWidthTablet'), baseBoxedWidth))), 320, 2400)), { silent: true });
    model.set('pgMinHeightTablet', String(clamp(Math.round(toNumber(model.get('pgMinHeightTablet'), baseMinHeight)), 0, 2000)), { silent: true });
    model.set('pgMinHeightMobile', String(clamp(Math.round(toNumber(model.get('pgMinHeightMobile'), toNumber(model.get('pgMinHeightTablet'), baseMinHeight))), 0, 2000)), { silent: true });
    model.set('pgColsTablet', String(clamp(Math.round(toNumber(model.get('pgColsTablet'), baseCols)), 1, 12)), { silent: true });
    model.set('pgColsMobile', String(clamp(Math.round(toNumber(model.get('pgColsMobile'), toNumber(model.get('pgColsTablet'), baseCols))), 1, 12)), { silent: true });
    model.set('pgRowsTablet', String(clamp(Math.round(toNumber(model.get('pgRowsTablet'), baseRows)), 1, 12)), { silent: true });
    model.set('pgRowsMobile', String(clamp(Math.round(toNumber(model.get('pgRowsMobile'), toNumber(model.get('pgRowsTablet'), baseRows))), 1, 12)), { silent: true });
    model.set('pgGapXTablet', String(clamp(Math.round(toNumber(model.get('pgGapXTablet'), baseGapX)), 0, 400)), { silent: true });
    model.set('pgGapXMobile', String(clamp(Math.round(toNumber(model.get('pgGapXMobile'), toNumber(model.get('pgGapXTablet'), baseGapX))), 0, 400)), { silent: true });
    model.set('pgGapYTablet', String(clamp(Math.round(toNumber(model.get('pgGapYTablet'), baseGapY)), 0, 400)), { silent: true });
    model.set('pgGapYMobile', String(clamp(Math.round(toNumber(model.get('pgGapYMobile'), toNumber(model.get('pgGapYTablet'), baseGapY))), 0, 400)), { silent: true });
    model.set('pgFlexDirTablet', String(model.get('pgFlexDirTablet') || baseFlexDir), { silent: true });
    model.set('pgFlexDirMobile', String(model.get('pgFlexDirMobile') || model.get('pgFlexDirTablet') || baseFlexDir), { silent: true });
    model.set('pgWrapTablet', String(model.get('pgWrapTablet') || baseWrap), { silent: true });
    model.set('pgWrapMobile', String(model.get('pgWrapMobile') || model.get('pgWrapTablet') || baseWrap), { silent: true });
    model.set('pgJustifyTablet', String(model.get('pgJustifyTablet') || baseJustify), { silent: true });
    model.set('pgJustifyMobile', String(model.get('pgJustifyMobile') || model.get('pgJustifyTablet') || baseJustify), { silent: true });
    model.set('pgItemsTablet', String(model.get('pgItemsTablet') || baseItems), { silent: true });
    model.set('pgItemsMobile', String(model.get('pgItemsMobile') || model.get('pgItemsTablet') || baseItems), { silent: true });
}

function applyContainerClasses(model) {
    const tag = model.get('pgTag') || 'section';
    const contentWidth = model.get('pgContentWidth') === 'full' ? 'full' : 'boxed';
    const fullWidth = clamp(Math.round(toNumber(model.get('pgFullWidth'), 100)), 10, 100);
    const boxedWidth = clamp(Math.round(toNumber(model.get('pgBoxedWidth'), 1200)), 320, 2400);
    const minHeight = clamp(Math.round(toNumber(model.get('pgMinHeight'), 0)), 0, 2000);
    const minHeightUnit = model.get('pgMinHeightUnit') === 'vh' ? 'vh' : 'px';
    const paddingX = model.get('pgPaddingX') || 'comfortable';
    const paddingY = model.get('pgPaddingY') || 'md';
    const layout = model.get('pgLayout') === 'flex' ? 'flex' : 'grid';
    const cols = clamp(Math.round(toNumber(model.get('pgCols'), 3)), 1, 12);
    const rows = clamp(Math.round(toNumber(model.get('pgRows'), 2)), 1, 12);
    const gridOutline = model.get('pgGridOutline') === true || model.get('pgGridOutline') === 'true' || model.get('pgGridOutline') === 1;
    const allowedGapUnits = ['px', 'rem', 'em', '%', 'vh', 'vw'];
    const gapUnit = allowedGapUnits.includes(model.get('pgGapUnit')) ? model.get('pgGapUnit') : 'px';
    const gapLinked = model.get('pgGapLinked') !== false;
    let gapX = clamp(Math.round(toNumber(model.get('pgGapX'), 20)), 0, 400);
    let gapY = clamp(Math.round(toNumber(model.get('pgGapY'), 20)), 0, 400);
    const items = model.get('pgItems') || 'stretch';
    const justify = model.get('pgJustify') || 'start';
    const flexDir = model.get('pgFlexDir') || 'row';
    const wrap = model.get('pgWrap') || 'wrap';
    const bgTypeRaw = String(model.get('pgBgType') || 'none');
    const bgType = ['none', 'color', 'image'].includes(bgTypeRaw) ? bgTypeRaw : 'none';
    const bgColor = String(model.get('pgBgColor') || '#ffffff');
    const bgImage = String(model.get('pgBgImage') || '');
    const bgPosition = String(model.get('pgBgPosition') || 'center center').toLowerCase();
    const bgSize = String(model.get('pgBgSize') || 'cover').toLowerCase();
    const bgRepeat = String(model.get('pgBgRepeat') || 'no-repeat').toLowerCase();

    const fullWidthTablet = clamp(Math.round(toNumber(model.get('pgFullWidthTablet'), fullWidth)), 10, 100);
    const fullWidthMobile = clamp(Math.round(toNumber(model.get('pgFullWidthMobile'), fullWidthTablet)), 10, 100);
    const boxedWidthTablet = clamp(Math.round(toNumber(model.get('pgBoxedWidthTablet'), boxedWidth)), 320, 2400);
    const boxedWidthMobile = clamp(Math.round(toNumber(model.get('pgBoxedWidthMobile'), boxedWidthTablet)), 320, 2400);
    const minHeightTablet = clamp(Math.round(toNumber(model.get('pgMinHeightTablet'), minHeight)), 0, 2000);
    const minHeightMobile = clamp(Math.round(toNumber(model.get('pgMinHeightMobile'), minHeightTablet)), 0, 2000);
    const colsTablet = clamp(Math.round(toNumber(model.get('pgColsTablet'), cols)), 1, 12);
    const colsMobile = clamp(Math.round(toNumber(model.get('pgColsMobile'), colsTablet)), 1, 12);
    const rowsTablet = clamp(Math.round(toNumber(model.get('pgRowsTablet'), rows)), 1, 12);
    const rowsMobile = clamp(Math.round(toNumber(model.get('pgRowsMobile'), rowsTablet)), 1, 12);
    const gapXTablet = clamp(Math.round(toNumber(model.get('pgGapXTablet'), gapX)), 0, 400);
    const gapXMobile = clamp(Math.round(toNumber(model.get('pgGapXMobile'), gapXTablet)), 0, 400);
    const gapYTablet = clamp(Math.round(toNumber(model.get('pgGapYTablet'), gapY)), 0, 400);
    const gapYMobile = clamp(Math.round(toNumber(model.get('pgGapYMobile'), gapYTablet)), 0, 400);
    const flexDirTablet = String(model.get('pgFlexDirTablet') || flexDir);
    const flexDirMobile = String(model.get('pgFlexDirMobile') || flexDirTablet || flexDir);
    const wrapTablet = String(model.get('pgWrapTablet') || wrap);
    const wrapMobile = String(model.get('pgWrapMobile') || wrapTablet || wrap);
    const justifyTablet = String(model.get('pgJustifyTablet') || justify);
    const justifyMobile = String(model.get('pgJustifyMobile') || justifyTablet || justify);
    const itemsTablet = String(model.get('pgItemsTablet') || items);
    const itemsMobile = String(model.get('pgItemsMobile') || itemsTablet || items);
    const editorDir = getEditorDir(model);
    const cssFlexDirection = traitFlexDirToCssValue(flexDir, editorDir);
    const cssFlexDirectionTablet = traitFlexDirToCssValue(flexDirTablet, editorDir);
    const cssFlexDirectionMobile = traitFlexDirToCssValue(flexDirMobile, editorDir);

    if (gapLinked) {
        gapY = gapX;
    }

    if (model.get('tagName') !== tag) {
        model.set('tagName', tag);
    }

    const inner = ensureInnerWrapper(model);
    if (!inner) return;

    autoSeedGridColumnsIfNeeded(model, inner, layout, cols);
    normalizeSeededGridColumns(inner);

    const outerCurrent = classListFromString(model.getAttributes()?.class);
    const innerCurrent = classListFromString(inner.getAttributes?.()?.class);
    const outerCleaned = cleanOuterContainerClasses(outerCurrent);
    const innerCleaned = cleanInnerContainerClasses(innerCurrent);

    const innerLayoutClasses = [];
    if (layout === 'flex') {
        innerLayoutClasses.push('flex', cssFlexDirToClass(cssFlexDirection));
        innerLayoutClasses.push(wrap === 'nowrap' ? 'flex-nowrap' : 'flex-wrap');
    } else {
        innerLayoutClasses.push('grid');
    }
    innerLayoutClasses.push(`items-${items}`, `justify-${justify}`);

    const outerNextClasses = [
        ...outerCleaned,
        'pg-layout',
        'pg-container',
        contentWidth === 'full' ? 'pg-content-full' : 'pg-content-boxed',
        ...splitMappedClasses(CONTENT_WIDTH_CLASS_MAP, contentWidth, 'boxed'),
        ...splitMappedClasses(PADDING_X_CLASS_MAP, paddingX, 'comfortable'),
        ...splitMappedClasses(PADDING_Y_CLASS_MAP, paddingY, 'md'),
    ];

    const innerNextClasses = [
        ...innerCleaned,
        'pg-layout',
        'pg-container-inner',
        'w-full',
        ...innerLayoutClasses,
    ];

    model.addAttributes({
        class: Array.from(new Set(outerNextClasses)).join(' ').trim(),
        'data-gjs-name': 'Container',
    });

    inner.addAttributes({
        class: Array.from(new Set(innerNextClasses)).join(' ').trim(),
        'data-pg-grid-outline': layout === 'grid' && gridOutline ? '1' : '0',
    });

    const outerStyles = { ...(model.getStyle?.() || {}) };
    outerStyles['margin-left'] = 'auto';
    outerStyles['margin-right'] = 'auto';
    outerStyles['max-width'] = 'none';
    outerStyles.width = contentWidth === 'full' ? `${fullWidth}%` : '100%';

    if (minHeight > 0) {
        outerStyles['min-height'] = `${minHeight}${minHeightUnit}`;
    } else {
        delete outerStyles['min-height'];
    }

    Object.assign(
        outerStyles,
        makeBackgroundStyles({
            bgType,
            bgColor,
            bgImage,
            bgPosition,
            bgSize,
            bgRepeat,
        })
    );

    model.setStyle(outerStyles);

    const innerStyles = { ...(inner.getStyle?.() || {}) };
    innerStyles.width = '100%';
    innerStyles['margin-left'] = 'auto';
    innerStyles['margin-right'] = 'auto';
    innerStyles['max-width'] = contentWidth === 'boxed' ? `${boxedWidth}px` : 'none';
    innerStyles['column-gap'] = `${gapX}${gapUnit}`;
    innerStyles['row-gap'] = `${gapY}${gapUnit}`;
    innerStyles['align-items'] = traitItemsToCssValue(items);
    innerStyles['justify-content'] = traitJustifyToCssValue(justify);

    if (layout === 'grid') {
        innerStyles['grid-template-columns'] = `repeat(${cols}, minmax(0, 1fr))`;
        innerStyles['grid-template-rows'] = `repeat(${rows}, minmax(0, 1fr))`;
        delete innerStyles['flex-direction'];
        delete innerStyles['flex-wrap'];
    } else {
        innerStyles['flex-direction'] = cssFlexDirection;
        innerStyles['flex-wrap'] = wrap === 'nowrap' ? 'nowrap' : 'wrap';
        delete innerStyles['grid-template-columns'];
        delete innerStyles['grid-template-rows'];
    }

    delete innerStyles['min-height'];
    inner.setStyle(innerStyles);

    if (gapLinked && String(model.get('pgGapY')) !== String(gapX)) {
        model.set('pgGapY', String(gapX), { silent: true });
    }

    const containerId = ensureContainerId(model);
    const outerSelector = `#${containerId}`;
    const innerSelector = `#${containerId} > .pg-container-inner`;

    const tabletOuterStyle = makeResponsiveOuterStyle({
        contentWidth,
        fullWidth: fullWidthTablet,
        minHeight: minHeightTablet,
        minHeightUnit,
    });
    const mobileOuterStyle = makeResponsiveOuterStyle({
        contentWidth,
        fullWidth: fullWidthMobile,
        minHeight: minHeightMobile,
        minHeightUnit,
    });

    const tabletInnerStyle = makeResponsiveInnerStyle({
        contentWidth,
        boxedWidth: boxedWidthTablet,
        gapX: gapXTablet,
        gapY: gapYTablet,
        gapUnit,
        layout,
        cols: colsTablet,
        rows: rowsTablet,
        flexDirection: cssFlexDirectionTablet,
        wrap: wrapTablet,
        items: itemsTablet,
        justify: justifyTablet,
    });
    const mobileInnerStyle = makeResponsiveInnerStyle({
        contentWidth,
        boxedWidth: boxedWidthMobile,
        gapX: gapXMobile,
        gapY: gapYMobile,
        gapUnit,
        layout,
        cols: colsMobile,
        rows: rowsMobile,
        flexDirection: cssFlexDirectionMobile,
        wrap: wrapMobile,
        items: itemsMobile,
        justify: justifyMobile,
    });

    setResponsiveRule(model, outerSelector, tabletOuterStyle, MEDIA_QUERY_TABLET);
    setResponsiveRule(model, outerSelector, mobileOuterStyle, MEDIA_QUERY_MOBILE);
    setResponsiveRule(model, innerSelector, tabletInnerStyle, MEDIA_QUERY_TABLET);
    setResponsiveRule(model, innerSelector, mobileInnerStyle, MEDIA_QUERY_MOBILE);
}

function ensureContainerEditorStyles(editor) {
    const STYLE_ID = 'pg-container-editor-only-style';
    const STYLE_CONTENT = `
        .pg-container-inner > .pg-grid-column {
            outline: 1px dashed rgba(148, 163, 184, 0.5);
            outline-offset: -1px;
        }

        .pg-container-inner[data-pg-grid-outline="1"] > * {
            outline: 1px dashed rgba(192, 132, 252, 0.75);
            outline-offset: -1px;
        }

        .pg-container-inner[data-pg-grid-outline="1"] > .pg-grid-column {
            background: rgba(192, 132, 252, 0.08);
        }
    `;

    const inject = () => {
        const doc = editor?.Canvas?.getDocument?.();
        if (!doc?.head) return;

        let styleEl = doc.getElementById(STYLE_ID);
        if (!styleEl) {
            styleEl = doc.createElement('style');
            styleEl.id = STYLE_ID;
            doc.head.appendChild(styleEl);
        }

        if (styleEl.innerHTML !== STYLE_CONTENT) {
            styleEl.innerHTML = STYLE_CONTENT;
        }
    };

    inject();
    editor.on('load', inject);
    editor.on('canvas:frame:load', inject);
}

export function registerContainerElement(editor) {
    const dc = editor.DomComponents;

    dc.addType('pg-container', {
        isComponent: (el) => {
            if (!el || !el.tagName) return false;

            const tag = el.tagName.toLowerCase();
            const validTags = ['section', 'div', 'main', 'article'];
            const name = (el.getAttribute?.('data-gjs-name') || '').toLowerCase();

            return (
                validTags.includes(tag) &&
                (name === 'container' || el.classList?.contains('pg-container'))
            );
        },

        model: {
            defaults: {
                tagName: 'section',
                name: 'Container',
                selectable: true,
                hoverable: true,
                draggable: true,
                droppable: true,
                attributes: {
                    class: 'pg-layout pg-container pg-content-boxed w-full px-4 sm:px-8 lg:px-24 py-12',
                    'data-gjs-name': 'Container',
                },
                components: [
                    {
                        type: 'default',
                        tagName: 'div',
                        ...INNER_WRAPPER_PROPS,
                        attributes: {
                            class: 'pg-layout pg-container-inner w-full grid grid-cols-1 items-stretch justify-start',
                        },
                        components: [],
                    },
                ],
                pgTag: 'section',
                pgContentWidth: 'boxed',
                pgFullWidth: '100',
                pgBoxedWidth: '1200',
                pgMinHeight: '0',
                pgMinHeightUnit: 'px',
                pgPaddingX: 'comfortable',
                pgPaddingY: 'md',
                pgLayout: 'grid',
                pgCols: '3',
                pgRows: '2',
                pgGridOutline: false,
                pgGap: '20',
                pgGapX: '20',
                pgGapY: '20',
                pgGapUnit: 'px',
                pgGapLinked: true,
                pgItems: 'stretch',
                pgJustify: 'start',
                pgFlexDir: 'row',
                pgWrap: 'wrap',
                pgAutoSeedDone: false,
                pgDevice: 'desktop',
                pgFullWidthTablet: '100',
                pgFullWidthMobile: '100',
                pgBoxedWidthTablet: '1200',
                pgBoxedWidthMobile: '1200',
                pgMinHeightTablet: '0',
                pgMinHeightMobile: '0',
                pgColsTablet: '3',
                pgColsMobile: '3',
                pgRowsTablet: '2',
                pgRowsMobile: '2',
                pgGapXTablet: '20',
                pgGapXMobile: '20',
                pgGapYTablet: '20',
                pgGapYMobile: '20',
                pgFlexDirTablet: 'row',
                pgFlexDirMobile: 'row',
                pgWrapTablet: 'wrap',
                pgWrapMobile: 'wrap',
                pgJustifyTablet: 'start',
                pgJustifyMobile: 'start',
                pgItemsTablet: 'stretch',
                pgItemsMobile: 'stretch',
                pgBgType: 'none',
                pgBgColor: '#ffffff',
                pgBgImage: '',
                pgBgPosition: 'center center',
                pgBgSize: 'cover',
                pgBgRepeat: 'no-repeat',
                traits: [
                    {
                        type: 'pg-trait-heading',
                        name: 'pgSecLayout',
                        label: ' ',
                        title: 'Layout',
                    },
                    {
                        type: 'select',
                        name: 'pgLayout',
                        label: 'Container Layout',
                        changeProp: 1,
                        options: [
                            { id: 'flex', name: 'Flexbox' },
                            { id: 'grid', name: 'Grid' },
                        ],
                    },
                    {
                        type: 'pg-trait-heading',
                        name: 'pgSecWidth',
                        label: ' ',
                        title: 'Width',
                    },
                    {
                        type: 'select',
                        name: 'pgContentWidth',
                        label: 'Content Width',
                        changeProp: 1,
                        options: [
                            { id: 'boxed', name: 'Inside Box' },
                            { id: 'full', name: 'Full Width' },
                        ],
                    },
                    {
                        type: 'pg-range',
                        name: 'pgFullWidth',
                        label: 'Section Width (%)',
                        min: 10,
                        max: 100,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgBoxedWidth',
                        label: 'Inner Content Width (px)',
                        min: 320,
                        max: 2400,
                        step: 10,
                        changeProp: 1,
                    },
                    {
                        type: 'select',
                        name: 'pgMinHeightUnit',
                        label: 'Min Height Unit',
                        changeProp: 1,
                        options: [
                            { id: 'px', name: 'px' },
                            { id: 'vh', name: 'vh' },
                        ],
                    },
                    {
                        type: 'pg-range',
                        name: 'pgMinHeight',
                        label: 'Min Height',
                        min: 0,
                        max: 2000,
                        step: 10,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-trait-heading',
                        name: 'pgSecFlexItems',
                        label: ' ',
                        title: 'Items (Flexbox)',
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgFlexDir',
                        label: 'Direction',
                        changeProp: 1,
                        options: FLEX_DIRECTION_OPTIONS,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgJustify',
                        label: 'Justify Content',
                        options: FLEX_JUSTIFY_OPTIONS,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgItems',
                        label: 'Align Items',
                        options: FLEX_ITEMS_OPTIONS,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgWrap',
                        label: 'Wrap',
                        hint: 'Items can stay in one line (No Wrap) or move to multiple lines (Wrap).',
                        changeProp: 1,
                        options: FLEX_WRAP_OPTIONS,
                    },
                    {
                        type: 'pg-trait-heading',
                        name: 'pgSecGridItems',
                        label: ' ',
                        title: 'Items (Grid)',
                    },
                    {
                        type: 'pg-switch',
                        name: 'pgGridOutline',
                        label: 'Grid Outline',
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgCols',
                        label: 'Columns',
                        unitLabel: 'fr',
                        min: 1,
                        max: 12,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgRows',
                        label: 'Rows',
                        unitLabel: 'fr',
                        min: 1,
                        max: 12,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-trait-heading',
                        name: 'pgSecGaps',
                        label: ' ',
                        title: 'Gaps',
                    },
                    {
                        type: 'pg-gap-control',
                        name: 'pgGapControl',
                        label: 'Gaps',
                        units: ['px'],
                        defaultUnit: 'px',
                        min: 0,
                        max: 400,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-trait-heading',
                        name: 'pgSecResponsive',
                        label: ' ',
                        title: 'Responsive',
                    },
                    {
                        type: 'select',
                        name: 'pgDevice',
                        label: 'Edit Device',
                        changeProp: 1,
                        options: [
                            { id: 'desktop', name: 'Desktop' },
                            { id: 'tablet', name: 'Tablet' },
                            { id: 'mobile', name: 'Mobile' },
                        ],
                    },
                    {
                        type: 'pg-range',
                        name: 'pgFullWidthTablet',
                        label: 'Tablet Section Width (%)',
                        min: 10,
                        max: 100,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgBoxedWidthTablet',
                        label: 'Tablet Inner Width (px)',
                        min: 320,
                        max: 2400,
                        step: 10,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgMinHeightTablet',
                        label: 'Tablet Min Height',
                        min: 0,
                        max: 2000,
                        step: 10,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgColsTablet',
                        label: 'Tablet Columns',
                        unitLabel: 'fr',
                        min: 1,
                        max: 12,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgRowsTablet',
                        label: 'Tablet Rows',
                        unitLabel: 'fr',
                        min: 1,
                        max: 12,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgGapXTablet',
                        label: 'Tablet Gap X',
                        min: 0,
                        max: 400,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgGapYTablet',
                        label: 'Tablet Gap Y',
                        min: 0,
                        max: 400,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgFlexDirTablet',
                        label: 'Tablet Direction',
                        changeProp: 1,
                        options: FLEX_DIRECTION_OPTIONS,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgJustifyTablet',
                        label: 'Tablet Justify',
                        changeProp: 1,
                        options: FLEX_JUSTIFY_OPTIONS,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgItemsTablet',
                        label: 'Tablet Align',
                        changeProp: 1,
                        options: FLEX_ITEMS_OPTIONS,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgWrapTablet',
                        label: 'Tablet Wrap',
                        changeProp: 1,
                        options: FLEX_WRAP_OPTIONS,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgFullWidthMobile',
                        label: 'Mobile Section Width (%)',
                        min: 10,
                        max: 100,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgBoxedWidthMobile',
                        label: 'Mobile Inner Width (px)',
                        min: 320,
                        max: 2400,
                        step: 10,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgMinHeightMobile',
                        label: 'Mobile Min Height',
                        min: 0,
                        max: 2000,
                        step: 10,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgColsMobile',
                        label: 'Mobile Columns',
                        unitLabel: 'fr',
                        min: 1,
                        max: 12,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgRowsMobile',
                        label: 'Mobile Rows',
                        unitLabel: 'fr',
                        min: 1,
                        max: 12,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgGapXMobile',
                        label: 'Mobile Gap X',
                        min: 0,
                        max: 400,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-range',
                        name: 'pgGapYMobile',
                        label: 'Mobile Gap Y',
                        min: 0,
                        max: 400,
                        step: 1,
                        changeProp: 1,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgFlexDirMobile',
                        label: 'Mobile Direction',
                        changeProp: 1,
                        options: FLEX_DIRECTION_OPTIONS,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgJustifyMobile',
                        label: 'Mobile Justify',
                        changeProp: 1,
                        options: FLEX_JUSTIFY_OPTIONS,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgItemsMobile',
                        label: 'Mobile Align',
                        changeProp: 1,
                        options: FLEX_ITEMS_OPTIONS,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgWrapMobile',
                        label: 'Mobile Wrap',
                        changeProp: 1,
                        options: FLEX_WRAP_OPTIONS,
                    },
                    {
                        type: 'pg-trait-heading',
                        name: 'pgSecBackground',
                        label: ' ',
                        title: 'Background',
                    },
                    {
                        type: 'select',
                        name: 'pgBgType',
                        label: 'Background Type',
                        changeProp: 1,
                        options: [
                            { id: 'none', name: 'None' },
                            { id: 'color', name: 'Color' },
                            { id: 'image', name: 'Image' },
                        ],
                    },
                    {
                        type: 'color',
                        name: 'pgBgColor',
                        label: 'Color',
                        changeProp: 1,
                    },
                    {
                        type: 'media-picker',
                        name: 'pgBgImage',
                        label: 'Image',
                        changeProp: 1,
                    },
                    {
                        type: 'select',
                        name: 'pgBgPosition',
                        label: 'Image Position',
                        changeProp: 1,
                        options: [
                            { id: 'left top', name: 'Left Top' },
                            { id: 'left center', name: 'Left Center' },
                            { id: 'left bottom', name: 'Left Bottom' },
                            { id: 'center top', name: 'Center Top' },
                            { id: 'center center', name: 'Center' },
                            { id: 'center bottom', name: 'Center Bottom' },
                            { id: 'right top', name: 'Right Top' },
                            { id: 'right center', name: 'Right Center' },
                            { id: 'right bottom', name: 'Right Bottom' },
                        ],
                    },
                    {
                        type: 'select',
                        name: 'pgBgSize',
                        label: 'Image Size',
                        changeProp: 1,
                        options: [
                            { id: 'cover', name: 'Cover' },
                            { id: 'contain', name: 'Contain' },
                            { id: 'auto', name: 'Auto' },
                        ],
                    },
                    {
                        type: 'select',
                        name: 'pgBgRepeat',
                        label: 'Image Repeat',
                        changeProp: 1,
                        options: [
                            { id: 'no-repeat', name: 'No Repeat' },
                            { id: 'repeat', name: 'Repeat' },
                            { id: 'repeat-x', name: 'Repeat X' },
                            { id: 'repeat-y', name: 'Repeat Y' },
                        ],
                    },
                    {
                        type: 'pg-trait-heading',
                        name: 'pgSecContainer',
                        label: ' ',
                        title: 'Container',
                    },
                    {
                        type: 'select',
                        name: 'pgPaddingX',
                        label: 'Padding X',
                        options: [
                            { id: 'none', name: 'None' },
                            { id: 'compact', name: 'Compact' },
                            { id: 'comfortable', name: 'Comfortable' },
                        ],
                        changeProp: 1,
                    },
                    {
                        type: 'select',
                        name: 'pgPaddingY',
                        label: 'Padding Y',
                        changeProp: 1,
                        options: [
                            { id: 'none', name: 'None' },
                            { id: 'sm', name: 'Small' },
                            { id: 'md', name: 'Medium' },
                            { id: 'lg', name: 'Large' },
                        ],
                    },
                    {
                        type: 'select',
                        name: 'pgTag',
                        label: 'Tag',
                        changeProp: 1,
                        options: [
                            { id: 'section', name: 'SECTION' },
                            { id: 'div', name: 'DIV' },
                            { id: 'main', name: 'MAIN' },
                            { id: 'article', name: 'ARTICLE' },
                        ],
                    },
                ],
            },

            init() {
                hydrateContainerProps(this);
                const styleProps = [
                    'pgTag',
                    'pgContentWidth',
                    'pgFullWidth',
                    'pgBoxedWidth',
                    'pgMinHeight',
                    'pgMinHeightUnit',
                    'pgPaddingX',
                    'pgPaddingY',
                    'pgLayout',
                    'pgGridOutline',
                    'pgCols',
                    'pgRows',
                    'pgGapX',
                    'pgGapY',
                    'pgGapUnit',
                    'pgGapLinked',
                    'pgItems',
                    'pgJustify',
                    'pgFlexDir',
                    'pgWrap',
                    'pgFullWidthTablet',
                    'pgFullWidthMobile',
                    'pgBoxedWidthTablet',
                    'pgBoxedWidthMobile',
                    'pgMinHeightTablet',
                    'pgMinHeightMobile',
                    'pgColsTablet',
                    'pgColsMobile',
                    'pgRowsTablet',
                    'pgRowsMobile',
                    'pgGapXTablet',
                    'pgGapXMobile',
                    'pgGapYTablet',
                    'pgGapYMobile',
                    'pgFlexDirTablet',
                    'pgFlexDirMobile',
                    'pgWrapTablet',
                    'pgWrapMobile',
                    'pgJustifyTablet',
                    'pgJustifyMobile',
                    'pgItemsTablet',
                    'pgItemsMobile',
                    'pgBgType',
                    'pgBgColor',
                    'pgBgImage',
                    'pgBgPosition',
                    'pgBgSize',
                    'pgBgRepeat',
                ];

                this.on(styleProps.map((prop) => `change:${prop}`).join(' '), () => applyContainerClasses(this));
                this.on('change:pgContentWidth change:pgLayout change:pgDevice change:pgBgType', () => {
                    requestAnimationFrame(() => syncContainerTraitRows(this));
                });

                applyContainerClasses(this);
                requestAnimationFrame(() => syncContainerTraitRows(this));
            },
        },
    });

    ensureContainerEditorStyles(editor);

    editor.on('component:selected', (component) => {
        if (!component) return;

        if (componentHasClass(component, 'pg-container-inner')) {
            const parent = component.parent?.();
            if (parent && parent.get?.('type') === 'pg-container') {
                editor.select(parent);
                requestAnimationFrame(() => syncContainerTraitRows(parent));
                return;
            }
        }

        if (component.get?.('type') !== 'pg-container') return;
        requestAnimationFrame(() => syncContainerTraitRows(component));
    });
}
