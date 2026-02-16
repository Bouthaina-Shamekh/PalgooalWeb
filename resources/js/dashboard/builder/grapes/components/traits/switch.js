function isOn(value) {
    return value === true || value === 1 || value === '1' || value === 'true' || value === 'on';
}

function paint(el, checked) {
    const track = el.querySelector('[data-role="switch-track"]');
    const knob = el.querySelector('[data-role="switch-knob"]');
    const hidden = el.querySelector('[data-role="switch-hidden"]');

    if (!track || !knob) return;

    track.style.background = checked ? '#d8b4fe' : '#e2e8f0';
    track.style.borderColor = checked ? '#c084fc' : '#cbd5e1';
    knob.style.transform = checked ? 'translateX(20px)' : 'translateX(0)';

    if (hidden) hidden.value = checked ? '1' : '0';
}

export function registerSwitchTrait(editor) {
    const tm = editor.TraitManager;

    tm.addType('pg-switch', {
        createInput({ trait }) {
            const name = trait.get('name') || '';
            const checked = isOn(trait.getTargetValue());

            const el = document.createElement('div');
            el.className = 'pg-switch-trait';
            el.innerHTML = `
                <input type="hidden" name="${name}" data-role="switch-hidden" value="${checked ? '1' : '0'}">
                <button
                    type="button"
                    data-role="switch-track"
                    aria-label="${name}"
                    style="position:relative;width:48px;height:26px;border:1px solid #cbd5e1;border-radius:999px;background:#e2e8f0;cursor:pointer;padding:0;transition:all .15s ease;"
                >
                    <span
                        data-role="switch-knob"
                        style="position:absolute;left:2px;top:2px;width:20px;height:20px;border-radius:999px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:transform .15s ease;"
                    ></span>
                </button>
            `;

            const track = el.querySelector('[data-role="switch-track"]');
            track?.addEventListener('click', (event) => {
                event.preventDefault();
                const current = isOn(trait.getTargetValue());
                const next = !current;
                paint(el, next);
                trait.setTargetValue(next);
            });

            paint(el, checked);
            return el;
        },

        onUpdate({ elInput, trait }) {
            paint(elInput, isOn(trait.getTargetValue()));
        },
    });
}
