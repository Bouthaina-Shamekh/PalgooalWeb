@php
    $showPaymentMethods = (bool) ($settings?->footer_show_payment_methods ?? true);
    $footerMenuLinks = collect();

    if (!empty($footerMenu?->items)) {
        foreach ($footerMenu->items as $item) {
            if (in_array($item->type, ['link', 'page'], true)) {
                $footerMenuLinks->push([
                    'label' => (string) $item->label,
                    'url' => (string) $item->url,
                ]);
            }

            if ($item->type === 'dropdown') {
                foreach ($item->processedChildren as $child) {
                    $footerMenuLinks->push([
                        'label' => (string) ($child['current_label'] ?? ''),
                        'url' => (string) ($child['current_url'] ?? '#'),
                    ]);
                }
            }
        }
    }

    $footerMenuLinks = $footerMenuLinks
        ->filter(fn (array $link) => trim((string) ($link['label'] ?? '')) !== '')
        ->values();

    $footerMenuColumns = $footerMenuLinks->isNotEmpty()
        ? $footerMenuLinks->chunk((int) ceil($footerMenuLinks->count() / 2))
        : collect();
@endphp

<style>
    .text-gray-dark {
        color: #8E8E8E;
    }

    .text-purple-brand {
        color: #240A37;
    }

    .hover\:text-purple-brand:hover {
        color: #240A37;
    }
</style>

<!-- Footer -->
<footer class="bg-[#F3F4F6] pt-16 pb-4 md:pb-8 px-4 sm:px-6 lg:px-12 text-start">
    <div class="container mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-24 mb-4 md:mb-8">

            <!-- Column 1: Company Info -->
            <div>
                <div class="flex items-center justify-start gap-4 mb-6">
                    <div>
                        <img src="{{ asset('assets/tamplate/images/logo.svg') }}" alt="PalGoals" class="w-full">
                    </div>
                </div>
                <p class="text-gray-dark text-base leading-relaxed max-w-sm">
                    Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt
                    ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo
                    dolores et ea rebum.
                </p>
            </div>

            <!-- Column 2: Pages Links -->
            <div>
                <h3 class="font-bold text-2xl md:text-3xl text-black mb-2 uppercase tracking-wide">PAGES</h3>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                    @if ($footerMenuColumns->isNotEmpty())
                        @foreach ([0, 1] as $columnIndex)
                            <ul class="space-y-3">
                                @foreach ($footerMenuColumns->get($columnIndex, collect()) as $menuLink)
                                    <li>
                                        <a href="{{ $menuLink['url'] ?: '#' }}"
                                            class="text-gray-dark hover:text-purple-brand transition text-base">
                                            {{ $menuLink['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    @else
                        <ul class="space-y-3">
                            <li><a href="{{ route('frontend.home') }}" class="text-gray-dark hover:text-purple-brand transition text-base">{{ t('frontend.Home', 'Home') }}</a></li>
                            <li><a href="{{ route('domains.page') }}" class="text-gray-dark hover:text-purple-brand transition text-base">{{ t('frontend.Domain', 'Domains') }}</a></li>
                        </ul>
                        <ul class="space-y-3">
                            <li><a href="{{ route('cart') }}" class="text-gray-dark hover:text-purple-brand transition text-base">{{ t('frontend.Cart', 'Cart') }}</a></li>
                            <li><a href="{{ route('testimonials.submit') }}" class="text-gray-dark hover:text-purple-brand transition text-base">{{ t('frontend.Contact_Us', 'Contact Us') }}</a></li>
                        </ul>
                    @endif
                </div>
            </div>

            <!-- Column 3: Payment & Help -->
            <div class="grid grid-cols-2 md:grid-cols-1 gap-x-4 gap-y-3">
                <div>
                    <h3 class="font-bold text-2xl md:text-3xl text-black mb-2 uppercase tracking-wide">PAYMENT</h3>
                    @if ($showPaymentMethods)
                        <div class="flex items-center gap-4 mb-8">
                            <div class="bg-white p-2 rounded-lg shadow-sm w-16 h-10 flex items-center justify-center">
                                <img src="{{ asset('assets/tamplate/images/mastercard.svg') }}" class="w-full" alt="Mastercard">
                            </div>
                            <div class="bg-white p-2 rounded-lg shadow-sm w-16 h-10 flex items-center justify-center">
                                <img src="{{ asset('assets/tamplate/images/visa.svg') }}" class="w-full" alt="Visa">
                            </div>
                        </div>
                    @endif
                </div>
                <div>
                    <p class="text-black text-lg font-bold mb-1">NEED HELP?</p>
                    <p class="font-medium md:font-bold text-xl text-black">
                        (+800) 1234 5678 90
                    </p>
                </div>
            </div>

            <div class=" items-center justify-center gap-4 ltr:flex-row rtl:flex-row-reverse flex md:hidden">
                <span class="text-black text-base uppercase">FOLLOW US:</span>
                <div class="flex items-center gap-5">
                    <a href="#"
                        class="text-gray-500 hover:text-purple-brand hover:-translate-y-1 transition-all duration-300">
                        <img src="{{ asset('assets/imgs/icons/icon-awesome-facebook-f.svg') }}"
                            class="w-5 h-5 opacity-60 hover:opacity-100" alt="Facebook">
                    </a>
                    <a href="#"
                        class="text-gray-500 hover:text-purple-brand hover:-translate-y-1 transition-all duration-300">
                        <img src="{{ asset('assets/imgs/icons/icon-awesome-snapchat-ghost.svg') }}"
                            class="w-5 h-5 opacity-60 hover:opacity-100" alt="Snapchat">
                    </a>
                    <a href="#"
                        class="text-gray-500 hover:text-purple-brand hover:-translate-y-1 transition-all duration-300">
                        <img src="{{ asset('assets/imgs/icons/icon-awesome-linkedin-in.svg') }}"
                            class="w-5 h-5 opacity-60 hover:opacity-100" alt="Linkedin">
                    </a>
                    <a href="#"
                        class="text-gray-500 hover:text-purple-brand hover:-translate-y-1 transition-all duration-300">
                        <svg class="w-5 h-5 opacity-60 hover:opacity-100 fill-current" viewBox="0 0 24 24">
                            <path
                                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                    </a>
                    <a href="#"
                        class="text-gray-500 hover:text-purple-brand hover:-translate-y-1 transition-all duration-300">
                        <img src="{{ asset('assets/imgs/icons/icon-awesome-instagram.svg') }}"
                            class="w-5 h-5 opacity-60 hover:opacity-100" alt="Instagram">
                    </a>
                </div>
            </div>
        </div>

        <!-- Bottom Row -->
        <div class="border-t border-gray-300 pt-4 md:pt-8 flex flex-col-reverse md:flex-row justify-between items-center gap-6">
            <p class="text-gray-dark text-base ltr:text-left rtl:text-right">
                All rights reserved to PalGoals company © 2025
            </p>

            <div class=" items-center gap-4 ltr:flex-row rtl:flex-row-reverse hidden md:flex">
                <span class="text-[#8E8E8E] text-base uppercase">FOLLOW US:</span>
                <div class="flex items-center gap-5">
                    <a href="#"
                        class="text-gray-500 hover:text-purple-brand hover:-translate-y-1 transition-all duration-300">
                        <img src="{{ asset('assets/imgs/icons/icon-awesome-facebook-f.svg') }}"
                            class="w-5 h-5 opacity-60 hover:opacity-100" alt="Facebook">
                    </a>
                    <a href="#"
                        class="text-gray-500 hover:text-purple-brand hover:-translate-y-1 transition-all duration-300">
                        <img src="{{ asset('assets/imgs/icons/icon-awesome-snapchat-ghost.svg') }}"
                            class="w-5 h-5 opacity-60 hover:opacity-100" alt="Snapchat">
                    </a>
                    <a href="#"
                        class="text-gray-500 hover:text-purple-brand hover:-translate-y-1 transition-all duration-300">
                        <img src="{{ asset('assets/imgs/icons/icon-awesome-linkedin-in.svg') }}"
                            class="w-5 h-5 opacity-60 hover:opacity-100" alt="Linkedin">
                    </a>
                    <a href="#"
                        class="text-gray-500 hover:text-purple-brand hover:-translate-y-1 transition-all duration-300">
                        <svg class="w-5 h-5 opacity-60 hover:opacity-100 fill-current" viewBox="0 0 24 24">
                            <path
                                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                    </a>
                    <a href="#"
                        class="text-gray-500 hover:text-purple-brand hover:-translate-y-1 transition-all duration-300">
                        <img src="{{ asset('assets/imgs/icons/icon-awesome-instagram.svg') }}"
                            class="w-5 h-5 opacity-60 hover:opacity-100" alt="Instagram">
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

