<div class="lg:col-span-2" data-build-step-repeater data-build-step-item-label="{{ __('Step') }}"
    data-build-step-item-hint="{{ __('Click to edit this step') }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <label class="block text-sm font-medium text-slate-700">{{ __('Build Steps') }}</label>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('Create the process steps as individual items with their own icon and highlight state.') }}</p>
        </div>
        <button type="button" data-add-build-step
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
            <span>{{ __('Add Step') }}</span>
        </button>
    </div>

    <div class="mt-3">
        <div class="space-y-3" data-build-step-items>
            @foreach ($buildStepItems as $stepIndex => $stepItem)
                <article data-build-step-item
                    class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                            <button type="button" data-build-step-drag-handle
                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                aria-label="{{ __('Reorder step') }}">
                                <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                            </button>
                            <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                <button type="button" data-duplicate-build-step
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                    aria-label="{{ __('Duplicate step') }}">
                                    <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                </button>
                                <button type="button" data-remove-build-step
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                    aria-label="{{ __('Remove step') }}">
                                    <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <button type="button" data-build-step-toggle
                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                            class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right">
                            <div class="min-w-0 flex-1">
                                <p dir="auto" data-build-step-item-title
                                    class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                    {{ filled($stepItem['title'] ?? '') ? $stepItem['title'] : __('Step') . ' ' . ($stepIndex + 1) }}
                                </p>
                                <p dir="auto" data-build-step-item-summary
                                    class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                    {{ !empty($stepItem['is_accent'])
                                        ? __('Highlighted in red')
                                        : (($stepItem['icon_source'] ?? 'class') === 'media'
                                            ? (!empty($stepItem['icon_media'])
                                                ? __('SVG from media library')
                                                : __('Click to edit this step'))
                                            : (filled($stepItem['icon'] ?? '')
                                                ? __('Tabler icon selected')
                                                : __('Click to edit this step'))) }}
                                </p>
                            </div>
                            <span
                                class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                <i data-build-step-toggle-icon
                                    class="ti ti-chevron-down text-base leading-none {{ $loop->first ? 'rotate-180' : '' }}"
                                    aria-hidden="true"></i>
                            </span>
                        </button>
                    </div>

                    <div data-build-step-item-body class="mt-4 space-y-4 {{ $loop->first ? '' : 'hidden' }}">
                        <div>
                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Step Title') }}</label>
                                <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                            </div>
                            <input type="text"
                                name="translations[{{ $code }}][content][steps][{{ $stepIndex }}][title]"
                                data-name-template="translations[{{ $code }}][content][steps][__INDEX__][title]"
                                data-build-step-field="title" value="{{ $stepItem['title'] ?? '' }}"
                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                placeholder="{{ __('Example: Development') }}">
                            <p class="mt-2 text-xs text-slate-500">
                                {{ __('This text appears inside the process card in the timeline.') }}</p>
                        </div>

                        @php
                            $stepMediaPreviewUrls = $mediaPreviewBuilder->build($stepItem['icon_media'] ?? null);
                        @endphp
                        <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                            <div data-build-step-icon-preview
                                class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand">
                                <i class="{{ $stepItem['icon'] ?: 'ti ti-search' }} text-2xl leading-none"
                                    aria-hidden="true"></i>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                    <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                </div>
                                <select
                                    name="translations[{{ $code }}][content][steps][{{ $stepIndex }}][icon_source]"
                                    data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon_source]"
                                    data-build-step-field="icon_source"
                                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                    <option value="class" @selected(($stepItem['icon_source'] ?? 'class') === 'class')>{{ __('Tabler Icon') }}
                                    </option>
                                    <option value="media" @selected(($stepItem['icon_source'] ?? 'class') === 'media')>{{ __('SVG From Media') }}
                                    </option>
                                </select>
                                <div data-build-step-icon-panel="class" class="space-y-3">
                                    <input type="text"
                                        name="translations[{{ $code }}][content][steps][{{ $stepIndex }}][icon]"
                                        data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon]"
                                        data-build-step-field="icon" value="{{ $stepItem['icon'] ?? '' }}"
                                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                        placeholder="ti ti-search">
                                    <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                        <button type="button" data-open-section-icon-library
                                            data-icon-input-selector='[data-build-step-field="icon"]'
                                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                            <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                            <span>{{ __('Choose From Icon Library') }}</span>
                                        </button>
                                    </div>
                                    <p class="text-xs text-slate-500">
                                        {{ __('Use the icon library or type a Tabler class manually.') }}</p>
                                </div>
                                <div data-build-step-icon-panel="media" class="space-y-2 hidden">
                                    <input type="hidden"
                                        name="translations[{{ $code }}][content][steps][{{ $stepIndex }}][icon_media]"
                                        data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon_media]"
                                        data-build-step-field="icon_media" value="{{ $stepItem['icon_media'] ?? '' }}">
                                    <button type="button" data-build-step-icon-media-button
                                        class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                        <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Choose SVG From Media') }}</span>
                                    </button>
                                    <div data-build-step-icon-media-preview class="flex flex-wrap gap-2">
                                        @foreach ($stepMediaPreviewUrls as $url)
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

                        <label
                            class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 rtl:flex-row-reverse">
                            <input type="checkbox"
                                name="translations[{{ $code }}][content][steps][{{ $stepIndex }}][is_accent]"
                                value="1"
                                data-name-template="translations[{{ $code }}][content][steps][__INDEX__][is_accent]"
                                data-build-step-field="accent" class="rounded border-slate-300"
                                {{ !empty($stepItem['is_accent']) ? 'checked' : '' }}>
                            <span>{{ __('Highlight this step in red') }}</span>
                        </label>
                    </div>
                </article>
            @endforeach
        </div>

        <div data-build-step-empty
            class="{{ count($buildStepItems) ? 'hidden ' : '' }}mt-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-6 text-center text-sm text-slate-500">
            {{ __('No build steps yet. Add the first step to start the process timeline.') }}
        </div>

        <template data-build-step-item-template>
            <article data-build-step-item
                class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                        <button type="button" data-build-step-drag-handle
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                            aria-label="{{ __('Reorder step') }}">
                            <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                        </button>
                        <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                            <button type="button" data-duplicate-build-step
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                aria-label="{{ __('Duplicate step') }}">
                                <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                            </button>
                            <button type="button" data-remove-build-step
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                aria-label="{{ __('Remove step') }}">
                                <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" data-build-step-toggle aria-expanded="false"
                        class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right">
                        <div class="min-w-0 flex-1">
                            <p dir="auto" data-build-step-item-title
                                class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                {{ __('New Step') }}</p>
                            <p dir="auto" data-build-step-item-summary
                                class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                {{ __('Click to edit this step') }}</p>
                        </div>
                        <span
                            class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                            <i data-build-step-toggle-icon class="ti ti-chevron-down text-base leading-none"
                                aria-hidden="true"></i>
                        </span>
                    </button>
                </div>
                <div data-build-step-item-body class="mt-4 hidden space-y-4">
                    <div>
                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                            <label class="block text-sm font-medium text-slate-700">{{ __('Step Title') }}</label>
                            <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                        </div>
                        <input type="text"
                            name="translations[{{ $code }}][content][steps][__INDEX__][title]"
                            data-name-template="translations[{{ $code }}][content][steps][__INDEX__][title]"
                            data-build-step-field="title" value=""
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                            placeholder="{{ __('Example: Development') }}">
                        <p class="mt-2 text-xs text-slate-500">
                            {{ __('This text appears inside the process card in the timeline.') }}</p>
                    </div>
                    <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                        <div data-build-step-icon-preview
                            class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand">
                            <i class="ti ti-search text-2xl leading-none" aria-hidden="true"></i>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                            </div>
                            <select name="translations[{{ $code }}][content][steps][__INDEX__][icon_source]"
                                data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon_source]"
                                data-build-step-field="icon_source"
                                class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                <option value="class">{{ __('Tabler Icon') }}</option>
                                <option value="media">{{ __('SVG From Media') }}</option>
                            </select>
                            <div data-build-step-icon-panel="class" class="space-y-3">
                                <input type="text"
                                    name="translations[{{ $code }}][content][steps][__INDEX__][icon]"
                                    data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon]"
                                    data-build-step-field="icon" value=""
                                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="ti ti-search">
                                <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                    <button type="button" data-open-section-icon-library
                                        data-icon-input-selector='[data-build-step-field="icon"]'
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                        <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Choose From Icon Library') }}</span>
                                    </button>
                                </div>
                                <p class="text-xs text-slate-500">
                                    {{ __('Use the icon library or type a Tabler class manually.') }}</p>
                            </div>
                            <div data-build-step-icon-panel="media" class="space-y-2 hidden">
                                <input type="hidden"
                                    name="translations[{{ $code }}][content][steps][__INDEX__][icon_media]"
                                    data-name-template="translations[{{ $code }}][content][steps][__INDEX__][icon_media]"
                                    data-build-step-field="icon_media" value="">
                                <button type="button" data-build-step-icon-media-button
                                    class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                    <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                    <span>{{ __('Choose SVG From Media') }}</span>
                                </button>
                                <div data-build-step-icon-media-preview class="flex flex-wrap gap-2"></div>
                                <p class="text-xs text-slate-500">
                                    {{ __('Upload or choose an SVG file from the media library when you need a branded icon.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <label
                        class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 rtl:flex-row-reverse">
                        <input type="checkbox"
                            name="translations[{{ $code }}][content][steps][__INDEX__][is_accent]"
                            value="1"
                            data-name-template="translations[{{ $code }}][content][steps][__INDEX__][is_accent]"
                            data-build-step-field="accent" class="rounded border-slate-300">
                        <span>{{ __('Highlight this step in red') }}</span>
                    </label>
                </div>
            </article>
        </template>

        <div
            class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-50/80 px-4 py-3 text-xs text-slate-500 rtl:flex-row-reverse">
            <span>{{ __('Each step keeps its own icon and highlight state. Drag items to reorder them.') }}</span>
            <button type="button" data-add-build-step
                class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                <span>{{ __('Add Step') }}</span>
            </button>
        </div>
    </div>
</div>
