{{--
    Dynamic editor — generic repeater field partial (Phase 5C).

    Expected $field keys (set by DynamicSectionEditorRenderer::buildRepeaterPayload):
      $field['fieldKey']    — string  e.g. "gallery_items"
      $field['label']       — string  human-readable field label
      $field['name']        — string  base input name prefix:
                                      translations[{locale}][content][{fieldKey}]
      $field['id']          — string  unique DOM id prefix for this widget
      $field['itemSchema']  — array   normalized sub-field definitions
      $field['items']       — array   resolved current items (old() → saved → [])
      $field['locale']      — string  current locale code
      $field['isRequired']  — bool
      $field['isTranslatable'] — bool
      $field['helpText']    — string|null

    Input name format per sub-field:
      translations[{locale}][content][{fieldKey}][{itemIndex}][{subfieldKey}]

    JS hook: the container carries data-dynamic-repeater, and each input carries
    data-name-template with __INDEX__ in place of the item index. The generic
    initDynamicRepeaters() function in workspace.blade.php drives add/remove/
    duplicate and calls reindexItems() after every mutation.

    V1 limitations:
      - No drag-and-drop reorder.
--}}

@php
    $itemSchema = $field['itemSchema'] ?? [];
    $items = $field['items'] ?? [];
    $nameBase = $field['name']; // translations[locale][content][fieldKey]
    $widgetId = $field['id']; // unique per field per locale
    $hasItems = count($items) > 0;
    $hasSchema = count($itemSchema) > 0;
@endphp

<div class="{{ $field['wrapperClass'] }}" data-dynamic-repeater data-dynamic-repeater-id="{{ $widgetId }}">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="block text-sm font-medium text-slate-700">
                    {{ $field['label'] }}
                </span>
                @if (!$field['isTranslatable'])
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                        {{ __('Shared') }}
                    </span>
                @endif
            </div>
            @if (filled($field['helpText']))
                <p class="mt-1 text-xs text-slate-500">{{ $field['helpText'] }}</p>
            @endif
            @if (!$field['isTranslatable'])
                <p class="mt-1 text-xs text-slate-500">
                    {{ __('This repeater is edited once on the default locale tab and reused across locales in V1.') }}
                </p>
            @endif
        </div>

        @if ($hasSchema)
            <button type="button" data-add-dynamic-repeater-item
                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <span class="text-base leading-none" aria-hidden="true">+</span>
                <span>{{ __('Add Item') }}</span>
            </button>
        @endif
    </div>

    @if (!$hasSchema)
        {{-- No sub-fields defined yet — developer needs to configure item_schema --}}
        <div
            class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
            {{ __('No sub-fields are defined yet. Return to the field definition and add at least one sub-field to enable this repeater.') }}
        </div>
    @else
        {{-- ── Item list ────────────────────────────────────────────────────── --}}
        <div class="mt-3 space-y-3" data-dynamic-repeater-items>

            @foreach ($items as $itemIndex => $itemData)
                @include('dashboard.pages.sections.partials.dynamic-editor.fields.repeater-item', [
                    'itemSchema' => $itemSchema,
                    'itemData' => is_array($itemData) ? $itemData : [],
                    'itemIndex' => $itemIndex,
                    'nameBase' => $nameBase,
                    'isFirst' => $loop->first,
                ])
            @endforeach
        </div>

        {{-- ── Empty state (hidden when items exist) ───────────────────────── --}}
        <div data-dynamic-repeater-empty
            class="{{ $hasItems ? 'hidden ' : '' }}mt-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
            {{ __('No items yet. Click "Add Item" to create the first entry.') }}
        </div>

        {{-- ── Template for JS-created new items ───────────────────────────── --}}
        {{--
            Blade still executes inside <template>, so we can use @foreach here.
            The name attribute is intentionally omitted from sub-field inputs inside
            the template — initDynamicRepeaters replaces __INDEX__ in
            data-name-template and sets .name when it appends the cloned item.
        --}}
        <template data-dynamic-repeater-template>
            @include('dashboard.pages.sections.partials.dynamic-editor.fields.repeater-item', [
                'itemSchema' => $itemSchema,
                'itemData' => [],
                'itemIndex' => '__INDEX__',
                'nameBase' => $nameBase,
                'isFirst' => false,
            ])
        </template>

        {{-- ── Footer add button (visible once list has at least 1 item) ───── --}}
        <div data-dynamic-repeater-footer-add class="{{ $hasItems ? '' : 'hidden ' }}mt-3 flex justify-end">
            <button type="button" data-add-dynamic-repeater-item
                class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                <span class="text-base leading-none" aria-hidden="true">+</span>
                <span>{{ __('Add Item') }}</span>
            </button>
        </div>
    @endif
</div>
