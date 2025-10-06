<x-dashboard-layout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
        <div class="mx-auto max-w-7xl space-y-8 px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">{{ __('Media Library') }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Upload, organise, and reuse the files that power your content.') }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        <span id="mediaCount" class="font-medium text-slate-600 dark:text-slate-300">0</span>
                        {{ __('items') }}
                    </p>
                </div>

                @can('create', 'App\\Models\\Media')
                    <form id="uploadForm" enctype="multipart/form-data" class="w-full sm:max-w-md">
                        @csrf
                        <div id="uploadZone"
                            class="flex w-full flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 p-4 text-center shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50/40 dark:border-slate-700 dark:bg-slate-800/60 dark:hover:border-indigo-500/50 dark:hover:bg-slate-800">
                            <input type="file" name="image" id="imageInput" required class="sr-only" />
                            <div class="flex items-center gap-3 text-slate-600 dark:text-slate-300">
                                <span
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </span>
                                <div class="text-left">
                                    <p class="text-sm font-medium">{{ __('Drag & drop a file here') }}</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('or click to browse') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" id="browseBtn"
                                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus:ring-offset-slate-900">
                                    {{ __('Choose file') }}
                                </button>
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                    {{ __('Upload') }}
                                </button>
                            </div>
                            <div id="uploadProgressWrapper" class="mt-2 hidden w-full">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                    <div id="uploadProgressBar" style="width:0%"
                                        class="h-full rounded-full bg-indigo-500 transition-[width]"></div>
                                </div>
                                <p id="uploadProgressText" class="mt-1 text-xs text-slate-500 dark:text-slate-400">0%</p>
                            </div>
                        </div>
                    </form>
                @endcan
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-md">
                    <input id="searchInput" type="text" placeholder="{{ __('Search media...') }}"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pl-9 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                    <span
                        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <label for="sortSelect"
                        class="text-xs text-slate-500 dark:text-slate-400">{{ __('Sort by') }}</label>
                    <select id="sortSelect"
                        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                        <option value="newest">{{ __('Newest first') }}</option>
                        <option value="oldest">{{ __('Oldest first') }}</option>
                        <option value="name_asc">{{ __('Name A–Z') }}</option>
                        <option value="name_desc">{{ __('Name Z–A') }}</option>
                    </select>
                </div>
            </div>

            <div id="mediaWrapper" class="space-y-6">
                <div id="mediaSkeletons" class="hidden gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <!-- skeleton cards -->
                    @for ($i = 0; $i < 8; $i++)
                        <div
                            class="animate-pulse overflow-hidden rounded-2xl bg-white shadow ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700">
                            <div class="h-56 w-full bg-slate-200/70 dark:bg-slate-700/50"></div>
                            <div
                                class="border-t border-slate-100 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/70">
                                <div class="h-4 w-2/3 rounded bg-slate-200/80 dark:bg-slate-700/60"></div>
                            </div>
                        </div>
                    @endfor
                </div>
                <div id="mediaGrid" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" aria-live="polite">
                </div>

                <div id="mediaEmptyState"
                    class="hidden rounded-3xl border border-dashed border-slate-300 bg-white/80 p-10 text-center text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300">
                    <div
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-500 dark:bg-indigo-500/10">
                        <i class="fas fa-images text-lg"></i>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-800 dark:text-white">{{ __('No media yet') }}</h2>
                    <p class="mt-2 leading-relaxed">
                        {{ __('Upload your first file to start building a reusable media library.') }}</p>
                </div>
            </div>
        </div>
    </div>

    @can('delete', 'App\\Models\\Media')
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog"
            aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-3xl border-0 bg-white shadow-2xl dark:bg-slate-900">
                    <div
                        class="modal-header flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                        <h5 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Delete media item') }}</h5>
                        <button type="button" class="text-slate-400 transition hover:text-slate-600"
                            data-pc-modal-dismiss="#confirmDeleteModal" aria-label="{{ __('Close') }}">
                            &times;
                        </button>
                    </div>
                    <div class="modal-body px-6 py-5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                        {{ __('Are you sure you want to delete this file? This action cannot be undone.') }}
                    </div>
                    <div
                        class="modal-footer flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-slate-800">
                        <button type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            data-pc-modal-dismiss="#confirmDeleteModal" id="closeDeleteModal">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 dark:bg-rose-500 dark:hover:bg-rose-400 dark:focus:ring-offset-slate-900"
                            id="confirmDeleteBtn">
                            {{ __('Delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('edit', 'App\\Models\\Media')
        <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form id="editForm" class="modal-content rounded-3xl border-0 bg-white shadow-2xl dark:bg-slate-900">
                    <div
                        class="modal-header flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                        <h5 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Edit media details') }}
                        </h5>
                        <button type="button" class="text-slate-400 transition hover:text-slate-600"
                            data-pc-modal-dismiss="#editModal" aria-label="{{ __('Close') }}">
                            &times;
                        </button>
                    </div>

                    <div class="modal-body px-6 py-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-6">
                                <img id="editPreview" src="" alt="preview"
                                    class="max-h-72 w-full rounded-2xl border border-slate-200 object-cover shadow-sm dark:border-slate-700">

                                <div
                                    class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300">
                                    <h6
                                        class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        {{ __('File information') }}</h6>
                                    <dl class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Name') }}
                                            </dt>
                                            <dd id="infoName" class="text-right text-slate-700 dark:text-slate-200">---
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Type') }}
                                            </dt>
                                            <dd id="infoMime" class="text-right text-slate-700 dark:text-slate-200">---
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Size') }}
                                            </dt>
                                            <dd class="text-right text-slate-700 dark:text-slate-200"><span
                                                    id="infoSize">---</span> KB</dd>
                                        </div>
                                    </dl>
                                    <div class="space-y-2">
                                        <label for="infoURL"
                                            class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Direct URL') }}</label>
                                        <input type="text" id="infoURL" readonly
                                            class="w-full cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-500 shadow-sm transition hover:border-indigo-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300"
                                            onclick="navigator.clipboard.writeText(this.value)">
                                        <p class="text-xs text-slate-400 dark:text-slate-500">
                                            {{ __('Click to copy the URL to your clipboard.') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <input type="hidden" id="editId">

                                <div
                                    class="space-y-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/70">
                                    <div class="space-y-2">
                                        <label for="editAlt"
                                            class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Alt text') }}</label>
                                        <input type="text" id="editAlt"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                                    </div>

                                    <div class="space-y-2">
                                        <label for="editTitle"
                                            class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Title') }}</label>
                                        <input type="text" id="editTitle"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                                    </div>

                                    <div class="space-y-2">
                                        <label for="editCaption"
                                            class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Caption') }}</label>
                                        <textarea id="editCaption" rows="2"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"></textarea>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="editDescription"
                                            class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Description') }}</label>
                                        <textarea id="editDescription" rows="3"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="modal-footer flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-slate-800">
                        <button type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            data-pc-modal-dismiss="#editModal" id="closeEditModal">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:focus:ring-offset-slate-900">
                            {{ __('Save changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @can('edit', 'App\\Models\\Media')
        <button type="button" class="hidden" data-pc-toggle="modal" data-pc-target="#editModal"
            id="openEditModalBtn"></button>
    @endcan

    @can('delete', 'App\\Models\\Media')
        <button type="button" class="hidden" data-pc-toggle="modal" data-pc-target="#confirmDeleteModal"
            id="openDeleteModalBtn"></button>
    @endcan

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                const grid = $('#mediaGrid');
                const emptyState = $('#mediaEmptyState');
                const skeletons = $('#mediaSkeletons');
                const mediaCount = $('#mediaCount');
                const sortSelect = $('#sortSelect');
                const searchInput = $('#searchInput');
                const uploadZone = $('#uploadZone');
                const imageInput = $('#imageInput');
                const browseBtn = $('#browseBtn');
                const progressWrapper = $('#uploadProgressWrapper');
                const progressBar = $('#uploadProgressBar');
                const progressText = $('#uploadProgressText');

                let allFiles = [];

                loadMedia();

                // Upload handling with progress
                $('#uploadForm').on('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    if (!imageInput[0].files.length) {
                        showToast("{{ __('Please choose a file to upload.') }}", 'warning');
                        return;
                    }

                    showProgress(0);

                    $.ajax({
                        url: "{{ route('dashboard.media.store') }}",
                        method: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        xhr: function() {
                            const xhr = new window.XMLHttpRequest();
                            xhr.upload.addEventListener('progress', function(evt) {
                                if (evt.lengthComputable) {
                                    const percent = Math.round((evt.loaded / evt.total) *
                                        100);
                                    showProgress(percent);
                                }
                            });
                            return xhr;
                        },
                        success: function() {
                            imageInput.val('');
                            showToast("{{ __('File uploaded successfully.') }}", 'success');
                            hideProgress();
                            loadMedia();
                        },
                        error: function() {
                            showToast("{{ __('Upload failed. Please try again.') }}", 'error');
                            hideProgress();
                        }
                    });
                });

                // Drag & drop support
                browseBtn.on('click', function() {
                    imageInput.trigger('click');
                });
                uploadZone.on('click', function(e) {
                    if (e.target.id !== 'browseBtn') imageInput.trigger('click');
                });
                uploadZone.on('dragenter dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    uploadZone.addClass('border-indigo-400 bg-indigo-50/40 dark:border-indigo-500/60');
                });
                uploadZone.on('dragleave dragend drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    uploadZone.removeClass('border-indigo-400 bg-indigo-50/40 dark:border-indigo-500/60');
                });
                uploadZone.on('drop', function(e) {
                    const dt = e.originalEvent.dataTransfer;
                    if (dt && dt.files && dt.files.length) {
                        imageInput[0].files = dt.files;
                        $('#uploadForm').trigger('submit');
                    }
                });

                function loadMedia() {
                    showSkeletons();
                    $.get("{{ route('dashboard.media.index') }}", function(data) {
                        allFiles = Array.isArray(data) ? data : [];
                        mediaCount.text(allFiles.length);
                        applyFilters();
                    }).fail(function() {
                        hideSkeletons();
                        grid.addClass('hidden').empty();
                        emptyState.removeClass('hidden');
                        showToast("{{ __('Failed to load media.') }}", 'error');
                    });
                }

                function applyFilters() {
                    let files = [...allFiles];
                    const q = (searchInput.val() || '').toLowerCase().trim();
                    if (q) {
                        files = files.filter(f => {
                            const name = (f.name || '').toLowerCase();
                            const alt = (f.alt || '').toLowerCase();
                            const title = (f.title || '').toLowerCase();
                            return name.includes(q) || alt.includes(q) || title.includes(q);
                        });
                    }

                    const sort = (sortSelect.val() || 'newest');
                    files.sort((a, b) => {
                        const nameA = (a.name || '').toLowerCase();
                        const nameB = (b.name || '').toLowerCase();
                        const timeA = Date.parse(a.created_at || '') || a.id || 0;
                        const timeB = Date.parse(b.created_at || '') || b.id || 0;
                        if (sort === 'name_asc') return nameA.localeCompare(nameB);
                        if (sort === 'name_desc') return nameB.localeCompare(nameA);
                        if (sort === 'oldest') return timeA - timeB;
                        return timeB - timeA; // newest
                    });

                    renderGrid(files);
                }

                searchInput.on('input', debounce(applyFilters, 150));
                sortSelect.on('change', applyFilters);

                function renderGrid(files) {
                    if (!files.length) {
                        hideSkeletons();
                        grid.addClass('hidden').empty();
                        emptyState.removeClass('hidden');
                        return;
                    }

                    emptyState.addClass('hidden');
                    grid.removeClass('hidden');

                    let html = '';
                    files.forEach(item => {
                        const fallbackName = `{{ __('Untitled file') }}`;
                        const itemName = item.name && item.name.length ? item.name : fallbackName;
                        const altText = item.alt ?? '';
                        const mime = (item.mime_type || '').toLowerCase();
                        const badge = getTypeBadge(mime);

                        html += `
    <div class="group relative overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-slate-200 transition-all hover:-translate-y-1 hover:shadow-2xl dark:bg-slate-800 dark:ring-slate-700">
        <div class="absolute left-3 top-3 z-10 inline-flex items-center gap-1 rounded-full bg-white/95 px-2 py-1 text-xs font-medium text-slate-600 shadow ring-1 ring-slate-200 dark:bg-slate-900/80 dark:text-slate-200 dark:ring-slate-700">
            <i class="${badge.icon}"></i>
            <span>${badge.label}</span>
        </div>
        <img src="/storage/${item.file_path}" alt="${altText}"
            class="h-56 w-full object-cover object-center transition duration-300 group-hover:scale-105">
        <div class="absolute inset-x-3 top-3 flex items-center justify-end gap-2 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
            @can('edit', 'App\\Models\\Media')
            <button class="edit-btn inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-slate-600 shadow ring-1 ring-slate-200 transition hover:bg-indigo-50 hover:text-indigo-600 dark:bg-slate-900/80 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800"
                data-id="${item.id}" data-name="${item.name ?? ''}" title="{{ __('Edit media') }}">
                <i class="fas fa-pen text-xs"></i>
            </button>
            @endcan
            @can('delete', 'App\\Models\\Media')
            <button class="delete-btn inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-rose-500 shadow ring-1 ring-slate-200 transition hover:bg-rose-50 hover:text-rose-600 dark:bg-slate-900/80 dark:text-rose-300 dark:ring-slate-700 dark:hover:bg-slate-800"
                data-id="${item.id}" title="{{ __('Delete media') }}">
                <i class="fas fa-trash text-xs"></i>
            </button>
            @endcan
        </div>
        <div class="border-t border-slate-100 bg-slate-50 px-4 py-3 text-center dark:border-slate-700 dark:bg-slate-900/70">
            <p class="truncate text-sm font-medium text-slate-700 dark:text-slate-100">${itemName}</p>
        </div>
    </div>
`;
                    });

                    grid.html(html);
                    hideSkeletons();
                }

                function getTypeBadge(mime) {
                    if (mime.startsWith('image/')) return {
                        label: 'Image',
                        icon: 'far fa-image'
                    };
                    if (mime.startsWith('video/')) return {
                        label: 'Video',
                        icon: 'far fa-file-video'
                    };
                    if (mime.startsWith('audio/')) return {
                        label: 'Audio',
                        icon: 'far fa-file-audio'
                    };
                    if (mime.includes('pdf')) return {
                        label: 'PDF',
                        icon: 'far fa-file-pdf'
                    };
                    if (mime.includes('zip')) return {
                        label: 'ZIP',
                        icon: 'far fa-file-archive'
                    };
                    return {
                        label: 'File',
                        icon: 'far fa-file'
                    };
                }

                function showSkeletons() {
                    skeletons.removeClass('hidden').addClass('grid');
                    grid.addClass('hidden').empty();
                    emptyState.addClass('hidden');
                }

                function hideSkeletons() {
                    skeletons.addClass('hidden').removeClass('grid');
                }

                function showProgress(percent) {
                    progressWrapper.removeClass('hidden');
                    progressBar.css('width', `${percent}%`);
                    progressText.text(`${percent}%`);
                }

                function hideProgress() {
                    progressWrapper.addClass('hidden');
                    progressBar.css('width', '0%');
                    progressText.text('0%');
                }

                let deleteId = null;
                let isClosingDelete = false;
                let isClosingEdit = false;
                let isOpeningDelete = false;
                let isOpeningEdit = false;

                $(document).on('click', '.delete-btn', function() {
                    deleteId = $(this).data('id');
                    try { if (document.activeElement && typeof document.activeElement.blur === 'function') { document.activeElement.blur(); } } catch(e){}
                    if (isOpeningDelete) return;
                    // Ensure edit modal is closed before opening delete
                    if ($('#editModal:visible').length) {
                        if (!isClosingEdit) {
                            isClosingEdit = true;
                            forceHideModal('#editModal');
                            setTimeout(() => { isClosingEdit = false; }, 250);
                        }
                    }
                    isOpeningDelete = true;
                    setTimeout(() => { forceShowModal('#confirmDeleteModal'); isOpeningDelete = false; }, 80);
                });

                $('#confirmDeleteBtn').on('click', function() {
                    if (!deleteId) {
                        return;
                    }

                    $.ajax({
                        url: `{{ route('dashboard.media.destroy', ':id') }}`.replace(':id', deleteId),
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function() {
                            if (!isClosingDelete) {
                                isClosingDelete = true;
                                forceHideModal('#confirmDeleteModal');
                                setTimeout(() => { isClosingDelete = false; }, 300);
                            }
                            showToast("{{ __('Media deleted successfully.') }}", 'success');
                            loadMedia();
                        },
                        error: function() {
                            showToast("{{ __('Delete failed. Please try again.') }}", 'error');
                        }
                    });
                });

                $(document).on('click', '.edit-btn', function() {
                    const id = $(this).data('id');

                    // Ensure delete modal is closed before opening edit
                    if ($('#confirmDeleteModal:visible').length) {
                        if (!isClosingDelete) {
                            isClosingDelete = true;
                            forceHideModal('#confirmDeleteModal');
                            setTimeout(() => { isClosingDelete = false; }, 250);
                        }
                    }

                    $.get(`{{ route('dashboard.media.edit', ':id') }}`.replace(':id', id), function(data) {
                        $('#editId').val(data.id);
                        $('#infoName').text(data.name ?? '---');
                        $('#infoMime').text(data.mime_type ?? '---');
                        $('#infoSize').text((data.size / 1024).toFixed(2));
                        $('#infoURL').val('/storage/' + data.file_path);

                        $('#editPreview').attr('src', '/storage/' + data.file_path).attr('alt', data
                            .alt || '');
                        $('#editAlt').val(data.alt || '');
                        $('#editTitle').val(data.title || '');
                        $('#editCaption').val(data.caption || '');
                        $('#editDescription').val(data.description || '');

                        try { if (document.activeElement && typeof document.activeElement.blur === 'function') { document.activeElement.blur(); } } catch(e){}
                        if (!isOpeningEdit) {
                            isOpeningEdit = true;
                            setTimeout(() => { forceShowModal('#editModal'); isOpeningEdit = false; }, 80);
                        }
                    });
                });

                $('#editForm').on('submit', function(e) {
                    e.preventDefault();
                    const id = $('#editId').val();

                    $.ajax({
                        url: `{{ route('dashboard.media.update', ':id') }}`.replace(':id', id),
                        method: 'PUT',
                        data: {
                            _token: '{{ csrf_token() }}',
                            alt: $('#editAlt').val(),
                            title: $('#editTitle').val(),
                            caption: $('#editCaption').val(),
                            description: $('#editDescription').val()
                        },
                        success: function() {
                            if (!isClosingEdit) {
                                isClosingEdit = true;
                                forceHideModal('#editModal');
                                setTimeout(() => { isClosingEdit = false; }, 300);
                            }
                            showToast("{{ __('Changes saved.') }}", 'success');
                            loadMedia();
                        },
                        error: function() {
                            showToast("{{ __('Save failed. Please try again.') }}", 'error');
                        }
                    });
                });

                // Copy URL toast feedback
                $('#infoURL').on('click', function() {
                    showToast("{{ __('URL copied to clipboard') }}", 'info');
                });

                // Tiny debounce helper
                function debounce(fn, delay) {
                    let t;
                    return function() {
                        const ctx = this,
                            args = arguments;
                        clearTimeout(t);
                        t = setTimeout(() => fn.apply(ctx, args), delay);
                    };
                }

                // Safe modal close to avoid aria-hidden focus issue
                function forceHideModal(modalSelector){
                    try { if (document.activeElement && typeof document.activeElement.blur === 'function') { document.activeElement.blur(); } } catch(e){}
                    const $m = $(modalSelector);
                    $m.removeClass('show').addClass('hidden').attr('aria-hidden','true');
                    // If any inline style display is set by library, clear it
                    const el = $m.get(0);
                    if (el && el.style) { el.style.display = 'none'; }
                    // Attempt to remove any backdrop if managed in DOM (defensive)
                    $(".modal-backdrop, [data-pc-modal-backdrop]").remove();
                    setTimeout(() => { try { if (document.body && typeof document.body.focus === 'function') { document.body.focus({preventScroll:true}); } } catch(e){} }, 20);
                }

                function forceShowModal(modalSelector){
                    try { if (document.activeElement && typeof document.activeElement.blur === 'function') { document.activeElement.blur(); } } catch(e){}
                    const $m = $(modalSelector);
                    $m.removeClass('hidden').addClass('show').attr('aria-hidden','false');
                    const el = $m.get(0);
                    if (el && el.style) { el.style.display = 'block'; }
                    if (!$('.modal-backdrop').length && !$('[data-pc-modal-backdrop]').length) {
                        const $bd = $('<div class="modal-backdrop fade show" data-pc-modal-backdrop></div>');
                        $('body').append($bd);
                    }
                }

                // Toasts
                function showToast(message, type = 'info') {
                    const colors = {
                        info: 'bg-slate-900 text-white',
                        success: 'bg-emerald-600 text-white',
                        warning: 'bg-amber-500 text-white',
                        error: 'bg-rose-600 text-white'
                    };
                    let $container = $('#toastContainer');
                    if (!$container.length) {
                        $container = $(
                            '<div id="toastContainer" class="fixed bottom-4 right-4 z-[100] space-y-2"></div>');
                        $('body').append($container);
                    }
                    const $toast = $(`<div class="pointer-events-auto min-w-[200px] max-w-xs rounded-xl px-4 py-3 text-sm shadow-lg ring-1 ring-black/5 ${colors[type] || colors.info}">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5">${message}</span>
                            <button class="ml-auto text-white/70 hover:text-white" aria-label="{{ __('Close') }}">&times;</button>
                        </div>
                    </div>`);
                    $toast.hide();
                    $container.append($toast);
                    $toast.fadeIn(150);
                    const timeout = setTimeout(() => dismiss(), 2500);

                    function dismiss() {
                        $toast.fadeOut(150, function() {
                            $toast.remove();
                        });
                    }
                    $toast.find('button').on('click', function() {
                        clearTimeout(timeout);
                        dismiss();
                    });
                }
            });
        </script>
    @endpush
</x-dashboard-layout>
