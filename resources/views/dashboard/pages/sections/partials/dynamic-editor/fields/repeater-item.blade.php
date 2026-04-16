{{--
    One repeater item card — included by repeater.blade.php for both server-rendered
    items and the <template> used to clone new blank items via JS.

    Variables:
      $itemSchema  — array   normalized sub-field definitions from item_schema
      $itemData    — array   saved/old values for this item (empty for new items)
      $itemIndex   — int|string  numeric index OR the literal string '__INDEX__'
                               when rendered inside the <template> element
      $nameBase    — string  base input name: translations[locale][content][fieldKey]
      $isFirst     — bool    true for the first server-rendered item (auto-expanded)

    Input name per sub-field:
      {nameBase}[{itemIndex}][{subfieldKey}]

    Each input also carries:
      data-name-template="{nameBase}[__INDEX__][{subfieldKey}]"
    so that initDynamicRepeaters() can reindex names after add/remove/duplicate.
--}}

@php
    $isTemplate = ($itemIndex === '__INDEX__');
@endphp

<div data-dynamic-repeater-item
     class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

    {{-- ── Item toolbar ─────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5">

        {{-- Collapse toggle --}}
        <button type="button"
                data-dynamic-repeater-toggle
                aria-expanded="{{ $isTemplate || ! $isFirst ? 'false' : 'true' }}"
                class="flex min-w-0 flex-1 items-center gap-2 text-left">
            <i data-dynamic-repeater-toggle-icon
               class="ti ti-chevron-down text-sm leading-none text-slate-400 transition-transform {{ ($isFirst && ! $isTemplate) ? 'rotate-180' : '' }}"
               aria-hidden="true"></i>
            <span data-dynamic-repeater-item-label
                  class="text-sm font-medium text-slate-700">
                {{-- JS updates this via reindexItems(); Blade sets a fallback for server-rendered rows --}}
                @if (! $isTemplate)
                    {{ __('Item') }} {{ is_int($itemIndex) ? $itemIndex + 1 : $itemIndex }}
                @else
                    {{ __('New Item') }}
                @endif
            </span>
        </button>

        <div class="flex shrink-0 items-center gap-1.5">
            {{-- Duplicate --}}
            <button type="button"
                    data-duplicate-dynamic-repeater-item
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                    title="{{ __('Duplicate item') }}"
                    aria-label="{{ __('Duplicate item') }}">
                <i class="ti ti-copy text-sm leading-none" aria-hidden="true"></i>
            </button>

            {{-- Remove --}}
            <button type="button"
                    data-remove-dynamic-repeater-item
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                    title="{{ __('Remove item') }}"
                    aria-label="{{ __('Remove item') }}">
                <i class="ti ti-trash text-sm leading-none" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    {{-- ── Sub-field body (collapsible) ────────────────────────────────── --}}
    <div data-dynamic-repeater-item-body
         class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-2 {{ ($isTemplate || ! $isFirst) ? 'hidden' : '' }}">

        @foreach ($itemSchema as $subField)
            @php
                $subKey      = $subField['key'];
                $subLabel    = $subField['label'];
                $subType     = $subField['type'];        // text|textarea|url|media|boolean|select
                $subRequired = (bool) ($subField['required'] ?? false);
                $subValue    = $isTemplate ? '' : ($itemData[$subKey] ?? '');

                // Full input name for this render pass
                $inputName = $nameBase . '[' . $itemIndex . '][' . $subKey . ']';
                // Template name used by JS to reindex after mutations
                $nameTpl   = $nameBase . '[__INDEX__][' . $subKey . ']';
            @endphp

            @if ($subType === 'boolean')
                {{-- ── Boolean: hidden + checkbox ─────────────────────────── --}}
                <div class="lg:col-span-2">
                    <label class="inline-flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                        {{-- Hidden keeps the "false" value when checkbox is unchecked --}}
                        <input type="hidden"
                               name="{{ $inputName }}"
                               data-name-template="{{ $nameTpl }}"
                               value="0">
                        <input type="checkbox"
                               name="{{ $inputName }}"
                               data-name-template="{{ $nameTpl }}"
                               value="1"
                               class="rounded border-slate-300"
                               @if (! $isTemplate) @checked(filter_var($subValue, FILTER_VALIDATE_BOOLEAN)) @endif>
                        <span>{{ $subLabel }}</span>
                    </label>
                </div>

            @elseif ($subType === 'textarea')
                {{-- ── Textarea ─────────────────────────────────────────── --}}
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">{{ $subLabel }}</label>
                    <textarea name="{{ $inputName }}"
                              data-name-template="{{ $nameTpl }}"
                              rows="3"
                              @if ($subRequired) required @endif
                              class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">{{ $isTemplate ? '' : e((string) $subValue) }}</textarea>
                </div>

            @elseif ($subType === 'media')
                {{-- ── Media: plain ID input (V1 — media-picker deferred to Phase 5D) --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ $subLabel }}</label>
                    <input type="text"
                           name="{{ $inputName }}"
                           data-name-template="{{ $nameTpl }}"
                           value="{{ $isTemplate ? '' : e((string) $subValue) }}"
                           @if ($subRequired) required @endif
                           placeholder="{{ __('Media ID') }}"
                           class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                    <p class="mt-1.5 text-xs text-slate-400">{{ __('Enter a media library ID. A full picker will be added in a future update.') }}</p>
                </div>

            @elseif ($subType === 'url')
                {{-- ── URL ──────────────────────────────────────────────── --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ $subLabel }}</label>
                    <input type="url"
                           name="{{ $inputName }}"
                           data-name-template="{{ $nameTpl }}"
                           value="{{ $isTemplate ? '' : e((string) $subValue) }}"
                           @if ($subRequired) required @endif
                           placeholder="https://"
                           class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                </div>

            @elseif ($subType === 'select')
                {{-- ── Select: plain text fallback (no per-subfield options in V1) --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ $subLabel }}</label>
                    <input type="text"
                           name="{{ $inputName }}"
                           data-name-template="{{ $nameTpl }}"
                           value="{{ $isTemplate ? '' : e((string) $subValue) }}"
                           @if ($subRequired) required @endif
                           class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                    <p class="mt-1.5 text-xs text-slate-400">{{ __('Option list configuration at sub-field level is coming in a future update.') }}</p>
                </div>

            @else
                {{-- ── Text (default, also catches any future unknown types) ── --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ $subLabel }}</label>
                    <input type="text"
                           name="{{ $inputName }}"
                           data-name-template="{{ $nameTpl }}"
                           value="{{ $isTemplate ? '' : e((string) $subValue) }}"
                           @if ($subRequired) required @endif
                           class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                </div>
            @endif
        @endforeach
    </div>
</div>
