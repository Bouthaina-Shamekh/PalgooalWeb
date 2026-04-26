export function registerSectionHeadingTrait(editor) {
    const tm = editor.TraitManager;

    tm.addType('pg-trait-heading', {
        createInput({ trait }) {
            const title = String(trait.get('title') || trait.get('label') || '').trim();
            const name = String(trait.get('name') || '').trim();

            const el = document.createElement('div');
            el.className = 'pg-trait-heading';
            if (name) el.setAttribute('data-pg-trait-name', name);

            el.innerHTML = `
                <div style="margin:8px 0 10px;border-top:1px solid #e2e8f0;padding-top:8px;">
                    <div style="font-size:11px;font-weight:700;letter-spacing:.04em;color:#64748b;text-transform:uppercase;">
                        ${title}
                    </div>
                </div>
            `;

            return el;
        },
    });
}
