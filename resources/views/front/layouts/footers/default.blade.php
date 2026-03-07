@php
    $logoSrc = $settings?->logo
        ? asset('storage/' . $settings->logo)
        : asset('assets/tamplate/images/logo.svg');
    $showContactBanner = (bool) ($settings?->footer_show_contact_banner ?? true);
    $showPaymentMethods = (bool) ($settings?->footer_show_payment_methods ?? true);
    $contactInfo = $settings?->resolved_contact_info ?? [];
@endphp

@if ($showContactBanner)
    <section class="py-20 px-4 sm:px-8 lg:px-24 bg-background dark:bg-gray-900 -mb-20 relative transition-colors duration-300">
        <div class="max-w-5xl mx-auto rounded-[2rem] bg-white dark:bg-slate-900 shadow-xl px-8 py-10 border border-slate-100 dark:border-white/10">
            <div class="grid gap-8 lg:grid-cols-[1.5fr_auto] items-center">
                <div class="space-y-4 text-center lg:text-start">
                    <p class="text-sm uppercase tracking-[0.25em] text-primary/70">{{ t('frontend.Need_Help', 'Need Help?') }}</p>
                    <h2 class="text-3xl font-extrabold text-primary dark:text-white">{{ t('frontend.Contact_Us', 'Contact Us') }}</h2>
                    <p class="text-slate-600 dark:text-slate-300 max-w-2xl">
                        {{ $settings?->resolved_site_discretion ?? t('frontend.Site_Description', 'Use this section as the final conversion band before the footer content starts.') }}
                    </p>
                </div>
                <div class="flex flex-wrap justify-center gap-4">
                    @if (! empty($contactInfo['phone'] ?? null))
                        <a href="tel:{{ $contactInfo['phone'] }}"
                            class="px-6 py-3 rounded-full bg-primary text-white font-semibold hover:bg-primary/90 transition-colors duration-200">
                            {{ $contactInfo['phone'] }}
                        </a>
                    @endif

                    @if (! empty($contactInfo['email'] ?? null))
                        <a href="mailto:{{ $contactInfo['email'] }}"
                            class="px-6 py-3 rounded-full border border-primary/20 text-primary dark:text-white dark:border-white/20 font-semibold hover:bg-primary/5 dark:hover:bg-white/10 transition-colors duration-200">
                            {{ $contactInfo['email'] }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif

<footer class="bg-gradient-to-tr from-primary/95 to-primary/85 dark:from-gray-900 dark:to-gray-800 text-white pt-14 pb-6 px-4 sm:px-8 lg:px-24 mt-16 rounded-t-[2rem] shadow-2xl transition-all duration-300">
    <div class="max-w-7xl mx-auto grid gap-10 lg:grid-cols-[1.2fr_1fr_1fr]">
        <div class="space-y-5 text-center lg:text-start">
            <a href="{{ url('/') }}" class="inline-flex items-center justify-center lg:justify-start group">
                <img src="{{ $logoSrc }}" alt="{{ $settings?->resolved_site_title ?? config('app.name', 'Palgoals') }}"
                    class="h-12 w-auto transition-transform group-hover:scale-105" loading="lazy" />
            </a>
            <p class="text-white/80 max-w-sm mx-auto lg:mx-0 leading-7">
                {{ $settings?->resolved_site_discretion ?? config('app.name', 'Palgoals') }}
            </p>
            <div class="flex justify-center lg:justify-start">
                @include('front.layouts.partials.footer.social-links', [
                    'wrapperClass' => 'flex flex-wrap gap-3',
                    'linkClass' => 'w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center transition-colors duration-200',
                ])
            </div>
        </div>

        <div class="text-center lg:text-start">
            <h4 class="font-extrabold text-lg mb-4">{{ t('frontend.Quick_Links', 'Quick Links') }}</h4>
            <ul class="space-y-3 text-white/85 text-sm">
                @include('front.layouts.partials.footer.menu-links', [
                    'footerMenu' => $footerMenu ?? null,
                    'linkClass' => 'hover:text-white transition-colors duration-200',
                ])
            </ul>
        </div>

        <div class="text-center lg:text-start">
            <h4 class="font-extrabold text-lg mb-4">{{ t('frontend.Contact_Us', 'Contact Us') }}</h4>
            <div class="text-white/85">
                @include('front.layouts.partials.footer.contact-items', [
                    'listClass' => 'space-y-3 text-sm',
                    'itemClass' => 'flex items-center justify-center lg:justify-start gap-3',
                    'linkClass' => 'hover:text-white transition-colors duration-200',
                ])
            </div>
        </div>
    </div>

    @if ($showPaymentMethods)
        <div class="max-w-7xl mx-auto mt-10 pt-6 border-t border-white/15 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-white/80">
            <span>{{ t('frontend.Accepted_Payment_Methods', 'Accepted Payment Methods:') }}</span>
            <div class="flex items-center gap-4">
                <img src="{{ asset('assets/tamplate/images/visa.svg') }}" alt="Visa" class="h-6 w-auto opacity-90" loading="lazy">
                <img src="{{ asset('assets/tamplate/images/mastercard.svg') }}" alt="MasterCard" class="h-6 w-auto opacity-90" loading="lazy">
            </div>
        </div>
    @endif

    <div class="max-w-7xl mx-auto border-t border-white/20 mt-8 pt-6 text-center text-xs text-white/70">
        &copy; {{ now()->year }} {{ $settings?->resolved_site_title ?? 'Palgoals' }}. {{ t('frontend.All_Rights_Reserved', 'All rights reserved.') }}
    </div>
</footer>
