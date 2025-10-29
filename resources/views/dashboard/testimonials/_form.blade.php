@php
    $testimonial = $testimonial ?? $feedback ?? null;
    $testimonialTranslations = $testimonialTranslations ?? $feedbackTranslations ?? [];
@endphp

{{-- الصورة --}}
<div class="col-span-6 space-y-4">
    <label class="block text-sm font-medium text-gray-700">الصورة</label>

    <div class="flex flex-wrap items-center gap-3">
        <input type="hidden" id="imagePathInput" name="image_path"
            value="{{ old('image_path', $testimonial?->image ?? '') }}">

        <button type="button" id="openMediaModalBtn"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M12 4v16m8-8H4" />
            </svg>
            <span>اختيار من مكتبة الوسائط</span>
        </button>
    </div>

    @php
        $initialImagePath = old('image_path', $testimonial?->image ?? '');
    @endphp
    <div id="imagePreview"
        class="flex h-28 w-full items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white/80">
        @if ($initialImagePath)
            <img src="{{ asset('storage/' . $initialImagePath) }}" alt="الصورة الحالية"
                class="h-24 w-24 rounded-xl object-cover shadow">
        @else
            <p class="text-sm text-gray-500">لم يتم اختيار صورة بعد</p>
        @endif
    </div>

    <div class="space-y-2">
        <label for="uploadImageInput" class="block text-sm font-medium text-gray-700">رفع صورة جديدة</label>
        <input type="file" id="uploadImageInput" name="image" accept="image/*"
            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
        <p class="text-xs text-gray-500">يمكنك رفع صورة بديلة من جهازك (الحد الأقصى 5MB).</p>
    </div>

    @error('image_path')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('image')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

{{-- ترتيب العرض --}}
<div class="col-span-6 space-y-2">
    <label class="block text-sm font-medium text-gray-700">ترتيب العرض</label>
    <input type="number" name="order" min="1"
        value="{{ old('order', $testimonial?->order ?? '') }}"
        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
    @error('order')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

{{-- التقييم بالنجوم --}}
<div class="col-span-6 space-y-2">
    <label class="block text-sm font-medium text-gray-700">عدد النجوم</label>
    <input type="number" name="star" min="1" max="5"
        value="{{ old('star', $testimonial?->star ?? '') }}"
        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
    <p class="text-xs text-gray-500">اختر قيمة من 1 إلى 5.</p>
    @error('star')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

{{-- الترجمات --}}
<div class="col-span-12 mt-8">
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="px-6 pt-5">
            <h3 class="text-lg font-semibold text-gray-800">الترجمات</h3>
            <p class="mt-1 text-sm text-gray-500">يرجى تعبئة تفاصيل الشهادة لكل لغة متاحة.</p>
        </div>

        <div class="flex flex-wrap gap-2 border-b border-gray-200 px-6 pt-4" role="tablist">
            @foreach ($languages as $lang)
                <button type="button" data-lang="{{ $lang->code }}" role="tab"
                    aria-controls="lang-panel-{{ $lang->code }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                    class="lang-tab rounded-lg px-3 py-2 text-sm font-medium transition {{ $loop->first ? 'bg-indigo-600 text-white shadow' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $lang->native }}
                </button>
            @endforeach
        </div>

        <div class="space-y-8 px-6 py-6">
            @foreach ($languages as $lang)
                @php
                    $translation = $testimonialTranslations[$lang->code] ?? null;
                @endphp
                <div id="lang-panel-{{ $lang->code }}" role="tabpanel"
                    class="lang-panel {{ $loop->first ? 'block' : 'hidden' }} space-y-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">اسم صاحب الشهادة</label>
                        <input type="text" name="testimonialTranslations[{{ $lang->code }}][name]"
                            value="{{ old('testimonialTranslations.' . $lang->code . '.name', $translation['name'] ?? '') }}"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                        @error('testimonialTranslations.' . $lang->code . '.name')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">نص الشهادة</label>
                        <textarea rows="4" name="testimonialTranslations[{{ $lang->code }}][feedback]"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">{{ old('testimonialTranslations.' . $lang->code . '.feedback', $translation['feedback'] ?? '') }}</textarea>
                        @error('testimonialTranslations.' . $lang->code . '.feedback')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">المسمى الوظيفي</label>
                        <input type="text" name="testimonialTranslations[{{ $lang->code }}][major]"
                            value="{{ old('testimonialTranslations.' . $lang->code . '.major', $translation['major'] ?? '') }}"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                        @error('testimonialTranslations.' . $lang->code . '.major')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <input type="hidden" name="testimonialTranslations[{{ $lang->code }}][locale]"
                        value="{{ $lang->code }}">
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="col-span-12 mt-6 flex justify-end gap-3">
    <a href="{{ route('dashboard.testimonials.index') }}"
        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-600 shadow-sm hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-400">إلغاء</a>
    <button type="submit"
        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">حفظ</button>
</div>

{{-- نافذة اختيار الوسائط --}}
<div id="mediaModal" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" data-modal-dismiss></div>

    <div class="relative mx-auto flex h-full max-h-[95vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">مكتبة الوسائط</h2>
                <p class="text-sm text-gray-500">اختر صورة من المكتبة أو قم برفع صورة جديدة.</p>
            </div>
            <button type="button" data-modal-dismiss
                class="inline-flex items-center justify-center rounded-full border border-gray-300 bg-white p-2 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-hidden">
            <div class="grid gap-6 px-6 py-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex w-full max-w-md items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M21 21l-4.35-4.35m0 0A7 7 0 1010 17.5a7 7 0 006.65-4.85z" />
                        </svg>
                        <input type="text" id="mediaSearch" placeholder="ابحث عن صورة" autocomplete="off"
                            class="w-full border-0 bg-transparent text-sm text-gray-700 focus:outline-none">
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="mediaRefresh"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-400">
                            تحديث القائمة
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div id="mediaMessage" class="hidden rounded-lg px-4 py-3 text-sm"></div>

                    <div id="mediaLoading" class="hidden items-center justify-center py-12">
                        <svg class="h-6 w-6 animate-spin text-indigo-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4"></circle>
                            <path class="opacity-75" stroke-width="4" d="M4 12a8 8 0 018-8"></path>
                        </svg>
                    </div>

                    <div id="mediaEmpty"
                        class="hidden rounded-xl border border-dashed border-gray-300 px-4 py-12 text-center text-sm text-gray-500">
                        لا توجد عناصر في المكتبة بعد.
                    </div>

                    <div class="max-h-[50vh] overflow-y-auto rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div id="mediaGrid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4"></div>
                    </div>
                </div>

                <form id="mediaUploadForm" class="space-y-3 rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-700">رفع صورة جديدة</h3>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <input type="file" id="mediaUploadInput" name="image" accept="image/*"
                            class="flex-1 min-w-[180px] rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            رفع الصورة
                        </button>
                    </div>
                    <p class="text-xs text-gray-500">الامتدادات المدعومة: JPG, PNG, GIF, WEBP, SVG بحجم أقصى 10MB.</p>
                </form>
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
            <button type="button" data-modal-dismiss
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-400">إغلاق</button>
            <button type="button" id="useSelectedMedia"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">استخدام الصورة</button>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        (function($) {
            const MEDIA_INDEX_URL = "{{ route('dashboard.media.index') }}";
            const MEDIA_STORE_URL = "{{ route('dashboard.media.store') }}";
            const MEDIA_DELETE_URL = "{{ route('dashboard.media.destroy', ':id') }}";
            const STORAGE_BASE_URL = "{{ rtrim(asset('storage'), '/') }}";
            const placeholderHtml = '<p class="text-sm text-gray-500">لم يتم اختيار صورة بعد</p>';

            const $mediaModal = $('#mediaModal');
            const $mediaGrid = $('#mediaGrid');
            const $mediaLoading = $('#mediaLoading');
            const $mediaEmpty = $('#mediaEmpty');
            const $mediaMessage = $('#mediaMessage');
            const $mediaSearch = $('#mediaSearch');
            const $mediaUploadForm = $('#mediaUploadForm');
            const $mediaUploadInput = $('#mediaUploadInput');
            const $imagePathInput = $('#imagePathInput');
            const $imagePreview = $('#imagePreview');
            const $uploadImageInput = $('#uploadImageInput');

            let mediaItems = [];
            let selectedMediaId = null;

            function openMediaModal() {
                selectedMediaId = null;
                showMessage('');
                $mediaModal.removeClass('hidden').addClass('flex');
                loadMedia();
            }

            function closeMediaModal() {
                $mediaModal.removeClass('flex').addClass('hidden');
                showMessage('');
            }

            function showMessage(message, type = 'info') {
                const classes = {
                    info: 'bg-blue-50 text-blue-700 border border-blue-100',
                    success: 'bg-green-50 text-green-700 border border-green-100',
                    warning: 'bg-amber-50 text-amber-700 border border-amber-100',
                    error: 'bg-red-50 text-red-700 border border-red-100'
                };

                if (!message) {
                    $mediaMessage.addClass('hidden').removeClass('bg-blue-50 bg-green-50 bg-amber-50 bg-red-50 text-blue-700 text-green-700 text-amber-700 text-red-700 border');
                    $mediaMessage.text('');
                    return;
                }

                $mediaMessage.removeClass('hidden bg-blue-50 bg-green-50 bg-amber-50 bg-red-50 text-blue-700 text-green-700 text-amber-700 text-red-700 border');
                $mediaMessage.addClass(classes[type] || classes.info);
                $mediaMessage.text(message);
            }

            function formatBytes(bytes) {
                if (!bytes) return '0 KB';
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(1024));
                return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
            }

            function renderMedia(items) {
                if (!items.length) {
                    $mediaGrid.empty();
                    $mediaEmpty.removeClass('hidden');
                    return;
                }
                $mediaEmpty.addClass('hidden');

                const cards = items.map(item => {
                    const url = item.url || `${STORAGE_BASE_URL}/${item.file_path}`;
                    const name = item.name || 'بدون اسم';
                    const size = formatBytes(item.size || 0);
                    const isSelected = selectedMediaId === item.id;
                    const selectedClasses = isSelected ? 'ring-2 ring-indigo-500 border-indigo-400' : 'hover:border-indigo-400';

                    return `
                        <div class="media-card relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition cursor-pointer ${selectedClasses}"
                             data-id="${item.id}" data-path="${item.file_path}" data-url="${url}">
                            <img src="${url}" alt="${name}" class="h-40 w-full object-cover" loading="lazy">
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-3 py-2 text-white">
                                <p class="text-xs font-semibold truncate" title="${name}">${name}</p>
                                <p class="text-[11px] opacity-80">${size}</p>
                            </div>
                            <button type="button" class="delete-media absolute top-2 right-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-red-500 shadow hover:bg-red-100"
                                data-id="${item.id}" title="حذف">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    `;
                }).join('');

                $mediaGrid.html(cards);
            }

            function loadMedia() {
                $mediaLoading.removeClass('hidden');
                $.get(MEDIA_INDEX_URL)
                    .done(function(response) {
                        mediaItems = Array.isArray(response) ? response : (response?.data || []);
                        renderMedia(mediaItems);
                    })
                    .fail(function() {
                        renderMedia([]);
                        showMessage('تعذر تحميل مكتبة الوسائط.', 'error');
                    })
                    .always(function() {
                        $mediaLoading.addClass('hidden');
                    });
            }

            function applySelectedImage(path, url) {
                if (!url) {
                    url = `${STORAGE_BASE_URL}/${path}`;
                }
                $imagePathInput.val(path);
                $imagePreview.html(`<img src="${url}" alt="الصورة المختارة" class="h-24 w-24 rounded-xl object-cover shadow">`);
            }

            function resetPreviewIfEmpty() {
                const path = $imagePathInput.val();
                if (!path && !$uploadImageInput[0].files.length) {
                    $imagePreview.html(placeholderHtml);
                }
            }

            function debounce(fn, delay) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => fn.apply(this, args), delay);
                };
            }

            $('[data-modal-dismiss]').on('click', function() {
                closeMediaModal();
            });

            $('#openMediaModalBtn').on('click', function(e) {
                e.preventDefault();
                openMediaModal();
            });

            $('#useSelectedMedia').on('click', function() {
                if (!selectedMediaId) {
                    showMessage('يرجى اختيار صورة أولاً.', 'warning');
                    return;
                }
                const selected = mediaItems.find(item => item.id === selectedMediaId);
                if (!selected) {
                    showMessage('لم يتم العثور على الصورة المختارة.', 'error');
                    return;
                }
                applySelectedImage(selected.file_path, selected.url);
                closeMediaModal();
            });

            $('#mediaRefresh').on('click', function() {
                loadMedia();
            });

            $mediaSearch.on('input', debounce(function() {
                const term = $(this).val().toLowerCase();
                const filtered = !term
                    ? mediaItems
                    : mediaItems.filter(item => (item.name || '').toLowerCase().includes(term));
                renderMedia(filtered);
            }, 200));

            $(document).on('click', '.media-card', function() {
                const id = Number($(this).data('id'));
                selectedMediaId = id;
                $('.media-card').removeClass('ring-2 ring-indigo-500 border-indigo-400');
                $(this).addClass('ring-2 ring-indigo-500 border-indigo-400');
            });

            $(document).on('click', '.delete-media', function(e) {
                e.stopPropagation();
                const id = $(this).data('id');
                if (!confirm('هل أنت متأكد من حذف هذه الصورة؟')) {
                    return;
                }
                $.ajax({
                    url: MEDIA_DELETE_URL.replace(':id', id),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    }
                }).done(function() {
                    if (selectedMediaId === id) {
                        selectedMediaId = null;
                    }
                    showMessage('تم حذف الصورة بنجاح.', 'success');
                    loadMedia();
                }).fail(function() {
                    showMessage('تعذر حذف الصورة.', 'error');
                });
            });

            $mediaUploadForm.on('submit', function(e) {
                e.preventDefault();
                const file = $mediaUploadInput[0].files[0];
                if (!file) {
                    showMessage('يرجى اختيار ملف لرفعه.', 'warning');
                    return;
                }
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('image', file);

                showMessage('جاري رفع الصورة...', 'info');

                $.ajax({
                    url: MEDIA_STORE_URL,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                }).done(function() {
                    $mediaUploadInput.val('');
                    showMessage('تم رفع الصورة بنجاح.', 'success');
                    loadMedia();
                }).fail(function(xhr) {
                    const msg = xhr.responseJSON?.message || 'تعذر رفع الصورة، حاول لاحقًا.';
                    showMessage(msg, 'error');
                });
            });

            $uploadImageInput.on('change', function() {
                if (!this.files || !this.files[0]) {
                    resetPreviewIfEmpty();
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(event) {
                    $imagePreview.html(`<img src="${event.target.result}" alt="الصورة الجديدة" class="h-24 w-24 rounded-xl object-cover shadow">`);
                    $imagePathInput.val('');
                };
                reader.readAsDataURL(this.files[0]);
            });

            $('.lang-tab').on('click', function() {
                const lang = $(this).data('lang');
                $('.lang-tab').removeClass('bg-indigo-600 text-white shadow').addClass('bg-gray-100 text-gray-600');
                $(this).removeClass('bg-gray-100 text-gray-600').addClass('bg-indigo-600 text-white shadow');

                $('.lang-panel').addClass('hidden');
                $(`#lang-panel-${lang}`).removeClass('hidden');
            });

            const currentPath = $imagePathInput.val();
            if (!currentPath && !$imagePreview.find('img').length) {
                $imagePreview.html(placeholderHtml);
            }
        })(jQuery);
    </script>
@endpush
