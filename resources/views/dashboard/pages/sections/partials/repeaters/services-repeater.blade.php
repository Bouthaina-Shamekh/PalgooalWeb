<div class="lg:col-span-2">
    <div data-service-repeater data-service-item-label="{{ __('Service') }}"
        data-service-item-hint="{{ __('Click to edit this service') }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">{{ __('Services List') }}</label>
                <p class="mt-1 text-xs text-slate-500">
                    {{ __('Create the services as individual items to keep the section organized.') }}
                </p>
            </div>
            <button type="button" data-add-service-item
                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                <span>{{ __('Add Service') }}</span>
            </button>
        </div>

        <div class="mt-3">
            <div class="space-y-3" data-service-items>
                @foreach ($serviceItems as $serviceIndex => $serviceItem)
                    <article data-service-item
                        class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                <button type="button" data-service-drag-handle
                                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                    aria-label="{{ __('Reorder service') }}">
                                    <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                                </button>
                                <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                    <button type="button" data-duplicate-service-item
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                        aria-label="{{ __('Duplicate service') }}">
                                        <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" data-remove-service-item
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                        aria-label="{{ __('Remove service') }}">
                                        <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="button" data-service-toggle
                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right">
                                <div class="min-w-0 flex-1">
                                    <p dir="auto" data-service-item-title
                                        class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                        {{ filled($serviceItem['text'] ?? '') ? $serviceItem['text'] : __('Service') . ' ' . ($serviceIndex + 1) }}
                                    </p>
                                    <p dir="auto" data-service-item-summary
                                        class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                        {{ __('Visible in the services list') }}</p>
                                </div>
                                <span
                                    class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                    <i data-service-toggle-icon
                                        class="ti ti-chevron-down text-base leading-none {{ $loop->first ? 'rotate-180' : '' }}"
                                        aria-hidden="true"></i>
                                </span>
                            </button>
                        </div>

                        @php
                            $serviceMediaPreviewUrls = $mediaPreviewBuilder->build($serviceItem['icon_media'] ?? null);
                        @endphp
                        <div data-service-item-body class="mt-4 space-y-4 {{ $loop->first ? '' : 'hidden' }}">
                            <div>
                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <label
                                        class="block text-sm font-medium text-slate-700">{{ __('Service Text') }}</label>
                                    <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                                </div>
                                <input type="text"
                                    name="translations[{{ $code }}][content][services][{{ $serviceIndex }}][text]"
                                    data-name-template="translations[{{ $code }}][content][services][__INDEX__][text]"
                                    data-service-field="text" value="{{ $serviceItem['text'] ?? '' }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                    placeholder="{{ __('Example: UI/UX') }}">
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ __('This text appears as one item in the services list.') }}</p>
                            </div>

                            <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                                <div data-service-icon-preview
                                    class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand">
                                    @if (($serviceItem['icon_source'] ?? 'class') === 'media' && !empty($serviceMediaPreviewUrls))
                                        <img src="{{ $serviceMediaPreviewUrls[0] }}" alt=""
                                            class="h-full w-full object-contain">
                                    @elseif (!empty($serviceItem['icon']))
                                        <i class="{{ $serviceItem['icon'] }} text-xl leading-none"
                                            aria-hidden="true"></i>
                                    @else
                                        <svg width="10" height="13" viewBox="0 0 10 13" fill="none"
                                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M9.75 6.49512L0 12.9903V-7.34329e-05L9.75 6.49512Z"
                                                fill="#BA112C" />
                                        </svg>
                                    @endif
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                        <label
                                            class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                        <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                    </div>
                                    <select
                                        name="translations[{{ $code }}][content][services][{{ $serviceIndex }}][icon_source]"
                                        data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon_source]"
                                        data-service-field="icon_source"
                                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                        <option value="class" @selected(($serviceItem['icon_source'] ?? 'class') === 'class')>{{ __('Tabler Icon') }}
                                        </option>
                                        <option value="media" @selected(($serviceItem['icon_source'] ?? 'class') === 'media')>{{ __('SVG From Media') }}
                                        </option>
                                    </select>

                                    <div data-service-icon-panel="class" class="space-y-3">
                                        <input type="text"
                                            name="translations[{{ $code }}][content][services][{{ $serviceIndex }}][icon]"
                                            data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon]"
                                            data-service-field="icon" value="{{ $serviceItem['icon'] ?? '' }}"
                                            class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                            placeholder="ti ti-layout-grid">
                                        <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                            <button type="button" data-open-section-icon-library
                                                data-icon-input-selector='[data-service-field="icon"]'
                                                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                                <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                                <span>{{ __('Choose From Icon Library') }}</span>
                                            </button>
                                        </div>
                                        <p class="text-xs text-slate-500">
                                            {{ __('Use the icon library or type a Tabler class manually.') }}</p>
                                    </div>

                                    <div data-service-icon-panel="media" class="space-y-2 hidden">
                                        <input type="hidden"
                                            name="translations[{{ $code }}][content][services][{{ $serviceIndex }}][icon_media]"
                                            data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon_media]"
                                            data-service-field="icon_media"
                                            value="{{ $serviceItem['icon_media'] ?? '' }}">
                                        <button type="button" data-service-icon-media-button
                                            class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                            <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                            <span>{{ __('Choose SVG From Media') }}</span>
                                        </button>
                                        <div data-service-icon-media-preview class="flex flex-wrap gap-2">
                                            @foreach ($serviceMediaPreviewUrls as $url)
                                                <div
                                                    class="relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                                    <img src="{{ $url }}" alt=""
                                                        class="h-full w-full object-contain p-2">
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="text-xs text-slate-500">
                                            {{ __('Upload or choose an SVG file from the media library when you need a branded service icon.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div data-service-empty
                class="{{ count($serviceItems) ? 'hidden ' : '' }}mt-3 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-6 text-center text-sm text-slate-500">
                {{ __('No services yet. Add the first one to build the list.') }}
            </div>

            <template data-service-item-template>
                <article data-service-item
                    class="overflow-hidden rounded-[1.75rem] bg-white p-4 shadow-[0_18px_38px_-30px_rgba(15,23,42,0.28),0_8px_18px_rgba(15,23,42,0.05)]">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                            <button type="button" data-service-drag-handle
                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-slate-300 hover:text-slate-600"
                                aria-label="{{ __('Reorder service') }}">
                                <i class="ti ti-grip-vertical text-lg leading-none" aria-hidden="true"></i>
                            </button>
                            <div class="flex shrink-0 items-center gap-2 rtl:flex-row-reverse">
                                <button type="button" data-duplicate-service-item
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                                    aria-label="{{ __('Duplicate service') }}">
                                    <i class="ti ti-copy text-base leading-none" aria-hidden="true"></i>
                                </button>
                                <button type="button" data-remove-service-item
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                    aria-label="{{ __('Remove service') }}">
                                    <i class="ti ti-trash text-base leading-none" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" data-service-toggle aria-expanded="false"
                            class="flex w-full min-w-0 items-start justify-between gap-3 rounded-2xl bg-slate-50/80 px-3 py-3 text-left transition hover:bg-slate-100 rtl:flex-row-reverse rtl:text-right">
                            <div class="min-w-0 flex-1">
                                <p dir="auto" data-service-item-title
                                    class="text-sm font-semibold leading-5 text-slate-900 break-words">
                                    {{ __('New Service') }}</p>
                                <p dir="auto" data-service-item-summary
                                    class="mt-1 text-xs leading-5 text-slate-500 break-words">
                                    {{ __('Click to edit this service') }}</p>
                            </div>
                            <span
                                class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500">
                                <i data-service-toggle-icon class="ti ti-chevron-down text-base leading-none"
                                    aria-hidden="true"></i>
                            </span>
                        </button>
                    </div>

                    <div data-service-item-body class="mt-4 hidden space-y-4">
                        <div>
                            <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                <label
                                    class="block text-sm font-medium text-slate-700">{{ __('Service Text') }}</label>
                                <span class="text-xs text-slate-400">{{ __('Visible on the page') }}</span>
                            </div>
                            <input type="text"
                                name="translations[{{ $code }}][content][services][__INDEX__][text]"
                                data-name-template="translations[{{ $code }}][content][services][__INDEX__][text]"
                                data-service-field="text" value=""
                                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                placeholder="{{ __('Example: UI/UX') }}">
                            <p class="mt-2 text-xs text-slate-500">
                                {{ __('This text appears as one item in the services list.') }}</p>
                        </div>

                        <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-3">
                            <div data-service-icon-preview
                                class="sections-editor-icon-preview flex h-14 w-14 items-center justify-center rounded-2xl border border-red-brand/15 bg-red-brand/5 text-red-brand">
                                <svg width="10" height="13" viewBox="0 0 10 13" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M9.75 6.49512L0 12.9903V-7.34329e-05L9.75 6.49512Z" fill="#BA112C" />
                                </svg>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                                    <label
                                        class="block text-sm font-medium text-slate-700">{{ __('Icon') }}</label>
                                    <span class="text-xs text-slate-400">{{ __('Source') }}</span>
                                </div>
                                <select
                                    name="translations[{{ $code }}][content][services][__INDEX__][icon_source]"
                                    data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon_source]"
                                    data-service-field="icon_source"
                                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
                                    <option value="class">{{ __('Tabler Icon') }}</option>
                                    <option value="media">{{ __('SVG From Media') }}</option>
                                </select>
                                <div data-service-icon-panel="class" class="space-y-3">
                                    <input type="text"
                                        name="translations[{{ $code }}][content][services][__INDEX__][icon]"
                                        data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon]"
                                        data-service-field="icon" value=""
                                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                        placeholder="ti ti-layout-grid">
                                    <div class="flex flex-wrap items-center gap-2 rtl:flex-row-reverse">
                                        <button type="button" data-open-section-icon-library
                                            data-icon-input-selector='[data-service-field="icon"]'
                                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                            <i class="ti ti-icons text-base leading-none" aria-hidden="true"></i>
                                            <span>{{ __('Choose From Icon Library') }}</span>
                                        </button>
                                    </div>
                                    <p class="text-xs text-slate-500">
                                        {{ __('Use the icon library or type a Tabler class manually.') }}</p>
                                </div>
                                <div data-service-icon-panel="media" class="space-y-2 hidden">
                                    <input type="hidden"
                                        name="translations[{{ $code }}][content][services][__INDEX__][icon_media]"
                                        data-name-template="translations[{{ $code }}][content][services][__INDEX__][icon_media]"
                                        data-service-field="icon_media" value="">
                                    <button type="button" data-service-icon-media-button
                                        class="btn-open-media-picker inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white rtl:flex-row-reverse">
                                        <i class="ti ti-photo text-base leading-none" aria-hidden="true"></i>
                                        <span>{{ __('Choose SVG From Media') }}</span>
                                    </button>
                                    <div data-service-icon-media-preview class="flex flex-wrap gap-2"></div>
                                    <p class="text-xs text-slate-500">
                                        {{ __('Upload or choose an SVG file from the media library when you need a branded service icon.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </template>

            <div
                class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-50/80 px-4 py-3 text-xs text-slate-500 rtl:flex-row-reverse">
                <span>{{ __('Each service stays as an individual list item. Drag items to reorder them.') }}</span>
                <button type="button" data-add-service-item
                    class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <i class="ti ti-plus text-base leading-none" aria-hidden="true"></i>
                    <span>{{ __('Add Service') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
