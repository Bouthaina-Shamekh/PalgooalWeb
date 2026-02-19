function getBridgePickerButton() {
    return document.querySelector('#gjs_bridge_picker button');
}

function resolveSelectedUrl(detail) {
    if (!detail || typeof detail !== 'object') return '';
    return String(
        detail.url ||
        detail.file?.url ||
        detail.file?.path ||
        detail.path ||
        (Array.isArray(detail.files) && detail.files[0]?.url) ||
        (Array.isArray(detail.files) && detail.files[0]?.path) ||
        ''
    ).trim();
}

function paintMediaPreview(elInput, value) {
    const hiddenInput = elInput.querySelector('[data-role="media-hidden"]');
    const previewContainer = elInput.querySelector('[data-role="media-preview"]');
    const previewImg = elInput.querySelector('[data-role="media-preview-image"]');
    const urlInput = elInput.querySelector('[data-role="media-url"]');

    const normalized = String(value || '').trim();

    if (hiddenInput) hiddenInput.value = normalized;
    if (urlInput && urlInput.value !== normalized) urlInput.value = normalized;

    if (!previewContainer || !previewImg) return;

    if (!normalized) {
        previewContainer.classList.add('hidden');
        previewImg.removeAttribute('src');
        return;
    }

    previewImg.src = normalized;
    previewContainer.classList.remove('hidden');
}

export function registerMediaTrait(editor) {
    editor.TraitManager.addType('media-picker', {
        createInput({ trait }) {
            const name = String(trait.get('name') || '');
            const currentValue = String(trait.getTargetValue() || '').trim();
            const hasBridge = !!getBridgePickerButton();

            const el = document.createElement('div');
            el.className = 'pg-gjs-media-trait-container';
            el.innerHTML = `
                <input type="hidden" name="${name}" value="${currentValue}" data-role="media-hidden">
                <div class="flex flex-col gap-2 p-2 border border-dashed border-slate-300 rounded-xl bg-slate-50">
                    <button
                        type="button"
                        data-role="media-open"
                        class="text-[11px] font-bold bg-white border border-slate-200 py-2 px-3 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-50 hover:text-blue-600"
                        ${hasBridge ? '' : 'disabled'}
                        title="${hasBridge ? 'Open media library' : 'Media bridge is not available on this page'}"
                    >
                        ${hasBridge ? 'Choose From Library' : 'Library Unavailable'}
                    </button>

                    <input
                        type="url"
                        data-role="media-url"
                        placeholder="https://example.com/image.jpg"
                        value="${currentValue}"
                        class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-700 focus:border-slate-400 focus:ring-2 focus:ring-slate-300/50"
                    >

                    <div class="text-[10px] text-slate-500">
                        Paste image URL manually if the media bridge is unavailable.
                    </div>

                    <div class="pg-media-preview ${currentValue ? '' : 'hidden'}" data-role="media-preview">
                        <img data-role="media-preview-image" src="${currentValue}" class="w-full h-24 object-cover rounded-lg shadow-sm border border-white">
                    </div>
                </div>
            `;

            const openButton = el.querySelector('[data-role="media-open"]');
            const urlInput = el.querySelector('[data-role="media-url"]');

            const commit = (nextValue) => {
                const normalized = String(nextValue || '').trim();
                trait.setTargetValue(normalized);
                paintMediaPreview(el, normalized);
            };

            openButton?.addEventListener('click', () => {
                const bridgeButton = getBridgePickerButton();
                if (!bridgeButton) return;

                bridgeButton.click();

                const handleSelection = (event) => {
                    const selectedUrl = resolveSelectedUrl(event?.detail);
                    if (!selectedUrl) return;
                    commit(selectedUrl);
                };

                window.addEventListener('media-selected', handleSelection, { once: true });
            });

            urlInput?.addEventListener('input', () => {
                commit(urlInput.value);
            });

            paintMediaPreview(el, currentValue);
            return el;
        },

        onUpdate({ elInput, trait }) {
            paintMediaPreview(elInput, trait.getTargetValue());
        },
    });
}

