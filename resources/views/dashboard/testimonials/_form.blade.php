@php
    $testimonial             = $testimonial ?? null;
    $testimonialTranslations = $testimonialTranslations ?? [];

    $featuredImageId   = old('featured_image_id', $testimonial?->image_id ?? null);
    $featuredImageUrls = [];

    if ($featuredImageId && $testimonial?->image) {
        $featuredImageUrls[] = $testimonial->image_url ?? $testimonial->image->url ?? '';
    }
@endphp

{{-- ══════════════════════════════════════════════════════════════════════
     SECTION ١ — الصورة الرئيسية
═══════════════════════════════════════════════════════════════════════ --}}
<div class="card mb-4">
    <div class="card-header flex items-center gap-3">
        <span class="badge-number">١</span>
        <h5 class="mb-0">{{ t('dashboard.Testimonial_Featured_Image', 'Profile Image') }}</h5>
    </div>
    <div class="card-body">
        <x-dashboard.media-picker
            id="featured_image_id"
            name="featured_image_id"
            label="{{ t('dashboard.Testimonial_Featured_Image', 'Profile Image') }}"
            :value="$featuredImageId"
            :preview-urls="$featuredImageUrls"
            button-text="{{ t('dashboard.Testimonial_Choose_Image', 'Choose from Media Library') }}"
        />
        @error('featured_image_id')
            <span class="text-danger text-sm mt-1 block">{{ $message }}</span>
        @enderror
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     SECTION ٢ — بيانات الشهادة
═══════════════════════════════════════════════════════════════════════ --}}
<div class="card mb-4">
    <div class="card-header flex items-center gap-3">
        <span class="badge-number">٢</span>
        <h5 class="mb-0">{{ t('dashboard.Testimonial_Details', 'Testimonial Details') }}</h5>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-12 gap-4">

            {{-- ترتيب الظهور --}}
            <div class="col-span-12 sm:col-span-4">
                <label class="form-label" for="testimonial_order">
                    {{ t('dashboard.Testimonial_Display_Order', 'Display Order') }}
                    <span class="text-red-500">*</span>
                </label>
                <input type="number" id="testimonial_order" name="order" min="1"
                    class="form-control @error('order') is-invalid @enderror"
                    value="{{ old('order', $testimonial?->order ?? 1) }}">
                @error('order')
                    <span class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>

            {{-- عدد النجوم --}}
            <div class="col-span-12 sm:col-span-4">
                <label class="form-label" for="testimonial_star">
                    {{ t('dashboard.Testimonial_Stars_Count', 'Stars (1–5)') }}
                </label>
                <input type="number" id="testimonial_star" name="star" min="1" max="5"
                    class="form-control @error('star') is-invalid @enderror"
                    value="{{ old('star', $testimonial?->star ?? '') }}"
                    placeholder="1 – 5">
                @error('star')
                    <span class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>

            {{-- حالة النشر --}}
            <div class="col-span-12 sm:col-span-4">
                <label class="form-label">{{ t('dashboard.Testimonial_Approval_Status', 'Publication Status') }}</label>
                <div class="flex flex-col gap-2 mt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="is_approved" value="1" class="form-radio"
                            {{ old('is_approved', (string)($testimonial?->is_approved ?? 1)) === '1' ? 'checked' : '' }}>
                        <span class="text-sm text-emerald-700 font-medium">
                            {{ t('dashboard.Testimonial_Approved_Label', 'Published — visible on site') }}
                        </span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="is_approved" value="0" class="form-radio"
                            {{ old('is_approved', (string)($testimonial?->is_approved ?? 1)) === '0' ? 'checked' : '' }}>
                        <span class="text-sm text-amber-700 font-medium">
                            {{ t('dashboard.Testimonial_Pending_Label', 'Pending — awaiting review') }}
                        </span>
                    </label>
                </div>
                @error('is_approved')
                    <span class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     SECTION ٣ — ترجمات الشهادة
═══════════════════════════════════════════════════════════════════════ --}}
@php
    $languageErrorMap = [];
    $firstErrorLang   = null;

    foreach ($languages as $language) {
        $code = $language->code;
        $languageErrorMap[$code] =
            $errors->has("testimonialTranslations.$code.name") ||
            $errors->has("testimonialTranslations.$code.text") ||
            $errors->has("testimonialTranslations.$code.major") ||
            $errors->has("testimonialTranslations.$code.locale");

        if ($languageErrorMap[$code] && $firstErrorLang === null) {
            $firstErrorLang = $code;
        }
    }

    $initialTabCode = $firstErrorLang ?? ($languages->first()->code ?? null);
@endphp

<div class="card mb-4">
    <div class="card-header flex items-center gap-3">
        <span class="badge-number">٣</span>
        <h5 class="mb-0">{{ t('dashboard.Testimonial_Translations', 'Testimonial Translations') }}</h5>
    </div>
    <div class="card-body p-0">

        {{-- @error for the whole translations block --}}
        @error('testimonialTranslations')
            <div class="alert alert-danger m-4">{{ $message }}</div>
        @enderror

        {{-- تبويبات اللغات --}}
        <div class="flex flex-wrap gap-1 border-b border-gray-200 px-4 pt-4 overflow-x-auto"
             role="tablist" id="languageTabs">
            @foreach ($languages as $lang)
                @php
                    $code         = $lang->code;
                    $hasError     = $languageErrorMap[$code] ?? false;
                    $isActive     = $firstErrorLang ? $firstErrorLang === $code : $loop->first;
                @endphp
                <button type="button"
                        onclick="switchLanguageTab('{{ $code }}')"
                        onkeydown="handleTabKeydown(event, '{{ $code }}')"
                        id="lang-tab-{{ $code }}"
                        role="tab"
                        aria-controls="lang-panel-{{ $code }}"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        tabindex="{{ $isActive ? '0' : '-1' }}"
                        data-lang-code="{{ $code }}"
                        data-has-error="{{ $hasError ? 'true' : 'false' }}"
                        class="lang-tab-btn flex items-center gap-2 px-4 py-2.5 text-sm rounded-t-lg transition-all duration-200 focus:outline-none focus:ring-2 whitespace-nowrap
                               {{ $isActive
                                   ? ($hasError ? 'text-red-600 border-b-2 border-red-500 font-semibold bg-red-50 focus:ring-red-300'
                                                : 'text-primary border-b-2 border-primary font-semibold bg-white focus:ring-primary/30')
                                   : ($hasError ? 'text-red-500 border-b-2 border-transparent bg-red-50 focus:ring-red-300 hover:text-red-600'
                                                : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 focus:ring-primary/30') }}">
                    <span class="w-6 h-6 rounded-full {{ $hasError ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600' }} inline-flex items-center justify-center text-xs font-bold">
                        {{ strtoupper(substr($code, 0, 2)) }}
                    </span>
                    {{ $lang->native }}
                    @if ($hasError)
                        <i class="ti ti-alert-circle text-sm text-red-500"></i>
                    @elseif ($lang->is_active)
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Panels --}}
        <div class="p-5 bg-gray-50/50">
            @foreach ($languages as $lang)
                @php $translation = $testimonialTranslations[$lang->code] ?? null; @endphp
                <div id="lang-panel-{{ $lang->code }}"
                     role="tabpanel"
                     aria-labelledby="lang-tab-{{ $lang->code }}"
                     data-lang-panel="{{ $lang->code }}"
                     data-has-error="{{ ($languageErrorMap[$lang->code] ?? false) ? 'true' : 'false' }}"
                     class="lang-panel {{ ($firstErrorLang ? $firstErrorLang === $lang->code : $loop->first) ? 'block' : 'hidden' }} transition-all duration-200">

                    <div class="grid grid-cols-12 gap-4">

                        {{-- اسم صاحب التقييم --}}
                        <div class="col-span-12 sm:col-span-6">
                            <label class="form-label" for="name_{{ $lang->code }}">
                                {{ t('dashboard.Testimonial_Author_Name', 'Reviewer Name') }}
                            </label>
                            <input type="text"
                                   id="name_{{ $lang->code }}"
                                   name="testimonialTranslations[{{ $lang->code }}][name]"
                                   class="form-control @error('testimonialTranslations.' . $lang->code . '.name') is-invalid @enderror"
                                   value="{{ old('testimonialTranslations.' . $lang->code . '.name', $translation['name'] ?? '') }}"
                                   placeholder="{{ t('dashboard.Testimonial_Author_Name', 'Reviewer Name') }}">
                            @error('testimonialTranslations.' . $lang->code . '.name')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- المسمى الوظيفي --}}
                        <div class="col-span-12 sm:col-span-6">
                            <label class="form-label" for="major_{{ $lang->code }}">
                                {{ t('dashboard.Testimonial_Major', 'Job Title / Field') }}
                            </label>
                            <input type="text"
                                   id="major_{{ $lang->code }}"
                                   name="testimonialTranslations[{{ $lang->code }}][major]"
                                   class="form-control @error('testimonialTranslations.' . $lang->code . '.major') is-invalid @enderror"
                                   value="{{ old('testimonialTranslations.' . $lang->code . '.major', $translation['major'] ?? '') }}"
                                   placeholder="{{ t('dashboard.Testimonial_Major_Placeholder', 'e.g. Marketing Manager') }}">
                            @error('testimonialTranslations.' . $lang->code . '.major')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- نص التقييم --}}
                        <div class="col-span-12">
                            <label class="form-label" for="text_{{ $lang->code }}">
                                {{ t('dashboard.Testimonial_Text', 'Testimonial Text') }}
                            </label>
                            <textarea id="text_{{ $lang->code }}"
                                      name="testimonialTranslations[{{ $lang->code }}][text]"
                                      rows="4"
                                      class="form-control @error('testimonialTranslations.' . $lang->code . '.text') is-invalid @enderror"
                                      placeholder="{{ t('dashboard.Testimonial_Text', 'Testimonial Text') }}">{{ old('testimonialTranslations.' . $lang->code . '.text', $translation['text'] ?? '') }}</textarea>
                            @error('testimonialTranslations.' . $lang->code . '.text')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    <input type="hidden"
                           name="testimonialTranslations[{{ $lang->code }}][locale]"
                           value="{{ $lang->code }}">

                </div>
            @endforeach
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     أزرار الحفظ والإلغاء
═══════════════════════════════════════════════════════════════════════ --}}
<div class="flex items-center justify-end gap-3 mb-6">
    <a href="{{ route('dashboard.testimonials.index') }}" class="btn btn-light">
        {{ t('dashboard.Cancel', 'Cancel') }}
    </a>
    <button type="submit" class="btn btn-primary flex items-center gap-2">
        <i class="ti ti-device-floppy text-base"></i>
        {{ isset($testimonial->id)
            ? t('dashboard.Update_Testimonial', 'Update Testimonial')
            : t('dashboard.Create_Testimonial', 'Save Testimonial') }}
    </button>
</div>


@push('scripts')
    <script>
        (() => {
            const tabIds        = @json($languages->pluck('code'));
            const firstErrorLang = @json($firstErrorLang ?? '');
            const defaultLang   = @json($languages->first()->code ?? '');
            const storageKey    = 'testimonialActiveLangTab';

            function applyTabClasses(tab, isActive) {
                const hasError = tab.dataset.hasError === 'true';

                const all = [
                    'text-primary', 'border-primary', 'font-semibold', 'bg-white',
                    'text-red-600', 'border-red-500', 'bg-red-50',
                    'text-gray-500', 'border-transparent', 'text-red-500',
                    'focus:ring-primary/30', 'focus:ring-red-300',
                ];
                tab.classList.remove(...all);

                if (isActive) {
                    if (hasError) {
                        tab.classList.add('text-red-600', 'border-b-2', 'border-red-500', 'font-semibold', 'bg-red-50', 'focus:ring-red-300');
                    } else {
                        tab.classList.add('text-primary', 'border-b-2', 'border-primary', 'font-semibold', 'bg-white', 'focus:ring-primary/30');
                    }
                    tab.setAttribute('aria-selected', 'true');
                    tab.setAttribute('tabindex', '0');
                } else {
                    if (hasError) {
                        tab.classList.add('text-red-500', 'border-b-2', 'border-transparent', 'bg-red-50', 'focus:ring-red-300');
                    } else {
                        tab.classList.add('text-gray-500', 'border-b-2', 'border-transparent', 'focus:ring-primary/30');
                    }
                    tab.setAttribute('aria-selected', 'false');
                    tab.setAttribute('tabindex', '-1');
                }
            }

            window.switchLanguageTab = function (langCode) {
                document.querySelectorAll('.lang-tab-btn').forEach(function (tab) {
                    applyTabClasses(tab, tab.id === 'lang-tab-' + langCode);
                });

                document.querySelectorAll('.lang-panel').forEach(function (p) {
                    p.classList.add('hidden');
                    p.classList.remove('block');
                });

                const panel = document.getElementById('lang-panel-' + langCode);
                if (panel) {
                    panel.classList.remove('hidden');
                    panel.classList.add('block');
                }

                if (tabIds.includes(langCode)) {
                    localStorage.setItem(storageKey, langCode);
                }
            };

            window.handleTabKeydown = function (event, langCode) {
                const tabs = Array.from(document.querySelectorAll('.lang-tab-btn'));
                const idx  = tabs.findIndex(function (t) { return t.id === 'lang-tab-' + langCode; });
                if (idx < 0) return;

                let next = null;
                if (event.key === 'ArrowLeft')  { event.preventDefault(); next = (idx - 1 + tabs.length) % tabs.length; }
                if (event.key === 'ArrowRight') { event.preventDefault(); next = (idx + 1) % tabs.length; }
                if (event.key === 'Home')       { event.preventDefault(); next = 0; }
                if (event.key === 'End')        { event.preventDefault(); next = tabs.length - 1; }

                if (next !== null) {
                    const code = tabs[next].id.replace('lang-tab-', '');
                    window.switchLanguageTab(code);
                    tabs[next].focus();
                }
            };

            document.addEventListener('DOMContentLoaded', function () {
                const saved   = localStorage.getItem(storageKey);
                const initial = firstErrorLang
                    || (saved && tabIds.includes(saved) ? saved : (defaultLang || tabIds[0]));
                if (initial) window.switchLanguageTab(initial);
            });
        })();
    </script>
@endpush
