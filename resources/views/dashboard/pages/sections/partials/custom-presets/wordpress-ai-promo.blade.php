@php
    $pv = \App\Support\Sections\SectionPresetEditorValues::for($customPresetEditor, $code);
    $eyebrowValue               = $pv->scalar('eyebrowValue');
    $titleValue                 = $pv->scalar('titleValue');
    $pricingValue               = $pv->scalar('pricingValue');
    $buttonLabelValue           = $pv->scalar('buttonLabelValue');
    $buttonUrlValue             = $pv->scalar('buttonUrlValue');
    $imageAltValue              = $pv->scalar('imageAltValue');
    $featureItems               = $pv->items('featureItems');
    $backgroundImageValue       = $pv->mediaId('backgroundImageValue');
    $backgroundImagePreviewUrls = $pv->items('backgroundImagePreviewUrls');
@endphp

<div class="{{ $contentGridClass }}">
    @if ($usesInternalLabel)
        <input type="hidden" name="translations[{{ $code }}][title]" value="{{ $sectionTitleValue }}">
    @else
        @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
            'label'   => __('Section Title') . ' (' . $code . ')',
            'name'    => 'translations[' . $code . '][title]',
            'value'   => $sectionTitleValue,
        ])
    @endif

    <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/60 p-5">
        <div class="mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                {{ __('Main Content') }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Edit the eyebrow label and headline shown in the WordPress AI Promo section.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label'       => __('Eyebrow'),
                'name'        => 'translations[' . $code . '][content][eyebrow]',
                'value'       => $eyebrowValue,
                'placeholder' => __('e.g. WordPress Hosting'),
            ])

            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label'       => __('Title'),
                'name'        => 'translations[' . $code . '][content][title]',
                'value'       => $titleValue,
                'placeholder' => __('e.g. Launch and manage WordPress with AI'),
            ])
        </div>
    </div>

    <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/60 p-5">
        <div class="mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                {{ __('Pricing') }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Edit the pricing information shown in the WordPress AI Promo section.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label'       => __('Pricing'),
                'name'        => 'translations[' . $code . '][content][pricing]',
                'value'       => $pricingValue,
                'placeholder' => __('e.g. $9.99/month'),
            ])
        </div>
    </div>

    <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/60 p-5">
        <div class="mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                {{ __('Button') }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Edit the button label shown in the WordPress AI Promo section.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label'       => __('Button Label'),
                'name'        => 'translations[' . $code . '][content][button_label]',
                'value'       => $buttonLabelValue,
                'placeholder' => __('e.g. Get Started'),
            ])

            @include('dashboard.pages.sections.partials.custom-presets.fields._text-field', [
                'label'       => __('Button URL'),
                'name'        => 'translations[' . $code . '][content][button_url]',
                'value'       => $buttonUrlValue,
                'placeholder' => __('e.g. https://example.com'),
            ])
        </div>
    </div>

    @include('dashboard.pages.sections.partials.repeaters.campaign-features-repeater', [
        'code' => $code,
        'campaignFeatureItems' => $featureItems,
        'mediaPreviewBuilder' => $mediaPreviewBuilder,
        'featureRepeaterHeading' => __('Features'),
        'featureRepeaterDescription' => __(
            'Manage the feature checklist items shown in the WordPress AI Promo section.'),
        'featureRepeaterAddLabel' => __('Add Feature'),
        'featureRepeaterEmptyState' => __('No features yet. Add the first one to build the list.'),
        'featureRepeaterTextHelp' => __('This text appears as one checklist item in the promo section.'),
        'featureRepeaterTextPlaceholder' => __('Example: Easy one-click installs'),
    ])

    @include('dashboard.pages.sections.partials.custom-presets.fields._background-image-card', [
        'code'        => $code,
        'heading'     => __('Background'),
        'description' => __('Choose the background image used behind the WordPress AI Promo section.'),
        'value'       => $backgroundImageValue,
        'previewUrls' => $backgroundImagePreviewUrls,
        'imageAltKey' => 'image_alt',
        'imageAlt'    => $imageAltValue,
    ])
</div>
