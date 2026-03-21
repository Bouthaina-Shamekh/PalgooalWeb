<div class="lg:col-span-2">
    <div
        data-pricing-plan-repeater
        data-plan-item-label="{{ __('Plan') }}"
        data-plan-item-hint="{{ __('Click to edit this plan card') }}"
        data-category-datalist-id="{{ $pricingCategoryDatalistId }}"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">{{ __('Plans Grid') }}</label>
                <p class="mt-1 text-xs text-slate-500">{{ __('Each plan card belongs to a category tab and contains its own feature list and CTA button.') }}</p>
            </div>
            <button
                type="button"
                data-add-pricing-plan
                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                <span>{{ __('Add Plan') }}</span>
            </button>
        </div>

        <div class="mt-3">
            <div class="space-y-3" data-pricing-plan-items>
                @foreach ($pricingPlanItems as $planIndex => $planItem)
                    @php
                        $planFeaturesCount = collect(preg_split("/\r\n|\r|\n/", (string) ($planItem['features_textarea'] ?? '')))
                            ->map(fn ($value) => trim((string) $value))
                            ->filter()
                            ->count();

                        $planSummaryParts = array_values(array_filter([
                            filled($planItem['category'] ?? '') ? __('Tab') . ': ' . $planItem['category'] : null,
                            $planFeaturesCount ? ($planFeaturesCount . ' ' . \Illuminate\Support\Str::plural('feature', $planFeaturesCount)) : null,
                            filled($planItem['button_label'] ?? '') ? __('CTA') . ': ' . $planItem['button_label'] : null,
                            ! empty($planItem['button_new_tab']) ? __('Opens in a new tab') : null,
                        ]));
                    @endphp
                    <article data-pricing-plan-item class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                <button
                                    type="button"
                                    data-pricing-plan-drag-handle
                                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                    aria-label="{{ __('Reorder plan') }}"
                                >
                                    <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                                </button>

                                <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                    <button
                                        type="button"
                                        data-duplicate-pricing-plan
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                        aria-label="{{ __('Duplicate plan') }}"
                                    >
                                        <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        type="button"
                                        data-remove-pricing-plan
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                        aria-label="{{ __('Remove plan') }}"
                                    >
                                        <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <button
                                type="button"
                                data-pricing-plan-toggle
                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right"
                            >
                                <div class="min-w-0 flex-1">
                                    <p dir="auto" data-pricing-plan-title class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                        {{ filled($planItem['title'] ?? '') ? $planItem['title'] : __('Plan') . ' ' . ($planIndex + 1) }}
                                    </p>
                                    <p dir="auto" data-pricing-plan-summary class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                        {{ $planSummaryParts !== [] ? implode(' - ', $planSummaryParts) : __('Click to edit this plan card') }}
                                    </p>
                                </div>

                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                    <i data-pricing-plan-toggle-icon class="ti ti-chevron-down text-base leading-none {{ $loop->first ? 'rotate-180' : '' }}" aria-hidden="true"></i>
                                </span>
                            </button>
                        </div>

                        <div data-pricing-plan-body class="mt-4 space-y-4 {{ $loop->first ? '' : 'hidden' }}">
                            <div>
                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Plan Name') }}</label>
                                    <span class="text-xs text-slate-400">{{ __('Card heading') }}</span>
                                </div>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][plans][{{ $planIndex }}][title]"
                                    data-name-template="translations[{{ $code }}][content][plans][__INDEX__][title]"
                                    data-pricing-plan-field="title"
                                    value="{{ $planItem['title'] ?? '' }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="{{ __('PLAN1') }}"
                                >
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Category Key') }}</label>
                                    <span class="text-xs text-slate-400">{{ __('Choose one of the tabs above') }}</span>
                                </div>
                                <input
                                    type="text"
                                    list="{{ $pricingCategoryDatalistId }}"
                                    name="translations[{{ $code }}][content][plans][{{ $planIndex }}][category]"
                                    data-name-template="translations[{{ $code }}][content][plans][__INDEX__][category]"
                                    data-pricing-plan-field="category"
                                    value="{{ $planItem['category'] ?? '' }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="shared"
                                >
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Plan Features') }}</label>
                                    <span class="text-xs text-slate-400">{{ __('Each line becomes one list item') }}</span>
                                </div>
                                <textarea
                                    name="translations[{{ $code }}][content][plans][{{ $planIndex }}][features_textarea]"
                                    data-name-template="translations[{{ $code }}][content][plans][__INDEX__][features_textarea]"
                                    data-pricing-plan-field="features"
                                    rows="6"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="{{ __('20 GB SSD Cloud storage') }}&#10;{{ __('5 hosted domains') }}"
                                >{{ $planItem['features_textarea'] ?? '' }}</textarea>
                            </div>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">{{ __('CTA Button Label') }}</label>
                                    <input
                                        type="text"
                                        name="translations[{{ $code }}][content][plans][{{ $planIndex }}][button_label]"
                                        data-name-template="translations[{{ $code }}][content][plans][__INDEX__][button_label]"
                                        data-pricing-plan-field="button_label"
                                        value="{{ $planItem['button_label'] ?? '' }}"
                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                        placeholder="{{ __('Choose Now') }}"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700">{{ __('CTA Button URL') }}</label>
                                    <input
                                        type="text"
                                        name="translations[{{ $code }}][content][plans][{{ $planIndex }}][button_url]"
                                        data-name-template="translations[{{ $code }}][content][plans][__INDEX__][button_url]"
                                        data-pricing-plan-field="button_url"
                                        value="{{ $planItem['button_url'] ?? '' }}"
                                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                        placeholder="#"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="translations[{{ $code }}][content][plans][{{ $planIndex }}][button_new_tab]"
                                        data-name-template="translations[{{ $code }}][content][plans][__INDEX__][button_new_tab]"
                                        data-pricing-plan-field="button_new_tab"
                                        value="1"
                                        class="rounded border-slate-300"
                                        {{ ! empty($planItem['button_new_tab']) ? 'checked' : '' }}
                                    >
                                    {{ __('Open CTA in a new tab') }}
                                </label>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div data-pricing-plan-empty class="{{ count($pricingPlanItems) ? 'hidden ' : '' }}mt-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-6 text-center text-sm text-slate-500">
                {{ __('No plans yet. Add the first pricing card to build the section.') }}
            </div>
        </div>

        <template data-pricing-plan-template>
            <article data-pricing-plan-item class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                        <button
                            type="button"
                            data-pricing-plan-drag-handle
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                            aria-label="{{ __('Reorder plan') }}"
                        >
                            <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                        </button>

                        <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                            <button
                                type="button"
                                data-duplicate-pricing-plan
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                aria-label="{{ __('Duplicate plan') }}"
                            >
                                <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                            </button>
                            <button
                                type="button"
                                data-remove-pricing-plan
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                aria-label="{{ __('Remove plan') }}"
                            >
                                <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        data-pricing-plan-toggle
                        aria-expanded="false"
                        class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right"
                    >
                        <div class="min-w-0 flex-1">
                            <p dir="auto" data-pricing-plan-title class="text-sm font-semibold leading-5 text-slate-900 break-words">{{ __('New Plan') }}</p>
                            <p dir="auto" data-pricing-plan-summary class="mt-1 text-xs leading-5 text-slate-500 break-words">{{ __('Click to edit this plan card') }}</p>
                        </div>

                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                            <i data-pricing-plan-toggle-icon class="ti ti-chevron-down text-base leading-none" aria-hidden="true"></i>
                        </span>
                    </button>
                </div>

                <div data-pricing-plan-body class="mt-4 hidden space-y-4">
                    <div>
                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                            <label class="block text-sm font-medium text-slate-700">{{ __('Plan Name') }}</label>
                            <span class="text-xs text-slate-400">{{ __('Card heading') }}</span>
                        </div>
                        <input
                            type="text"
                            name="translations[{{ $code }}][content][plans][__INDEX__][title]"
                            data-name-template="translations[{{ $code }}][content][plans][__INDEX__][title]"
                            data-pricing-plan-field="title"
                            value=""
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            placeholder="{{ __('PLAN1') }}"
                        >
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                            <label class="block text-sm font-medium text-slate-700">{{ __('Category Key') }}</label>
                            <span class="text-xs text-slate-400">{{ __('Choose one of the tabs above') }}</span>
                        </div>
                        <input
                            type="text"
                            list="{{ $pricingCategoryDatalistId }}"
                            name="translations[{{ $code }}][content][plans][__INDEX__][category]"
                            data-name-template="translations[{{ $code }}][content][plans][__INDEX__][category]"
                            data-pricing-plan-field="category"
                            value=""
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            placeholder="shared"
                        >
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                            <label class="block text-sm font-medium text-slate-700">{{ __('Plan Features') }}</label>
                            <span class="text-xs text-slate-400">{{ __('Each line becomes one list item') }}</span>
                        </div>
                        <textarea
                            name="translations[{{ $code }}][content][plans][__INDEX__][features_textarea]"
                            data-name-template="translations[{{ $code }}][content][plans][__INDEX__][features_textarea]"
                            data-pricing-plan-field="features"
                            rows="6"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            placeholder="{{ __('20 GB SSD Cloud storage') }}&#10;{{ __('5 hosted domains') }}"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('CTA Button Label') }}</label>
                            <input
                                type="text"
                                name="translations[{{ $code }}][content][plans][__INDEX__][button_label]"
                                data-name-template="translations[{{ $code }}][content][plans][__INDEX__][button_label]"
                                data-pricing-plan-field="button_label"
                                value=""
                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                placeholder="{{ __('Choose Now') }}"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('CTA Button URL') }}</label>
                            <input
                                type="text"
                                name="translations[{{ $code }}][content][plans][__INDEX__][button_url]"
                                data-name-template="translations[{{ $code }}][content][plans][__INDEX__][button_url]"
                                data-pricing-plan-field="button_url"
                                value=""
                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                placeholder="#"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                            <input
                                type="checkbox"
                                name="translations[{{ $code }}][content][plans][__INDEX__][button_new_tab]"
                                data-name-template="translations[{{ $code }}][content][plans][__INDEX__][button_new_tab]"
                                data-pricing-plan-field="button_new_tab"
                                value="1"
                                class="rounded border-slate-300"
                            >
                            {{ __('Open CTA in a new tab') }}
                        </label>
                    </div>
                </div>
            </article>
        </template>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-50/80 px-4 py-3 text-xs text-slate-500 rtl:flex-row-reverse">
            <span>{{ __('Use one plan card per offering, then assign it to the matching tab with the same category key.') }}</span>
            <button
                type="button"
                data-add-pricing-plan
                class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
                <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                <span>{{ __('Add Plan') }}</span>
            </button>
        </div>
    </div>
</div>
