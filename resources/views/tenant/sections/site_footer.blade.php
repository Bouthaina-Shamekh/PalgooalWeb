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
    $description = trim((string) data_get($content, 'description'));
    $contactEmail = trim((string) data_get($content, 'contact_email', $resolvedSubscription?->client?->email ?? ''));
    $contactPhone = trim((string) data_get($content, 'contact_phone', $resolvedSubscription?->client?->phone ?? ''));
    $copyright = trim((string) data_get($content, 'copyright'));

    $navigationLinks = $navigationPages
        ->map(function ($navPage) {
            $navTranslation = method_exists($navPage, 'translation') ? $navPage->translation() : null;
            $label = trim((string) ($navTranslation?->title ?? ''));
            $slug = trim((string) ($navTranslation?->slug ?? ''), '/');

            return [
                'label' => $label !== '' ? $label : ($navPage->is_home ? __('Home') : __('Page')),
                'href' => $navPage->is_home ? '/' : ($slug !== '' ? '/' . $slug : '/'),
            ];
        })
        ->values();
@endphp

<footer class="bg-slate-950 text-slate-200">
    <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(16rem,0.85fr)] lg:px-8">
        <div class="space-y-4 text-start">
            <h2 class="text-2xl font-semibold tracking-tight text-white">{{ $brandName !== '' ? $brandName : __('My Website') }}</h2>

            @if ($description !== '')
                <p class="max-w-2xl text-sm leading-7 text-slate-300">
                    {{ $description }}
                </p>
            @endif

            @if ($copyright !== '')
                <p class="text-xs text-slate-500">{{ $copyright }}</p>
            @endif
        </div>

        <div class="grid gap-8 sm:grid-cols-2">
            <div class="space-y-3 text-start">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{{ __('Pages') }}</p>
                <div class="space-y-2">
                    @foreach ($navigationLinks as $link)
                        <a href="{{ $link['href'] }}" class="block text-sm text-slate-300 transition hover:text-white">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="space-y-3 text-start">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{{ __('Contact') }}</p>
                <div class="space-y-2 text-sm text-slate-300">
                    @if ($contactEmail !== '')
                        <a href="mailto:{{ $contactEmail }}" class="block transition hover:text-white">
                            {{ $contactEmail }}
                        </a>
                    @endif

                    @if ($contactPhone !== '')
                        <a href="tel:{{ preg_replace('/\s+/', '', $contactPhone) }}" class="block transition hover:text-white">
                            {{ $contactPhone }}
                        </a>
                    @endif

                    @if ($contactEmail === '' && $contactPhone === '')
                        <p class="text-slate-500">{{ __('Add your contact email or phone from the footer editor.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</footer>
