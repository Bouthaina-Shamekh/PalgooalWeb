{{-- Secondary button fields: extracted partial with CTA-aware wording and layout; intentionally simpler than primary_button. --}}
@php
    $isSecondaryCtaContext =
        $isHeroCampaign ||
        $isHowWeBuild ||
        $isProgrammingShowcase ||
        $isMobileAppShowcase ||
        $isDesignShowcase ||
        $isDigitalMarketingShowcase;

    $secondaryButtonFieldColumnClass = $isSecondaryCtaContext ? 'lg:col-span-2' : '';

    $secondaryButtonLabelText = $isSecondaryCtaContext
        ? __('Secondary CTA Label')
        : __('Secondary Button Label');

    $secondaryButtonUrlLabelText = $isSecondaryCtaContext
        ? __('Secondary CTA URL')
        : __('Secondary Button URL');
@endphp

<div class="{{ $secondaryButtonFieldColumnClass }}">
    <label class="block text-sm font-medium text-slate-700">
        {{ $secondaryButtonLabelText }}
    </label>
    <input type="text"
        name="translations[{{ $code }}][content][secondary_button][label]"
        value="{{ $secondaryButtonLabelValue }}"
        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
</div>

<div class="{{ $secondaryButtonFieldColumnClass }}">
    <label class="block text-sm font-medium text-slate-700">
        {{ $secondaryButtonUrlLabelText }}
    </label>
    <input type="text"
        name="translations[{{ $code }}][content][secondary_button][url]"
        value="{{ $secondaryButtonUrlValue }}"
        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
</div>
