<div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <label
                class="block text-sm font-medium text-slate-700">{{ __('Portfolios Source') }}</label>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('This section reads portfolio cards directly from the Portfolios module in the dashboard.') }}
            </p>
        </div>
        <a href="{{ route('dashboard.portfolios.index') }}"
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <i class="ti ti-briefcase text-base leading-none" aria-hidden="true"></i>
            <span>{{ __('Open Portfolios') }}</span>
        </a>
    </div>

    {{-- 
    This block is intentionally kept manual (Phase 18 rules).
    It may be upgraded later to hybrid or schema-driven if needed.
    --}}
    <div class="mt-5 space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <label
                class="block text-sm font-medium text-slate-700">{{ __('Items Limit') }}</label>
            <input type="number" min="1" name="translations[{{ $code }}][content][limit]"
                value="{{ $ourWorkLimitValue }}"
                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                placeholder="6">
            <p class="mt-2 text-xs text-slate-500">
                {{ __('Optional. Use this to show only the first portfolio items ordered from the Portfolios module.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <label
                class="block text-sm font-medium text-slate-700">{{ __('Visit Button Label') }}</label>
            <input type="text" name="translations[{{ $code }}][content][visit_label]"
                value="{{ $ourWorkVisitLabelValue }}"
                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                placeholder="{{ __('Visit') }}">
            <p class="mt-2 text-xs text-slate-500">
                {{ __('This text appears on the card button for every portfolio item in the slider.') }}
            </p>
        </div>
    </div>
</div>
