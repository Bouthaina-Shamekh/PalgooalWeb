@php
    $isEditing = $sectionDefinition->exists;
    $selectedTemplateOption = is_array($templateOptions[$selectedTemplateKey] ?? null)
        ? $templateOptions[$selectedTemplateKey]
        : null;
    $selectedTemplateMeta = is_array($selectedTemplateMeta ?? null)
        ? $selectedTemplateMeta
        : $selectedTemplateOption;
    $selectedCustomPresetOption = is_array($customPresetOptions[$selectedCustomEditorKey] ?? null)
        ? $customPresetOptions[$selectedCustomEditorKey]
        : null;
    $selectedDefinitionCategory = old('category', $sectionDefinition->category);
    $templateOptionSummaries = collect($templateOptions)
        ->mapWithKeys(fn ($templateOption, $templateKey) => [
            $templateKey => [
                'label' => $templateOption['label'] ?? $templateKey,
                'view' => $templateOption['view'] ?? '',
                'category' => $templateOption['category'] ?? '',
                'source' => $templateOption['resolution_source'] ?? 'registry',
            ],
        ])
        ->all();
@endphp

<div class="grid grid-cols-12 gap-x-6 gap-y-4">
    <div class="col-span-12">
        <div class="rounded border border-slate-200 bg-slate-50 px-4 py-4">
            <h6 class="mb-2 text-sm font-semibold text-slate-900">{{ __('Recommended workflow for a normal reusable section') }}</h6>
            <p class="mb-0 text-sm text-slate-600">
                {{ __('Keep Editor Mode on Dynamic, enter a stable Category and Template Key, save the definition, then continue to field definitions. Runtime will first honor any code-side override and otherwise try the convention view front.sections.{category}.{template_key} without storing a Blade path in the database.') }}
            </p>
        </div>
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="name" class="form-label">{{ __('Name') }}</label>
        <input
            id="name"
            type="text"
            name="name"
            class="form-control"
            value="{{ old('name', $sectionDefinition->label) }}"
            placeholder="{{ __('Section Definition Name') }}"
            required
        >
        @error('name')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="key" class="form-label">{{ __('Key') }}</label>
        <input
            id="key"
            type="text"
            name="key"
            class="form-control"
            value="{{ old('key', $sectionDefinition->section_key) }}"
            placeholder="hero_default"
            required
        >
        <div class="mt-1 text-xs text-slate-500">
            {{ __('Use a stable developer key with lowercase letters, numbers, underscores, or dashes only.') }}
        </div>
        @error('key')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-span-12">
        <label for="description" class="form-label">{{ __('Description') }}</label>
        <textarea
            id="description"
            name="description"
            class="form-control"
            rows="3"
            placeholder="{{ __('Internal description for maintainers and admin users.') }}"
        >{{ old('description', $sectionDefinition->description) }}</textarea>
        @error('description')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-span-12 md:col-span-4">
        <label for="category" class="form-label">{{ __('Category') }}</label>
        <input
            id="category"
            type="text"
            name="category"
            class="form-control"
            value="{{ old('category', $sectionDefinition->category) }}"
            placeholder="{{ __('hero, services, pricing') }}"
        >
        @error('category')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-span-12 md:col-span-8">
        <x-dashboard.media-picker
            id="preview_media_id"
            name="preview_media_id"
            :label="__('Preview Image')"
            :button-text="__('Choose From Media Library')"
            :value="$previewMediaValue"
            :preview-urls="$previewMediaPreviewUrls"
            store-value="id"
            class="col-span-12"
        />
        <div class="mt-1 text-xs text-slate-500">
            {{ __('Used for admin library cards. This does not change frontend rendering.') }}
        </div>
        @error('preview_media_id')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-span-12 md:col-span-4">
        <label for="template_key" class="form-label">{{ __('Template Key') }}</label>
        <input
            id="template_key"
            type="text"
            name="template_key"
            class="form-control"
            list="template_key_suggestions"
            value="{{ $selectedTemplateKey }}"
            placeholder="hero_default"
            data-template-key-input
        >
        <datalist id="template_key_suggestions">
            @foreach ($templateOptions as $templateKey => $templateOption)
                <option value="{{ $templateKey }}">{{ $templateOption['label'] }} ({{ $templateKey }})</option>
            @endforeach
        </datalist>
        <div class="mt-1 text-xs text-slate-500">
            {{ __('Enter any safe stable key using lowercase letters, numbers, underscores, or dashes. If no explicit code-side override is registered, runtime will try front.sections.{category}.{template_key}.') }}
        </div>
        <div class="mt-3 rounded border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-600" data-template-selection-summary>
            <div class="font-medium text-slate-900" data-template-selection-title>
                @if ($selectedTemplateKey)
                    {{ ($selectedTemplateMeta['resolution_source'] ?? null) === 'registry'
                        ? __('Code override available')
                        : __('Convention-based renderer key') }}
                @else
                    {{ __('No template selected yet') }}
                @endif
            </div>
            <div class="mt-1" data-template-selection-meta>
                @if ($selectedTemplateKey)
                    {{ $selectedTemplateMeta['label'] ?? \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', $selectedTemplateKey)) }} / {{ $selectedTemplateKey }}
                    @if (! empty($selectedTemplateMeta['category'] ?? null))
                        / {{ \Illuminate\Support\Str::headline($selectedTemplateMeta['category']) }}
                    @endif
                @else
                    {{ __('Dynamic definitions should select a template key before field work begins.') }}
                @endif
            </div>
            <div class="mt-1 text-xs text-slate-500" data-template-selection-view>
                @if ($selectedTemplateKey)
                    {{ __('Renderer candidate: :view', ['view' => $selectedTemplateMeta['view'] ?? ('front.sections.' . \App\Support\Sections\SectionTemplateRegistry::normalizeCategory($selectedDefinitionCategory) . '.' . $selectedTemplateKey)]) }}
                @else
                    {{ __('Frontend view resolution stays code-side and is derived later from the selected template key.') }}
                @endif
            </div>
        </div>
        @error('template_key')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-span-12 md:col-span-4">
        <label for="sort_order" class="form-label">{{ __('Sort Order') }}</label>
        <input
            id="sort_order"
            type="number"
            min="0"
            name="sort_order"
            class="form-control"
            value="{{ old('sort_order', $sectionDefinition->sort_order ?? 0) }}"
        >
        @error('sort_order')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="editor_mode" class="form-label">{{ __('Editor Mode') }}</label>
        <select id="editor_mode" name="editor_mode" class="form-control" required data-editor-mode-select>
            @foreach ($editorModeOptions as $editorModeValue => $editorModeLabel)
                <option value="{{ $editorModeValue }}" @selected($selectedEditorMode === $editorModeValue)>
                    {{ $editorModeLabel }}
                </option>
            @endforeach
        </select>
        <div class="mt-1 text-xs text-slate-500">
            {{ __('Dynamic uses the selected template key plus field definitions. Custom preset uses a dedicated preset already registered in code.') }}
        </div>
        <div class="mt-3 rounded border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-600">
            <div class="{{ $selectedEditorMode === \App\Models\Sections\SectionDefinition::EDITOR_MODE_DYNAMIC ? '' : 'hidden' }}" data-editor-mode-panel="dynamic">
                <div class="font-medium text-slate-900">{{ __('Dynamic definition workflow') }}</div>
                <div class="mt-1">{{ __('This is the normal path for new reusable sections: enter a template key here, save the definition, then add field definitions.') }}</div>
            </div>
            <div class="{{ $selectedEditorMode === \App\Models\Sections\SectionDefinition::EDITOR_MODE_CUSTOM_PRESET ? '' : 'hidden' }}" data-editor-mode-panel="custom_preset">
                <div class="font-medium text-slate-900">{{ __('Custom preset workflow') }}</div>
                <div class="mt-1">{{ __('Use this only when a matching custom preset editor already exists in the code registry. Field definitions can still be stored, but the preset remains the primary editing contract.') }}</div>
            </div>
        </div>
        @error('editor_mode')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-span-12 md:col-span-6 {{ $selectedEditorMode === \App\Models\Sections\SectionDefinition::EDITOR_MODE_CUSTOM_PRESET ? '' : 'hidden' }}" data-custom-preset-panel>
        <label for="custom_editor_key" class="form-label">{{ __('Custom Editor Key') }}</label>
        <select id="custom_editor_key" name="custom_editor_key" class="form-control">
            <option value="">{{ __('No Custom Preset Selected') }}</option>
            @foreach ($customPresetOptions as $presetKey => $presetOption)
                <option
                    value="{{ $presetKey }}"
                    data-preset-label="{{ $presetOption['label'] }}"
                    data-preset-description="{{ $presetOption['description'] ?? '' }}"
                    @selected($selectedCustomEditorKey === $presetKey)
                >
                    {{ $presetOption['label'] }} ({{ $presetKey }})
                </option>
            @endforeach
        </select>
        <div class="mt-1 text-xs text-slate-500">
            {{ __('Select only from the registered custom preset keys. Dynamic sections normally leave this empty.') }}
        </div>
        <div class="mt-3 rounded border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-600">
            <div class="font-medium text-slate-900" data-custom-preset-summary-title>
                {{ $selectedCustomPresetOption ? __('Registered preset selected') : __('No custom preset selected') }}
            </div>
            <div class="mt-1" data-custom-preset-summary-description>
                {{ $selectedCustomPresetOption['description'] ?? __('Choose a registered preset only when this definition should use a dedicated code-side editor.') }}
            </div>
        </div>
        @error('custom_editor_key')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-span-12">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <label class="flex items-center gap-3 rounded border border-slate-200 px-4 py-3">
                <input type="hidden" name="is_active" value="0">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="form-checkbox"
                    @checked(old('is_active', $sectionDefinition->is_active))
                >
                <span>
                    <span class="block font-medium text-slate-900">{{ __('Active') }}</span>
                    <span class="block text-sm text-slate-500">{{ __('Inactive definitions stay stored but should not be offered in future admin tooling.') }}</span>
                </span>
            </label>

            <label class="flex items-center gap-3 rounded border border-slate-200 px-4 py-3">
                <input type="hidden" name="is_visible_in_library" value="0">
                <input
                    type="checkbox"
                    name="is_visible_in_library"
                    value="1"
                    class="form-checkbox"
                    @checked(old('is_visible_in_library', $sectionDefinition->is_visible))
                >
                <span>
                    <span class="block font-medium text-slate-900">{{ __('Visible In Library') }}</span>
                    <span class="block text-sm text-slate-500">{{ __('Enable this together with Active so the definition can appear in the admin section library.') }}</span>
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

    <div class="col-span-12 mt-2 flex items-center justify-end gap-3">
        <a href="{{ route('dashboard.section_definitions.index') }}" class="btn btn-light">
            {{ __('Cancel') }}
        </a>
        @if ($isEditing)
            <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}" class="btn btn-light-primary">
                {{ __('Manage Fields') }}
            </a>
            <button type="submit" name="after_save" value="fields" class="btn btn-light-primary">
                {{ __('Update And Manage Fields') }}
            </button>
        @endif
        <button type="submit" class="btn btn-primary">
            {{ $isEditing ? __('Update Definition') : __('Create Definition And Continue') }}
        </button>
    </div>
</div>

<script>
    (function() {
        const editorModeSelect = document.querySelector('[data-editor-mode-select]');
        const editorModePanels = document.querySelectorAll('[data-editor-mode-panel]');
        const customPresetPanel = document.querySelector('[data-custom-preset-panel]');
        const templateInput = document.querySelector('[data-template-key-input]');
        const templateSummaryTitle = document.querySelector('[data-template-selection-title]');
        const templateSummaryMeta = document.querySelector('[data-template-selection-meta]');
        const templateSummaryView = document.querySelector('[data-template-selection-view]');
        const categoryInput = document.getElementById('category');
        const customPresetSelect = document.getElementById('custom_editor_key');
        const customPresetSummaryTitle = document.querySelector('[data-custom-preset-summary-title]');
        const customPresetSummaryDescription = document.querySelector('[data-custom-preset-summary-description]');
        const templateOptions = @json($templateOptionSummaries);

        const syncEditorMode = function() {
            if (!editorModeSelect) {
                return;
            }

            const selectedMode = editorModeSelect.value;

            editorModePanels.forEach(function(panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-editor-mode-panel') !== selectedMode);
            });

            if (customPresetPanel) {
                customPresetPanel.classList.toggle('hidden', selectedMode !== 'custom_preset');
            }
        };

        const normalizeCategory = function(category) {
            const normalizedCategory = String(category || '').trim().toLowerCase();

            return /^[a-z0-9_-]+$/.test(normalizedCategory) ? normalizedCategory : 'uncategorized';
        };

        const buildConventionView = function(templateKey) {
            return 'front.sections.' + normalizeCategory(categoryInput ? categoryInput.value : '') + '.' + templateKey;
        };

        const buildTemplateLabel = function(templateKey) {
            return templateKey
                .replace(/[_-]+/g, ' ')
                .replace(/\b\w/g, function(match) {
                    return match.toUpperCase();
                });
        };

        const syncTemplateSummary = function() {
            if (!templateInput || !templateSummaryTitle || !templateSummaryMeta || !templateSummaryView) {
                return;
            }

            const selectedValue = templateInput.value.trim();

            if (!selectedValue) {
                templateSummaryTitle.textContent = '{{ __('No template selected yet') }}';
                templateSummaryMeta.textContent = '{{ __('Dynamic definitions should select a template key before field work begins.') }}';
                templateSummaryView.textContent = '{{ __('Frontend view resolution stays code-side and is derived later from the selected template key.') }}';
                return;
            }

            const templateOption = templateOptions[selectedValue] || null;
            const templateLabel = templateOption ? templateOption.label : buildTemplateLabel(selectedValue);
            const templateCategory = templateOption ? templateOption.category : '';
            const templateView = templateOption && templateOption.view
                ? templateOption.view
                : buildConventionView(selectedValue);
            const templateSource = templateOption ? templateOption.source : 'convention';
            const summaryParts = [templateLabel, selectedValue];

            if (templateCategory) {
                summaryParts.push(templateCategory.replace(/[_-]+/g, ' '));
            }

            templateSummaryTitle.textContent = templateSource === 'registry'
                ? '{{ __('Code override available') }}'
                : '{{ __('Convention-based renderer key') }}';
            templateSummaryMeta.textContent = summaryParts.join(' / ');
            templateSummaryView.textContent = '{{ __('Renderer candidate:') }}' + ' ' + templateView;
        };

        const syncCustomPresetSummary = function() {
            if (!customPresetSelect || !customPresetSummaryTitle || !customPresetSummaryDescription) {
                return;
            }

            const selectedOption = customPresetSelect.options[customPresetSelect.selectedIndex];
            const selectedValue = selectedOption ? selectedOption.value : '';

            if (!selectedValue) {
                customPresetSummaryTitle.textContent = '{{ __('No custom preset selected') }}';
                customPresetSummaryDescription.textContent = '{{ __('Choose a registered preset only when this definition should use a dedicated code-side editor.') }}';
                return;
            }

            customPresetSummaryTitle.textContent = '{{ __('Registered preset selected') }}';
            customPresetSummaryDescription.textContent = selectedOption.getAttribute('data-preset-description')
                || '{{ __('This preset stays code-side and is activated here by its stable key only.') }}';
        };

        if (editorModeSelect) {
            editorModeSelect.addEventListener('change', syncEditorMode);
            syncEditorMode();
        }

        if (templateInput) {
            templateInput.addEventListener('input', syncTemplateSummary);
            templateInput.addEventListener('change', syncTemplateSummary);
            syncTemplateSummary();
        }

        if (categoryInput) {
            categoryInput.addEventListener('input', syncTemplateSummary);
            categoryInput.addEventListener('change', syncTemplateSummary);
        }

        if (customPresetSelect) {
            customPresetSelect.addEventListener('change', syncCustomPresetSummary);
            syncCustomPresetSummary();
        }
    })();
</script>
