@php
    $currentLocale = app()->getLocale();
    $sectionTranslation = method_exists($section, 'translation') ? $section->translation($currentLocale) : null;
    $fallbackTranslation = $sectionTranslation ?? $section->translations->first();
    $sectionTypeMeta = $sectionTypes[$section->type] ?? null;
    $sectionTypeLabel = $sectionTypeMeta['label'] ?? \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', $section->type));
    $editorTitle = $fallbackTranslation?->title ?: $sectionTypeLabel;
@endphp

<div class="flex h-full min-h-0 flex-col overflow-hidden rounded-[1.5rem] bg-white shadow-[0_24px_60px_-36px_rgba(15,23,42,0.35)]" data-section-editor-root>
    <div class="border-b border-slate-200 px-4 py-3">
        <div class="min-w-0">
            <button
                type="button"
                data-close-section-editor
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-slate-900 rtl:flex-row-reverse"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6 4.5 12l6 6M19.5 12h-15" />
                </svg>
                <span>{{ __('Back to elements') }}</span>
            </button>

            <h3 class="mt-2 truncate text-lg font-semibold text-slate-900" data-section-editor-heading>{{ $editorTitle }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('Update this section without leaving the workspace.') }}</p>

            <div class="mt-3 flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                <span data-section-editor-type class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600">
                    {{ $sectionTypeLabel }}
                </span>
                <span
                    data-section-editor-status
                    class="rounded-full px-3 py-1 text-xs font-medium {{ $section->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}"
                >
                    {{ $section->is_active ? __('Active') : __('Hidden') }}
                </span>
            </div>
        </div>
    </div>

    <div class="workspace-scrollbar min-h-0 flex-1 overflow-y-auto px-0 py-0">
        @include('dashboard.pages.sections.partials.editor-form', [
            'formId' => 'sidebar-section-edit-form',
            'formClass' => 'space-y-0',
            'preventNativeSubmit' => true,
            'surfaceClass' => 'rounded-none border-b border-slate-200 bg-transparent shadow-none',
            'sectionHeaderClass' => 'border-b border-slate-200 px-4 py-3',
            'sectionBodyClass' => 'px-4 py-3',
            'settingsGridClass' => 'grid grid-cols-1 gap-3',
            'contentGridClass' => 'grid grid-cols-1 gap-3',
            'showOrderField' => false,
        ])
    </div>

    <div class="shrink-0 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur">
        <button
            type="button"
            data-section-editor-submit
            class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
            {{ __('Save Changes') }}
        </button>
    </div>
</div>
