{{-- ═══════════════════════════════════════════════════════
     القسم ١: معلومات الخدمة
     ═══════════════════════════════════════════════════════ --}}
<div class="card mb-4">
    <div class="card-header">
        <div class="flex items-center gap-2">
            <span class="badge bg-primary rounded-circle flex items-center justify-center"
                  style="width:28px;height:28px;font-size:14px;">١</span>
            <h5 class="mb-0">{{ t('dashboard.Service_Info', 'Service Information') }}</h5>
        </div>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-12 gap-4">

            {{-- أيقونة الخدمة (media picker) --}}
            @php
                $iconPath       = old('icon', $service->icon ?? null);
                $iconPreviewUrl = $iconPath
                    ? asset('storage/' . ltrim((string) $iconPath, '/'))
                    : null;
            @endphp
            <x-dashboard.media-picker
                name="icon"
                :value="$iconPath"
                :label="t('dashboard.Service_Icon', 'Service Icon')"
                store-value="path"
                :button-text="t('dashboard.Choose_From_Media', 'Choose from Media Library')"
                :preview-urls="$iconPreviewUrl ? [$iconPreviewUrl] : []"
            />

            {{-- ترتيب الظهور --}}
            <div class="col-span-6">
                <label class="form-label" for="service-order">
                    {{ t('dashboard.Service_Display_Order', 'Display Order') }}
                    <span class="text-danger">*</span>
                </label>
                <input type="number" id="service-order" name="order"
                    value="{{ old('order', $service->order ?? 0) }}"
                    class="form-control"
                    placeholder="1"
                    min="0">
                @error('order')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- رابط الخدمة --}}
            <div class="col-span-12">
                <label class="form-label" for="service-url">
                    {{ t('dashboard.Service_Link', 'Service URL') }}
                    <span class="text-muted small">({{ t('dashboard.Optional', 'optional') }})</span>
                </label>
                <input type="text" id="service-url" name="url"
                    value="{{ old('url', $service->url ?? '') }}"
                    class="form-control font-mono"
                    dir="ltr"
                    placeholder="https://example.com/service">
                @error('url')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     القسم ٢: ترجمات الخدمة
     ═══════════════════════════════════════════════════════ --}}
<div class="card mb-4">
    <div class="card-header">
        <div class="flex items-center gap-2">
            <span class="badge bg-primary rounded-circle flex items-center justify-center"
                  style="width:28px;height:28px;font-size:14px;">٢</span>
            <h5 class="mb-0">{{ t('dashboard.Service_Translations', 'Service Translations') }}</h5>
        </div>
    </div>
    <div class="card-body">

        {{-- تبويبات اللغات --}}
        <div class="border-b border-gray-200 mb-4" role="tablist" id="serviceLangTabs">
            <div class="flex gap-0 overflow-x-auto">
                @foreach ($languages as $lang)
                    <button type="button"
                        id="service-lang-tab-{{ $lang->code }}"
                        onclick="switchServiceLangTab('{{ $lang->code }}')"
                        onkeydown="handleServiceTabKeydown(event, '{{ $lang->code }}')"
                        role="tab"
                        aria-controls="service-lang-panel-{{ $lang->code }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        tabindex="{{ $loop->first ? '0' : '-1' }}"
                        class="service-lang-tab px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors border-b-2
                               {{ $loop->first ? 'border-primary text-primary' : 'border-transparent text-muted' }}">
                        {{ strtoupper(substr($lang->code, 0, 2)) }} — {{ $lang->native }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- لوحات اللغات --}}
        @foreach ($languages as $lang)
            @php $translation = $serviceTranslations[$lang->code] ?? null; @endphp
            <div id="service-lang-panel-{{ $lang->code }}"
                 role="tabpanel"
                 aria-labelledby="service-lang-tab-{{ $lang->code }}"
                 class="service-lang-panel {{ $loop->first ? '' : 'hidden' }}">
                <div class="grid grid-cols-12 gap-4">

                    {{-- عنوان الخدمة --}}
                    <div class="col-span-12">
                        <label class="form-label" for="service-title-{{ $lang->code }}">
                            {{ t('dashboard.Service_Title_Label', 'Service Title') }}
                            @if ($loop->first)
                                <span class="text-danger">*</span>
                            @endif
                        </label>
                        <input type="text"
                            id="service-title-{{ $lang->code }}"
                            name="serviceTranslations[{{ $lang->code }}][title]"
                            value="{{ old('serviceTranslations.' . $lang->code . '.title', $translation['title'] ?? '') }}"
                            class="form-control"
                            placeholder="{{ t('dashboard.Service_Title_Label', 'Service Title') }}"
                            @if ($loop->first) required @endif>
                        <input type="hidden" name="serviceTranslations[{{ $lang->code }}][locale]"
                               value="{{ $lang->code }}">
                        @error("serviceTranslations.{$lang->code}.title")
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- وصف الخدمة --}}
                    <div class="col-span-12">
                        <label class="form-label" for="service-desc-{{ $lang->code }}">
                            {{ t('dashboard.Service_Description_Label', 'Description') }}
                            <span class="text-muted small">({{ t('dashboard.Optional', 'optional') }})</span>
                        </label>
                        <textarea id="service-desc-{{ $lang->code }}"
                            name="serviceTranslations[{{ $lang->code }}][description]"
                            class="form-control"
                            rows="4"
                            placeholder="{{ t('dashboard.Service_Description_Label', 'Description') }}">{{ old('serviceTranslations.' . $lang->code . '.description', $translation['description'] ?? '') }}</textarea>
                        @error("serviceTranslations.{$lang->code}.description")
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        @endforeach

    </div>
</div>

{{-- أزرار الحفظ / الإلغاء --}}
<div class="d-flex justify-content-end gap-3 mb-4">
    <a href="{{ route('dashboard.services.index') }}" class="btn btn-light">
        {{ t('dashboard.Cancel', 'Cancel') }}
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-check me-1"></i>
        {{ t('dashboard.Save_Service', 'Save Service') }}
    </button>
</div>

@push('scripts')
<script>
    (() => {
        const tabIds = @json($languages->pluck('code'));

        function switchServiceLangTab(langCode) {
            document.querySelectorAll('.service-lang-tab').forEach(function (tab) {
                const isActive = tab.id === 'service-lang-tab-' + langCode;
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                tab.setAttribute('tabindex', isActive ? '0' : '-1');
                tab.classList.remove('border-primary', 'text-primary', 'border-transparent', 'text-muted');
                if (isActive) {
                    tab.classList.add('border-primary', 'text-primary');
                } else {
                    tab.classList.add('border-transparent', 'text-muted');
                }
            });

            document.querySelectorAll('.service-lang-panel').forEach(function (panel) {
                const isActive = panel.id === 'service-lang-panel-' + langCode;
                panel.classList.toggle('hidden', !isActive);
            });

            try { localStorage.setItem('servicesActiveLangTab', langCode); } catch (e) {}
        }

        function handleServiceTabKeydown(event, langCode) {
            const tabs = Array.from(document.querySelectorAll('.service-lang-tab'));
            const idx = tabs.findIndex(function (t) { return t.id === 'service-lang-tab-' + langCode; });
            if (idx < 0) return;

            let nextIndex = null;
            if (event.key === 'ArrowLeft')  nextIndex = (idx - 1 + tabs.length) % tabs.length;
            if (event.key === 'ArrowRight') nextIndex = (idx + 1) % tabs.length;
            if (event.key === 'Home')       nextIndex = 0;
            if (event.key === 'End')        nextIndex = tabs.length - 1;

            if (nextIndex !== null) {
                event.preventDefault();
                const targetTab = tabs[nextIndex];
                const targetCode = targetTab.id.replace('service-lang-tab-', '');
                switchServiceLangTab(targetCode);
                targetTab.focus();
            }
        }

        window.switchServiceLangTab    = switchServiceLangTab;
        window.handleServiceTabKeydown = handleServiceTabKeydown;

        document.addEventListener('DOMContentLoaded', function () {
            // Switch to tab with validation errors first
            let initial = null;
            document.querySelectorAll('.service-lang-panel').forEach(function (panel) {
                if (!initial && panel.querySelector('.text-danger')) {
                    initial = panel.id.replace('service-lang-panel-', '');
                }
            });
            // Fallback: restore last selected tab
            if (!initial) {
                try { initial = localStorage.getItem('servicesActiveLangTab'); } catch (e) {}
            }
            if (initial && tabIds.includes(initial)) {
                switchServiceLangTab(initial);
            } else if (tabIds.length) {
                switchServiceLangTab(tabIds[0]);
            }
        });
    })();
</script>
@endpush
