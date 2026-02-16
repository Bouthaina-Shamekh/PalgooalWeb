function clamp(value, min, max) {
    const n = Number(value);
    if (!Number.isFinite(n)) return min;
    return Math.min(max, Math.max(min, n));
}

function toNum(value, fallback) {
    const n = Number(value);
    return Number.isFinite(n) ? n : fallback;
}

function syncInputs(root, value) {
    const numberEl = root.querySelector('[data-role="number"]');
    const rangeEl = root.querySelector('[data-role="range"]');
    const hiddenEl = root.querySelector('[data-role="hidden"]');

    if (numberEl) numberEl.value = String(value);
    if (rangeEl) rangeEl.value = String(value);
    if (hiddenEl) hiddenEl.value = String(value);
}

export function registerRangeTrait(editor) {
    const tm = editor.TraitManager;

    tm.addType('pg-range', {
        createInput({ trait }) {
            const min = toNum(trait.get('min'), 0);
            const max = toNum(trait.get('max'), 100);
            const step = toNum(trait.get('step'), 1);
            const unitLabel = String(trait.get('unitLabel') || '').trim();
            const fallback = toNum(trait.get('value'), min);
            const current = toNum(trait.getTargetValue(), fallback);
            const next = clamp(current, min, max);
            const name = trait.get('name') || '';

            const el = document.createElement('div');
            el.className = 'pg-range-trait';
            el.innerHTML = `
                <input data-role="hidden" type="hidden" name="${name}" value="${next}">
                ${unitLabel ? `<div style="font-size:12px;color:#64748b;margin-bottom:6px;">${unitLabel}</div>` : ''}
                <div class="pg-range-trait-row" style="display:flex;align-items:center;gap:10px;">
                    <input data-role="number" type="number" min="${min}" max="${max}" step="${step}" value="${next}" style="width:68px;" />
                    <input data-role="range" type="range" min="${min}" max="${max}" step="${step}" value="${next}" style="flex:1;" />
                </div>
            `;

            const numberEl = el.querySelector('[data-role="number"]');
            const rangeEl = el.querySelector('[data-role="range"]');

            const onInput = (raw) => {
                const val = clamp(raw, min, max);
                syncInputs(el, val);
            };

            numberEl?.addEventListener('input', () => onInput(numberEl.value));
            rangeEl?.addEventListener('input', () => onInput(rangeEl.value));

            return el;
        },

        onEvent({ elInput, trait, event }) {
            const target = event?.target;
            if (!(target instanceof HTMLInputElement)) return;

            const numberEl = elInput.querySelector('[data-role="number"]');
            const rangeEl = elInput.querySelector('[data-role="range"]');
            if (!numberEl || !rangeEl) return;

            const min = toNum(numberEl.min, 0);
            const max = toNum(numberEl.max, 100);
            const value = clamp(target.value, min, max);

            syncInputs(elInput, value);
            trait.setTargetValue(String(value));
        },

        onUpdate({ elInput, trait }) {
            const numberEl = elInput.querySelector('[data-role="number"]');
            if (!numberEl) return;

            const min = toNum(numberEl.min, 0);
            const max = toNum(numberEl.max, 100);
            const value = clamp(trait.getTargetValue(), min, max);
            syncInputs(elInput, value);
        },
    });
}
