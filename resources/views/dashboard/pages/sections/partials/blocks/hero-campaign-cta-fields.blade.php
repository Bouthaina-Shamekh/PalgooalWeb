{{-- Hero campaign CTA block: stabilized for future extraction; uses dedicated CTA wording and layout. --}}
@php
    $heroCtaColumnClass = 'lg:col-span-2';
@endphp

<div class="{{ $heroCtaColumnClass }}">
    <label class="block text-sm font-medium text-slate-700">
        {{ __('Primary CTA Label') }}
    </label>
    <input type="text"
        name="translations[{{ $code }}][content][primary_button][label]"
        value="{{ $primaryButtonLabelValue }}"
        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
</div>

<div class="{{ $heroCtaColumnClass }}">
    <label class="block text-sm font-medium text-slate-700">
        {{ __('Primary CTA URL') }}
    </label>
    <input type="text"
        name="translations[{{ $code }}][content][primary_button][url]"
        value="{{ $primaryButtonUrlValue }}"
        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
</div>

<div class="lg:col-span-2">
    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
        <input type="hidden"
            name="translations[{{ $code }}][content][primary_button][visible]"
            value="0">
        <input type="checkbox"
            name="translations[{{ $code }}][content][primary_button][visible]"
            value="1"
            class="rounded border-slate-300"
            {{ $primaryButtonVisibleValue ? 'checked' : '' }}>
        {{ __('Show CTA button') }}
    </label>
</div>

<div class="lg:col-span-2">
    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
        <input type="hidden"
            name="translations[{{ $code }}][content][primary_button][new_tab]"
            value="0">
        <input type="checkbox"
            name="translations[{{ $code }}][content][primary_button][new_tab]"
            value="1"
            class="rounded border-slate-300"
            {{ $primaryButtonNewTabValue ? 'checked' : '' }}>
        {{ __('Open CTA in a new tab') }}
    </label>
</div>
