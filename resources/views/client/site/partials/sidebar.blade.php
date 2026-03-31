@php
    $mobile = $mobile ?? false;
    $siteDashboardUrl = route('client.subscriptions.site', $subscription);
    $pagesUrl = route('client.subscriptions.pages', $subscription);
    $editorUrl = route('client.subscriptions.homepage-editor.index', $subscription);
    $headerEditorUrl = route('client.subscriptions.site-header-editor.index', $subscription);
    $footerEditorUrl = route('client.subscriptions.site-footer-editor.index', $subscription);
    $domainUrl = route('client.domains.index');
    $settingsUrl = route('client.subscriptions.show', $subscription);
    $accountUrl = route('client.subscriptions');
    $viewWebsiteUrl = $siteUrl ?: $siteDashboardUrl;

    $navSections = [
        'Main' => [
            [
                'label' => 'Dashboard',
                'href' => $siteDashboardUrl,
                'active' => request()->routeIs('client.subscriptions.site'),
                'icon' => 'dashboard',
            ],
            [
                'label' => 'Pages',
                'href' => $pagesUrl,
                'active' => request()->routeIs('client.subscriptions.pages*'),
                'icon' => 'pages',
            ],
            [
                'label' => 'Editor',
                'href' => $editorUrl,
                'active' => request()->routeIs('client.subscriptions.homepage-editor.*'),
                'icon' => 'editor',
            ],
            [
                'label' => 'Header',
                'href' => $headerEditorUrl,
                'active' => request()->routeIs('client.subscriptions.site-header-editor.*'),
                'icon' => 'header',
            ],
            [
                'label' => 'Footer',
                'href' => $footerEditorUrl,
                'active' => request()->routeIs('client.subscriptions.site-footer-editor.*'),
                'icon' => 'footer',
            ],
        ],
        'Growth' => [
            [
                'label' => 'Domain',
                'href' => $domainUrl,
                'active' => request()->routeIs('client.domains.*'),
                'icon' => 'domain',
            ],
            [
                'label' => 'Settings',
                'href' => $settingsUrl,
                'active' => request()->routeIs('client.subscriptions.show', 'client.subscriptions.content'),
                'icon' => 'settings',
            ],
        ],
    ];

    $linkBaseClass = 'group flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition';
@endphp

<div class="flex h-full flex-col {{ $mobile ? 'gap-4' : 'gap-6 p-5' }}">
    <div class="space-y-2 {{ $mobile ? 'px-1' : '' }}">
        <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#240B36] text-white shadow-sm">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5h15M6.75 16.5V6.375A1.875 1.875 0 0 1 8.625 4.5h6.75A1.875 1.875 0 0 1 17.25 6.375V16.5M9.75 9.75h4.5M9.75 13.5h4.5" />
                </svg>
            </span>
            <div class="min-w-0 text-start">
                <p class="truncate text-sm font-semibold text-slate-950">{{ $siteName }}</p>
                <p class="truncate text-xs text-slate-500">{{ __('Site dashboard') }}</p>
            </div>
        </div>
    </div>

    <div class="flex-1 space-y-6">
        @foreach ($navSections as $sectionTitle => $items)
            <div class="space-y-2">
                <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __($sectionTitle) }}</p>
                <nav class="space-y-1.5">
                    @foreach ($items as $item)
                        @php
                            $itemClasses = $item['active']
                                ? 'border border-[#240B36]/15 bg-[#240B36] text-white shadow-sm'
                                : 'border border-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-950';
                        @endphp

                        <a href="{{ $item['href'] }}" class="{{ $linkBaseClass }} {{ $itemClasses }}">
                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl {{ $item['active'] ? 'bg-white/15 text-white' : 'bg-white text-slate-600 shadow-sm ring-1 ring-slate-200' }}">
                                @if ($item['icon'] === 'dashboard')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h7.5v5.25h-7.5V5.25Zm9 0h7.5v8.25h-7.5V5.25Zm-9 6.75h7.5v6.75h-7.5V12Zm9 3h7.5v3.75h-7.5V15Z" />
                                    </svg>
                                @elseif ($item['icon'] === 'pages')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 6.75h10.125a1.875 1.875 0 0 1 1.875 1.875v8.625a1.875 1.875 0 0 1-1.875 1.875H7.5m0-12.375H6A1.5 1.5 0 0 0 4.5 8.25v7.5A1.5 1.5 0 0 0 6 17.25h1.5m0-10.5v10.5" />
                                    </svg>
                                @elseif ($item['icon'] === 'editor')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L9.832 16.82a4.5 4.5 0 0 1-1.897 1.13l-3.094.885.885-3.094a4.5 4.5 0 0 1 1.13-1.897L16.862 4.487ZM18 14.25v4.125A1.125 1.125 0 0 1 16.875 19.5H5.625A1.125 1.125 0 0 1 4.5 18.375V7.125A1.125 1.125 0 0 1 5.625 6H9.75" />
                                    </svg>
                                @elseif ($item['icon'] === 'header')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15M4.5 10.5h15M6.75 6.75v10.5m10.5-10.5v10.5M4.5 17.25h15" />
                                    </svg>
                                @elseif ($item['icon'] === 'footer')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15m-15 10.5h15m-12-6h9m-9 3h6" />
                                    </svg>
                                @elseif ($item['icon'] === 'domain')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18m0 18c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m-8.25 9h16.5" />
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h3m-7.5 6.75h12m-10.5 5.25h9a1.5 1.5 0 0 0 1.5-1.5V7.5A1.5 1.5 0 0 0 16.5 6h-9A1.5 1.5 0 0 0 6 7.5v9A1.5 1.5 0 0 0 7.5 18Z" />
                                    </svg>
                                @endif
                            </span>

                            <span class="text-start">
                                <span class="block">{{ __($item['label']) }}</span>
                            </span>
                        </a>
                    @endforeach
                </nav>
            </div>
        @endforeach
    </div>

    <div class="space-y-2 border-t border-slate-200 pt-4">
        <a href="{{ $viewWebsiteUrl }}" @if ($siteUrl) target="_blank" rel="noopener" @endif
            class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-white hover:text-slate-950">
            <span class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18m0 0v4.5M18 6l-7.5 7.5M6 10.5v7.875A1.125 1.125 0 0 0 7.125 19.5H15a1.125 1.125 0 0 0 1.125-1.125V10.5" />
                    </svg>
                </span>
                <span class="text-start">{{ __('View website') }}</span>
            </span>
            <svg class="h-4 w-4 text-slate-400 {{ $isRtl ? 'rotate-180' : '' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.22 4.47a.75.75 0 0 1 1.06 0l4.75 4.75a.75.75 0 0 1 0 1.06l-4.75 4.75a.75.75 0 1 1-1.06-1.06L11.44 10 7.22 5.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </a>

        <a href="{{ $accountUrl }}"
            class="group flex items-center justify-between gap-3 rounded-2xl border border-transparent px-3 py-3 text-sm font-medium text-slate-500 transition hover:bg-slate-50 hover:text-slate-900">
            <span class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 19.5-7.5-7.5L15 4.5" />
                    </svg>
                </span>
                <span class="text-start">{{ __('Back to account') }}</span>
            </span>
        </a>
    </div>
</div>
