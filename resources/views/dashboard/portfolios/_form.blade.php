@push('styles')
    <style>
        ul[id^="type_suggestions_"] {
            position: absolute;
            border-radius: 6px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
            overflow-y: auto;
            max-height: 200px;
            min-width: 200px;
            background: #fff;
            border: 1px solid #e5e7eb;
            z-index: 1050;
            top: calc(100% + 4px);
            display: none;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        ul[id^="type_suggestions_"] li {
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
            line-height: 1.5;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.15s ease;
        }

        ul[id^="type_suggestions_"] li:last-child {
            border-bottom: none;
        }

        ul[id^="type_suggestions_"] li:hover,
        ul[id^="type_suggestions_"] li.highlighted {
            background-color: #f3f4f6;
            font-weight: 500;
        }
    </style>
@endpush

{{-- ══════════════════════════════════════════════════════════════════════
     SECTION ١ — الصور
═══════════════════════════════════════════════════════════════════════ --}}
<div class="card mb-4">
    <div class="card-header flex items-center gap-3">
        <span class="badge-number">١</span>
        <h5 class="mb-0">{{ t('dashboard.Portfolio_Images_Section', 'Images') }}</h5>
    </div>
    <div class="card-body space-y-4">

        {{-- الصورة الافتراضية --}}
        @php
            $rawDefaultImage = old('default_image', $portfolio->default_image ?? null);
            $defaultImageId = null;
            $defaultImagePreviewUrls = [];

            if (is_numeric($rawDefaultImage)) {
                $defaultImageId = (int) $rawDefaultImage;
                $media = \App\Models\Media::find($defaultImageId);
                if ($media && $media->file_path) {
                    $defaultImagePreviewUrls = [asset('storage/' . $media->file_path)];
                }
            } elseif (is_string($rawDefaultImage) && !empty($rawDefaultImage)) {
                $defaultImagePreviewUrls = [asset('storage/' . $rawDefaultImage)];
                $defaultImageId = $rawDefaultImage;
            }
        @endphp
        <x-dashboard.media-picker
            id="default_image_picker"
            name="default_image"
            label="{{ t('dashboard.Portfolio_Default_Image', 'Default Image') }}"
            :value="$defaultImageId"
            :previewUrls="$defaultImagePreviewUrls"
            buttonText="{{ t('dashboard.Portfolio_Choose_Image', 'Choose from Media Library') }}"
        />

        {{-- الصور المتعددة --}}
        @php
            $rawImages = old('images', $portfolio->images ?? null);
            $imagesArray = [];
            $imagesPreviewUrls = [];

            if ($rawImages) {
                if (is_string($rawImages)) {
                    $decoded = json_decode($rawImages, true);
                    $imagesArray = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
                } elseif (is_array($rawImages)) {
                    $imagesArray = $rawImages;
                }

                if (!empty($imagesArray) && is_numeric($imagesArray[0] ?? null)) {
                    $mediaRecords = \App\Models\Media::whereIn('id', $imagesArray)->get();
                    foreach ($mediaRecords as $media) {
                        if ($media->file_path) {
                            $imagesPreviewUrls[] = asset('storage/' . $media->file_path);
                        }
                    }
                } else {
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
            label="{{ t('dashboard.Portfolio_Images', 'Gallery Images') }}"
            multiple="true"
            :value="implode(',', array_filter($imagesArray))"
            :previewUrls="$imagesPreviewUrls"
            buttonText="{{ t('dashboard.Portfolio_Choose_Images', 'Choose Images from Media Library') }}"
        />

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     SECTION ٢ — بيانات المشروع
═══════════════════════════════════════════════════════════════════════ --}}
<div class="card mb-4">
    <div class="card-header flex items-center gap-3">
        <span class="badge-number">٢</span>
        <h5 class="mb-0">{{ t('dashboard.Portfolio_Project_Info', 'Project Details') }}</h5>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-12 gap-4">

            {{-- ترتيب الظهور --}}
            <div class="col-span-12 sm:col-span-6">
                <label class="form-label" for="portfolio_order">
                    {{ t('dashboard.Portfolio_Display_Order', 'Display Order') }}
                    <span class="text-red-500">*</span>
                </label>
                <input type="number" id="portfolio_order" name="order" min="0"
                    class="form-control @error('order') is-invalid @enderror"
                    value="{{ old('order', $portfolio->order ?? 0) }}">
                @error('order')
                    <span class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>

            {{-- تاريخ التسليم --}}
            <div class="col-span-12 sm:col-span-6">
                <label class="form-label" for="portfolio_delivery_date">
                    {{ t('dashboard.Portfolio_Delivery_Date', 'Delivery Date') }}
                    <span class="text-red-500">*</span>
                </label>
                <input type="date" id="portfolio_delivery_date" name="delivery_date"
                    class="form-control @error('delivery_date') is-invalid @enderror"
                    value="{{ old('delivery_date', isset($portfolio->delivery_date) ? \Carbon\Carbon::parse($portfolio->delivery_date)->format('Y-m-d') : '') }}">
                @error('delivery_date')
                    <span class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>

            {{-- مدة التنفيذ --}}
            <div class="col-span-12 sm:col-span-6">
                <label class="form-label" for="portfolio_impl_days">
                    {{ t('dashboard.Portfolio_Implementation_Days', 'Implementation Duration (days)') }}
                </label>
                <input type="number" id="portfolio_impl_days" name="implementation_period_days" min="0"
                    class="form-control @error('implementation_period_days') is-invalid @enderror"
                    value="{{ old('implementation_period_days', $portfolio->implementation_period_days ?? '') }}">
                @error('implementation_period_days')
                    <span class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>

            {{-- اسم العميل --}}
            <div class="col-span-12 sm:col-span-6">
                <label class="form-label" for="portfolio_client">
                    {{ t('dashboard.Portfolio_Client_Name', 'Client Name') }}
                </label>
                <input type="text" id="portfolio_client" name="client"
                    class="form-control @error('client') is-invalid @enderror"
                    value="{{ old('client', $portfolio->client ?? '') }}">
                @error('client')
                    <span class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     SECTION ٣ — ترجمات المعرض
═══════════════════════════════════════════════════════════════════════ --}}
<div class="card mb-4">
    <div class="card-header flex items-center gap-3">
        <span class="badge-number">٣</span>
        <h5 class="mb-0">{{ t('dashboard.Portfolio_Translations', 'Portfolio Translations') }}</h5>
    </div>
    <div class="card-body p-0">

        {{-- تبويبات اللغات --}}
        <div class="flex flex-wrap gap-1 border-b border-gray-200 px-4 pt-4 overflow-x-auto"
             role="tablist" id="portfolioLanguageTabs">
            @foreach ($languages as $index => $lang)
                <button type="button"
                        onclick="portfolioSwitchLanguageTab('{{ $lang->code }}')"
                        onkeydown="portfolioHandleTabKeydown(event, '{{ $lang->code }}')"
                        id="lang-tab-{{ $lang->code }}"
                        role="tab"
                        aria-controls="lang-panel-{{ $lang->code }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        tabindex="{{ $loop->first ? '0' : '-1' }}"
                        class="lang-tab-btn flex items-center gap-2 px-4 py-2.5 text-sm rounded-t-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/30 whitespace-nowrap
                               {{ $loop->first
                                   ? 'text-primary border-b-2 border-primary font-semibold bg-white'
                                   : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700' }}">
                    <span class="w-6 h-6 rounded-full bg-gray-100 text-gray-600 inline-flex items-center justify-center text-xs font-bold">
                        {{ strtoupper(substr($lang->code, 0, 2)) }}
                    </span>
                    {{ $lang->native }}
                    @if ($lang->is_active)
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" title="{{ t('dashboard.Active', 'Active') }}"></span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Panels --}}
        <div class="p-5 bg-gray-50/50">
            @foreach ($languages as $index => $lang)
                @php $translation = $portfolioTranslations[$lang->code] ?? null; @endphp
                <div id="lang-panel-{{ $lang->code }}"
                     role="tabpanel"
                     aria-labelledby="lang-tab-{{ $lang->code }}"
                     class="lang-panel {{ $loop->first ? 'block' : 'hidden' }} transition-all duration-200">

                    <input type="hidden"
                           name="translations[{{ $index }}][locale]"
                           value="{{ old('translations.' . $index . '.locale', $lang->code) }}">

                    <div class="grid grid-cols-12 gap-4">

                        {{-- العنوان --}}
                        <div class="col-span-12 sm:col-span-6">
                            <label class="form-label" for="title_{{ $lang->code }}">
                                {{ t('dashboard.Portfolio_Title', 'Title') }}
                                @if ($lang->is_active) <span class="text-red-500">*</span> @endif
                            </label>
                            <input type="text"
                                   id="title_{{ $lang->code }}"
                                   name="translations[{{ $index }}][title]"
                                   class="form-control @error('translations.' . $index . '.title') is-invalid @enderror"
                                   value="{{ old('translations.' . $index . '.title', $translation['title'] ?? '') }}"
                                   @if ($lang->is_active) required @endif>
                            @error('translations.' . $index . '.title')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- النوع مع autocomplete --}}
                        <div class="col-span-12 sm:col-span-6">
                            <label class="form-label" for="type_input_{{ $lang->code }}">
                                {{ t('dashboard.Portfolio_Type', 'Type') }}
                                @if ($lang->is_active) <span class="text-red-500">*</span> @endif
                            </label>
                            <div class="relative">
                                <input type="text"
                                       id="type_input_{{ $lang->code }}"
                                       name="translations[{{ $index }}][type]"
                                       class="form-control @error('translations.' . $index . '.type') is-invalid @enderror"
                                       value="{{ old('translations.' . $index . '.type', $translation['type'] ?? '') }}"
                                       oninput="showSuggestions('{{ $lang->code }}')"
                                       onfocus="showSuggestions('{{ $lang->code }}')"
                                       onkeydown="handleTypeKeydown(event, '{{ $lang->code }}')"
                                       autocomplete="off"
                                       @if ($lang->is_active) required @endif>
                                <ul id="type_suggestions_{{ $lang->code }}"></ul>
                            </div>
                            @error('translations.' . $index . '.type')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- المواد --}}
                        <div class="col-span-12 sm:col-span-6">
                            <label class="form-label" for="materials_{{ $lang->code }}">
                                {{ t('dashboard.Portfolio_Materials', 'Materials') }}
                                @if ($lang->is_active) <span class="text-red-500">*</span> @endif
                            </label>
                            <input type="text"
                                   id="materials_{{ $lang->code }}"
                                   name="translations[{{ $index }}][materials]"
                                   class="form-control @error('translations.' . $index . '.materials') is-invalid @enderror"
                                   value="{{ old('translations.' . $index . '.materials', $translation['materials'] ?? '') }}"
                                   @if ($lang->is_active) required @endif>
                            @error('translations.' . $index . '.materials')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- الرابط --}}
                        <div class="col-span-12 sm:col-span-6">
                            <label class="form-label" for="link_{{ $lang->code }}">
                                {{ t('dashboard.Portfolio_Link', 'Project Link') }}
                            </label>
                            <input type="text"
                                   id="link_{{ $lang->code }}"
                                   name="translations[{{ $index }}][link]"
                                   class="form-control font-mono @error('translations.' . $index . '.link') is-invalid @enderror"
                                   dir="ltr"
                                   value="{{ old('translations.' . $index . '.link', $translation['link'] ?? '') }}"
                                   placeholder="https://">
                            @error('translations.' . $index . '.link')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- الحالة --}}
                        <div class="col-span-12 sm:col-span-6">
                            <label class="form-label" for="status_{{ $lang->code }}">
                                {{ t('dashboard.Portfolio_Status', 'Status') }}
                            </label>
                            <select id="status_{{ $lang->code }}"
                                    name="translations[{{ $index }}][status]"
                                    class="form-control @error('translations.' . $index . '.status') is-invalid @enderror">
                                <option value="">{{ t('dashboard.Portfolio_Select_Status', 'Select status') }}</option>
                                @foreach ($statusSuggestions[$lang->code] ?? ($statusSuggestions['en'] ?? []) as $status)
                                    <option value="{{ $status }}"
                                        {{ old('translations.' . $index . '.status', $translation['status'] ?? '') === $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                            @error('translations.' . $index . '.status')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- الوصف --}}
                        <div class="col-span-12">
                            <label class="form-label" for="description_{{ $lang->code }}">
                                {{ t('dashboard.Portfolio_Description', 'Description') }}
                            </label>
                            <textarea id="description_{{ $lang->code }}"
                                      name="translations[{{ $index }}][description]"
                                      rows="4"
                                      class="form-control @error('translations.' . $index . '.description') is-invalid @enderror">{{ old('translations.' . $index . '.description', $translation['description'] ?? '') }}</textarea>
                            @error('translations.' . $index . '.description')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                </div>
            @endforeach
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     أزرار الحفظ والإلغاء
═══════════════════════════════════════════════════════════════════════ --}}
<div class="flex items-center justify-end gap-3 mb-6">
    <a href="{{ route('dashboard.portfolios.index') }}"
       class="btn btn-light">
        {{ t('dashboard.Cancel', 'Cancel') }}
    </a>
    <button type="submit" class="btn btn-primary flex items-center gap-2">
        <i class="ti ti-device-floppy text-base"></i>
        {{ isset($portfolio->id)
            ? t('dashboard.Update_Portfolio', 'Update Portfolio')
            : t('dashboard.Create_Portfolio', 'Create Portfolio') }}
    </button>
</div>


@push('scripts')
    <script>
        // ────────────────────────────────────────────────
        // Type suggestions autocomplete
        // ────────────────────────────────────────────────
        const _typeSuggestionsData = @json($typeSuggestions ?? []);

        function showSuggestions(langCode) {
            const input = document.getElementById('type_input_' + langCode);
            const list  = document.getElementById('type_suggestions_' + langCode);
            if (!input || !list) return;

            const query   = input.value.trim().toLowerCase();
            const pool    = _typeSuggestionsData[langCode] ?? [];
            const matches = query ? pool.filter(s => s.toLowerCase().includes(query)) : pool;

            if (matches.length === 0) { list.style.display = 'none'; return; }

            list.innerHTML = matches.map(s =>
                `<li onclick="selectTypeSuggestion('${langCode}', ${JSON.stringify(s)})">${s}</li>`
            ).join('');
            list.style.display = 'block';
        }

        function selectTypeSuggestion(langCode, value) {
            const input = document.getElementById('type_input_' + langCode);
            const list  = document.getElementById('type_suggestions_' + langCode);
            if (input) input.value = value;
            if (list)  list.style.display = 'none';
        }

        function handleTypeKeydown(event, langCode) {
            const list = document.getElementById('type_suggestions_' + langCode);
            if (!list || list.style.display === 'none') return;

            const items = list.querySelectorAll('li');
            if (!items.length) return;

            const highlighted = list.querySelector('li.highlighted');
            let idx = Array.from(items).indexOf(highlighted);

            if (event.key === 'ArrowDown')  { event.preventDefault(); idx = (idx + 1) % items.length; }
            else if (event.key === 'ArrowUp') { event.preventDefault(); idx = (idx - 1 + items.length) % items.length; }
            else if (event.key === 'Enter' && highlighted) { event.preventDefault(); selectTypeSuggestion(langCode, highlighted.textContent); return; }
            else if (event.key === 'Escape') { list.style.display = 'none'; return; }
            else return;

            items.forEach(li => li.classList.remove('highlighted'));
            items[idx]?.classList.add('highlighted');
        }

        // إغلاق القوائم عند النقر خارجها
        document.addEventListener('click', function (e) {
            document.querySelectorAll('ul[id^="type_suggestions_"]').forEach(function (list) {
                if (!list.closest('.relative')?.contains(e.target)) {
                    list.style.display = 'none';
                }
            });
        });


        // ────────────────────────────────────────────────
        // Language tab switching (portfolio)
        // ────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            const tabIds = @json($languages->pluck('code'));

            function setTabActive(tabEl, isActive) {
                if (!tabEl) return;
                if (isActive) {
                    tabEl.classList.remove('text-gray-500', 'border-transparent', 'hover:text-gray-700');
                    tabEl.classList.add('text-primary', 'border-b-2', 'border-primary', 'font-semibold', 'bg-white');
                    tabEl.setAttribute('aria-selected', 'true');
                    tabEl.setAttribute('tabindex', '0');
                } else {
                    tabEl.classList.remove('text-primary', 'border-primary', 'font-semibold', 'bg-white');
                    tabEl.classList.add('text-gray-500', 'border-transparent', 'hover:text-gray-700');
                    tabEl.setAttribute('aria-selected', 'false');
                    tabEl.setAttribute('tabindex', '-1');
                }
            }

            window.portfolioSwitchLanguageTab = function (langCode) {
                document.querySelectorAll('#portfolioLanguageTabs .lang-tab-btn').forEach(function (tab) {
                    setTabActive(tab, false);
                });
                document.querySelectorAll('.lang-panel').forEach(function (p) {
                    p.classList.add('hidden');
                    p.classList.remove('block');
                });

                const activeTab = document.getElementById('lang-tab-' + langCode);
                setTabActive(activeTab, true);

                const panel = document.getElementById('lang-panel-' + langCode);
                if (panel) {
                    panel.classList.remove('hidden');
                    panel.classList.add('block');
                    setTimeout(function () {
                        panel.querySelector('input[type="text"]')?.focus();
                    }, 60);
                }
                localStorage.setItem('portfolioActiveLangTab', langCode);
            };

            window.portfolioHandleTabKeydown = function (event, langCode) {
                const tabs = document.querySelectorAll('#portfolioLanguageTabs .lang-tab-btn');
                const idx  = Array.from(tabs).findIndex(function (t) { return t.id === 'lang-tab-' + langCode; });
                if (idx < 0) return;
                let next = null;
                if (event.key === 'ArrowLeft')  { event.preventDefault(); next = (idx - 1 + tabs.length) % tabs.length; }
                if (event.key === 'ArrowRight') { event.preventDefault(); next = (idx + 1) % tabs.length; }
                if (event.key === 'Home')       { event.preventDefault(); next = 0; }
                if (event.key === 'End')        { event.preventDefault(); next = tabs.length - 1; }
                if (next != null) {
                    const code = tabs[next].id.replace('lang-tab-', '');
                    window.portfolioSwitchLanguageTab(code);
                    tabs[next].focus();
                }
            };

            // استعادة آخر لسان تم اختياره
            const saved = localStorage.getItem('portfolioActiveLangTab');
            const first = (saved && tabIds.includes(saved)) ? saved : tabIds[0];
            if (first) window.portfolioSwitchLanguageTab(first);
        });
    </script>
@endpush
