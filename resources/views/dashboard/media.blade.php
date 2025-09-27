<x-dashboard-layout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
        <div class="mx-auto max-w-7xl space-y-8 px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-3xl font-semibold text-slate-900 dark:text-white">{{ __('Media Library') }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Upload, organise, and reuse the files that power your content.') }}</p>
                </div>

                @can('create', 'App\\Models\\Media')
                    <form id="uploadForm" enctype="multipart/form-data"
                        class="flex w-full flex-col gap-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 p-4 shadow-sm backdrop-blur sm:max-w-md sm:flex-row sm:items-center sm:gap-4 dark:border-slate-700 dark:bg-slate-800/60">
                        @csrf
                        <label for="imageInput"
                            class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Upload a new file') }}</label>
                        <input type="file" name="image" id="imageInput" required
                            class="block w-full cursor-pointer rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus:ring-offset-slate-900">
                            {{ __('Upload') }}
                        </button>
                    </form>
                @endcan
            </div>

            <div id="mediaWrapper" class="space-y-6">
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
                            data-bs-dismiss="modal" aria-label="{{ __('Close') }}">
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
                            data-bs-dismiss="modal" aria-label="{{ __('Close') }}">
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

                loadMedia();

                $('#uploadForm').on('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);

                    $.ajax({
                        url: "{{ route('dashboard.media.store') }}",
                        method: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function() {
                            $('#imageInput').val('');
                            loadMedia();
                        }
                    });
                });

                function loadMedia() {
                    $.get("{{ route('dashboard.media.index') }}", function(data) {
                        const files = Array.isArray(data) ? data : [];

                        if (!files.length) {
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

                            html += `
    <div class="group relative overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-slate-200 transition-all hover:-translate-y-1 hover:shadow-2xl dark:bg-slate-800 dark:ring-slate-700">
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
                    });
                }

                let deleteId = null;

                $(document).on('click', '.delete-btn', function() {
                    deleteId = $(this).data('id');
                    $('#openDeleteModalBtn').trigger('click');
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
                            $('#closeDeleteModal').trigger('click');
                            loadMedia();
                        }
                    });
                });

                $(document).on('click', '.edit-btn', function() {
                    const id = $(this).data('id');

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

                        $('#openEditModalBtn').trigger('click');
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
                            $('#closeEditModal').trigger('click');
                            loadMedia();
                        }
                    });
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
