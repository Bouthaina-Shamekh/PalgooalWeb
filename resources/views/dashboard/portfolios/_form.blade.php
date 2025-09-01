@push('styles')
    <style>
        ul[id^="type_suggestions_"] li {
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            line-height: 1.5;
        }

        ul[id^="type_suggestions_"] li:hover {
            background-color: #f3f3f3;
            font-weight: 500;
            border-radius: 5px;
        }

        ul[id^="type_suggestions_"] {
            border-radius: 6px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
            overflow-y: auto;
            max-height: 200px;
            width: 200px;
        }
    </style>
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
{{-- الصورة --}}
<div class="col-span-6">
    <label class="block text-sm font-medium">الصورة الافتراضية</label>

    <div class="flex items-center gap-2">
        <input type="hidden" id="imageInput" name="default_image"
            value="{{ old('default_image', $portfolio->default_image ?? '') }}">
        <button type="button" data-mode="single"
            class="openMediaModal bg-primary text-white px-2 py-1 rounded text-sm">
            اختر من الوسائط
        </button>
    </div>

    @if (!empty($portfolio->default_image))
        <img src="{{ asset('storage/' . $portfolio->default_image) }}" class="mt-2 w-12 h-12">
    @endif

    @error('default_image')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>


{{-- الصور --}}
<div class="col-span-6">
    <label class="block text-sm font-medium">الصور المتعددة</label>

    <div class="flex items-center gap-2">
        <input type="hidden" id="imagesInput" name="images"
            value="{{ old('images', isset($portfolio->images) ? implode(',', json_decode($portfolio->images)) : '') }}">
        <button type="button" data-mode="multiple"
            class="openMediaModal bg-primary text-white px-2 py-1 rounded text-sm">
            اختر من الوسائط
        </button>
    </div>

    @if (!empty($portfolio->images))
        <div class="flex flex-wrap gap-2 mt-2">
            @foreach (json_decode($portfolio->images) as $image)
                <img src="{{ asset('storage/' . $image) }}" class="w-12 h-12 object-cover rounded">
            @endforeach
        </div>
    @endif

    @error('images')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>


{{-- مدة التنفيذ بالأيام --}}
<div class="col-span-6">
    <label class="block text-sm font-medium">مدة التنفيذ بالأيام</label>
    <input type="number" name="implementation_period_days" class="form-control"
        value="{{ old('implementation_period_days', $portfolio->implementation_period_days ?? '') }}">
    @error('implementation_period_days')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>

{{-- الترتيب --}}
<div class="col-span-6">
    <label class="block text-sm font-medium">ترتيب الظهور</label>
    <input type="number" name="order" class="form-control" value="{{ old('order', $portfolio->order ?? '') }}">
    @error('order')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>

{{-- Client --}}
<div class="col-span-6">
    <label class="block text-sm font-medium">العميل</label>
    <input type="text" name="client" class="form-control" value="{{ old('client', $portfolio->client ?? '') }}">
    @error('client')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>

{{-- Date --}}
<div class="col-span-6">
    <label class="block text-sm font-medium">التاريخ</label>
    <input type="date" name="delivery_date" class="form-control"
        value="{{ old('delivery_date', $portfolio->delivery_date ?? '') }}">
    @error('delivery_date')
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</div>

{{-- الترجمات --}}
<div class="col-span-12 grid grid-cols-{{ count($languages) }} gap-4">
    @foreach ($languages as $index => $lang)
        @php
            $translation = $portfolioTranslations[$lang->code] ?? null;
        @endphp
        <div class="border p-4 rounded shadow-sm position-relative">
            <h4 class="text-lg font-bold mb-2">{{ $lang->native }}</h4>

            <input type="text" class="form-control mb-2" placeholder="العنوان"
                name="translations[{{ $index }}][title]"
                value="{{ old('translations.' . $index . '.title', $translation['title'] ?? '') }}">

            <textarea class="form-control mb-2" placeholder="الوصف" rows="3"
                name="translations[{{ $index }}][description]">{{ old('translations.' . $index . '.description', $translation['description'] ?? '') }}</textarea>

            <input type="text" class="form-control mb-2" placeholder="النوع"
                name="translations[{{ $index }}][type]"
                value="{{ old('translations.' . $index . '.type', $translation['type'] ?? '') }}"
                id="type_input_{{ $lang->code }}" oninput="showSuggestions('{{ $lang->code }}')"
                onfocus="showSuggestions('{{ $lang->code }}')"
                onkeydown="handleTypeKeydown(event, '{{ $lang->code }}')" autocomplete="off">

            <ul class="list-group shadow rounded border position-absolute" id="type_suggestions_{{ $lang->code }}"
                style="top: calc(100% + 4px); z-index: 1050; display: none;
                       background: #fff; width: 200px; max-height: 200px;
                       overflow-y: auto; box-shadow: 0 6px 12px rgba(0,0,0,0.15); border: 1px solid #ddd;">
            </ul>

            <input type="text" class="form-control mb-2" placeholder="المواد"
                name="translations[{{ $index }}][materials]"
                value="{{ old('translations.' . $index . '.materials', $translation['materials'] ?? '') }}">

            <input type="text" class="form-control mb-2" placeholder="الرابط"
                name="translations[{{ $index }}][link]"
                value="{{ old('translations.' . $index . '.link', $translation['link'] ?? '') }}">

            <select class="form-control mb-2" name="translations[{{ $index }}][status]">
                <option value="">اختر الحالة</option>
                @foreach ($statusSuggestions[$lang->code] as $status)
                    <option value="{{ $status }}"
                        {{ old('translations.' . $index . '.status', $translation['status'] ?? '') == $status ? 'selected' : '' }}>
                        {{ $status }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="translations[{{ $index }}][locale]"
                value="{{ old('translations.' . $index . '.locale', $lang->code) }}">
        </div>
    @endforeach
</div>

<div class="col-span-12 text-right mt-6">
    <a href="{{ route('dashboard.portfolios.index') }}" class="btn btn-secondary">إلغاء</a>
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
                    <input type="file" name="image" id="imageInputUpload" class="form-control mb-2">
                    <button type="button" id="uploadFormBtn" class="btn btn-primary">رفع صورة</button>
                </form>
                <div id="mediaGrid" class="masonry">
                    {{-- الصور ستُملأ تلقائيًا عبر jQuery --}}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="selectMediaBtn">اختيار</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog"
    aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
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
        let mediaMode = 'single'; // default
        let selectedImages = [];

        $(document).ready(function() {
            // عند فتح المودال
            $(document).on('click', '.openMediaModal', function() {
                mediaMode = $(this).data('mode');
                selectedImages = [];
                loadMedia();
                $('.modal').removeClass('show animate');
                $('#mediaModal').addClass('show animate');
                if (mediaMode === 'multiple') {
                    $('#selectMediaBtn').show();
                } else {
                    $('#selectMediaBtn').hide();
                }
            });

            // إغلاق المودال
            $(document).on('click', '#closeMediaModal', function() {
                $('.modal').removeClass('show animate');
            });

            // جلب الصور
            function loadMedia() {
                $.get("{{ route('dashboard.media.index') }}", function(data) {
                    let html = '';
                    data.forEach(item => {
                        html += `
                        <div class="masonry-item position-relative overflow-hidden border border-light rounded mb-2" data-path="${item.file_path}">
                            <img src="/storage/${item.file_path}" class="img-fluid media-image" style="cursor:pointer;">
                            <div class="media-actions position-absolute top-0 end-0 p-2" style="display: none;">
                                <button class="btn btn-sm btn-light border rounded-circle me-1 delete-btn" data-id="${item.id}" title="حذف">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </div>
                            <div class="info text-center p-2">
                                <small>${item.name}</small>
                            </div>
                        </div>`;
                    });
                    $('#mediaGrid').html(html);
                });
            }

            // اختيار صورة
            $(document).on('click', '.masonry-item', function() {
                const path = $(this).data('path');

                if (mediaMode === 'single') {
                    $('#imageInput').val(path);
                    $('#closeMediaModal').click();
                } else {
                    // multiple mode
                    if (!selectedImages.includes(path)) {
                        selectedImages.push(path);
                        $(this).addClass('border-primary border-2');
                    } else {
                        selectedImages = selectedImages.filter(p => p !== path);
                        $(this).removeClass('border-primary border-2');
                    }

                }
            });

            $(document).on('click', '#closeMediaModal, #selectMediaBtn', function() {
                if (mediaMode === 'multiple') {
                    $('#imagesInput').val(JSON.stringify(selectedImages));
                    $('.modal').removeClass('show animate');
                }
            });

            // رفع صورة جديدة
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
        });
    </script>
@endpush
