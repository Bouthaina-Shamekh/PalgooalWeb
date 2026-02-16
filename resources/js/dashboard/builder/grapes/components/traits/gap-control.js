function toNum(value, fallback) {
    const n = Number(value);
    return Number.isFinite(n) ? n : fallback;
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function normalizeState(component, trait) {
    const min = toNum(trait.get('min'), 0);
    const max = toNum(trait.get('max'), 400);
    const step = toNum(trait.get('step'), 1);
    const units = trait.get('units') || ['px'];
    const defaultUnit = trait.get('defaultUnit') || units[0] || 'px';

    const col = clamp(toNum(component.get('pgGapX'), 20), min, max);
    const row = clamp(toNum(component.get('pgGapY'), 20), min, max);
    const linked = component.get('pgGapLinked') !== false;
    const unit = units.includes(component.get('pgGapUnit')) ? component.get('pgGapUnit') : defaultUnit;

    return { col, row, linked, unit, min, max, step, units };
}

function paint(el, state) {
    const unitEl = el.querySelector('[data-role="gap-unit"]');
    const linkEl = el.querySelector('[data-role="gap-link"]');
    const colEl = el.querySelector('[data-role="gap-col"]');
    const rowEl = el.querySelector('[data-role="gap-row"]');
    const hiddenEl = el.querySelector('[data-role="gap-hidden"]');

    if (unitEl) unitEl.value = state.unit;
    if (colEl) colEl.value = String(state.col);
    if (rowEl) rowEl.value = String(state.row);
    if (hiddenEl) hiddenEl.value = `${state.col},${state.row},${state.unit},${state.linked ? '1' : '0'}`;

    if (linkEl) {
        linkEl.style.background = state.linked ? '#d1d5db' : '#f8fafc';
        linkEl.style.color = state.linked ? '#1f2937' : '#475569';
        linkEl.style.borderColor = state.linked ? '#9ca3af' : '#e2e8f0';
        linkEl.setAttribute('aria-pressed', state.linked ? 'true' : 'false');
    }
}

function applyComponentState(component, next) {
    component.set({
        pgGapX: String(next.col),
        pgGapY: String(next.row),
        pgGapUnit: next.unit,
        pgGapLinked: !!next.linked,
    });
}

const LINK_ICON = `
<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M10 13a5 5 0 0 0 7.1 0l2.8-2.8a5 5 0 0 0-7.1-7.1L11 4"></path>
    <path d="M14 11a5 5 0 0 0-7.1 0l-2.8 2.8a5 5 0 1 0 7.1 7.1L13 20"></path>
</svg>
`;

export function registerGapControlTrait(editor) {
    const tm = editor.TraitManager;

    tm.addType('pg-gap-control', {
        createInput({ trait, component }) {
            const state = normalizeState(component, trait);
            const name = trait.get('name') || 'pgGapControl';

            const el = document.createElement('div');
            el.className = 'pg-gap-control-trait';

            const unitOptions = state.units
                .map((u) => `<option value="${u}">${u}</option>`)
                .join('');

            el.innerHTML = `
                <input type="hidden" name="${name}" data-role="gap-hidden" />
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <select data-role="gap-unit" style="height:30px;border:1px solid #e2e8f0;border-radius:6px;padding:0 8px;background:#fff;">
                        ${unitOptions}
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:40px 1fr 1fr;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;background:#fff;">
                    <button type="button" data-role="gap-link" title="Link values" style="border:none;border-right:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;cursor:pointer;background:#f8fafc;">
                        ${LINK_ICON}
                    </button>
                    <input data-role="gap-row" type="number" min="${state.min}" max="${state.max}" step="${state.step}" style="height:34px;border:none;border-right:1px solid #e2e8f0;text-align:center;background:#fff;" />
                    <input data-role="gap-col" type="number" min="${state.min}" max="${state.max}" step="${state.step}" style="height:34px;border:none;text-align:center;background:#fff;" />
                </div>
                <div style="display:grid;grid-template-columns:40px 1fr 1fr;margin-top:6px;font-size:11px;color:#64748b;">
                    <span></span>
                    <span style="text-align:center;">Row</span>
                    <span style="text-align:center;">Column</span>
                </div>
            `;

            const unitEl = el.querySelector('[data-role="gap-unit"]');
            const linkEl = el.querySelector('[data-role="gap-link"]');
            const rowEl = el.querySelector('[data-role="gap-row"]');
            const colEl = el.querySelector('[data-role="gap-col"]');

            const commit = (partial, source = 'row') => {
                const current = normalizeState(component, trait);
                const unit = unitEl?.value || current.unit;
                const linked = linkEl?.getAttribute('aria-pressed') === 'true';
                const row = clamp(toNum(rowEl?.value, current.row), current.min, current.max);
                const col = clamp(toNum(colEl?.value, current.col), current.min, current.max);
                const pivot = source === 'col' ? col : row;

                const next = {
                    ...current,
                    unit,
                    linked,
                    row: linked ? pivot : row,
                    col: linked ? pivot : col,
                };

                paint(el, next);
                applyComponentState(component, next);
                trait.set('value', `${next.col},${next.row},${next.unit},${next.linked ? '1' : '0'}`, { silent: !!partial });
            };

            unitEl?.addEventListener('change', () => commit(false));
            rowEl?.addEventListener('input', () => commit(true, 'row'));
            rowEl?.addEventListener('change', () => commit(false, 'row'));
            colEl?.addEventListener('input', () => commit(true, 'col'));
            colEl?.addEventListener('change', () => commit(false, 'col'));

            linkEl?.addEventListener('click', (event) => {
                event.preventDefault();
                const current = normalizeState(component, trait);
                const linked = !(linkEl.getAttribute('aria-pressed') === 'true');
                const next = {
                    ...current,
                    linked,
                    row: linked ? clamp(toNum(rowEl?.value, current.row), current.min, current.max) : current.row,
                    col: linked
                        ? clamp(toNum(rowEl?.value, current.row), current.min, current.max)
                        : clamp(toNum(colEl?.value, current.col), current.min, current.max),
                    unit: unitEl?.value || current.unit,
                };

                paint(el, next);
                applyComponentState(component, next);
                trait.set('value', `${next.col},${next.row},${next.unit},${next.linked ? '1' : '0'}`);
            });

            paint(el, state);
            return el;
        },

        onUpdate({ elInput, trait, component }) {
            const state = normalizeState(component, trait);
            paint(elInput, state);
        },
    });
}
