@php
    $topbarStatus = $topbarStatus ?? [
        'label' => 'Draft',
        'tone' => 'border-amber-200 bg-amber-50 text-amber-700',
    ];
@endphp

<header class="border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <div class="min-w-0 text-start">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="truncate text-xl font-semibold text-slate-950">{{ $siteName }}</h1>
                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $topbarStatus['tone'] }}">
                    {{ __($topbarStatus['label']) }}
                </span>
            </div>
            <p class="mt-1 truncate text-sm text-slate-500">
                {{ $siteUrl ? preg_replace('#^https?://#', '', rtrim($siteUrl, '/')) : __('Your site dashboard') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if ($previewUrl)
                <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    {{ __('Preview') }}
                </a>
            @endif

            @if ($editUrl)
                <a href="{{ $editUrl }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-[#240B36] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#34104d]">
                    {{ __('Edit site') }}
                </a>
            @endif
        </div>
    </div>
</header>
