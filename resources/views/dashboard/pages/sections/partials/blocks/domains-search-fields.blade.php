{{-- Domains search fields: extracted partial for the bounded search-oriented region; keeps heading, description, and placeholder behavior together. --}}
@php
    $isDomainsSearchContext = $isDomainsShowcase;
    $domainsSearchWrapperClass = 'lg:col-span-2';
    $domainsSearchDescriptionLabel = __('Search Box Description');
    $domainsSearchPlaceholderLabel = __('Input Placeholder');
    $domainsSearchPlaceholderExample = __('enter your domain here...');
    $searchHeadingFieldContext = $schemaFieldContext(
        'content',
        'search_heading',
        __('Search Box Title'),
        __('Find your perfect Domain name'),
    );
    $searchHeadingRenderConfig = $schemaRenderableFieldConfig(
        $searchHeadingFieldContext,
        'text',
        3,
    );
@endphp

@if ($showDomainsSearchHeadingField)
    @include(
        'dashboard.pages.sections.partials.fields.schema-field-renderer',
        $schemaRendererPayload(
            $searchHeadingRenderConfig,
            'translations[' . $code . '][content][search_heading]',
            $domainsSearchHeadingValue,
            'search_heading',
            $domainsSearchWrapperClass,
        )
    )
@endif

@if ($showDescriptionField)
    @php
        $descriptionFieldContext = $schemaFieldContext(
            'content',
            'description',
            $isDomainsSearchContext ? $domainsSearchDescriptionLabel : __('Description'),
            null,
        );
    @endphp

    @include('dashboard.pages.sections.partials.fields.schema-field-renderer', [
        'fieldType' => 'textarea',
        'label' => $descriptionFieldContext['label'],
        'name' => 'translations[' . $code . '][content][description]',
        'value' => $descriptionValue,
        'placeholder' => null,
        'rows' => $schemaFieldRows($descriptionFieldContext['schemaMeta'], 4),
        'schemaField' => 'description',
        'schemaMeta' => $descriptionFieldContext['schemaMeta'],
        'wrapperClass' => $domainsSearchWrapperClass,
    ])
@endif

@if ($showDomainsPlaceholderField)
    <div class="{{ $domainsSearchWrapperClass }}">
        <label class="block text-sm font-medium text-slate-700">{{ $domainsSearchPlaceholderLabel }}</label>
        <input type="text"
            name="translations[{{ $code }}][content][input_placeholder]"
            value="{{ $domainsInputPlaceholderValue }}"
            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
            placeholder="{{ $domainsSearchPlaceholderExample }}">
    </div>
@endif
