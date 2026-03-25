<div class="lg:col-span-2">
    <div data-pricing-category-repeater data-category-item-label="{{ __('Category') }}"
        data-category-item-hint="{{ __('Click to edit this pricing tab') }}"
        data-category-datalist-id="{{ $pricingCategoryDatalistId }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">{{ __('Pricing Categories') }}</label>
                <p class="mt-1 text-xs text-slate-500">
                    {{ __('Create the tabs shown above the plans grid. Each tab needs a label and a stable category key.') }}
                </p>
            </div>
            <button type="button" data-add-pricing-category
                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                <span>{{ __('Add Category') }}</span>
            </button>
        </div>

        <div class="mt-3">
            <div class="space-y-3" data-pricing-category-items>
                @foreach ($pricingCategoryItems as $categoryIndex => $categoryItem)
                    <article data-pricing-category-item
                        class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                <button type="button" data-pricing-category-drag-handle
                                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                    aria-label="{{ __('Reorder category') }}">
                                    <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                                </button>

                                <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                    <button type="button" data-duplicate-pricing-category
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                        aria-label="{{ __('Duplicate category') }}">
                                        <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" data-remove-pricing-category
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                        aria-label="{{ __('Remove category') }}">
                                        <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="button" data-pricing-category-toggle
                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right">
                                <div class="min-w-0 flex-1">
                                    <p dir="auto" data-pricing-category-title
                                        class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                        {{ filled($categoryItem['label'] ?? '') ? $categoryItem['label'] : __('Category') . ' ' . ($categoryIndex + 1) }}
                                    </p>
                                    <p dir="auto" data-pricing-category-summary
                                        class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                        {{ filled($categoryItem['key'] ?? '') ? __('Key') . ': ' . $categoryItem['key'] : __('Click to edit this pricing tab') }}
                                    </p>
                                </div>

                                <span
                                    class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                    <i data-pricing-category-toggle-icon
                                        class="ti ti-chevron-down text-base leading-none {{ $loop->first ? 'rotate-180' : '' }}"
                                        aria-hidden="true"></i>
                                </span>
                            </button>
                        </div>

                        <div data-pricing-category-body class="mt-4 space-y-4 {{ $loop->first ? '' : 'hidden' }}">
                            <div>
                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <label
                                        class="block text-sm font-medium text-slate-700">{{ __('Tab Label') }}</label>
                                    <span class="text-xs text-slate-400">{{ __('Visible to visitors') }}</span>
                                </div>
                                <input type="text"
                                    name="translations[{{ $code }}][content][categories][{{ $categoryIndex }}][label]"
                                    data-name-template="translations[{{ $code }}][content][categories][__INDEX__][label]"
                                    data-pricing-category-field="label" value="{{ $categoryItem['label'] ?? '' }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="{{ __('Shared') }}">
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <label
                                        class="block text-sm font-medium text-slate-700">{{ __('Category Key') }}</label>
                                    <span
                                        class="text-xs text-slate-400">{{ __('Used to match plans with this tab') }}</span>
                                </div>
                                <input type="text"
                                    name="translations[{{ $code }}][content][categories][{{ $categoryIndex }}][key]"
                                    data-name-template="translations[{{ $code }}][content][categories][__INDEX__][key]"
                                    data-pricing-category-field="key" value="{{ $categoryItem['key'] ?? '' }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="shared">
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ __('Use simple lowercase keys like shared, store, or dedicated. Plans connect to tabs through this key.') }}
                                </p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div data-pricing-category-empty
                class="{{ count($pricingCategoryItems) ? 'hidden ' : '' }}mt-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-6 text-center text-sm text-slate-500">
                {{ __('No pricing categories yet. Add the first tab to start building the section.') }}
            </div>
        </div>

        <template data-pricing-category-template>
            <article data-pricing-category-item
                class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                        <button type="button" data-pricing-category-drag-handle
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                            aria-label="{{ __('Reorder category') }}">
                            <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                        </button>

                        <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                            <button type="button" data-duplicate-pricing-category
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                aria-label="{{ __('Duplicate category') }}">
                                <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                            </button>
                            <button type="button" data-remove-pricing-category
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                aria-label="{{ __('Remove category') }}">
                                <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="button" data-pricing-category-toggle aria-expanded="false"
                        class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right">
                        <div class="min-w-0 flex-1">
                            <p dir="auto" data-pricing-category-title
                                class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                {{ __('New Category') }}</p>
                            <p dir="auto" data-pricing-category-summary
                                class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                {{ __('Click to edit this pricing tab') }}</p>
                        </div>

                        <span
                            class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                            <i data-pricing-category-toggle-icon class="ti ti-chevron-down text-base leading-none"
                                aria-hidden="true"></i>
                        </span>
                    </button>
                </div>

                <div data-pricing-category-body class="mt-4 hidden space-y-4">
                    <div>
                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                            <label class="block text-sm font-medium text-slate-700">{{ __('Tab Label') }}</label>
                            <span class="text-xs text-slate-400">{{ __('Visible to visitors') }}</span>
                        </div>
                        <input type="text"
                            name="translations[{{ $code }}][content][categories][__INDEX__][label]"
                            data-name-template="translations[{{ $code }}][content][categories][__INDEX__][label]"
                            data-pricing-category-field="label" value=""
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            placeholder="{{ __('Shared') }}">
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                            <label class="block text-sm font-medium text-slate-700">{{ __('Category Key') }}</label>
                            <span class="text-xs text-slate-400">{{ __('Used to match plans with this tab') }}</span>
                        </div>
                        <input type="text"
                            name="translations[{{ $code }}][content][categories][__INDEX__][key]"
                            data-name-template="translations[{{ $code }}][content][categories][__INDEX__][key]"
                            data-pricing-category-field="key" value=""
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            placeholder="shared">
                        <p class="mt-2 text-xs text-slate-500">
                            {{ __('Use simple lowercase keys like shared, store, or dedicated. Plans connect to tabs through this key.') }}
                        </p>
                    </div>
                </div>
            </article>
        </template>

        <div
            class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-50/80 px-4 py-3 text-xs text-slate-500 rtl:flex-row-reverse">
            <span>{{ __('Keep the tab labels localized, but use stable keys so each plan always appears under the right tab.') }}</span>
            <button type="button" data-add-pricing-category
                class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                <span>{{ __('Add Category') }}</span>
            </button>
        </div>

        <datalist id="{{ $pricingCategoryDatalistId }}" data-pricing-category-datalist></datalist>
    </div>
</div>
