@php
    $showPromoBar = (bool) ($settings?->header_show_promo_bar ?? true);
    $headerIsSticky = (bool) ($settings?->header_is_sticky ?? true);
@endphp

@if ($showPromoBar)
    @include('front.layouts.partials.promo-bar')
@endif

<header class="bg-white dark:bg-[#1c1c1c] shadow-md {{ $headerIsSticky ? 'sticky top-0 z-50' : 'relative z-30' }}">
    <div class="flex items-center justify-between py-3 px-4 md:px-8 lg:px-24 h-20">
        @include('front.layouts.partials.header-brand')

        @include('front.layouts.partials.desktop-nav', ['header' => $header])

        @include('front.layouts.partials.header-actions')
    </div>

    <div id="sidebar-overlay"
        class="fixed inset-0 bg-black bg-opacity-20 z-40 hidden opacity-0 md:hidden transition-opacity duration-300">
    </div>

    @include('front.layouts.partials.mobile-sidebar', ['header' => $header])
</header>
