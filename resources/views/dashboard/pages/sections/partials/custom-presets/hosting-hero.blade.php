@php
    $pv = \App\Support\Sections\SectionPresetEditorValues::for($customPresetEditor, $code);
    $breadcrumbHomeLabelValue    = $pv->scalar('breadcrumbHomeLabelValue');
    $breadcrumbHomeUrlValue      = $pv->scalar('breadcrumbHomeUrlValue');
    $breadcrumbCurrentLabelValue = $pv->scalar('breadcrumbCurrentLabelValue');
    $titleValue                  = $pv->scalar('titleValue');
    $subtitleValue               = $pv->scalar('subtitleValue');
    $featureItems                = $pv->items('featureItems');
    $cardTitleValue              = $pv->scalar('cardTitleValue');
    $cardButtonLabelValue        = $pv->scalar('cardButtonLabelValue');
    $cardButtonUrlValue          = $pv->scalar('cardButtonUrlValue');
    $backgroundImageValue        = $pv->mediaId('backgroundImageValue');
    $backgroundImagePreviewUrls  = $pv->items('backgroundImagePreviewUrls');
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
                {{ __('Breadcrumb') }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Configure the breadcrumb labels shown at the top of the hosting hero section.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label'       => __('Home Label'),
                'name'        => 'translations[' . $code . '][content][breadcrumb_home_label]',
                'value'       => $breadcrumbHomeLabelValue,
                'placeholder' => __('Home'),
                'colSpan'     => '',
            ])

            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label'       => __('Home URL'),
                'name'        => 'translations[' . $code . '][content][breadcrumb_home_url]',
                'value'       => $breadcrumbHomeUrlValue,
                'placeholder' => 'index.html',
                'type'        => 'url',
                'colSpan'     => '',
            ])

            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label'       => __('Current Label'),
                'name'        => 'translations[' . $code . '][content][breadcrumb_current_label]',
                'value'       => $breadcrumbCurrentLabelValue,
                'placeholder' => __('Hosting'),
            ])
        </div>
    </div>

    <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/60 p-5">
        <div class="mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                {{ __('Main Content') }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Edit the core hero message shown in the main content area.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label' => __('Title'),
                'name'  => 'translations[' . $code . '][content][title]',
                'value' => $titleValue,
            ])

            @include('dashboard.pages.sections.partials.custom-presets.fields._textarea-field', [
                'label' => __('Subtitle'),
                'name'  => 'translations[' . $code . '][content][subtitle]',
                'value' => $subtitleValue,
                'rows'  => 4,
            ])
        </div>
    </div>

    @include('dashboard.pages.sections.partials.repeaters.campaign-features-repeater', [
        'code' => $code,
        'campaignFeatureItems' => $featureItems,
        'mediaPreviewBuilder' => $mediaPreviewBuilder,
        'featureRepeaterHeading' => __('Features List'),
        'featureRepeaterDescription' => __('Manage the supporting checklist items shown beside the hosting hero content.'),
        'featureRepeaterAddLabel' => __('Add Feature'),
        'featureRepeaterEmptyState' => __('No features yet. Add the first one to build the checklist.'),
        'featureRepeaterTextHelp' => __('This text appears as one checklist item in the hosting hero section.'),
        'featureRepeaterTextPlaceholder' => __('Example: 24/7 technical support'),
    ])

    <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/60 p-5">
        <div class="mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                {{ __('Side Card') }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Edit the supporting CTA card displayed on the right side of the hero.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label' => __('Card Title'),
                'name'  => 'translations[' . $code . '][content][card_title]',
                'value' => $cardTitleValue,
            ])

            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label'   => __('Button Label'),
                'name'    => 'translations[' . $code . '][content][card_button_label]',
                'value'   => $cardButtonLabelValue,
                'colSpan' => '',
            ])

            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label'   => __('Button URL'),
                'name'    => 'translations[' . $code . '][content][card_button_url]',
                'value'   => $cardButtonUrlValue,
                'type'    => 'url',
                'colSpan' => '',
            ])
        </div>
    </div>

    @include('dashboard.pages.sections.partials.custom-presets.fields._background-image-card', [
        'code'        => $code,
        'heading'     => __('Background'),
        'description' => __('Choose the background image used behind the hosting hero section.'),
        'value'       => $backgroundImageValue,
        'previewUrls' => $backgroundImagePreviewUrls,
    ])
</div>
