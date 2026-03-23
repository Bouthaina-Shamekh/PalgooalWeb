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
{{-- الصورة الافتراضية --}}
@php
    // إذا كانت البيانات نصية (من قاعدة البيانات القديمة بمسارات)، احفظها
    // إذا كانت رقمية (من media picker)، استخدمها مباشرة
    $rawDefaultImage = old('default_image', $portfolio->default_image ?? null);
    $defaultImageId = null;
    $defaultImagePreviewUrls = [];
    
    // إذا كانت القيمة رقمية، هذا ID من media picker
    if (is_numeric($rawDefaultImage)) {
        $defaultImageId = (int)$rawDefaultImage;
        $media = \App\Models\Media::find($defaultImageId);
        if ($media && $media->file_path) {
            $defaultImagePreviewUrls = [asset('storage/' . $media->file_path)];
        }
    } elseif (is_string($rawDefaultImage) && !empty($rawDefaultImage)) {
        // نصية = مسار قديم، استخدمها للمعاينة فقط
        $defaultImagePreviewUrls = [asset('storage/' . $rawDefaultImage)];
        // سنحفظها كنص حالياً (للتوافقية للخلف)
        $defaultImageId = $rawDefaultImage;
    }
@endphp
<x-dashboard.media-picker 
    id="default_image_picker"
    name="default_image" 
    label="الصورة الافتراضية"
    :value="$defaultImageId"
    :previewUrls="$defaultImagePreviewUrls"
    buttonText="اختر صورة من المكتبة"
/>


{{-- الصور المتعددة --}}
@php
    $rawImages = old('images', $portfolio->images ?? null);
    $imagesArray = [];
    $imagesPreviewUrls = [];
    
    if ($rawImages) {
        // تحويل JSON إلى array إذا لزم الأمر
        if (is_string($rawImages)) {
            $decoded = json_decode($rawImages, true);
            $imagesArray = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        } elseif (is_array($rawImages)) {
            $imagesArray = $rawImages;
        }
        
        // إذا كانت العناصر أرقام (IDs من media picker)
        if (!empty($imagesArray) && is_numeric($imagesArray[0] ?? null)) {
            $mediaRecords = \App\Models\Media::whereIn('id', $imagesArray)->get();
            foreach ($mediaRecords as $media) {
                if ($media->file_path) {
                    $imagesPreviewUrls[] = asset('storage/' . $media->file_path);
                }
            }
        } else {
            // مسارات قديمة (للتوافقية للخلف)
            foreach ($imagesArray as $path) {
                if (!empty($path)) {
                    $imagesPreviewUrls[] = asset('storage/' . $path);
                }
            }
        }
    }
@endphp
<x-dashboard.media-picker 
    id="images_picker"
    name="images" 
    label="الصور المتعددة"
    multiple="true"
    :value="implode(',', array_filter($imagesArray))"
    :previewUrls="$imagesPreviewUrls"
    buttonText="اختر صورًا من المكتبة"
/>


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

{{-- الترجمات (واجهة تبويبات مثل الخدمات) --}}
<div class="col-span-12 mt-8">
    <div
        class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-gray-800 dark:to-gray-700 p-6 rounded-xl border border-indigo-200 dark:border-gray-600 mb-6">
        <h3 class="flex items-center text-xl font-cairo-bold text-gray-800 dark:text-gray-200 mb-2">
            <svg class="w-6 h-6 ml-2 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129">
                </path>
            </svg>
            ترجمات المعرض
        </h3>
        <p class="text-gray-600 dark:text-gray-300 font-cairo-regular">أدخل العنوان والحقول الأخرى لكل لغة</p>
    </div>

    <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
        <!-- تبويبات اللغات -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-0 space-x-2 rtl:space-x-reverse px-6 pt-6 overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600"
            role="tablist" id="portfolioLanguageTabs">
            @foreach ($languages as $index => $lang)
                <button type="button" onclick="portfolioSwitchLanguageTab('{{ $lang->code }}')"
                    onkeydown="portfolioHandleTabKeydown(event, '{{ $lang->code }}')"
                    id="lang-tab-{{ $lang->code }}" role="tab" aria-controls="lang-panel-{{ $lang->code }}"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}"
                    class="lang-tab-btn px-4 py-3 rounded-t-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:focus:ring-indigo-500 whitespace-nowrap hover:bg-gray-50 dark:hover:bg-gray-700 {{ $loop->first ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-500 dark:border-indigo-400 font-cairo-semibold' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-transparent font-cairo-regular' }}">
                    <div class="flex items-center space-x-2 rtl:space-x-reverse">
                        <div
                            class="w-6 h-6 bg-indigo-100 dark:bg-indigo-800 text-indigo-600 dark:text-indigo-300 rounded-full flex items-center justify-center text-xs font-cairo-bold">
                            {{ strtoupper(substr($lang->code, 0, 2)) }}
                        </div>
                        <span>{{ $lang->native }}</span>
                        @if ($loop->first)
                            <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400 opacity-75" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>

        <!-- Panels -->
        <div class="p-6 bg-gray-50 dark:bg-gray-900">
            @foreach ($languages as $index => $lang)
                @php $translation = $portfolioTranslations[$lang->code] ?? null; @endphp
                <div id="lang-panel-{{ $lang->code }}" role="tabpanel"
                    aria-labelledby="lang-tab-{{ $lang->code }}"
                    class="lang-panel {{ $loop->first ? 'block' : 'hidden' }} opacity-100 transform transition-all duration-300 ease-out">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">العنوان</label>
                            <input type="text" class="form-control" placeholder="العنوان"
                                name="translations[{{ $index }}][title]"
                                value="{{ old('translations.' . $index . '.title', $translation['title'] ?? '') }}"
                                @if ($lang->is_active) required @endif>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">الوصف</label>
                            <textarea class="form-control" placeholder="الوصف" rows="3"
                                name="translations[{{ $index }}][description]">{{ old('translations.' . $index . '.description', $translation['description'] ?? '') }}</textarea>
                        </div>

                        <div class="relative">
                            <label class="block text-sm font-medium mb-1">النوع</label>
                            <input type="text" class="form-control" placeholder="النوع"
                                name="translations[{{ $index }}][type]"
                                value="{{ old('translations.' . $index . '.type', $translation['type'] ?? '') }}"
                                id="type_input_{{ $lang->code }}" oninput="showSuggestions('{{ $lang->code }}')"
                                onfocus="showSuggestions('{{ $lang->code }}')"
                                onkeydown="handleTypeKeydown(event, '{{ $lang->code }}')" autocomplete="off"
                                @if ($lang->is_active) required @endif>
                            <ul class="list-group shadow rounded border position-absolute"
                                id="type_suggestions_{{ $lang->code }}"
                                style="top: calc(100% + 4px); z-index: 1050; display: none; background: #fff; width: 200px; max-height: 200px; overflow-y: auto; box-shadow: 0 6px 12px rgba(0,0,0,0.15); border: 1px solid #ddd;">
                            </ul>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">المواد</label>
                            <input type="text" class="form-control" placeholder="المواد"
                                name="translations[{{ $index }}][materials]"
                                value="{{ old('translations.' . $index . '.materials', $translation['materials'] ?? '') }}"
                                @if ($lang->is_active) required @endif>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">الرابط</label>
                            <input type="text" class="form-control" placeholder="الرابط"
                                name="translations[{{ $index }}][link]"
                                value="{{ old('translations.' . $index . '.link', $translation['link'] ?? '') }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">الحالة</label>
                            <select class="form-control" name="translations[{{ $index }}][status]">
                                <option value="">اختر الحالة</option>
                                @foreach ($statusSuggestions[$lang->code] ?? ($statusSuggestions['en'] ?? []) as $status)
                                    <option value="{{ $status }}"
                                        {{ old('translations.' . $index . '.status', $translation['status'] ?? '') == $status ? 'selected' : '' }}>
                                        {{ $status }}</option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="translations[{{ $index }}][locale]"
                            value="{{ old('translations.' . $index . '.locale', $lang->code) }}">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
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

@push('scripts')
    <script>
        // تبويبات لغات ترجمة المعرض (معزولة عن خدمات)
        document.addEventListener('DOMContentLoaded', function() {
            const tabIds = @json($languages->pluck('code'));

            function setTabActive(tabEl, isActive) {
                if (!tabEl) return;
                if (isActive) {
                    tabEl.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'text-gray-600', 'dark:text-gray-300',
                        'border-transparent', 'font-cairo-regular');
                    tabEl.classList.add('bg-white', 'dark:bg-gray-800', 'text-indigo-600', 'dark:text-indigo-400',
                        'border-b-2', 'border-indigo-500', 'dark:border-indigo-400', 'font-cairo-semibold');
                    tabEl.setAttribute('aria-selected', 'true');
                    tabEl.setAttribute('tabindex', '0');
                } else {
                    tabEl.classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-600', 'dark:text-gray-300',
                        'border-transparent', 'font-cairo-regular');
                    tabEl.classList.remove('bg-white', 'dark:bg-gray-800', 'text-indigo-600',
                        'dark:text-indigo-400', 'border-b-2', 'border-indigo-500', 'dark:border-indigo-400',
                        'font-cairo-semibold');
                    tabEl.setAttribute('aria-selected', 'false');
                    tabEl.setAttribute('tabindex', '-1');
                    const check = tabEl.querySelector('.lang-checkmark');
                    if (check) check.remove();
                }
            }

            window.portfolioSwitchLanguageTab = function(langCode) {
                // deactivate all
                document.querySelectorAll('#portfolioLanguageTabs .lang-tab-btn').forEach(tab => setTabActive(
                    tab, false));
                document.querySelectorAll('.lang-panel').forEach(p => {
                    p.classList.add('hidden');
                    p.classList.remove('block');
                });

                // activate
                const activeTab = document.getElementById('lang-tab-' + langCode);
                setTabActive(activeTab, true);
                const iconContainer = activeTab?.querySelector('.flex');
                iconContainer && iconContainer.insertAdjacentHTML('beforeend', `
                <svg class="lang-checkmark w-4 h-4 text-indigo-500 dark:text-indigo-400 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>`);

                const panel = document.getElementById('lang-panel-' + langCode);
                if (panel) {
                    panel.classList.remove('hidden');
                    panel.classList.add('block');
                    setTimeout(() => {
                        panel.querySelector('input[type="text"]')?.focus();
                    }, 60);
                }
                localStorage.setItem('portfolioActiveLangTab', langCode);
            }

            window.portfolioHandleTabKeydown = function(event, langCode) {
                const tabs = document.querySelectorAll('#portfolioLanguageTabs .lang-tab-btn');
                const idx = Array.from(tabs).findIndex(t => t.id === 'lang-tab-' + langCode);
                if (idx < 0) return;
                let next = null;
                if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    next = (idx - 1 + tabs.length) % tabs.length;
                }
                if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    next = (idx + 1) % tabs.length;
                }
                if (event.key === 'Home') {
                    event.preventDefault();
                    next = 0;
                }
                if (event.key === 'End') {
                    event.preventDefault();
                    next = tabs.length - 1;
                }
                if (next != null) {
                    const code = tabs[next].id.replace('lang-tab-', '');
                    window.portfolioSwitchLanguageTab(code);
                    tabs[next].focus();
                }
            }

            const saved = localStorage.getItem('portfolioActiveLangTab');
            const first = saved && tabIds.includes(saved) ? saved : tabIds[0];
            if (first) window.portfolioSwitchLanguageTab(first);
        });
    </script>
@endpush
