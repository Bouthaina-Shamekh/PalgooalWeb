// resources/js/dashboard/builder/grapes/style-manager.js

export function registerStyleManager(editor, { isRtl = false } = {}) {
    const sm = editor.StyleManager;

    // Reset any previously registered sectors.
    sm.getSectors().reset([]);

    // Temporary normalization: force English labels to avoid mojibake from legacy Arabic strings.
    const t = (en, _ar) => en;

    const parseBorderRadiusValue = (value) => {
        const source = String(value || '')
            .split('/')[0]
            .trim();

        if (!source) {
            return { unit: 'px', values: [0, 0, 0, 0] };
        }

        const tokens = source.split(/\s+/).filter(Boolean);
        const parsedTokens = tokens
            .map((token) => {
                const match = String(token).trim().match(/^(-?\d*\.?\d+)([a-z%]*)$/i);
                if (!match) return null;
                const next = Number(match[1]);
                if (!Number.isFinite(next)) return null;
                return { value: next, unit: (match[2] || '').toLowerCase() };
            })
            .filter(Boolean);

        if (!parsedTokens.length) {
            return { unit: 'px', values: [0, 0, 0, 0] };
        }

        const firstUnit = parsedTokens.find((token) => token.unit)?.unit || 'px';
        const unit = firstUnit === '%' ? '%' : 'px';
        const numbers = parsedTokens.map((token) => token.value);

        let values;
        if (numbers.length === 1) {
            values = [numbers[0], numbers[0], numbers[0], numbers[0]];
        } else if (numbers.length === 2) {
            values = [numbers[0], numbers[1], numbers[0], numbers[1]];
        } else if (numbers.length === 3) {
            values = [numbers[0], numbers[1], numbers[2], numbers[1]];
        } else {
            values = [numbers[0], numbers[1], numbers[2], numbers[3]];
        }

        return { unit, values };
    };

    const parseBorderWidthValue = (value) => {
        const source = String(value || '').trim();

        if (!source) {
            return { unit: 'px', values: [0, 0, 0, 0] };
        }

        const tokens = source.split(/\s+/).filter(Boolean);
        const parsedTokens = tokens
            .map((token) => {
                const match = String(token).trim().match(/^(-?\d*\.?\d+)([a-z%]*)$/i);
                if (!match) return null;
                const next = Number(match[1]);
                if (!Number.isFinite(next)) return null;
                return { value: next, unit: (match[2] || '').toLowerCase() };
            })
            .filter(Boolean);

        if (!parsedTokens.length) {
            return { unit: 'px', values: [0, 0, 0, 0] };
        }

        const firstUnit = parsedTokens.find((token) => token.unit)?.unit || 'px';
        const unit = ['em', 'rem'].includes(firstUnit) ? firstUnit : 'px';
        const numbers = parsedTokens.map((token) => token.value);

        let values;
        if (numbers.length === 1) {
            values = [numbers[0], numbers[0], numbers[0], numbers[0]];
        } else if (numbers.length === 2) {
            values = [numbers[0], numbers[1], numbers[0], numbers[1]];
        } else if (numbers.length === 3) {
            values = [numbers[0], numbers[1], numbers[2], numbers[1]];
        } else {
            values = [numbers[0], numbers[1], numbers[2], numbers[3]];
        }

        return { unit, values };
    };

    const parseSingleDimensionValue = (value) => {
        const source = String(value || '').trim();
        if (!source) return { value: null, unit: '' };

        const match = source.match(/^(-?\d*\.?\d+)([a-z%]*)$/i);
        if (!match) return { value: null, unit: '' };

        const next = Number(match[1]);
        if (!Number.isFinite(next)) return { value: null, unit: '' };

        return { value: next, unit: (match[2] || '').toLowerCase() };
    };

    const toNumber = (value, fallback) => {
        const next = Number(value);
        return Number.isFinite(next) ? next : fallback;
    };

    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const DEFAULT_SIZE_RANGES = {
        px: { min: 0, max: 2400, step: 1 },
        '%': { min: 0, max: 100, step: 1 },
        vw: { min: 0, max: 100, step: 1 },
        vh: { min: 0, max: 100, step: 1 },
        rem: { min: 0, max: 120, step: 0.1 },
        em: { min: 0, max: 120, step: 0.1 },
    };

    const DEFAULT_FILTER_VALUES = {
        brightness: 100,
        contrast: 100,
        saturate: 100,
        hueRotate: 0,
        blur: 0,
    };

    const FILTER_CONTROLS = [
        { key: 'brightness', label: 'Brightness', unit: '%', min: 0, max: 200, step: 1, default: 100 },
        { key: 'contrast', label: 'Contrast', unit: '%', min: 0, max: 200, step: 1, default: 100 },
        { key: 'saturate', label: 'Saturate', unit: '%', min: 0, max: 200, step: 1, default: 100 },
        { key: 'hueRotate', label: 'Hue Rotate', unit: 'deg', min: 0, max: 360, step: 1, default: 0 },
        { key: 'blur', label: 'Blur', unit: 'px', min: 0, max: 20, step: 1, default: 0 },
    ];

    const getSizeRangeByUnit = (props, unit) => {
        const unitRanges = props?.pgUnitRanges || {};
        const fromProps = unitRanges?.[unit] || {};
        const fromDefaults = DEFAULT_SIZE_RANGES?.[unit] || {};

        const min = toNumber(fromProps.min, toNumber(fromDefaults.min, toNumber(props?.min, 0)));
        const max = toNumber(fromProps.max, toNumber(fromDefaults.max, toNumber(props?.max, 2400)));
        const step = toNumber(fromProps.step, toNumber(fromDefaults.step, toNumber(props?.step, 1)));

        return {
            min,
            max: max >= min ? max : min,
            step: step > 0 ? step : 1,
        };
    };

    const parseCssFilterValue = (value) => {
        const source = String(value || '').trim().toLowerCase();
        const defaults = { ...DEFAULT_FILTER_VALUES };
        if (!source || source === 'none') return defaults;

        const read = (pattern, fallback) => {
            const matched = source.match(pattern);
            if (!matched) return fallback;
            return toNumber(matched[1], fallback);
        };

        return {
            brightness: read(/brightness\((-?\d*\.?\d+)%\)/, defaults.brightness),
            contrast: read(/contrast\((-?\d*\.?\d+)%\)/, defaults.contrast),
            saturate: read(/saturate\((-?\d*\.?\d+)%\)/, defaults.saturate),
            hueRotate: read(/hue-rotate\((-?\d*\.?\d+)deg\)/, defaults.hueRotate),
            blur: read(/blur\((-?\d*\.?\d+)px\)/, defaults.blur),
        };
    };

    if (!sm.getType('pg-border-radius')) {
        sm.addType('pg-border-radius', {
            create({ change }) {
                const el = document.createElement('div');
                el.className = 'pg-sm-radius';
                el.innerHTML = `
                    <div class="pg-sm-radius__head">
                        <select class="pg-sm-radius__unit" aria-label="Border radius unit">
                            <option value="px">px</option>
                            <option value="%">%</option>
                        </select>
                    </div>
                    <div class="pg-sm-radius__row">
                        <button type="button" class="pg-sm-radius__link is-linked" data-linked="true" title="Link values" aria-label="Link values">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07L11 4" />
                                <path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 1 0 7.07 7.07L13 20" />
                            </svg>
                        </button>
                        <div class="pg-sm-radius__grid">
                            <input type="number" min="0" step="1" value="0" data-corner="top" />
                            <input type="number" min="0" step="1" value="0" data-corner="right" />
                            <input type="number" min="0" step="1" value="0" data-corner="bottom" />
                            <input type="number" min="0" step="1" value="0" data-corner="left" />
                        </div>
                    </div>
                    <div class="pg-sm-radius__labels">
                        <span>Top</span>
                        <span>Right</span>
                        <span>Bottom</span>
                        <span>Left</span>
                    </div>
                `;

                const unitEl = el.querySelector('.pg-sm-radius__unit');
                const linkEl = el.querySelector('.pg-sm-radius__link');
                const inputs = Array.from(el.querySelectorAll('.pg-sm-radius__grid input'));

                const isLinked = () => linkEl?.dataset.linked !== 'false';
                const setLinked = (linked) => {
                    if (!linkEl) return;
                    linkEl.dataset.linked = linked ? 'true' : 'false';
                    linkEl.classList.toggle('is-linked', linked);
                };
                const mirrorLinkedValue = (sourceInput) => {
                    if (!isLinked()) return;
                    const value = sourceInput?.value ?? '0';
                    inputs.forEach((input) => {
                        if (input !== sourceInput) input.value = value;
                    });
                };

                unitEl?.addEventListener('change', () => change({ partial: false }));

                inputs.forEach((input) => {
                    input.addEventListener('input', () => {
                        mirrorLinkedValue(input);
                        change({ partial: true });
                    });
                    input.addEventListener('change', () => {
                        mirrorLinkedValue(input);
                        change({ partial: false });
                    });
                });

                linkEl?.addEventListener('click', () => {
                    const next = !isLinked();
                    setLinked(next);
                    if (next) mirrorLinkedValue(inputs[0]);
                    change({ partial: false });
                });

                return el;
            },

            emit({ el, updateStyle }, { partial } = {}) {
                const unitEl = el.querySelector('.pg-sm-radius__unit');
                const unit = unitEl?.value === '%' ? '%' : 'px';
                const readCorner = (corner) => {
                    const input = el.querySelector(`[data-corner="${corner}"]`);
                    const next = Number(input?.value);
                    return `${Number.isFinite(next) ? next : 0}${unit}`;
                };

                const value = [
                    readCorner('top'),
                    readCorner('right'),
                    readCorner('bottom'),
                    readCorner('left'),
                ].join(' ');

                updateStyle(value, { partial: !!partial });
            },

            update({ value, el }) {
                const parsed = parseBorderRadiusValue(value);
                const unitEl = el.querySelector('.pg-sm-radius__unit');
                const linkEl = el.querySelector('.pg-sm-radius__link');
                const values = parsed.values || [0, 0, 0, 0];

                const topEl = el.querySelector('[data-corner="top"]');
                const rightEl = el.querySelector('[data-corner="right"]');
                const bottomEl = el.querySelector('[data-corner="bottom"]');
                const leftEl = el.querySelector('[data-corner="left"]');

                if (unitEl) unitEl.value = parsed.unit === '%' ? '%' : 'px';
                if (topEl) topEl.value = String(values[0] ?? 0);
                if (rightEl) rightEl.value = String(values[1] ?? 0);
                if (bottomEl) bottomEl.value = String(values[2] ?? 0);
                if (leftEl) leftEl.value = String(values[3] ?? 0);

                const linked = values.every((item) => item === values[0]);
                if (linkEl) {
                    linkEl.dataset.linked = linked ? 'true' : 'false';
                    linkEl.classList.toggle('is-linked', linked);
                }
            },
        });
    }

    if (!sm.getType('pg-border-width')) {
        sm.addType('pg-border-width', {
            create({ change }) {
                const el = document.createElement('div');
                el.className = 'pg-sm-width';
                el.innerHTML = `
                    <div class="pg-sm-width__head">
                        <select class="pg-sm-width__unit" aria-label="Border width unit">
                            <option value="px">px</option>
                            <option value="em">em</option>
                            <option value="rem">rem</option>
                        </select>
                    </div>
                    <div class="pg-sm-width__row">
                        <button type="button" class="pg-sm-width__link is-linked" data-linked="true" title="Link values" aria-label="Link values">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07L11 4" />
                                <path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 1 0 7.07 7.07L13 20" />
                            </svg>
                        </button>
                        <div class="pg-sm-width__grid">
                            <input type="number" min="0" step="1" value="0" data-side="top" />
                            <input type="number" min="0" step="1" value="0" data-side="right" />
                            <input type="number" min="0" step="1" value="0" data-side="bottom" />
                            <input type="number" min="0" step="1" value="0" data-side="left" />
                        </div>
                    </div>
                    <div class="pg-sm-width__labels">
                        <span>Top</span>
                        <span>Right</span>
                        <span>Bottom</span>
                        <span>Left</span>
                    </div>
                `;

                const unitEl = el.querySelector('.pg-sm-width__unit');
                const linkEl = el.querySelector('.pg-sm-width__link');
                const inputs = Array.from(el.querySelectorAll('.pg-sm-width__grid input'));

                const isLinked = () => linkEl?.dataset.linked !== 'false';
                const setLinked = (linked) => {
                    if (!linkEl) return;
                    linkEl.dataset.linked = linked ? 'true' : 'false';
                    linkEl.classList.toggle('is-linked', linked);
                };
                const mirrorLinkedValue = (sourceInput) => {
                    if (!isLinked()) return;
                    const value = sourceInput?.value ?? '0';
                    inputs.forEach((input) => {
                        if (input !== sourceInput) input.value = value;
                    });
                };

                unitEl?.addEventListener('change', () => change({ partial: false }));

                inputs.forEach((input) => {
                    input.addEventListener('input', () => {
                        mirrorLinkedValue(input);
                        change({ partial: true });
                    });
                    input.addEventListener('change', () => {
                        mirrorLinkedValue(input);
                        change({ partial: false });
                    });
                });

                linkEl?.addEventListener('click', () => {
                    const next = !isLinked();
                    setLinked(next);
                    if (next) mirrorLinkedValue(inputs[0]);
                    change({ partial: false });
                });

                return el;
            },

            emit({ el, updateStyle }, { partial } = {}) {
                const unitEl = el.querySelector('.pg-sm-width__unit');
                const unit = ['em', 'rem'].includes(unitEl?.value) ? unitEl.value : 'px';
                const readSide = (side) => {
                    const input = el.querySelector(`[data-side="${side}"]`);
                    const next = Number(input?.value);
                    return `${Number.isFinite(next) ? next : 0}${unit}`;
                };

                const value = [
                    readSide('top'),
                    readSide('right'),
                    readSide('bottom'),
                    readSide('left'),
                ].join(' ');

                updateStyle(value, { partial: !!partial });
            },

            update({ value, el }) {
                const parsed = parseBorderWidthValue(value);
                const unitEl = el.querySelector('.pg-sm-width__unit');
                const linkEl = el.querySelector('.pg-sm-width__link');
                const values = parsed.values || [0, 0, 0, 0];

                const topEl = el.querySelector('[data-side="top"]');
                const rightEl = el.querySelector('[data-side="right"]');
                const bottomEl = el.querySelector('[data-side="bottom"]');
                const leftEl = el.querySelector('[data-side="left"]');

                if (unitEl) unitEl.value = ['em', 'rem'].includes(parsed.unit) ? parsed.unit : 'px';
                if (topEl) topEl.value = String(values[0] ?? 0);
                if (rightEl) rightEl.value = String(values[1] ?? 0);
                if (bottomEl) bottomEl.value = String(values[2] ?? 0);
                if (leftEl) leftEl.value = String(values[3] ?? 0);

                const linked = values.every((item) => item === values[0]);
                if (linkEl) {
                    linkEl.dataset.linked = linked ? 'true' : 'false';
                    linkEl.classList.toggle('is-linked', linked);
                }
            },
        });
    }

    if (!sm.getType('pg-size-range')) {
        sm.addType('pg-size-range', {
            create({ props, change }) {
                const el = document.createElement('div');
                el.className = 'pg-sm-size';

                const units = Array.isArray(props?.units) && props.units.length ? props.units : ['px'];
                const defaultUnit = units.includes(props?.unit) ? props.unit : units[0];
                const unitLabels = props?.pgUnitLabels || {};
                const getUnitLabel = (unit) => {
                    if (Object.prototype.hasOwnProperty.call(unitLabels, unit)) {
                        return String(unitLabels[unit] ?? '');
                    }
                    if (unit === '') return 'unitless';
                    return String(unit || '');
                };

                el.innerHTML = `
                    <div class="pg-sm-size__unit-row">
                        <select class="pg-sm-size__unit" aria-label="${props?.name || 'Unit'}">
                            ${units.map((unit) => `<option value="${unit}">${getUnitLabel(unit)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="pg-sm-size__control-row">
                        <input class="pg-sm-size__number" type="number" />
                        <input class="pg-sm-size__range" type="range" />
                    </div>
                `;

                const unitEl = el.querySelector('.pg-sm-size__unit');
                const numberEl = el.querySelector('.pg-sm-size__number');
                const rangeEl = el.querySelector('.pg-sm-size__range');

                const syncBounds = () => {
                    const unit = units.includes(unitEl?.value) ? unitEl.value : defaultUnit;
                    const bounds = getSizeRangeByUnit(props, unit);

                    numberEl.min = String(bounds.min);
                    numberEl.max = String(bounds.max);
                    numberEl.step = String(bounds.step);

                    rangeEl.min = String(bounds.min);
                    rangeEl.max = String(bounds.max);
                    rangeEl.step = String(bounds.step);

                    return bounds;
                };

                const syncInputs = (next, options = {}) => {
                    const bounds = syncBounds();
                    const normalized = clamp(toNumber(next, bounds.min), bounds.min, bounds.max);
                    const finalValue =
                        bounds.step >= 1 ? Math.round(normalized) : Number(normalized.toFixed(2));

                    numberEl.value = String(finalValue);
                    rangeEl.value = String(finalValue);

                    if (!options.silent) {
                        change({ partial: !!options.partial });
                    }
                };

                if (unitEl) unitEl.value = defaultUnit;
                syncInputs(props?.defaults, { silent: true });

                unitEl?.addEventListener('change', () => {
                    syncInputs(numberEl.value, { partial: false });
                });

                numberEl?.addEventListener('input', () => {
                    syncInputs(numberEl.value, { partial: true });
                });

                numberEl?.addEventListener('change', () => {
                    syncInputs(numberEl.value, { partial: false });
                });

                rangeEl?.addEventListener('input', () => {
                    syncInputs(rangeEl.value, { partial: true });
                });

                rangeEl?.addEventListener('change', () => {
                    syncInputs(rangeEl.value, { partial: false });
                });

                return el;
            },

            emit({ el, props, updateStyle }, { partial } = {}) {
                const unitEl = el.querySelector('.pg-sm-size__unit');
                const numberEl = el.querySelector('.pg-sm-size__number');

                const units = Array.isArray(props?.units) && props.units.length ? props.units : ['px'];
                const defaultUnit = units.includes(props?.unit) ? props.unit : units[0];
                const unit = units.includes(unitEl?.value) ? unitEl.value : defaultUnit;
                const bounds = getSizeRangeByUnit(props, unit);

                const rawValue = String(numberEl?.value ?? '').trim();
                if (!rawValue && props?.pgAllowEmpty) {
                    updateStyle('', { partial: !!partial });
                    return;
                }

                const numeric = clamp(toNumber(rawValue, bounds.min), bounds.min, bounds.max);
                const finalValue = bounds.step >= 1 ? Math.round(numeric) : Number(numeric.toFixed(2));
                updateStyle(`${finalValue}${unit}`, { partial: !!partial });
            },

            update({ value, props, el }) {
                const unitEl = el.querySelector('.pg-sm-size__unit');
                const numberEl = el.querySelector('.pg-sm-size__number');
                const rangeEl = el.querySelector('.pg-sm-size__range');

                const units = Array.isArray(props?.units) && props.units.length ? props.units : ['px'];
                const defaultUnit = units.includes(props?.unit) ? props.unit : units[0];

                const parsedCurrent = parseSingleDimensionValue(value);
                const parsedDefault = parseSingleDimensionValue(props?.defaults);
                const selectedUnit = units.includes(parsedCurrent.unit)
                    ? parsedCurrent.unit
                    : units.includes(parsedDefault.unit)
                    ? parsedDefault.unit
                    : defaultUnit;

                if (unitEl) unitEl.value = selectedUnit;
                const bounds = getSizeRangeByUnit(props, selectedUnit);

                if (numberEl) {
                    numberEl.min = String(bounds.min);
                    numberEl.max = String(bounds.max);
                    numberEl.step = String(bounds.step);
                }
                if (rangeEl) {
                    rangeEl.min = String(bounds.min);
                    rangeEl.max = String(bounds.max);
                    rangeEl.step = String(bounds.step);
                }

                const sourceValue = Number.isFinite(parsedCurrent.value)
                    ? parsedCurrent.value
                    : parsedDefault.value;
                const next = clamp(toNumber(sourceValue, bounds.min), bounds.min, bounds.max);
                const finalValue = bounds.step >= 1 ? Math.round(next) : Number(next.toFixed(2));

                if (numberEl) numberEl.value = String(finalValue);
                if (rangeEl) rangeEl.value = String(finalValue);
            },
        });
    }

    if (!sm.getType('pg-css-filters')) {
        sm.addType('pg-css-filters', {
            create({ change }) {
                const el = document.createElement('div');
                el.className = 'pg-sm-filters';
                el.innerHTML = `
                    <button type="button" class="pg-sm-filters__toggle" aria-label="Edit CSS Filters" title="Edit CSS Filters">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                        </svg>
                    </button>
                    <div class="pg-sm-filters__panel" hidden>
                        ${FILTER_CONTROLS.map((control) => `
                            <div class="pg-sm-filters__item">
                                <div class="pg-sm-filters__label">${control.label}</div>
                                <div class="pg-sm-filters__control">
                                    <input
                                        type="number"
                                        class="pg-sm-filters__number"
                                        data-filter-number="${control.key}"
                                        min="${control.min}"
                                        max="${control.max}"
                                        step="${control.step}"
                                    />
                                    <input
                                        type="range"
                                        class="pg-sm-filters__range"
                                        data-filter-range="${control.key}"
                                        min="${control.min}"
                                        max="${control.max}"
                                        step="${control.step}"
                                    />
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;

                const panelEl = el.querySelector('.pg-sm-filters__panel');
                const toggleEl = el.querySelector('.pg-sm-filters__toggle');

                const syncControl = (control, next, options = {}) => {
                    const numberEl = el.querySelector(`[data-filter-number="${control.key}"]`);
                    const rangeEl = el.querySelector(`[data-filter-range="${control.key}"]`);
                    if (!numberEl || !rangeEl) return;

                    const normalized = clamp(toNumber(next, control.default), control.min, control.max);
                    numberEl.value = String(normalized);
                    rangeEl.value = String(normalized);

                    if (!options.silent) {
                        change({ partial: !!options.partial });
                    }
                };

                FILTER_CONTROLS.forEach((control) => {
                    const numberEl = el.querySelector(`[data-filter-number="${control.key}"]`);
                    const rangeEl = el.querySelector(`[data-filter-range="${control.key}"]`);
                    if (!numberEl || !rangeEl) return;

                    syncControl(control, control.default, { silent: true });

                    numberEl.addEventListener('input', () => {
                        syncControl(control, numberEl.value, { partial: true });
                    });
                    numberEl.addEventListener('change', () => {
                        syncControl(control, numberEl.value, { partial: false });
                    });

                    rangeEl.addEventListener('input', () => {
                        syncControl(control, rangeEl.value, { partial: true });
                    });
                    rangeEl.addEventListener('change', () => {
                        syncControl(control, rangeEl.value, { partial: false });
                    });
                });

                toggleEl?.addEventListener('click', () => {
                    if (!panelEl) return;
                    panelEl.hidden = !panelEl.hidden;
                    toggleEl.classList.toggle('is-open', !panelEl.hidden);
                });

                return el;
            },

            emit({ el, updateStyle }, { partial } = {}) {
                const values = {};

                FILTER_CONTROLS.forEach((control) => {
                    const numberEl = el.querySelector(`[data-filter-number="${control.key}"]`);
                    const raw = numberEl?.value;
                    values[control.key] = clamp(toNumber(raw, control.default), control.min, control.max);
                });

                const isDefault = FILTER_CONTROLS.every((control) => values[control.key] === control.default);
                if (isDefault) {
                    updateStyle('none', { partial: !!partial });
                    return;
                }

                const filterValue = [
                    `brightness(${values.brightness}%)`,
                    `contrast(${values.contrast}%)`,
                    `saturate(${values.saturate}%)`,
                    `hue-rotate(${values.hueRotate}deg)`,
                    `blur(${values.blur}px)`,
                ].join(' ');

                updateStyle(filterValue, { partial: !!partial });
            },

            update({ value, el }) {
                const parsed = parseCssFilterValue(value);

                FILTER_CONTROLS.forEach((control) => {
                    const numberEl = el.querySelector(`[data-filter-number="${control.key}"]`);
                    const rangeEl = el.querySelector(`[data-filter-range="${control.key}"]`);
                    if (!numberEl || !rangeEl) return;

                    const normalized = clamp(
                        toNumber(parsed?.[control.key], control.default),
                        control.min,
                        control.max
                    );

                    numberEl.value = String(normalized);
                    rangeEl.value = String(normalized);
                });
            },
        });
    }

    const fontFamilies = [
        { id: 'inherit', name: t('Default', 'Default') },
        { id: 'Cairo, ui-sans-serif, system-ui', name: 'Cairo' },
        { id: 'Tajawal, ui-sans-serif, system-ui', name: 'Tajawal' },
        { id: 'Almarai, ui-sans-serif, system-ui', name: 'Almarai' },
        { id: '"Poppins", ui-sans-serif, system-ui', name: 'Poppins' },
        { id: '"Montserrat", ui-sans-serif, system-ui', name: 'Montserrat' },
        { id: '"Nunito", ui-sans-serif, system-ui', name: 'Nunito' },
        { id: '"Raleway", ui-sans-serif, system-ui', name: 'Raleway' },
        { id: '"Roboto", ui-sans-serif, system-ui', name: 'Roboto' },
        { id: '"Open Sans", ui-sans-serif, system-ui', name: 'Open Sans' },
        { id: '"Lato", ui-sans-serif, system-ui', name: 'Lato' },
        { id: '"Source Sans 3", ui-sans-serif, system-ui', name: 'Source Sans 3' },
        { id: '"Playfair Display", ui-serif, Georgia, serif', name: 'Playfair Display' },
        { id: '"Merriweather", ui-serif, Georgia, serif', name: 'Merriweather' },
        { id: '"Oswald", ui-sans-serif, system-ui', name: 'Oswald' },
        { id: 'Inter, ui-sans-serif, system-ui', name: 'Inter' },
        { id: 'system-ui, -apple-system, Segoe UI, Roboto, Arial', name: 'System' },
    ];

    const fontWeights = [
        { id: '300', name: t('Light', 'ط®ظپظٹظپ') },
        { id: '400', name: t('Regular', 'ط¹ط§ط¯ظٹ') },
        { id: '500', name: t('Medium', 'ظ…طھظˆط³ط·') },
        { id: '600', name: t('SemiBold', 'ط´ط¨ظ‡ ط¹ط±ظٹط¶') },
        { id: '700', name: t('Bold', 'ط¹ط±ظٹط¶') },
        { id: '800', name: t('ExtraBold', 'ط«ظ‚ظٹظ„') },
        { id: '900', name: t('Black', 'ط£ط³ظˆط¯') },
    ];

    const textTransforms = [
        { id: 'none', name: t('None', 'ط¨ط¯ظˆظ†') },
        { id: 'uppercase', name: t('Uppercase', 'ط£ط­ط±ظپ ظƒط¨ظٹط±ط©') },
        { id: 'lowercase', name: t('Lowercase', 'ط£ط­ط±ظپ طµط؛ظٹط±ط©') },
        { id: 'capitalize', name: t('Capitalize', 'ط£ظˆظ„ ط­ط±ظپ ظƒط¨ظٹط±') },
    ];

    const backgroundSizes = [
        { id: 'auto', name: t('Auto', 'طھظ„ظ‚ط§ط¦ظٹ') },
        { id: 'cover', name: t('Cover', 'طھط؛ط·ظٹط©') },
        { id: 'contain', name: t('Contain', 'ط§ط­طھظˆط§ط،') },
    ];

    const backgroundRepeats = [
        { id: 'no-repeat', name: t('No Repeat', 'ط¨ط¯ظˆظ† طھظƒط±ط§ط±') },
        { id: 'repeat', name: t('Repeat', 'طھظƒط±ط§ط±') },
        { id: 'repeat-x', name: t('Repeat X', 'طھظƒط±ط§ط± ط£ظپظ‚ظٹ') },
        { id: 'repeat-y', name: t('Repeat Y', 'طھظƒط±ط§ط± ط¹ظ…ظˆط¯ظٹ') },
    ];

    const backgroundPositions = [
        { id: 'left top', name: t('Left Top', 'ط£ط¹ظ„ظ‰ ظٹط³ط§ط±') },
        { id: 'center top', name: t('Center Top', 'ط£ط¹ظ„ظ‰ ظˆط³ط·') },
        { id: 'right top', name: t('Right Top', 'ط£ط¹ظ„ظ‰ ظٹظ…ظٹظ†') },
        { id: 'left center', name: t('Left Center', 'ظˆط³ط· ظٹط³ط§ط±') },
        { id: 'center center', name: t('Center', 'ظˆط³ط·') },
        { id: 'right center', name: t('Right Center', 'ظˆط³ط· ظٹظ…ظٹظ†') },
        { id: 'left bottom', name: t('Left Bottom', 'ط£ط³ظپظ„ ظٹط³ط§ط±') },
        { id: 'center bottom', name: t('Center Bottom', 'ط£ط³ظپظ„ ظˆط³ط·') },
        { id: 'right bottom', name: t('Right Bottom', 'ط£ط³ظپظ„ ظٹظ…ظٹظ†') },
    ];

    const backgroundAttachments = [
        { id: 'scroll', name: t('Scroll', 'طھظ…ط±ظٹط±') },
        { id: 'fixed', name: t('Fixed', 'ط«ط§ط¨طھ') },
        { id: 'local', name: t('Local', 'ظ…ط­ظ„ظٹ') },
    ];

    // =========================================================
    // 1) Background (Color / Image)
    // =========================================================
    sm.addSector('pg-background', {
        name: t('Background', 'ط§ظ„ط®ظ„ظپظٹط©'),
        open: true,
        buildProps: [
            'background-color',
            'background-image',
            'background-position',
            'background-size',
            'background-repeat',
            'background-attachment',
            'opacity',
        ],
        properties: [
            {
                id: 'background-color',
                name: t('Color', 'ظ„ظˆظ†'),
                property: 'background-color',
                type: 'color',
                defaults: 'transparent',
            },
            {
                id: 'background-image',
                name: t('Image', 'طµظˆط±ط©'),
                property: 'background-image',
                type: 'file',
                functionName: 'url',
                full: true,
                defaults: '',
            },
            {
                id: 'background-position',
                name: t('Position', 'ظ…ظˆط¶ط¹ ط§ظ„طµظˆط±ط©'),
                property: 'background-position',
                type: 'select',
                defaults: 'center center',
                list: backgroundPositions,
            },
            {
                id: 'background-size',
                name: t('Size', 'ط­ط¬ظ… ط§ظ„طµظˆط±ط©'),
                property: 'background-size',
                type: 'select',
                defaults: 'cover',
                list: backgroundSizes,
            },
            {
                id: 'background-repeat',
                name: t('Repeat', 'طھظƒط±ط§ط± ط§ظ„طµظˆط±ط©'),
                property: 'background-repeat',
                type: 'select',
                defaults: 'no-repeat',
                list: backgroundRepeats,
            },
            {
                id: 'background-attachment',
                name: t('Attachment', 'ط«ط¨ط§طھ ط§ظ„طµظˆط±ط©'),
                property: 'background-attachment',
                type: 'select',
                defaults: 'scroll',
                list: backgroundAttachments,
            },
            {
                id: 'opacity',
                name: t('Opacity', 'ط§ظ„ط´ظپط§ظپظٹط©'),
                property: 'opacity',
                type: 'slider',
                min: 0,
                max: 1,
                step: 0.01,
                defaults: 1,
            },
        ],
    });

    // =========================================================
    // 1.5) Image Size
    // =========================================================
    sm.addSector('pg-image-size', {
        name: t('Image', 'Image'),
        open: false,
        buildProps: ['width', 'max-width', 'height', 'opacity', 'filter'],
        properties: [
            {
                id: 'width',
                name: t('Width', 'Width'),
                property: 'width',
                type: 'pg-size-range',
                units: ['%', 'px', 'vw'],
                unit: '%',
                defaults: '100%',
                min: 0,
                max: 2400,
                step: 1,
                full: true,
                pgUnitRanges: {
                    '%': { min: 0, max: 100, step: 1 },
                    px: { min: 0, max: 2400, step: 1 },
                    vw: { min: 0, max: 100, step: 1 },
                },
            },
            {
                id: 'max-width',
                name: t('Max Width', 'Max Width'),
                property: 'max-width',
                type: 'pg-size-range',
                units: ['%', 'px', 'vw'],
                unit: '%',
                defaults: '100%',
                min: 0,
                max: 2400,
                step: 1,
                full: true,
                pgUnitRanges: {
                    '%': { min: 0, max: 100, step: 1 },
                    px: { min: 0, max: 2400, step: 1 },
                    vw: { min: 0, max: 100, step: 1 },
                },
            },
            {
                id: 'height',
                name: t('Height', 'Height'),
                property: 'height',
                type: 'pg-size-range',
                units: ['px', 'vh', '%'],
                unit: 'px',
                defaults: '',
                min: 0,
                max: 2400,
                step: 1,
                full: true,
                pgAllowEmpty: true,
                pgUnitRanges: {
                    px: { min: 0, max: 2400, step: 1 },
                    vh: { min: 0, max: 100, step: 1 },
                    '%': { min: 0, max: 100, step: 1 },
                },
            },
            {
                id: 'image-opacity',
                name: t('Opacity', 'Opacity'),
                property: 'opacity',
                type: 'slider',
                min: 0,
                max: 1,
                step: 0.01,
                defaults: 1,
                full: true,
            },
            {
                id: 'image-filter',
                name: t('CSS Filters', 'CSS Filters'),
                property: 'filter',
                type: 'pg-css-filters',
                defaults: 'none',
                full: true,
            },
        ],
    });
    // =========================================================
    // 1.6) Border
    // =========================================================
    sm.addSector('pg-border', {
        name: t('Border', 'Border'),
        open: false,
        buildProps: ['border-style', 'border-width', 'border-color'],
        properties: [
            {
                id: 'border-style',
                name: t('Border Type', 'Border Type'),
                property: 'border-style',
                type: 'select',
                defaults: 'none',
                list: [
                    { id: 'none', name: t('None', 'None') },
                    { id: 'solid', name: t('Solid', 'Solid') },
                    { id: 'dashed', name: t('Dashed', 'Dashed') },
                    { id: 'dotted', name: t('Dotted', 'Dotted') },
                    { id: 'double', name: t('Double', 'Double') },
                ],
            },
            {
                id: 'border-width',
                name: t('Border Width', 'Border Width'),
                property: 'border-width',
                type: 'pg-border-width',
                defaults: '0px',
                full: true,
            },
            {
                id: 'border-color',
                name: t('Border Color', 'Border Color'),
                property: 'border-color',
                type: 'color',
                defaults: '#cbd5e1',
            },
            {
                id: 'border-radius',
                name: t('Border Radius', 'Border Radius'),
                property: 'border-radius',
                type: 'pg-border-radius',
                defaults: '0px',
                full: true,
            },
        ],
    });
    // =========================================================
    // 2) Typography
    // =========================================================
    sm.addSector('pg-typography', {
        name: t('Typography', 'ط§ظ„ط®ط·ظˆط·'),
        open: false,
        buildProps: [
            'text-align',
            'font-family',
            'font-size',
            'font-weight',
            'text-transform',
            'line-height',
            'letter-spacing',
            'color',
        ],
        properties: [
            {
                id: 'text-align',
                name: t('Align', 'ط§ظ„ظ…ط­ط§ط°ط§ط©'),
                property: 'text-align',
                type: 'radio',
                defaults: isRtl ? 'right' : 'left',
                list: [
                    { value: 'left', name: 'Left' },
                    { value: 'center', name: 'Center' },
                    { value: 'right', name: 'Right' },
                    { value: 'justify', name: 'Justify' },
                ],
            },
            {
                id: 'font-family',
                name: t('Font', 'ظ†ظˆط¹ ط§ظ„ط®ط·'),
                property: 'font-family',
                type: 'select',
                defaults: 'inherit',
                list: fontFamilies,
            },
            {
                id: 'font-size',
                name: t('Size', 'ط§ظ„ط­ط¬ظ…'),
                property: 'font-size',
                type: 'pg-size-range',
                units: ['px', 'rem'],
                unit: 'px',
                defaults: '32px',
                min: 8,
                max: 240,
                step: 1,
                full: true,
                pgUnitRanges: {
                    px: { min: 8, max: 240, step: 1 },
                    rem: { min: 0.5, max: 15, step: 0.05 },
                },
            },
            {
                id: 'font-weight',
                name: t('Weight', 'ط§ظ„ط³ظڈظ…ظƒ'),
                property: 'font-weight',
                type: 'select',
                defaults: '800',
                list: fontWeights,
            },
            {
                id: 'text-transform',
                name: t('Transform', 'طھط­ظˆظٹظ„'),
                property: 'text-transform',
                type: 'select',
                defaults: 'none',
                list: textTransforms,
            },
            {
                id: 'line-height',
                name: t('Line Height', 'ط§ط±طھظپط§ط¹ ط§ظ„ط³ط·ط±'),
                property: 'line-height',
                type: 'pg-size-range',
                units: ['', 'px'],
                unit: '',
                defaults: '1.2',
                min: 0.6,
                max: 3,
                step: 0.05,
                full: true,
                pgUnitLabels: {
                    '': 'unitless',
                    px: 'px',
                },
                pgUnitRanges: {
                    '': { min: 0.6, max: 3, step: 0.05 },
                    px: { min: 8, max: 240, step: 1 },
                },
            },
            {
                id: 'letter-spacing',
                name: t('Letter Spacing', 'طھط¨ط§ط¹ط¯ ط§ظ„ط£ط­ط±ظپ'),
                property: 'letter-spacing',
                type: 'pg-size-range',
                units: ['px', 'em'],
                unit: 'px',
                defaults: '0px',
                min: -10,
                max: 40,
                step: 0.1,
                full: true,
                pgUnitRanges: {
                    px: { min: -10, max: 40, step: 0.1 },
                    em: { min: -1, max: 3, step: 0.01 },
                },
            },
            {
                id: 'color',
                name: t('Text Color', 'ظ„ظˆظ† ط§ظ„ظ†طµ'),
                property: 'color',
                type: 'color',
                defaults: '#240B36',
            },
        ],
    });

    // =========================================================
    // 3) Text Shadow
    // =========================================================
    sm.addSector('pg-text-shadow', {
        name: t('Text Shadow', 'ط¸ظ„ ط§ظ„ظ†طµ'),
        open: false,
        buildProps: ['text-shadow'],
        properties: [
            {
                id: 'text-shadow',
                name: t('Shadow', 'ط§ظ„ط¸ظ„'),
                property: 'text-shadow',
                type: 'composite',
                properties: [
                    { name: 'X', property: 'text-shadow-h', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: 'Y', property: 'text-shadow-v', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: t('Blur', 'ط§ظ„طھظ…ظˆظٹظ‡'), property: 'text-shadow-blur', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: t('Color', 'ط§ظ„ظ„ظˆظ†'), property: 'text-shadow-color', type: 'color', defaults: 'rgba(0,0,0,0.35)' },
                ],
                toStyle(values) {
                    const h = values['text-shadow-h'] ?? 0;
                    const v = values['text-shadow-v'] ?? 0;
                    const b = values['text-shadow-blur'] ?? 0;
                    const c = values['text-shadow-color'] ?? 'rgba(0,0,0,0.35)';
                    if (!h && !v && !b) return { 'text-shadow': 'none' };
                    return { 'text-shadow': `${h}px ${v}px ${b}px ${c}` };
                },
            },
        ],
    });

    // =========================================================
    // 4) Text Stroke
    // =========================================================
    sm.addSector('pg-text-stroke', {
        name: t('Text Stroke', 'ط­ط¯ظˆط¯ ط§ظ„ظ†طµ'),
        open: false,
        buildProps: ['-webkit-text-stroke-width', '-webkit-text-stroke-color'],
        properties: [
            {
                id: 'stroke-width',
                name: t('Width', 'ط§ظ„ط³ظڈظ…ظƒ'),
                property: '-webkit-text-stroke-width',
                type: 'slider',
                units: ['px'],
                unit: 'px',
                defaults: '0px',
                min: 0,
                max: 10,
                step: 0.5,
            },
            {
                id: 'stroke-color',
                name: t('Color', 'ط§ظ„ظ„ظˆظ†'),
                property: '-webkit-text-stroke-color',
                type: 'color',
                defaults: '#000000',
            },
        ],
    });
}



