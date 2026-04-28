@php
    $workspaceRoutePrefix = $workspaceRoutePrefix ?? 'dashboard.pages.sections.';
    $workspaceRouteBaseParameters = $workspaceRouteBaseParameters ?? ['page' => $page];
    $workspaceRouteFor =
        $workspaceRouteFor ??
        fn(string $name, array $extra = [], bool $absolute = true) => route(
            $workspaceRoutePrefix . $name,
            array_merge($workspaceRouteBaseParameters, $extra),
            $absolute,
        );
    $formId = $formId ?? 'section-edit-form';
    $formAction = $formAction ?? $workspaceRouteFor('update', ['section' => $section], false);
    $saveAction = $saveAction ?? $formAction;
    $formClass = $formClass ?? 'space-y-6';
    $formMethod = $formMethod ?? 'POST';
    $formMethodSpoof = $formMethodSpoof ?? 'PUT';
    $preventNativeSubmit = $preventNativeSubmit ?? false;
    $surfaceClass = $surfaceClass ?? 'rounded-3xl border border-slate-200 bg-white shadow-sm';
    $sectionHeaderClass = $sectionHeaderClass ?? 'border-b border-slate-200 px-5 py-4 lg:px-6';
    $sectionBodyClass = $sectionBodyClass ?? 'p-5 lg:p-6';
    $settingsGridClass = $settingsGridClass ?? 'grid grid-cols-1 gap-5 lg:grid-cols-2';
    $contentGridClass = $contentGridClass ?? 'grid grid-cols-1 gap-5 lg:grid-cols-2';
    $showOrderField = $showOrderField ?? true;
    $feedbackMessage = $feedbackMessage ?? null;
    $feedbackTone = $feedbackTone ?? 'success';
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $editorState = $editorState ?? [];
    $editorDefaultLocale = $editorState['defaultLocale'] ?? app()->getLocale();
    $workspaceMode = $workspaceMode ?? 'admin';
    $isClientWorkspace = $workspaceMode === 'client';
    $displayOrderLabel = $isClientWorkspace ? __('Block Order') : __('Display Order');
    $displayOrderHelp = $isClientWorkspace ? __('Lower numbers appear earlier on the page.') : null;
    $activeToggleLabel = $isClientWorkspace ? __('Show this block on your website') : __('Active on frontend');
    $contentSectionLabel = $isClientWorkspace ? __('Block Content') : __('Section Content');
    $contentSectionHelp = $isClientWorkspace
        ? __('Update the text, media, and settings for this block in each language.')
        : __('Edit localized content for each language.');
@endphp

<form id="{{ $formId }}" method="{{ strtoupper($formMethod) }}" action="{{ $formAction }}"
    class="{{ $formClass }}" data-section-editor-form data-section-id="{{ $section->id }}"
    data-default-editor-tab="lang-{{ $editorDefaultLocale }}" data-save-action="{{ $saveAction }}"
    @if ($preventNativeSubmit) onsubmit="return false;" @endif>
    @csrf
    @if ($formMethodSpoof)
        @method($formMethodSpoof)
    @endif

    @php
        $feedbackVisible = $viewErrors->any() || filled($feedbackMessage);
        $feedbackClasses =
            $feedbackTone === 'error'
                ? 'border-red-200 bg-red-50 text-red-800'
                : 'border-emerald-200 bg-emerald-50 text-emerald-800';
        $selectedType = $editorState['selectedType'] ?? $section->type;
        $usesInternalLabel = (bool) ($editorState['usesInternalLabel'] ?? false);
        $dynamicEditor = is_array($editorState['dynamicEditor'] ?? null) ? $editorState['dynamicEditor'] : [];
    @endphp

    <div data-section-editor-feedback
        class="hidden rounded-2xl border px-4 py-3 text-sm {{ $feedbackVisible ? $feedbackClasses : 'border-slate-200 bg-slate-50 text-slate-600' }}">
        <ul class="{{ $feedbackVisible ? '' : 'hidden ' }}space-y-1" data-section-editor-feedback-list>
            @if (filled($feedbackMessage))
                <li>{{ $feedbackMessage }}</li>
            @endif

            @foreach ($viewErrors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>

    <div class="{{ $surfaceClass }}">
        <div class="{{ $sectionBodyClass }}">
            <input type="hidden" name="type" value="{{ $selectedType }}">
            <input type="hidden" name="section_definition_id" value="{{ $section->section_definition_id }}">
            <input type="hidden" name="variant" value="{{ old('variant', $section->variant) }}">

            @if ($showOrderField)
                <div class="{{ $settingsGridClass }}">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ $displayOrderLabel }}</label>
                        <input type="number" name="order" value="{{ old('order', $section->order ?? 1) }}"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                        @if (filled($displayOrderHelp))
                            <p class="mt-2 text-xs text-slate-500">{{ $displayOrderHelp }}</p>
                        @endif
                    </div>

                    <div class="flex items-center">
                        <label
                            class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
                                {{ old('is_active', $section->is_active) ? 'checked' : '' }}>
                            {{ $activeToggleLabel }}
                        </label>
                    </div>
                </div>
            @else
                <input type="hidden" name="order" value="{{ old('order', $section->order ?? 1) }}">
                <input type="hidden" name="is_active" value="{{ old('is_active', $section->is_active) ? '1' : '0' }}">
            @endif
        </div>
    </div>

    <div class="{{ $surfaceClass }}">
        <div class="{{ $sectionHeaderClass }}">
            <h2 class="text-lg font-semibold text-slate-900">{{ $contentSectionLabel }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $contentSectionHelp }}</p>
        </div>

        <div class="{{ $sectionBodyClass }}">
            <div class="mb-5 border-b border-slate-200">
                <nav class="-mb-px flex flex-wrap gap-2" aria-label="Language tabs">
                    @foreach ($languages as $language)
                        @php
                            $active = $language->code === $editorDefaultLocale;
                        @endphp
                        <button type="button"
                            class="rounded-t-2xl border-b-2 px-4 py-2 text-sm font-medium transition {{ $active ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' }}"
                            data-editor-tab-button data-tab="lang-{{ $language->code }}">
                            {{ $language->name }} ({{ $language->code }})
                        </button>
                    @endforeach
                </nav>
            </div>

            @foreach ($languages as $language)
                @php
                    $code = $language->code;
                    $localeScalarValues = $editorState['localeScalarValues'][$code] ?? [];
                    $sectionTitleValue = $localeScalarValues['sectionTitleValue'] ?? '';
                @endphp

                <div id="lang-{{ $code }}" data-editor-tab-panel
                    class="{{ $code === $editorDefaultLocale ? '' : 'hidden' }}">
                    <input type="hidden" name="translations[{{ $code }}][locale]"
                        value="{{ $code }}">

                    @include('dashboard.pages.sections.partials.dynamic-editor.renderer', [
                        'code' => $code,
                        'dynamicEditor' => $dynamicEditor,
                        'contentGridClass' => $contentGridClass,
                        'usesInternalLabel' => $usesInternalLabel,
                        'sectionTitleValue' => $sectionTitleValue,
                    ])
                </div>
            @endforeach
        </div>
    </div>
</form>
