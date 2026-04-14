@php
    $itemRepeaterHeading = $itemRepeaterHeading ?? __('Items');
    $itemRepeaterDescription = $itemRepeaterDescription ?? __('Manage the card items shown in this section.');
    $itemRepeaterAddLabel = $itemRepeaterAddLabel ?? __('Add Item');
    $itemRepeaterEmptyState = $itemRepeaterEmptyState ?? __('No items yet. Add the first one.');
    $itemRepeaterTitlePlaceholder = $itemRepeaterTitlePlaceholder ?? __('e.g. Automatic malware removal');
    $itemRepeaterDescPlaceholder = $itemRepeaterDescPlaceholder ?? __('e.g. We scan for threats continuously.');
    $itemRepeaterItemLabel = $itemRepeaterItemLabel ?? __('Item');
    $itemRepeaterItemHint = $itemRepeaterItemHint ?? __('Click to edit this item');
@endphp

<div class="lg:col-span-2" data-feature-repeater data-feature-item-label="{{ $itemRepeaterItemLabel }}"
    data-feature-item-hint="{{ $itemRepeaterItemHint }}">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <label class="block text-sm font-medium text-slate-700">{{ $itemRepeaterHeading }}</label>
            <p class="mt-1 text-xs text-slate-500">{{ $itemRepeaterDescription }}</p>
        </div>
        <button type="button" data-add-feature-item
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
            <span>{{ $itemRepeaterAddLabel }}</span>
        </button>
    </div>

    <div class="mt-3">
        <div class="space-y-3" data-feature-items>
            @foreach ($protectionItems as $itemIndex => $item)
                @php
                    $itemMediaPreviewUrls = $mediaPreviewBuilder->build($item['icon_media'] ?? null);
                @endphp
                <article data-feature-item
                    class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                            <button type="button" data-feature-drag-handle
                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                aria-label="{{ __('Reorder item') }}">
                                <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                            </button>

                            <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                <button type="button" data-duplicate-feature-item
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                    aria-label="{{ __('Duplicate item') }}">
                                    <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                </button>
                                <button type="button" data-remove-feature-item
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                    aria-label="{{ __('Remove item') }}">
                                    <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <button type="button" data-feature-toggle aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                            class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right">
                            <div class="min-w-0 flex-1">
                                <p dir="auto" data-feature-item-title
                                    class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                    {{ filled($item['title'] ?? '') ? $item['title'] : $itemRepeaterItemLabel . ' ' . ($itemIndex + 1) }}
                                </p>
                                <p dir="auto" data-feature-item-summary
                                    class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                    {{ filled($item['description'] ?? '') ? $item['description'] : $itemRepeaterItemHint }}
                                </p>
                            </div>
                            <span
                                class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                <i data-feature-toggle-icon
                                    class="ti ti-chevron-down text-base leading-none {{ $loop->first ? 'rotate-180' : '' }}"
                                    aria-hidden="true"></i>
                            </span>
                        </button>
                    </div>

                    <div data-feature-item-body class="mt-4 space-y-4 {{ $loop->first ? '' : 'hidden' }}">

                        {{-- Title --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Title') }}</label>
                            <input type="text"
                                name="translations[{{ $code }}][content][items][{{ $itemIndex }}][title]"
                                data-name-template="translations[{{ $code }}][content][items][__INDEX__][title]"
                                data-feature-field="text" value="{{ $item['title'] ?? '' }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                placeholder="{{ $itemRepeaterTitlePlaceholder }}">
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                            <textarea name="translations[{{ $code }}][content][items][{{ $itemIndex }}][description]"
                                data-name-template="translations[{{ $code }}][content][items][__INDEX__][description]"
                                data-feature-field="description" rows="3"
                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                placeholder="{{ $itemRepeaterDescPlaceholder }}">{{ $item['description'] ?? '' }}</textarea>
                        </div>

                        {{-- Icon --}}
                        <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                            <div data-feature-icon-preview
                                class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand">
                                @if (!empty($item['icon']))
                                    <i class="{{ $item['icon'] }} text-2xl leading-none" aria-hidden="true"></i>
                                @else
                                    <i class="ti ti-shield-check text-2xl leading-none" aria-hidden="true"></i>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                    <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                </div>
                                <select
                                    name="translations[{{ $code }}][content][items][{{ $itemIndex }}][icon_source]"
                                    data-name-template="translations[{{ $code }}][content][items][__INDEX__][icon_source]"
                                    data-feature-field="icon_source"
                                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                    <option value="class" @selected(($item['icon_source'] ?? 'class') === 'class')>{{ __('Tabler Icon') }}
                                    </option>
                                    <option value="media" @selected(($item['icon_source'] ?? 'class') === 'media')>{{ __('SVG From Media') }}
                                    </option>
                                </select>

                                <div data-feature-icon-panel="class" class="space-y-3">
                                    <input type="text"
                                        name="translations[{{ $code }}][content][items][{{ $itemIndex }}][icon]"
                                        data-name-template="translations[{{ $code }}][content][items][__INDEX__][icon]"
                                        data-feature-field="icon" value="{{ $item['icon'] ?? '' }}"
                                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                        placeholder="ti ti-shield-check">
                                    <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                        <button type="button" data-open-section-icon-library
                                            data-icon-input-selector='[data-feature-field="icon"]'
                                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                            <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                            <span>{{ __('Choose From Icon Library') }}</span>
                                        </button>
                                    </div>
                                    <p class="text-xs text-slate-500">
                                        {{ __('Use the icon library or type a Tabler class manually.') }}
                                    </p>
                                </div>

                                <div data-feature-icon-panel="media" class="space-y-2 hidden">
                                    <input type="hidden"
                                        name="translations[{{ $code }}][content][items][{{ $itemIndex }}][icon_media]"
                                        data-name-template="translations[{{ $code }}][content][items][__INDEX__][icon_media]"
                                        data-feature-field="icon_media" value="{{ $item['icon_media'] ?? '' }}">
                                    <button type="button" data-feature-icon-media-button
                                        class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                        <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Choose SVG From Media') }}</span>
                                    </button>
                                    <div data-feature-icon-media-preview class="flex flex-wrap gap-2">
                                        @foreach ($itemMediaPreviewUrls as $url)
                                            <div
                                                class="relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                                <img src="{{ $url }}" alt=""
                                                    class="h-full w-full object-contain p-2">
                                            </div>
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-slate-500">
                                        {{ __('Upload or choose an SVG file from the media library when you need a branded icon.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div data-feature-empty
            class="{{ count($protectionItems) ? 'hidden ' : '' }}mt-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-6 text-center text-sm text-slate-500">
            {{ $itemRepeaterEmptyState }}
        </div>

        <template data-feature-item-template>
            <article data-feature-item
                class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                        <button type="button" data-feature-drag-handle
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                            aria-label="{{ __('Reorder item') }}">
                            <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                        </button>
                        <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                            <button type="button" data-duplicate-feature-item
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                aria-label="{{ __('Duplicate item') }}">
                                <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                            </button>
                            <button type="button" data-remove-feature-item
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                aria-label="{{ __('Remove item') }}">
                                <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="button" data-feature-toggle aria-expanded="false"
                        class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right">
                        <div class="min-w-0 flex-1">
                            <p dir="auto" data-feature-item-title
                                class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                {{ $itemRepeaterItemLabel }}</p>
                            <p dir="auto" data-feature-item-summary
                                class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                {{ $itemRepeaterItemHint }}</p>
                        </div>
                        <span
                            class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                            <i data-feature-toggle-icon class="ti ti-chevron-down text-base leading-none"
                                aria-hidden="true"></i>
                        </span>
                    </button>
                </div>

                <div data-feature-item-body class="mt-4 hidden space-y-4">

                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('Title') }}</label>
                        <input type="text"
                            name="translations[{{ $code }}][content][items][__INDEX__][title]"
                            data-name-template="translations[{{ $code }}][content][items][__INDEX__][title]"
                            data-feature-field="text" value=""
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            placeholder="{{ $itemRepeaterTitlePlaceholder }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                        <textarea name="translations[{{ $code }}][content][items][__INDEX__][description]"
                            data-name-template="translations[{{ $code }}][content][items][__INDEX__][description]"
                            data-feature-field="description" rows="3"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            placeholder="{{ $itemRepeaterDescPlaceholder }}"></textarea>
                    </div>

                    <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                        <div data-feature-icon-preview
                            class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand">
                            <i class="ti ti-shield-check text-2xl leading-none" aria-hidden="true"></i>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                            </div>
                            <select name="translations[{{ $code }}][content][items][__INDEX__][icon_source]"
                                data-name-template="translations[{{ $code }}][content][items][__INDEX__][icon_source]"
                                data-feature-field="icon_source"
                                class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                <option value="class">{{ __('Tabler Icon') }}</option>
                                <option value="media">{{ __('SVG From Media') }}</option>
                            </select>

                            <div data-feature-icon-panel="class" class="space-y-3">
                                <input type="text"
                                    name="translations[{{ $code }}][content][items][__INDEX__][icon]"
                                    data-name-template="translations[{{ $code }}][content][items][__INDEX__][icon]"
                                    data-feature-field="icon" value=""
                                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="ti ti-shield-check">
                                <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                    <button type="button" data-open-section-icon-library
                                        data-icon-input-selector='[data-feature-field="icon"]'
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                        <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Choose From Icon Library') }}</span>
                                    </button>
                                </div>
                                <p class="text-xs text-slate-500">
                                    {{ __('Use the icon library or type a Tabler class manually.') }}
                                </p>
                            </div>

                            <div data-feature-icon-panel="media" class="space-y-2 hidden">
                                <input type="hidden"
                                    name="translations[{{ $code }}][content][items][__INDEX__][icon_media]"
                                    data-name-template="translations[{{ $code }}][content][items][__INDEX__][icon_media]"
                                    data-feature-field="icon_media" value="">
                                <button type="button" data-feature-icon-media-button
                                    class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                    <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                    <span>{{ __('Choose SVG From Media') }}</span>
                                </button>
                                <div data-feature-icon-media-preview class="flex flex-wrap gap-2"></div>
                                <p class="text-xs text-slate-500">
                                    {{ __('Upload or choose an SVG file from the media library when you need a branded icon.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </template>
    </div>
</div>
