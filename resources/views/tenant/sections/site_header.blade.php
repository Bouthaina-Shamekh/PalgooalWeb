@php
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
            $isActive = $navPage->is_home ? $currentPath === '' : $slug !== '' && $slug === $currentPath;

            return [
                'label' => $label !== '' ? $label : ($navPage->is_home ? __('Home') : __('Page')),
                'href' => $href,
                'active' => $isActive,
            ];
        })
        ->values();
@endphp

<header class="border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <div class="flex items-center justify-between gap-4 rtl:flex-row-reverse">
            <a href="/" class="text-xl font-semibold tracking-tight text-slate-950">
                {{ $brandName !== '' ? $brandName : __('My Website') }}
            </a>

            @if ($navigationLinks->isNotEmpty())
                <nav class="hidden items-center gap-2 lg:flex rtl:flex-row-reverse">
                    @foreach ($navigationLinks as $link)
                        <a href="{{ $link['href'] }}"
                            class="rounded-full px-3 py-2 text-sm font-medium transition {{ $link['active'] ? 'bg-[#240B36] text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3 rtl:flex-row-reverse">
            @if ($navigationLinks->isNotEmpty())
                <nav class="flex flex-1 flex-wrap items-center gap-2 lg:hidden rtl:flex-row-reverse">
                    @foreach ($navigationLinks as $link)
                        <a href="{{ $link['href'] }}"
                            class="rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium transition {{ $link['active'] ? 'border-[#240B36] text-[#240B36]' : 'text-slate-600 hover:border-slate-300 hover:text-slate-950' }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            @endif

            @if ($buttonLabel !== '')
                <a href="{{ $buttonUrl !== '' ? $buttonUrl : '#' }}"
                    @if ($buttonNewTab) target="_blank" rel="noopener" @endif
                    class="inline-flex items-center justify-center rounded-full bg-[#240B36] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#34104d]">
                    {{ $buttonLabel }}
                </a>
            @endif
        </div>
    </div>
</header>
