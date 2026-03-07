@php
    $logoSrc = $settings?->logo
        ? asset('storage/' . $settings->logo)
        : asset('assets/tamplate/images/logo.svg');
    $contact = $settings?->contact_info ?? [];
    $showContactBanner = (bool) ($settings?->footer_show_contact_banner ?? true);
@endphp

@if ($showContactBanner)
    <section class="px-4 sm:px-8 lg:px-24 mt-16">
        <div class="rounded-[2rem] bg-gradient-to-br from-primary to-secondary text-white px-6 sm:px-10 py-10 shadow-2xl">
            <div class="max-w-5xl mx-auto text-center space-y-6">
                <p class="text-sm uppercase tracking-[0.3em] text-white/70">{{ t('frontend.Need_Help', 'Need Help?') }}</p>
                <h2 class="text-3xl sm:text-4xl font-extrabold">{{ t('frontend.Contact_Us', 'Contact Us') }}</h2>
                <p class="text-white/80 max-w-2xl mx-auto">
                    {{ $settings?->site_discretion ?? t('frontend.Site_Description', 'Build a flexible footer system now, then extend it into full customer themes later.') }}
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    @if (! empty($contact['phone'] ?? null))
                        <a href="tel:{{ $contact['phone'] }}"
                            class="px-6 py-3 rounded-full bg-white text-primary font-semibold hover:bg-white/90 transition-colors duration-200">
                            {{ $contact['phone'] }}
                        </a>
                    @endif

                    @if (! empty($contact['email'] ?? null))
                        <a href="mailto:{{ $contact['email'] }}"
                            class="px-6 py-3 rounded-full border border-white/30 font-semibold hover:bg-white/10 transition-colors duration-200">
                            {{ $contact['email'] }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif

<footer class="bg-white dark:bg-[#171717] text-slate-700 dark:text-slate-200 px-4 sm:px-8 lg:px-24 pt-10 pb-8">
    <div class="max-w-5xl mx-auto text-center space-y-6">
        <a href="{{ url('/') }}" class="inline-flex items-center justify-center group">
            <img src="{{ $logoSrc }}" alt="{{ $settings?->site_title ?? config('app.name', 'Palgoals') }}"
                class="h-12 w-auto transition-transform group-hover:scale-105" loading="lazy" />
        </a>

        <nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-sm font-medium">
            <ul class="flex flex-wrap items-center justify-center gap-x-6 gap-y-3">
                @include('front.layouts.partials.footer.menu-links', [
                    'footerMenu' => $footerMenu ?? null,
                    'linkClass' => 'hover:text-primary transition-colors duration-200',
                ])
            </ul>
        </nav>

        <div class="flex justify-center text-primary dark:text-white">
            @include('front.layouts.partials.footer.social-links', [
                'wrapperClass' => 'flex flex-wrap justify-center gap-3',
                'linkClass' => 'w-10 h-10 rounded-full border border-current/15 bg-current/5 hover:bg-current/10 flex items-center justify-center transition-colors duration-200',
            ])
        </div>

        <div class="flex justify-center text-sm text-slate-500 dark:text-slate-400">
            @include('front.layouts.partials.footer.contact-items', [
                'listClass' => 'space-y-2',
                'itemClass' => 'flex items-center justify-center gap-2',
                'linkClass' => 'hover:text-primary transition-colors duration-200',
            ])
        </div>

        <div class="border-t border-slate-200 dark:border-white/10 pt-6 text-xs text-slate-500 dark:text-slate-400">
            &copy; {{ now()->year }} {{ $settings?->site_title ?? 'Palgoals' }}. {{ t('frontend.All_Rights_Reserved', 'All rights reserved.') }}
        </div>
    </div>
</footer>
