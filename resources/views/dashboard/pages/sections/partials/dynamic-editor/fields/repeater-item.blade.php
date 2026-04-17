@php
    $isTemplate = $itemIndex === '__INDEX__';
    $hasIconSourceField = collect($itemSchema)->contains(
        fn ($field) => is_array($field) && (($field['key'] ?? null) === 'icon_source'),
    );
    $hasIconSvgField = collect($itemSchema)->contains(
        fn ($field) => is_array($field) && (($field['key'] ?? null) === 'icon_svg'),
    );
    $mediaPreviewBuilder = app(\App\Support\Sections\SectionMediaPreviewBuilder::class);

    // Pre-compute the initial icon source for the picker card
    if ($hasIconSourceField) {
        $allowedCardSources = $hasIconSvgField ? ['class', 'media', 'svg'] : ['class', 'media'];
        $rawCardSource = (string) ($itemData['icon_source'] ?? 'class');
        $cardInitialSource = $isTemplate
            ? 'class'
            : (in_array($rawCardSource, $allowedCardSources, true) ? $rawCardSource : 'class');
    }
@endphp

<div data-dynamic-repeater-item class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5">
        <button type="button" data-dynamic-repeater-toggle
            aria-expanded="{{ $isTemplate || ! $isFirst ? 'false' : 'true' }}"
            class="flex min-w-0 flex-1 items-center gap-2 text-left">
            <i data-dynamic-repeater-toggle-icon
                class="ti ti-chevron-down text-sm leading-none text-slate-400 transition-transform {{ $isFirst && ! $isTemplate ? 'rotate-180' : '' }}"
                aria-hidden="true"></i>
            <span data-dynamic-repeater-item-label class="text-sm font-medium text-slate-700">
                @if (! $isTemplate)
                    {{ __('Item') }} {{ is_int($itemIndex) ? $itemIndex + 1 : $itemIndex }}
                @else
                    {{ __('New Item') }}
                @endif
            </span>
        </button>

        <div class="flex shrink-0 items-center gap-1.5">
            <button type="button" data-duplicate-dynamic-repeater-item
                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                title="{{ __('Duplicate item') }}" aria-label="{{ __('Duplicate item') }}">
                <i class="ti ti-copy text-sm leading-none" aria-hidden="true"></i>
            </button>

            <button type="button" data-remove-dynamic-repeater-item
                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                title="{{ __('Remove item') }}" aria-label="{{ __('Remove item') }}">
                <i class="ti ti-trash text-sm leading-none" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div data-dynamic-repeater-item-body
        class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-2 {{ $isTemplate || ! $isFirst ? 'hidden' : '' }}">
        @foreach ($itemSchema as $subField)
            @php
                $subKey = (string) ($subField['key'] ?? '');
                $subLabel = (string) ($subField['label'] ?? $subKey);
                $subType = (string) ($subField['type'] ?? 'text');
                $subRequired = (bool) ($subField['required'] ?? false);
                $subValue = $isTemplate ? '' : ($itemData[$subKey] ?? '');
                $inputName = $nameBase . '[' . $itemIndex . '][' . $subKey . ']';
                $nameTpl = $nameBase . '[__INDEX__][' . $subKey . ']';
                $fieldIdBase =
                    'dyn_rep_' .
                    substr(md5($nameBase), 0, 8) .
                    '_' .
                    preg_replace('/[^A-Za-z0-9_]+/', '_', $subKey) .
                    '_' .
                    $itemIndex;
                $fieldDomId = $fieldIdBase;
                $previewDomId = $fieldIdBase . '_preview';
                $allowedIconSources = $hasIconSvgField ? ['class', 'media', 'svg'] : ['class', 'media'];
                $currentIconSource = (string) ($itemData['icon_source'] ?? 'class');
                $normalizedIconSource = in_array($currentIconSource, $allowedIconSources, true)
                    ? $currentIconSource
                    : 'class';
                $isIconSourceField = $subKey === 'icon_source';
                $isIconMediaField = $subKey === 'icon_media';
                $isIconSvgField = $subKey === 'icon_svg';
                $isIconClassField =
                    ! $isIconSourceField &&
                    ! $isIconMediaField &&
                    ! $isIconSvgField &&
                    ($subKey === 'icon' ||
                        $subKey === 'icon_class' ||
                        str_ends_with($subKey, '_icon') ||
                        str_ends_with($subKey, '_icon_class'));
                $iconPanel = null;

                if ($hasIconSourceField) {
                    if ($isIconMediaField) {
                        $iconPanel = 'media';
                    } elseif ($isIconSvgField) {
                        $iconPanel = 'svg';
                    } elseif ($isIconClassField) {
                        $iconPanel = 'class';
                    }
                }

                $fieldWrapperClass = trim(
                    ($subType === 'boolean' || $subType === 'textarea' ? 'lg:col-span-2 ' : '') .
                        ($iconPanel !== null && $iconPanel !== $normalizedIconSource ? 'hidden' : ''),
                );
                $mediaPreviewUrls = $subType === 'media' && ! $isTemplate
                    ? $mediaPreviewBuilder->build($subValue)
                    : [];
            @endphp

            @if ($hasIconSourceField && $isIconSourceField)
                {{--
                    Picker card takes over the icon_source UI.
                    Keep the select hidden in the DOM: getIconSource() and toggleIconPanels()
                    still read from it, and reindexItems() still rewrites its name.
                --}}
                <select name="{{ $inputName }}" id="{{ $fieldDomId }}"
                    data-name-template="{{ $nameTpl }}"
                    data-dynamic-repeater-field="{{ $subKey }}"
                    class="sr-only" aria-hidden="true" tabindex="-1">
                    <option value="class" @selected(! $isTemplate && $normalizedIconSource === 'class')>class</option>
                    <option value="media" @selected(! $isTemplate && $normalizedIconSource === 'media')>media</option>
                    @if ($hasIconSvgField)
                        <option value="svg" @selected(! $isTemplate && $normalizedIconSource === 'svg')>svg</option>
                    @endif
                </select>
            @elseif ($hasIconSourceField && $isIconClassField)
                {{--
                    Picker card takes over the icon class UI.
                    Keep the input hidden: the icon library modal writes to it and
                    data-dynamic-repeater-icon-class-field lets JS find/sanitize it.
                --}}
                <input type="hidden" name="{{ $inputName }}" id="{{ $fieldDomId }}"
                    data-name-template="{{ $nameTpl }}"
                    data-dynamic-repeater-field="{{ $subKey }}"
                    data-dynamic-repeater-icon-class-field
                    value="{{ $isTemplate ? '' : (string) $subValue }}">
            @elseif ($hasIconSourceField && $isIconMediaField)
                {{--
                    Picker card takes over the icon media UI.
                    Keep the hidden input and the preview div: ensureMediaTargets() wires them
                    to the card's Choose Media button; media-picker.js renders thumbnails
                    into the preview div; refreshIconCard() reads from it.
                    Both elements are rendered here but presented inside the picker card below.
                --}}
                <input type="hidden" id="{{ $fieldDomId }}" name="{{ $inputName }}"
                    data-name-template="{{ $nameTpl }}"
                    data-dynamic-repeater-field="{{ $subKey }}"
                    data-dynamic-repeater-media-input
                    value="{{ $isTemplate ? '' : (string) $subValue }}">
            @elseif ($subType === 'boolean')
                <div class="{{ $fieldWrapperClass !== '' ? $fieldWrapperClass : 'lg:col-span-2' }}"
                    @if ($iconPanel) data-dynamic-repeater-icon-panel="{{ $iconPanel }}" @endif>
                    <label
                        class="inline-flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                        <input type="hidden" name="{{ $inputName }}" data-name-template="{{ $nameTpl }}"
                            data-dynamic-repeater-field="{{ $subKey }}" value="0">
                        <input type="checkbox" name="{{ $inputName }}" data-name-template="{{ $nameTpl }}"
                            data-dynamic-repeater-field="{{ $subKey }}" value="1" class="rounded border-slate-300"
                            @if (! $isTemplate) @checked(filter_var($subValue, FILTER_VALIDATE_BOOLEAN)) @endif>
                        <span>{{ $subLabel }}</span>
                    </label>
                </div>
            @elseif ($subType === 'textarea')
                <div class="{{ $fieldWrapperClass !== '' ? $fieldWrapperClass : 'lg:col-span-2' }}"
                    @if ($iconPanel) data-dynamic-repeater-icon-panel="{{ $iconPanel }}" @endif>
                    <label class="block text-sm font-medium text-slate-700">{{ $subLabel }}</label>
                    <textarea name="{{ $inputName }}" data-name-template="{{ $nameTpl }}"
                        data-dynamic-repeater-field="{{ $subKey }}" rows="3"
                        @if ($subRequired) required @endif
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">{{ $isTemplate ? '' : (string) $subValue }}</textarea>
                </div>
            @elseif ($subType === 'media')
                <div class="{{ $fieldWrapperClass }}" @if ($iconPanel) data-dynamic-repeater-icon-panel="{{ $iconPanel }}" @endif>
                    <label class="block text-sm font-medium text-slate-700">{{ $subLabel }}</label>
                    <input type="hidden" id="{{ $fieldDomId }}" name="{{ $inputName }}"
                        data-name-template="{{ $nameTpl }}" data-dynamic-repeater-field="{{ $subKey }}"
                        data-dynamic-repeater-media-input value="{{ $isTemplate ? '' : (string) $subValue }}">

                    <button type="button" data-dynamic-repeater-media-picker-button
                        data-dynamic-repeater-field="{{ $subKey }}" data-target-input="{{ $fieldDomId }}"
                        data-target-preview="{{ $previewDomId }}" data-multiple="false" data-store-value="id"
                        class="btn-open-media-picker mt-2 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white">
                        <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                        <span>{{ __('Choose From Media Library') }}</span>
                    </button>

                    <div id="{{ $previewDomId }}" data-dynamic-repeater-media-preview
                        data-dynamic-repeater-field="{{ $subKey }}" class="mt-2 flex flex-wrap gap-2">
                        @foreach ($mediaPreviewUrls as $url)
                            <div
                                class="relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                <img src="{{ $url }}" alt="" class="h-full w-full object-contain p-2">
                            </div>
                        @endforeach
                    </div>

                    <p class="mt-1.5 text-xs text-slate-500">
                        {{ __('Choose a file from the media library for this field.') }}
                    </p>
                </div>
            @elseif ($isIconSourceField)
                {{-- Fallback: icon_source without a paired icon class field — render raw select --}}
                <div class="{{ $fieldWrapperClass }}">
                    <label class="block text-sm font-medium text-slate-700">{{ $subLabel }}</label>
                    <select id="{{ $fieldDomId }}" name="{{ $inputName }}" data-name-template="{{ $nameTpl }}"
                        data-dynamic-repeater-field="{{ $subKey }}" @if ($subRequired) required @endif
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                        <option value="class" @selected($normalizedIconSource === 'class')>{{ __('Tabler Icon') }}</option>
                        <option value="media" @selected($normalizedIconSource === 'media')>{{ __('SVG From Media') }}</option>
                        @if ($hasIconSvgField)
                            <option value="svg" @selected($normalizedIconSource === 'svg')>{{ __('Inline SVG') }}</option>
                        @endif
                    </select>
                </div>
            @elseif ($subType === 'url')
                <div class="{{ $fieldWrapperClass }}" @if ($iconPanel) data-dynamic-repeater-icon-panel="{{ $iconPanel }}" @endif>
                    <label class="block text-sm font-medium text-slate-700">{{ $subLabel }}</label>
                    <input type="url" name="{{ $inputName }}" data-name-template="{{ $nameTpl }}"
                        data-dynamic-repeater-field="{{ $subKey }}" value="{{ $isTemplate ? '' : (string) $subValue }}"
                        @if ($subRequired) required @endif placeholder="https://"
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                </div>
            @elseif ($subType === 'select')
                <div class="{{ $fieldWrapperClass }}" @if ($iconPanel) data-dynamic-repeater-icon-panel="{{ $iconPanel }}" @endif>
                    <label class="block text-sm font-medium text-slate-700">{{ $subLabel }}</label>
                    <input type="text" name="{{ $inputName }}" data-name-template="{{ $nameTpl }}"
                        data-dynamic-repeater-field="{{ $subKey }}" value="{{ $isTemplate ? '' : (string) $subValue }}"
                        @if ($subRequired) required @endif
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                    <p class="mt-1.5 text-xs text-slate-400">
                        {{ __('Option list configuration at sub-field level is coming in a future update.') }}
                    </p>
                </div>
            @else
                <div class="{{ $fieldWrapperClass }}" @if ($iconPanel) data-dynamic-repeater-icon-panel="{{ $iconPanel }}" @endif>
                    <label class="block text-sm font-medium text-slate-700">{{ $subLabel }}</label>
                    <input type="text" name="{{ $inputName }}" data-name-template="{{ $nameTpl }}"
                        data-dynamic-repeater-field="{{ $subKey }}"
                        @if ($isIconClassField) data-dynamic-repeater-icon-class-field @endif
                        value="{{ $isTemplate ? '' : (string) $subValue }}" @if ($subRequired) required @endif
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">

                    @if ($isIconClassField)
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <button type="button" data-open-section-icon-library
                                data-icon-input-selector='[data-dynamic-repeater-field="{{ $subKey }}"]'
                                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white">
                                <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                <span>{{ __('Choose From Icon Library') }}</span>
                            </button>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500">
                            {{ __('Use the icon library or type a Tabler class manually.') }}
                        </p>
                    @endif
                </div>
            @endif
        @endforeach

        @if ($hasIconSourceField)
            {{--
                Icon / Media Picker Card.
                Visual: large centered preview → two primary buttons → clear link.
                Data contract preserved via the hidden inputs rendered in the @foreach above:
                  • sr-only <select data-dynamic-repeater-field="icon_source">
                  • <input type="hidden" data-dynamic-repeater-icon-class-field>
                  • <input type="hidden" data-dynamic-repeater-media-input data-dynamic-repeater-field="icon_media">
            --}}
            <div data-icon-picker-card class="lg:col-span-2 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">

                <div class="flex flex-col items-center gap-3 px-5 py-5">

                    {{--
                        Large preview box — populated entirely by refreshIconCard() in JS.
                        Shows the selected Tabler icon class, media thumbnail clone, or placeholder.
                        Dashed border signals an intentional content slot; fills cleanly when set.
                    --}}
                    <div data-icon-card-preview
                        class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-200 bg-white text-slate-400">
                    </div>

                    {{-- Primary action buttons — side-by-side, wrapping only on very narrow widths --}}
                    <div class="flex flex-wrap justify-center gap-2">

                        {{--
                            Icon Library — opens the shared icon library modal.
                            data-open-section-icon-library: handled by the global click delegation in workspace JS.
                            data-icon-input-selector: resolveInputFromTrigger() scopes the lookup to the item.
                            data-icon-card-choose-icon: bindItem() adds a pre-click listener that sets
                              icon_source='class' before the global delegation fires.
                        --}}
                        <button type="button" data-icon-card-choose-icon
                            data-open-section-icon-library
                            data-icon-input-selector="[data-dynamic-repeater-icon-class-field]"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                            <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                            {{ __('Icon Library') }}
                        </button>

                        {{--
                            Upload SVG — opens the media picker.
                            btn-open-media-picker: handled by media-picker.js on click.
                            data-dynamic-repeater-media-picker-button + data-dynamic-repeater-field="icon_media":
                              ensureMediaTargets() assigns IDs and wires data-target-input / data-target-preview.
                            data-icon-card-choose-media: bindItem() adds a pre-click listener that sets
                              icon_source='media' before media-picker.js fires.
                        --}}
                        <button type="button" data-icon-card-choose-media
                            class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                            data-dynamic-repeater-media-picker-button
                            data-dynamic-repeater-field="icon_media"
                            data-multiple="false"
                            data-store-value="id">
                            <i class="ti ti-photo-up text-base leading-none" aria-hidden="true"></i>
                            {{ __('Upload SVG') }}
                        </button>

                    </div>

                    {{-- Clear — secondary, visually recessive --}}
                    <button type="button" data-icon-card-clear
                        class="text-xs text-slate-400 transition hover:text-rose-500 focus:outline-none focus-visible:ring-1 focus-visible:ring-rose-300 focus-visible:ring-offset-1">
                        {{ __('Clear') }}
                    </button>

                </div>

                {{--
                    Hidden media preview div — never shown in the UI.
                    Required by ensureMediaTargets() (assigns an ID, wires it to the Upload SVG button)
                    and by media-picker.js (renders thumbnails into it on selection).
                    refreshIconCard() reads the <img> inside it to clone into the visible preview box.
                --}}
                <div data-icon-card-media-preview
                    data-dynamic-repeater-media-preview
                    data-dynamic-repeater-field="icon_media"
                    class="hidden" aria-hidden="true">
                    @if (! $isTemplate)
                        @php
                            $cardMediaValue = (string) ($itemData['icon_media'] ?? '');
                            $cardMediaUrls = $cardMediaValue !== ''
                                ? $mediaPreviewBuilder->build($cardMediaValue)
                                : [];
                        @endphp
                        @foreach ($cardMediaUrls as $cardUrl)
                            <img src="{{ $cardUrl }}" alt="" class="h-full w-full object-contain">
                        @endforeach
                    @endif
                </div>

            </div>
        @endif
    </div>
</div>
