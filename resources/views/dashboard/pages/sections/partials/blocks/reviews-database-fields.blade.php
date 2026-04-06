<div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700">{{ __('Testimonials Source') }}</label>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('This section now reads approved testimonial cards directly from the Testimonials module in the dashboard.') }}
            </p>
        </div>
        <a href="{{ route('dashboard.testimonials.index') }}"
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <i class="ti ti-message-star text-base leading-none" aria-hidden="true"></i>
            <span>{{ __('Open Testimonials') }}</span>
        </a>
</div>

    <div class="mt-5">
        @php
            $reviewsLimitFieldContext = $schemaFieldContext(
                'content',
                'limit',
                __('Items Limit'),
                '6',
            );
        @endphp

        {{-- This block remains mostly manual for simplicity, but the limit field resolves its label and placeholder through shared schema context for consistency. --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <label class="block text-sm font-medium text-slate-700">{{ $reviewsLimitFieldContext['label'] }}</label>
            <input type="number" min="1" name="translations[{{ $code }}][content][limit]"
                value="{{ $reviewsLimitValue }}"
                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                placeholder="{{ $reviewsLimitFieldContext['placeholder'] }}">
            <p class="mt-2 text-xs text-slate-500">
                {{ __('Optional. Leave this empty to show all approved testimonials from the Testimonials module.') }}
            </p>
        </div>
    </div>
</div>
