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
            `input[name="${name}"], select[name="${name}"], textarea[name="${name}"]`
        );
        if (!field) return;
        row.style.display = visible ? '' : 'none';
    });
}

function syncContainerTraitRows(model) {
    const contentWidth = model?.get?.('pgContentWidth') === 'full' ? 'full' : 'boxed';
    const layout = model?.get?.('pgLayout') === 'flex' ? 'flex' : 'grid';

    setTraitRowVisible('pgFullWidth', contentWidth === 'full');
    setTraitRowVisible('pgBoxedWidth', contentWidth === 'boxed');
    setTraitRowVisible('pgGridOutline', layout === 'grid');
    setTraitRowVisible('pgCols', layout === 'grid');
    setTraitRowVisible('pgRows', layout === 'grid');
    setTraitRowVisible('pgFlexDir', layout === 'flex');
    setTraitRowVisible('pgWrap', layout === 'flex');
    setTraitRowVisible('pgGapControl', layout === 'flex' || layout === 'grid');
    setTraitRowVisible('pgJustify', layout === 'flex');
    setTraitRowVisible('pgItems', layout === 'flex');
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

function ensureInnerWrapper(model) {
    const comps = model.components?.();
    if (!comps) return null;

    let inner = findInnerWrapper(model);

    if (!inner) {
        const previousChildren = [];
        comps.each((child) => previousChildren.push(child.toJSON()));

        comps.reset([
            {
                type: 'default',
                tagName: 'div',
                attributes: { class: 'pg-layout pg-container-inner w-full' },
                components: previousChildren,
            },
        ]);

        return comps.at(0);
    }

    const outOfWrapperChildren = [];
    comps.each((child) => {
        if (child !== inner) outOfWrapperChildren.push(child);
    });

    if (outOfWrapperChildren.length) {
        const moved = outOfWrapperChildren.map((child) => child.toJSON());
        outOfWrapperChildren.forEach((child) => comps.remove(child));
        inner.components().add(moved);
    }

    return inner;
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

    const explicitFull = outerClasses.includes('pg-content-full');
    const explicitBoxed = outerClasses.includes('pg-content-boxed');
    const parsedOuterWidth = parseCssDimension(outerStyles.width);
    const parsedOuterMax = parseCssDimension(outerStyles['max-width']);
    const parsedInnerMax = parseCssDimension(innerStyles['max-width']);
    const parsedMinHeight = parseCssDimension(outerStyles['min-height']);

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

    if (innerClasses.includes('flex-row-reverse')) {
        model.set('pgFlexDir', 'row-reverse', { silent: true });
    } else if (innerClasses.includes('flex-col-reverse')) {
        model.set('pgFlexDir', 'col-reverse', { silent: true });
    } else if (innerClasses.includes('flex-col')) {
        model.set('pgFlexDir', 'col', { silent: true });
    } else {
        model.set('pgFlexDir', 'row', { silent: true });
    }

    if (innerClasses.includes('flex-nowrap')) {
        model.set('pgWrap', 'nowrap', { silent: true });
    } else {
        model.set('pgWrap', 'wrap', { silent: true });
    }
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

    if (gapLinked) {
        gapY = gapX;
    }

    if (model.get('tagName') !== tag) {
        model.set('tagName', tag);
    }

    const inner = ensureInnerWrapper(model);
    if (!inner) return;

    const outerCurrent = classListFromString(model.getAttributes()?.class);
    const innerCurrent = classListFromString(inner.getAttributes?.()?.class);
    const outerCleaned = cleanOuterContainerClasses(outerCurrent);
    const innerCleaned = cleanInnerContainerClasses(innerCurrent);

    const innerLayoutClasses = [];
    if (layout === 'flex') {
        const directionClassMap = {
            row: 'flex-row',
            'row-reverse': 'flex-row-reverse',
            col: 'flex-col',
            'col-reverse': 'flex-col-reverse',
        };
        innerLayoutClasses.push('flex', directionClassMap[flexDir] || 'flex-row');
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

    model.setStyle(outerStyles);

    const innerStyles = { ...(inner.getStyle?.() || {}) };
    innerStyles.width = '100%';
    innerStyles['margin-left'] = 'auto';
    innerStyles['margin-right'] = 'auto';
    innerStyles['max-width'] = contentWidth === 'boxed' ? `${boxedWidth}px` : 'none';
    innerStyles['column-gap'] = `${gapX}${gapUnit}`;
    innerStyles['row-gap'] = `${gapY}${gapUnit}`;

    if (layout === 'grid') {
        innerStyles['grid-template-columns'] = `repeat(${cols}, minmax(0, 1fr))`;
        innerStyles['grid-template-rows'] = `repeat(${rows}, minmax(0, 1fr))`;
    } else {
        delete innerStyles['grid-template-columns'];
        delete innerStyles['grid-template-rows'];
    }

    delete innerStyles['min-height'];
    inner.setStyle(innerStyles);

    if (gapLinked && String(model.get('pgGapY')) !== String(gapX)) {
        model.set('pgGapY', String(gapX), { silent: true });
    }
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
                        attributes: {
                            class: 'pg-layout pg-container-inner w-full grid grid-cols-1 items-stretch justify-start',
                        },
                        components: [
                            {
                                type: 'default',
                                tagName: 'div',
                                attributes: {
                                    class: 'pg-layout min-h-12 rounded-xl border border-dashed border-slate-300 p-4 text-slate-600',
                                },
                                components: [{ type: 'text', content: 'Container content...' }],
                            },
                        ],
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
                traits: [
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
                        type: 'select',
                        name: 'pgContentWidth',
                        label: 'Content Width',
                        changeProp: 1,
                        options: [
                            { id: 'boxed', name: 'Inside Boxed' },
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
                        label: 'Inner Box Width (px)',
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
                    {
                        type: 'select',
                        name: 'pgPaddingX',
                        label: 'Padding X',
                        changeProp: 1,
                        options: [
                            { id: 'none', name: 'None' },
                            { id: 'compact', name: 'Compact' },
                            { id: 'comfortable', name: 'Comfortable' },
                        ],
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
                        type: 'pg-icon-select',
                        name: 'pgFlexDir',
                        label: 'Direction',
                        changeProp: 1,
                        options: [
                            { id: 'col-reverse', name: 'Up', icon: FLEX_ICON_ARROW_UP },
                            { id: 'row', name: 'Right', icon: FLEX_ICON_ARROW_RIGHT },
                            { id: 'col', name: 'Down', icon: FLEX_ICON_ARROW_DOWN },
                            { id: 'row-reverse', name: 'Left', icon: FLEX_ICON_ARROW_LEFT },
                        ],
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgWrap',
                        label: 'Wrap',
                        hint: 'Items can stay in one line (No Wrap) or move to multiple lines (Wrap).',
                        changeProp: 1,
                        options: [
                            { id: 'wrap', name: 'Wrap', icon: FLEX_ICON_WRAP },
                            { id: 'nowrap', name: 'No Wrap', icon: FLEX_ICON_NOWRAP },
                        ],
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
                        type: 'pg-icon-select',
                        name: 'pgItems',
                        label: 'Align Items',
                        options: [
                            { id: 'start', name: 'Start', icon: FLEX_ICON_ALIGN_START },
                            { id: 'center', name: 'Center', icon: FLEX_ICON_ALIGN_CENTER },
                            { id: 'end', name: 'End', icon: FLEX_ICON_ALIGN_END },
                            { id: 'stretch', name: 'Stretch', icon: FLEX_ICON_ALIGN_STRETCH },
                        ],
                        changeProp: 1,
                    },
                    {
                        type: 'pg-icon-select',
                        name: 'pgJustify',
                        label: 'Justify Content',
                        options: [
                            { id: 'start', name: 'Start', icon: FLEX_ICON_JUSTIFY_START },
                            { id: 'center', name: 'Center', icon: FLEX_ICON_JUSTIFY_CENTER },
                            { id: 'end', name: 'End', icon: FLEX_ICON_JUSTIFY_END },
                            { id: 'between', name: 'Between', icon: FLEX_ICON_JUSTIFY_BETWEEN },
                            { id: 'around', name: 'Around', icon: FLEX_ICON_JUSTIFY_AROUND },
                            { id: 'evenly', name: 'Evenly', icon: FLEX_ICON_JUSTIFY_EVENLY },
                        ],
                        changeProp: 1,
                    },
                ],
            },

            init() {
                hydrateContainerProps(this);
                this.on(
                    'change:pgTag change:pgContentWidth change:pgFullWidth change:pgBoxedWidth change:pgMinHeight change:pgMinHeightUnit change:pgPaddingX change:pgPaddingY change:pgLayout change:pgGridOutline change:pgCols change:pgRows change:pgGapX change:pgGapY change:pgGapUnit change:pgGapLinked change:pgItems change:pgJustify change:pgFlexDir change:pgWrap',
                    () => applyContainerClasses(this)
                );
                this.on('change:pgContentWidth change:pgLayout', () => {
                    requestAnimationFrame(() => syncContainerTraitRows(this));
                });

                applyContainerClasses(this);
                requestAnimationFrame(() => syncContainerTraitRows(this));
            },
        },
    });

    editor.addStyle(`
        .pg-container-inner[data-pg-grid-outline="1"] > * {
            outline: 1px dashed rgba(192, 132, 252, 0.75);
            outline-offset: -1px;
        }
    `);

    editor.on('component:selected', (component) => {
        if (!component || component.get?.('type') !== 'pg-container') return;
        requestAnimationFrame(() => syncContainerTraitRows(component));
    });
}
