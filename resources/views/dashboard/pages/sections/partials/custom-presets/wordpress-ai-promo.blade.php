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
        <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-slate-700">
                {{ __('Section Title') }} ({{ $code }})
            </label>
            <input type="text" name="translations[{{ $code }}][title]" value="{{ $sectionTitleValue }}"
                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
        </div>
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
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">{{ __('Eyebrow') }}</label>
                <input type="text" name="translations[{{ $code }}][content][eyebrow]"
                    value="{{ $eyebrowValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="{{ __('e.g. WordPress Hosting') }}">
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">{{ __('Title') }}</label>
                <input type="text" name="translations[{{ $code }}][content][title]"
                    value="{{ $titleValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="{{ __('e.g. Launch and manage WordPress with AI') }}">
            </div>
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
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">{{ __('Pricing') }}</label>
                <input type="text" name="translations[{{ $code }}][content][pricing]"
                    value="{{ $pricingValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="{{ __('e.g. $9.99/month') }}">
            </div>
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
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">{{ __('Button Label') }}</label>
                <input type="text" name="translations[{{ $code }}][content][button_label]"
                    value="{{ $buttonLabelValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="{{ __('e.g. Get Started') }}">
            </div>
        </div>
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">{{ __('Button URL') }}</label>
                <input type="text" name="translations[{{ $code }}][content][button_url]"
                    value="{{ $buttonUrlValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="{{ __('e.g. https://example.com') }}">
            </div>
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

    <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/60 p-5">
        <div class="mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                {{ __('Background') }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Choose the background image used behind the WordPress AI Promo section.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5">
            <div>
                <x-dashboard.media-picker :name="'translations[' . $code . '][content][background_image]'" :label="__('Background Image')" :button-text="__('Choose From Media Library')" :value="$backgroundImageValue"
                    :preview-urls="$backgroundImagePreviewUrls" :multiple="false" store-value="id" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">{{ __('Image Alt Text') }}</label>
                <input type="text" name="translations[{{ $code }}][content][image_alt]"
                    value="{{ $imageAltValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="{{ __('e.g. WordPress hosting dashboard preview') }}">
            </div>
        </div>
    </div>
</div>
