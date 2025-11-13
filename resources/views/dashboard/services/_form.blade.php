{{-- ====== حقول الخدمة الأساسية ====== --}}


@include('dashboard.partials.media-picker-advanced', [
    'fieldName' => 'icon',
    'value' => $service->icon ?? null,
])

{{-- الترتيب --}}
<div class="col-span-6">
    <div
        class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-800 dark:to-gray-700 p-6 rounded-xl border border-purple-200 dark:border-gray-600">
        <label class="flex items-center text-sm font-cairo-semibold text-gray-700 dark:text-gray-200 mb-4">
            <svg class="w-5 h-5 ml-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
            </svg>
            ترتيب الظهور
        </label>
        <input type="number" name="order" value="{{ old('order', $service->order ?? '') }}"
            class="w-full px-4 py-3 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 font-cairo-regular"
            placeholder="أدخل رقم الترتيب">
        @error('order')
            <span class="flex items-center text-red-500 text-sm mt-2 font-cairo-regular">
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ $message }}
            </span>
        @enderror
    </div>
</div>

{{-- الرابط --}}
<div class="col-span-6">
    <div
        class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-gray-800 dark:to-gray-700 p-6 rounded-xl border border-green-200 dark:border-gray-600">
        <label class="flex items-center text-sm font-cairo-semibold text-gray-700 dark:text-gray-200 mb-4">
            <svg class="w-5 h-5 ml-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                </path>
            </svg>
            رابط الخدمة
        </label>
        <input type="text" name="url" value="{{ old('url', $service->url ?? '') }}"
            class="w-full px-4 py-3 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 font-cairo-regular"
            placeholder="https://example.com">
        @error('url')
            <span class="flex items-center text-red-500 text-sm mt-2 font-cairo-regular">
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ $message }}
            </span>
        @enderror
    </div>
</div>

{{-- ====== الترجمات ====== --}}
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
            ترجمات الخدمة
        </h3>
        <p class="text-gray-600 dark:text-gray-300 font-cairo-regular">أدخل العنوان والوصف لكل لغة مدعومة</p>
    </div>

    <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
        <!-- تبويبات اللغات -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-0 space-x-2 rtl:space-x-reverse px-6 pt-6 overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600"
            role="tablist" id="languageTabs">
            @foreach ($languages as $index => $lang)
                <button type="button" onclick="switchLanguageTab('{{ $lang->code }}')"
                    onkeydown="handleTabKeydown(event, '{{ $lang->code }}')" id="lang-tab-{{ $lang->code }}"
                    role="tab" aria-controls="lang-panel-{{ $lang->code }}"
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
            @foreach ($languages as $lang)
                @php $translation = $serviceTranslations[$lang->code] ?? null; @endphp
                <div id="lang-panel-{{ $lang->code }}" role="tabpanel"
                    aria-labelledby="lang-tab-{{ $lang->code }}"
                    class="lang-panel {{ $loop->first ? 'block' : 'hidden' }} opacity-100 transform transition-all duration-300 ease-out">
                    <div class="space-y-6">
                        <div>
                            <label
                                class="flex items-center text-sm font-cairo-semibold text-gray-700 dark:text-gray-200 mb-2">
                                <svg class="w-4 h-4 ml-2 text-gray-500 dark:text-gray-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                    </path>
                                </svg>
                                عنوان الخدمة
                            </label>
                            <input type="text"
                                class="w-full px-4 py-3 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 font-cairo-regular"
                                placeholder="أدخل عنوان الخدمة"
                                name="serviceTranslations[{{ $lang->code }}][title]"
                                value="{{ old('serviceTranslations[' . $lang->code . '][title]', $translation['title'] ?? '') }}"
                                aria-describedby="title-help-{{ $lang->code }}"
                                @if ($lang->code === 'ar') required @endif>
                            <input type="hidden" name="serviceTranslations[{{ $lang->code }}][locale]"
                                value="{{ $lang->code }}">
                            @error("serviceTranslations.{$lang->code}.title")
                                <div class="flex items-center text-red-500 text-sm mt-2 animate-pulse font-cairo-regular"
                                    role="alert">
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label
                                class="flex items-center text-sm font-cairo-semibold text-gray-700 dark:text-gray-200 mb-2">
                                <svg class="w-4 h-4 ml-2 text-gray-500 dark:text-gray-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h7"></path>
                                </svg>
                                وصف الخدمة
                                <span
                                    class="text-gray-400 dark:text-gray-500 text-xs mr-2 font-cairo-light">(اختياري)</span>
                            </label>
                            <textarea
                                class="w-full px-4 py-3 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 font-cairo-regular resize-none"
                                rows="4" placeholder="أدخل وصف مفصل للخدمة (اختياري)"
                                name="serviceTranslations[{{ $lang->code }}][description]"
                                aria-describedby="description-help-{{ $lang->code }}">{{ old('serviceTranslations[' . $lang->code . '][description]', $translation['description'] ?? '') }}</textarea>
                            <p id="description-help-{{ $lang->code }}"
                                class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-cairo-light">
                                يمكنك ترك هذا الحقل فارغاً إذا كنت لا تريد إضافة وصف للخدمة
                            </p>
                            @error("serviceTranslations.{$lang->code}.description")
                                <div class="flex items-center text-red-500 text-sm mt-2 animate-pulse font-cairo-regular"
                                    role="alert">
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- أزرار الحفظ --}}
<div class="col-span-12 mt-8">
    <div
        class="bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-700 p-6 rounded-xl border-t border-gray-200 dark:border-gray-600">
        <div class="flex flex-col sm:flex-row items-center justify-end gap-4">
            <a href="{{ route('dashboard.services.index') }}"
                class="inline-flex items-center px-8 py-3 bg-gray-500  hover:from-gray-600 hover:to-gray-700 dark:from-gray-600 dark:to-gray-700 dark:hover:from-gray-700 dark:hover:to-gray-800 text-white font-cairo-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
                إلغاء
            </a>
            <button type="submit"
                class="inline-flex items-center px-8 py-3 btn btn-primary">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                حفظ الخدمة
            </button>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const tabIds = @json($languages->pluck('code'));

            function setTabState(tab, isActive) {
                const baseClasses = ['bg-gray-100', 'dark:bg-gray-700', 'text-gray-600', 'dark:text-gray-300',
                    'border-transparent', 'font-cairo-regular'
                ];
                const activeClasses = ['bg-white', 'dark:bg-gray-800', 'text-indigo-600', 'dark:text-indigo-400',
                    'border-b-2', 'border-indigo-500', 'dark:border-indigo-400', 'font-cairo-semibold'
                ];
                tab.classList.remove(...baseClasses, ...activeClasses);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                tab.setAttribute('tabindex', isActive ? '0' : '-1');
                if (isActive) {
                    tab.classList.add(...activeClasses);
                } else {
                    tab.classList.add(...baseClasses);
                    tab.querySelector('.lang-checkmark')?.remove();
                }
            }

            function switchLanguageTab(langCode) {
                document.querySelectorAll('.lang-tab-btn').forEach(tab => setTabState(tab, false));
                document.querySelectorAll('.lang-panel').forEach(panel => {
                    panel.classList.add('hidden');
                    panel.classList.remove('block');
                });

                const activeTab = document.getElementById(`lang-tab-${langCode}`);
                if (!activeTab) return;
                setTabState(activeTab, true);

                const iconContainer = activeTab.querySelector('.flex');
                if (iconContainer && !iconContainer.querySelector('.lang-checkmark')) {
                    iconContainer.insertAdjacentHTML('beforeend', `
                        <svg class="lang-checkmark w-4 h-4 text-indigo-500 dark:text-indigo-400 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>`);
                }

                const panel = document.getElementById(`lang-panel-${langCode}`);
                if (panel) {
                    panel.classList.remove('hidden');
                    panel.classList.add('block');
                    setTimeout(() => {
                        panel.querySelector('input[type="text"]')?.focus();
                    }, 60);
                }

                localStorage.setItem('activeLangTab', langCode);
            }

            function handleTabKeydown(event, langCode) {
                const tabs = Array.from(document.querySelectorAll('.lang-tab-btn'));
                const idx = tabs.findIndex(tab => tab.id === `lang-tab-${langCode}`);
                if (idx < 0) return;

                let nextIndex = null;
                switch (event.key) {
                    case 'ArrowLeft':
                        nextIndex = (idx - 1 + tabs.length) % tabs.length;
                        break;
                    case 'ArrowRight':
                        nextIndex = (idx + 1) % tabs.length;
                        break;
                    case 'Home':
                        nextIndex = 0;
                        break;
                    case 'End':
                        nextIndex = tabs.length - 1;
                        break;
                }

                if (nextIndex != null) {
                    event.preventDefault();
                    const targetTab = tabs[nextIndex];
                    const targetCode = targetTab.id.replace('lang-tab-', '');
                    switchLanguageTab(targetCode);
                    targetTab.focus();
                }
            }

            window.switchLanguageTab = switchLanguageTab;
            window.handleTabKeydown = handleTabKeydown;

            document.addEventListener('DOMContentLoaded', () => {
                const saved = localStorage.getItem('activeLangTab');
                const initial = saved && tabIds.includes(saved) ? saved : tabIds[0];
                if (initial) {
                    switchLanguageTab(initial);
                }
            });
        })();
    </script>
@endpush
