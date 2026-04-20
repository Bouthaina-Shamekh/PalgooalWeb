@php
    $isEditing = $sectionDefinition->exists;
    $selectedTemplateOption = is_array($templateOptions[$selectedTemplateKey] ?? null)
        ? $templateOptions[$selectedTemplateKey]
        : null;
    $selectedCustomPresetOption = is_array($customPresetOptions[$selectedCustomEditorKey] ?? null)
        ? $customPresetOptions[$selectedCustomEditorKey]
        : null;
@endphp

<div class="grid grid-cols-12 gap-x-6 gap-y-4">
    <div class="col-span-12">
        <div class="rounded border border-slate-200 bg-slate-50 px-4 py-4">
            <h6 class="mb-2 text-sm font-semibold text-slate-900">{{ __('Recommended workflow for a normal reusable section') }}</h6>
            <p class="mb-0 text-sm text-slate-600">
                {{ __('Keep Editor Mode on Dynamic, choose a registered Template Key, save the definition, then continue to field definitions. Once the definition is active and visible in the library, it can appear in the section library without adding a new config/sections.php entry.') }}
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
        <select id="template_key" name="template_key" class="form-control" data-template-key-select>
            <option value="">{{ __('No Template Selected') }}</option>
            @foreach ($templateOptions as $templateKey => $templateOption)
                <option
                    value="{{ $templateKey }}"
                    data-template-label="{{ $templateOption['label'] }}"
                    data-template-view="{{ $templateOption['view'] }}"
                    data-template-category="{{ $templateOption['category'] ?? '' }}"
                    @selected($selectedTemplateKey === $templateKey)
                >
                    {{ $templateOption['label'] }} ({{ $templateKey }})
                </option>
            @endforeach
        </select>
        <div class="mt-1 text-xs text-slate-500">
            {{ __('Choose a registered template key from the existing code-side registry. The database stores only the stable reference, not the Blade path.') }}
        </div>
        <div class="mt-3 rounded border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-600" data-template-selection-summary>
            <div class="font-medium text-slate-900" data-template-selection-title>
                {{ $selectedTemplateOption ? __('Registered template selected') : __('No template selected yet') }}
            </div>
            <div class="mt-1" data-template-selection-meta>
                @if ($selectedTemplateOption)
                    {{ $selectedTemplateOption['label'] }} / {{ $selectedTemplateKey }}
                    @if (! empty($selectedTemplateOption['category']))
                        / {{ \Illuminate\Support\Str::headline($selectedTemplateOption['category']) }}
                    @endif
                @else
                    {{ __('Dynamic definitions should usually select a registered template key before field work begins.') }}
                @endif
            </div>
            <div class="mt-1 text-xs text-slate-500" data-template-selection-view>
                @if ($selectedTemplateOption)
                    {{ __('Resolved in code to view: :view', ['view' => $selectedTemplateOption['view']]) }}
                @else
                    {{ __('Frontend view mapping remains code-side and is resolved later from the selected template key.') }}
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
                <div class="mt-1">{{ __('This is the normal path for new reusable sections: select a template key here, save the definition, then add field definitions.') }}</div>
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
        const templateSelect = document.querySelector('[data-template-key-select]');
        const templateSummaryTitle = document.querySelector('[data-template-selection-title]');
        const templateSummaryMeta = document.querySelector('[data-template-selection-meta]');
        const templateSummaryView = document.querySelector('[data-template-selection-view]');
        const customPresetSelect = document.getElementById('custom_editor_key');
        const customPresetSummaryTitle = document.querySelector('[data-custom-preset-summary-title]');
        const customPresetSummaryDescription = document.querySelector('[data-custom-preset-summary-description]');

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

        const syncTemplateSummary = function() {
            if (!templateSelect || !templateSummaryTitle || !templateSummaryMeta || !templateSummaryView) {
                return;
            }

            const selectedOption = templateSelect.options[templateSelect.selectedIndex];
            const selectedValue = selectedOption ? selectedOption.value : '';

            if (!selectedValue) {
                templateSummaryTitle.textContent = '{{ __('No template selected yet') }}';
                templateSummaryMeta.textContent = '{{ __('Dynamic definitions should usually select a registered template key before field work begins.') }}';
                templateSummaryView.textContent = '{{ __('Frontend view mapping remains code-side and is resolved later from the selected template key.') }}';
                return;
            }

            const templateLabel = selectedOption.getAttribute('data-template-label') || selectedValue;
            const templateCategory = selectedOption.getAttribute('data-template-category') || '';
            const templateView = selectedOption.getAttribute('data-template-view') || '';
            const summaryParts = [templateLabel, selectedValue];

            if (templateCategory) {
                summaryParts.push(templateCategory.replace(/[_-]+/g, ' '));
            }

            templateSummaryTitle.textContent = '{{ __('Registered template selected') }}';
            templateSummaryMeta.textContent = summaryParts.join(' / ');
            templateSummaryView.textContent = templateView
                ? '{{ __('Resolved in code to view:') }}' + ' ' + templateView
                : '{{ __('Frontend view mapping remains code-side and is resolved later from the selected template key.') }}';
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

        if (templateSelect) {
            templateSelect.addEventListener('change', syncTemplateSummary);
            syncTemplateSummary();
        }

        if (customPresetSelect) {
            customPresetSelect.addEventListener('change', syncCustomPresetSummary);
            syncCustomPresetSummary();
        }
    })();
</script>
