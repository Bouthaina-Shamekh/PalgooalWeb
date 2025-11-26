<div id="media-picker-backdrop" class="fixed inset-0 z-[69] bg-black/40 hidden">
</div>

<div id="media-picker-modal" class="fixed inset-0 z-[70] hidden items-center justify-center px-4 py-8">
    <div
        class="relative w-full max-w-5xl rounded-2xl bg-white shadow-2xl dark:bg-gray-950 border border-gray-200 dark:border-gray-800 flex flex-col max-h-[90vh]">
        {{-- Header --}}
        <header class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    اختيار وسائط من المكتبة
                </h2>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    اختر صورة واحدة أو عدة صور حسب إعداد الزر الذي فتح هذه النافذة.
                </p>
            </div>
            <button type="button" id="media-picker-close"
                class="inline-flex h-8 w-8 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                aria-label="إغلاق">
                ✕
            </button>
        </header>

        {{-- Filters + Search --}}
        <section class="px-4 py-3 border-b border-gray-200 dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-type=""
                        class="media-picker-filter-btn rounded-full border px-3 py-1 text-[11px] font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        الكل
                    </button>
                    <button type="button" data-type="image"
                        class="media-picker-filter-btn rounded-full border px-3 py-1 text-[11px] font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        صور
                    </button>
                    <button type="button" data-type="video"
                        class="media-picker-filter-btn rounded-full border px-3 py-1 text-[11px] font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        فيديو
                    </button>
                    <button type="button" data-type="document"
                        class="media-picker-filter-btn rounded-full border px-3 py-1 text-[11px] font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        مستندات
                    </button>
                    <button type="button" data-type="other"
                        class="media-picker-filter-btn rounded-full border px-3 py-1 text-[11px] font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        أخرى
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <input id="media-picker-search" type="text" placeholder="بحث بالاسم أو العنوان..."
                        class="w-full sm:w-64 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
            </div>
        </section>

        {{-- Content --}}
        <main class="flex-1 flex flex-col">
            <div class="flex-1 overflow-y-auto px-4 py-3">
                <div id="media-picker-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-3">
                    {{-- يتم تعبئتها عبر JS --}}
                </div>

                <div id="media-picker-loading" class="mt-4 text-center text-xs text-gray-500 dark:text-gray-400 hidden">
                    جاري التحميل...
                </div>

                <div id="media-picker-empty" class="mt-4 text-center text-xs text-gray-400 dark:text-gray-500 hidden">
                    لا توجد وسائط مطابقة لبحثك حاليًا.
                </div>
            </div>

            {{-- Footer --}}
            <footer
                class="border-t border-gray-200 dark:border-gray-800 px-4 py-3 flex items-center justify-between gap-3">
                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                    العناصر المحددة:
                    <span id="media-picker-selection-count">0</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="media-picker-clear"
                        class="hidden text-[11px] text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        إلغاء التحديد
                    </button>

                    <button type="button" id="media-picker-cancel"
                        class="rounded-full border border-gray-300 px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        إلغاء
                    </button>

                    <button type="button" id="media-picker-confirm"
                        class="rounded-full bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed">
                        استخدام العناصر المحددة
                    </button>
                </div>
            </footer>
        </main>
    </div>
</div>
