<!-- Promo Bar -->
@include('tamplate.layouts.partials.promo-bar')
<!-- Header -->
<header class="bg-white dark:bg-[#1c1c1c] shadow-md sticky top-0 z-50">
    <div class="flex items-center justify-between py-3 px-4 md:px-8 lg:px-24 h-20">
        <!-- Logo -->
        <a href="/" class="flex items-center gap-2 group">
            <img src="{{$settings?->logo ? asset('storage/' . $settings->logo) : asset('assets/tamplate/images/logo.svg') }}" alt="Palgoals Logo" loading="eager"
                fetchpriority="high"
                class="h-10 w-auto transition-transform group-hover:scale-105 will-change-transform" />
        </a>
        <!-- Desktop Navigation -->
        @php
            use App\Models\Header;
            $header = \App\Models\Header::with(['items.translations', 'items.page.translations'])->first();
        @endphp
        @include('tamplate.layouts.partials.desktop-nav', ['header' => $header])

        <!-- Header Actions -->
        <div class="flex items-center gap-2 sm:gap-4">
            <!-- Language Switch -->
            <x-lang.language-switcher />
            <!-- User Menu -->
            @auth('client')
                <a href="{{ route('client.home') }}" id="user-menu-toggle"
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg border border-primary text-primary dark:text-white dark:border-white text-sm font-semibold hover:bg-primary/10 dark:hover:bg-white/20 transition-all duration-200"
                    aria-label="القائمة الشخصية">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5.121 17.804A11.963 11.963 0 0112 15c2.21 0 4.266.642 5.879 1.742M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>أهلا بك : {{ Auth::guard('client')->user()->first_name }}</span>
                </a>
            @else
                <a href="/client/login" id="user-menu-toggle"
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg border border-primary text-primary dark:text-white dark:border-white text-sm font-semibold hover:bg-primary/10 dark:hover:bg-white/20 transition-all duration-200"
                    aria-label="القائمة الشخصية">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5.121 17.804A11.963 11.963 0 0112 15c2.21 0 4.266.642 5.879 1.742M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>تسجيل / دخول</span>
                </a>
            @endauth
            <!-- Mobile Toggle -->
            <button id="sidebar-toggle"
                class="md:hidden p-2 rounded text-primary dark:text-white hover:bg-primary/10 dark:hover:bg-white/20"
                aria-label="فتح القائمة">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>
    <!-- Overlay خلفي للشريط الجانبي -->
    <div id="sidebar-overlay"
        class="fixed inset-0 bg-black bg-opacity-20 z-40 hidden opacity-0 md:hidden transition-opacity duration-300">
    </div>
    <!-- Sidebar Mobile Menu -->
    @include('tamplate.layouts.partials.mobile-sidebar', ['header' => $header])

</header>
