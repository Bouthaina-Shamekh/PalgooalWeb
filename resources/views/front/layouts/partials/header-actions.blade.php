@php
    $containerClass = $containerClass ?? 'flex items-center gap-2 sm:gap-4';
    $authButtonClass = $authButtonClass ?? 'inline-flex items-center gap-2 px-4 py-1.5 rounded-lg border border-primary text-primary dark:text-white dark:border-white text-sm font-semibold hover:bg-primary/10 dark:hover:bg-white/20 transition-all duration-200';
    $mobileToggleClass = $mobileToggleClass ?? 'md:hidden p-2 rounded text-primary dark:text-white hover:bg-primary/10 dark:hover:bg-white/20';
@endphp

<div class="{{ $containerClass }}">
    <x-lang.language-switcher />

    @auth('client')
        <a
            href="{{ route('client.home') }}"
            id="user-menu-toggle"
            class="{{ $authButtonClass }}"
            aria-label="{{ t('frontend.Client_Area', 'Client Area') }}"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M5.121 17.804A11.963 11.963 0 0112 15c2.21 0 4.266.642 5.879 1.742M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{{ t('frontend.Hello', 'Hello') }}: {{ Auth::guard('client')->user()->first_name }}</span>
        </a>
    @else
        <a
            href="{{ url('/client/login') }}"
            id="user-menu-toggle"
            class="{{ $authButtonClass }}"
            aria-label="{{ t('frontend.Login', 'Login / Register') }}"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M5.121 17.804A11.963 11.963 0 0112 15c2.21 0 4.266.642 5.879 1.742M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{{ t('frontend.Login', 'Login / Register') }}</span>
        </a>
    @endauth

    <button
        id="sidebar-toggle"
        class="{{ $mobileToggleClass }}"
        aria-label="{{ t('frontend.Menu', 'Open Menu') }}"
    >
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
</div>
