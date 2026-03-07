@php
    $wrapperClass = $wrapperClass ?? 'flex items-center gap-2 group';
    $imageClass = $imageClass ?? 'h-10 w-auto transition-transform group-hover:scale-105 will-change-transform';
    $logoSrc = $settings?->logo
        ? asset('storage/' . $settings->logo)
        : asset('assets/tamplate/images/logo.svg');
    $logoAlt = $settings?->resolved_site_title ?: config('app.name', 'Palgoals');
@endphp

<a href="{{ url('/') }}" class="{{ $wrapperClass }}">
    <img
        src="{{ $logoSrc }}"
        alt="{{ $logoAlt }}"
        loading="eager"
        fetchpriority="high"
        class="{{ $imageClass }}"
    />
</a>
