@php
    $showPaymentMethods = (bool) ($settings?->footer_show_payment_methods ?? true);
    $footerVariantSettings = is_array($settings?->footer_variant_settings ?? null)
        ? $settings->footer_variant_settings
        : [];
    $variantSettings = is_array($footerVariantSettings['palgoals_marketing'] ?? null)
        ? $footerVariantSettings['palgoals_marketing']
        : [];
    $socialLinks = is_array($settings?->social_links ?? null) ? $settings->social_links : [];
    $contactInfo = is_array($settings?->resolved_contact_info ?? null) ? $settings->resolved_contact_info : [];

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

    $descriptionText = $resolveLocalizedText(
        $variantSettings['description_text'] ?? null,
        'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.',
    );
    $pagesTitle = $resolveLocalizedText($variantSettings['pages_title'] ?? null, 'PAGES');
    $paymentTitle = $resolveLocalizedText($variantSettings['payment_title'] ?? null, 'PAYMENT');
    $helpTitle = $resolveLocalizedText($variantSettings['help_title'] ?? null, 'NEED HELP?');
    $followUsLabel = $resolveLocalizedText($variantSettings['follow_us_label'] ?? null, 'FOLLOW US:');
    $copyrightText = $resolveLocalizedText(
        $variantSettings['copyright_text'] ?? null,
        'All rights reserved to PalGoals company © 2025',
    );
    $showSocialIcons = (bool) ($variantSettings['show_social_icons'] ?? true);
    $followLabelAlignment = current_dir() === 'rtl' ? 'text-right' : 'text-left';

    $colorThemeKey = strtolower(trim((string) ($variantSettings['color_theme'] ?? '')));

    $defaultThemeClasses = [
        'shell' => 'bg-gray-100',
        'muted_text' => 'text-gray-500',
        'heading_text' => 'text-black',
        'hover_text' => 'hover:text-purple-brand',
        'payment_card' => 'bg-white',
        'top_border' => 'border-gray-300',
        'follow_label' => 'text-gray-500',
    ];

    $configuredThemes = config('front_layouts.color_libraries.palgoals_marketing.themes', []);
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

    $defaultThemeKey = strtolower(trim((string) config('front_layouts.color_libraries.palgoals_marketing.default', 'classic')));
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

    $paymentLogoPaths = $variantSettings['payment_logos'] ?? [];
    if (is_string($paymentLogoPaths)) {
        $paymentLogoPaths = array_values(array_filter(array_map('trim', explode(',', $paymentLogoPaths))));
    } elseif (!is_array($paymentLogoPaths)) {
        $paymentLogoPaths = [];
    }

    $paymentLogoUrls = collect($paymentLogoPaths)
        ->map(function ($path) {
            $normalizedPath = trim((string) $path);
            if ($normalizedPath === '') {
                return null;
            }

            return \Illuminate\Support\Str::startsWith($normalizedPath, ['http://', 'https://', '//'])
                ? $normalizedPath
                : asset('storage/' . ltrim($normalizedPath, '/'));
        })
        ->filter()
        ->values();

    $contactItems = collect([
        [
            'type' => 'phone',
            'value' => trim((string) ($contactInfo['phone'] ?? '')),
        ],
        [
            'type' => 'email',
            'value' => trim((string) ($contactInfo['email'] ?? '')),
        ],
        [
            'type' => 'address',
            'value' => trim((string) ($contactInfo['address'] ?? '')),
        ],
    ])->filter(fn (array $item) => $item['value'] !== '')->values();

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

<footer class="pt-16 pb-4 md:pb-8 px-4 sm:px-6 lg:px-12 text-start {{ $theme['shell'] }}">
    <div class="container mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-24 mb-4 md:mb-8">
            <div>
                <div class="flex items-center justify-start gap-4 mb-6">
                    <div class="pf-marketing-logo-frame shrink-0">
                        <img src="{{ $logoSrc }}" alt="{{ $settings?->resolved_site_title ?? 'PalGoals' }}" class="pf-marketing-logo-image">
                    </div>
                </div>
                <p class="text-base leading-relaxed max-w-sm {{ $theme['muted_text'] }}">
                    {{ $descriptionText }}
                </p>
            </div>

            <div>
                <h3 class="font-bold text-2xl md:text-3xl mb-2 uppercase tracking-wide {{ $theme['heading_text'] }}">
                    {{ $pagesTitle }}
                </h3>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                    @if ($footerMenuColumns->isNotEmpty())
                        @foreach ([0, 1] as $columnIndex)
                            <ul class="space-y-3">
                                @foreach ($footerMenuColumns->get($columnIndex, collect()) as $menuLink)
                                    <li>
                                        <a
                                            href="{{ $menuLink['url'] ?: '#' }}"
                                            class="transition text-base {{ $theme['muted_text'] }} {{ $theme['hover_text'] }}"
                                        >
                                            {{ $menuLink['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    @else
                        <ul class="space-y-3">
                            <li>
                                <a href="{{ route('frontend.home') }}" class="transition text-base {{ $theme['muted_text'] }} {{ $theme['hover_text'] }}">
                                    {{ t('frontend.Home', 'Home') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('domains.page') }}" class="transition text-base {{ $theme['muted_text'] }} {{ $theme['hover_text'] }}">
                                    {{ t('frontend.Domain', 'Domains') }}
                                </a>
                            </li>
                        </ul>
                        <ul class="space-y-3">
                            <li>
                                <a href="{{ route('cart') }}" class="transition text-base {{ $theme['muted_text'] }} {{ $theme['hover_text'] }}">
                                    {{ t('frontend.Cart', 'Cart') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('testimonials.submit') }}" class="transition text-base {{ $theme['muted_text'] }} {{ $theme['hover_text'] }}">
                                    {{ t('frontend.Contact_Us', 'Contact Us') }}
                                </a>
                            </li>
                        </ul>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-1 gap-x-4 gap-y-3">
                <div>
                    <h3 class="font-bold text-2xl md:text-3xl mb-2 uppercase tracking-wide {{ $theme['heading_text'] }}">
                        {{ $paymentTitle }}
                    </h3>
                    @if ($showPaymentMethods)
                        <div class="flex flex-wrap items-center gap-4 mb-8">
                            @forelse ($paymentLogoUrls as $paymentLogoUrl)
                                <div class="pf-marketing-payment-card-size p-2 rounded-lg shadow-sm flex items-center justify-center {{ $theme['payment_card'] }}">
                                    <img src="{{ $paymentLogoUrl }}" class="w-full h-full object-contain" alt="Payment Logo">
                                </div>
                            @empty
                                <div class="pf-marketing-payment-card-size p-2 rounded-lg shadow-sm flex items-center justify-center {{ $theme['payment_card'] }}">
                                    <img src="{{ asset('assets/tamplate/images/mastercard.svg') }}" class="w-full h-full object-contain" alt="Mastercard">
                                </div>
                                <div class="pf-marketing-payment-card-size p-2 rounded-lg shadow-sm flex items-center justify-center {{ $theme['payment_card'] }}">
                                    <img src="{{ asset('assets/tamplate/images/visa.svg') }}" class="w-full h-full object-contain" alt="Visa">
                                </div>
                            @endforelse
                        </div>
                    @endif
                </div>
                <div>
                    <p class="text-lg font-bold mb-1 {{ $theme['heading_text'] }}">{{ $helpTitle }}</p>
                    @if ($contactItems->isNotEmpty())
                        <div class="space-y-2 {{ $theme['muted_text'] }}">
                            @foreach ($contactItems as $contactItem)
                                @if ($contactItem['type'] === 'phone')
                                    <a href="tel:{{ $contactItem['value'] }}" class="block text-base font-medium transition {{ $theme['hover_text'] }}">
                                        {{ $contactItem['value'] }}
                                    </a>
                                @elseif ($contactItem['type'] === 'email')
                                    <a href="mailto:{{ $contactItem['value'] }}" class="block text-base font-medium transition {{ $theme['hover_text'] }}">
                                        {{ $contactItem['value'] }}
                                    </a>
                                @else
                                    <p class="text-base font-medium mb-0">
                                        {{ $contactItem['value'] }}
                                    </p>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if ($showSocialIcons)
                <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-3 md:hidden">
                    <span class="text-base uppercase {{ $followLabelAlignment }} {{ $theme['follow_label'] }}">{{ $followUsLabel }}</span>
                    <div class="flex items-center gap-5">
                        <a href="{{ $socialLinks['facebook'] ?? '#' }}" class="{{ $theme['muted_text'] }} {{ $theme['hover_text'] }} hover:-translate-y-1 transition-all duration-300">
                            <img src="{{ asset('assets/imgs/icons/icon-awesome-facebook-f.svg') }}" class="w-5 h-5 opacity-60 hover:opacity-100" alt="Facebook">
                        </a>
                        <a href="{{ $socialLinks['whatsapp'] ?? '#' }}" class="{{ $theme['muted_text'] }} {{ $theme['hover_text'] }} hover:-translate-y-1 transition-all duration-300">
                            <img src="{{ asset('assets/imgs/icons/icon-awesome-snapchat-ghost.svg') }}" class="w-5 h-5 opacity-60 hover:opacity-100" alt="Snapchat">
                        </a>
                        <a href="{{ $socialLinks['linkedin'] ?? '#' }}" class="{{ $theme['muted_text'] }} {{ $theme['hover_text'] }} hover:-translate-y-1 transition-all duration-300">
                            <img src="{{ asset('assets/imgs/icons/icon-awesome-linkedin-in.svg') }}" class="w-5 h-5 opacity-60 hover:opacity-100" alt="Linkedin">
                        </a>
                        <a href="{{ $socialLinks['twitter'] ?? '#' }}" class="{{ $theme['muted_text'] }} {{ $theme['hover_text'] }} hover:-translate-y-1 transition-all duration-300">
                            <svg class="w-5 h-5 opacity-60 hover:opacity-100 fill-current" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg>
                        </a>
                        <a href="{{ $socialLinks['instagram'] ?? '#' }}" class="{{ $theme['muted_text'] }} {{ $theme['hover_text'] }} hover:-translate-y-1 transition-all duration-300">
                            <img src="{{ asset('assets/imgs/icons/icon-awesome-instagram.svg') }}" class="w-5 h-5 opacity-60 hover:opacity-100" alt="Instagram">
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <div class="border-t pt-4 md:pt-8 flex flex-col-reverse md:flex-row justify-between items-center gap-6 {{ $theme['top_border'] }}">
            <p class="text-base ltr:text-left rtl:text-right {{ $theme['muted_text'] }}">
                {{ $copyrightText }}
            </p>

            @if ($showSocialIcons)
                <div class="hidden md:flex flex-wrap items-center gap-x-4 gap-y-3">
                    <span class="text-base uppercase {{ $followLabelAlignment }} {{ $theme['follow_label'] }}">{{ $followUsLabel }}</span>
                    <div class="flex items-center gap-5">
                        <a href="{{ $socialLinks['facebook'] ?? '#' }}" class="{{ $theme['muted_text'] }} {{ $theme['hover_text'] }} hover:-translate-y-1 transition-all duration-300">
                            <img src="{{ asset('assets/imgs/icons/icon-awesome-facebook-f.svg') }}" class="w-5 h-5 opacity-60 hover:opacity-100" alt="Facebook">
                        </a>
                        <a href="{{ $socialLinks['whatsapp'] ?? '#' }}" class="{{ $theme['muted_text'] }} {{ $theme['hover_text'] }} hover:-translate-y-1 transition-all duration-300">
                            <img src="{{ asset('assets/imgs/icons/icon-awesome-snapchat-ghost.svg') }}" class="w-5 h-5 opacity-60 hover:opacity-100" alt="Snapchat">
                        </a>
                        <a href="{{ $socialLinks['linkedin'] ?? '#' }}" class="{{ $theme['muted_text'] }} {{ $theme['hover_text'] }} hover:-translate-y-1 transition-all duration-300">
                            <img src="{{ asset('assets/imgs/icons/icon-awesome-linkedin-in.svg') }}" class="w-5 h-5 opacity-60 hover:opacity-100" alt="Linkedin">
                        </a>
                        <a href="{{ $socialLinks['twitter'] ?? '#' }}" class="{{ $theme['muted_text'] }} {{ $theme['hover_text'] }} hover:-translate-y-1 transition-all duration-300">
                            <svg class="w-5 h-5 opacity-60 hover:opacity-100 fill-current" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg>
                        </a>
                        <a href="{{ $socialLinks['instagram'] ?? '#' }}" class="{{ $theme['muted_text'] }} {{ $theme['hover_text'] }} hover:-translate-y-1 transition-all duration-300">
                            <img src="{{ asset('assets/imgs/icons/icon-awesome-instagram.svg') }}" class="w-5 h-5 opacity-60 hover:opacity-100" alt="Instagram">
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</footer>
