<div id="sections-icon-library-overlay" class="fixed inset-0 z-[70] hidden bg-slate-950/55"></div>
<div id="sections-icon-library-modal" class="fixed inset-0 z-[71] hidden items-center justify-center p-4 lg:p-6" aria-hidden="true">
    <div class="flex max-h-[calc(100vh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
        <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
            <div class="flex items-start justify-between gap-4 rtl:flex-row-reverse">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('Icon Library') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Search and choose an icon for the current item.') }}</p>
                </div>
                <div class="flex items-center gap-2 rtl:flex-row-reverse">
                    <button
                        type="button"
                        data-section-icon-library-clear
                        class="hidden rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                    >
                        {{ __('Clear Icon') }}
                    </button>
                    <button
                        type="button"
                        data-close-section-icon-library
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
                        aria-label="{{ __('Close') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-1 flex-col overflow-hidden px-5 py-4 lg:px-6">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 ltr:left-3 rtl:right-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6 6a7.5 7.5 0 0 0 10.65 10.65Z" />
                </svg>
                <input
                    id="sections-icon-library-search"
                    type="text"
                    placeholder="{{ __('Search icons by name or use case') }}"
                    class="w-full rounded-full border border-slate-200 bg-white py-3 text-sm text-slate-700 outline-none transition focus:border-slate-400 ltr:pl-10 ltr:pr-4 ltr:text-left rtl:pl-4 rtl:pr-10 rtl:text-right"
                >
            </div>

            <div class="mt-3 flex items-center justify-between gap-3 text-xs text-slate-500 rtl:flex-row-reverse">
                <p>{{ __('Click any icon to apply it immediately to the current field.') }}</p>
                <span id="sections-icon-library-count"></span>
            </div>

            <div
                id="sections-icon-library-grid"
                class="workspace-scrollbar mt-4 grid flex-1 grid-cols-2 gap-3 overflow-y-auto pb-1 pr-1 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
            ></div>

            <div
                id="sections-icon-library-empty"
                class="mt-4 hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500"
            >
                {{ __('No icons match this search yet.') }}
            </div>
        </div>
    </div>
</div>
