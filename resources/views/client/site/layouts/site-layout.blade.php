@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'fa', 'he', 'ur'], true);
@endphp

<div class="min-h-screen rounded-[2rem] border border-slate-200 bg-slate-50 shadow-sm">
    <div class="flex min-h-screen flex-col lg:flex-row {{ $isRtl ? 'lg:flex-row-reverse' : '' }}">
        <aside class="hidden lg:flex lg:w-72 lg:shrink-0 lg:flex-col {{ $isRtl ? 'lg:border-l' : 'lg:border-r' }} border-slate-200 bg-white">
            <div class="sticky top-0 flex h-screen flex-col">
                @include('client.site.partials.sidebar', [
                    'subscription' => $subscription,
                    'siteName' => $siteName,
                    'siteUrl' => $siteUrl,
                    'isRtl' => $isRtl,
                ])
            </div>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <div class="border-b border-slate-200 bg-white px-4 py-4 lg:hidden">
                <details class="group rounded-2xl border border-slate-200 bg-slate-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-slate-900 marker:content-none">
                        <span>{{ __('Site navigation') }}</span>
                        <svg class="h-5 w-5 text-slate-500 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </summary>

                    <div class="border-t border-slate-200 px-3 py-3">
                        @include('client.site.partials.sidebar', [
                            'subscription' => $subscription,
                            'siteName' => $siteName,
                            'siteUrl' => $siteUrl,
                            'isRtl' => $isRtl,
                            'mobile' => true,
                        ])
                    </div>
                </details>
            </div>

            @if ($showTopbar ?? true)
                @include('client.site.partials.topbar', [
                    'siteName' => $siteName,
                    'siteUrl' => $siteUrl,
                    'topbarStatus' => $topbarStatus ?? null,
                    'editUrl' => $editUrl ?? null,
                    'previewUrl' => $previewUrl ?? null,
                ])
            @endif

            <main class="flex-1 px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto w-full max-w-6xl">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</div>
