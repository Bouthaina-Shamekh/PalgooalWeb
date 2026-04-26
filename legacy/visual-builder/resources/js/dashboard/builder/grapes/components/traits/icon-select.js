function normalizeOptions(options) {
    return (options || []).map((opt) => {
        if (typeof opt === 'string') {
            return { id: opt, name: opt, icon: '' };
        }
        return {
            id: opt.id ?? opt.value ?? opt.name ?? '',
            name: opt.name ?? opt.label ?? String(opt.id ?? ''),
            icon: opt.icon ?? '',
        };
    });
}

function activeStyles(active) {
    if (active) {
        return {
            background: '#d1d5db',
            color: '#1f2937',
            borderColor: '#9ca3af',
        };
    }
    return {
        background: '#f8fafc',
        color: '#334155',
        borderColor: '#e2e8f0',
    };
}

function paintButtons(el, value) {
    const buttons = el.querySelectorAll('[data-role="icon-select-btn"]');
    buttons.forEach((btn) => {
        const isActive = btn.getAttribute('data-value') === value;
        const styles = activeStyles(isActive);
        btn.style.background = styles.background;
        btn.style.color = styles.color;
        btn.style.borderColor = styles.borderColor;
    });

    const hiddenInput = el.querySelector('[data-role="icon-select-hidden"]');
    if (hiddenInput) hiddenInput.value = value;
}

export function registerIconSelectTrait(editor) {
    const tm = editor.TraitManager;

    tm.addType('pg-icon-select', {
        createInput({ trait }) {
            const name = trait.get('name') || '';
            const hint = String(trait.get('hint') || '').trim();
            const options = normalizeOptions(trait.get('options'));
            const first = options[0]?.id || '';
            const current = String(trait.getTargetValue() ?? trait.get('value') ?? first);

            const el = document.createElement('div');
            el.className = 'pg-icon-select-trait';

            const buttons = options
                .map((opt) => {
                    const id = String(opt.id || '');
                    const title = opt.name || id;
                    const icon = opt.icon || '';
                    return `
                        <button
                            type="button"
                            data-role="icon-select-btn"
                            data-value="${id}"
                            title="${title}"
                            aria-label="${title}"
                            style="height:34px;min-width:44px;padding:0 10px;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;cursor:pointer;"
                        >
                            ${icon || `<span style="font-size:12px;font-weight:600;">${title}</span>`}
                        </button>
                    `;
                })
                .join('');

            el.innerHTML = `
                <input type="hidden" name="${name}" data-role="icon-select-hidden" value="${current}">
                <div data-role="icon-select-group" style="display:flex;gap:0;border-radius:6px;overflow:hidden;border:1px solid #e2e8f0;background:#f8fafc;">
                    ${buttons}
                </div>
                ${hint ? `<div style="margin-top:8px;font-size:12px;line-height:1.4;color:#64748b;">${hint}</div>` : ''}
            `;

            const btnEls = el.querySelectorAll('[data-role="icon-select-btn"]');
            btnEls.forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.preventDefault();
                    const val = btn.getAttribute('data-value') || '';
                    paintButtons(el, val);
                    trait.setTargetValue(val);
                });
            });

            paintButtons(el, current);
            return el;
        },

        onUpdate({ elInput, trait }) {
            const options = normalizeOptions(trait.get('options'));
            const first = options[0]?.id || '';
            const value = String(trait.getTargetValue() ?? first);
            paintButtons(elInput, value);
        },
    });
}
