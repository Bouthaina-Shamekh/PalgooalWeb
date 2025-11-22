<div class="p-6">
    <style>
        .masonry {
            column-count: 4;
            column-gap: 1rem;
        }

        @media (max-width: 992px) {
            .masonry {
                column-count: 3;
            }
        }

        @media (max-width: 768px) {
            .masonry {
                column-count: 2;
            }
        }

        @media (max-width: 576px) {
            .masonry {
                column-count: 1;
            }
        }

        .masonry-item {
            position: relative;
            transition: transform 0.2s ease;
            border-radius: 10px;
            box-shadow: 1px 6px 8px rgba(0, 0, 0, 0.3);
            margin-bottom: 11px;
        }

        .masonry-item:hover {
            transform: scale(1.03);
            z-index: 2;
        }

        .media-actions {
            position: absolute;
            top: 0;
            right: -60px;
            display: flex;
            gap: 0.25rem;
            flex-direction: column;
            align-items: center;
            transition: 0.3s all;
        }

        .masonry-item:hover .media-actions {
            display: flex !important;
            right: 0;

        }

        .media-actions .btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .masonry-item img {
            width: 100%;
            height: auto;
            display: block;
        }

        .masonry-item .info {
            padding: 0.75rem;
            text-align: center;
        }

        .masonry-item .info small {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .masonry-item .actions {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }
    </style>

    {{-- SweetAlert Trigger --}}
    @if ($alert)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: '{{ $alertType }}',
                    title: '{{ $alertType === 'success' ? 'نجاح' : 'خطأ' }}',
                    text: '{{ $alertMessage }}',
                    confirmButtonText: 'حسناً'
                });
            });
        </script>
    @endif

    {{-- Index View --}}
    @if ($mode === 'index')
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold">ط§ظ„طھظ‚ظٹظٹظ…ط§طھ</h2>
            @can('create','App\\Models\\Testimonial')
            <button wire:click="showAdd" class="btn btn-primary">+ ط¥ط¶ط§ظپط© طھظ‚ظٹظٹظ…</button>
            @endcan
        </div>

        <table class="table-auto w-full text-right border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2">#</th>
                    <th class="p-2">ط§ظ„طµظˆط±ط©</th>
                    <th class="p-2">ط§ظ„ط§ط³ظ… ({{ app()->getLocale() }})</th>
                    <th class="p-2">ط§ظ„طھظ‚ظٹظٹظ…</th>
                    <th class="p-2">ط§ظ„ظ†طµ ({{ app()->getLocale() }})</th>
                    <th class="p-2">حالة الاعتماد</th>
                    <th class="p-2">ط§ظ„ط¥ط¬ط±ط§ط،ط§طھ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($testimonials as $testimonial)
                    <tr class="border-t">
                        <td class="p-2">{{ $loop->iteration }}</td>
                        <td class="p-2">
                            <img src="{{ asset('storage/' . $testimonial->image) }}" class="w-10 h-10">
                        </td>
                        <td class="p-2">
                            {{ $testimonial->translation()?->name }}
                        </td>
                        <td class="p-2">{{ $testimonial->star }}</td>
                        <td class="p-2">
                            {{ $testimonial->translation()?->feedback }}
                        </td>
                        <td class="p-2">
                            @if ($testimonial->is_approved)
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">معتمد</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">بانتظار الموافقة</span>
                            @endif
                        </td>
                        <td class="p-2 space-x-2">
                            @can('edit','App\\Models\\Testimonial')
                            <button wire:click="showEdit({{ $testimonial->id }})"
                                class="btn btn-sm btn-warning">طھط¹ط¯ظٹظ„</button>
                            @endcan
                            @can('delete','App\\Models\\Testimonial')
                            <button onclick="confirmDeleteTestimonial({{ $testimonial->id }})"
                                class="btn btn-danger">ط­ط°ظپ</button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $testimonials->links() }}
        </div>
    @endif

    {{-- Add/Edit View --}}
    @if (in_array($mode, ['add', 'edit']))
        <div class="mb-4">
            <h2 class="text-xl font-bold">{{ $mode === 'edit' ? 'طھط¹ط¯ظٹظ„ طھظ‚ظٹظٹظ…' : 'ط¥ط¶ط§ظپط© طھظ‚ظٹظٹظ…' }}</h2>
        </div>

        <form wire:submit.prevent="save" class="grid grid-cols-12 gap-6">
            {{-- ط§ظ„طµظˆط±ط© --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">ط§ظ„طµظˆط±ط©</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="imageInput" wire:model="testimonial.image" class="form-control hidden">
                    <button type="button" data-pc-toggle="modal" data-pc-target="#mediaModal" id="openMediaModalBtn"
                        class="bg-primary text-white px-2 py-1 rounded text-sm">
                        ط§ط®طھط± ظ…ظ† ط§ظ„ظˆط³ط§ط¦ط· ط£ظˆ ط§ط±ظپط¹ ط¬ط¯ظٹط¯
                    </button>
                </div>

                @if ($testimonial['image'])
                    <img src="{{ asset('storage/' . $testimonial['image']) }}" class="mt-2 w-12 h-12">
                @endif
            </div>


            {{-- ط§ظ„طھط±طھظٹط¨ --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">طھط±طھظٹط¨ ط§ظ„ط¸ظ‡ظˆط±</label>
                <input type="number" wire:model="testimonial.order" class="form-control">
                @error('testimonial.order')
                    <span class="text-red-600">{{ $message }}</span>
                @enderror
            </div>

            {{-- ط§ظ„طھظ‚ظٹظٹظ… --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">ط§ظ„طھظ‚ظٹظٹظ…</label>
                <input type="number" wire:model="testimonial.star" class="form-control">
                @error('testimonial.star')
                    <span class="text-red-600">{{ $message }}</span>
                @enderror
            </div>

            {{-- حالة الاعتماد --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">حالة الاعتماد</label>
                <div class="flex items-center gap-3 mt-2">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="testimonial.is_approved" class="sr-only peer">
                        <div class="w-14 h-8 bg-gray-200 rounded-full peer-checked:bg-green-500 transition relative">
                            <span class="absolute top-1 left-1 w-6 h-6 bg-white rounded-full shadow peer-checked:translate-x-6 transition"></span>
                        </div>
                    </label>
                    <span class="text-sm text-gray-600">
                        {{ $testimonial['is_approved'] ? 'سيظهر فوراً على الموقع' : 'سيبقى قيد المراجعة' }}
                    </span>
                </div>
            </div>

            {{-- ط§ظ„طھط±ط¬ظ…ط§طھ --}}
            <div class="col-span-12 grid grid-cols-{{ count($languages) }} gap-4">
                @foreach ($languages as $index => $lang)
                    <div class="border p-4 rounded shadow-sm">
                        <h4 class="text-lg font-bold mb-2">{{ $lang->native }}</h4>

                        <input type="text" class="form-control mb-2" placeholder="ط§ظ„ط§ط³ظ…"
                            wire:model="testimonialTranslations.{{ $index }}.name">

                        <textarea class="form-control" rows="3" placeholder="ط§ظ„ظ†طµ"
                            wire:model="testimonialTranslations.{{ $index }}.feedback"></textarea>

                        <input type="text" class="form-control mb-2" placeholder="ط§ظ„ظ…ظ‡ظ†ط©"
                            wire:model="testimonialTranslations.{{ $index }}.major">

                        <input type="hidden" wire:model="testimonialTranslations.{{ $index }}.locale">
                    </div>
                @endforeach
            </div>

            <div class="col-span-12 text-right mt-6">
                <button type="button" wire:click="showIndex" class="btn btn-secondary">ط¥ظ„ط؛ط§ط،</button>
                <button type="submit" class="btn btn-primary">ط­ظپط¸</button>
            </div>
        </form>


        {{-- ظ…ظˆط¯ط§ظ„ ط§ظ„ظˆط³ط§ط¦ط· --}}
        <div class="modal fade" id="mediaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-2xl font-bold mb-6">ًں“پ ظ…ظƒطھط¨ط© ط§ظ„ظˆط³ط§ط¦ط·</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeMediaModal"
                            data-pc-modal-dismiss="#mediaModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <form id="uploadForm" enctype="multipart/form-data" class="mb-3">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="file" name="image" id="imageInput" class="form-control mb-2" required>
                            <button type="button" id="uploadFormBtn" class="btn btn-primary">ط±ظپط¹ طµظˆط±ط©</button>
                        </form>
                        <div id="mediaGrid" class="masonry">
                            {{-- ط§ظ„طµظˆط± ط³طھظڈظ…ظ„ط£ طھظ„ظ‚ط§ط¦ظٹظ‹ط§ ط¹ط¨ط± jQuery --}}
                        </div>
                    </div>
                    {{-- <div class="modal-footer px-4 py-3">
                <button type="button" class="btn btn-secondary" data-pc-modal-dismiss="#editModal"
                    id="closeEditModal">ط¥ظ„ط؛ط§ط،</button>
                <button type="submit" class="btn btn-success">ًں’¾ ط­ظپط¸ ط§ظ„طھط¹ط¯ظٹظ„ط§طھ</button>
            </div> --}}
                </div>
            </div>
        </div>
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog"
            aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">طھط£ظƒظٹط¯ ط§ظ„ط­ط°ظپ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeDeleteModal"
                            data-pc-modal-dismiss="#confirmDeleteModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        ظ‡ظ„ ط£ظ†طھ ظ…طھط£ظƒط¯ ظ…ظ† ط­ط°ظپ ظ‡ط°ظ‡ ط§ظ„طµظˆط±ط©طں
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-pc-modal-dismiss="#confirmDeleteModal"
                            id="closeDeleteModal">ط¥ظ„ط؛ط§ط،</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">ظ†ط¹ظ…طŒ ط­ط°ظپ</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function confirmDeleteTestimonial(testimonialId) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'لا يمكن التراجع عن حذف هذه الشهادة.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، احذف',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('deleteTestimonialConfirmed', {
                        id: testimonialId
                    });
                }
            });
        }

        window.addEventListener('testimonial-deleted-success', () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'âœ… طھظ… ط­ط°ظپ ط§ظ„ط®ط¯ظ…ط© ط¨ظ†ط¬ط§ط­',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        });

        window.addEventListener('testimonial-delete-failed', () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'â‌Œ ظپط´ظ„ ط­ط°ظپ ط§ظ„ط®ط¯ظ…ط©',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        });
    </script>



</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $(document).on('click', '#openMediaModalBtn', function() {
            loadMedia();
            $('.modal').removeClass('show animate');
            $('#mediaModal').addClass('show animate');
        });
        $(document).on('click', '#closeMediaModal', function() {
            $('.modal').removeClass('show animate');
        });
        $(document).on('click', '#closeDeleteModal', function() {
            $('.modal').removeClass('show animate');
        });

        $(document).on('click', '#uploadFormBtn', function(e) {
            e.preventDefault();

            const $form = $(this).closest('form');
            const formEl = $form[0];

            const fileInput = $form.find('input[type="file"]')[0];

            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                alert('ظ…ظ† ظپط¶ظ„ظƒ ط§ط®طھط± طµظˆط±ط© ظ‚ط¨ظ„ ط§ظ„ط±ظپط¹.');
                return;
            }

            const formData = new FormData(formEl);

            $.ajax({
                url: "{{ route('dashboard.media.store') }}",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function() {
                    $(fileInput).val('');
                    loadMedia();
                },
                error: function(xhr) {
                    console.error(xhr.responseText || xhr.statusText);
                    alert('طھط¹ط°ظ‘ط± ط±ظپط¹ ط§ظ„طµظˆط±ط©.');
                }
            });
        });



        // ط¬ظ„ط¨ ط§ظ„طµظˆط±
        function loadMedia() {
            $.get("{{ route('dashboard.media.index') }}", function(data) {
                let html = '';
                data.forEach(item => {
                    html += `
                <div class="masonry-item position-relative overflow-hidden" data-path="${item.file_path}">
                    <img src="/storage/${item.file_path}" class="img-fluid media-image">
                    <div class="media-actions position-absolute top-0 end-0 p-2" style="display: none;">
                        <button class="btn btn-sm btn-light border rounded-circle me-1 delete-btn" data-id="${item.id}" title="ط­ط°ظپ">
                            <i class="fas fa-trash text-danger"></i>
                        </button>
                    </div>
                    <div class="info text-center p-2">
                        <small>${item.name}</small>
                    </div>
                </div>
            `;
                });
                $('#mediaGrid').html(html);
            });
        }


        let deleteId = null;

        $(document).on('click', '.delete-btn', function() {
            deleteId = $(this).data('id');

            // ط§ظپطھط­ ط§ظ„ظ…ظˆط¯ط§ظ„ ط¨ط¶ط؛ط· ط§ظ„ط²ط±
            $('#openDeleteModalBtn').click();
        });

        $('#confirmDeleteBtn').click(function() {
            if (deleteId) {
                $.ajax({
                    url: `{{ route('dashboard.media.destroy', ':id') }}`.replace(':id',
                        deleteId),
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function() {
                        $('#closeDeleteModal').click();
                        loadMedia();
                    }
                });
            }
        });
        // ط§ط®طھظٹط§ط± ط§ظ„طµظˆط±ط©
        $(document).on('click', '.masonry-item', function() {
            let path = $(this).data('path');
            $('#closeMediaModal').click();
            const input = document.getElementById('imageInput');
            input.value = path;
            input.dispatchEvent(new Event('input', {
                bubbles: true
            }));
        });
    });
</script>


