@php
    $variantSettings = is_array($headerVariantSettings ?? null) ? $headerVariantSettings : [];
    $socialLinks = is_array($settings?->social_links ?? null) ? $settings->social_links : [];
    $currentLocale = strtolower((string) app()->getLocale());
    $fallbackLocale = strtolower((string) config('app.fallback_locale', 'en'));

    $resolveLocalizedText = static function ($value, string $default = '') use ($currentLocale, $fallbackLocale): string {
        if (is_array($value)) {
            $normalizedValues = [];
            foreach ($value as $langKey => $langValue) {
                $normalizedValues[strtolower((string) $langKey)] = $langValue;
            }

            $localizedValue = trim((string) (
                $normalizedValues[$currentLocale]
                ?? $normalizedValues[$fallbackLocale]
                ?? ''
            ));

            if ($localizedValue !== '') {
                return $localizedValue;
            }

            foreach ($normalizedValues as $candidate) {
                $candidate = trim((string) $candidate);
                if ($candidate !== '') {
                    return $candidate;
                }
            }

            return $default;
        }

        $scalar = trim((string) $value);
        return $scalar !== '' ? $scalar : $default;
    };

    $announcementText = $resolveLocalizedText(
        $variantSettings['announcement_text'] ?? null,
        'Launch your own website in 5 minutes at minimal cost',
    );
    $showSocialIcons = (bool) ($variantSettings['show_social_icons'] ?? true);
    $showLoginButton = (bool) ($variantSettings['show_login_button'] ?? true);
    $loginLabel = $resolveLocalizedText($variantSettings['login_label'] ?? null, 'Login');
    $loginUrl = trim((string) ($variantSettings['login_url'] ?? '/client/login'));
    $usesClientDashboardLogin = trim($loginUrl, '/') === 'client/login';
    $canAccessClientDashboard = auth('client')->check()
        && session('client_impersonated_by_admin')
        && auth('web')->check()
        && (int) session('client_impersonator_admin_id') === (int) auth('web')->id();
    $resolvedLoginUrl = $usesClientDashboardLogin && $canAccessClientDashboard
        ? route('client.home')
        : $loginUrl;
    $resolvedLoginLabel = $usesClientDashboardLogin && $canAccessClientDashboard
        ? t('frontend.Client_Area', 'Client Area')
        : $loginLabel;
    $shouldRenderLoginButton = $showLoginButton && (!$usesClientDashboardLogin || $canAccessClientDashboard);
    $showLanguageSwitcher = (bool) ($variantSettings['show_language_switcher'] ?? true);
    $contactButtonLabel = $resolveLocalizedText($variantSettings['contact_button_label'] ?? null, 'Contact us');
    $contactButtonUrl = trim((string) ($variantSettings['contact_button_url'] ?? '#contact'));
    $headerIsSticky = (bool) ($settings?->header_is_sticky ?? true);
    $showPromoBar = (bool) ($settings?->header_show_promo_bar ?? true);
    $colorThemeKey = strtolower(trim((string) ($variantSettings['color_theme'] ?? '')));

    $defaultThemeClasses = [
        'promo_bar' => 'bg-purple-brand text-white',
        'promo_hover' => 'hover:text-red-brand',
        'social_icon' => 'fill-[#7F6F8A] hover:fill-red-brand',
        'nav_shell' => 'bg-white border-gray-100',
        'nav_text' => 'text-black',
        'nav_hover' => 'hover:text-red-brand',
        'dropdown_shell' => 'bg-white border-gray-200',
        'dropdown_item' => 'text-black hover:bg-gray-100 hover:text-red-brand',
        'contact_btn' => 'text-purple-brand border-red-brand hover:bg-red-brand hover:text-white',
        'hamburger_bar' => 'bg-purple-brand',
        'mobile_panel' => 'bg-white',
        'mobile_link_border' => 'border-gray-100',
        'mobile_subtext' => 'text-gray-dark',
    ];

    $configuredThemes = config('front_layouts.color_libraries.purple_topbar.themes', []);
    if (!is_array($configuredThemes)) {
        $configuredThemes = [];
    }

    $resolvedThemes = [];
    foreach ($configuredThemes as $themeKey => $themeConfig) {
        $normalizedThemeKey = strtolower(trim((string) $themeKey));
        if ($normalizedThemeKey === '' || !is_array($themeConfig)) {
            continue;
        }

        $themeClasses = is_array($themeConfig['classes'] ?? null)
            ? $themeConfig['classes']
            : $themeConfig;

        if (!is_array($themeClasses)) {
            continue;
        }

        $resolvedThemes[$normalizedThemeKey] = array_replace($defaultThemeClasses, $themeClasses);
    }

    $defaultThemeKey = strtolower(trim((string) config('front_layouts.color_libraries.purple_topbar.default', 'classic')));
    if (!array_key_exists($defaultThemeKey, $resolvedThemes)) {
        $defaultThemeKey = (string) (array_key_first($resolvedThemes) ?? 'classic');
    }

    $activeThemeKey = array_key_exists($colorThemeKey, $resolvedThemes) ? $colorThemeKey : $defaultThemeKey;
    $theme = $resolvedThemes[$activeThemeKey] ?? $defaultThemeClasses;

    $logoPath = $variantSettings['logo_override'] ?? null;
    if (empty($logoPath)) {
        $logoPath = $settings?->logo;
    }

    $logoSrc = !empty($logoPath)
        ? (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', '//'])
            ? $logoPath
            : asset('storage/' . ltrim($logoPath, '/')))
        : asset('assets/tamplate/images/logo.svg');
@endphp

@if ($showPromoBar)
    <header class="relative z-[60] py-3 px-4 sm:px-6 lg:px-12 {{ $theme['promo_bar'] }}">
        <div class="container mx-auto flex flex-wrap justify-around md:justify-between items-center gap-3 md:gap-0">
            <!-- Announcement Text -->
            <p class="text-base md:text-lg text-center md:text-start">
                {{ $announcementText }}
            </p>

            @if ($showSocialIcons)
                <!-- Social Media Icons -->
                <div class="flex items-center gap-4 md:gap-6">
                    <!-- Facebook -->
                    <a href="{{ $socialLinks['facebook'] ?? '#' }}"
                        class="{{ $theme['social_icon'] }} opacity-70 hover:opacity-100 hover:scale-110 transition-all duration-300">
                        <!-- Facebook -->
                        <svg class="h-4" viewBox="0 0 10 18" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M8.55813 9.61851L9.03305 6.52389H6.06367V4.51569C6.06367 3.66906 6.47847 2.84381 7.80836 2.84381H9.15829V0.209069C9.15829 0.209069 7.93326 0 6.76201 0C4.31664 0 2.71823 1.48219 2.71823 4.16535V6.52389H0V9.61851H2.71823V17.0996H6.06367V9.61851H8.55813Z" />
                        </svg>
                    </a>
                    <a href="{{ $socialLinks['whatsapp'] ?? '#' }}"
                        class="{{ $theme['social_icon'] }} opacity-70 hover:opacity-100 hover:scale-110 transition-all duration-300">
                        <svg class="h-4" viewBox="0 0 19 17" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M18.028 13.3165C17.8441 13.7456 17.0667 14.0608 15.6508 14.2806C15.578 14.3789 15.5176 14.7989 15.4212 15.126C15.3638 15.3224 15.2228 15.439 14.9932 15.439L14.9827 15.4388C14.6512 15.4388 14.305 15.2863 13.6116 15.2863C12.6757 15.2863 12.3531 15.4995 11.6264 16.0128C10.8559 16.5576 10.117 17.0279 9.01396 16.9797C7.89716 17.0621 6.96624 16.383 6.44232 16.0126C5.71132 15.4959 5.38968 15.2863 4.45756 15.2863C3.79184 15.2863 3.37288 15.4529 3.08646 15.4529C2.80156 15.4529 2.69075 15.2792 2.64809 15.1338C2.5527 14.8096 2.49267 14.3835 2.41789 14.2823C1.68812 14.169 0.0424999 13.8819 0.000575008 13.1477C-0.00994727 12.9566 0.12506 12.7883 0.313847 12.7571C2.76945 12.3528 3.87548 9.83146 3.92146 9.72443C3.92408 9.71821 3.92694 9.71229 3.92983 9.70625C4.06086 9.44027 4.09019 9.21752 4.01675 9.04445C3.83853 8.62464 3.06843 8.47402 2.74443 8.34584C1.90752 8.01531 1.79106 7.63552 1.84057 7.37522C1.92658 6.92223 2.60726 6.64347 3.00522 6.82988C3.31998 6.97742 3.59962 7.0521 3.8362 7.0521C4.01343 7.0521 4.12601 7.00961 4.1877 6.97549C4.1156 5.70729 3.9371 3.89499 4.38839 2.8829C5.58025 0.210789 8.10644 0.00314081 8.8518 0.00314081C8.88512 0.00314081 9.17439 0 9.20859 0C11.0489 0 12.8172 0.945075 13.6807 2.88121C14.1316 3.89228 13.9541 5.6971 13.8817 6.97521C13.9375 7.00598 14.0354 7.04374 14.1851 7.05069C14.4109 7.0406 14.6726 6.96638 14.9639 6.82988C15.1787 6.72944 15.4723 6.74303 15.6867 6.83193L15.6877 6.83228C16.0221 6.95174 16.2325 7.19277 16.2378 7.46292C16.2443 7.80689 15.9371 8.10397 15.3246 8.34581C15.2499 8.37528 15.1589 8.40422 15.0623 8.43492C14.7165 8.54453 14.1941 8.71036 14.0524 9.04442C13.979 9.21748 14.0081 9.44006 14.1392 9.70604C14.1423 9.71197 14.1451 9.71811 14.1476 9.72422C14.1935 9.83115 15.2986 12.3519 17.7554 12.7569C17.9822 12.7942 18.1493 13.0348 18.028 13.3165Z" />
                        </svg>
                    </a>
                    <a href="{{ $socialLinks['linkedin'] ?? '#' }}"
                        class="{{ $theme['social_icon'] }} opacity-70 hover:opacity-100 hover:scale-110 transition-all duration-300">
                        <svg class="h-4" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M3.89154 17.3854H0.287169V5.77806H3.89154V17.3854ZM2.08742 4.19471C0.934855 4.19471 0 3.24005 0 2.08746C0 0.934588 0.934568 0 2.08742 0C3.24026 0 4.17483 0.934588 4.17483 2.08746C4.17483 3.24005 3.23959 4.19471 2.08742 4.19471ZM17.3816 17.3854H13.7849V11.735C13.7849 10.3884 13.7578 8.66147 11.911 8.66147C10.037 8.66147 9.74981 10.1245 9.74981 11.638V17.3854H6.14932V5.77806H9.60623V7.36141H9.65668C10.1379 6.44944 11.3133 5.48701 13.067 5.48701C16.7148 5.48701 17.3854 7.8892 17.3854 11.0093V17.3854H17.3816Z" />
                        </svg>
                    </a>
                    <a href="{{ $socialLinks['twitter'] ?? '#' }}"
                        class="{{ $theme['social_icon'] }} opacity-70 hover:opacity-100 hover:scale-110 transition-all duration-300">
                        <svg class="h-4" viewBox="0 0 19 17" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M3.36783 1.6331H5.08224L15.389 15.2637H13.7898L3.36783 1.6331ZM14.8029 0L10.0561 5.42784L5.94622 0H0L7.10497 9.29035L0.372698 16.9883H3.25602L8.45346 11.0488L12.997 16.9883H18.7975L11.3876 7.19647L17.6828 0H14.8029Z" />
                        </svg>
                    </a>
                    <a href="{{ $socialLinks['instagram'] ?? '#' }}"
                        class="{{ $theme['social_icon'] }} opacity-70 hover:opacity-100 hover:scale-110 transition-all duration-300">
                        <svg class="h-4" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M8.6962 4.23516C6.22903 4.23516 4.239 6.22521 4.239 8.69242C4.239 11.1596 6.22903 13.1497 8.6962 13.1497C11.1634 13.1497 13.1534 11.1596 13.1534 8.69242C13.1534 6.22522 11.1634 4.23516 8.6962 4.23516ZM8.6962 11.5902C7.10185 11.5902 5.79844 10.2907 5.79844 8.69242C5.79844 7.09417 7.09797 5.79462 8.6962 5.79462C10.2944 5.79462 11.594 7.09417 11.594 8.69242C11.594 10.2907 10.2906 11.5902 8.6962 11.5902ZM14.3754 4.05284C14.3754 4.63085 13.9098 5.09248 13.3357 5.09248C12.7577 5.09248 12.2961 4.62697 12.2961 4.05284C12.2961 3.47871 12.7616 3.0132 13.3357 3.0132C13.9098 3.0132 14.3754 3.47871 14.3754 4.05284ZM17.3274 5.10799C17.2615 3.71534 16.9434 2.48175 15.9232 1.46538C14.9068 0.449022 13.6732 0.130925 12.2806 0.0610982C10.8453 -0.0203661 6.54324 -0.0203661 5.10794 0.0610982C3.71918 0.127045 2.4856 0.445143 1.46537 1.46151C0.445138 2.47787 0.130922 3.71147 0.0610974 5.10411C-0.0203658 6.53943 -0.0203658 10.8415 0.0610974 12.2768C0.127043 13.6695 0.445138 14.9031 1.46537 15.9194C2.4856 16.9358 3.7153 17.2539 5.10794 17.3237C6.54324 17.4052 10.8453 17.4052 12.2806 17.3237C13.6732 17.2578 14.9068 16.9397 15.9232 15.9194C16.9395 14.9031 17.2576 13.6695 17.3274 12.2768C17.4089 10.8415 17.4089 6.54331 17.3274 5.10799ZM15.4732 13.8169C15.1706 14.5772 14.5848 15.163 13.8206 15.4695C12.6763 15.9233 9.96082 15.8186 8.6962 15.8186C7.43158 15.8186 4.71226 15.9194 3.57177 15.4695C2.81145 15.1669 2.22569 14.5811 1.91923 13.8169C1.46537 12.6725 1.57011 9.95705 1.57011 8.69242C1.57011 7.42778 1.46925 4.70843 1.91923 3.56793C2.22181 2.8076 2.80757 2.22184 3.57177 1.91538C4.71614 1.46151 7.43158 1.56624 8.6962 1.56624C9.96082 1.56624 12.6801 1.46538 13.8206 1.91538C14.581 2.21796 15.1667 2.80372 15.4732 3.56793C15.927 4.71231 15.8223 7.42778 15.8223 8.69242C15.8223 9.95705 15.927 12.6764 15.4732 13.8169Z" />
                        </svg>
                    </a>
                </div>
            @endif

            <!-- Right Side: Social Icons + Login + Language -->
            <div class="flex items-center gap-4 md:gap-6">
                @if ($shouldRenderLoginButton)
                    <a href="{{ $resolvedLoginUrl }}"
                        class="flex items-center gap-2 transition-all duration-300 text-sm md:text-base {{ $theme['promo_hover'] }}">
                        <svg class="h-4" viewBox="0 0 21 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g opacity="0.6">
                                <path
                                    d="M11.9849 15.3462C8.11731 15.3462 4.81445 15.931 4.81445 18.2729C4.81445 20.6148 8.09636 21.2205 11.9849 21.2205C15.8525 21.2205 19.1545 20.6348 19.1545 18.2938C19.1545 15.9529 15.8735 15.3462 11.9849 15.3462Z"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M11.9849 12.0059C14.523 12.0059 16.5801 9.94781 16.5801 7.40971C16.5801 4.87162 14.523 2.81448 11.9849 2.81448C9.44679 2.81448 7.3887 4.87162 7.3887 7.40971C7.38013 9.93924 9.42394 11.9973 11.9525 12.0059H11.9849Z"
                                    stroke="currentColor" stroke-width="1.42857" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </g>
                        </svg>
                        <span>{{ $resolvedLoginLabel }}</span>
                    </a>
                @endif

                @if ($showLanguageSwitcher)
                    <x-lang.language-switcher variant="topbar" />
                @endif
            </div>
        </div>
    </header>
@endif
<!-- Navigation -->
<nav
    class="border-b px-4 sm:px-6 lg:px-12 py-4 {{ $theme['nav_shell'] }} {{ $headerIsSticky ? 'sticky top-0 z-50' : 'relative z-30' }}">
    <div class="container mx-auto flex justify-between items-center gap-4">
        <!-- Logo -->
        <div class="flex items-center gap-2 ltr:flex-row rtl:flex-row-reverse">
            <img src="{{ $logoSrc }}" alt="{{ $settings?->resolved_site_title ?? 'Palgoals' }}" class="h-14 w-auto">
        </div>
        <!-- Desktop Navigation -->
        <div class="hidden lg:flex justify-between items-center gap-8 lg:gap-10 font-bold {{ $theme['nav_text'] }}">
            @forelse ($header?->items ?? [] as $item)
                @if ($item->type === 'link' || $item->type === 'page')
                    <a href="{{ $item->url }}" class="transition-colors {{ $theme['nav_hover'] }}">
                        {{ $item->label }}
                    </a>
                @elseif ($item->type === 'dropdown')
                    <div class="relative group">
                        <div class="flex items-center gap-1 cursor-pointer transition-colors {{ $theme['nav_hover'] }}">
                            <span>{{ $item->label }}</span>
                            <svg class="w-4 h-4 mt-0.5 transform transition-transform group-hover:rotate-180"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <div
                            class="absolute top-full end-0 mt-2 w-56 border rounded-lg shadow-md z-50 text-sm font-normal opacity-0 invisible scale-95 group-hover:opacity-100 group-hover:visible group-hover:scale-100 transition-all duration-200 {{ $theme['dropdown_shell'] }}">
                            @foreach ($item->processedChildren as $child)
                                <a href="{{ $child['current_url'] ?? '#' }}"
                                    class="block px-4 py-2 transition-colors {{ $theme['dropdown_item'] }}">
                                    {{ $child['current_label'] ?? '-' }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @empty
                <span class="text-sm text-gray-500 italic">No menu items found</span>
            @endforelse
        </div>

        <a href="{{ $contactButtonUrl }}"
            class="hidden lg:block border px-4 py-2 rounded-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-lg {{ $theme['contact_btn'] }}">
            {{ $contactButtonLabel }}
        </a>
        <!-- Mobile Menu Toggle -->
        <button id="mobile-menu-toggle" class="lg:hidden flex flex-col gap-1.5 p-2 z-[60] relative">
            <span class="w-6 h-0.5 transition-all duration-300 origin-center {{ $theme['hamburger_bar'] }}"></span>
            <span class="w-6 h-0.5 transition-all duration-300 {{ $theme['hamburger_bar'] }}"></span>
            <span class="w-6 h-0.5 transition-all duration-300 origin-center {{ $theme['hamburger_bar'] }}"></span>
        </button>
        <!-- Mobile Menu Overlay -->
        <div id="mobile-menu"
            class="fixed inset-0 bg-black/60 backdrop-blur-md z-[55] invisible opacity-0 transition-all duration-500 lg:hidden">
            <div id="mobile-menu-container"
                class="absolute top-0 ltr:right-0 rtl:left-0 w-[80%] max-w-sm h-full shadow-2xl ltr:translate-x-full rtl:-translate-x-full transition-transform duration-500 ease-out p-8 flex flex-col pt-24 {{ $theme['mobile_panel'] }}">

                <div class="flex flex-col gap-6 font-bold text-xl {{ $theme['nav_text'] }}">
                    @forelse ($header?->items ?? [] as $item)
                        @if ($item->type === 'link' || $item->type === 'page')
                            <a href="{{ $item->url }}"
                                class="transition-colors w-full border-b pb-4 {{ $theme['nav_hover'] }} {{ $theme['mobile_link_border'] }}">
                                {{ $item->label }}
                            </a>
                        @elseif ($item->type === 'dropdown')
                            <div class="w-full border-b pb-4 {{ $theme['mobile_link_border'] }}">
                                <div class="{{ $theme['nav_text'] }}">{{ $item->label }}</div>
                                @if (!empty($item->processedChildren))
                                    <div class="mt-3 flex flex-col gap-3 pe-4 text-base font-semibold {{ $theme['mobile_subtext'] }}">
                                        @foreach ($item->processedChildren as $child)
                                            <a href="{{ $child['current_url'] ?? '#' }}"
                                                class="transition-colors {{ $theme['nav_hover'] }}">
                                                {{ $child['current_label'] ?? '-' }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    @empty
                        <div class="text-sm text-gray-500 italic">No menu items found</div>
                    @endforelse
                </div>

                <div class="mt-auto space-y-6">
                    <a href="{{ $contactButtonUrl }}"
                        class="block w-full text-center border-2 px-6 py-4 rounded-2xl font-bold text-xl transition-all shadow-lg {{ $theme['contact_btn'] }}">
                        {{ $contactButtonLabel }}
                    </a>
                    <div class="flex justify-center items-center gap-6">
                        <img src="{{ $logoSrc }}" alt="{{ $settings?->resolved_site_title ?? 'Palgoals' }}"
                            class="h-12 w-auto">
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
