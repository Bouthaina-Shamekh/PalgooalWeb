@php
    $shortDesc = Str::limit(strip_tags($translation?->description ?? ''), 160);
@endphp
<x-template.layouts.index-layouts
    title="{{ t('Frontend.Checkout', 'Checkout') }} - {{ t('Frontend.Palgoals', 'Palgoals') }}"
    description="{{ $shortDesc }}" keywords="خدمات حجز دومين , افضل شركة برمجيات , استضافة مواقع , ..."
    ogImage="{{ asset('assets/dashboard/images/logo-white.svg') }}">

    <livewire:checkout-client :template_id="$template_id" />
</x-template.layouts.index-layouts>
