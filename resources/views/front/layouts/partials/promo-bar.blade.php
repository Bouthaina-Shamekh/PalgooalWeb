@php
    $promoStickyClass = ($settings?->header_is_sticky ?? true)
        ? 'sticky top-0 z-40'
        : 'relative z-20';
@endphp

<div
    id="promo-bar"
    role="banner"
    aria-label="{{ t('frontend.Promo_Banner', 'Promotional Banner') }}"
    class="{{ $promoStickyClass }} px-4 sm:px-20 py-4 shadow-md text-sm sm:text-base text-white bg-secondary dark:bg-primary dark:text-gray-100 transition-opacity duration-300"
>
    <div class="container mx-auto flex flex-col md:flex-row justify-between items-center gap-2 text-center md:text-start">
        <div class="flex flex-wrap items-center gap-2 justify-center md:justify-start text-start">
            <span class="font-bold">{{ t('frontend.Limited_Offer', 'Limited Offer:') }}</span>
            <span>{{ t('frontend.Promo_Text', 'Launch your site in minutes with a lower setup cost.') }}</span>
            <a href="/templates" class="underline underline-offset-4 hover:text-primary dark:hover:text-primary transition font-bold">
                {{ t('frontend.Browse_Templates', 'Browse Templates') }}
            </a>
        </div>

        <div class="flex items-center gap-2 font-semibold">
            <span>{{ t('frontend.Ends_In', 'Ends in:') }}</span>
            <span id="promo-time" class="bg-white text-secondary dark:bg-gray-200 dark:text-primary px-2 py-0.5 rounded text-sm"></span>
        </div>
    </div>

    <button
        id="close-promo-bar"
        class="absolute top-2 right-4 text-white dark:text-gray-200 hover:text-primary dark:hover:text-yellow-400 transition text-xl font-bold leading-none"
        aria-label="{{ t('frontend.Close_Banner', 'Close Banner') }}"
    >
        &times;
    </button>
</div>
