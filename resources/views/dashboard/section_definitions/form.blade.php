@php
    $isEditing = $sectionDefinition->exists;
@endphp

<div class="grid grid-cols-12 gap-x-6 gap-y-4">
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

    <div class="col-span-12 md:col-span-4">
        <label for="template_key" class="form-label">{{ __('Template Key') }}</label>
        <select id="template_key" name="template_key" class="form-control">
            <option value="">{{ __('No Template Selected') }}</option>
            @foreach ($templateOptions as $templateKey => $templateOption)
                <option value="{{ $templateKey }}" @selected($selectedTemplateKey === $templateKey)>
                    {{ $templateOption['label'] }} ({{ $templateKey }})
                </option>
            @endforeach
        </select>
        <div class="mt-1 text-xs text-slate-500">
            {{ __('Template keys come from the code-side registry. The database stores only the selected reference.') }}
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
        <select id="editor_mode" name="editor_mode" class="form-control" required>
            @foreach ($editorModeOptions as $editorModeValue => $editorModeLabel)
                <option value="{{ $editorModeValue }}" @selected(old('editor_mode', $sectionDefinition->editor_mode) === $editorModeValue)>
                    {{ $editorModeLabel }}
                </option>
            @endforeach
        </select>
        <div class="mt-1 text-xs text-slate-500">
            {{ __('Dynamic mode will use normalized definitions later. Custom mode reserves a dedicated preset key.') }}
        </div>
        @error('editor_mode')
            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="custom_editor_key" class="form-label">{{ __('Custom Editor Key') }}</label>
        <input
            id="custom_editor_key"
            type="text"
            name="custom_editor_key"
            class="form-control"
            value="{{ old('custom_editor_key', $sectionDefinition->custom_editor_key) }}"
            placeholder="header_editor"
        >
        <div class="mt-1 text-xs text-slate-500">
            {{ __('Leave empty unless this definition should use a dedicated custom preset later.') }}
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
                    <span class="block text-sm text-slate-500">{{ __('This controls discoverability in future admin section selection interfaces.') }}</span>
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
        <button type="submit" class="btn btn-primary">
            {{ $isEditing ? __('Update Definition') : __('Create Definition') }}
        </button>
    </div>
</div>
