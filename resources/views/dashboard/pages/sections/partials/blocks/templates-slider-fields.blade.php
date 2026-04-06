<div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <label
                class="block text-sm font-medium text-slate-700">{{ __('Templates Source') }}</label>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('This section loads template cards automatically from the Templates module. Use the fields below only to control the section heading, card button labels, and item limit.') }}
            </p>
        </div>
        <a href="{{ route('dashboard.templates.index') }}"
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <i class="ti ti-layout-grid text-base leading-none" aria-hidden="true"></i>
            <span>{{ __('Open Templates') }}</span>
        </a>
    </div>

    {{--
    This block is intentionally kept manual for now.
    It can be revisited later if a consistent schema/hybrid strategy is needed.
    --}}
    <div class="mt-5 grid grid-cols-1 gap-5">
        <div class="lg:col-span-2">
            <label
                class="block text-sm font-medium text-slate-700">{{ __('Buy Button Label') }}</label>
            <input type="text" name="translations[{{ $code }}][content][buy_label]"
                value="{{ $templatesSliderBuyLabelValue }}"
                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                placeholder="{{ __('Buy Now') }}">
            <p class="mt-2 text-xs text-slate-500">
                {{ __('This label appears on the main CTA button in every template card.') }}
            </p>
        </div>

        <div class="lg:col-span-2">
            <label
                class="block text-sm font-medium text-slate-700">{{ __('Preview Button Label') }}</label>
            <input type="text" name="translations[{{ $code }}][content][preview_label]"
                value="{{ $templatesSliderPreviewLabelValue }}"
                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                placeholder="{{ __('Live Preview') }}">
            <p class="mt-2 text-xs text-slate-500">
                {{ __('This label appears on the secondary button in every template card.') }}
            </p>
        </div>

        <div class="lg:col-span-2">
            <label
                class="block text-sm font-medium text-slate-700">{{ __('Items Limit') }}</label>
            <input type="number" min="1" name="translations[{{ $code }}][content][limit]"
                value="{{ $templatesSliderLimitValue }}"
                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                placeholder="6">
            <p class="mt-2 text-xs text-slate-500">
                {{ __('Optional. Leave this empty to use the default number of template cards for the slider.') }}
            </p>
        </div>
    </div>
</div>
