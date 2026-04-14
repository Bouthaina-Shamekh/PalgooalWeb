@php
    $pv = \App\Support\Sections\SectionPresetEditorValues::for($customPresetEditor, $code);
    $titleValue      = $pv->scalar('titleValue');
    $subtitleValue   = $pv->scalar('subtitleValue');
    $protectionItems = $pv->items('protectionItems');
@endphp
<div class="{{ $contentGridClass }}">
    @if ($usesInternalLabel)
        <input type="hidden" name="translations[{{ $code }}][title]" value="{{ $sectionTitleValue }}">
    @else
        @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
            'label' => __('Section Title') . ' (' . $code . ')',
            'name'  => 'translations[' . $code . '][title]',
            'value' => $sectionTitleValue,
        ])
    @endif

    <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/60 p-5">
        <div class="mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                {{ __('Main Content') }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Edit the headline and supporting text shown in the Website Protection section.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label'       => __('Title'),
                'name'        => 'translations[' . $code . '][content][title]',
                'value'       => $titleValue,
                'placeholder' => __('e.g. Website Protection Features'),
            ])

            @include('dashboard.pages.sections.partials.custom-presets.fields._textarea-field', [
                'label'       => __('Subtitle'),
                'name'        => 'translations[' . $code . '][content][subtitle]',
                'value'       => $subtitleValue,
                'rows'        => 3,
                'placeholder' => __('e.g. Comprehensive security features to keep your website safe.'),
            ])
        </div>
    </div>

    @include('dashboard.pages.sections.partials.repeaters.protection-items-repeater', [
        'code' => $code,
        'protectionItems' => $protectionItems,
        'mediaPreviewBuilder' => $mediaPreviewBuilder,
        'itemRepeaterHeading' => __('Protection Cards'),
        'itemRepeaterDescription' => __('Manage the feature cards shown in the Website Protection section.'),
        'itemRepeaterAddLabel' => __('Add Card'),
        'itemRepeaterEmptyState' => __('No cards yet. Add the first one to build the grid.'),
        'itemRepeaterTitlePlaceholder' => __('e.g. Automatic malware removal'),
        'itemRepeaterDescPlaceholder' => __('e.g. We scan for threats and remove malicious files.'),
    ])
</div>
