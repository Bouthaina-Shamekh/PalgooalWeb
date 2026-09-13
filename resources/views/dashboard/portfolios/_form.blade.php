@if (session('error'))
    <x-dashboard.alert type="error" class="mb-4" role="alert" id="portfolio-save-error">
        {{ session('error') }}
    </x-dashboard.alert>
@endif

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
            // Submit a Media ID; the legacy path is only used for the preview.
            $rawDefaultImageId = old('default_image', $portfolio->default_image_media_id ?? null);
            $defaultImageId = is_scalar($rawDefaultImageId) && ctype_digit((string) $rawDefaultImageId)
                ? (int) $rawDefaultImageId : null;
            $defaultImagePreviewPath = null;
            $defaultImagePreviewUrls = [];

            if ($defaultImageId && $defaultImageId === (int) $portfolio->default_image_media_id) {
                $defaultImagePreviewPath = $portfolio->resolvedDefaultImagePath();
            } elseif ($defaultImageId) {
                $defaultImagePreviewPath = \App\Models\Media::find($defaultImageId)?->file_path;
            } elseif (! $portfolio->default_image_media_id) {
                $defaultImagePreviewPath = $portfolio->resolvedDefaultImagePath();
            }

            if ($defaultImagePreviewPath) {
                $defaultImagePreviewUrls = [asset('storage/' . $defaultImagePreviewPath)];
            }
        @endphp
        <x-dashboard.media-picker
            id="default_image_picker"
            name="default_image"
            :errorMessage="$errors->first('default_image')"
            label="{{ t('dashboard.Portfolio_Default_Image', 'Default Image') }}"
            :value="$defaultImageId"
            :previewUrls="$defaultImagePreviewUrls"
            buttonText="{{ t('dashboard.Portfolio_Choose_Image', 'Choose from Media Library') }}"
        />

        {{-- الصور المتعددة --}}
        @php
            // Form contract: ordered, unique positive Media IDs serialized as CSV.
            // null from this normalizer means invalid, not an intentional empty selection.
            $normalizeGalleryIds = static function ($value): ?array {
                if ($value === null || $value === '') {
                    return [];
                }
                if (is_string($value)) {
                    $value = trim($value);
                    if ($value === '') {
                        return [];
                    }
                    if (str_starts_with($value, '[')) {
                        $value = json_decode($value, true, 512, JSON_BIGINT_AS_STRING);
                        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($value)) {
                            return null;
                        }
                    } else {
                        $value = explode(',', $value);
                    }
                } elseif (is_int($value)) {
                    $value = [$value];
                }
                if (! is_array($value) || ! array_is_list($value)) {
                    return null;
                }

                $ids = [];
                foreach ($value as $candidate) {
                    if (! is_int($candidate) && ! is_string($candidate)) {
                        return null;
                    }
                    $candidate = trim((string) $candidate);
                    if (! ctype_digit($candidate)) {
                        return null;
                    }
                    $id = filter_var(ltrim($candidate, '0'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if ($id === false) {
                        return null;
                    }
                    $ids[$id] = $id;
                }

                return array_values($ids);
            };

            $storedImages = $portfolio->images ?? null;
            $imagesArray = $normalizeGalleryIds(old('images', $storedImages));
            if ($imagesArray === null) {
                // Never turn a parse failure into a request that silently clears saved images.
                $imagesArray = $normalizeGalleryIds($storedImages);
            }
            $galleryRestoreBlocked = $imagesArray === null;
            $imagesArray ??= [];
            $imagesPreviewUrls = [];

            if ($imagesArray !== []) {
                $mediaRecords = \App\Models\Media::whereIn('id', $imagesArray)->get()->keyBy('id');
                foreach ($imagesArray as $imageId) {
                    $media = $mediaRecords->get($imageId);
                    if ($media?->file_path) {
                        $imagesPreviewUrls[] = asset('storage/' . $media->file_path);
                    }
                }
            } elseif ($galleryRestoreBlocked) {
                // Legacy path-only galleries remain visible, but are never submitted as IDs.
                $legacyImages = is_string($storedImages) ? json_decode($storedImages, true) : $storedImages;
                foreach (is_array($legacyImages) ? $legacyImages : [] as $path) {
                    if (is_string($path) && $path !== '') {
                        $imagesPreviewUrls[] = asset('storage/' . $path);
                    }
                }
            }
        @endphp
        <x-dashboard.media-picker
            id="images_picker"
            name="images"
            :errorMessage="$errors->first('images')"
            :helpId="$galleryRestoreBlocked ? 'portfolio-gallery-restore-error' : null"
            label="{{ t('dashboard.Portfolio_Images', 'Gallery Images') }}"
            multiple="true"
            :value="implode(',', $imagesArray)"
            :previewUrls="$imagesPreviewUrls"
            buttonText="{{ t('dashboard.Portfolio_Choose_Images', 'Choose Images from Media Library') }}"
        />
        @if ($galleryRestoreBlocked)
            <p id="portfolio-gallery-restore-error" class="text-danger text-sm" role="alert">
                {{ t('dashboard.Portfolio_Gallery_Restore_Error', 'The saved gallery could not be restored as media IDs. Choose the gallery images again before saving.') }}
            </p>
        @endif

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
                    @if ($errors->has('order')) aria-invalid="true" aria-describedby="portfolio_order_error" @endif
                    class="form-control @error('order') is-invalid @enderror"
                    value="{{ old('order', $portfolio->order ?? 0) }}">
                @error('order')
                    <span id="portfolio_order_error" class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>

            {{-- تاريخ التسليم --}}
            <div class="col-span-12 sm:col-span-6">
                <label class="form-label" for="portfolio_delivery_date">
                    {{ t('dashboard.Portfolio_Delivery_Date', 'Delivery Date') }}
                    <span class="text-red-500">*</span>
                </label>
                <input type="date" id="portfolio_delivery_date" name="delivery_date"
                    @if ($errors->has('delivery_date')) aria-invalid="true" aria-describedby="portfolio_delivery_date_error" @endif
                    class="form-control @error('delivery_date') is-invalid @enderror"
                    value="{{ old('delivery_date', isset($portfolio->delivery_date) ? \Carbon\Carbon::parse($portfolio->delivery_date)->format('Y-m-d') : '') }}">
                @error('delivery_date')
                    <span id="portfolio_delivery_date_error" class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>

            {{-- مدة التنفيذ --}}
            <div class="col-span-12 sm:col-span-6">
                <label class="form-label" for="portfolio_impl_days">
                    {{ t('dashboard.Portfolio_Implementation_Days', 'Implementation Duration (days)') }}
                </label>
                <input type="number" id="portfolio_impl_days" name="implementation_period_days" min="0"
                    @if ($errors->has('implementation_period_days')) aria-invalid="true" aria-describedby="portfolio_impl_days_error" @endif
                    class="form-control @error('implementation_period_days') is-invalid @enderror"
                    value="{{ old('implementation_period_days', $portfolio->implementation_period_days ?? '') }}">
                @error('implementation_period_days')
                    <span id="portfolio_impl_days_error" class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>

            {{-- اسم العميل --}}
            <div class="col-span-12 sm:col-span-6">
                <label class="form-label" for="portfolio_client">
                    {{ t('dashboard.Portfolio_Client_Name', 'Client Name') }}
                </label>
                <input type="text" id="portfolio_client" name="client"
                    @if ($errors->has('client')) aria-invalid="true" aria-describedby="portfolio_client_error" @endif
                    class="form-control @error('client') is-invalid @enderror"
                    value="{{ old('client', $portfolio->client ?? '') }}">
                @error('client')
                    <span id="portfolio_client_error" class="text-danger text-sm">{{ $message }}</span>
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

        @php
            $firstErrorLanguage = null;
            foreach ($languages as $index => $lang) {
                if ($errors->has('translations.' . $index . '.*')) {
                    $firstErrorLanguage = $lang->code;
                    break;
                }
            }
            $initialLanguage = $firstErrorLanguage ?? $languages->first()?->code;
        @endphp
        {{-- تبويبات اللغات --}}
        <div class="flex flex-wrap gap-1 border-b border-gray-200 px-4 pt-4 overflow-x-auto"
             role="tablist" id="portfolioLanguageTabs">
            @foreach ($languages as $index => $lang)
                <button type="button"
                        onclick="portfolioSwitchLanguageTab('{{ $lang->code }}')"
                        onkeydown="portfolioHandleTabKeydown(event, '{{ $lang->code }}')"
                        id="lang-tab-{{ $lang->code }}"
                        data-validation-error="{{ $errors->has('translations.' . $index . '.*') ? 'true' : 'false' }}"
                        role="tab"
                        aria-controls="lang-panel-{{ $lang->code }}"
                        aria-selected="{{ $lang->code === $initialLanguage ? 'true' : 'false' }}"
                        tabindex="{{ $lang->code === $initialLanguage ? '0' : '-1' }}"
                        class="lang-tab-btn flex items-center gap-2 px-4 py-2.5 text-sm rounded-t-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/30 whitespace-nowrap
                               {{ $lang->code === $initialLanguage
                                   ? 'text-primary border-b-2 border-primary font-semibold bg-white'
                                   : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700' }}">
                    <span class="w-6 h-6 rounded-full bg-gray-100 text-gray-600 inline-flex items-center justify-center text-xs font-bold">
                        {{ strtoupper(substr($lang->code, 0, 2)) }}
                    </span>
                    {{ $lang->native }}
                    @if ($errors->has('translations.' . $index . '.*'))
                        <span class="text-danger font-bold" role="img"
                              aria-label="{{ $errors->first('translations.' . $index . '.*') }}"
                              title="{{ $errors->first('translations.' . $index . '.*') }}">!</span>
                    @endif
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
                     class="lang-panel {{ $lang->code === $initialLanguage ? 'block' : 'hidden' }} transition-all duration-200">

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
                    @if ($errors->has('translations.' . $index . '.title')) aria-invalid="true" aria-describedby="title_{{ $lang->code }}_error" @endif
                                   class="form-control @error('translations.' . $index . '.title') is-invalid @enderror"
                                   value="{{ old('translations.' . $index . '.title', $translation['title'] ?? '') }}"
                                   @if ($lang->is_active) required @endif>
                           @error('translations.' . $index . '.title')
                                <span id="title_{{ $lang->code }}_error" class="text-danger text-sm">{{ $message }}</span>
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
                                       role="combobox" aria-autocomplete="list" aria-expanded="false"
                                       aria-controls="type_suggestions_{{ $lang->code }}"
                                   name="translations[{{ $index }}][type]"
                    @if ($errors->has('translations.' . $index . '.type')) aria-invalid="true" aria-describedby="type_{{ $lang->code }}_error" @endif
                                       class="form-control @error('translations.' . $index . '.type') is-invalid @enderror"
                                       value="{{ old('translations.' . $index . '.type', $translation['type'] ?? '') }}"
                                       oninput="showSuggestions('{{ $lang->code }}')"
                                       onfocus="showSuggestions('{{ $lang->code }}')"
                                       onkeydown="handleTypeKeydown(event, '{{ $lang->code }}')"
                                       autocomplete="off"
                                       @if ($lang->is_active) required @endif>
                                <ul id="type_suggestions_{{ $lang->code }}" role="listbox" aria-labelledby="type_input_{{ $lang->code }}"></ul>
                            </div>
                           @error('translations.' . $index . '.type')
                                <span id="type_{{ $lang->code }}_error" class="text-danger text-sm">{{ $message }}</span>
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
                    @if ($errors->has('translations.' . $index . '.materials')) aria-invalid="true" aria-describedby="materials_{{ $lang->code }}_error" @endif
                                   class="form-control @error('translations.' . $index . '.materials') is-invalid @enderror"
                                   value="{{ old('translations.' . $index . '.materials', $translation['materials'] ?? '') }}"
                                   @if ($lang->is_active) required @endif>
                           @error('translations.' . $index . '.materials')
                                <span id="materials_{{ $lang->code }}_error" class="text-danger text-sm">{{ $message }}</span>
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
                    @if ($errors->has('translations.' . $index . '.link')) aria-invalid="true" aria-describedby="link_{{ $lang->code }}_error" @endif
                                   class="portfolio-placeholder form-control font-mono @error('translations.' . $index . '.link') is-invalid @enderror"
                                   dir="ltr"
                                   value="{{ old('translations.' . $index . '.link', $translation['link'] ?? '') }}"
                                   placeholder="https://">
                           @error('translations.' . $index . '.link')
                                <span id="link_{{ $lang->code }}_error" class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- الحالة --}}
                        <div class="col-span-12 sm:col-span-6">
                            <label class="form-label" for="status_{{ $lang->code }}">
                                {{ t('dashboard.Portfolio_Status', 'Status') }}
                            </label>
                            <select id="status_{{ $lang->code }}"
                                   name="translations[{{ $index }}][status]"
                    @if ($errors->has('translations.' . $index . '.status')) aria-invalid="true" aria-describedby="status_{{ $lang->code }}_error" @endif
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
                                <span id="status_{{ $lang->code }}_error" class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- الوصف --}}
                        <div class="col-span-12">
                            <label class="form-label" for="description_{{ $lang->code }}">
                                {{ t('dashboard.Portfolio_Description', 'Description') }}
                            </label>
                            <textarea id="description_{{ $lang->code }}"
                                   name="translations[{{ $index }}][description]"
                    @if ($errors->has('translations.' . $index . '.description')) aria-invalid="true" aria-describedby="description_{{ $lang->code }}_error" @endif
                                      rows="4"
                                      class="form-control @error('translations.' . $index . '.description') is-invalid @enderror">{{ old('translations.' . $index . '.description', $translation['description'] ?? '') }}</textarea>
                           @error('translations.' . $index . '.description')
                                <span id="description_{{ $lang->code }}_error" class="text-danger text-sm">{{ $message }}</span>
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
    <button type="submit" id="portfolio-save" class="btn btn-primary flex items-center gap-2" @disabled($galleryRestoreBlocked)>
        <i class="ti ti-device-floppy text-base"></i>
        {{ isset($portfolio->id)
            ? t('dashboard.Update_Portfolio', 'Update Portfolio')
            : t('dashboard.Create_Portfolio', 'Create Portfolio') }}
    </button>
</div>


@push('scripts')
    <script>
        // An unresolvable stored gallery must not be silently replaced with an empty value.
        document.addEventListener('DOMContentLoaded', function () {
            const restoreError = document.getElementById('portfolio-gallery-restore-error');
            if (!restoreError) return;

            const galleryInput = document.getElementById('images_picker');
            const saveButton = document.getElementById('portfolio-save');
            let restoreBlocked = true;
            galleryInput.form.addEventListener('submit', function (event) {
                if (restoreBlocked) event.preventDefault();
            });
            galleryInput.addEventListener('change', function () {
                if (!/^[1-9]\d*(,[1-9]\d*)*$/.test(galleryInput.value)) return;
                restoreBlocked = false;
                saveButton.disabled = false;
                restoreError.hidden = true;
            });
        });

        // ────────────────────────────────────────────────
        // Type suggestions autocomplete
        // ────────────────────────────────────────────────
        const _typeSuggestionsData = @json($typeSuggestions ?? []);

        function closeTypeSuggestions(langCode) {
            const input = document.getElementById('type_input_' + langCode);
            const list = document.getElementById('type_suggestions_' + langCode);
            if (list) {
                list.style.display = 'none';
                list.querySelectorAll('li').forEach(item => {
                    item.classList.remove('highlighted');
                    item.setAttribute('aria-selected', 'false');
                });
            }
            input?.setAttribute('aria-expanded', 'false');
            input?.removeAttribute('aria-activedescendant');
        }

        function showSuggestions(langCode) {
            const input = document.getElementById('type_input_' + langCode);
            const list  = document.getElementById('type_suggestions_' + langCode);
            if (!input || !list) return;

            const query   = input.value.trim().toLowerCase();
            const pool    = _typeSuggestionsData[langCode] ?? [];
            const matches = query ? pool.filter(s => s.toLowerCase().includes(query)) : pool;

            closeTypeSuggestions(langCode);
            if (matches.length === 0) return;

            list.replaceChildren();
            matches.forEach((s, index) => {
                const item = document.createElement('li');
                item.id = list.id + '-option-' + index;
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', 'false');
                item.textContent = s;
                item.addEventListener('click', () => selectTypeSuggestion(langCode, s));
                list.appendChild(item);
            });
            list.style.display = 'block';
            input.setAttribute('aria-expanded', 'true');
        }

        function selectTypeSuggestion(langCode, value) {
            const input = document.getElementById('type_input_' + langCode);
            const list  = document.getElementById('type_suggestions_' + langCode);
            if (input) input.value = value;
            closeTypeSuggestions(langCode);
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
            else if (event.key === 'Escape' || event.key === 'Tab') { closeTypeSuggestions(langCode); return; }
            else return;

            items.forEach(li => {
                li.classList.remove('highlighted');
                li.setAttribute('aria-selected', 'false');
            });
            items[idx]?.classList.add('highlighted');
            items[idx]?.setAttribute('aria-selected', 'true');
            document.getElementById('type_input_' + langCode)?.setAttribute('aria-activedescendant', items[idx].id);
        }

        // إغلاق القوائم عند النقر خارجها
        document.addEventListener('click', function (e) {
            document.querySelectorAll('ul[id^="type_suggestions_"]').forEach(function (list) {
                if (!list.closest('.relative')?.contains(e.target)) {
                    closeTypeSuggestions(list.id.replace('type_suggestions_', ''));
                }
            });
        });


        // ────────────────────────────────────────────────
        // Language tab switching (portfolio)
        // ────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            const tabIds = @json($languages->pluck('code'));
            let focusTimer;

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

            window.portfolioSwitchLanguageTab = function (langCode, focusInput = true) {
                clearTimeout(focusTimer);
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
                    if (focusInput) focusTimer = setTimeout(function () {
                        panel.querySelector('input[type="text"]')?.focus();
                    }, 60);
                }
                try { localStorage.setItem('portfolioActiveLangTab', langCode); } catch (error) { /* Tab visibility does not depend on storage. */ }
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
                    window.portfolioSwitchLanguageTab(code, false);
                    tabs[next].focus();
                }
            };

            // استعادة آخر لسان تم اختياره
            const form = document.getElementById('portfolioLanguageTabs').closest('form');
            form?.addEventListener('invalid', function (event) {
                // Native validation fires for every invalid control before focusing one.
                // Keep only the first eligible control's native report/focus, so later
                // hidden panels cannot steal activation or cause an unfocusable error.
                const firstInvalid = Array.from(form.elements).find(field => field.willValidate && !field.validity.valid);
                if (event.target !== firstInvalid) {
                    event.preventDefault();
                    return;
                }
                clearTimeout(focusTimer);
                const panel = event.target.closest('.lang-panel');
                if (panel) window.portfolioSwitchLanguageTab(panel.id.replace('lang-panel-', ''), false);
            }, true);

            let saved;
            try { saved = localStorage.getItem('portfolioActiveLangTab'); } catch (error) { /* Use the rendered default. */ }
            const errorTab = document.querySelector('#portfolioLanguageTabs [data-validation-error="true"]');
            const first = errorTab ? errorTab.id.replace('lang-tab-', '') : ((saved && tabIds.includes(saved)) ? saved : tabIds[0]);
            if (first) window.portfolioSwitchLanguageTab(first, !errorTab);
        });
    </script>
@endpush
