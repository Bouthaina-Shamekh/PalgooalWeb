{{-- resources/views/dashboard/pages/builder.blade.php --}}
@php
    $translation = $page->translation();
    $pageTitle   = $translation?->title ?? __('Page Builder');
    $frontUrl    = $page->is_home
        ? url('/')
        : ($translation?->slug ? url($translation->slug) : url('/'));
@endphp

<x-dashboard-layout :title="$pageTitle">
    @push('scripts')
        @vite('resources/js/dashboard/page-builder.js')
    @endpush

    <div id="page-builder-root"
         class="min-h-[calc(100vh-120px)] flex flex-col gap-4"
         data-load-url="{{ route('dashboard.pages.builder.data', $page) }}"
         data-save-url="{{ route('dashboard.pages.builder.data.save', $page) }}"
         data-preview-url="{{ $frontUrl }}"
         data-page-id="{{ $page->id }}">

        {{-- Top bar --}}
        <header
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 shadow-sm flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span class="uppercase tracking-wide">{{ __('Page Builder') }}</span>
                    <span class="text-slate-300">•</span>
                    <span class="truncate max-w-[200px] sm:max-w-xs">{{ $pageTitle }}</span>
                </div>
                <h1 class="text-lg sm:text-xl font-semibold text-slate-900 dark:text-white">
                    {{ __('Visual builder') }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Drag sections from the left, then edit texts and content directly in the preview area.') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 justify-start sm:justify-end">
                {{-- Device preview controls (يمكن ربطها لاحقاً مع GrapesJS) --}}
                <div
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-1 py-1 text-xs">
                    <button type="button"
                            id="builder-device-desktop"
                            class="px-2 py-1 rounded-md text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 shadow-sm">
                        {{ __('Desktop') }}
                    </button>
                    <button type="button"
                            id="builder-device-tablet"
                            class="px-2 py-1 rounded-md text-slate-500 dark:text-slate-300 hover:bg-white/70 dark:hover:bg-slate-900/60">
                        {{ __('Tablet') }}
                    </button>
                    <button type="button"
                            id="builder-device-mobile"
                            class="px-2 py-1 rounded-md text-slate-500 dark:text-slate-300 hover:bg-white/70 dark:hover:bg-slate-900/60">
                        {{ __('Mobile') }}
                    </button>
                </div>

                <span class="hidden sm:inline-block w-px h-6 bg-slate-200 dark:bg-slate-700"></span>

                <button id="builder-save"
                        type="button"
                        class="btn btn-primary">
                    {{ __('Save') }}
                </button>

                <button id="builder-preview"
                        type="button"
                        class="btn btn-outline-secondary">
                    {{ __('Preview in editor') }}
                </button>

                <a href="{{ $frontUrl }}" target="_blank" rel="noopener"
                   class="btn btn-outline-secondary">
                    {{ __('Open live page') }}
                </a>

                <a href="{{ route('dashboard.pages.edit', $page) }}"
                   class="btn btn-link text-primary">
                    {{ __('Back to page settings') }}
                </a>
            </div>
        </header>

        {{-- Builder area --}}
        <main class="flex-1 grid grid-cols-12 gap-4 pb-4">
            {{-- Left sidebar: Blocks --}}
            <section
                class="col-span-12 lg:col-span-3 flex flex-col border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 overflow-hidden">
                <header
                    class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                            {{ __('Sections & blocks') }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('Drag a block into the page area to start building.') }}
                        </p>
                    </div>
                    {{-- مساحة مستقبلية لتصفية البلوكات أو البحث --}}
                </header>

                <div id="gjs-blocks"
                     class="flex-1 overflow-y-auto px-2 py-3 space-y-2">
                    {{-- GrapesJS سيقوم بملء هذه المنطقة بالبلوكات --}}
                </div>
            </section>

            {{-- Right: Canvas --}}
            <section
                class="col-span-12 lg:col-span-9 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 overflow-hidden flex flex-col">
                <div
                    class="px-4 py-2 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>{{ __('Canvas') }}</span>
                    <span class="hidden sm:inline">
                        {{ __('Use Ctrl/⌘ + Preview to open the live page in a new tab.') }}
                    </span>
                </div>

                <div id="gjs" class="flex-1 min-h-[400px]">
                    {{-- GrapesJS canvas --}}
                </div>
            </section>
        </main>
    </div>
</x-dashboard-layout>
