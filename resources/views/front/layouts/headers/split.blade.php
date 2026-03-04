@php
    $showPromoBar = (bool) ($settings?->header_show_promo_bar ?? true);
    $headerIsSticky = (bool) ($settings?->header_is_sticky ?? true);
@endphp

@if ($showPromoBar)
    @include('front.layouts.partials.promo-bar')
@endif

<header class="bg-white/95 dark:bg-[#1b1b1b]/95 backdrop-blur shadow-sm border-b border-gray-200/80 dark:border-white/10 {{ $headerIsSticky ? 'sticky top-0 z-50' : 'relative z-30' }}">
    <div class="px-4 md:px-8 lg:px-24 py-3">
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-4">
            <div class="hidden md:flex items-center">
                @include('front.layouts.partials.desktop-nav', ['header' => $header])
            </div>

            <div class="justify-self-center">
                @include('front.layouts.partials.header-brand', [
                    'wrapperClass' => 'flex items-center justify-center group',
                    'imageClass' => 'h-11 w-auto transition-transform group-hover:scale-105 will-change-transform',
                ])
            </div>

            <div class="justify-self-end">
                @include('front.layouts.partials.header-actions', [
                    'authButtonClass' => 'inline-flex items-center gap-2 px-4 py-2 rounded-full border border-primary/20 bg-primary/5 text-primary dark:bg-white/10 dark:text-white dark:border-white/20 text-sm font-semibold hover:bg-primary/10 dark:hover:bg-white/20 transition-all duration-200',
                ])
            </div>
        </div>
    </div>

    <div id="sidebar-overlay"
        class="fixed inset-0 bg-black bg-opacity-20 z-40 hidden opacity-0 md:hidden transition-opacity duration-300">
    </div>

    @include('front.layouts.partials.mobile-sidebar', ['header' => $header])
</header>
