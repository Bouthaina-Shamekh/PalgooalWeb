@php
    $presetLocaleEditor = $customPresetEditor['locales'][$code] ?? null;
    $presetValues = is_array($presetLocaleEditor['values'] ?? null) ? $presetLocaleEditor['values'] : [];
    $breadcrumbHomeLabelValue = $presetValues['breadcrumbHomeLabelValue'] ?? '';
    $breadcrumbHomeUrlValue = $presetValues['breadcrumbHomeUrlValue'] ?? '';
    $breadcrumbCurrentLabelValue = $presetValues['breadcrumbCurrentLabelValue'] ?? '';
    $titleValue = $presetValues['titleValue'] ?? '';
    $subtitleValue = $presetValues['subtitleValue'] ?? '';
    $featureItems = is_array($presetValues['featureItems'] ?? null) ? $presetValues['featureItems'] : [];
    $cardTitleValue = $presetValues['cardTitleValue'] ?? '';
    $cardButtonLabelValue = $presetValues['cardButtonLabelValue'] ?? '';
    $cardButtonUrlValue = $presetValues['cardButtonUrlValue'] ?? '';
    $backgroundImageValue = $presetValues['backgroundImageValue'] ?? null;
    $backgroundImagePreviewUrls = is_array($presetValues['backgroundImagePreviewUrls'] ?? null)
        ? $presetValues['backgroundImagePreviewUrls']
        : [];
@endphp
<div class="{{ $contentGridClass }}">
    @if ($usesInternalLabel)
        <input type="hidden" name="translations[{{ $code }}][title]" value="{{ $sectionTitleValue }}">
    @else
        <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-slate-700">
                {{ __('Section Title') }} ({{ $code }})
            </label>
            <input type="text" name="translations[{{ $code }}][title]"
                value="{{ $sectionTitleValue }}"
                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
        </div>
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
            <div>
                <label class="block text-sm font-medium text-slate-700">{{ __('Home Label') }}</label>
                <input type="text"
                    name="translations[{{ $code }}][content][breadcrumb_home_label]"
                    value="{{ $breadcrumbHomeLabelValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="{{ __('Home') }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">{{ __('Home URL') }}</label>
                <input type="url"
                    name="translations[{{ $code }}][content][breadcrumb_home_url]"
                    value="{{ $breadcrumbHomeUrlValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="index.html">
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">{{ __('Current Label') }}</label>
                <input type="text"
                    name="translations[{{ $code }}][content][breadcrumb_current_label]"
                    value="{{ $breadcrumbCurrentLabelValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="{{ __('Hosting') }}">
            </div>
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
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">{{ __('Title') }}</label>
                <input type="text"
                    name="translations[{{ $code }}][content][title]"
                    value="{{ $titleValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">{{ __('Subtitle') }}</label>
                <textarea name="translations[{{ $code }}][content][subtitle]" rows="4"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">{{ $subtitleValue }}</textarea>
            </div>
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
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">{{ __('Card Title') }}</label>
                <input type="text"
                    name="translations[{{ $code }}][content][card_title]"
                    value="{{ $cardTitleValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">{{ __('Button Label') }}</label>
                <input type="text"
                    name="translations[{{ $code }}][content][card_button_label]"
                    value="{{ $cardButtonLabelValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">{{ __('Button URL') }}</label>
                <input type="url"
                    name="translations[{{ $code }}][content][card_button_url]"
                    value="{{ $cardButtonUrlValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="https://">
            </div>
        </div>
    </div>

    <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/60 p-5">
        <div class="mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                {{ __('Background') }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Choose the background image used behind the hosting hero section.') }}
            </p>
        </div>

        <x-dashboard.media-picker :name="'translations[' . $code . '][content][background_image]'" :label="__('Background Image')"
            :button-text="__('Choose From Media Library')" :value="$backgroundImageValue" :preview-urls="$backgroundImagePreviewUrls" :multiple="false"
            store-value="id" />
    </div>
</div>