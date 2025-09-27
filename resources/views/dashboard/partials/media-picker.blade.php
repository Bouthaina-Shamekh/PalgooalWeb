@php
    $modalId = $modalId ?? 'mediaPickerModal';
    $listUrl = route('dashboard.media.index');
    $uploadUrl = route('dashboard.media.store');
@endphp

@once
    @push('styles')
        <style>
            .media-picker-modal {
                position: fixed;
                inset: 0;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                z-index: 9998;
                overflow-y: auto;
            }

            .media-picker-modal.show {
                display: flex !important;
            }


            .media-picker-backdrop {
                position: fixed;
                inset: 0;
                background-color: rgba(15, 23, 42, 0.55);
                z-index: 9998;
            }

            .media-picker-dialog {
                margin: auto;
                z-index: 9999;
                width: min(100%, 960px);
                max-height: 90vh;
            }

            .media-picker-content {
                background: white;
                border-radius: 0.75rem;
                box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
                display: flex;
                flex-direction: column;
                max-height: 90vh;
            }

            .dark .media-picker-content {
                background: #1f2937;
                color: #f9fafb;
            }

            .media-picker-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 16px;
            }

            .media-picker-item {
                border: 1px solid rgba(148, 163, 184, 0.5);
                border-radius: 0.75rem;
                overflow: hidden;
                cursor: pointer;
                transition: transform 0.18s ease, box-shadow 0.18s ease;
                background: #fff;
            }

            .media-picker-item:hover {
                transform: scale(1.02);
                box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12);
            }

            .media-picker-item img {
                width: 100%;
                height: 140px;
                object-fit: cover;
            }

            .media-picker-item .media-picker-name {
                padding: 0.6rem;
                font-size: 0.75rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        </style>
    @endpush
@endonce

<div class="media-picker-modal hidden" id="{{ $modalId }}" aria-hidden="true">
    <div class="media-picker-backdrop" data-media-close></div>
    <div class="media-picker-dialog">
        <div class="media-picker-content">
            <div class="flex items-start justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('Media Library') }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-300">{{ __('Choose an image or upload a new one.') }}</p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-800 dark:text-slate-200" data-media-close>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="px-6 py-4 overflow-y-auto space-y-5">
                <form data-media-upload class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-300">{{ __('Upload image') }}</label>
                        <input type="file" accept="image/*" class="mt-1 block w-full text-sm" required>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white text-sm rounded-md hover:bg-primary/90">
                        {{ __('Upload') }}
                    </button>
                    <span data-media-upload-status class="text-xs text-slate-500"></span>
                </form>

                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
                    <input type="search" data-media-search placeholder="{{ __('Search media...') }}" class="w-full sm:w-72 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900">
                    <a href="{{ route('dashboard.media.index') }}" target="_blank" class="text-sm text-primary hover:underline">
                        {{ __('Open full media manager') }}
                    </a>
                </div>

                <div class="media-picker-grid" data-media-grid>
                    <div class="col-span-full text-center text-sm text-slate-500 py-8" data-media-empty>{{ __('No media yet. Upload a file to get started.') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const modalId = @json($modalId);
            window.__mediaPickerInstances = window.__mediaPickerInstances || {};
            if (window.__mediaPickerInstances[modalId]) {
                return;
            }
            window.__mediaPickerInstances[modalId] = true;

            const listUrl = @json($listUrl);
            const uploadUrl = @json($uploadUrl);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            let modal;
            let grid;
            let emptyState;
            let searchInput;
            let uploadForm;
            let uploadStatus;
            let currentLocale = null;
            let mediaCache = [];

            const resolveElements = () => {
                modal = document.getElementById(modalId);
                if (!modal) {
                    return false;
                }
                grid = modal.querySelector('[data-media-grid]');
                emptyState = modal.querySelector('[data-media-empty]');
                searchInput = modal.querySelector('[data-media-search]');
                uploadForm = modal.querySelector('[data-media-upload]');
                uploadStatus = modal.querySelector('[data-media-upload-status]');
                return true;
            };

            const isVisible = () => modal?.classList.contains('show');

            const showModal = (locale) => {
                if (!resolveElements()) {
                    return;
                }
                currentLocale = locale;
                modal.classList.add('show');
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                loadMedia();
            };

            const hideModal = () => {
                if (!modal) {
                    return;
                }
                modal.classList.remove('show');
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                currentLocale = null;
            };

            const renderMedia = (items) => {
                if (!grid) {
                    return;
                }
                if (!items.length) {
                    grid.innerHTML = '';
                    emptyState?.classList.remove('hidden');
                    return;
                }
                emptyState?.classList.add('hidden');
                grid.innerHTML = items.map(item => `
                    <div class="media-picker-item" data-media-path="${item.file_path}" title="${item.name}">
                        <img src="/storage/${item.file_path}" alt="${item.name}">
                        <div class="media-picker-name">${item.name}</div>
                    </div>
                `).join('');
            };

            const loadMedia = () => {
                if (mediaCache.length) {
                    renderMedia(mediaCache);
                    return;
                }
                if (grid) {
                    grid.innerHTML = `<div class="col-span-full text-center text-sm text-slate-500 py-6">${@json(__('Loading...'))}</div>`;
                }
                fetch(listUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => response.ok ? response.json() : Promise.reject())
                    .then(data => {
                        mediaCache = Array.isArray(data) ? data : [];
                        renderMedia(mediaCache);
                    })
                    .catch(() => {
                        if (grid) {
                            grid.innerHTML = `<div class="col-span-full text-center text-red-500 py-6">${@json(__('Unable to load media.'))}</div>`;
                        }
                    });
            };

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest(`[data-media-modal="${modalId}"]`);
                if (trigger) {
                    event.preventDefault();
                    showModal(trigger.dataset.mediaLocale || null);
                    return;
                }

                if (!modal || !modal.contains(event.target)) {
                    return;
                }

                if (event.target.closest('[data-media-close]')) {
                    hideModal();
                    return;
                }

                const item = event.target.closest('[data-media-path]');
                if (item && currentLocale) {
                    const path = item.getAttribute('data-media-path');
                    const input = document.querySelector(`[data-media-input="${currentLocale}"]`);
                    const previewWrapper = document.querySelector(`[data-media-preview-wrapper="${currentLocale}"]`);
                    const preview = document.querySelector(`[data-media-preview="${currentLocale}"]`);
                    if (path && input) {
                        const fullUrl = `${window.location.origin.replace(/\/$/, '')}/storage/${path.replace(/^\//, '')}`;
                        input.value = fullUrl;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        if (previewWrapper) {
                            previewWrapper.classList.remove('hidden');
                        }
                        if (preview) {
                            preview.src = fullUrl;
                            preview.classList.remove('hidden');
                        }
                    }
                    hideModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && isVisible()) {
                    hideModal();
                }
            });

            const attachSearch = () => {
                if (!searchInput) {
                    return;
                }
                searchInput.addEventListener('input', () => {
                    const term = searchInput.value.toLowerCase();
                    const filtered = mediaCache.filter(item => (item.name || '').toLowerCase().includes(term));
                    renderMedia(filtered);
                });
            };

            const attachUpload = () => {
                if (!uploadForm) {
                    return;
                }
                uploadForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    const fileInput = uploadForm.querySelector('input[type="file"]');
                    if (!fileInput?.files?.length) {
                        if (uploadStatus) {
                            uploadStatus.textContent = @json(__('Select an image first.'));
                            uploadStatus.classList.add('text-red-500');
                        }
                        return;
                    }
                    const formData = new FormData();
                    formData.append('image', fileInput.files[0]);
                    if (uploadStatus) {
                        uploadStatus.textContent = @json(__('Uploading...'));
                        uploadStatus.classList.remove('text-red-500');
                    }
                    fetch(uploadUrl, {
                        method: 'POST',
                        headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
                        body: formData,
                    })
                        .then(response => response.ok ? response.json() : Promise.reject())
                        .then(item => {
                            if (item) {
                                mediaCache.unshift(item);
                                renderMedia(mediaCache);
                            }
                            if (fileInput) {
                                fileInput.value = '';
                            }
                            if (uploadStatus) {
                                uploadStatus.textContent = @json(__('Uploaded successfully.'));
                                uploadStatus.classList.remove('text-red-500');
                                setTimeout(() => uploadStatus.textContent = '', 2000);
                            }
                        })
                        .catch(() => {
                            if (uploadStatus) {
                                uploadStatus.textContent = @json(__('Upload failed, please try again.'));
                                uploadStatus.classList.add('text-red-500');
                            }
                        });
                });
            };

            if (resolveElements()) {
                attachSearch();
                attachUpload();
            }
        })();
    </script>
@endpush