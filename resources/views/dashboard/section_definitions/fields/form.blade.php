@php
    $isEditing = $field->exists;
    $isTranslatable = old('is_translatable', $field->isTranslatable());
@endphp

<div class="grid grid-cols-12 gap-x-6 gap-y-6">

    {{-- ── Main Form Column ──────────────────────────────────────── --}}
    <div class="col-span-12 lg:col-span-7">

        {{-- Card 1: Field Metadata --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">{{ t('dashboard.Field_Metadata', 'بيانات الحقل') }}</h5>
                <p class="mb-0 text-sm text-slate-500">
                    {{ t('dashboard.Field_Metadata_Desc', 'حدد المخطط الأساسي للحقل. تبقى المفاتيح ثابتة وتواجه المطورين.') }}
                </p>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-12 gap-x-6 gap-y-4">

                    {{-- Key --}}
                    <div class="col-span-12 md:col-span-6">
                        <label for="key" class="form-label">{{ t('dashboard.Field_Key', 'المفتاح') }}</label>
                        <input id="key" type="text" name="key" class="form-control font-mono" dir="ltr"
                            value="{{ old('key', $field->field_key) }}" placeholder="headline" required>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ t('dashboard.Field_Key_Hint', 'حروف صغيرة وأرقام ونقاط وشرطات سفلية فقط.') }}
                        </div>
                        @error('key')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Label --}}
                    <div class="col-span-12 md:col-span-6">
                        <label for="label" class="form-label">{{ t('dashboard.Field_Label', 'الاسم') }}</label>
                        <input id="label" type="text" name="label" class="form-control"
                            value="{{ old('label', $field->label) }}"
                            placeholder="{{ t('dashboard.Field_Label_Placeholder', 'العنوان الرئيسي') }}" required>
                        @error('label')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Type --}}
                    <div class="col-span-12 md:col-span-4">
                        <label for="type" class="form-label">{{ t('dashboard.Field_Type', 'النوع') }}</label>
                        <select id="type" name="type" class="form-control" required>
                            @foreach ($fieldTypeOptions as $fieldTypeValue => $fieldTypeLabel)
                                <option value="{{ $fieldTypeValue }}" @selected($selectedFieldType === $fieldTypeValue)>
                                    {{ $fieldTypeLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Group --}}
                    <div class="col-span-12 md:col-span-4">
                        <label for="group" class="form-label">{{ t('dashboard.Field_Group', 'المجموعة') }}</label>
                        <input id="group" type="text" name="group" class="form-control"
                            list="field-group-suggestions"
                            value="{{ old('group', $field->group_name) }}"
                            placeholder="content">
                        <datalist id="field-group-suggestions">
                            @foreach ($groupSuggestions as $groupSuggestion)
                                <option value="{{ $groupSuggestion }}"></option>
                            @endforeach
                        </datalist>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ t('dashboard.Field_Group_Hint', 'استخدم المجموعات لتقسيم الحقول في واجهة الإدارة.') }}
                        </div>
                        @error('group')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Sort Order --}}
                    <div class="col-span-12 md:col-span-4">
                        <label for="sort_order" class="form-label">{{ t('dashboard.Sort_Order', 'الترتيب') }}</label>
                        <input id="sort_order" type="number" min="0" name="sort_order" class="form-control"
                            value="{{ old('sort_order', $field->sort_order ?? 0) }}">
                        @error('sort_order')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Translatable + Required toggles --}}
                    <div class="col-span-12">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <label class="flex items-center gap-3 rounded border border-slate-200 px-4 py-3 cursor-pointer">
                                <input type="hidden" name="is_translatable" value="0">
                                <input type="checkbox" name="is_translatable" value="1" class="form-checkbox"
                                    data-field-translatable-toggle @checked($isTranslatable)>
                                <span>
                                    <span class="block font-medium text-slate-900">{{ t('dashboard.Translatable', 'قابل للترجمة') }}</span>
                                    <span class="block text-sm text-slate-500">
                                        {{ t('dashboard.Translatable_Hint', 'يمكّن القيم الافتراضية لكل لغة من اللغات النشطة.') }}
                                    </span>
                                </span>
                            </label>

                            <label class="flex items-center gap-3 rounded border border-slate-200 px-4 py-3 cursor-pointer">
                                <input type="hidden" name="is_required" value="0">
                                <input type="checkbox" name="is_required" value="1" class="form-checkbox"
                                    @checked(old('is_required', $field->is_required))>
                                <span>
                                    <span class="block font-medium text-slate-900">{{ t('dashboard.Required', 'إلزامي') }}</span>
                                    <span class="block text-sm text-slate-500">
                                        {{ t('dashboard.Required_Hint', 'يجعل الحقل إلزامياً في عقود التحقق لاحقاً.') }}
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Card 2: Repeater Sub-fields (conditional) --}}
        <div class="card mt-6 {{ $selectedFieldType !== 'repeater' ? 'hidden' : '' }}" data-repeater-panel>
            <div class="card-header">
                <h5 class="mb-1">{{ t('dashboard.Repeater_Sub_fields', 'حقول فرعية للـ Repeater') }}</h5>
                <p class="mb-0 text-sm text-slate-500">
                    {{ t('dashboard.Repeater_Sub_fields_Desc', 'عرّف مخطط كل عنصر في هذا الـ Repeater. يجب أن تكون المفاتيح فريدة داخل الـ Repeater.') }}
                </p>
            </div>
            <div class="card-body">
                @include('dashboard.section_definitions.fields.partials.repeater-item-schema-editor', [
                    'repeaterItemSchema' => $repeaterItemSchema,
                    'repeaterSubFieldTypeOptions' => $repeaterSubFieldTypeOptions,
                ])
            </div>
        </div>

        {{-- Card 3: Default Value --}}
        <div class="card mt-6">
            <div class="card-header">
                <h5 class="mb-1">{{ t('dashboard.Default_Value', 'القيمة الافتراضية') }}</h5>
                <p class="mb-0 text-sm text-slate-500">
                    {{ t('dashboard.Default_Value_Desc', 'الحقول المشتركة تحتفظ بقيمة واحدة؛ القابلة للترجمة تحتفظ بقيمة لكل لغة.') }}
                </p>
            </div>
            <div class="card-body">
                <div class="mb-4 rounded border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    {{ t('dashboard.Default_Value_Hint', 'استخدم قيماً خام. للـ select: القيمة المختارة. للـ boolean: true/false. للـ media: معرّف الميديا.') }}
                </div>

                <div data-default-shared-panel class="{{ $isTranslatable ? 'hidden' : '' }}">
                    <label for="default_value_shared" class="form-label">{{ t('dashboard.Shared_Default_Value', 'القيمة الافتراضية المشتركة') }}</label>
                    @include('dashboard.section_definitions.fields.partials.default-value-input', [
                        'inputId' => 'default_value_shared',
                        'inputName' => 'default_value_shared',
                        'value' => $sharedDefaultValue,
                        'placeholder' => t('dashboard.Default_Value_Placeholder', 'القيمة الافتراضية'),
                    ])
                </div>

                <div data-default-translatable-panel class="{{ $isTranslatable ? '' : 'hidden' }}">
                    <div class="grid grid-cols-12 gap-x-6 gap-y-4">
                        @foreach ($locales as $locale)
                            <div class="col-span-12 md:col-span-6">
                                <label for="default_value_translations_{{ $locale['code'] }}" class="form-label">
                                    {{ t('dashboard.Default_Value', 'القيمة الافتراضية') }} ({{ $locale['label'] }})
                                </label>
                                @include(
                                    'dashboard.section_definitions.fields.partials.default-value-input',
                                    [
                                        'inputId' => 'default_value_translations_' . $locale['code'],
                                        'inputName' => 'default_value_translations[' . $locale['code'] . ']',
                                        'value' => $translatableDefaultValues[$locale['code']] ?? null,
                                        'placeholder' => strtr(
                                            t('dashboard.Default_Value_For', 'القيمة الافتراضية لـ :locale'),
                                            [':locale' => $locale['label']]
                                        ),
                                    ]
                                )
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Sidebar Column ─────────────────────────────────────────── --}}
    <div class="col-span-12 lg:col-span-5">
        <div class="card sticky top-6">
            <div class="card-header">
                <h5 class="mb-1">{{ t('dashboard.Validation_And_Options', 'التحقق والخيارات') }}</h5>
                <p class="mb-0 text-sm text-slate-500">
                    {{ t('dashboard.Validation_Desc', 'بيانات وصفية صريحة ومنظّمة. منطق العرض يبقى في الكود.') }}
                </p>
            </div>
            <div class="card-body space-y-4">

                {{-- Validation Rules --}}
                <div>
                    <label for="validation_rules" class="form-label">{{ t('dashboard.Validation', 'قواعد التحقق') }}</label>
                    <textarea id="validation_rules" name="validation_rules" class="form-control font-mono" rows="5"
                        dir="ltr" placeholder="required&#10;string&#10;max:255">{{ $validationRulesTextarea }}</textarea>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ t('dashboard.Validation_Rules_Hint', 'قاعدة واحدة في كل سطر. تُخزَّن كبيانات وصفية للتحقق مستقبلاً.') }}
                    </div>
                    @error('validation_rules')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Options --}}
                <div>
                    <label for="options" class="form-label">{{ t('dashboard.Options', 'الخيارات') }}</label>
                    <textarea id="options" name="options" class="form-control font-mono" rows="6"
                        dir="ltr" placeholder="draft|Draft&#10;published|Published">{{ $optionsTextarea }}</textarea>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ t('dashboard.Options_Hint', 'للـ select. خيار واحد في كل سطر بصيغة value|label. إذا حُذف الـ label يُستخدم الـ value.') }}
                    </div>
                    @error('options')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Settings (JSON) --}}
                <div>
                    <label for="settings" class="form-label">{{ t('dashboard.Settings', 'الإعدادات') }}</label>
                    <textarea id="settings" name="settings" class="form-control font-mono" rows="8"
                        dir="ltr" placeholder='{"placeholder":"Enter value"}'>{{ $settingsJson }}</textarea>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ t('dashboard.Settings_Hint', 'كائن JSON لبيانات وصفية إضافية مثل النصوص التوضيحية وإعدادات المحرر.') }}
                    </div>
                    @error('settings')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            {{-- Action Buttons (داخل الـ card لتبقى مرئية مع الـ sticky) --}}
            <div class="card-footer flex items-center justify-end gap-3">
                <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}"
                   class="btn btn-light">
                    {{ t('dashboard.Cancel', 'إلغاء') }}
                </a>

                @if ($isEditing)
                    <button type="submit" form="delete-field-form" class="btn btn-danger">
                        <i class="ti ti-trash me-1"></i>
                        {{ t('dashboard.Delete_Field', 'حذف الحقل') }}
                    </button>
                @endif

                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>
                    {{ $isEditing ? t('dashboard.Update_Field', 'حفظ التعديلات') : t('dashboard.Create_Field', 'إنشاء الحقل') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // ── Translatable toggle ──────────────────────────────────────────────
    const toggle = document.querySelector('[data-field-translatable-toggle]');
    const sharedPanel = document.querySelector('[data-default-shared-panel]');
    const translatablePanel = document.querySelector('[data-default-translatable-panel]');

    if (toggle && sharedPanel && translatablePanel) {
        const syncPanels = function() {
            const isChecked = toggle.checked;
            sharedPanel.classList.toggle('hidden', isChecked);
            translatablePanel.classList.toggle('hidden', !isChecked);
        };
        toggle.addEventListener('change', syncPanels);
        syncPanels();
    }

    // ── Repeater panel visibility ────────────────────────────────────────
    const typeSelect = document.getElementById('type');
    const repeaterPanel = document.querySelector('[data-repeater-panel]');

    const syncRepeaterPanel = function() {
        if (!repeaterPanel) return;
        repeaterPanel.classList.toggle('hidden', typeSelect.value !== 'repeater');
    };

    if (typeSelect) {
        typeSelect.addEventListener('change', syncRepeaterPanel);
        syncRepeaterPanel();
    }

    // ── Repeater row management ──────────────────────────────────────────
    const tbody = document.getElementById('repeater-schema-tbody');
    const addBtn = document.getElementById('repeater-add-row');

    const subTypeOptions = @json($repeaterSubFieldTypeOptions);
    let rowIndex = {{ count($repeaterItemSchema) }};

    function buildTypeSelect(name, selectedType) {
        const sel = document.createElement('select');
        sel.name = name;
        sel.className = 'form-control form-control-sm';
        Object.entries(subTypeOptions).forEach(([value, label]) => {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = label;
            if (value === (selectedType || 'text')) opt.selected = true;
            sel.appendChild(opt);
        });
        return sel;
    }

    function buildCheckboxCell(name, checked) {
        const wrapper = document.createElement('td');
        wrapper.className = 'py-2 pr-3 text-center';
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = name; hidden.value = '0';
        const cb = document.createElement('input');
        cb.type = 'checkbox'; cb.name = name; cb.value = '1';
        cb.checked = checked; cb.className = 'form-checkbox';
        wrapper.appendChild(hidden);
        wrapper.appendChild(cb);
        return wrapper;
    }

    function buildOptionsCell(name) {
        const wrapper = document.createElement('td');
        wrapper.className = 'py-2 pr-3';
        const textarea = document.createElement('textarea');
        textarea.name = name;
        textarea.className = 'form-control form-control-sm min-w-48';
        textarea.rows = 3;
        textarea.placeholder = '1x1|Normal\n2x1|Wide';
        wrapper.appendChild(textarea);
        return wrapper;
    }

    function addRow() {
        if (!tbody) return;
        const tr = document.createElement('tr');
        tr.className = 'repeater-schema-row border-b border-slate-100 align-top';

        const keyTd = document.createElement('td');
        keyTd.className = 'py-2 pr-3';
        const keyInput = document.createElement('input');
        keyInput.type = 'text';
        keyInput.name = 'item_schema[' + rowIndex + '][key]';
        keyInput.className = 'form-control form-control-sm font-mono';
        keyInput.placeholder = 'field_key';
        keyInput.pattern = '[a-z0-9_]+';
        keyTd.appendChild(keyInput);

        const labelTd = document.createElement('td');
        labelTd.className = 'py-2 pr-3';
        const labelInput = document.createElement('input');
        labelInput.type = 'text';
        labelInput.name = 'item_schema[' + rowIndex + '][label]';
        labelInput.className = 'form-control form-control-sm';
        labelInput.placeholder = '{{ t('dashboard.Field_Label', 'الاسم') }}';
        labelTd.appendChild(labelInput);

        const typeTd = document.createElement('td');
        typeTd.className = 'py-2 pr-3';
        typeTd.appendChild(buildTypeSelect('item_schema[' + rowIndex + '][type]', 'text'));

        const optionsTd = buildOptionsCell('item_schema[' + rowIndex + '][options]');
        const requiredTd = buildCheckboxCell('item_schema[' + rowIndex + '][required]', false);
        const translatableTd = buildCheckboxCell('item_schema[' + rowIndex + '][translatable]', true);

        const removeTd = document.createElement('td');
        removeTd.className = 'py-2';
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'repeater-remove-row w-7 h-7 rounded-lg inline-flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 transition';
        removeBtn.title = '{{ t('dashboard.Remove_Sub_field', 'حذف الحقل الفرعي') }}';
        removeBtn.innerHTML = '<i class="ti ti-trash text-sm leading-none"></i>';
        removeTd.appendChild(removeBtn);

        tr.appendChild(keyTd);
        tr.appendChild(labelTd);
        tr.appendChild(typeTd);
        tr.appendChild(optionsTd);
        tr.appendChild(requiredTd);
        tr.appendChild(translatableTd);
        tr.appendChild(removeTd);

        tbody.appendChild(tr);
        rowIndex++;
    }

    if (addBtn) {
        addBtn.addEventListener('click', addRow);
    }

    if (tbody) {
        tbody.addEventListener('click', function(e) {
            const btn = e.target.closest('.repeater-remove-row');
            if (btn) btn.closest('tr').remove();
        });
    }
})();
</script>
