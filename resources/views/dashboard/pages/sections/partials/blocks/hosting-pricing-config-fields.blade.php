{{--
Mixed hosting pricing config block:
schema-driven CTA label + manual category visibility controls.
--}}

<div class="lg:col-span-2 rounded-[1.75rem] bg-slate-50/80 px-5 py-4 text-sm text-slate-600">
    <p class="font-medium text-slate-900">{{ __('Plans Grid') }}</p>
    <p class="mt-1 leading-6">
        {{ __('Tabs and plan cards are loaded automatically from the Plans and Plan Categories modules. Manage the actual plans there, and use this section only for the heading and shared CTA label.') }}
    </p>
</div>

@php
    $hostingPricingButtonLabelFieldContext = $schemaFieldContext(
        'content',
        'button_label',
        __('CTA Button Label'),
        __('Choose Now'),
    );
    $hostingPricingButtonLabelRenderConfig = $schemaRenderableFieldConfig(
        $hostingPricingButtonLabelFieldContext,
        'text',
        3,
    );
@endphp

@include(
    'dashboard.pages.sections.partials.fields.schema-field-renderer',
    $schemaRendererPayload(
        $hostingPricingButtonLabelRenderConfig,
        'translations[' . $code . '][content][button_label]',
        $hostingPricingButtonLabelValue,
        'button_label',
        'lg:col-span-2',
    )
)

<div class="lg:col-span-2">
    <label class="block text-sm font-medium text-slate-700">{{ __('Visible Categories') }}</label>
    <p class="mt-1 text-xs leading-5 text-slate-500">
        {{ __('Choose only the plan categories you want to show in this section. Leave all unchecked to show every active category.') }}
    </p>

    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
        @forelse ($hostingPricingAvailableCategories as $availableCategory)
            @php
                $availableCategoryTranslation =
                    $availableCategory->translation($code) ??
                    $availableCategory->translations->first();
                $availableCategoryLabel = trim(
                    (string) ($availableCategoryTranslation?->title ??
                        __('Category') . ' #' . $availableCategory->id),
                );
                $availableCategoryKey = trim(
                    (string) ($availableCategoryTranslation?->slug ??
                        'category-' . $availableCategory->id),
                );
                $isVisibleCategoryChecked = in_array(
                    (int) $availableCategory->id,
                    $hostingPricingVisibleCategoryIds,
                    true,
                );
            @endphp

            <label
                class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 transition hover:border-slate-300">
                <input type="checkbox" name="translations[{{ $code }}][content][visible_category_ids][]"
                    value="{{ $availableCategory->id }}" @checked($isVisibleCategoryChecked)
                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                <span class="min-w-0 flex-1">
                    <span dir="auto"
                        class="block font-medium text-slate-900 break-words">{{ $availableCategoryLabel }}</span>
                    <span dir="ltr"
                        class="mt-1 block text-xs text-slate-500 break-all">{{ $availableCategoryKey }}</span>
                </span>
            </label>
        @empty
            <div
                class="sm:col-span-2 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                {{ __('No active plan categories found.') }}
            </div>
        @endforelse
    </div>
</div>
