@php
    $isEditing = $field->exists;
    $isTranslatable = old('is_translatable', $field->isTranslatable());
@endphp

<div class="grid grid-cols-12 gap-x-6 gap-y-6">
    <div class="col-span-12 lg:col-span-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">{{ __('Field Metadata') }}</h5>
                <p class="mb-0 text-sm text-slate-500">
                    {{ __('Define the base schema for this field. Keys remain developer-facing and stable over time.') }}
                </p>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-12 gap-x-6 gap-y-4">
                    <div class="col-span-12 md:col-span-6">
                        <label for="key" class="form-label">{{ __('Key') }}</label>
                        <input id="key" type="text" name="key" class="form-control"
                            value="{{ old('key', $field->field_key) }}" placeholder="headline" required>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ __('Use lowercase letters, numbers, dots, underscores, or dashes only.') }}
                        </div>
                        @error('key')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label for="label" class="form-label">{{ __('Label') }}</label>
                        <input id="label" type="text" name="label" class="form-control"
                            value="{{ old('label', $field->label) }}" placeholder="{{ __('Headline') }}" required>
                        @error('label')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label for="type" class="form-label">{{ __('Type') }}</label>
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

                    <div class="col-span-12 md:col-span-4">
                        <label for="group" class="form-label">{{ __('Group') }}</label>
                        <input id="group" type="text" name="group" class="form-control"
                            list="field-group-suggestions" value="{{ old('group', $field->group_name) }}"
                            placeholder="{{ __('content') }}">
                        <datalist id="field-group-suggestions">
                            @foreach ($groupSuggestions as $groupSuggestion)
                                <option value="{{ $groupSuggestion }}"></option>
                            @endforeach
                        </datalist>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ __('Use groups to cluster related fields in the admin UI.') }}
                        </div>
                        @error('group')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label for="sort_order" class="form-label">{{ __('Sort Order') }}</label>
                        <input id="sort_order" type="number" min="0" name="sort_order" class="form-control"
                            value="{{ old('sort_order', $field->sort_order ?? 0) }}">
                        @error('sort_order')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-span-12">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <label class="flex items-center gap-3 rounded border border-slate-200 px-4 py-3">
                                <input type="hidden" name="is_translatable" value="0">
                                <input type="checkbox" name="is_translatable" value="1" class="form-checkbox"
                                    data-field-translatable-toggle @checked($isTranslatable)>
                                <span>
                                    <span class="block font-medium text-slate-900">{{ __('Translatable') }}</span>
                                    <span class="block text-sm text-slate-500">
                                        {{ __('Enable locale-specific default values and future content storage for every active application locale.') }}
                                    </span>
                                </span>
                            </label>

                            <label class="flex items-center gap-3 rounded border border-slate-200 px-4 py-3">
                                <input type="hidden" name="is_required" value="0">
                                <input type="checkbox" name="is_required" value="1" class="form-checkbox"
                                    @checked(old('is_required', $field->is_required))>
                                <span>
                                    <span class="block font-medium text-slate-900">{{ __('Required') }}</span>
                                    <span class="block text-sm text-slate-500">
                                        {{ __('Marks the field as required for future editor validation contracts.') }}
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Repeater Sub-field Schema (Phase 5B) --}}
        <div class="card mt-6 {{ $selectedFieldType !== 'repeater' ? 'hidden' : '' }}" data-repeater-panel>
            <div class="card-header">
                <h5 class="mb-1">{{ __('Repeater Sub-fields') }}</h5>
                <p class="mb-0 text-sm text-slate-500">
                    {{ __('Define the schema for each item in this repeater. Keys must be unique within the repeater and use lowercase letters, numbers, and underscores only.') }}
                    {{ __('In V1, locale behavior is still controlled by the main field Shared/Translatable setting; the per-sub-field Translatable flag is stored as schema metadata for future phases.') }}
                </p>
            </div>
            <div class="card-body">
                @include('dashboard.section_definitions.fields.partials.repeater-item-schema-editor', [
                    'repeaterItemSchema' => $repeaterItemSchema,
                    'repeaterSubFieldTypeOptions' => $repeaterSubFieldTypeOptions,
                ])
            </div>
        </div>

        <div class="card mt-6">
            <div class="card-header">
                <h5 class="mb-1">{{ __('Default Value') }}</h5>
                <p class="mb-0 text-sm text-slate-500">
                    {{ __('Defaults are stored locale-agnostically: shared fields keep one value, while translatable fields store values by enabled locale code.') }}
                </p>
            </div>
            <div class="card-body">
                <div class="mb-4 rounded border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    {{ __('Use raw values here. For select fields, enter the option value. For boolean fields, use true/false or 1/0. For media fields, store a media ID or stable asset path reference.') }}
                </div>

                <div data-default-shared-panel class="{{ $isTranslatable ? 'hidden' : '' }}">
                    <label for="default_value_shared" class="form-label">{{ __('Shared Default Value') }}</label>
                    @include('dashboard.section_definitions.fields.partials.default-value-input', [
                        'inputId' => 'default_value_shared',
                        'inputName' => 'default_value_shared',
                        'value' => $sharedDefaultValue,
                        'placeholder' => __('Default value'),
                    ])
                </div>

                <div data-default-translatable-panel class="{{ $isTranslatable ? '' : 'hidden' }}">
                    <div class="grid grid-cols-12 gap-x-6 gap-y-4">
                        @foreach ($locales as $locale)
                            <div class="col-span-12 md:col-span-6">
                                <label for="default_value_translations_{{ $locale['code'] }}" class="form-label">
                                    {{ __('Default Value') }} ({{ $locale['label'] }})
                                </label>
                                @include(
                                    'dashboard.section_definitions.fields.partials.default-value-input',
                                    [
                                        'inputId' => 'default_value_translations_' . $locale['code'],
                                        'inputName' => 'default_value_translations[' . $locale['code'] . ']',
                                        'value' => $translatableDefaultValues[$locale['code']] ?? null,
                                        'placeholder' => __('Default value for :locale', [
                                            'locale' => $locale['label'],
                                        ]),
                                    ]
                                )
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-span-12 lg:col-span-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">{{ __('Validation And Options') }}</h5>
                <p class="mb-0 text-sm text-slate-500">
                    {{ __('Keep builder metadata explicit and normalized. Rendering logic still stays in code.') }}
                </p>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <label for="validation_rules" class="form-label">{{ __('Validation Rules') }}</label>
                    <textarea id="validation_rules" name="validation_rules" class="form-control" rows="5"
                        placeholder="required&#10;string&#10;max:255">{{ $validationRulesTextarea }}</textarea>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ __('One rule per line. These are stored as normalized metadata for future admin/editor validation flows.') }}
                    </div>
                    @error('validation_rules')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="options" class="form-label">{{ __('Options') }}</label>
                    <textarea id="options" name="options" class="form-control" rows="6"
                        placeholder="draft|Draft&#10;published|Published">{{ $optionsTextarea }}</textarea>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ __('Used primarily for select fields. Enter one option per line in the format value|label. If label is omitted, the value will be reused.') }}
                    </div>
                    @error('options')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="settings" class="form-label">{{ __('Settings') }}</label>
                    <textarea id="settings" name="settings" class="form-control font-mono" rows="8"
                        placeholder='{"placeholder":"Enter value"}'>{{ $settingsJson }}</textarea>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ __('Provide a JSON object for extra field metadata such as placeholders or editor-only flags.') }}
                    </div>
                    @error('settings')
                        <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}"
                class="btn btn-light">
                {{ __('Cancel') }}
            </a>

            @if ($isEditing)
                <button type="submit" form="delete-field-form" class="btn btn-danger">
                    {{ __('Delete Field') }}
                </button>
            @endif

            <button type="submit" class="btn btn-primary">
                {{ $isEditing ? __('Update Field') : __('Create Field') }}
            </button>
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
            if (!repeaterPanel) {
                return;
            }
            repeaterPanel.classList.toggle('hidden', typeSelect.value !== 'repeater');
        };

        if (typeSelect) {
            typeSelect.addEventListener('change', syncRepeaterPanel);
            syncRepeaterPanel();
        }

        // ── Repeater row management ──────────────────────────────────────────
        const tbody = document.getElementById('repeater-schema-tbody');
        const addBtn = document.getElementById('repeater-add-row');

        // Sub-field type options passed from Blade as a JSON map.
        const subTypeOptions = @json($repeaterSubFieldTypeOptions);

        // Row index counter — starts after the server-rendered rows so names
        // remain unique and don't clash with old() restored rows.
        let rowIndex = {{ count($repeaterItemSchema) }};

        function buildTypeSelect(name, selectedType) {
            const sel = document.createElement('select');
            sel.name = name;
            sel.className = 'form-control form-control-sm';

            Object.entries(subTypeOptions).forEach(([value, label]) => {
                const opt = document.createElement('option');
                opt.value = value;
                opt.textContent = label;
                if (value === (selectedType || 'text')) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });

            return sel;
        }

        function buildCheckboxCell(name, checked) {
            const wrapper = document.createElement('td');
            wrapper.className = 'py-2 pr-3 text-center';

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            hidden.value = '0';

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = name;
            cb.value = '1';
            cb.checked = checked;
            cb.className = 'form-checkbox';

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
            if (!tbody) {
                return;
            }

            const tr = document.createElement('tr');
            tr.className = 'repeater-schema-row border-b border-slate-100 align-top';

            // Key cell
            const keyTd = document.createElement('td');
            keyTd.className = 'py-2 pr-3';
            const keyInput = document.createElement('input');
            keyInput.type = 'text';
            keyInput.name = 'item_schema[' + rowIndex + '][key]';
            keyInput.className = 'form-control form-control-sm';
            keyInput.placeholder = 'field_key';
            keyInput.pattern = '[a-z0-9_]+';
            keyTd.appendChild(keyInput);

            // Label cell
            const labelTd = document.createElement('td');
            labelTd.className = 'py-2 pr-3';
            const labelInput = document.createElement('input');
            labelInput.type = 'text';
            labelInput.name = 'item_schema[' + rowIndex + '][label]';
            labelInput.className = 'form-control form-control-sm';
            labelInput.placeholder = '{{ __('Label') }}';
            labelTd.appendChild(labelInput);

            // Type cell
            const typeTd = document.createElement('td');
            typeTd.className = 'py-2 pr-3';
            typeTd.appendChild(buildTypeSelect('item_schema[' + rowIndex + '][type]', 'text'));

            // Options cell, primarily used when the sub-field type is select.
            const optionsTd = buildOptionsCell('item_schema[' + rowIndex + '][options]');

            // Required cell (unchecked by default)
            const requiredTd = buildCheckboxCell('item_schema[' + rowIndex + '][required]', false);

            // Translatable cell (checked by default — matches normalizeItemSchemaForPersistence)
            const translatableTd = buildCheckboxCell('item_schema[' + rowIndex + '][translatable]', true);

            // Remove button cell
            const removeTd = document.createElement('td');
            removeTd.className = 'py-2';
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'repeater-remove-row rounded px-2 py-1 text-xs text-red-500 hover:bg-red-50';
            removeBtn.title = '{{ __('Remove sub-field') }}';
            removeBtn.textContent = '×';
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
                if (btn) {
                    btn.closest('tr').remove();
                }
            });
        }
    })();
</script>
