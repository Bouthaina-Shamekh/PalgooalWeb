@php
    $generalSettings = \App\Models\GeneralSetting::first();
    $site_title = $generalSettings->site_title;
    $site_discretion = $generalSettings->site_discretion;
@endphp
<!-- Contact Section -->
<section
    class="py-20 px-4 sm:px-8 lg:px-24 bg-background dark:bg-gray-900 -mb-25 -z-30 relative transition-colors duration-300"
    aria-labelledby="support-heading" itemscope itemtype="https://schema.org/ContactPoint" itemprop="contactType"
    content="customer support">
    <div class="max-w-3xl mx-auto text-center">
        <h2 id="support-heading"
            class="text-title-h2 font-extrabold text-primary dark:text-white mb-4 transition-colors duration-300">
            نحن هنا لمساعدتك 24/7
        </h2>
        <p class="text-tertiary dark:text-gray-300 text-suptitle font-light mb-8 transition-colors duration-300">
            تواصل معنا بسهولة على مدار الساعة. فريق <strong itemprop="name"
                class="text-primary dark:text-white">Palgoals</strong> للدعم الفني متواجد دائمًا
            للإجابة على استفساراتك وتقديم المساعدة عبر الهاتف أو البريد الإلكتروني أو نموذج الاتصال.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-x-6 gap-y-4">
            <a href="tel:+970599123456" itemprop="telephone"
                class="group px-8 py-4 rounded-xl font-bold flex items-center justify-center gap-3 bg-gradient-to-r from-primary to-primary/90 text-white transition-all duration-300 hover:from-secondary hover:to-secondary/90 hover:scale-105 hover:shadow-lg transform">
                <svg class="w-6 h-6 transition-transform duration-300 group-hover:scale-110" fill="currentColor"
                    viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z" />
                </svg>
                <span class="font-extrabold">اتصل بنا</span>
            </a>
            <a href="mailto:info@palgoals.com" itemprop="email"
                class="group px-8 py-4 rounded-xl font-bold flex items-center justify-center gap-3 bg-gradient-to-r from-secondary to-secondary/90 text-white transition-all duration-300 hover:from-primary hover:to-primary/90 hover:scale-105 hover:shadow-lg transform">
                <svg class="w-6 h-6 transition-transform duration-300 group-hover:scale-110" fill="currentColor"
                    viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                </svg>
                <span class="font-extrabold">راسلنا عبر البريد</span>
            </a>
            <a href="#contact" aria-label="نموذج الاتصال بنا"
                class="group px-8 py-4 rounded-xl font-bold flex items-center justify-center gap-3 bg-white dark:bg-gray-800 text-primary dark:text-white border-2 border-primary/30 dark:border-gray-600 transition-all duration-300 hover:bg-primary dark:hover:bg-gray-700 hover:text-white hover:border-primary dark:hover:border-gray-500 hover:scale-105 hover:shadow-lg transform">
                <svg class="w-6 h-6 transition-transform duration-300 group-hover:scale-110" fill="currentColor"
                    viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                </svg>
                <span class="font-extrabold">تواصل معنا</span>
            </a>
        </div>
    </div>
</section>
<!-- Footer Section -->
<footer
    class="bg-gradient-to-tr from-primary/90 to-primary/95 dark:from-gray-900 dark:to-gray-800 text-white pt-12 pb-6 px-4 sm:px-8 lg:px-24 mt-16 rounded-t-3xl shadow-2xl dark:shadow-gray-900/50 transition-all duration-300"
    itemscope itemtype="https://schema.org/Organization">
    <meta itemprop="name" content="Palgoals" />
    <meta itemprop="url" content="https://palgoals.com" />
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between gap-10">
        <!-- Logo & About -->
        <div class="flex-1 mb-8 md:mb-0 text-center md:text-center">
            <a href="/" class="flex flex-col items-center justify-center md:justify-start gap-3 mb-4 group">
                <img src="{{ $settings?->logo ? asset(path: 'storage/' . $settings->logo) : asset('assets/tamplate/images/logo.svg') }}"
                    alt="Palgoals Logo" class="h-12 w-auto transition-transform group-hover:scale-105" itemprop="logo"
                    loading="lazy" />
                {{-- <span
                    class="font-extrabold text-xl tracking-tight group-hover:text-[#AE1028] transition hidden sm:inline">
                    {{ $settings?->site_title ?? t('General.' . $site_title, 'palgoals') }}
                </span> --}}
            </a>
            <p
                class="text-white/80 dark:text-gray-300 text-sm max-w-xs mx-auto md:mx-0 leading-relaxed transition-colors duration-300">
                {{ $settings?->site_discretion ?? t('General.' . $site_discretion, 'discretion') }}
            </p>
            <div class="flex flex-wrap justify-center md:justify-start gap-3 mt-5" aria-label="روابط التواصل الاجتماعي">
                @if (!empty($settings->social_links['facebook']))
                    <!-- Facebook -->
                    <a href="{{ $settings->social_links['facebook'] }}" target="_blank" rel="noopener"
                        aria-label="Facebook"
                        class="w-10 h-10 bg-white/20 dark:bg-white/10 rounded-full flex items-center justify-center hover:bg-white/30 dark:hover:bg-white/20 transition-colors duration-200">
                        <svg class="w-5 h-5 text-white dark:text-gray-200" fill="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </a>
                @endif
                @if (!empty($settings->social_links['twitter']))
                    <!-- Twitter X -->
                    <a href="{{ $settings->social_links['twitter'] }}" target="_blank" rel="noopener"
                        aria-label="Twitter X"
                        class="w-10 h-10 bg-white/20 dark:bg-white/10 rounded-full flex items-center justify-center hover:bg-white/30 dark:hover:bg-white/20 transition-colors duration-200">
                        <svg class="w-4 h-4 text-white dark:text-gray-200" fill="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path
                                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                    </a>
                @endif
                @if (!empty($settings->social_links['linkedin']))
                    <!-- LinkedIn -->
                    <a href="{{ $settings->social_links['linkedin'] }}" target="_blank" rel="noopener"
                        aria-label="LinkedIn"
                        class="w-10 h-10 bg-white/20 dark:bg-white/10 rounded-full flex items-center justify-center hover:bg-white/30 dark:hover:bg-white/20 transition-colors duration-200">
                        <svg class="w-5 h-5 text-white dark:text-gray-200" fill="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path
                                d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                        </svg>
                    </a>
                @endif
                @if (!empty($settings->social_links['instagram']))
                    <!-- Instagram -->
                    <a href="{{ $settings->social_links['instagram'] }}" target="_blank" rel="noopener"
                        aria-label="Instagram"
                        class="w-10 h-10 bg-white/20 dark:bg-white/10 rounded-full flex items-center justify-center hover:bg-white/30 dark:hover:bg-white/20 transition-colors duration-200">
                        <svg class="w-5 h-5 text-white dark:text-gray-200" fill="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path
                                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.40s-.644-1.44-1.439-1.44z" />
                        </svg>
                    </a>
                @endif
                @if (!empty($settings->social_links['whatsapp']))
                    <!-- WhatsApp -->
                    <a href="{{ $settings->social_links['whatsapp'] }}" target="_blank" rel="noopener"
                        aria-label="WhatsApp"
                        class="w-10 h-10 bg-white/20 dark:bg-white/10 rounded-full flex items-center justify-center hover:bg-white/30 dark:hover:bg-white/20 transition-colors duration-200">
                        <svg class="w-5 h-5 text-white dark:text-gray-200" fill="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488" />
                        </svg>
                    </a>
                @endif
            </div>
        </div>
        <!-- Links -->
        <div class="flex-1 grid grid-cols-2 gap-8 text-center md:text-right ltr:md:text-left">
            <div>
                <h4 class="font-extrabold text-lg mb-4 text-white dark:text-gray-100 transition-colors duration-300">
                    {{ t('frontend.Quick_Links', 'Quick Links') }}</h4>
                <ul class="space-y-2 text-white/90 dark:text-gray-300 text-sm">
                    <li><a href="#"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200">{{ t('frontend.Home', 'Home') }}</a>
                    </li>
                    <li><a href="#"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200">{{ t('frontend.Hosting', 'Hosting') }}</a>
                    </li>
                    <li><a href="#"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200">{{ t('frontend.Domain', 'Domain') }}</a>
                    </li>
                    <li><a href="#"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200">{{ t('frontend.Templates', 'Templates') }}</a>
                    </li>
                    <li><a href="#"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200">{{ t('frontend.Our_Work', 'Our Work') }}</a>
                    </li>
                    <li><a href="#"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200">{{ t('frontend.Blog', 'Blog') }}</a>
                    </li>
                    <li><a href="#"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200">{{ t('frontend.Contact_Us', 'Contact Us') }}</a>
                    </li>
                </ul>
            </div>
            <div>
                <h4 class="font-extrabold text-lg mb-4 text-white dark:text-gray-100 transition-colors duration-300">
                    {{ t('frontend.Services', 'Services') }}</h4>
                <ul class="space-y-2 text-white/90 dark:text-gray-300 text-sm">
                    <li><a href="#"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200">{{ t('frontend.Web_Design', 'Web Design') }}</a>
                    </li>
                    <li><a href="#"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200">{{ t('frontend.WordPress_Hosting', 'WordPress Hosting') }}</a>
                    </li>
                    <li><a href="#"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200">{{ t('frontend.Shared_Hosting', 'Shared Hosting') }}</a>
                    </li>
                    <li><a href="#"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200">{{ t('frontend.SEO_Optimization', 'SEO Optimization') }}</a>
                    </li>
                </ul>
            </div>
        </div>
        <!-- Contact Info -->
        <div class="flex-1 text-center md:text-right ltr:md:text-left">
            <h4
                class="font-extrabold text-lg mb-4 text-white dark:text-gray-100 capitalize transition-colors duration-300">
                {{ t('frontend.Contact_us', 'Contact Us') }}
            </h4>
            <ul class="space-y-3 text-white/90 dark:text-gray-300 text-sm">
                @if (!empty($settings->contact_info['phone']))
                    <li class="flex items-center justify-center md:justify-start gap-2">
                        <!-- Phone Icon -->
                        <svg class="w-5 h-5 text-white dark:text-gray-200" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 5a2 2 0 012-2h1.2a1 1 0 01.9.6l1.2 2.4a1 1 0 01-.2 1.1L7.5 9a16 16 0 006.5 6.5l1.5-1.5a1 1 0 011.1-.2l2.4 1.2a1 1 0 01.6.9V19a2 2 0 01-2 2h-1C9.4 21 3 14.6 3 7V6a2 2 0 012-1z" />
                        </svg>
                        <a href="tel:{{ $settings->contact_info['phone'] }}"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200 font-semibold"
                            itemprop="telephone">
                            {{ $settings->contact_info['phone'] }}
                        </a>
                    </li>
                @endif

                @if (!empty($settings->contact_info['email']))
                    <li class="flex items-center justify-center md:justify-start gap-2">
                        <!-- Mail Icon -->
                        <svg class="w-5 h-5 text-white dark:text-gray-200" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4h16c1.1 0 2 .9 2v12a2 2 0 01-2 2H4c-1.1 0-2-.9-2-2V6a2 2 0 012-2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M22 6l-10 7L2 6" />
                        </svg>
                        <a href="mailto:{{ $settings->contact_info['email'] }}"
                            class="hover:text-[#AE1028] dark:hover:text-white transition-colors duration-200 font-semibold"
                            itemprop="email">
                            {{ $settings->contact_info['email'] }}
                        </a>
                    </li>
                @endif

                @if (!empty($settings->contact_info['address']))
                    <li class="flex items-center justify-center md:justify-start gap-2">
                        <!-- Map Pin Icon -->
                        <svg class="w-5 h-5 text-white dark:text-gray-200" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 11a4 4 0 100-8 4 4 0 000 8z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 14c-4.4 0-8 1.8-8 4v2h16v-2c0-2.2-3.6-4-8-4z" />
                        </svg>
                        <span itemprop="address">{{ $settings->contact_info['address'] }}</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>
    <!-- وسائل الدفع -->
    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 border-t border-white/10 dark:border-gray-700 pt-6 transition-colors duration-300"
        itemprop="paymentAccepted">
        <span
            class="text-white/80 dark:text-gray-300 text-sm font-semibold">{{ t('frontend.Accepted_Payment_Methods', 'Accepted Payment Methods:') }}</span>
        <div class="flex items-center gap-4">
            <img src="{{ asset('assets/tamplate/images/visa.svg') }}" alt="بطاقة Visa"
                class="h-6 w-auto sm:h-8 opacity-90 hover:opacity-100 transition-opacity duration-200" loading="lazy"
                itemprop="image">
            <img src="{{ asset('assets/tamplate/images/mastercard.svg') }}" alt="بطاقة MasterCard"
                class="h-6 w-auto sm:h-8 opacity-90 hover:opacity-100 transition-opacity duration-200" loading="lazy"
                itemprop="image">
        </div>
    </div>
    <div
        class="border-t border-white/20 dark:border-gray-700 mt-10 pt-6 text-center text-xs text-white/70 dark:text-gray-400 transition-colors duration-300">
        جميع الحقوق محفوظة &copy; <span id="footer-year"></span> بال قول | Palgoals
    </div>
</footer>
