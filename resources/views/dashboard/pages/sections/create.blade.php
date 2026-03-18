@php
    $normalizedTypes = collect($sectionTypes ?? [])->mapWithKeys(function ($meta, $key) {
        $type = $meta['type'] ?? $meta['key'] ?? $key;

        return [
            $type => $meta + ['_type' => $type],
        ];
    });

    $sectionTypes = $normalizedTypes;
    $selectedType = old('type', 'hero_default');
    $sectionTypeMeta = $sectionTypes[$selectedType] ?? null;
    $sectionTypeLabel = $sectionTypeMeta['label'] ?? $selectedType;
    $pageTitle = $page->translation()?->title ?? $page->slug;
    $groupedTypes = $sectionTypes->groupBy(fn ($meta, $type) => $meta['category'] ?? 'other');
@endphp

@extends('dashboard.pages.sections.layouts.workspace')

@section('workspace-header-actions')
    <a
        href="{{ route('dashboard.pages.sections.index', $page) }}"
        class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:shadow-sm"
    >
        {{ __('Back to Sections') }}
    </a>

    <button
        type="submit"
        form="section-create-form"
        class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
    >
        {{ __('Create Section') }}
    </button>
@endsection

@section('workspace-main')
    @if ($errors->any())
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        id="section-create-form"
        method="POST"
        action="{{ route('dashboard.pages.sections.store', $page, false) }}"
        class="space-y-6"
    >
        @csrf

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Section Type') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Pick a block type first. This advanced form is best when you already know what you want to create.') }}</p>
            </div>

            <div class="space-y-6 p-5 lg:p-6">
                <input type="hidden" name="type" id="section-type-input" value="{{ $selectedType }}">

                <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Selected Type') }}</p>
                        <p id="selected-type-label" class="mt-1 text-base font-semibold text-slate-900">{{ $sectionTypeLabel }}</p>
                    </div>
                    <span id="selected-type-category" class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600">
                        {{ \Illuminate\Support\Str::headline($sectionTypeMeta['category'] ?? 'other') }}
                    </span>
                </div>

                <div class="space-y-5">
                    @foreach ($groupedTypes as $category => $items)
                        <section>
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">
                                    {{ \Illuminate\Support\Str::headline($category) }}
                                </h3>
                                <span class="text-xs text-slate-400">{{ count($items) }} {{ __('types') }}</span>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($items as $type => $meta)
                                    @php
                                        $isActive = $selectedType === $type;
                                    @endphp
                                    <button
                                        type="button"
                                        class="js-section-type-card group overflow-hidden rounded-3xl border bg-white text-left transition hover:-translate-y-0.5 hover:shadow-md {{ $isActive ? 'border-slate-900 ring-1 ring-slate-900/10' : 'border-slate-200' }}"
                                        data-type="{{ $type }}"
                                        data-label="{{ $meta['label'] ?? $type }}"
                                        data-category="{{ $meta['category'] ?? 'other' }}"
                                    >
                                        @if (! empty($meta['preview']))
                                            <div class="aspect-[16/10] overflow-hidden bg-slate-100">
                                                <img
                                                    src="{{ asset($meta['preview']) }}"
                                                    alt="{{ $meta['label'] ?? $type }}"
                                                    class="h-full w-full object-cover transition duration-200 group-hover:scale-[1.02]"
                                                    loading="lazy"
                                                >
                                            </div>
                                        @endif

                                        <div class="p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-slate-900">{{ $meta['label'] ?? $type }}</h4>
                                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $meta['description'] ?? __('No description provided.') }}</p>
                                                </div>

                                                @if ($isActive)
                                                    <span class="rounded-full bg-slate-900 px-2.5 py-1 text-[11px] font-semibold text-white">
                                                        {{ __('Selected') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Section Settings') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Set the order, visibility, and optional variant before saving.') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-3 lg:p-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Display Order') }}</label>
                    <input
                        type="number"
                        name="order"
                        value="{{ old('order', $nextOrder ?? 1) }}"
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Variant') }}</label>
                    <input
                        type="text"
                        name="variant"
                        value="{{ old('variant') }}"
                        placeholder="default / minimal / v2"
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    >
                </div>

                <div class="flex items-center">
                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            class="rounded border-slate-300"
                            {{ old('is_active', 1) ? 'checked' : '' }}
                        >
                        {{ __('Active on frontend') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Section Content') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Fill localized content for each language. This form is optimized for hero-style content.') }}</p>
            </div>

            <div class="p-5 lg:p-6">
                <div class="mb-5 border-b border-slate-200">
                    <nav class="-mb-px flex flex-wrap gap-2" aria-label="Language tabs">
                        @foreach($languages as $index => $language)
                            @php
                                $active = $index === 0;
                            @endphp
                            <button
                                type="button"
                                class="tab-btn rounded-t-2xl border-b-2 px-4 py-2 text-sm font-medium transition {{ $active ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}"
                                data-tab="lang-{{ $language->code }}"
                            >
                                {{ $language->name }} ({{ $language->code }})
                            </button>
                        @endforeach
                    </nav>
                </div>

                @foreach($languages as $index => $language)
                    @php
                        $code = $language->code;
                        $mediaTypeOld = old("translations.$code.content.media_type", 'image');
                    @endphp

                    <div class="tab-panel {{ $index === 0 ? '' : 'hidden' }}" id="lang-{{ $code }}">
                        <input type="hidden" name="translations[{{ $code }}][locale]" value="{{ $code }}">

                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Section Title') }} ({{ $code }})</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][title]"
                                    value="{{ old("translations.$code.title") }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Eyebrow') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][eyebrow]"
                                    value="{{ old("translations.$code.content.eyebrow") }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Hero Title') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][title]"
                                    value="{{ old("translations.$code.content.title") }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Subtitle') }}</label>
                                <textarea
                                    name="translations[{{ $code }}][content][subtitle]"
                                    rows="4"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >{{ old("translations.$code.content.subtitle") }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Primary Button Label') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][primary_button][label]"
                                    value="{{ old("translations.$code.content.primary_button.label") }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Primary Button URL') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][primary_button][url]"
                                    value="{{ old("translations.$code.content.primary_button.url") }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Secondary Button Label') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][secondary_button][label]"
                                    value="{{ old("translations.$code.content.secondary_button.label") }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Secondary Button URL') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][secondary_button][url]"
                                    value="{{ old("translations.$code.content.secondary_button.url") }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Features (each line = one bullet)') }}</label>
                                <textarea
                                    name="translations[{{ $code }}][content][features_textarea]"
                                    rows="5"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >{{ old("translations.$code.content.features_textarea") }}</textarea>
                                <p class="mt-2 text-xs text-slate-500">{{ __('Each line will be converted to a feature item.') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Media Type') }}</label>
                                <select
                                    name="translations[{{ $code }}][content][media_type]"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                                    <option value="image" {{ $mediaTypeOld === 'image' ? 'selected' : '' }}>Image</option>
                                    <option value="video" {{ $mediaTypeOld === 'video' ? 'selected' : '' }}>Video</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Media URL') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][media_url]"
                                    value="{{ old("translations.$code.content.media_url") }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Style Settings') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Optional shared styling for the section across all locales.') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-3 lg:p-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Background Color / Class') }}</label>
                    <input
                        type="text"
                        name="style[background_color]"
                        value="{{ old('style.background_color', 'bg-background dark:bg-gray-950') }}"
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Text Alignment') }}</label>
                    @php
                        $alignOld = old('style.text_align', 'text-center lg:text-left');
                    @endphp
                    <select
                        name="style[text_align]"
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    >
                        <option value="text-center lg:text-left" {{ $alignOld === 'text-center lg:text-left' ? 'selected' : '' }}>{{ __('Center on mobile, left on desktop') }}</option>
                        <option value="text-center" {{ $alignOld === 'text-center' ? 'selected' : '' }}>{{ __('Center everywhere') }}</option>
                        <option value="rtl:text-right ltr:text-left" {{ $alignOld === 'rtl:text-right ltr:text-left' ? 'selected' : '' }}>{{ __('Match language direction') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Vertical Padding') }}</label>
                    <input
                        type="text"
                        name="style[padding_y]"
                        value="{{ old('style.padding_y', 'py-16 sm:py-20') }}"
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    >
                </div>
            </div>
        </div>
    </form>
@endsection

@section('workspace-sidebar')
    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
        <h3 class="text-base font-semibold text-slate-900">{{ __('Create Summary') }}</h3>
        <div class="mt-4 space-y-3 text-sm text-slate-600">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Page') }}</p>
                <p class="mt-2 font-medium text-slate-900">{{ $pageTitle }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Selected Type') }}</p>
                <p id="sidebar-selected-type" class="mt-2 font-medium text-slate-900">{{ $sectionTypeLabel }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Suggested Order') }}</p>
                <p class="mt-2 font-medium text-slate-900">{{ old('order', $nextOrder ?? 1) }}</p>
            </div>
        </div>
    </div>

    <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 p-5">
        <h3 class="text-base font-semibold text-slate-900">{{ __('Good Defaults') }}</h3>
        <div class="mt-4 space-y-4 text-sm text-slate-600">
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">1</span>
                <p>{{ __('Choose the type first. The selected card updates the form target immediately.') }}</p>
            </div>
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">2</span>
                <p>{{ __('Fill at least one localized title and main content before saving.') }}</p>
            </div>
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">3</span>
                <p>{{ __('If you only need a quick draft, the section library inside the workspace is usually faster.') }}</p>
            </div>
        </div>
    </div>

    <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 p-5">
        <h3 class="text-base font-semibold text-slate-900">{{ __('Quick Links') }}</h3>
        <div class="mt-4 space-y-3">
            <a
                href="{{ route('dashboard.pages.sections.index', $page) }}"
                class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left transition hover:bg-slate-50"
            >
                <span>
                    <span class="block text-sm font-semibold text-slate-900">{{ __('Back to Sections') }}</span>
                    <span class="block text-xs text-slate-500">{{ __('Return to the workspace outline.') }}</span>
                </span>
                <span class="text-sm font-semibold text-slate-500">{{ __('Open') }}</span>
            </a>

            <a
                href="{{ route('dashboard.pages.builder', $page) }}"
                class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left transition hover:bg-slate-50"
            >
                <span>
                    <span class="block text-sm font-semibold text-slate-900">{{ __('Visual Builder') }}</span>
                    <span class="block text-xs text-slate-500">{{ __('Open the visual builder for this page.') }}</span>
                </span>
                <span class="text-sm font-semibold text-slate-500">{{ __('Open') }}</span>
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.tab-btn');
            const panels = document.querySelectorAll('.tab-panel');
            const typeInput = document.getElementById('section-type-input');
            const typeCards = document.querySelectorAll('.js-section-type-card');
            const selectedTypeLabel = document.getElementById('selected-type-label');
            const sidebarSelectedType = document.getElementById('sidebar-selected-type');
            const selectedTypeCategory = document.getElementById('selected-type-category');

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const target = btn.getAttribute('data-tab');

                    buttons.forEach((button) => {
                        button.classList.remove('border-slate-900', 'text-slate-900');
                        button.classList.add('border-transparent', 'text-slate-500');
                    });

                    btn.classList.add('border-slate-900', 'text-slate-900');
                    btn.classList.remove('border-transparent', 'text-slate-500');

                    panels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.id !== target);
                    });
                });
            });

            typeCards.forEach((card) => {
                card.addEventListener('click', () => {
                    const type = card.getAttribute('data-type') || '';
                    const label = card.getAttribute('data-label') || type;
                    const category = card.getAttribute('data-category') || 'other';

                    if (typeInput) typeInput.value = type;
                    if (selectedTypeLabel) selectedTypeLabel.textContent = label;
                    if (sidebarSelectedType) sidebarSelectedType.textContent = label;
                    if (selectedTypeCategory) selectedTypeCategory.textContent = category.replace(/[_-]+/g, ' ');

                    typeCards.forEach((item) => {
                        item.classList.remove('border-slate-900', 'ring-1', 'ring-slate-900/10');
                        item.classList.add('border-slate-200');
                    });

                    card.classList.remove('border-slate-200');
                    card.classList.add('border-slate-900', 'ring-1', 'ring-slate-900/10');
                });
            });
        });
    </script>
@endpush
