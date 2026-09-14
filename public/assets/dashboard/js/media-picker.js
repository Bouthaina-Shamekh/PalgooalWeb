document.addEventListener('DOMContentLoaded', () => {
    /**
     * ----------------------------------------------------------------------
     *  Global configuration (shared with the main Media Library)
     * ----------------------------------------------------------------------
     * MEDIA_CONFIG is expected to be defined globally from Blade:
     *   window.MEDIA_CONFIG = { baseUrl: '/admin/media', csrfToken: '...' }
     */
    const mediaConfig = window.MEDIA_CONFIG || {};
    const baseUrl = mediaConfig.baseUrl || '/admin/media';
    const csrfToken =
        mediaConfig.csrfToken ||
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    /**
     * ----------------------------------------------------------------------
     *  Core DOM elements for the Media Picker Modal
     * ----------------------------------------------------------------------
     */
    const backdropEl = document.getElementById('media-picker-backdrop');
    const modalEl = document.getElementById('media-picker-modal');
    const gridEl = document.getElementById('media-picker-grid');
    const loadingEl = document.getElementById('media-picker-loading');
    const emptyEl = document.getElementById('media-picker-empty');
    const statusEl = document.getElementById('media-picker-status');
    const loadMoreBtnEl = document.getElementById('media-picker-load-more');

    const searchInputEl = document.getElementById('media-picker-search');
    const filterButtons = document.querySelectorAll('.media-picker-filter-btn');

    const selectionCountEl = document.getElementById('media-picker-selection-count');
    const clearSelectionBtnEl = document.getElementById('media-picker-clear');
    const cancelBtnEl = document.getElementById('media-picker-cancel');
    const closeBtnEl = document.getElementById('media-picker-close');
    const confirmBtnEl = document.getElementById('media-picker-confirm');
    const confirmSelectionLabel = confirmBtnEl?.textContent.trim();

    /**
     * Elements for uploading media directly from inside the popup
     */
    const uploadBtnEl = document.getElementById('media-picker-upload-btn');
    const fileInputEl = document.getElementById('media-picker-file-input');

    /**
     * Drag & Drop area inside the popup
     * - Allows dropping files directly to upload
     */
    const popupDropzoneEl = document.getElementById('media-picker-dropzone');

    // If there is no modal on the page, the picker is not used here.
    if (!modalEl || !gridEl) {
        return;
    }

    const messages = {
        loadMore: modalEl.dataset.loadMoreLabel || '',
        noMore: modalEl.dataset.noMoreLabel || '',
        loadError: modalEl.dataset.loadError || '',
        unnamed: modalEl.dataset.unnamedLabel || '',
        file: modalEl.dataset.fileLabel || '',
        uploadUnavailable: modalEl.dataset.uploadUnavailable || '',
        uploadValidationError: modalEl.dataset.uploadValidationError || '',
        uploadSuccess: modalEl.dataset.uploadSuccess || '',
        uploadError: modalEl.dataset.uploadError || '',
        moveEarlier: modalEl.dataset.moveEarlierLabel || '',
        moveLater: modalEl.dataset.moveLaterLabel || '',
        movedPosition: modalEl.dataset.movedPositionLabel || '',
    };

    /**
     * ----------------------------------------------------------------------
     *  Internal state
     * ----------------------------------------------------------------------
     */
    let pickerOpen = false;
    let uploadOperation = null;
    let openCycle = 0;
    const uploadStatusEl = document.getElementById('media-picker-upload-status');
    const uploadLabel = uploadBtnEl?.textContent.trim();
    const uploadDisabledStates = new Map();
    const uploadMessage = message => { if (uploadStatusEl) uploadStatusEl.textContent = message; };
    const setUploadBusy = busy => {
        if (busy) {
            [uploadBtnEl, fileInputEl, clearSelectionBtnEl, confirmBtnEl, ...gridEl.querySelectorAll('button')].filter(Boolean).forEach(element => {
                if (!uploadDisabledStates.has(element)) uploadDisabledStates.set(element, element.disabled);
                element.disabled = true;
            });
        } else {
            uploadDisabledStates.forEach((disabled, element) => { element.disabled = disabled; });
            uploadDisabledStates.clear();
            gridEl.querySelectorAll('button').forEach(button => { button.disabled = false; });
        }
        uploadBtnEl?.setAttribute('aria-busy', String(busy));
        popupDropzoneEl?.setAttribute('aria-disabled', String(busy));
        if (uploadBtnEl) uploadBtnEl.textContent = busy ? uploadBtnEl.dataset.pendingLabel : uploadLabel;
    };
    let openingTrigger = null;
    const isolatedElements = new Map();
    const focusableSelector = 'a[href], button, input, select, textarea, [tabindex], [contenteditable="true"]';
    const canFocus = (element) => element?.isConnected && !element.matches(':disabled') &&
        !element.closest('[inert]') && element.getClientRects().length > 0 &&
        getComputedStyle(element).visibility !== 'hidden';
    const dialogControls = () => Array.from(modalEl.querySelectorAll(focusableSelector))
        .filter(element => element.tabIndex >= 0 && canFocus(element));
    const focusDialog = () => {
        const target = canFocus(searchInputEl) ? searchInputEl : (dialogControls()[0] || modalEl);
        target.focus();
    };
    const isolateBackground = () => {
        // Walk ancestors so this works even when the partial is nested in a layout.
        let branch = modalEl;
        while (branch.parentElement) {
            for (const sibling of branch.parentElement.children) {
                if (sibling === branch || sibling === backdropEl || sibling.contains(backdropEl) || isolatedElements.has(sibling)) continue;
                isolatedElements.set(sibling, {
                    inert: sibling.getAttribute('inert'),
                    ariaHidden: sibling.getAttribute('aria-hidden'),
                    pointerEvents: sibling.style.pointerEvents,
                });
                sibling.setAttribute('inert', '');
                sibling.setAttribute('aria-hidden', 'true');
                if (!('inert' in sibling)) sibling.style.pointerEvents = 'none';
            }
            branch = branch.parentElement;
            if (branch === document.body) break;
        }
    };
    const backgroundObserver = new MutationObserver(() => {
        if (pickerOpen) isolateBackground();
    });
    const restoreBackground = () => {
        backgroundObserver.disconnect();
        isolatedElements.forEach((state, element) => {
            for (const [attribute, value] of [['inert', state.inert], ['aria-hidden', state.ariaHidden]]) {
                if (value === null) element.removeAttribute(attribute);
                else element.setAttribute(attribute, value);
            }
            element.style.pointerEvents = state.pointerEvents;
        });
        isolatedElements.clear();
    };
    let currentPage = 1;
    let lastPage = 1;
    let currentFilterType = '';
    let currentSearch = '';
    let isLoading = false;
    let requestVersion = 0;
    let activeRequest = null;
    const invalidateRequest = () => {
        requestVersion++;
        activeRequest?.abort();
        activeRequest = null;
    };

    // Information about the field that opened the picker
    let currentTargetInputId = null;
    let currentPreviewContainerId = null;
    let isMultiple = false;
    let currentStoreValue = 'id';
    let acceptedType = null;
    const originalUploadAccept = fileInputEl?.getAttribute('accept');
    const acceptsMedia = item => !acceptedType || item.file_type === acceptedType;
    let selectionExplicitlyCleared = false;
    let hasUnresolvedSelection = false;
    let currentRemoveInputId = null;

    /**
     * Map of selected media items
     * - Key: media ID
     * - Value: { id, url, name, file_type, mime_type }
     */
    const selectedItems = new Map();

    const enhancePreviewReordering = previewContainer => {
        const trigger = document.querySelector(`.btn-open-media-picker[data-target-preview="${previewContainer.id}"][data-multiple="true"][data-store-value="id"]`);
        const input = trigger ? document.getElementById(trigger.dataset.targetInput) : null;
        if (!input) return;

        const items = () => Array.from(previewContainer.children)
            .filter(item => item.classList.contains('media-picker-preview-item') && item.dataset.mediaId);
        const update = (focusItem = null, focusDirection = null) => {
            const ordered = items();
            const orderedIds = Array.from(new Set(ordered.map(item => item.dataset.mediaId)));
            const validIds = new Set(orderedIds);
            const existingIds = Array.from(new Set(input.value.split(',').map(id => id.trim()).filter(Boolean)));
            const replacement = [...orderedIds];
            const nextIds = existingIds.map(id => validIds.has(id) ? replacement.shift() : id);
            replacement.forEach(id => nextIds.push(id));
            input.value = nextIds.join(',');
            ordered.forEach((item, index) => {
                const earlier = item.querySelector('[data-reorder="earlier"]');
                const later = item.querySelector('[data-reorder="later"]');
                if (earlier) earlier.disabled = index === 0;
                if (later) later.disabled = index === ordered.length - 1;
            });
            if (focusItem && focusDirection) focusItem.querySelector(`[data-reorder="${focusDirection}"]`)?.focus();
        };

        items().forEach(item => {
            if (item.querySelector('.media-picker-reorder-controls')) return;
            const controls = document.createElement('div');
            controls.className = 'media-picker-reorder-controls absolute inset-x-0 bottom-0 flex justify-center gap-1 bg-black/60 p-1';
            [['earlier', '↑', messages.moveEarlier], ['later', '↓', messages.moveLater]].forEach(([direction, icon, label]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.reorder = direction;
                button.className = 'media-picker-reorder-btn inline-flex h-7 w-7 items-center justify-center rounded bg-white/90 text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-40';
                button.setAttribute('aria-label', label);
                button.textContent = icon;
                button.addEventListener('click', () => {
                    const ordered = items();
                    const index = ordered.indexOf(item);
                    const target = direction === 'earlier' ? index - 1 : index + 1;
                    if (index < 0 || target < 0 || target >= ordered.length) return;
                    if (direction === 'earlier') previewContainer.insertBefore(item, ordered[target]);
                    else previewContainer.insertBefore(ordered[target], item);
                    update(item, direction);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    const status = document.getElementById(`${previewContainer.id}_reorder_status`);
                    if (status) status.textContent = messages.movedPosition.replace(':position', String(items().indexOf(item) + 1));
                });
                controls.appendChild(button);
            });
            item.appendChild(controls);
        });
        update();
    };

    /**
     * ----------------------------------------------------------------------
     *  Utility: Debounce helper
     * ----------------------------------------------------------------------
     * Ensures a function is not called too frequently (used for search input)
     */
    const debounce = (fn, delay = 300) => {
        let t;
        const debounced = (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
        debounced.cancel = () => clearTimeout(t);
        return debounced;
    };

    /**
     * ----------------------------------------------------------------------
     *  Utility: Simple Toast Notification
     * ----------------------------------------------------------------------
     * Displays small notifications in the bottom corner of the screen.
     */
    const showToast = (message, type = 'info') => {
        const colors = {
            info: 'bg-slate-900 text-white',
            success: 'bg-emerald-600 text-white',
            warning: 'bg-amber-500 text-white',
            error: 'bg-rose-600 text-white',
        };

        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className =
                'fixed bottom-4 right-4 rtl:left-4 rtl:right-auto z-[99999] space-y-2';
            document.body.appendChild(container);
        }

        const el = document.createElement('div');
        el.className =
            `pointer-events-auto min-w-[200px] max-w-xs rounded-xl px-4 py-3 text-sm shadow-lg ring-1 ring-black/5 opacity-0 translate-y-2 transition-all duration-200 ${colors[type] || colors.info}`;
        el.innerHTML = `
            <div class="flex items-start gap-3">
                <span class="mt-0.5">${message}</span>
                <button class="media-picker-toast-close ml-auto text-white/70 hover:text-white" aria-label="Close">&times;</button>
            </div>
        `;

        container.appendChild(el);

        requestAnimationFrame(() => {
            el.classList.remove('opacity-0', 'translate-y-2');
            el.classList.add('opacity-100', 'translate-y-0');
        });

        const timeout = setTimeout(dismiss, 2500);
        function dismiss() {
            el.classList.remove('opacity-100', 'translate-y-0');
            el.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => el.remove(), 200);
        }

        el.querySelector('button')?.addEventListener('click', () => {
            clearTimeout(timeout);
            dismiss();
        });
    };

    /**
     * ----------------------------------------------------------------------
     *  Modal Handling: Open / Close
     * ----------------------------------------------------------------------
     */

    /**
     * Opens the media picker with a given configuration:
     * - targetInputId: hidden input where the selected IDs will be stored
     * - previewContainerId: container where thumbnails will be rendered
     * - multiple: whether multiple selection is allowed
     */
    const openPicker = (config) => {
        if (pickerOpen) return;
        openCycle++;
        uploadMessage('');
        openingTrigger = config.trigger || document.activeElement;
        currentTargetInputId = config.targetInputId;
        currentPreviewContainerId = config.previewContainerId;
        isMultiple = config.multiple;
        currentStoreValue = config.storeValue || 'id';
        acceptedType = config.acceptedType === 'image' ? 'image' : null;
        if (fileInputEl) {
            if (acceptedType) fileInputEl.setAttribute('accept', 'image/*');
            else if (originalUploadAccept === null) fileInputEl.removeAttribute('accept');
            else fileInputEl.setAttribute('accept', originalUploadAccept);
        }
        currentRemoveInputId = config.removeInputId || null;

        // Reset state for fresh view every time the picker opens
        currentPage = 1;
        lastPage = 1;
        currentFilterType = acceptedType || '';
        currentSearch = '';
        scheduleSearch.cancel();
        if (searchInputEl) searchInputEl.value = '';
        filterButtons.forEach(button => {
            button.hidden = Boolean(acceptedType && button.dataset.type !== acceptedType);
            ['bg-indigo-50', 'border-indigo-500', 'text-indigo-600'].forEach(className => {
                button.classList.toggle(className, button.dataset.type === currentFilterType);
            });
        });
        selectedItems.clear();
        selectionExplicitlyCleared = false;
        const currentInput = document.getElementById(currentTargetInputId);
        const currentPreview = document.getElementById(currentPreviewContainerId);
        const rawValue = String(currentInput?.value || '').trim();
        hasUnresolvedSelection = Boolean(rawValue || currentPreview?.querySelector('img'));
        if (currentStoreValue === 'id' && /^\d+(,\d+)*$/.test(rawValue)) {
            const ids = rawValue.split(',').map(Number);
            if (ids.every(id => Number.isSafeInteger(id) && id > 0)) {
                for (const id of (isMultiple ? ids : ids.slice(0, 1))) {
                    const preview = currentPreview?.querySelector(`img[data-media-id="${id}"]`);
                    selectedItems.set(id, { id, url: preview?.getAttribute('src') || '', name: preview?.getAttribute('alt') || '' });
                }
                hasUnresolvedSelection = false;
            }
        }
        updateSelectionUI();
        gridEl.innerHTML = '';
        if (emptyEl) emptyEl.classList.add('hidden');

        // Let JS fully control the visibility of "Load more" button
        if (loadMoreBtnEl) {
            loadMoreBtnEl.classList.add('hidden');
        }

        // Show modal and backdrop
        backdropEl.classList.remove('hidden');
        modalEl.classList.remove('hidden');
        modalEl.classList.add('flex');
        pickerOpen = true;
        focusDialog();
        isolateBackground();
        backgroundObserver.observe(document.body, { childList: true, subtree: true });

        // Initial media load
        loadMedia(1, false);
    };

    /**
     * Closes the media picker modal and hides the overlay.
     * Does not clear the form values; it only hides the UI.
     */
    const closePicker = () => {
        if (!pickerOpen) return;
        pickerOpen = false;
        openCycle++;
        if (uploadOperation) {
            uploadOperation.controller.abort();
            uploadOperation = null;
            setUploadBusy(false);
        }
        scheduleSearch.cancel();
        invalidateRequest();
        setLoading(false);
        backdropEl.classList.add('hidden');
        modalEl.classList.add('hidden');
        modalEl.classList.remove('flex');
        restoreBackground();
        const trigger = openingTrigger;
        openingTrigger = null;
        if (canFocus(trigger)) trigger.focus();
    };

    /**
     * ----------------------------------------------------------------------
     *  Loading State Helper
     * ----------------------------------------------------------------------
     */

    /**
     * Toggles the loading state and optionally clears the grid.
     */
    const setLoading = (state, reset = false) => {
        isLoading = state;
        if (reset) {
            gridEl.innerHTML = '';
        }

        if (state) {
            if (loadingEl) loadingEl.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');
            if (loadMoreBtnEl) loadMoreBtnEl.classList.add('hidden');
        } else {
            if (loadingEl) loadingEl.classList.add('hidden');
        }
        if (statusEl && pickerOpen) {
            const message = state ? loadingEl?.textContent.trim()
                : (emptyEl && !emptyEl.classList.contains('hidden') ? emptyEl.textContent.trim() : '');
            if (statusEl.textContent !== (message || '')) statusEl.textContent = message || '';
        }
    };

    /**
     * ----------------------------------------------------------------------
     *  Fetching Media Items from the API
     * ----------------------------------------------------------------------
     * Loads paginated media items, optionally appending to the grid.
     */
    const loadMedia = async (page = 1, append = false) => {
        if (!pickerOpen || (append && isLoading)) return;
        invalidateRequest();
        const version = requestVersion;
        const controller = new AbortController();
        activeRequest = controller;
        const isCurrent = () => pickerOpen && version === requestVersion;
        setLoading(true, !append);

        const params = new URLSearchParams();
        params.set('page', page);
        // Small per_page so we always have multiple pages to test with
        params.set('per_page', '8');
        if (acceptedType || currentFilterType) params.set('type', acceptedType || currentFilterType);
        if (currentSearch) params.set('search', currentSearch);
        // Cache-busting param
        params.set('_', Date.now().toString());

        try {
            const res = await fetch(`${baseUrl}?${params.toString()}`, {
                signal: controller.signal,
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!isCurrent()) return;
            if (!res.ok) {
                throw new Error('Failed to load media for picker');
            }

            const json = await res.json();
            if (!isCurrent()) return;

            currentPage = json.current_page || 1;
            lastPage = json.last_page || 1;
            const items = (json.data || []).filter(acceptsMedia);

            if (!append) {
                gridEl.innerHTML = '';
            }

            if (!items.length && currentPage === 1) {
                if (emptyEl) emptyEl.classList.remove('hidden');
            } else {
                if (emptyEl) emptyEl.classList.add('hidden');
            }

            renderMediaItems(items);

            // Handle "Load more" button visibility and state
            if (loadMoreBtnEl) {
                loadMoreBtnEl.classList.remove('hidden');

                if (currentPage < lastPage && items.length > 0) {
                    loadMoreBtnEl.disabled = false;
                    loadMoreBtnEl.textContent = messages.loadMore;
                } else {
                    loadMoreBtnEl.disabled = true;
                    loadMoreBtnEl.textContent = messages.noMore;
                }
            }
        } catch (e) {
            if (!isCurrent() || e.name === 'AbortError') return;
            console.error(e);
            showToast(messages.loadError, 'error');
        } finally {
            if (isCurrent()) {
                activeRequest = null;
                setLoading(false);
            }
        }
    };

    /**
     * ----------------------------------------------------------------------
     *  Rendering Media Items inside the Grid
     * ----------------------------------------------------------------------
     * Creates clickable buttons for each media item (image or generic file).
     */
    const renderMediaItems = (items) => {
        items.forEach((item) => {
            if (!acceptsMedia(item)) return;
            const isImage =
                item.file_type === 'image' ||
                (item.mime_type && item.mime_type.startsWith('image/'));

            const imageUrl = item.url || `/storage/${item.file_path}`;
            const name =
                item.file_original_name || item.file_name || messages.unnamed;

            const btn = document.createElement('button');
            // Enrich restored IDs without changing their insertion order.
            if (selectedItems.has(item.id)) {
                selectedItems.set(item.id, { id: item.id, url: imageUrl, path: item.file_path || '', name,
                    file_type: item.file_type, mime_type: item.mime_type });
            }
            btn.type = 'button';
            btn.disabled = Boolean(uploadOperation);
            btn.className =
                'media-picker-item group relative w-full aspect-square rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden bg-gray-50 dark:bg-gray-900 text-start';
            btn.dataset.id = item.id;
            btn.setAttribute('aria-pressed', String(selectedItems.has(item.id)));

            if (selectedItems.has(item.id)) {
                btn.classList.add('ring-2', 'ring-indigo-500');
            }

            if (isImage) {
                const img = document.createElement('img');
                img.src = imageUrl;
                img.alt = name;
                img.className = 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-200';
                btn.appendChild(img);
            } else {
                const placeholder = document.createElement('div');
                placeholder.className = 'w-full h-full flex items-center justify-center text-[11px] text-gray-500 dark:text-gray-300';
                const extension = document.createElement('span');
                extension.className = 'px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
                extension.textContent = (item.file_extension || '').toUpperCase() || messages.file;
                placeholder.appendChild(extension);
                btn.appendChild(placeholder);
            }

            const caption = document.createElement('div');
            caption.className = 'absolute inset-x-0 bottom-0 bg-black/40 text-[10px] text-white px-2 py-1 truncate';
            caption.textContent = name;
            btn.appendChild(caption);

            // Click handler for selecting/deselecting this item
            btn.addEventListener('click', () => {
                if (uploadOperation) return;
                const alreadySelected = selectedItems.has(item.id);

                if (isMultiple) {
                    // Multiple selection mode: toggle the item
                    if (alreadySelected) {
                        selectedItems.delete(item.id);
                        if (!selectedItems.size) selectionExplicitlyCleared = true;
                        btn.classList.remove('ring-2', 'ring-indigo-500');
                    } else {
                        selectedItems.set(item.id, {
                            id: item.id,
                            url: imageUrl,
                            path: item.file_path || '',
                            name,
                            file_type: item.file_type,
                            mime_type: item.mime_type,
                        });
                        btn.classList.add('ring-2', 'ring-indigo-500');
                    }
                } else {
                    // Single selection mode: clear all, then select this one
                    selectedItems.clear();
                    document
                        .querySelectorAll('.media-picker-item')
                        .forEach((el) =>
                            el.classList.remove('ring-2', 'ring-indigo-500')
                        );

                    selectedItems.set(item.id, {
                        id: item.id,
                        url: imageUrl,
                        path: item.file_path || '',
                        name,
                        file_type: item.file_type,
                        mime_type: item.mime_type,
                    });
                    btn.classList.add('ring-2', 'ring-indigo-500');
                }

                updateSelectionUI();
            });

            gridEl.appendChild(btn);
        });
    };

    /**
     * ----------------------------------------------------------------------
     *  Selection UI Helpers
     * ----------------------------------------------------------------------
     */

    /**
     * Updates counters, "clear" button, and confirm button state
     * based on how many items are currently selected.
     */
    const updateSelectionUI = () => {
        gridEl.querySelectorAll('.media-picker-item').forEach(button => {
            button.setAttribute('aria-pressed', String(Array.from(selectedItems.keys()).some(id => String(id) === button.dataset.id)));
        });
        const count = selectedItems.size;
        if (selectionCountEl) {
            if (selectionCountEl.textContent !== String(count)) selectionCountEl.textContent = String(count);
        }

        if (clearSelectionBtnEl) {
            if (count > 0 || hasUnresolvedSelection) {
                clearSelectionBtnEl.classList.remove('hidden');
            } else {
                clearSelectionBtnEl.classList.add('hidden');
            }
        }

        // Disable "Use selected items" button when nothing is selected
        if (confirmBtnEl) {
            confirmBtnEl.disabled = Boolean(uploadOperation) || (count === 0 && !selectionExplicitlyCleared);
            confirmBtnEl.textContent = count === 0 && selectionExplicitlyCleared
                ? (confirmBtnEl.dataset.clearLabel || confirmSelectionLabel) : confirmSelectionLabel;
        }
    };

    /**
     * Clears all selected items and removes highlight from all tiles.
     */
    const clearSelection = () => {
        if (uploadOperation) return;
        selectedItems.clear();
        selectionExplicitlyCleared = true;
        hasUnresolvedSelection = false;
        document
            .querySelectorAll('.media-picker-item')
            .forEach((el) =>
                el.classList.remove('ring-2', 'ring-indigo-500')
            );
        updateSelectionUI();
    };

    /**
     * ----------------------------------------------------------------------
     *  Applying the Selection back to the Form
     * ----------------------------------------------------------------------
     * Called when the user confirms their selection.
     * - Writes the selected values into the hidden input (comma-separated)
     * - Renders thumbnails in the preview container (if provided)
     */
    const applySelection = () => {
        if (uploadOperation) return;
        if (!selectedItems.size && !selectionExplicitlyCleared) return;
        if (!currentTargetInputId) {
            closePicker();
            return;
        }

        const targetInput = document.getElementById(currentTargetInputId);
        const previewContainer = currentPreviewContainerId
            ? document.getElementById(currentPreviewContainerId)
            : null;

        const items = Array.from(selectedItems.values());
        const ids = items.map((item) => item.id);
        const values = items
            .map((item) => {
                if (currentStoreValue === 'url') return item.url || '';
                if (currentStoreValue === 'path') return item.path || '';
                return item.id;
            })
            .filter((value) => value !== null && value !== undefined && String(value).trim() !== '');

        // Store selected values in hidden input:
        // - id mode   => "1" or "1,5,9"
        // - path mode => "media/2026/03/file.png"
        // - url mode  => "https://..."
        if (targetInput) {
            targetInput.dataset.mediaPickerCleared = String(selectionExplicitlyCleared && !items.length);
            const removeInput = currentRemoveInputId ? document.getElementById(currentRemoveInputId) : null;
            if (removeInput) removeInput.value = !items.length && selectionExplicitlyCleared ? '1' : '0';
            targetInput.value = isMultiple ? values.join(',') : (values[0] ?? '');
            targetInput.dispatchEvent(new Event('input', { bubbles: true }));
            targetInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Render preview images (small thumbnails)
        if (previewContainer) {
            previewContainer.innerHTML = '';
            items.forEach((item) => {
                if (!item.url) return;
                const wrapper = document.createElement('div');
                wrapper.className =
                    'media-picker-preview-item relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900';
                wrapper.dataset.mediaId = String(item.id);

                const img = document.createElement('img');
                img.src = item.url;
                img.dataset.mediaId = String(item.id);
                img.alt = item.name || '';
                img.className = 'w-full h-full object-cover';

                wrapper.appendChild(img);
                previewContainer.appendChild(wrapper);
            });
            enhancePreviewReordering(previewContainer);
        }

        // Bridge event for custom integrations (full payload)
        window.dispatchEvent(
            new CustomEvent('media-picker-confirmed', {
                detail: {
                    items,
                    ids,
                    values,
                    storeValue: currentStoreValue,
                    targetInputId: currentTargetInputId,
                },
            })
        );

        // Backward-compatible event (single item + files list)
        const first = items[0] || null;
        if (first) {
            window.dispatchEvent(
                new CustomEvent('media-selected', {
                    detail: {
                        id: first.id,
                        url: first.url || '',
                        path: first.path || '',
                        name: first.name || '',
                        file: first,
                        files: items,
                        values,
                        storeValue: currentStoreValue,
                        targetInputId: currentTargetInputId,
                    },
                })
            );
        }

        closePicker();
    };

    /**
     * ----------------------------------------------------------------------
     *  Uploading Files from inside the Popup
     * ----------------------------------------------------------------------
     * This is used both by:
     * - Clicking "Upload New Image" button (file input)
     * - Drag & Drop area (drop event)
     */
    const uploadFilesFromPicker = async (files) => {
        if (!pickerOpen || uploadOperation || !files || !files.length) return;
        if (!csrfToken) {
            console.error('CSRF token missing');
            uploadMessage(messages.uploadUnavailable);
            return;
        }

        const formData = new FormData();
        Array.from(files).forEach((file) => formData.append('files[]', file));
        const operation = { controller: new AbortController(), cycle: openCycle, field: currentTargetInputId, multiple: isMultiple };
        uploadOperation = operation;
        const isCurrentUpload = () => pickerOpen && uploadOperation === operation && openCycle === operation.cycle && currentTargetInputId === operation.field;
        setUploadBusy(true);
        uploadMessage(uploadBtnEl?.dataset.pendingLabel || '');

        try {
            const res = await fetch(baseUrl, {
                method: 'POST',
                signal: operation.controller.signal,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: formData,
            });

            if (!res.ok) {
                if (!isCurrentUpload()) return;
                if (res.status === 422) {
                    const validation = await res.json();
                    if (!isCurrentUpload()) return;
                    const details = Object.values(validation.errors || {}).flat().find(message => typeof message === 'string' && message.trim());
                    uploadMessage(details || (typeof validation.message === 'string' ? validation.message : messages.uploadValidationError));
                    return;
                }
                throw new Error('Upload failed');
            }

            const data = await res.json();
            if (!isCurrentUpload()) return;

            let newlyUploaded = [];
            if (Array.isArray(data)) {
                newlyUploaded = data;
            } else if (Array.isArray(data.uploaded)) {
                newlyUploaded = data.uploaded;
            } else if (data && typeof data === 'object' && data.id) {
                newlyUploaded = [data];
            }

            uploadMessage(messages.uploadSuccess);

            // Auto-select uploaded items
            newlyUploaded = newlyUploaded.filter(acceptsMedia);
            if (newlyUploaded.length > 0) {
                // In single-select mode, only use the last uploaded file
                if (!operation.multiple) {
                    newlyUploaded = [newlyUploaded[newlyUploaded.length - 1]];
                }

                selectedItems.clear();

                newlyUploaded.forEach((item) => {
                    const imageUrl = item.url || `/storage/${item.file_path}`;
                    const name =
                        item.file_original_name || item.file_name || messages.unnamed;

                    selectedItems.set(item.id, {
                        id: item.id,
                        url: imageUrl,
                        path: item.file_path || '',
                        name,
                        file_type: item.file_type,
                        mime_type: item.mime_type,
                    });
                });

                updateSelectionUI();
            }

            // Reload media grid to include the new uploads
            currentPage = 1;
            lastPage = 1;
            await loadMedia(1, false);
        } catch (e) {
            if (!isCurrentUpload()) return;
            if (e.name === 'AbortError') { uploadMessage(''); return; }
            console.error(e);
            uploadMessage(messages.uploadError);
        } finally {
            if (isCurrentUpload()) {
                uploadOperation = null;
                setUploadBusy(false);
                updateSelectionUI();
            }
        }
    };

    /**
     * ----------------------------------------------------------------------
     *  Event Bindings
     * ----------------------------------------------------------------------
     */

    /**
     * Open buttons are handled through event delegation so the picker also works
     * for forms injected later (for example inline editors loaded via AJAX).
     */
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('.btn-open-media-picker');
        if (!btn) {
            return;
        }

        const targetInputId = btn.dataset.targetInput;
        const previewContainerId = btn.dataset.targetPreview || null;
        const multiple = btn.dataset.multiple === 'true';
        const storeValue = btn.dataset.storeValue || 'id';

        if (!targetInputId) {
            console.warn('[MediaPicker] data-target-input is not defined on button:', btn);
            return;
        }

        openPicker({
            trigger: btn,
            removeInputId: btn.dataset.removeInput,
            acceptedType: btn.dataset.acceptedType,
            targetInputId,
            previewContainerId,
            multiple,
            storeValue,
        });
    });

    // Register once; all close actions share the same focus and isolation cleanup.
    document.addEventListener('keydown', (event) => {
        if (!pickerOpen) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            closePicker();
        } else if (event.key === 'Tab') {
            const controls = dialogControls();
            const index = controls.indexOf(document.activeElement);
            event.preventDefault();
            if (!controls.length) modalEl.focus();
            else {
                const next = index < 0 ? (event.shiftKey ? controls.length - 1 : 0)
                    : (index + (event.shiftKey ? -1 : 1) + controls.length) % controls.length;
                controls[next].focus();
            }
        }
    }, true);
    document.addEventListener('focusin', (event) => {
        if (pickerOpen && !modalEl.contains(event.target)) focusDialog();
    });
    modalEl.addEventListener('click', (event) => {
        if (event.target === modalEl) closePicker();
    });

    // Close modal via "Cancel" button
    if (cancelBtnEl) {
        cancelBtnEl.addEventListener('click', () => closePicker());
    }

    // Close modal via "X" button
    if (closeBtnEl) {
        closeBtnEl.addEventListener('click', () => closePicker());
    }

    // Close modal by clicking on the backdrop
    if (backdropEl) {
        backdropEl.addEventListener('click', () => closePicker());
    }

    // "Clear selection" button
    if (clearSelectionBtnEl) {
        clearSelectionBtnEl.addEventListener('click', (e) => {
            e.preventDefault();
            clearSelection();
        });
    }

    // "Use selected items" button
    if (confirmBtnEl) {
        confirmBtnEl.addEventListener('click', (e) => {
            e.preventDefault();
            applySelection();
        });
    }

    /**
     * Search input with debounce:
     * - Waits 400ms after user stops typing before firing a new request.
     */
    const scheduleSearch = debounce(() => loadMedia(1, false), 400);
    if (searchInputEl) {
        searchInputEl.addEventListener(
            'input',
            (e) => {
                if (!pickerOpen) return;
                currentSearch = e.target.value.trim();
                currentPage = 1;
                invalidateRequest();
                setLoading(false, true);
                if (emptyEl) emptyEl.classList.add('hidden');
                if (statusEl) statusEl.textContent = '';
                if (loadMoreBtnEl) loadMoreBtnEl.classList.add('hidden');
                scheduleSearch();
            }
        );
    }

    /**
     * Filter buttons:
     * - Change the media type (image, video, document, other, etc.)
     * - Reload the grid from page 1.
     */
    if (filterButtons.length) {
        filterButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                if (acceptedType && btn.dataset.type !== acceptedType) return;
                scheduleSearch.cancel();
                filterButtons.forEach((b) =>
                    b.classList.remove(
                        'bg-indigo-50',
                        'border-indigo-500',
                        'text-indigo-600'
                    )
                );
                btn.classList.add(
                    'bg-indigo-50',
                    'border-indigo-500',
                    'text-indigo-600'
                );

                currentFilterType = btn.dataset.type || '';
                currentPage = 1;
                loadMedia(1, false);
            });
        });
    }

    /**
     * "Load more" pagination button
     * - Loads the next page and appends items to the grid.
     */
    if (loadMoreBtnEl) {
        loadMoreBtnEl.addEventListener('click', () => {
            if (!isLoading && currentPage < lastPage) {
                loadMedia(currentPage + 1, true);
            }
        });
    }

    /**
     * Upload button inside the popup:
     * - Triggers the hidden file input.
     */
    if (uploadBtnEl && fileInputEl) {
        uploadBtnEl.addEventListener('click', () => {
            if (uploadOperation) return;
            fileInputEl.click();
        });

        fileInputEl.addEventListener('change', (e) => {
            uploadFilesFromPicker(e.target.files);
            // Reset input to allow re-uploading the same file if needed
            e.target.value = '';
        });
    }

    /**
     * ----------------------------------------------------------------------
     *  Drag & Drop inside the popup (media-picker-dropzone)
     * ----------------------------------------------------------------------
     * Supports:
     *  - Clicking on the dropzone to open the file picker
     *  - Dragging files over the zone
     *  - Dropping files to upload them directly
     */
    if (popupDropzoneEl && fileInputEl) {
        // Clicking the dropzone opens the file picker
        popupDropzoneEl.addEventListener('click', (e) => {
            if (uploadOperation) return;
            // Allow clicking anywhere inside the dropzone
            if (e.target === popupDropzoneEl || popupDropzoneEl.contains(e.target)) {
                fileInputEl.click();
            }
        });

        // Highlight dropzone on drag enter/over
        ['dragenter', 'dragover'].forEach(eventName => {
            popupDropzoneEl.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                popupDropzoneEl.classList.add('border-indigo-400', 'bg-indigo-50');
            });
        });

        // Remove highlight on drag leave/drop
        ['dragleave', 'drop'].forEach(eventName => {
            popupDropzoneEl.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                popupDropzoneEl.classList.remove('border-indigo-400', 'bg-indigo-50');
            });
        });

        // Handle dropped files
        popupDropzoneEl.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt?.files;
            if (files && files.length) {
                uploadFilesFromPicker(files);
            }
        });
    }

    document.querySelectorAll('[id$="_preview"]').forEach(enhancePreviewReordering);
});
