@php
    $presetLocaleEditor = $customPresetEditor['locales'][$code] ?? null;
    $presetValues = is_array($presetLocaleEditor['values'] ?? null) ? $presetLocaleEditor['values'] : [];
    $titleValue = $presetValues['titleValue'] ?? '';
    $subtitleValue = $presetValues['subtitleValue'] ?? '';
    $protectionItems = is_array($presetValues['protectionItems'] ?? null) ? $presetValues['protectionItems'] : [];
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
                {{ __('Edit the headline and supporting text shown in the Website Protection section.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">{{ __('Title') }}</label>
                <input type="text" name="translations[{{ $code }}][content][title]"
                    value="{{ $titleValue }}"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="{{ __('e.g. Website Protection Features') }}">
            </div>

            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-slate-700">{{ __('Subtitle') }}</label>
                <textarea name="translations[{{ $code }}][content][subtitle]" rows="3"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    placeholder="{{ __('e.g. Comprehensive security features to keep your website safe.') }}">{{ $subtitleValue }}</textarea>
            </div>
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
