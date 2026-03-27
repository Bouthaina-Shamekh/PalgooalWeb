@php
    $currentLocale = $currentLocale ?? app()->getLocale();
    $selectedSectionId = (int) ($selectedSectionId ?? 0);
    $workspaceRoutePrefix = $workspaceRoutePrefix ?? 'dashboard.pages.sections.';
    $workspaceRouteBaseParameters = $workspaceRouteBaseParameters ?? ['page' => $page];
    $workspaceRouteFor =
        $workspaceRouteFor
        ?? fn(string $name, array $extra = [], bool $absolute = true) => route(
            $workspaceRoutePrefix . $name,
            array_merge($workspaceRouteBaseParameters, $extra),
            $absolute,
        );
    $sidebarTranslation = method_exists($section, 'translation') ? $section->translation($currentLocale) : null;
    $sidebarFallbackTranslation = $sidebarTranslation ?? $section->translations->first();
    $sidebarTypeMeta = $sectionTypes[$section->type] ?? null;
    $sidebarTypeLabel =
        $sidebarTypeMeta['label'] ?? \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', $section->type));
    $sidebarTitle = $sidebarFallbackTranslation?->title ?: $sidebarTypeLabel;
    $editorUrl = $workspaceRouteFor('editor', ['section' => $section], false);
    $fallbackEditUrl = $workspaceRouteFor('edit', ['section' => $section], false);
@endphp

<article data-section-id="{{ $section->id }}" data-edit-section-url="{{ $editorUrl }}"
    data-edit-section-fallback-url="{{ $fallbackEditUrl }}"
    class="sections-outline-item group rounded-2xl border border-transparent bg-white px-4 py-3 shadow-sm transform-gpu transition-all duration-200 ease-out hover:-translate-y-0.5 hover:border-slate-200 hover:bg-slate-50/70 hover:shadow-md focus-within:-translate-y-0.5 focus-within:border-slate-300 focus-within:shadow-md active:scale-[0.99] {{ $selectedSectionId === $section->id ? 'is-selected border-slate-300 bg-slate-50 shadow-lg -translate-y-0.5' : '' }}">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0 flex-1 ltr:text-left rtl:text-right">
            <p data-section-title class="truncate text-sm font-semibold text-slate-900">
                {{ $sidebarTitle }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2 ltr:justify-start rtl:justify-end rtl:flex-row-reverse">
                <span data-section-type-label class="text-xs text-slate-500">{{ $sidebarTypeLabel }}</span>
                <span data-section-status
                    class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $section->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                    {{ $section->is_active ? __('Active') : __('Hidden') }}
                </span>
            </div>
        </div>

        <div
            class="flex shrink-0 items-center gap-1 rounded-full border border-slate-200 bg-white/90 p-1 shadow-sm transition duration-200 group-hover:border-slate-300 group-hover:bg-white group-hover:shadow rtl:flex-row-reverse">
            <button type="button" data-edit-section-button
                class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition duration-200 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"
                aria-label="{{ __('Edit section') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                </svg>
            </button>
            <div class="relative">
                <button type="button" data-section-menu-button aria-expanded="false"
                    aria-label="{{ __('Open section actions') }}"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition duration-200 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 12a.75.75 0 1 0 0 .001V12Zm5.25 0a.75.75 0 1 0 0 .001V12Zm5.25 0a.75.75 0 1 0 0 .001V12Z" />
                    </svg>
                </button>

                <div data-section-menu
                    class="absolute top-full z-20 mt-2 hidden w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl ltr:right-0 ltr:left-auto rtl:left-0 rtl:right-auto">
                    <form action="{{ $workspaceRouteFor('toggle-active', ['section' => $section], false) }}"
                        method="POST">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 ltr:text-left rtl:text-right rtl:flex-row-reverse">
                            <span>{{ $section->is_active ? __('Hide') : __('Show') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 3l18 18M10.584 10.587a2 2 0 1 0 2.829 2.828M9.878 5.697A9.953 9.953 0 0 1 12 5.5c5 0 8.27 4.11 9 5.083a1.74 1.74 0 0 1 0 1.834 15.45 15.45 0 0 1-4.083 4.251M6.228 6.228A15.953 15.953 0 0 0 3 10.583a1.74 1.74 0 0 0 0 1.834C3.73 13.39 7 17.5 12 17.5c1.657 0 3.152-.45 4.478-1.065" />
                            </svg>
                        </button>
                    </form>

                    <form action="{{ $workspaceRouteFor('duplicate', ['section' => $section], false) }}"
                        method="POST">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 ltr:text-left rtl:text-right rtl:flex-row-reverse">
                            <span>{{ __('Duplicate') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 17.25v1.5a2.25 2.25 0 0 1-2.25 2.25h-9A2.25 2.25 0 0 1 2.25 18.75v-9A2.25 2.25 0 0 1 4.5 7.5H6m3-4.5h10.5A2.25 2.25 0 0 1 21.75 5.25v10.5A2.25 2.25 0 0 1 19.5 18H9A2.25 2.25 0 0 1 6.75 15.75V5.25A2.25 2.25 0 0 1 9 3Z" />
                            </svg>
                        </button>
                    </form>

                    <button type="button" data-rename-toggle
                        class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 ltr:text-left rtl:text-right rtl:flex-row-reverse">
                        <span>{{ __('Rename') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                        </svg>
                    </button>

                    <form action="{{ $workspaceRouteFor('destroy', ['section' => $section], false) }}"
                        method="POST"
                        onsubmit="return confirm('{{ __('Are you sure you want to delete this section? This action cannot be undone.') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50 ltr:text-left rtl:text-right rtl:flex-row-reverse">
                            <span>{{ __('Delete') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.245-2.327L4.772 5.79m14.456 0A48.108 48.108 0 0 0 15.75 5.25m3.478.54a48.11 48.11 0 0 1-3.478-.54m0 0V4.5A2.25 2.25 0 0 0 13.5 2.25h-3A2.25 2.25 0 0 0 8.25 4.5v.75m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            <button type="button" data-drag-handle
                class="sections-drag-handle inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition duration-200 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"
                aria-label="{{ __('Drag to reorder') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h8M8 12h8M8 18h8" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 6h.01M5 12h.01M5 18h.01" />
                </svg>
            </button>
        </div>
    </div>

    <div data-rename-panel class="mt-3 hidden border-t border-slate-200 pt-3">
        <form action="{{ $workspaceRouteFor('rename', ['section' => $section], false) }}" method="POST"
            class="space-y-3">
            @csrf
            <input type="hidden" name="locale" value="{{ $currentLocale }}">
            <div>
                <label for="rename-section-{{ $section->id }}"
                    class="mb-1 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 ltr:text-left rtl:text-right">{{ __('Section Name') }}</label>
                <input id="rename-section-{{ $section->id }}" name="title" value="{{ $sidebarTitle }}"
                    data-rename-input type="text"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition duration-200 focus:border-slate-400 focus:ring-2 focus:ring-slate-200 ltr:text-left rtl:text-right"
                    required>
            </div>
            <div class="flex items-center justify-end gap-2 rtl:flex-row-reverse">
                <button type="button" data-rename-cancel
                    class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition duration-200 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">{{ __('Cancel') }}</button>
                <button type="submit"
                    class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">{{ __('Save Name') }}</button>
            </div>
        </form>
    </div>
</article>
