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
            el.className = 'flex flex-col gap-2';
            el.innerHTML = `
                <input data-role="hidden" type="hidden" name="${name}" value="${next}">
                ${unitLabel ? `
                    <div class="inline-flex w-fit select-none items-center gap-1 text-xs leading-none text-slate-600">
                        <svg class="h-2.5 w-2.5 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 8l5 5 5-5"></path>
                        </svg>
                        <span>${unitLabel}</span>
                    </div>
                ` : ''}
                <div class="flex items-center gap-2">
                    <input data-role="number" class="h-9 w-20 rounded-md border border-slate-300 bg-white px-2 text-center text-sm font-medium text-slate-700 outline-none transition [appearance:textfield] focus:border-slate-400 focus:ring-2 focus:ring-slate-300/50 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" type="number" min="${min}" max="${max}" step="${step}" value="${next}" />
                    <input data-role="range" class="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-slate-300 accent-slate-500" type="range" min="${min}" max="${max}" step="${step}" value="${next}" />
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
