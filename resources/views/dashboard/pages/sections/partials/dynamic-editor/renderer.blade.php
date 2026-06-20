@php
    use App\Support\Sections\SectionFieldClassifier;

    $dynamicLocaleEditor = $dynamicEditor['locales'][$code] ?? null;
    $dynamicGroups       = is_array($dynamicLocaleEditor['groups'] ?? null) ? $dynamicLocaleEditor['groups'] : [];

    // Split all groups into Content and Design buckets.
    // Unknown field_keys fall back to 'content' — see SectionFieldClassifier.
    ['content' => $contentGroups, 'design' => $designGroups] =
        SectionFieldClassifier::splitGroups($dynamicGroups);

    // Unique tab-switcher scope per locale panel (e.g. "field-tab-ar", "field-tab-en").
    $fieldTabId = 'field-tab-' . $code;

    // Phase B — Field counts computed from PHP split (no JS recalculation needed).
    $contentFieldCount = array_sum(array_map(fn ($g) => count($g['fields']), $contentGroups));
    $designFieldCount  = array_sum(array_map(fn ($g) => count($g['fields']), $designGroups));

    // Phase A — localStorage key scoped to this section (shared across locales).
    $storageKey = 'section-editor-tab-' . ($section->id ?? '0');
@endphp

<div class="{{ $contentGridClass }}">
    {{-- Hidden title replica — always present regardless of active tab --}}
    <input type="hidden" name="translations[{{ $code }}][title]" value="{{ $sectionTitleValue }}">

    @if ($dynamicGroups === [])

        {{-- ── No definition-driven fields at all ──────────────────────────── --}}
        <div class="lg:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
            {{ t('dashboard.No_Dynamic_Fields', 'No dynamic fields are registered for this locale yet.') }}
        </div>

    @else

        {{-- ── Content / Design Tab Switcher ───────────────────────────────── --}}
        <div class="lg:col-span-2">
            <div class="flex gap-1 rounded-2xl bg-slate-100 p-1"
                 role="tablist"
                 aria-label="{{ t('dashboard.Content_Tab', 'المحتوى') }} / {{ t('dashboard.Design_Tab', 'التنسيق') }}">

                {{-- Content tab — active by default; JS may override via localStorage --}}
                <button type="button" role="tab" aria-selected="true"
                    data-field-tab-btn="{{ $fieldTabId }}"
                    data-field-tab="content"
                    class="flex-1 rounded-xl px-4 py-2 text-sm font-semibold text-slate-900 bg-white shadow-sm transition-all duration-150 inline-flex items-center justify-center gap-2">
                    {{ t('dashboard.Content_Tab', 'المحتوى') }}
                    {{-- Phase B: field count badge --}}
                    <span data-field-tab-count="{{ $fieldTabId }}"
                          class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-indigo-100 text-indigo-700 transition-colors duration-150">
                        {{ $contentFieldCount }}
                    </span>
                </button>

                {{-- Design tab — hidden by default --}}
                <button type="button" role="tab" aria-selected="false"
                    data-field-tab-btn="{{ $fieldTabId }}"
                    data-field-tab="design"
                    class="flex-1 rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition-all duration-150 hover:text-slate-700 inline-flex items-center justify-center gap-2">
                    {{ t('dashboard.Design_Tab', 'التنسيق') }}
                    {{-- Phase B: field count badge --}}
                    <span data-field-tab-count="{{ $fieldTabId }}"
                          class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-slate-200 text-slate-500 transition-colors duration-150">
                        {{ $designFieldCount }}
                    </span>
                </button>
            </div>
        </div>

        {{-- ── Content Tab Panel ─────────────────────────────────────────────── --}}
        {{-- IMPORTANT: both panels stay in the DOM at all times.                --}}
        {{-- Hidden inputs inside display:none elements ARE submitted by         --}}
        {{-- the browser, so the save payload is always complete.                --}}
        <div class="lg:col-span-2 space-y-4"
             data-field-tab-panel="{{ $fieldTabId }}"
             data-field-tab="content">

            @foreach ($contentGroups as $dynamicGroup)
                {{-- Phase 3: Collapsible group — closed by default; JS opens first (or last saved state) --}}
                <details class="rounded-3xl border border-slate-200 bg-white overflow-hidden"
                         data-group-key="{{ $dynamicGroup['key'] }}">
                    <summary class="flex cursor-pointer select-none items-center justify-between px-5 py-4 hover:bg-slate-50 transition-colors duration-150">
                        <div class="flex items-center gap-2.5">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                                {{ $dynamicGroup['label'] }}
                            </h3>
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                {{ count($dynamicGroup['fields']) }}
                            </span>
                        </div>
                        <svg class="group-chevron h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </summary>
                    <div class="border-t border-slate-100 px-5 pb-5 pt-4">
                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-1">
                            @foreach ($dynamicGroup['fields'] as $field)
                                @include($field['partial'], ['field' => $field])
                                @foreach ($field['replicaInputs'] ?? [] as $replicaInput)
                                    <input type="hidden" name="{{ $replicaInput['name'] }}" value="{{ $replicaInput['value'] }}">
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </details>
            @endforeach

            @if ($contentFieldCount === 0)
                {{-- All fields are design fields — content tab is empty (rare edge case) --}}
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-6 text-center text-sm text-slate-400">
                    {{ t('dashboard.No_Content_Fields', 'لا توجد حقول محتوى لهذا السكشن.') }}
                </div>
            @endif
        </div>

        {{-- ── Design Tab Panel ─────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-4 hidden"
             data-field-tab-panel="{{ $fieldTabId }}"
             data-field-tab="design">

            @foreach ($designGroups as $dynamicGroup)
                {{-- Phase 3: Collapsible group — closed by default; JS opens first (or last saved state) --}}
                <details class="rounded-3xl border border-slate-200 bg-white overflow-hidden"
                         data-group-key="{{ $dynamicGroup['key'] }}">
                    <summary class="flex cursor-pointer select-none items-center justify-between px-5 py-4 hover:bg-slate-50 transition-colors duration-150">
                        <div class="flex items-center gap-2.5">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                                {{ $dynamicGroup['label'] }}
                            </h3>
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                {{ count($dynamicGroup['fields']) }}
                            </span>
                        </div>
                        <svg class="group-chevron h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </summary>
                    <div class="border-t border-slate-100 px-5 pb-5 pt-4">
                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-1">
                            @foreach ($dynamicGroup['fields'] as $field)
                                @include($field['partial'], ['field' => $field])
                                @foreach ($field['replicaInputs'] ?? [] as $replicaInput)
                                    <input type="hidden" name="{{ $replicaInput['name'] }}" value="{{ $replicaInput['value'] }}">
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </details>
            @endforeach

            @if ($designFieldCount === 0)
                {{-- Empty Design state — section has no design fields --}}
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-600">
                        {{ t('dashboard.No_Design_Fields', 'لا توجد إعدادات تنسيق لهذا السكشن حالياً.') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        {{ t('dashboard.No_Design_Fields_Hint', 'أضف حقول تنسيق (layout_style، image_position…) لتظهر هنا.') }}
                    </p>
                </div>
            @endif
        </div>

    @endif
</div>

{{-- JS initializers registered globally in workspace.blade.php:                      --}}
{{--   window.initFieldTabs     → Content / Design tab switcher                       --}}
{{--   window.initGroupAccordion → Collapsible field group accordion (Phase 3)        --}}
{{-- Both called from bindSectionEditor() in index.blade.php after AJAX load.         --}}
{{-- Inline <script> tags inside innerHTML are NOT executed by the browser,           --}}
{{-- so all logic must live in pre-registered global initializers.                    --}}
