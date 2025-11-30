<x-dashboard-layout>
    {{-- CSRF token for AJAX calls from media-library.js --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 px-4 py-6">
        {{-- 
            Header: page title + primary upload button
            - Left: title & subtitle (description)
            - Right: "Upload files" button + hidden <input type="file">
        --}}
        <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    مكتبة الوسائط
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    إدارة الصور والملفات المرفوعة، مع فلترة وبحث وتعديل سريع.
                </p>
            </div>

            <div class="flex items-center gap-3">
                {{-- Primary upload trigger (handled by media-library.js → #btn-upload) --}}
                <button id="btn-upload"
                    class="inline-flex items-center justify-center rounded-lg border border-transparent bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    رفع ملفات
                </button>

                {{-- Hidden file input used for both click and drag & drop uploads --}}
                <input id="file-input" type="file" class="hidden" multiple accept="image/*">
            </div>
        </header>

        {{-- 
            Filter & search bar:
            - Filter pills: all / image / video / document / other
            - Search input: text search by name / title
            - JS hooks:
              * .filter-btn + data-filter-type
              * #search-input
        --}}
        <section
            class="mb-4 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <button data-filter-type=""
                    class="filter-btn rounded-full border px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    الكل
                </button>
                <button data-filter-type="image"
                    class="filter-btn rounded-full border px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    صور
                </button>
                <button data-filter-type="video"
                    class="filter-btn rounded-full border px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    فيديو
                </button>
                <button data-filter-type="document"
                    class="filter-btn rounded-full border px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    مستندات
                </button>
                <button data-filter-type="other"
                    class="filter-btn rounded-full border px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    أخرى
                </button>
            </div>

            {{-- Text search input (used by media-library.js to call /admin/media?search=...) --}}
            <div class="flex items-center gap-2">
                <input id="search-input" type="text" placeholder="بحث بالاسم أو العنوان..."
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:w-64">
            </div>
        </section>

        {{-- 
            Main content:
            - Left (3/4): upload dropzone + media grid + load more
            - Right (1/4): sidebar with details + metadata editing
        --}}
        <section class="mt-4">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 items-start">
                    {{-- 
                        Left side: upload area + media grid
                        - #dropzone: drag & drop upload area (handled by media-library.js)
                        - #media-grid: dynamic grid filled by JS with media items
                        - #media-loading / #media-empty: loading / empty states
                        - #btn-load-more: pagination "Load more" button
                    --}}
                    <section class="lg:col-span-3">
                        {{-- Drag & Drop upload zone (root-level library) --}}
                        <div id="dropzone"
                            class="mb-3 flex min-h-[140px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 text-center text-gray-500 transition hover:border-indigo-400 hover:bg-indigo-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-indigo-400 dark:hover:bg-gray-900/60">
                            <p class="text-sm font-medium">اسحب الملفات هنا أو اضغط على زر "رفع ملفات"</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                يدعم الصور حتى 10MB لكل ملف (JPEG, PNG, GIF, WEBP, SVG)
                            </p>
                        </div>

                        {{-- Media grid container (items rendered by media-library.js) --}}
                        <div id="media-grid"
                            class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                            {{-- Items are injected dynamically via JavaScript --}}
                        </div>

                        {{-- Loading and empty states (toggled by JavaScript) --}}
                        <div id="media-loading" class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            جاري التحميل...
                        </div>

                        <div id="media-empty" class="mt-4 hidden text-center text-sm text-gray-400 dark:text-gray-500">
                            لا توجد وسائط لعرضها حاليًا.
                        </div>

                        {{-- "Load more" pagination button (visible when more pages are available) --}}
                        <div class="mt-4 flex justify-center">
                            <button id="btn-load-more"
                                class="hidden rounded-full border border-gray-300 px-4 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                                تحميل المزيد
                            </button>
                        </div>
                    </section>

                    {{-- 
                        Right side: details sidebar
                        - Shows metadata for the currently selected media item
                        - Also supports multi-selection info and bulk delete
                    --}}
                    <aside id="media-details"
                        class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:sticky lg:top-24">
                        <h2 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                            تفاصيل الوسائط
                        </h2>

                        {{-- Multi-selection info (count + clear selection + bulk delete) --}}
                        <div
                            class="mb-3 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                            <span>
                                العناصر المحددة:
                                <span id="selection-count">0</span>
                            </span>
                            <div class="flex items-center gap-2">
                                {{-- Clear selection button (visible when at least one item is selected) --}}
                                <button type="button" id="btn-clear-selection"
                                    class="hidden text-[11px] text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                    إلغاء التحديد
                                </button>

                                {{-- Bulk delete button (visible when multiple items are selected) --}}
                                <button type="button" id="btn-bulk-delete"
                                    class="hidden text-[11px] text-red-500 hover:text-red-600">
                                    حذف المحدد
                                </button>
                            </div>
                        </div>

                        {{-- Placeholder shown when no media item is selected --}}
                        <div id="details-empty" class="text-xs text-gray-400 dark:text-gray-500">
                            اختر عنصرًا من المكتبة لعرض تفاصيله وتعديل بياناته.
                        </div>

                        {{-- Main details panel (visible when a single media item is selected) --}}
                        <div id="details-panel" class="hidden space-y-3 text-sm">
                            {{-- Preview box (image preview or icon depending on type) --}}
                            <div
                                class="rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800">
                                <img id="details-preview" src="" alt=""
                                    class="mx-auto max-h-40 rounded-md object-contain">
                            </div>

                            {{-- Technical information: type, size, dimensions, path --}}
                            <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                                <div>
                                    <span class="font-semibold">النوع:</span>
                                    <span id="details-type"></span>
                                </div>
                                <div>
                                    <span class="font-semibold">الحجم:</span>
                                    <span id="details-size"></span>
                                </div>
                                <div>
                                    <span class="font-semibold">الأبعاد:</span>
                                    <span id="details-dimensions"></span>
                                </div>
                                <div class="truncate text-[11px]">
                                    <span class="font-semibold">المسار:</span>
                                    <span id="details-path"></span>
                                </div>
                            </div>

                            {{-- Metadata edit form (bound via media-library.js to /admin/media/{id}) --}}
                            <form id="details-form" class="space-y-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        الاسم الأصلي
                                    </label>
                                    <input id="details-original-name" type="text"
                                        class="mt-1 w-full rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        العنوان (Title)
                                    </label>
                                    <input id="details-title" type="text"
                                        class="mt-1 w-full rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        النص البديل (Alt)
                                    </label>
                                    <input id="details-alt" type="text"
                                        class="mt-1 w-full rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        التسمية التوضيحية (Caption)
                                    </label>
                                    <textarea id="details-caption" rows="2"
                                        class="mt-1 w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        الوصف
                                    </label>
                                    <textarea id="details-description" rows="2"
                                        class="mt-1 w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"></textarea>
                                </div>

                                {{-- Footer: delete media + save metadata actions --}}
                                <div class="flex items-center justify-between pt-2">
                                    {{-- Delete current media item --}}
                                    <button type="button" id="btn-delete"
                                        class="text-xs font-medium text-red-500 hover:text-red-600">
                                        حذف الملف
                                    </button>

                                    {{-- Save metadata changes --}}
                                    <button type="submit"
                                        class="rounded-full bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700">
                                        حفظ التعديلات
                                    </button>
                                </div>
                            </form>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </div>

    {{-- 
        Global JS configuration for the media library:
        - baseUrl: base endpoint used by media-library.js for all AJAX calls
        - csrfToken: used for POST/PUT/DELETE requests
    --}}
    <script>
        window.MEDIA_CONFIG = {
            baseUrl: @json(url('admin/media')),
            csrfToken: @json(csrf_token())
        };
    </script>

    {{-- Main media library JavaScript (handles listing, upload, filters, selection, metadata editing) --}}
    <script src="{{ asset('assets/dashboard/js/media-library.js') }}" defer></script>

</x-dashboard-layout>
