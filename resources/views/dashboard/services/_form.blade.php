@push('styles')
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
@endpush

{{-- أيقونة --}}
<div class="col-span-6">
    <label class="block text-sm font-medium">الأيقونة</label>

    <div class="flex items-center gap-2">
        <input type="hidden" id="iconInput" name="icon" value="{{ old('icon', $service->icon ?? '') }}" class="form-control hidden">
        <button type="button" data-pc-toggle="modal" data-pc-target="#mediaModal" id="openMediaModalBtn"
            class="bg-primary text-white px-2 py-1 rounded text-sm">
            اختر من الوسائط أو ارفع جديد
        </button>
    </div>

    @if ($service->icon)
        <img src="{{ asset('storage/' . $service->icon) }}" class="mt-2 w-12 h-12">
    @endif
</div>


{{-- الترتيب --}}
<div class="col-span-6">
    <label class="block text-sm font-medium">ترتيب الظهور</label>
    <input type="number" name="order" value="{{ old('order', $service->order ?? '') }}" class="form-control">
    @error('order')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>
<div class="col-span-6">
    <label class="block text-sm font-medium">رابط الخدمة</label>
    <input type="text" name="url" value="{{ old('url', $service->url ?? '') }}" class="form-control">
    @error('url')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>

{{-- الترجمات --}}
<div class="col-span-12 grid grid-cols-{{ count($languages) }} gap-4">
    @foreach ($languages as $index => $lang)
    @php
    $translation = $serviceTranslations[$lang->code] ?? null;
@endphp
        <div class="border p-4 rounded shadow-sm">
            <h4 class="text-lg font-bold mb-2">{{ $lang->native }}</h4>

            <input type="text" class="form-control mb-2" placeholder="العنوان"
                name="serviceTranslations[{{ $lang->code }}][title]" value="{{ old('serviceTranslations[' . $lang->code . '][title]', $translation['title'] ?? '') }}">

            <textarea class="form-control" rows="3" placeholder="الوصف"
                name="serviceTranslations[{{ $lang->code }}][description]">{{ old('serviceTranslations[' . $lang->code . '][description]', $translation['description'] ?? '') }}</textarea>

            <input type="hidden" name="serviceTranslations[{{ $lang->code }}][locale]" value="{{ $lang->code }}">
        </div>
    @endforeach
</div>

<div class="col-span-12 text-right mt-6">
    <a href="{{ route('dashboard.services.index') }}" class="btn btn-secondary">إلغاء</a>
    <button type="submit" class="btn btn-primary">حفظ</button>
</div>

{{-- مودال الوسائط --}}
<div class="modal fade" id="mediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-2xl font-bold mb-6">📁 مكتبة الوسائط</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeMediaModal"
                    data-pc-modal-dismiss="#mediaModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body p-4">
                <form id="uploadForm" enctype="multipart/form-data" class="mb-3">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="file" name="image" id="imageInput" class="form-control mb-2">
                    <button type="button" id="uploadFormBtn" class="btn btn-primary">رفع صورة</button>
                </form>
                <div id="mediaGrid" class="masonry">
                    {{-- الصور ستُملأ تلقائيًا عبر jQuery --}}
                </div>
            </div>
            {{-- <div class="modal-footer px-4 py-3">
                        <button type="button" class="btn btn-secondary" data-pc-modal-dismiss="#editModal"
                            id="closeEditModal">إلغاء</button>
                        <button type="submit" class="btn btn-success">💾 حفظ التعديلات</button>
                    </div> --}}
        </div>
    </div>
</div>
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeDeleteModal"
                    data-pc-modal-dismiss="#confirmDeleteModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                هل أنت متأكد من حذف هذه الصورة؟
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-pc-modal-dismiss="#confirmDeleteModal"
                    id="closeDeleteModal">إلغاء</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">نعم، حذف</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
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

            // رفع صورة
            $(document).on('click', '#uploadFormBtn', function(e) {
                e.preventDefault();

                const $form = $(this).closest('form');
                const formEl = $form[0];

                const fileInput = $form.find('input[type="file"]')[0];

                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    alert('من فضلك اختر صورة قبل الرفع.');
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
                        alert('تعذّر رفع الصورة.');
                    }
                });
            });

            // جلب الصور
            function loadMedia() {
                $.get("{{ route('dashboard.media.index') }}", function(data) {
                    let html = '';
                    data.forEach(item => {
                        html += `
                <div class="masonry-item position-relative overflow-hidden" data-path="${item.file_path}">
                    <img src="/storage/${item.file_path}" class="img-fluid media-image">
                    <div class="media-actions position-absolute top-0 end-0 p-2" style="display: none;">
                        <button class="btn btn-sm btn-light border rounded-circle me-1 delete-btn" data-id="${item.id}" title="حذف">
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

                // افتح المودال بضغط الزر
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
            // اختيار الصورة
            $(document).on('click', '.masonry-item', function() {
                let path = $(this).data('path');
                $('#closeMediaModal').click();
                const input = document.getElementById('iconInput');
                input.value = path;
                input.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
            });
        });
    </script>
@endpush
