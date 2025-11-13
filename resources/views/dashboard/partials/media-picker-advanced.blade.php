@php
    $fieldName = $fieldName ?? 'icon';
    $value = old($fieldName, $value ?? '');
    $label = $label ?? 'أيقونة الخدمة';
    $buttonText = $buttonText ?? 'اختر أو حدد أيقونة جديدة من مكتبة الوسائط';
    $supportedFormatsText = $supportedFormatsText ?? 'الصيغ المدعومة: JPG, PNG, SVG';
@endphp

{{-- الأيقونة --}}
<div class="col-span-6">
    <div
        class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 p-6 rounded-xl border border-blue-200 dark:border-gray-600">
        <label class="flex items-center text-sm font-cairo-semibold text-gray-700 dark:text-gray-200 mb-4">
            <svg class="w-5 h-5 ml-2 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                </path>
            </svg>
            {{ $label }}
        </label>

        <div class="flex items-center justify-between gap-6">
            <input type="hidden" id="iconInput" name="{{ $fieldName }}" value="{{ $value }}">

            <!-- زر اختيار الوسائط -->
            <button type="button" id="openMediaModalBtn"
                class="group relative inline-flex items-center gap-3 px-6 py-4 bg-primary hover:bg-secondary text-white font-cairo-semibold rounded-2xl shadow-lg shadow-primary/25 hover:shadow-xl hover:shadow-secondary/30 transition-all duration-300 transform hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-primary/20 dark:focus:ring-primary/40 active:scale-95 backdrop-blur-sm border border-white/10">

                <!-- أيقونة سحابية -->
                <div class="relative">
                    <svg class="w-6 h-6 transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                        </path>
                    </svg>
                    <!-- مؤشر متوهج -->
                    <div
                        class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full border-2 border-white animate-pulse">
                    </div>
                </div>

                <!-- نص الزر -->
                <span class="text-base group-hover:text-blue-50 transition-colors">
                    {{ $buttonText }}
                </span>

                <!-- خط تحتي -->
                <div
                    class="absolute bottom-0 left-0 w-0 h-0.5 bg-white/30 group-hover:w-full transition-all duration-300">
                </div>

                <!-- تأثير لمعان -->
                <div
                    class="absolute inset-0 rounded-2xl bg-gradient-to-br from-white/0 via-white/5 to-white/0 group-hover:from-white/10 group-hover:via-white/20 group-hover:to-white/10 transition-all duration-300">
                </div>
            </button>

            <!-- معلومات الصيغ المدعومة -->
            <div class="hidden sm:flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 font-cairo-regular">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ $supportedFormatsText }}</span>
            </div>
        </div>

        @if (!empty($value))
            <div
                class="preview-container mt-4 p-4 bg-white dark:bg-gray-800 rounded-xl border-2 border-green-200 dark:border-green-700 shadow-sm">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('storage/' . ltrim($value, '/')) }}"
                        class="preview-image w-16 h-16 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 object-cover">
                    <div>
                        <p class="preview-message text-sm font-medium text-green-700 dark:text-green-300 mb-1">تم تحديث الأيقونة بنجاح!</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">يمكنك تغييرها في أي وقت عبر "اختيار أيقونة جديدة"</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
<div class="fixed inset-0 z-[9999] hidden" id="mediaModal" aria-hidden="true" aria-modal="true" role="dialog"
    aria-labelledby="mediaModalTitle" style="z-index: 9999 !important;">
    <!-- Background overlay -->
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80 transition-opacity backdrop-blur-sm"
        id="modalBackdrop"></div>

    <!-- Modal container -->
    <div class="fixed inset-0 overflow-y-auto flex items-center justify-center p-4">
        <div class="flex min-h-full items-center justify-center p-4 w-full">
            <div class="modal-content relative transform rounded-2xl bg-white dark:bg-gray-800 text-left shadow-2xl transition-all w-full max-w-7xl max-h-[95vh] flex flex-col scale-95 opacity-0 overflow-y-auto"
                id="modalContent">
                <!-- Focus trap start sentinel -->
                <button class="sr-only" aria-hidden="true" tabindex="0" id="focusStart"></button>

                <!-- Header -->
                <div
                    class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-700 dark:via-gray-800 dark:to-gray-900 border-b border-gray-200 dark:border-gray-600 p-6">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center space-x-4 rtl:space-x-reverse">
                            <div class="relative">
                                <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div
                                    class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white">
                                </div>
                            </div>
                            <div>
                                <h5 id="mediaModalTitle"
                                    class="text-2xl font-cairo-bold text-gray-900 dark:text-gray-100 mb-1">مكتبة
                                    الوسائط</h5>
                                <p
                                    class="text-gray-600 dark:text-gray-300 text-sm flex items-center font-cairo-regular">
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    اختر صورة أو اسحبها مباشرة لرفعها
                                </p>
                            </div>
                        </div>

                        <!-- إحصائيات -->
                        <div class="hidden md:flex items-center space-x-6 rtl:space-x-reverse text-sm">
                            <div
                                class="text-center bg-blue-50 dark:bg-blue-900/20 rounded-lg px-3 py-2 border border-blue-100 dark:border-blue-800">
                                <div class="text-xl font-cairo-bold text-blue-600 dark:text-blue-400"
                                    id="totalImages">0</div>
                                <div class="text-gray-600 dark:text-gray-400 font-cairo-medium text-sm">صورة</div>
                            </div>
                            <div
                                class="text-center bg-green-50 dark:bg-green-900/20 rounded-lg px-3 py-2 border border-green-100 dark:border-green-800">
                                <div class="text-xl font-cairo-bold text-green-600 dark:text-green-400"
                                    id="selectedCount">0</div>
                                <div class="text-gray-600 dark:text-gray-400 font-cairo-medium text-sm">محدد</div>
                            </div>
                        </div>

                        <button type="button"
                            class="p-2 hover:bg-red-100 dark:hover:bg-red-900 hover:text-red-600 dark:hover:text-red-400 rounded-full transition-all duration-200 group"
                            id="closeMediaModal" aria-label="إغلاق النافذة">
                            <svg class="w-6 h-6 text-gray-500 dark:text-gray-400 group-hover:text-red-600 dark:group-hover:text-red-400 group-hover:rotate-90 transition-all duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- aria-live region for toasts (screen readers) -->
                <div id="toastRegion" class="sr-only" aria-live="polite"></div>

                <!-- Tabs Navigation -->
                <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex">
                        <button type="button" id="mediaLibraryTab"
                            class="tab-button active flex-1 px-6 py-4 text-sm font-cairo font-medium text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20 transition-all hover:bg-blue-100 dark:hover:bg-blue-900/30">
                            <div class="flex items-center justify-center space-x-2 rtl:space-x-reverse">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <span>مكتبة الصور</span>
                                <span
                                    class="bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-800 dark:to-indigo-800 text-blue-800 dark:text-blue-200 px-2.5 py-1 rounded-full text-xs font-cairo-medium border border-blue-200 dark:border-blue-600 shadow-sm mr-2"
                                    id="libraryCount">0</span>
                            </div>
                        </button>
                        <button type="button" id="uploadNewTab"
                            class="tab-button flex-1 px-6 py-4 text-sm font-cairo font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all border-b-2 border-transparent">
                            <div class="flex items-center justify-center space-x-2 rtl:space-x-reverse">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                    </path>
                                </svg>
                                <span>رفع صورة جديدة</span>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden">
                    <div id="tabContent" class="h-full">
                        <!-- Media Library Content -->
                        <div id="mediaLibraryContent" class="tab-content active h-full flex flex-col">
                            <!-- شريط أدوات -->
                            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600 p-4">
                                <div
                                    class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                                    <!-- البحث والفلترة على اليسار -->
                                    <div
                                        class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 w-full lg:w-auto">
                                        <!-- حقل البحث -->
                                        <div class="relative flex-3 min-w-[300px] group">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 group-hover:text-blue-500 transition-colors duration-200"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            </div>
                                            <input type="text" id="imageSearch" placeholder="ابحث في الصور..."
                                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-2xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 hover:border-blue-300 dark:hover:border-blue-600 transition-all duration-300 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 text-gray-900 dark:text-gray-100 font-cairo-regular shadow-sm hover:shadow-md focus:shadow-lg backdrop-blur-sm">
                                        </div>

                                        <!-- فلتر الأنواع -->
                                        <div class="relative flex-3 min-w-[100px] group">
                                            <select id="fileTypeFilter"
                                                class="appearance-none border-2 border-gray-200 dark:border-gray-600 rounded-2xl px-4 py-3 pr-10 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 hover:border-blue-300 dark:hover:border-blue-600 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 text-gray-900 dark:text-gray-100 font-cairo-medium shadow-sm hover:shadow-md focus:shadow-lg transition-all duration-300 min-w-[180px] w-full sm:w-auto">
                                                <option value="">🎨 جميع الأنواع</option>
                                                <option value="jpg,jpeg">📷 JPEG</option>
                                                <option value="png">🖼️ PNG</option>
                                                <option value="gif">🎞️ GIF</option>
                                                <option value="webp">🌐 WebP</option>
                                                <option value="svg">⚡ SVG</option>
                                            </select>
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- أزرار العرض والتحديث على اليمين -->
                                    <div
                                        class="flex items-center gap-3 w-full sm:w-auto justify-center sm:justify-end">
                                        <div
                                            class="flex items-center bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-2xl p-1.5 shadow-sm border border-gray-200 dark:border-gray-600">
                                            <button type="button" id="gridView"
                                                class="view-btn active px-3 py-2 rounded-xl text-sm font-medium transition-all duration-300 hover:scale-105">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                                                    </path>
                                                </svg>
                                            </button>
                                            <button type="button" id="listView"
                                                class="view-btn px-3 py-2 rounded-xl text-sm font-medium transition-all duration-300 hover:scale-105">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                                </svg>
                                            </button>

                                            <!-- فاصل -->
                                            <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                                            <!-- زر التحديث -->
                                            <button type="button" id="refreshMedia"
                                                class="px-3 py-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-xl transition-all duration-300 hover:scale-110 hover:rotate-180"
                                                title="تحديث القائمة">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                    </path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- شبكة الصور -->
                            <div
                                class="flex-1 overflow-y-auto scroll-smooth scrollbar-thin scrollbar-thumb-blue-300 scrollbar-track-gray-100 dark:scrollbar-thumb-blue-600 dark:scrollbar-track-gray-700 hover:scrollbar-thumb-blue-400 dark:hover:scrollbar-thumb-blue-500">
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h6
                                            class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex items-center font-cairo-bold">
                                            <svg class="w-5 h-5 ml-2 text-gray-600 dark:text-gray-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                                </path>
                                            </svg>
                                            <span>مكتبة الصور</span>
                                            <span id="imageCounter"
                                                class="mr-3 px-3 py-1.5 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 text-blue-800 dark:text-blue-200 text-sm rounded-full font-cairo-semibold border border-blue-200 dark:border-blue-700 shadow-sm">0</span>
                                        </h6>

                                        <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                            <div
                                                class="flex items-center bg-gradient-to-r from-gray-50 to-white dark:from-gray-700 dark:to-gray-800 rounded-xl border border-gray-200 dark:border-gray-600 shadow-sm">
                                                <button type="button" id="scrollToTopBtn" title="الانتقال لأعلى"
                                                    class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-l-xl transition-all duration-300 hover:scale-110">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                                    </svg>
                                                </button>
                                                <div class="w-px h-5 bg-gray-200 dark:bg-gray-600"></div>
                                                <button type="button" id="scrollToBottomBtn" title="الانتقال لأسفل"
                                                    class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-r-xl transition-all duration-300 hover:scale-110">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div
                                                class="w-px h-8 bg-gradient-to-b from-transparent via-gray-300 to-transparent dark:via-gray-600">
                                            </div>
                                            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                                <button type="button" id="selectAllBtn"
                                                    class="px-4 py-2 text-sm font-cairo-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-700 transition-all duration-300 hover:scale-105 shadow-sm">
                                                    ✓ تحديد الكل
                                                </button>
                                                <button type="button" id="clearSelectionBtn"
                                                    class="px-4 py-2 text-sm font-cairo-medium text-gray-600 hover:text-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 transition-all duration-300 hover:scale-105 shadow-sm">
                                                    ✕ إلغاء التحديد
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex-1 overflow-hidden">
                                    <div id="mediaLoading" class="flex items-center justify-center py-12">
                                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                                            <div
                                                class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin">
                                            </div>
                                            <span class="text-gray-600">جاري تحميل الصور...</span>
                                        </div>
                                    </div>

                                    <div id="mediaEmpty" class="hidden text-center py-12">
                                        <div
                                            class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                        <p class="text-gray-500 text-lg mb-2">لا توجد صور بعد</p>
                                        <p class="text-gray-400 text-sm">ابدأ برفع أول صورة لك</p>
                                    </div>

                                    <div id="mediaGrid"
                                        class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-4 p-4 overflow-y-auto scroll-smooth"
                                        style="min-height: 300px; max-height: 50vh; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 12px;">
                                        {{-- الصور تُحقن هنا --}}
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div
                                    class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 border-t border-gray-200 dark:border-gray-600 p-6">
                                    <div class="flex flex-col sm:flex-row items-center justify-between w-full gap-4">
                                        <div
                                            class="flex items-center space-x-4 rtl:space-x-reverse text-sm text-gray-600 dark:text-gray-300">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="font-cairo-regular text-gray-600 dark:text-gray-400">المحدد:</span>
                                                <span id="selectedInfo"
                                                    class="font-cairo-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2.5 py-1 rounded-full text-xs border border-blue-200 dark:border-blue-700">لم
                                                    يتم التحديد</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                                            <button type="button"
                                                class="px-6 py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 font-cairo-regular"
                                                id="cancelSelectionBtn">إلغاء</button>
                                            <button type="button" id="confirmSelectionBtn" disabled
                                                class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-primary rounded-lg hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-cairo-semibold">
                                                تأكيد الاختيار
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload New Images Content (المُصلَح) -->
                        <div id="uploadNewContent" class="tab-content hidden h-full">
                            <div id="uploadDropZone"
                                class="flex-1 min-h-[400px] mx-4 mt-4 mb-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-800 hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-300 cursor-pointer">
                                <div class="h-full flex flex-col items-center justify-center p-8 text-center">
                                    <div class="mb-6 relative">
                                        <div
                                            class="w-24 h-24 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-3xl shadow-2xl flex items-center justify-center transform hover:scale-110 transition-transform duration-300">
                                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                                </path>
                                            </svg>
                                        </div>
                                        <div
                                            class="absolute -top-2 -right-2 w-8 h-8 bg-green-500 rounded-full border-4 border-white dark:border-gray-800 animate-pulse">
                                        </div>
                                    </div>

                                    <h3 class="text-2xl font-cairo-bold text-gray-900 dark:text-white mb-4">اسحب الصور
                                        هنا أو اضغط للتحديد</h3>
                                    <p class="text-gray-600 dark:text-gray-300 mb-6 max-w-md font-cairo-regular">يمكنك
                                        رفع صور بصيغة JPG، PNG، GIF أو SVG بحد أقصى 10MB لكل صورة</p>

                                    <!-- label/for يضمن ظهور نافذة الاختيار -->
                                    <label for="imageUploadInput" id="selectFilesBtn"
                                        class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-cairo-semibold rounded-2xl shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300 cursor-pointer">
                                        <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        اختر الصور
                                    </label>

                                    <!-- input مخفي بأسلوب sr-only -->
                                    <input type="file" id="imageUploadInput" multiple accept="image/*,.svg"
                                        class="sr-only" />

                                    <div
                                        class="mt-8 flex flex-wrap items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span
                                            class="bg-white dark:bg-gray-700 px-3 py-1 rounded-full border">JPG</span>
                                        <span
                                            class="bg-white dark:bg-gray-700 px-3 py-1 rounded-full border">PNG</span>
                                        <span
                                            class="bg-white dark:bg-gray-700 px-3 py-1 rounded-full border">GIF</span>
                                        <span
                                            class="bg-white dark:bg-gray-700 px-3 py-1 rounded-full border">SVG</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress -->
                            <div id="uploadProgress" class="mx-4 mb-4 hidden">
                                <div class="bg-white dark:bg-gray-700 rounded-xl p-4 shadow-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-cairo-medium text-gray-700 dark:text-gray-300">جاري
                                            الرفع...</span>
                                        <span class="text-sm font-cairo-bold text-blue-600"
                                            id="uploadPercentage">0%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-3">
                                        <div id="uploadProgressBar"
                                            class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-300"
                                            style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview -->
                            <div id="uploadPreview" class="mx-4 mb-4 hidden">
                                <h4 class="text-lg font-cairo-semibold text-gray-900 dark:text-white mb-4">معاينة الصور
                                    المحددة:</h4>
                                <div id="previewGrid"
                                    class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 max-h-40 overflow-y-auto">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Focus trap end sentinel -->
                <button class="sr-only" aria-hidden="true" tabindex="0" id="focusEnd"></button>
            </div> <!-- /modal-content -->
        </div>
    </div>

    {{-- تأكيد الحذف --}}
    <div class="fixed inset-0 z-[10001] hidden bg-black/50 backdrop-blur-sm items-center justify-center p-4"
        id="confirmDeleteModal" role="dialog" aria-modal="true" aria-labelledby="confirmDeleteModalLabel"
        aria-hidden="true">
        <!-- خلفية المودال -->
        <div class="absolute inset-0 bg-black/50" id="deleteModalBackdrop"></div>

        <!-- محتوى المودال -->
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0"
            id="deleteModalContent">
            <!-- رأس المودال -->
            <div
                class="bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-b border-gray-200 dark:border-gray-600 rounded-t-2xl p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 rtl:space-x-reverse">
                        <div class="p-2 bg-red-100 dark:bg-red-900 rounded-full">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </div>
                        <h5 class="text-xl font-bold text-gray-800 dark:text-gray-200">تأكيد الحذف</h5>
                    </div>
                    <button type="button"
                        class="p-2 hover:bg-red-100 dark:hover:bg-red-900 rounded-full transition-colors duration-200"
                        id="closeDeleteModal">
                        <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- جسم المودال -->
            <div class="p-6">
                <div class="flex items-center space-x-3 rtl:space-x-reverse">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.182 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h6 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">هل أنت متأكد؟</h6>
                        <p class="text-gray-600 dark:text-gray-400">سيتم حذف هذه الصورة نهائياً ولا يمكن التراجع عن هذا
                            الإجراء.</p>
                    </div>
                </div>
            </div>

            <!-- تذييل المودال -->
            <div class="bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 rounded-b-2xl p-6">
                <div class="flex items-center justify-end space-x-3 rtl:space-x-reverse">
                    <button type="button"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        id="closeDeleteModalBtn">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        إلغاء
                    </button>
                    <button type="button"
                        class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-300"
                        id="confirmDeleteBtn">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        نعم، حذف نهائياً
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .tab-button {
                transition: all .3s ease
            }

        .tab-button.active {
            background-color: rgba(59, 130, 246, .1);
            color: #3b82f6;
            border-bottom-color: #3b82f6
        }

        .dark .tab-button.active {
            background-color: rgba(99, 102, 241, .2);
            color: #6366f1;
            border-bottom-color: #6366f1
        }

        /* Enhanced View Buttons */
        .view-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: #6b7280;
            background: transparent;
        }

        .view-btn:hover {
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            transform: scale(1.05);
        }

        .view-btn.active {
            color: #ffffff;
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.3);
            transform: scale(1.05);
        }

        .dark .view-btn.active {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            box-shadow: 0 4px 14px 0 rgba(79, 70, 229, 0.4);
        }

        .tab-content {
            transition: opacity .3s ease;
            width: 100%;
            min-height: 500px;
            max-height: calc(95vh - 200px)
        }

        .tab-content.active {
            display: flex !important;
            flex-direction: column;
            opacity: 1
        }

        .tab-content.hidden {
            display: none !important;
            opacity: 0
        }

        .modal-content {
            max-height: 90vh !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            scroll-behavior: smooth
        }

        .modal-content::-webkit-scrollbar {
            width: 10px
        }

        .modal-content::-webkit-scrollbar-track {
            background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
            border-radius: 10px;
            border: 1px solid #e2e8f0
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #60a5fa, #3b82f6);
            border-radius: 10px;
            border: 2px solid #f8fafc;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, .1)
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #3b82f6, #1d4ed8);
            box-shadow: inset 0 1px 4px rgba(0, 0, 0, .2)
        }

        .dark .modal-content::-webkit-scrollbar-track {
            background: linear-gradient(to bottom, #374151, #1f2937);
            border-color: #4b5563
        }

        .dark .modal-content::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #6366f1, #4f46e5);
            border-color: #374151
        }

        #mediaGrid.masonry-enhanced {
            display: grid;
            grid-template-columns: repeat(8, minmax(0, 1fr));
            gap: .75rem
        }

        @media (max-width:1536px) {
            #mediaGrid.masonry-enhanced {
                grid-template-columns: repeat(6, 1fr)
            }
        }

        @media (max-width:1280px) {
            #mediaGrid.masonry-enhanced {
                grid-template-columns: repeat(5, 1fr)
            }
        }

        @media (max-width:1024px) {
            #mediaGrid.masonry-enhanced {
                grid-template-columns: repeat(4, 1fr)
            }
        }

        @media (max-width:768px) {
            #mediaGrid.masonry-enhanced {
                grid-template-columns: repeat(3, 1fr)
            }
        }

        @media (max-width:640px) {
            #mediaGrid.masonry-enhanced {
                grid-template-columns: repeat(2, 1fr)
            }
        }

        /* تحسين عرض البحث والفلاتر */
        #imageSearch {
            min-width: 250px;
        }

        @media (max-width: 768px) {
            #imageSearch {
                min-width: 200px;
            }
        }

        @media (max-width: 640px) {
            .flex.flex-col.sm\\:flex-row.items-stretch.sm\\:items-center {
                flex-direction: column;
                align-items: stretch;
            }

            #imageSearch {
                min-width: auto;
                width: 100%;
            }

            #fileTypeFilter {
                min-width: auto;
                width: 100%;
            }
        }

        /* تحسين تصميم القائمة المنسدلة */
        #fileTypeFilter {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: left 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
        }

        #fileTypeFilter option {
            padding: 8px 12px;
            background: white;
            color: #374151;
            font-family: 'Cairo', sans-serif;
        }

        #fileTypeFilter option:hover {
            background: #f3f4f6;
        }

        #fileTypeFilter option:checked {
            background: #3b82f6;
            color: white;
        }

        #mediaGrid.list-view {
            display: block
        }

        #mediaGrid.list-view .media-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            height: 96px;
            aspect-ratio: auto;
            padding-right: .5rem;
            padding-left: .25rem
        }

        #mediaGrid.list-view .media-item img {
            width: 96px;
            height: 96px;
            object-fit: cover;
            border-radius: .75rem
        }

        #mediaGrid .media-item {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            min-height: 150px !important;
            background: #fff !important;
            border: 1px solid #e5e7eb !important
        }

        #mediaGrid .media-item img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            display: block !important
        }

        #mediaGrid {
            min-height: 300px;
            max-height: 60vh;
            position: relative
        }

        #mediaGrid::-webkit-scrollbar {
            width: 8px;
            height: 8px
        }

        #mediaGrid::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
            margin: 4px
        }

        #mediaGrid::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, #60a5fa, #3b82f6);
            border-radius: 8px;
            border: 1px solid #e2e8f0
        }

        #mediaGrid::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, #3b82f6, #1d4ed8)
        }
    </style>
    @endpush
@endonce

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function() {
            // ======== حالة عامة ========
            let mediaItems = []; // GET /dashboard/media (JSON)
            let selectedImages = []; // IDs
            let currentFilter = '';
            let currentSearch = '';
            const MEDIA_INDEX_URL = "{{ route('dashboard.media.index') }}";
            const MEDIA_STORE_URL = "{{ route('dashboard.media.store') }}";
            const MEDIA_DELETE_URL = "{{ route('dashboard.media.destroy', ':id') }}".replace('%3Aid', ':id');
            // Use relative path to avoid APP_URL port mismatches in dev
            const STORAGE_PUBLIC_URL = "/storage";
            window.SVG_PLACEHOLDER =
                "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 150' preserveAspectRatio='none'%3E%3Crect width='100%25' height='100%25' fill='%23e5e7eb'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%239ca3af' font-family='Cairo,Arial' font-size='14'%3ENo Image%3C/text%3E%3C/svg%3E";

            // منع إرسال النموذج بالضغط على Enter في نافذة الوسائط
            $(document).on('keydown', '#mediaModal', function(e) {
                if (e.key === 'Enter' && !$(e.target).is('textarea')) {
                    e.preventDefault();
                    return false;
                }
            });

            // ======== تبويب نافذة الوسائط (مكتبة/رفع) ========
            function switchTab(tabId) {
                $('.tab-button').removeClass(
                        'active text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20'
                    )
                    .addClass('text-gray-500 dark:text-gray-400 border-transparent');
                $('.tab-content').removeClass('active').addClass('hidden');

                if (tabId === 'media-library') {
                    $('#mediaLibraryTab').addClass(
                            'active text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20'
                        )
                        .removeClass('text-gray-500 dark:text-gray-400 border-transparent');
                    $('#mediaLibraryContent').removeClass('hidden').addClass('active');
                } else {
                    $('#uploadNewTab').addClass(
                            'active text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20'
                        )
                        .removeClass('text-gray-500 dark:text-gray-400 border-transparent');
                    $('#uploadNewContent').removeClass('hidden').addClass('active');
                    setTimeout(initializeUploadElements, 100);
                }
            }
            $('#mediaLibraryTab').on('click', () => switchTab('media-library'));
            $('#uploadNewTab').on('click', () => switchTab('upload-new'));

            // ======== فتح/إغلاق مودال الوسائط ========
            function openMediaModal() {
                selectedImages = [];
                lastFocusedBeforeModal = document.activeElement;
                $('#mediaModal').removeClass('hidden').css('display', 'flex').removeAttr('aria-hidden');
                $('body').addClass('overflow-hidden');
                $('#modalBackdrop,#modalContent').removeAttr('aria-hidden');
                $('#modalContent').removeClass('opacity-0 scale-95').addClass('opacity-100 scale-100');

                $('#imageSearch').val('');
                $('#fileTypeFilter').val('');
                currentFilter = '';
                currentSearch = '';
                switchTab('media-library');
                loadMedia();
                initializeUploadElements();
            }

            function closeMediaModal() {
                $('#mediaModal .modal-content').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
                setTimeout(() => {
                    $('#mediaModal').addClass('hidden').css('display', 'none').attr('aria-hidden', 'true');
                    $('body').removeClass('overflow-hidden');
                    $('#modalBackdrop,#modalContent').attr('aria-hidden', 'true');
                    if (lastFocusedBeforeModal && typeof lastFocusedBeforeModal.focus === 'function') {
                        lastFocusedBeforeModal.focus();
                    } else {
                        document.getElementById('openMediaModalBtn')?.focus();
                    }
                }, 200);
            }
            $(document).on('click', '#openMediaModalBtn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openMediaModal();
                return false;
            });
            $(document).on('click', '#closeMediaModal, #cancelSelectionBtn', closeMediaModal);
            $(document).on('click', '#modalBackdrop', function(e) {
                if (e.target === e.currentTarget) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeMediaModal();
                    return false;
                }
            });

            // منع إغلاق النافذة عند النقر على المحتوى
            $(document).on('click', '#modalContent', function(e) {
                e.stopPropagation();
                return false;
            });
            let lastFocusedBeforeModal = null;
            $(document).on('keydown', e => {
                if (e.key === 'Escape' && !$('#mediaModal').hasClass('hidden')) closeMediaModal();
                if (!$('#mediaModal').hasClass('hidden') && e.key === 'Tab') {
                    const $scope = $('#modalContent');
                    const $focusables = $scope.find(
                            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
                        .filter(':visible');
                    if (!$focusables.length) return;
                    const first = $focusables.get(0);
                    const last = $focusables.get($focusables.length - 1);
                    if (e.shiftKey && document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    } else if (!e.shiftKey && document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            });

            // ======== رفع الصور (تبويب الرفع) ========
            let uploadedFiles = [];

            function initializeUploadElements() {
                $('#uploadPreview').addClass('hidden');
                $('#uploadProgress').addClass('hidden');
                $('#imageUploadInput').val('');
            }
            // التغيير من input
            $(document).on('change', '#imageUploadInput', function() {
                handleFileSelect(this.files);
            });
            // السحب والإفلات + الضغط
            $(document).on('dragover', '#uploadDropZone', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            $(document).on('dragleave', '#uploadDropZone', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });
            $(document).on('drop', '#uploadDropZone', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                handleFileSelect(e.originalEvent.dataTransfer.files);
            });
            $(document).on('click', '#uploadDropZone', function() {
                const el = document.getElementById('imageUploadInput');
                if (el) el.click();
            });

            function handleFileSelect(files) {
                if (!files || !files.length) return;
                const validFiles = [];
                const maxSize = 10 * 1024 * 1024;
                Array.from(files).forEach(file => {
                    if (file.size > maxSize) return alert(`الملف ${file.name} أكبر من 10MB`);
                    if (!file.type.startsWith('image/') && file.type !== 'image/svg+xml') return alert(
                        `الملف ${file.name} ليس صورة صالحة`);
                    validFiles.push(file);
                });
                if (validFiles.length) {
                    showFilePreview(validFiles);
                    uploadFiles(validFiles);
                }
            }

            function showFilePreview(files) {
                const grid = document.getElementById('previewGrid');
                grid.innerHTML = '';
                files.forEach(file => {
                    const reader = new FileReader();
                    reader.onload = e => {
                        const wrap = document.createElement('div');
                        wrap.className = 'relative group';
                        wrap.innerHTML = `
          <img src="${e.target.result}" alt="${file.name}" class="w-full h-20 object-cover rounded-lg border-2 border-gray-200 dark:border-gray-600">
          <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-200 rounded-lg flex items-center justify-center">
            <span class="text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-200">${file.name}</span>
          </div>`;
                        grid.appendChild(wrap);
                    };
                    reader.readAsDataURL(file);
                });
                document.getElementById('uploadPreview').classList.remove('hidden');
            }

            function uploadFiles(files) {
                function makeFormData(fieldName) {
                    const fd = new FormData();
                    if (fieldName.endsWith('[]')) {
                        files.forEach(file => fd.append(fieldName, file));
                    } else {
                        // أغلب الـ Controllers المفردة تتوقع "image" واحدة
                        fd.append(fieldName, files[0]);
                    }
                    fd.append('_token', '{{ csrf_token() }}');
                    return fd;
                }

                function parseAndShowError(xhr) {
                    let msg = 'حدث خطأ أثناء رفع الصور';
                    const res = xhr.responseJSON;
                    if (xhr.status === 422 && res?.errors) {
                        const firstKey = Object.keys(res.errors)[0];
                        msg = res.errors[firstKey]?.[0] || msg;
                    } else if (res?.message) {
                        msg = res.message; // لو رجعت "validation.required" سنعرضها كما هي
                    } else if (xhr.statusText) {
                        msg = xhr.statusText;
                    }
                    alert(msg);
                }

                function send(fieldName, onRetryIfNeeded) {
                    $('#uploadProgress').removeClass('hidden');
                    return $.ajax({
                        url: MEDIA_STORE_URL,
                        type: 'POST',
                        data: makeFormData(fieldName),
                        processData: false,
                        contentType: false,
                        xhr: function() {
                            const xhr = new window.XMLHttpRequest();
                            xhr.upload.addEventListener('progress', function(evt) {
                                if (evt.lengthComputable) {
                                    const percent = Math.round((evt.loaded / evt.total) * 100);
                                    $('#uploadProgressBar').css('width', percent + '%');
                                    $('#uploadPercentage').text(percent + '%');
                                }
                            });
                            return xhr;
                        }
                    }).done(function() {
                        $('#uploadProgress').addClass('hidden');
                        $('#uploadPreview').addClass('hidden');
                        $('#uploadProgressBar').css('width', '0%');
                        $('#uploadPercentage').text('0%');
                        $('#imageUploadInput').val('');
                        switchTab('media-library');
                        loadMedia();
                        alert('تم رفع الصور بنجاح!');
                    }).fail(function(xhr) {
                        // لو فالديشن يقول "files" مطلوبة، جرّب "image"
                        const errs = xhr.responseJSON?.errors || {};
                        const filesErr = errs.files || errs['files.*'];
                        if (xhr.status === 422 && filesErr && onRetryIfNeeded) {
                            onRetryIfNeeded(); // المحاولة الثانية بـ image
                        } else {
                            $('#uploadProgress').addClass('hidden');
                            parseAndShowError(xhr);
                        }
                    });
                }

                // 1) المحاولة الأولى بـ files[]
                // 2) إن فشلت لغياب "files" نحاول بـ image
                send('files[]', () => send('image'));
            }


            // ======== جلب الوسائط وعرضها ========
            function loadMedia(applyFilter = false) {
                showMediaLoading();
                $.getJSON(MEDIA_INDEX_URL)
                    .done(data => {
                        mediaItems = Array.isArray(data) ? data : [];
                        hideMediaLoading();
                        if (!mediaItems.length) showMediaEmpty();
                        else renderMediaGrid(applyFilter ? filterItems() : mediaItems);
                        updateStats();
                    })
                    .fail(xhr => {
                        hideMediaLoading();
                        showMediaEmpty();
                        showToast('فشل تحميل الصور: ' + xhr.status + ' ' + xhr.statusText, 'error');
                    });
            }

            function filterItems() {
                let items = mediaItems.slice();
                if (currentSearch) {
                    items = items.filter(it => (it.name || '').toLowerCase().includes(currentSearch));
                }
                if (currentFilter) {
                    const types = currentFilter.split(',').map(s => s.trim().toLowerCase());
                    items = items.filter(it => {
                        const mt = (it.mime_type || '').toLowerCase(); // e.g., image/png, image/svg+xml
                        const sub = mt.includes('/') ? mt.split('/').pop() : mt; // png, jpeg, svg+xml
                        if (!sub) return false;
                        // Normalize svg+xml to svg match
                        const normalized = sub.startsWith('svg') ? 'svg' : sub;
                        return types.includes(normalized);
                    });
                }
                return items;
            }

            function renderMediaGrid(items) {
                const $grid = $('#mediaGrid');
                if (!items.length) return showMediaEmpty();
                const html = items.map(item => {
                    const isSel = selectedImages.includes(item.id);
                    const size = formatBytes(item.size || 0);
                    const name = item.name || 'صورة';
                    const border = isSel ? 'border-green-500 ring-4 ring-green-200 dark:ring-green-800' :
                        'border-transparent hover:border-blue-500';
                    const badge = isSel ?
                        `<div class="select-badge absolute top-2 left-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center text-white">
                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                     </div>` :
                        `<div class="select-badge absolute top-2 left-2 w-6 h-6 border-2 border-white/80 rounded-full bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>`;
                    return `
        <div class="media-item relative aspect-square rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer border-2 ${border} group"
             data-id="${item.id}" data-path="${item.file_path}" data-name="${name}" data-size="${item.size || 0}">
      <img src="${item.url || (STORAGE_PUBLIC_URL + '/' + item.file_path)}" alt="${name}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" loading="lazy"
          onerror="this.onerror=null; this.src=window.SVG_PLACEHOLDER">
          <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button type="button" class="preview w-8 h-8 bg-white/90 dark:bg-gray-800/90 hover:bg-white dark:hover:bg-gray-700 rounded-lg flex items-center justify-center text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300" data-id="${item.id}" title="معاينة">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
            </button>
            <button type="button" class="delete-btn w-8 h-8 bg-white/90 dark:bg-gray-800/90 hover:bg-red-100 dark:hover:bg-red-900 rounded-lg flex items-center justify-center text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300" data-id="${item.id}" title="حذف">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>
          <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent text-white p-3 transform translate-y-full group-hover:translate-y-0 transition-transform">
            <div class="text-xs font-cairo-medium truncate" title="${name}">${name}</div>
            <div class="text-xs font-cairo-light opacity-80">${size}</div>
          </div>
          ${badge}
        </div>`;
                }).join('');
                $grid.html(html);
                $('#mediaEmpty').addClass('hidden');
                $('#mediaLoading').addClass('hidden');
            }

            // اختيار/إلغاء تحديد عنصر من الشبكة (اختيار واحد)
            $(document).on('click', '#mediaGrid .media-item', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if ($(e.target).closest('button').length) return false;
                const id = parseInt($(this).data('id'));
                const isSelected = selectedImages.includes(id);
                if (isSelected) {
                    selectedImages = [];
                    $(this).removeClass('border-green-500 ring-4 ring-green-200 dark:ring-green-800')
                        .addClass('border-transparent');
                    $(this).find('.select-badge').replaceWith(
                        `<div class="select-badge absolute top-2 left-2 w-6 h-6 border-2 border-white/80 rounded-full bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>`
                    );
                } else {
                    selectedImages = [id];
                    $('#mediaGrid .media-item').removeClass(
                        'border-green-500 ring-4 ring-green-200 dark:ring-green-800').addClass(
                        'border-transparent');
                    $('#mediaGrid .media-item .select-badge').replaceWith(
                        `<div class="select-badge absolute top-2 left-2 w-6 h-6 border-2 border-white/80 rounded-full bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>`
                    );
                    $(this).removeClass('border-transparent').addClass(
                        'border-green-500 ring-4 ring-green-200 dark:ring-green-800');
                    $(this).find('.select-badge').replaceWith(
                        `<div class="select-badge absolute top-2 left-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center text-white"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"></path></svg></div>`
                    );
                }
                updateStats();
            });

            // معاينة
            $(document).on('click', '.preview', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const id = parseInt($(this).data('id'));
                const item = mediaItems.find(x => x.id === id);
                if (!item) return;
                const $m = $(`
      <div class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/75 p-4" id="imagePreviewModal">
        <div class="relative max-w-4xl w-full max-h-full bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-2xl">
          <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 border-b dark:border-gray-700">
            <div>
              <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">${item.name || 'صورة'}</h3>
              <p class="text-sm text-gray-600 dark:text-gray-300">${formatBytes(item.size || 0)}</p>
            </div>
            <button class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full" id="closePreview">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="p-4">
                        <img src="${item.url || (STORAGE_PUBLIC_URL + '/' + item.file_path)}" alt="${item.name || ''}" class="max-w-full max-h-96 mx-auto rounded-lg" onerror="this.onerror=null; this.src=window.SVG_PLACEHOLDER">
          </div>
          <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-700">
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" id="chooseThisImage">اختيار هذه الصورة</button>
          </div>
        </div>
      </div>`);
                $('body').append($m);
                $m.on('click', e2 => {
                    if (e2.target.id === 'imagePreviewModal') $m.remove();
                });
                $('#closePreview').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $m.remove();
                });
                $('#chooseThisImage').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectImage(item.file_path);
                    $m.remove();
                });
            });

            // تأكيد الاختيار
            $(document).on('click', '#confirmSelectionBtn', function() {
                if (!selectedImages.length) return;
                const selectedId = selectedImages[0];
                const selectedItem = mediaItems.find(item => item.id === selectedId);
                if (!selectedItem) return showToast('تعذر العثور على الصورة المحددة.', 'error');
                selectImage(selectedItem.file_path);
            });

            // اختيار الصورة لحقل الأيقونة
            window.selectImage = function(path) {
                const $input = $('#iconInput');
                if ($input.length) {
                    // Avoid triggering input events recursively; we update the preview manually
                    $input.val(path);
                    updateIconPreview(path);
                }
                showToast('تم اختيار الصورة بنجاح!', 'success');
                closeMediaModal();
            };

            function updateIconPreview(path) {
                const $section = $('#iconInput').closest('.col-span-6');
                let $preview = $section.find('.preview-container');
                if (!$preview.length) {
                    $preview = $(`
        <div class="preview-container mt-4 p-4 bg-white dark:bg-gray-800 rounded-xl border-2 border-green-200 dark:border-green-700 shadow-sm">
          <div class="flex items-center gap-3">
            <img class="preview-image w-16 h-16 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 object-cover" />
            <div>
              <p class="preview-message text-sm font-medium text-green-700 dark:text-green-300 mb-1">تم اختيار الأيقونة بنجاح!</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">يمكنك تغييرها بالضغط على "اختر من الوسائط"</p>
            </div>
          </div>
        </div>`);
                    $section.find('.bg-gradient-to-r').append($preview);
                }
                $preview.addClass('border-2 border-green-200 dark:border-green-700');
                $preview.find('.preview-image').attr('src', "/storage/" + path).attr('alt',
                    'أيقونة الخدمة');
                $preview.hide().fadeIn(250);
            }

            // حذف صورة
            let deleteId = null;

            // فتح نافذة تأكيد الحذف
            function openDeleteModal() {
                const modal = $('#confirmDeleteModal');
                const modalContent = $('#deleteModalContent');
                modal.removeClass('hidden').addClass('flex');
                $('body').addClass('overflow-hidden');
                setTimeout(() => {
                    modalContent.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
                }, 10);
            }

            // إغلاق نافذة تأكيد الحذف
            function closeDeleteModal() {
                const modal = $('#confirmDeleteModal');
                const modalContent = $('#deleteModalContent');
                modalContent.removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
                setTimeout(() => {
                    modal.removeClass('flex').addClass('hidden');
                    $('body').removeClass('overflow-hidden');
                    deleteId = null;
                }, 200);
            }

            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                deleteId = $(this).data('id');
                openDeleteModal();
                return false;
            });

            $(document).on('click', '#closeDeleteModal, #closeDeleteModalBtn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeDeleteModal();
                return false;
            });

            $(document).on('click', '#deleteModalBackdrop', function(e) {
                if (e.target === this) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeDeleteModal();
                    return false;
                }
            });

            $('#confirmDeleteBtn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (!deleteId) return false;

                // إضافة حالة التحميل
                $(this).prop('disabled', true).html(`
                    <svg class="w-5 h-5 ml-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    جاري الحذف...
                `);

                $.ajax({
                    url: MEDIA_DELETE_URL.replace(':id', deleteId),
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    }
                }).done(() => {
                    closeDeleteModal();
                    mediaItems = mediaItems.filter(x => x.id !== deleteId);
                    selectedImages = selectedImages.filter(x => x !== deleteId);
                    $(`#mediaGrid .media-item[data-id="${deleteId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        if (!mediaItems.length) showMediaEmpty();
                        updateStats();
                    });
                    showToast('تم حذف الصورة بنجاح', 'success');
                }).fail(xhr => {
                    closeDeleteModal();
                    const errorMsg = xhr.responseJSON?.message || 'فشل حذف الصورة';
                    showToast(errorMsg, 'error');
                }).always(() => {
                    $('#confirmDeleteBtn').prop('disabled', false).html(`
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        نعم، حذف نهائياً
                    `);
                });
            });

            // بحث/فلترة/عرض
            $('#imageSearch').on('input', debounce(function() {
                currentSearch = $(this).val().toLowerCase();
                renderMediaGrid(filterItems());
                updateStats();
            }, 250));
            $('#fileTypeFilter').on('change', function() {
                currentFilter = $(this).val();
                renderMediaGrid(filterItems());
                updateStats();
            });
            $('.view-btn').on('click', function() {
                $('.view-btn').removeClass('active');
                $(this).addClass('active');
                const view = $(this).attr('id');
                if (view === 'listView') $('#mediaGrid').removeClass('masonry-enhanced').addClass(
                    'list-view');
                else $('#mediaGrid').removeClass('list-view').addClass('masonry-enhanced');
            });
            $('#refreshMedia').on('click', function() {
                const $icon = $(this).find('svg');
                $icon.addClass('animate-spin');
                loadMedia(true);
                setTimeout(() => $icon.removeClass('animate-spin'), 800);
            });

            // أزرار تنقل سريع
            $('#scrollToTopBtn').on('click', function() {
                const mediaGrid = document.getElementById('mediaGrid');
                const modalContent = document.querySelector('.modal-content');
                mediaGrid?.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                modalContent?.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            $('#scrollToBottomBtn').on('click', function() {
                const mediaGrid = document.getElementById('mediaGrid');
                const modalContent = document.querySelector('.modal-content');
                mediaGrid?.scrollTo({
                    top: mediaGrid.scrollHeight,
                    behavior: 'smooth'
                });
                modalContent?.scrollTo({
                    top: modalContent.scrollHeight,
                    behavior: 'smooth'
                });
            });

            function updateScrollIndicator() {
                const mediaGrid = document.getElementById('mediaGrid');
                const scrollTopBtn = document.getElementById('scrollToTopBtn');
                const scrollBottomBtn = document.getElementById('scrollToBottomBtn');
                if (!mediaGrid || !scrollTopBtn || !scrollBottomBtn) return;
                const st = mediaGrid.scrollTop;
                const max = mediaGrid.scrollHeight - mediaGrid.clientHeight;
                st > 100 ? scrollTopBtn.classList.remove('opacity-50') : scrollTopBtn.classList.add('opacity-50');
                st < max - 100 ? scrollBottomBtn.classList.remove('opacity-50') : scrollBottomBtn.classList.add(
                    'opacity-50');
            }
            $(document).on('scroll', '#mediaGrid', updateScrollIndicator);
            // Replace deprecated DOMSubtreeModified with a MutationObserver on #mediaGrid
            const gridEl = document.getElementById('mediaGrid');
            if (gridEl) {
                const mo = new MutationObserver(() => setTimeout(updateScrollIndicator, 60));
                mo.observe(gridEl, {
                    childList: true,
                    subtree: false
                });
            }

            // ======== أدوات مساعدة ========
            function formatBytes(bytes) {
                if (!bytes) return '0 B';
                const k = 1024,
                    sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            }

            function debounce(fn, wait) {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), wait);
                };
            }

            function showToast(msg, type = 'info') {
                // Update aria-live region for assistive tech
                const live = document.getElementById('toastRegion');
                if (live) live.textContent = msg;
                const $n = $(
                    `<div class="fixed top-4 right-4 z-[10001] p-4 rounded-lg shadow-lg text-white max-w-sm whitespace-pre-line ${type==='success'?'bg-green-600': type==='error'?'bg-red-600':'bg-blue-600'}">${msg}</div>`
                );
                $('body').append($n);
                setTimeout(() => $n.fadeOut(200, () => $n.remove()), 2600);
            }

            function showMediaLoading() {
                $('#mediaLoading').removeClass('hidden');
                $('#mediaEmpty').addClass('hidden');
                $('#mediaGrid').empty();
            }

            function hideMediaLoading() {
                $('#mediaLoading').addClass('hidden');
            }

            function showMediaEmpty() {
                hideMediaLoading();
                $('#mediaEmpty').removeClass('hidden');
                $('#mediaGrid').empty();
            }

            function updateStats() {
                $('#totalImages, #imageCounter, #libraryCount').text(mediaItems.length);
                $('#selectedCount').text(selectedImages.length);
                $('#selectedInfo').text(selectedImages.length ? `${selectedImages.length} صورة محددة` :
                    'لم يتم التحديد');
                const confirmBtn = $('#confirmSelectionBtn');
                selectedImages.length === 0 ? confirmBtn.prop('disabled', true).addClass(
                        'opacity-50 cursor-not-allowed') :
                    confirmBtn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
            }
        });
    </script>
@endpush
