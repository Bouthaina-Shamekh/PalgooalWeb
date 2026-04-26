/**
 * ---------------------------------------------------------
 * Preview (devices)
 * ---------------------------------------------------------
 */
export function initPreviewDropdown(editor) {
    const q = (sel, root = document) => root.querySelector(sel);
    const qa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    const previewToggleBtn = q('#preview-toggle-btn');
    const previewMenu = q('#preview-menu');
    const previewLabel = q('[data-preview-label]');
    const previewBtns = qa('.builder-preview-btn'); // Desktop / Tablet / Mobile
    if (!previewBtns.length && !previewToggleBtn && !previewMenu) return;

    const deviceMap = {
        desktop: 'Desktop',
        tablet: 'Tablet',
        mobile: 'Mobile',
    };

    const deviceLabelMap = {
        desktop: 'Desktop',
        tablet: 'Tablet',
        mobile: 'Mobile',
    };

    function updateDeviceButtons(activeId) {
        previewBtns.forEach((btn) => {
            const isActive = btn.dataset.preview === activeId;

            btn.classList.toggle('bg-white', isActive);
            btn.classList.toggle('text-slate-900', isActive);
            btn.classList.toggle('shadow-sm', isActive);

            btn.classList.toggle('bg-transparent', !isActive);
            btn.classList.toggle('text-slate-500', !isActive);
        });

        if (previewLabel && deviceLabelMap[activeId]) {
            previewLabel.textContent = deviceLabelMap[activeId];
        }
    }

    function setBuilderDevice(id) {
        const deviceName = deviceMap[id] || 'Desktop';
        editor.setDevice(deviceName);
        updateDeviceButtons(id);
    }

    if (previewBtns.length) {
        previewBtns.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.dataset.preview;
                setBuilderDevice(id);
            });
        });

        setBuilderDevice('desktop');
    }

    if (previewToggleBtn && previewMenu) {
        const close = () => previewMenu.classList.remove('open');
        const toggle = () => previewMenu.classList.toggle('open');

        previewToggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            toggle();
        });

        document.addEventListener('click', (e) => {
            const inside =
                previewMenu.contains(e.target) || previewToggleBtn.contains(e.target);
            if (!inside) close();
        });
    }
}