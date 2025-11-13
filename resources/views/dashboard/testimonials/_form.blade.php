@php
    $testimonial = $testimonial ?? $feedback ?? null;
    $testimonialTranslations = $testimonialTranslations ?? $feedbackTranslations ?? [];
@endphp

{{-- Testimonial Image --}}
@include('dashboard.partials.media-picker-advanced', [
    'fieldName' => 'image_path',
    'value' => $testimonial?->image ?? null,
    'label' => 'صورة العميل',
    'buttonText' => 'اختر أو حمّل صورة العميل من مكتبة الوسائط',
    'supportedFormatsText' => 'الصيغ المدعومة: JPG, PNG, SVG',
])

{{-- Display Order --}}
<div class="col-span-6">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 h-full">
        <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
            <svg class="w-5 h-5 ml-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4">
                </path>
            </svg>
            ترتيب الظهور
        </label>
        <input type="number" name="order" min="1"
            value="{{ old('order', $testimonial?->order ?? 1) }}" class="form-control"
            placeholder="مثال: 1 للظهور أولاً">
        <p class="text-xs text-gray-500 mt-2">استخدم أرقامًا متسلسلة للتحكم في ترتيب بطاقات الشهادات.</p>
        @error('order')
            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>


{{-- Stars --}}
<div class="col-span-6">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 h-full">
        <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
            <svg class="w-4 h-4 ml-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.174c.969 0 1.371 1.24.588 1.81l-3.378 2.455a1 1 0 00-.364 1.118l1.286 3.966c.3.922-.755 1.688-1.54 1.118l-3.379-2.454a1 1 0 00-1.175 0l-3.379 2.454c-.784.57-1.838-.196-1.539-1.118l1.285-3.966a1 1 0 00-.364-1.118L2.96 9.394c-.783-.57-.38-1.81.588-1.81h4.174a1 1 0 00.95-.69l1.286-3.967z">
                </path>
            </svg>
            عدد النجوم
        </label>
        <input type="number" name="star" min="1" max="5"
            value="{{ old('star', $testimonial?->star ?? '') }}" class="form-control"
            placeholder="اختر قيمة من 1 إلى 5">
        <p class="text-xs text-gray-500 mt-2">يستخدم هذا الحقل لعرض معدل التقييم (الحد الأدنى 1 والحد الأقصى 5).</p>
        @error('star')
            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>
@php
    $languageErrorMap = [];
    $firstErrorLang = null;

    foreach ($languages as $language) {
        $code = $language->code;
        $languageErrorMap[$code] =
            $errors->has("testimonialTranslations.$code.name") ||
            $errors->has("testimonialTranslations.$code.feedback") ||
            $errors->has("testimonialTranslations.$code.major") ||
            $errors->has("testimonialTranslations.$code.locale");

        if ($languageErrorMap[$code] && $firstErrorLang === null) {
            $firstErrorLang = $code;
        }
    }

    $initialTabCode = $firstErrorLang ?? ($languages->first()->code ?? null);
@endphp

{{-- Translations --}}
<div class="col-span-12 mt-8">
    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 p-6 rounded-2xl border border-indigo-200 shadow-sm mb-6">
        <h3 class="flex items-center text-xl font-semibold text-gray-800 mb-2">
            <svg class="w-6 h-6 ml-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 5h12M9 3v2m3.5 13.5a18.5 18.5 0 01-6-7.5m6 7.5h7M11 21l4.5-10L20 21M12.75 5C11.8 10.8 8.1 15.6 3 18.1">
                </path>
            </svg>
            الترجمات
        </h3>
        <p class="text-gray-600 text-sm">قم بتعبئة بيانات التقييم لكل لغة متاحة لضمان تجربة متكاملة.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="flex border-b border-gray-200 mb-0 space-x-2 rtl:space-x-reverse px-6 pt-6 overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300"
            role="tablist" id="languageTabs">
            @foreach ($languages as $index => $lang)
                @php
                    $code = $lang->code;
                    $hasLanguageError = $languageErrorMap[$code] ?? false;
                    $isDefaultActive = $firstErrorLang ? $firstErrorLang === $code : $loop->first;
                    $tabClasses = 'lang-tab lang-tab-btn px-4 py-3 rounded-t-lg transition-all duration-200 focus:outline-none whitespace-nowrap hover:bg-gray-50 ';

                    if ($isDefaultActive) {
                        $tabClasses .= $hasLanguageError
                            ? 'bg-red-50 text-red-700 border-b-2 border-red-500 font-semibold focus:ring-red-400 '
                            : 'bg-white text-indigo-600 border-b-2 border-indigo-500 font-semibold focus:ring-indigo-400 ';
                    } else {
                        $tabClasses .= $hasLanguageError
                            ? 'bg-red-50 text-red-700 border border-red-200 focus:ring-red-400 '
                            : 'bg-gray-100 text-gray-600 border-transparent focus:ring-indigo-400 ';
                    }
                @endphp
                <button type="button" onclick="switchLanguageTab('{{ $code }}')"
                    onkeydown="handleTabKeydown(event, '{{ $code }}')" id="lang-tab-{{ $code }}"
                    role="tab" aria-controls="lang-panel-{{ $code }}"
                    aria-selected="{{ $isDefaultActive ? 'true' : 'false' }}" tabindex="{{ $isDefaultActive ? '0' : '-1' }}"
                    aria-invalid="{{ $hasLanguageError ? 'true' : 'false' }}" data-lang-code="{{ $code }}"
                    data-has-error="{{ $hasLanguageError ? 'true' : 'false' }}" class="{{ trim($tabClasses) }}">
                    <div class="lang-tab-label flex items-center space-x-2 rtl:space-x-reverse">
                        <div
                            class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-bold">
                            {{ strtoupper(substr($code, 0, 2)) }}
                        </div>
                        <span>{{ $lang->native }}</span>
                        @if ($hasLanguageError)
                            <span class="lang-error-indicator inline-flex items-center gap-1 text-xs font-semibold text-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z"></path>
                                </svg>
                                <span>تحقق</span>
                            </span>
                        @endif
                        @if ($isDefaultActive)
                            <svg class="lang-checkmark w-4 h-4 {{ $hasLanguageError ? 'text-red-500' : 'text-indigo-500' }}" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>

        <div class="p-6 bg-gray-50">
            @foreach ($languages as $lang)
                @php
                    $translation = $testimonialTranslations[$lang->code] ?? null;
                    $panelCode = $lang->code;
                    $panelHasError = $languageErrorMap[$panelCode] ?? false;
                    $shouldShowPanel = $firstErrorLang ? $firstErrorLang === $panelCode : $loop->first;
                @endphp
                <div id="lang-panel-{{ $panelCode }}" role="tabpanel"
                    aria-labelledby="lang-tab-{{ $panelCode }}"
                    class="lang-panel {{ $shouldShowPanel ? 'block' : 'hidden' }} transition-all duration-300 ease-out"
                    data-lang-panel="{{ $panelCode }}" data-has-error="{{ $panelHasError ? 'true' : 'false' }}">
                    <div class="space-y-6">
                        <div>
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                <svg class="w-4 h-4 ml-2 text-indigo-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 11c0 4.418-1.79 8-4 8s-4-3.582-4-8 1.79-8 4-8 4 3.582 4 8zm0 0c0 4.418 1.79 8 4 8s4-3.582 4-8-1.79-8-4-8-4 3.582-4 8z">
                                    </path>
                                </svg>
                                اسم صاحب التقييم
                            </label>
                            <input type="text" name="testimonialTranslations[{{ $lang->code }}][name]"
                                value="{{ old('testimonialTranslations.' . $lang->code . '.name', $translation['name'] ?? '') }}"
                                class="form-control" placeholder="أدخل اسم صاحب التقييم">
                            @error('testimonialTranslations.' . $lang->code . '.name')
                                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                <svg class="w-4 h-4 ml-2 text-indigo-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 8h10M7 12h8m-5 4h5M5 6h-.01M5 10h-.01M5 14h-.01M5 18h-.01"></path>
                                </svg>
                                نص التقييم
                            </label>
                            <textarea rows="4" name="testimonialTranslations[{{ $lang->code }}][feedback]" class="form-control min-h-[120px]"
                                placeholder="اكتب التقييم">{{ old('testimonialTranslations.' . $lang->code . '.feedback', $translation['feedback'] ?? '') }}</textarea>
                            @error('testimonialTranslations.' . $lang->code . '.feedback')
                                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                <svg class="w-4 h-4 ml-2 text-indigo-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.69 6.479A11.952 11.952 0 0112 21.75a11.952 11.952 0 01-6.85-4.693 12.086 12.086 0 01.69-6.479L12 14z">
                                    </path>
                                </svg>
                                المسمى الوظيفي أو مجال العمل
                            </label>
                            <input type="text" name="testimonialTranslations[{{ $lang->code }}][major]"
                                value="{{ old('testimonialTranslations.' . $lang->code . '.major', $translation['major'] ?? '') }}"
                                class="form-control" placeholder="مثال: مدير التسويق">
                            @error('testimonialTranslations.' . $lang->code . '.major')
                                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <input type="hidden" name="testimonialTranslations[{{ $lang->code }}][locale]"
                            value="{{ $lang->code }}">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
{{-- Actions --}}
<div class="col-span-12 mt-8">
    <div
        class="bg-gradient-to-r from-gray-50 to-white p-6 rounded-2xl border border-gray-200 flex flex-col sm:flex-row items-center justify-end gap-4">
        <a href="{{ route('dashboard.testimonials.index') }}"
            class="inline-flex items-center px-8 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-gray-300">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            إلغاء
        </a>
        <button type="submit"
            class="inline-flex items-center px-8 py-3 btn btn-primary">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            حفظ التقييم
        </button>
    </div>
</div>


@push('scripts')
    <script>
        (() => {
            const tabIds = @json($languages->pluck('code'));
            const firstErrorLang = @json($firstErrorLang ?? '');
            const defaultLang = @json($languages->first()->code ?? '');
            const storageKey = 'testimonialActiveLangTab';

            function applyTabClasses(tab, isActive) {
                const hasError = tab.dataset.hasError === 'true';
                const classesToRemove = [
                    'bg-white', 'text-indigo-600', 'border-b-2', 'border-indigo-500', 'font-semibold',
                    'bg-gray-100', 'text-gray-600', 'border-transparent',
                    'bg-red-50', 'text-red-700', 'border-red-500', 'border-red-200', 'border',
                    'focus:ring-indigo-400', 'focus:ring-red-400'
                ];
                tab.classList.remove(...classesToRemove);

                if (isActive) {
                    if (hasError) {
                        tab.classList.add('bg-red-50', 'text-red-700', 'border-b-2', 'border-red-500', 'font-semibold', 'focus:ring-red-400');
                    } else {
                        tab.classList.add('bg-white', 'text-indigo-600', 'border-b-2', 'border-indigo-500', 'font-semibold', 'focus:ring-indigo-400');
                    }
                } else {
                    if (hasError) {
                        tab.classList.add('bg-red-50', 'text-red-700', 'border', 'border-red-200', 'focus:ring-red-400');
                    } else {
                        tab.classList.add('bg-gray-100', 'text-gray-600', 'border-transparent', 'focus:ring-indigo-400');
                    }
                }
            }

            function updatePanelVisibility(activeCode) {
                document.querySelectorAll('.lang-panel').forEach(panel => {
                    panel.classList.add('hidden');
                });
                const panel = document.getElementById(`lang-panel-${activeCode}`);
                if (panel) {
                    panel.classList.remove('hidden');
                }
            }

            function switchLanguageTab(langCode) {
                const tabs = document.querySelectorAll('.lang-tab-btn');
                tabs.forEach(tab => {
                    const isActive = tab.id === `lang-tab-${langCode}`;
                    applyTabClasses(tab, isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    tab.setAttribute('tabindex', isActive ? '0' : '-1');
                    tab.querySelector('.lang-checkmark')?.remove();
                });

                const activeTab = document.getElementById(`lang-tab-${langCode}`);
                if (activeTab) {
                    const label = activeTab.querySelector('.lang-tab-label');
                    if (label && !label.querySelector('.lang-checkmark')) {
                        const checkIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        checkIcon.setAttribute('class', `lang-checkmark w-4 h-4 ${activeTab.dataset.hasError === 'true' ? 'text-red-500' : 'text-indigo-500'}`);
                        checkIcon.setAttribute('fill', 'none');
                        checkIcon.setAttribute('stroke', 'currentColor');
                        checkIcon.setAttribute('viewBox', '0 0 24 24');
                        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        path.setAttribute('stroke-linecap', 'round');
                        path.setAttribute('stroke-linejoin', 'round');
                        path.setAttribute('stroke-width', '2');
                        path.setAttribute('d', 'M5 13l4 4L19 7');
                        checkIcon.appendChild(path);
                        label.appendChild(checkIcon);
                    }
                }

                updatePanelVisibility(langCode);

                if (tabIds.includes(langCode)) {
                    localStorage.setItem(storageKey, langCode);
                }
            }

            function handleTabKeydown(event, langCode) {
                const tabs = Array.from(document.querySelectorAll('.lang-tab-btn'));
                const currentIndex = tabs.findIndex(tab => tab.id === `lang-tab-${langCode}`);
                if (currentIndex === -1) {
                    return;
                }

                let nextIndex = null;
                switch (event.key) {
                    case 'ArrowLeft':
                        nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
                        break;
                    case 'ArrowRight':
                        nextIndex = (currentIndex + 1) % tabs.length;
                        break;
                    case 'Home':
                        nextIndex = 0;
                        break;
                    case 'End':
                        nextIndex = tabs.length - 1;
                        break;
                }

                if (nextIndex !== null) {
                    event.preventDefault();
                    const nextCode = tabs[nextIndex].id.replace('lang-tab-', '');
                    switchLanguageTab(nextCode);
                    tabs[nextIndex].focus();
                }
            }

            window.switchLanguageTab = switchLanguageTab;
            window.handleTabKeydown = handleTabKeydown;

            document.addEventListener('DOMContentLoaded', () => {
                const saved = localStorage.getItem(storageKey);
                const initial = firstErrorLang || (saved && tabIds.includes(saved) ? saved : (defaultLang || tabIds[0]));
                if (initial) {
                    switchLanguageTab(initial);
                }
            });
        })();
    </script>
@endpush




