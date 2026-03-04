@php
    $showPromoBar = (bool) ($settings?->header_show_promo_bar ?? true);
    $headerIsSticky = (bool) ($settings?->header_is_sticky ?? true);
@endphp

@if ($showPromoBar)
    @include('front.layouts.partials.promo-bar')
@endif

<header class="bg-white dark:bg-[#1c1c1c] shadow-md border-b border-gray-100 dark:border-white/10 {{ $headerIsSticky ? 'sticky top-0 z-50' : 'relative z-30' }}">
    <div class="px-4 md:px-8 lg:px-24 py-4">
        <div class="flex items-center justify-between gap-4">
            @include('front.layouts.partials.header-brand', [
                'imageClass' => 'h-10 md:h-11 w-auto transition-transform group-hover:scale-105 will-change-transform',
            ])

            @include('front.layouts.partials.header-actions')
        </div>

        <div class="hidden md:flex justify-center mt-4 pt-4 border-t border-gray-100 dark:border-white/10">
            @include('front.layouts.partials.desktop-nav', ['header' => $header])
        </div>
    </div>

    <div id="sidebar-overlay"
        class="fixed inset-0 bg-black bg-opacity-20 z-40 hidden opacity-0 md:hidden transition-opacity duration-300">
    </div>

    @include('front.layouts.partials.mobile-sidebar', ['header' => $header])
</header>
