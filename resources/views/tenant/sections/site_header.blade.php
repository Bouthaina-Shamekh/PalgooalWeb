@php
    use App\Support\Sections\SectionMediaPreviewBuilder;
    use Illuminate\Support\Str;

    $resolvedSubscription = $subscription
        ?? ($tenantSubscription ?? $section->tenant ?? $page?->tenant ?? $section->page?->tenant ?? null);

    $navigationPages = collect($siteNavigationPages ?? []);

    if ($navigationPages->isEmpty() && $resolvedSubscription instanceof \App\Models\Tenancy\Subscription) {
        $navigationPages = $resolvedSubscription->canonicalPages()
            ->with('translations')
            ->where('context', 'tenant')
            ->where('is_active', true)
            ->orderByDesc('is_home')
            ->orderBy('id')
            ->get();
    }

    $brandName = trim((string) (data_get($content, 'title') ?: $translation?->title ?: __('My Website')));
    $displayBrandName = $brandName !== '' ? $brandName : __('My Website');
    $brandInitial = Str::upper(Str::substr($displayBrandName, 0, 1));
    $brandLogoValue = data_get($content, 'logo');
    $brandLogoUrl = app(SectionMediaPreviewBuilder::class)->build($brandLogoValue)[0] ?? null;
    $buttonLabel = trim((string) data_get($content, 'primary_button.label'));
    $buttonUrl = trim((string) data_get($content, 'primary_button.url'));
    $buttonNewTab = (bool) data_get($content, 'primary_button.new_tab', false);
    $currentPath = trim((string) request()->path(), '/');

    $navigationLinks = $navigationPages
        ->map(function ($navPage) use ($currentPath) {
            $navTranslation = method_exists($navPage, 'translation') ? $navPage->translation() : null;
            $label = trim((string) ($navTranslation?->title ?? ''));
            $slug = trim((string) ($navTranslation?->slug ?? ''), '/');
            $href = $navPage->is_home ? '/' : ($slug !== '' ? '/' . $slug : '/');
            $resolvedLabel = $label !== '' ? $label : ($navPage->is_home ? __('Home') : __('Page'));
            $isActive = $navPage->is_home ? $currentPath === '' : $slug !== '' && $slug === $currentPath;

            return [
                'label' => $resolvedLabel,
                'href' => $href,
                'active' => $isActive,
                'badge' => Str::upper(Str::substr($resolvedLabel, 0, 1)),
                'description' => $navPage->is_home
                    ? __('Go to the homepage')
                    : __('Open the :page page', ['page' => $resolvedLabel]),
            ];
        })
        ->values();

    $desktopPrimaryLinks = $navigationLinks->count() > 3
        ? $navigationLinks->take(2)->values()
        : $navigationLinks;

    $desktopDropdownLinks = $navigationLinks->count() > 3
        ? $navigationLinks->slice(2)->values()
        : collect();

    $ctaUrl = $buttonUrl !== '' ? $buttonUrl : '#contact';
@endphp

<header class="border-b border-gray-200/80 bg-white">
    <nav aria-label="Global" class="relative mx-auto flex max-w-7xl items-center justify-between p-6 lg:px-8">
        <div class="flex lg:flex-1">
            <a href="/" class="-m-1.5 flex items-center gap-3 p-1.5 rtl:flex-row-reverse">
                @if (filled($brandLogoUrl))
                    <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <img src="{{ $brandLogoUrl }}" alt="{{ $displayBrandName }}" class="h-full w-full object-contain p-1">
                    </span>
                @else
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#240B36] text-sm font-semibold text-white shadow-sm">
                        {{ $brandInitial !== '' ? $brandInitial : 'W' }}
                    </span>
                @endif
                <div class="text-start">
                    <span class="block text-base font-semibold text-gray-900">{{ $displayBrandName }}</span>
                    <span class="block text-xs text-gray-500">{{ __('Your website') }}</span>
                </div>
            </a>
        </div>

        <details class="relative lg:hidden">
            <summary class="-m-2.5 flex list-none items-center justify-center rounded-md p-2.5 text-gray-700 [&::-webkit-details-marker]:hidden">
                <span class="sr-only">{{ __('Open main menu') }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6">
                    <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </summary>

            <div class="absolute inset-x-0 top-full z-30 mt-4 overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-gray-900/5">
                <div class="space-y-6 p-6">
                    <div class="space-y-2">
                        @foreach ($navigationLinks as $link)
                            <a
                                href="{{ $link['href'] }}"
                                class="block rounded-2xl px-4 py-3 text-base font-semibold transition {{ $link['active'] ? 'bg-[#240B36] text-white' : 'text-gray-900 hover:bg-gray-50' }}"
                            >
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>

                    @if ($buttonLabel !== '')
                        <div class="border-t border-gray-200 pt-4">
                            <a
                                href="{{ $ctaUrl }}"
                                @if ($buttonNewTab) target="_blank" rel="noopener" @endif
                                class="inline-flex w-full items-center justify-center rounded-full bg-[#240B36] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#34104d]"
                            >
                                {{ $buttonLabel }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </details>

        <div class="hidden lg:flex lg:items-center lg:gap-x-12">
            @foreach ($desktopPrimaryLinks as $link)
                <a
                    href="{{ $link['href'] }}"
                    class="text-sm/6 font-semibold transition {{ $link['active'] ? 'text-[#240B36]' : 'text-gray-900 hover:text-[#240B36]' }}"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach

            @if ($desktopDropdownLinks->isNotEmpty())
                <details class="group relative">
                    <summary class="flex list-none items-center gap-x-1 text-sm/6 font-semibold text-gray-900 [&::-webkit-details-marker]:hidden">
                        {{ __('Pages') }}
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-5 flex-none text-gray-400 transition group-open:rotate-180">
                            <path d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
                        </svg>
                    </summary>

                    <div class="absolute top-full z-30 mt-3 w-screen max-w-md overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-gray-900/5 ltr:left-1/2 ltr:-translate-x-1/2 rtl:right-1/2 rtl:translate-x-1/2">
                        <div class="p-4">
                            @foreach ($desktopDropdownLinks as $link)
                                <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-sm/6 transition hover:bg-gray-50 rtl:flex-row-reverse">
                                    <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 text-sm font-semibold text-gray-600 group-hover:bg-white group-hover:text-[#240B36]">
                                        {{ $link['badge'] }}
                                    </div>
                                    <div class="min-w-0 flex-auto text-start">
                                        <a href="{{ $link['href'] }}" class="block font-semibold text-gray-900">
                                            {{ $link['label'] }}
                                            <span class="absolute inset-0"></span>
                                        </a>
                                        <p class="mt-1 text-gray-600">{{ $link['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($buttonLabel !== '')
                            <div class="border-t border-gray-200 bg-gray-50 p-3">
                                <a
                                    href="{{ $ctaUrl }}"
                                    @if ($buttonNewTab) target="_blank" rel="noopener" @endif
                                    class="flex items-center justify-center gap-x-2.5 rounded-2xl px-3 py-3 text-sm/6 font-semibold text-gray-900 transition hover:bg-gray-100"
                                >
                                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-5 flex-none text-gray-400">
                                        <path d="M10.75 3.5a.75.75 0 0 0-1.5 0v5.69H3.5a.75.75 0 0 0 0 1.5h5.75v5.81a.75.75 0 0 0 1.5 0v-5.81h5.75a.75.75 0 0 0 0-1.5h-5.75V3.5Z" />
                                    </svg>
                                    {{ $buttonLabel }}
                                </a>
                            </div>
                        @endif
                    </div>
                </details>
            @endif
        </div>

        <div class="hidden lg:flex lg:flex-1 lg:justify-end">
            @if ($buttonLabel !== '')
                <a
                    href="{{ $ctaUrl }}"
                    @if ($buttonNewTab) target="_blank" rel="noopener" @endif
                    class="inline-flex items-center gap-2 rounded-full bg-[#240B36] px-4 py-2.5 text-sm/6 font-semibold text-white transition hover:bg-[#34104d]"
                >
                    {{ $buttonLabel }}
                    <span aria-hidden="true">&rarr;</span>
                </a>
            @endif
        </div>
    </nav>
</header>
