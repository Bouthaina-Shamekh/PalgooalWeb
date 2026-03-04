@php
    $logoSrc = $settings?->logo
        ? asset('storage/' . $settings->logo)
        : asset('assets/tamplate/images/logo.svg');
    $showPaymentMethods = (bool) ($settings?->footer_show_payment_methods ?? true);
@endphp

<footer class="bg-slate-950 text-slate-100 mt-16">
    <div class="px-4 sm:px-8 lg:px-24 py-14">
        <div class="grid gap-10 lg:grid-cols-[1.4fr_1fr_1fr] items-start">
            <div class="space-y-5">
                <a href="{{ url('/') }}" class="inline-flex items-center group">
                    <img src="{{ $logoSrc }}" alt="{{ $settings?->site_title ?? config('app.name', 'Palgoals') }}"
                        class="h-12 w-auto transition-transform group-hover:scale-105" loading="lazy" />
                </a>
                <p class="max-w-md text-slate-300 leading-7">
                    {{ $settings?->site_discretion ?? config('app.name', 'Palgoals') }}
                </p>
                @include('front.layouts.partials.footer.social-links', [
                    'wrapperClass' => 'flex flex-wrap gap-3 text-slate-100',
                    'linkClass' => 'w-10 h-10 rounded-full border border-white/10 bg-white/5 hover:bg-white/10 flex items-center justify-center transition-colors duration-200',
                ])
            </div>

            <div>
                <h4 class="text-lg font-semibold mb-4">{{ t('frontend.Quick_Links', 'Quick Links') }}</h4>
                <ul class="space-y-3 text-sm text-slate-300">
                    <li><a href="{{ route('frontend.home') }}" class="hover:text-white transition-colors duration-200">{{ t('frontend.Home', 'Home') }}</a></li>
                    <li><a href="{{ route('domains.page') }}" class="hover:text-white transition-colors duration-200">{{ t('frontend.Domain', 'Domains') }}</a></li>
                    <li><a href="{{ route('cart') }}" class="hover:text-white transition-colors duration-200">{{ t('frontend.Cart', 'Cart') }}</a></li>
                    <li><a href="{{ route('testimonials.submit') }}" class="hover:text-white transition-colors duration-200">{{ t('frontend.Contact_Us', 'Contact Us') }}</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-lg font-semibold mb-4">{{ t('frontend.Contact_Us', 'Contact Us') }}</h4>
                <div class="text-slate-300">
                    @include('front.layouts.partials.footer.contact-items', [
                        'listClass' => 'space-y-3 text-sm',
                        'itemClass' => 'flex items-start gap-3',
                        'linkClass' => 'hover:text-white transition-colors duration-200',
                    ])
                </div>
            </div>
        </div>

        @if ($showPaymentMethods)
            <div class="mt-10 pt-5 border-t border-white/10 flex flex-col gap-3 md:flex-row md:items-center md:justify-between text-sm text-slate-400">
                <span>{{ t('frontend.Accepted_Payment_Methods', 'Accepted Payment Methods:') }}</span>
                <div class="flex items-center gap-4">
                    <img src="{{ asset('assets/tamplate/images/visa.svg') }}" alt="Visa" class="h-6 w-auto opacity-90" loading="lazy" />
                    <img src="{{ asset('assets/tamplate/images/mastercard.svg') }}" alt="MasterCard" class="h-6 w-auto opacity-90" loading="lazy" />
                </div>
            </div>
        @endif

        <div class="mt-6 text-xs text-slate-500">
            &copy; {{ now()->year }} {{ $settings?->site_title ?? 'Palgoals' }}. {{ t('frontend.All_Rights_Reserved', 'All rights reserved.') }}
        </div>
    </div>
</footer>
