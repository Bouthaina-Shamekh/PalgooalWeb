@php
    $isEditing = $sectionDefinition->exists;
    $selectedTemplateOption = is_array($templateOptions[$selectedTemplateKey] ?? null)
        ? $templateOptions[$selectedTemplateKey]
        : null;
    $selectedTemplateMeta = is_array($selectedTemplateMeta ?? null) ? $selectedTemplateMeta : $selectedTemplateOption;
    $selectedDefinitionCategory = old('category', $sectionDefinition->category);
    $templateOptionSummaries = collect($templateOptions)
        ->mapWithKeys(
            fn($templateOption, $templateKey) => [
                $templateKey => [
                    'label' => $templateOption['label'] ?? $templateKey,
                    'view' => $templateOption['view'] ?? '',
                    'category' => $templateOption['category'] ?? '',
                    'source' => $templateOption['resolution_source'] ?? 'registry',
                ],
            ],
        )
        ->all();
@endphp

<div class="grid grid-cols-12 gap-x-6 gap-y-4">

    {{-- مربع الإرشادات --}}
    <div class="col-span-12">
        <div class="rounded border border-slate-200 bg-slate-50 px-4 py-4">
            <h6 class="mb-2 text-sm font-semibold text-slate-900">
                {{ t('dashboard.Def_Workflow_Title', 'مسار العمل الموصى به') }}
            </h6>
            <p class="mb-0 text-sm text-slate-600">
                {{ t('dashboard.Def_Workflow_Desc', 'اختر Dynamic لوضع المحرر، أدخل Category و Template Key مستقرَّين، احفظ التعريف، ثم انتقل لتعريفات الحقول.') }}
            </p>
        </div>
    </div>

    {{-- الاسم --}}
    <div class="col-span-12 md:col-span-6">
        <label for="name" class="form-label">{{ t('dashboard.Name', 'الاسم') }}</label>
        <input id="name" type="text" name="name" class="form-control"
            value="{{ old('name', $sectionDefinition->label) }}"
            placeholder="{{ t('dashboard.Def_Name_Placeholder', 'اسم تعريف القسم') }}"
            required>
        @error('name')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    {{-- المفتاح --}}
    <div class="col-span-12 md:col-span-6">
        <label for="key" class="form-label">{{ t('dashboard.Field_Key', 'المفتاح') }}</label>
        <input id="key" type="text" name="key" class="form-control font-mono" dir="ltr"
            value="{{ old('key', $sectionDefinition->section_key) }}"
            placeholder="hero_campaign" required>
        <div class="mt-1 text-xs text-slate-500">
            {{ t('dashboard.Def_Key_Hint', 'استخدم مفتاحاً مستقراً بحروف صغيرة وأرقام وشرطات سفلية أو عادية فقط.') }}
        </div>
        @error('key')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    {{-- الوصف --}}
    <div class="col-span-12">
        <label for="description" class="form-label">{{ t('dashboard.Description', 'الوصف') }}</label>
        <textarea id="description" name="description" class="form-control" rows="3"
            placeholder="{{ t('dashboard.Def_Description_Placeholder', 'وصف داخلي للمطورين والمشرفين.') }}">{{ old('description', $sectionDefinition->description) }}</textarea>
        @error('description')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    {{-- التصنيف --}}
    <div class="col-span-12 md:col-span-4">
        <label for="category" class="form-label">{{ t('dashboard.Category', 'التصنيف') }}</label>
        <input id="category" type="text" name="category" class="form-control"
            value="{{ old('category', $sectionDefinition->category) }}"
            placeholder="{{ t('dashboard.Def_Category_Placeholder', 'hero، services، pricing') }}">
        @error('category')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    {{-- صورة المعاينة --}}
    <div class="col-span-12 md:col-span-8">
        <x-dashboard.media-picker
            id="preview_media_id"
            name="preview_media_id"
            :label="t('dashboard.Preview_Image', 'صورة المعاينة')"
            :button-text="t('dashboard.Choose_From_Media', 'اختر من مكتبة الوسائط')"
            :value="$previewMediaValue"
            :preview-urls="$previewMediaPreviewUrls"
            store-value="id"
            class="col-span-12" />
        <div class="mt-1 text-xs text-slate-500">
            {{ t('dashboard.Def_Preview_Image_Hint', 'تُستخدم في بطاقات مكتبة الأقسام. لا تؤثر على الواجهة الأمامية.') }}
        </div>
        @error('preview_media_id')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    {{-- مفتاح القالب --}}
    <div class="col-span-12 md:col-span-4">
        <label for="template_key" class="form-label">{{ t('dashboard.Def_Template_Key', 'مفتاح القالب') }}</label>
        <input id="template_key" type="text" name="template_key" class="form-control font-mono" dir="ltr"
            list="template_key_suggestions"
            value="{{ $selectedTemplateKey }}"
            placeholder="hero_campaign"
            data-template-key-input>
        <datalist id="template_key_suggestions">
            @foreach ($templateOptions as $templateKey => $templateOption)
                <option value="{{ $templateKey }}">{{ $templateOption['label'] }} ({{ $templateKey }})</option>
            @endforeach
        </datalist>
        <div class="mt-1 text-xs text-slate-500">
            {{ t('dashboard.Def_Template_Key_Hint', 'أدخل مفتاحاً مستقراً. إذا لم يُسجَّل override برمجياً، سيحاول النظام المسار الاصطلاحي front.sections.{category}.{template_key}.') }}
        </div>

        {{-- ملخص اختيار القالب --}}
        <div class="mt-3 rounded border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-600"
            data-template-selection-summary>
            <div class="font-medium text-slate-900" data-template-selection-title>
                @if ($selectedTemplateKey)
                    {{ ($selectedTemplateMeta['resolution_source'] ?? null) === 'registry'
                        ? t('dashboard.Def_Code_Override', 'تجاوز برمجي متوفر')
                        : t('dashboard.Def_Convention_Key', 'مفتاح مسار اصطلاحي') }}
                @else
                    {{ t('dashboard.Def_No_Template', 'لم يُحدد قالب بعد') }}
                @endif
            </div>
            <div class="mt-1" data-template-selection-meta>
                @if ($selectedTemplateKey)
                    {{ $selectedTemplateMeta['label'] ?? \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', $selectedTemplateKey)) }}
                    / {{ $selectedTemplateKey }}
                    @if (!empty($selectedTemplateMeta['category'] ?? null))
                        / {{ \Illuminate\Support\Str::headline($selectedTemplateMeta['category']) }}
                    @endif
                @else
                    {{ t('dashboard.Def_No_Template_Desc', 'يجب اختيار مفتاح قالب قبل البدء في تعريف الحقول.') }}
                @endif
            </div>
            <div class="mt-1 text-xs text-slate-500" data-template-selection-view>
                @if ($selectedTemplateKey)
                    {{ strtr(t('dashboard.Def_Renderer_Candidate_Label', 'المرشح:'), []) }}
                    {{ $selectedTemplateMeta['view'] ?? 'front.sections.' . \App\Support\Sections\SectionTemplateRegistry::normalizeCategory($selectedDefinitionCategory) . '.' . $selectedTemplateKey }}
                @else
                    {{ t('dashboard.Def_View_Resolution', 'يُحدد مسار العرض برمجياً من مفتاح القالب المختار.') }}
                @endif
            </div>
        </div>

        @error('template_key')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    {{-- الترتيب --}}
    <div class="col-span-12 md:col-span-4">
        <label for="sort_order" class="form-label">{{ t('dashboard.Sort_Order', 'الترتيب') }}</label>
        <input id="sort_order" type="number" min="0" name="sort_order" class="form-control"
            value="{{ old('sort_order', $sectionDefinition->sort_order ?? 0) }}">
        @error('sort_order')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    {{-- وضع المحرر — QW1: استبدل select وهمي بـ badge ثابت --}}
    <div class="col-span-12 md:col-span-6">
        <label class="form-label">{{ t('dashboard.Editor_Mode', 'وضع المحرر') }}</label>

        {{-- Hidden input يُرسل القيمة للـ Controller بدون تغيير --}}
        <input type="hidden" name="editor_mode" value="{{ \App\Models\Sections\SectionDefinition::EDITOR_MODE_DYNAMIC }}">

        {{-- Badge ثابت بدلاً من select --}}
        <div class="rounded border border-blue-200 bg-blue-50 px-3 py-2.5">
            <span class="inline-flex items-center gap-1.5 font-semibold text-blue-700 text-sm">
                <i class="ti ti-bolt"></i>
                {{ t('dashboard.Dynamic', 'ديناميكي') }}
            </span>
            <p class="mt-1 mb-0 text-xs text-blue-600">
                {{ t('dashboard.Def_Editor_Mode_Hint', 'هذا التعريف يستخدم المحرر الديناميكي فقط. لا توجد أوضاع تحرير أخرى حالياً.') }}
            </p>
        </div>

        <div class="mt-3 rounded border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-600">
            <div class="font-medium text-slate-900">{{ t('dashboard.Def_Dynamic_Workflow_Title', 'مسار Dynamic') }}</div>
            <div class="mt-1">
                {{ t('dashboard.Def_Dynamic_Workflow_Desc', 'المسار المعتاد للأقسام: أدخل مفتاح القالب، احفظ، ثم أضف تعريفات الحقول.') }}
            </div>
        </div>
    </div>

    {{-- الحالة والرؤية --}}
    <div class="col-span-12">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <label class="flex cursor-pointer items-center gap-3 rounded border border-slate-200 px-4 py-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="form-checkbox"
                    @checked(old('is_active', $sectionDefinition->is_active))>
                <span>
                    <span class="block font-medium text-slate-900">{{ t('dashboard.Active', 'مفعّل') }}</span>
                    <span class="block text-sm text-slate-500">{{ t('dashboard.Def_Active_Hint', 'التعريفات غير النشطة تبقى محفوظة لكن لا تُعرض في الأدوات.') }}</span>
                </span>
            </label>

            <label class="flex cursor-pointer items-center gap-3 rounded border border-slate-200 px-4 py-3">
                <input type="hidden" name="is_visible_in_library" value="0">
                <input type="checkbox" name="is_visible_in_library" value="1" class="form-checkbox"
                    @checked(old('is_visible_in_library', $sectionDefinition->is_visible))>
                <span>
                    <span class="block font-medium text-slate-900">{{ t('dashboard.Visible_In_Library', 'ظاهر في المكتبة') }}</span>
                    <span class="block text-sm text-slate-500">{{ t('dashboard.Def_Visible_Hint', 'فعّله مع Active لظهور التعريف في مكتبة الأقسام.') }}</span>
                </span>
            </label>
        </div>
        @error('is_active')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
        @error('is_visible_in_library')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    {{-- QW4: قائمة الحقول read-only — تعيد استخدام $bladeFields المُحمَّل مسبقاً في edit.blade.php --}}
    @if($sectionDefinition->exists && ($bladeFields ?? collect())->isNotEmpty())
        <div class="col-span-12">
            <div class="rounded border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex items-center justify-between mb-2">
                    <h6 class="mb-0 text-xs font-semibold text-slate-700 uppercase tracking-wide">
                        <i class="ti ti-layout-list me-1 text-slate-400"></i>
                        {{ t('dashboard.Fields', 'الحقول') }}
                        <span class="ms-1 font-normal normal-case text-slate-400">({{ $fieldsCount ?? ($bladeFields ?? collect())->count() }})</span>
                    </h6>
                    <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}"
                       class="text-xs text-indigo-600 hover:text-indigo-800 hover:underline transition">
                        {{ t('dashboard.Manage_Fields', 'إدارة الحقول') }} ←
                    </a>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach(($bladeFields ?? collect()) as $qw4Field)
                        @php
                            $qw4IsRequired = (bool) ($qw4Field->is_required ?? false)
                                || (is_array($qw4Field->validation_rules ?? null) && in_array('required', $qw4Field->validation_rules))
                                || (is_string($qw4Field->validation_rules ?? '') && str_contains($qw4Field->validation_rules ?? '', 'required'));
                        @endphp
                        <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs shadow-sm">
                            @if(($qw4Field->field_scope ?? '') === 'translatable')
                                <span class="inline-flex items-center justify-center rounded bg-blue-100 font-bold text-blue-600 flex-shrink-0"
                                      style="width:13px;height:13px;font-size:8px;"
                                      title="{{ t('dashboard.Translatable', 'قابل للترجمة') }}">ت</span>
                            @else
                                <span class="inline-flex items-center justify-center rounded bg-gray-100 font-bold text-gray-500 flex-shrink-0"
                                      style="width:13px;height:13px;font-size:8px;"
                                      title="{{ t('dashboard.Shared', 'مشترك') }}">م</span>
                            @endif
                            <span class="font-mono text-slate-700">{{ $qw4Field->field_key }}</span>
                            <span class="text-slate-400">{{ $qw4Field->field_type }}</span>
                            @if($qw4IsRequired)
                                <span class="font-semibold"
                                      style="background:#fee2e2;color:#b91c1c;border-radius:3px;padding:0 4px;font-size:9px;">
                                    {{ t('dashboard.Required', 'مطلوب') }}
                                </span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

</div>

<script>
    (function () {
        const templateInput = document.querySelector('[data-template-key-input]');
        const templateSummaryTitle = document.querySelector('[data-template-selection-title]');
        const templateSummaryMeta = document.querySelector('[data-template-selection-meta]');
        const templateSummaryView = document.querySelector('[data-template-selection-view]');
        const categoryInput = document.getElementById('category');
        const templateOptions = @json($templateOptionSummaries);

        const noTemplateLabel = '{{ t('dashboard.Def_No_Template', 'لم يُحدد قالب بعد') }}';
        const noTemplateDesc  = '{{ t('dashboard.Def_No_Template_Desc', 'يجب اختيار مفتاح قالب قبل البدء في تعريف الحقول.') }}';
        const viewResolution  = '{{ t('dashboard.Def_View_Resolution', 'يُحدد مسار العرض برمجياً من مفتاح القالب المختار.') }}';
        const codeOverride    = '{{ t('dashboard.Def_Code_Override', 'تجاوز برمجي متوفر') }}';
        const conventionKey   = '{{ t('dashboard.Def_Convention_Key', 'مفتاح مسار اصطلاحي') }}';
        const rendererLabel   = '{{ t('dashboard.Def_Renderer_Candidate_Label', 'المرشح:') }}';

        const normalizeCategory = function (category) {
            const normalizedCategory = String(category || '').trim().toLowerCase();
            return /^[a-z0-9_-]+$/.test(normalizedCategory) ? normalizedCategory : 'uncategorized';
        };

        const buildConventionView = function (templateKey) {
            return 'front.sections.' + normalizeCategory(categoryInput ? categoryInput.value : '') + '.' + templateKey;
        };

        const buildTemplateLabel = function (templateKey) {
            return templateKey
                .replace(/[_-]+/g, ' ')
                .replace(/\b\w/g, function (match) {
                    return match.toUpperCase();
                });
        };

        const syncTemplateSummary = function () {
            if (!templateInput || !templateSummaryTitle || !templateSummaryMeta || !templateSummaryView) {
                return;
            }

            const selectedValue = templateInput.value.trim();

            if (!selectedValue) {
                templateSummaryTitle.textContent = noTemplateLabel;
                templateSummaryMeta.textContent  = noTemplateDesc;
                templateSummaryView.textContent  = viewResolution;
                return;
            }

            const templateOption   = templateOptions[selectedValue] || null;
            const templateLabel    = templateOption ? templateOption.label : buildTemplateLabel(selectedValue);
            const templateCategory = templateOption ? templateOption.category : '';
            const templateView     = templateOption && templateOption.view
                ? templateOption.view
                : buildConventionView(selectedValue);
            const templateSource   = templateOption ? templateOption.source : 'convention';
            const summaryParts     = [templateLabel, selectedValue];

            if (templateCategory) {
                summaryParts.push(templateCategory.replace(/[_-]+/g, ' '));
            }

            templateSummaryTitle.textContent = templateSource === 'registry' ? codeOverride : conventionKey;
            templateSummaryMeta.textContent  = summaryParts.join(' / ');
            templateSummaryView.textContent  = rendererLabel + ' ' + templateView;
        };

        if (templateInput) {
            templateInput.addEventListener('input', syncTemplateSummary);
            templateInput.addEventListener('change', syncTemplateSummary);
            syncTemplateSummary();
        }

        if (categoryInput) {
            categoryInput.addEventListener('input', syncTemplateSummary);
            categoryInput.addEventListener('change', syncTemplateSummary);
        }
    })();
</script>
