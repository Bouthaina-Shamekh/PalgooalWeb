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

    const stylePanelState = {
        backgroundState: 'normal',
    };

    const splitClasses = (value) =>
        String(value || '')
            .split(/\s+/)
            .filter(Boolean);

    const normalizeHoverBgColor = (value) => {
        const raw = String(value || '').trim();
        if (!raw) return '';

        const lowered = raw.toLowerCase().replace(/\s+/g, '');
        if (['transparent', 'inherit', 'initial', 'unset', 'none', '#0000'].includes(lowered)) {
            return '';
        }
        if (['rgba(0,0,0,0)', 'hsla(0,0%,0%,0)'].includes(lowered)) {
            return '';
        }

        return raw;
    };

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

    if (!sm.getType('pg-bg-state')) {
        sm.addType('pg-bg-state', {
            create({ props, change }) {
                const el = document.createElement('div');
                el.className = 'pg-sm-state';
                el.innerHTML = `
                    <button type="button" class="pg-sm-state__btn" data-state="normal">Normal</button>
                    <button type="button" class="pg-sm-state__btn" data-state="hover">Hover</button>
                `;

                const buttons = Array.from(el.querySelectorAll('.pg-sm-state__btn'));

                const setState = (value, { triggerChange = true } = {}) => {
                    const state = value === 'hover' ? 'hover' : 'normal';
                    el.dataset.state = state;

                    buttons.forEach((button) => {
                        const isActive = button.getAttribute('data-state') === state;
                        button.classList.toggle('is-active', isActive);
                        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });

                    stylePanelState.backgroundState = state;
                    if (typeof syncBackgroundPanelState === 'function') {
                        syncBackgroundPanelState();
                    } else {
                        applyBackgroundState(state);
                    }

                    if (triggerChange) {
                        change({ partial: false });
                    }
                };

                buttons.forEach((button) => {
                    button.addEventListener('click', () => {
                        setState(button.getAttribute('data-state'));
                    });
                });

                const initialState = stylePanelState.backgroundState || props?.defaults || 'normal';
                setState(initialState, { triggerChange: false });

                el.__setState = setState;
                return el;
            },

            emit() {},

            update({ props, el }) {
                const setState = el?.__setState;
                if (typeof setState !== 'function') return;

                const state = stylePanelState.backgroundState || props?.defaults || 'normal';
                setState(state, { triggerChange: false });
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
    if (!sm.getType('pg-font-family-select')) {
        sm.addType('pg-font-family-select', {
            create({ props, change }) {
                const el = document.createElement('div');
                el.className = 'pg-sm-font';

                const selectEl = document.createElement('select');
                selectEl.className = 'pg-sm-font__select';
                selectEl.setAttribute('aria-label', props?.name || 'Font Family');

                const previewEl = document.createElement('div');
                previewEl.className = 'pg-sm-font__preview';
                previewEl.textContent = 'The quick brown fox jumps over the lazy dog.';

                const list = Array.isArray(props?.list) ? props.list : [];
                list.forEach((item) => {
                    const value = String(item?.id ?? item?.value ?? '');
                    const label = String(item?.name ?? item?.label ?? value);

                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    if (value && value !== 'inherit') {
                        option.style.fontFamily = value;
                    }
                    selectEl.appendChild(option);
                });

                const applyPreview = (fontValue) => {
                    const next = String(fontValue || '').trim();
                    previewEl.style.fontFamily =
                        next && next !== 'inherit'
                            ? next
                            : 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif';
                };

                const emitChange = (partial) => change({ partial: !!partial });

                selectEl.addEventListener('input', () => {
                    applyPreview(selectEl.value);
                    emitChange(true);
                });

                selectEl.addEventListener('change', () => {
                    applyPreview(selectEl.value);
                    emitChange(false);
                });

                el.appendChild(selectEl);
                el.appendChild(previewEl);

                const defaultValue = String(props?.defaults ?? 'inherit');
                if (defaultValue) selectEl.value = defaultValue;
                applyPreview(selectEl.value || defaultValue);

                return el;
            },

            emit({ el, props, updateStyle }, { partial } = {}) {
                const selectEl = el.querySelector('.pg-sm-font__select');
                const fallback = String(props?.defaults ?? 'inherit');
                const nextValue = String(selectEl?.value || fallback || 'inherit');
                updateStyle(nextValue, { partial: !!partial });
            },

            update({ value, props, el }) {
                const selectEl = el.querySelector('.pg-sm-font__select');
                const previewEl = el.querySelector('.pg-sm-font__preview');
                if (!selectEl) return;

                const fallback = String(props?.defaults ?? 'inherit');
                const nextValue = String(value || fallback || 'inherit');

                const hasOption = Array.from(selectEl.options).some(
                    (option) => option.value === nextValue
                );

                if (!hasOption && nextValue) {
                    const customOption = document.createElement('option');
                    customOption.value = nextValue;
                    customOption.textContent = nextValue;
                    if (nextValue !== 'inherit') {
                        customOption.style.fontFamily = nextValue;
                    }
                    selectEl.appendChild(customOption);
                }

                selectEl.value = nextValue;

                if (previewEl) {
                    previewEl.style.fontFamily =
                        nextValue && nextValue !== 'inherit'
                            ? nextValue
                            : 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif';
                }
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
        { id: '300', name: t('Light', 'ط·آ®ط¸ظ¾ط¸ظ¹ط¸ظ¾') },
        { id: '400', name: t('Regular', 'ط·آ¹ط·آ§ط·آ¯ط¸ظ¹') },
        { id: '500', name: t('Medium', 'ط¸â€¦ط·ع¾ط¸ث†ط·آ³ط·آ·') },
        { id: '600', name: t('SemiBold', 'ط·آ´ط·آ¨ط¸â€، ط·آ¹ط·آ±ط¸ظ¹ط·آ¶') },
        { id: '700', name: t('Bold', 'ط·آ¹ط·آ±ط¸ظ¹ط·آ¶') },
        { id: '800', name: t('ExtraBold', 'ط·آ«ط¸â€ڑط¸ظ¹ط¸â€‍') },
        { id: '900', name: t('Black', 'ط·آ£ط·آ³ط¸ث†ط·آ¯') },
    ];

    const textTransforms = [
        { id: 'none', name: t('None', 'ط·آ¨ط·آ¯ط¸ث†ط¸â€ ') },
        { id: 'uppercase', name: t('Uppercase', 'ط·آ£ط·آ­ط·آ±ط¸ظ¾ ط¸ئ’ط·آ¨ط¸ظ¹ط·آ±ط·آ©') },
        { id: 'lowercase', name: t('Lowercase', 'ط·آ£ط·آ­ط·آ±ط¸ظ¾ ط·آµط·ط›ط¸ظ¹ط·آ±ط·آ©') },
        { id: 'capitalize', name: t('Capitalize', 'ط·آ£ط¸ث†ط¸â€‍ ط·آ­ط·آ±ط¸ظ¾ ط¸ئ’ط·آ¨ط¸ظ¹ط·آ±') },
    ];

    const backgroundSizes = [
        { id: 'auto', name: t('Auto', 'ط·ع¾ط¸â€‍ط¸â€ڑط·آ§ط·آ¦ط¸ظ¹') },
        { id: 'cover', name: t('Cover', 'ط·ع¾ط·ط›ط·آ·ط¸ظ¹ط·آ©') },
        { id: 'contain', name: t('Contain', 'ط·آ§ط·آ­ط·ع¾ط¸ث†ط·آ§ط·طŒ') },
    ];

    const backgroundRepeats = [
        { id: 'no-repeat', name: t('No Repeat', 'ط·آ¨ط·آ¯ط¸ث†ط¸â€  ط·ع¾ط¸ئ’ط·آ±ط·آ§ط·آ±') },
        { id: 'repeat', name: t('Repeat', 'ط·ع¾ط¸ئ’ط·آ±ط·آ§ط·آ±') },
        { id: 'repeat-x', name: t('Repeat X', 'ط·ع¾ط¸ئ’ط·آ±ط·آ§ط·آ± ط·آ£ط¸ظ¾ط¸â€ڑط¸ظ¹') },
        { id: 'repeat-y', name: t('Repeat Y', 'ط·ع¾ط¸ئ’ط·آ±ط·آ§ط·آ± ط·آ¹ط¸â€¦ط¸ث†ط·آ¯ط¸ظ¹') },
    ];

    const backgroundPositions = [
        { id: 'left top', name: t('Left Top', 'ط·آ£ط·آ¹ط¸â€‍ط¸â€° ط¸ظ¹ط·آ³ط·آ§ط·آ±') },
        { id: 'center top', name: t('Center Top', 'ط·آ£ط·آ¹ط¸â€‍ط¸â€° ط¸ث†ط·آ³ط·آ·') },
        { id: 'right top', name: t('Right Top', 'ط·آ£ط·آ¹ط¸â€‍ط¸â€° ط¸ظ¹ط¸â€¦ط¸ظ¹ط¸â€ ') },
        { id: 'left center', name: t('Left Center', 'ط¸ث†ط·آ³ط·آ· ط¸ظ¹ط·آ³ط·آ§ط·آ±') },
        { id: 'center center', name: t('Center', 'ط¸ث†ط·آ³ط·آ·') },
        { id: 'right center', name: t('Right Center', 'ط¸ث†ط·آ³ط·آ· ط¸ظ¹ط¸â€¦ط¸ظ¹ط¸â€ ') },
        { id: 'left bottom', name: t('Left Bottom', 'ط·آ£ط·آ³ط¸ظ¾ط¸â€‍ ط¸ظ¹ط·آ³ط·آ§ط·آ±') },
        { id: 'center bottom', name: t('Center Bottom', 'ط·آ£ط·آ³ط¸ظ¾ط¸â€‍ ط¸ث†ط·آ³ط·آ·') },
        { id: 'right bottom', name: t('Right Bottom', 'ط·آ£ط·آ³ط¸ظ¾ط¸â€‍ ط¸ظ¹ط¸â€¦ط¸ظ¹ط¸â€ ') },
    ];

    const backgroundAttachments = [
        { id: 'scroll', name: t('Scroll', 'ط·ع¾ط¸â€¦ط·آ±ط¸ظ¹ط·آ±') },
        { id: 'fixed', name: t('Fixed', 'ط·آ«ط·آ§ط·آ¨ط·ع¾') },
        { id: 'local', name: t('Local', 'ط¸â€¦ط·آ­ط¸â€‍ط¸ظ¹') },
    ];
    const applyBackgroundState = (value) => {
        const state = value === 'hover' ? 'hover' : 'normal';
        stylePanelState.backgroundState = state;

        const sector = sm.getSector('pg-background');
        if (!sector) return;

        const hoverProps = new Set([
            'pg-hover-bg-color',
            'pg-hover-text-color',
            '--pg-hover-bg',
            '--pg-hover-color',
        ]);

        sector.getProperties().forEach((propertyModel) => {
            const id = String(propertyModel.get?.('id') || '').trim();
            const prop = String(propertyModel.get?.('property') || '').trim();
            const key = id || prop;

            if (id === 'pg-bg-state' || prop === 'pg-bg-state') {
                propertyModel.set('visible', true);
                return;
            }

            const isHoverProp = hoverProps.has(key) || hoverProps.has(id) || hoverProps.has(prop);
            propertyModel.set('visible', state === 'hover' ? isHoverProp : !isHoverProp);
        });
    };

    const reorderBackgroundRows = () => {
        const root = document.getElementById('gjs-styles');
        const propsEl = root?.querySelector('.gjs-sm-sector.gjs-sm-sector__pg-background .gjs-sm-properties');
        if (!propsEl) return;

        const stateRow = propsEl.querySelector('.pg-bg-prop-state');
        if (stateRow && propsEl.firstElementChild !== stateRow) {
            propsEl.prepend(stateRow);
        }
    };

    const syncBackgroundPanelState = () => {
        const state = stylePanelState.backgroundState;
        applyBackgroundState(state);
        reorderBackgroundRows();

        if (typeof requestAnimationFrame === 'function') {
            requestAnimationFrame(() => {
                applyBackgroundState(state);
                reorderBackgroundRows();
            });
        }
    };

    // =========================================================
    // 1) Background (Color)
    // =========================================================
    sm.addSector('pg-background', {
        name: t('Background', 'ط·آ§ط¸â€‍ط·آ®ط¸â€‍ط¸ظ¾ط¸ظ¹ط·آ©'),
        open: true,
        buildProps: [
            'pg-bg-state',
            'background-color',
            '--pg-hover-bg',
            '--pg-hover-color',
        ],
        properties: [
            {
                id: 'pg-bg-state',
                name: t('State', 'State'),
                property: 'pg-bg-state',
                type: 'pg-bg-state',
                defaults: 'normal',
                full: true,
                className: 'pg-bg-prop-state',
            },
            {
                id: 'background-color',
                name: t('Color', 'Color'),
                property: 'background-color',
                type: 'color',
                defaults: 'transparent',
                full: true,
                className: 'pg-bg-prop-color',
            },
            {
                id: 'pg-hover-bg-color',
                name: t('Hover Color', 'Hover Color'),
                property: '--pg-hover-bg',
                type: 'color',
                defaults: '',
                full: true,
                className: 'pg-bg-prop-hover-bg',
            },
            {
                id: 'pg-hover-text-color',
                name: t('Hover Text Color', 'Hover Text Color'),
                property: '--pg-hover-color',
                type: 'color',
                defaults: '',
                full: true,
                className: 'pg-bg-prop-hover-text',
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
        name: t('Typography', 'ط·آ§ط¸â€‍ط·آ®ط·آ·ط¸ث†ط·آ·'),
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
                name: t('Align', 'ط·آ§ط¸â€‍ط¸â€¦ط·آ­ط·آ§ط·آ°ط·آ§ط·آ©'),
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
                name: t('Font', 'ط¸â€ ط¸ث†ط·آ¹ ط·آ§ط¸â€‍ط·آ®ط·آ·'),
                property: 'font-family',
                type: 'pg-font-family-select',
                defaults: 'inherit',
                list: fontFamilies,
            },
            {
                id: 'font-size',
                name: t('Size', 'ط·آ§ط¸â€‍ط·آ­ط·آ¬ط¸â€¦'),
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
                name: t('Weight', 'ط·آ§ط¸â€‍ط·آ³ط¸عˆط¸â€¦ط¸ئ’'),
                property: 'font-weight',
                type: 'select',
                defaults: '800',
                list: fontWeights,
            },
            {
                id: 'text-transform',
                name: t('Transform', 'ط·ع¾ط·آ­ط¸ث†ط¸ظ¹ط¸â€‍'),
                property: 'text-transform',
                type: 'select',
                defaults: 'none',
                list: textTransforms,
            },
            {
                id: 'line-height',
                name: t('Line Height', 'ط·آ§ط·آ±ط·ع¾ط¸ظ¾ط·آ§ط·آ¹ ط·آ§ط¸â€‍ط·آ³ط·آ·ط·آ±'),
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
                name: t('Letter Spacing', 'ط·ع¾ط·آ¨ط·آ§ط·آ¹ط·آ¯ ط·آ§ط¸â€‍ط·آ£ط·آ­ط·آ±ط¸ظ¾'),
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
                name: t('Text Color', 'ط¸â€‍ط¸ث†ط¸â€  ط·آ§ط¸â€‍ط¸â€ ط·آµ'),
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
        name: t('Text Shadow', 'ط·آ¸ط¸â€‍ ط·آ§ط¸â€‍ط¸â€ ط·آµ'),
        open: false,
        buildProps: ['text-shadow'],
        properties: [
            {
                id: 'text-shadow',
                name: t('Shadow', 'ط·آ§ط¸â€‍ط·آ¸ط¸â€‍'),
                property: 'text-shadow',
                type: 'composite',
                properties: [
                    { name: 'X', property: 'text-shadow-h', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: 'Y', property: 'text-shadow-v', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: t('Blur', 'ط·آ§ط¸â€‍ط·ع¾ط¸â€¦ط¸ث†ط¸ظ¹ط¸â€،'), property: 'text-shadow-blur', type: 'integer', units: ['px'], unit: 'px', defaults: 0 },
                    { name: t('Color', 'ط·آ§ط¸â€‍ط¸â€‍ط¸ث†ط¸â€ '), property: 'text-shadow-color', type: 'color', defaults: 'rgba(0,0,0,0.35)' },
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
        name: t('Text Stroke', 'ط·آ­ط·آ¯ط¸ث†ط·آ¯ ط·آ§ط¸â€‍ط¸â€ ط·آµ'),
        open: false,
        buildProps: ['-webkit-text-stroke-width', '-webkit-text-stroke-color'],
        properties: [
            {
                id: 'stroke-width',
                name: t('Width', 'ط·آ§ط¸â€‍ط·آ³ط¸عˆط¸â€¦ط¸ئ’'),
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
                name: t('Color', 'ط·آ§ط¸â€‍ط¸â€‍ط¸ث†ط¸â€ '),
                property: '-webkit-text-stroke-color',
                type: 'color',
                defaults: '#000000',
            },
        ],
    });

    const syncHoverBgClass = (component) => {
        if (!component?.getStyle || !component?.getAttributes) return;

        const styles = component.getStyle() || {};
        const hoverBgColor = normalizeHoverBgColor(styles['--pg-hover-bg']);
        const hoverTextColor = normalizeHoverBgColor(styles['--pg-hover-color']);
        const attrs = component.getAttributes() || {};
        const classes = splitClasses(attrs.class);
        const hasClass = classes.includes('pg-has-hover-bg');

        const hasHoverState = !!hoverBgColor || !!hoverTextColor;
        const nextClasses = hasHoverState
            ? hasClass
                ? classes
                : [...classes, 'pg-has-hover-bg']
            : classes.filter((cls) => cls !== 'pg-has-hover-bg');

        if (nextClasses.length === classes.length && nextClasses.join(' ') === classes.join(' ')) {
            return;
        }

        const nextAttrs = { ...attrs, class: nextClasses.join(' ').trim() };
        if (typeof component.setAttributes === 'function') {
            component.setAttributes(nextAttrs);
        } else {
            component.addAttributes?.(nextAttrs);
        }
    };

    const syncHoverBgClassInTree = () => {
        const wrapper = editor.getWrapper?.();
        if (!wrapper) return;

        syncHoverBgClass(wrapper);
        const all = wrapper.find?.('*') || [];
        all.forEach((component) => syncHoverBgClass(component));
    };

    // Show the Image sector only for image components.
    const imageSector = sm.getSector('pg-image-size');
    const stylesHost = document.getElementById('gjs-styles');

    const isImageLike = (component) => {
        let current = component;

        while (current) {
            const type = String(current.get?.('type') || '').toLowerCase();
            if (type === 'pg-image') return true;

            const attrs = current.getAttributes?.() || {};
            const name = String(attrs['data-gjs-name'] || current.get?.('name') || '').trim().toLowerCase();
            const tag = String(current.get?.('tagName') || '').trim().toLowerCase();
            const classes = String(attrs.class || '');

            if (name === 'image' && (tag === 'img' || tag === 'a')) return true;
            if (tag === 'img') return true;
            if (tag === 'a' && (classes.includes('pg-image-link') || classes.includes('pg-image'))) return true;

            current = current.parent?.();
        }

        return false;
    };

    const syncSectorVisibility = (component) => {
        const isImage = isImageLike(component);

        if (imageSector) {
            imageSector.set('visible', isImage);
        }

        if (stylesHost) {
            stylesHost.classList.toggle('pg-hide-image-sector', !isImage);
        }
    };

    editor.on('component:styleUpdate', (component) => {
        syncHoverBgClass(component);
    });

    editor.on('component:add', (component) => {
        syncHoverBgClass(component);
    });

    editor.on('component:selected', (component) => {
        syncBackgroundPanelState();
        syncHoverBgClass(component);
        syncSectorVisibility(component);
    });

    editor.on('component:deselected', () => {
        syncBackgroundPanelState();
        syncSectorVisibility(null);
    });

    editor.on('load', () => {
        syncBackgroundPanelState();
        syncHoverBgClassInTree();
        syncSectorVisibility(editor.getSelected?.());
    });

    syncBackgroundPanelState();
    syncSectorVisibility(editor.getSelected?.());
}

