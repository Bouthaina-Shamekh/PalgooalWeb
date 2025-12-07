@php
    use Illuminate\Support\Arr;

    /**
     * Normalize $sectionTypes so that the KEY = type slug
     * ممكن تكون جاية من الكنترولر بمفاتيح رقمية، فنحوّلها لمفاتيح نصية
     * مثل: hero_default, hero_split ...
     */
    $normalizedTypes = collect($sectionTypes ?? [])->mapWithKeys(function ($meta, $key) {
        // نحاول نطلع type من الميتا، لو مش موجود نستخدم المفتاح نفسه
        $type = $meta['type'] ?? $meta['key'] ?? $key;

        return [
            $type => $meta + ['_type' => $type],
        ];
    });

    // نشتغل من الآن فصاعدًا على الشكل الموحّد
    $sectionTypes  = $normalizedTypes;
    $selectedType  = old('type', 'hero_default');

    // group by category (hero, features, ...), default = other
    $groupedTypes = $sectionTypes->groupBy(fn ($meta, $type) => $meta['category'] ?? 'other');
@endphp

<x-dashboard-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 px-4 py-6">
        {{-- Page header --}}
        <header class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ __('Add Section to Page') }}:
                    <span class="text-primary">
                        {{ $page->translation()?->title ?? $page->slug }}
                    </span>
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Page Builder v2 – Create a structured Hero section with multi-language support.') }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard.pages.sections.index', $page) }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs sm:text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                    ⬅ {{ __('Back to Sections') }}
                </a>
            </div>
        </header>

        <form method="POST" action="{{ route('dashboard.pages.sections.store', $page) }}" class="space-y-8">
            @csrf

            {{-- ───────────────── Basic Settings ───────────────── --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 sm:p-7">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ __('Basic Settings') }}
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('Choose the section type, display order, and visibility. These settings affect how the section appears on the page.') }}
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                        Hero · Default
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Left column: type + admin title --}}
                    <div class="lg:col-span-2 space-y-4">
                        {{-- Section type (visual picker) --}}
                        <div>
                            <label class="flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-200">
                                <span>{{ __('Section Type') }}</span>
                                <span class="text-[11px] text-gray-400">
                                    {{ __('You can add more types later (features, FAQ, etc.)') }}
                                </span>
                            </label>

                            <div class="space-y-3 mt-1">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                            {{ __('Choose a layout by category, then click on the preview card to select it.') }}
                                        </p>
                                    </div>
                                    <span id="selected-type-label"
                                          class="hidden sm:inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1 text-[11px] text-gray-600 dark:text-gray-300">
                                        {{ __('Selected') }}:
                                        {{ $sectionTypes[$selectedType]['label'] ?? $selectedType }}
                                    </span>
                                </div>

                                {{-- hidden input that holds the value --}}
                                <input type="hidden" name="type" id="section-type-input" value="{{ $selectedType }}">

                                {{-- Category tabs (hero, features, etc.) --}}
                                <div class="border-b border-gray-200 dark:border-gray-700 mb-3">
                                    <nav class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 text-xs sm:text-sm"
                                         role="tablist"
                                         aria-label="{{ __('Section categories') }}">
@foreach ($groupedTypes as $categoryKey => $items)
    <div class="js-section-cat-panel {{ !$loop->first && !collect($items)->keys()->contains($selectedType) ? 'hidden' : '' }}"
         data-category="{{ $categoryKey }}"
         id="section-cat-panel-{{ $categoryKey }}"
         role="tabpanel"
         aria-labelledby="section-cat-{{ $categoryKey }}">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($items as $type => $meta)
                @php
                    $isActive    = $selectedType === $type;
                    $previewPath = $meta['preview'] ?? null;
                @endphp

                <button type="button"
                        class="js-section-type-card group relative text-left rounded-xl border text-sm ... 
                               {{ $isActive ? 'border-primary ring-1 ring-primary/50' : 'border-gray-200 dark:border-gray-700' }}"
                        data-type="{{ $type }}">
                    {{-- Active badge --}}
                    @if ($isActive)
                        <span
                            class="absolute right-2 top-2 inline-flex items-center rounded-full bg-primary px-2 py-0.5 text-[11px] font-semibold text-white shadow">
                            ✓ {{ __('Selected') }}
                        </span>
                    @endif

                    {{-- Preview image --}}
                    @if ($previewPath)
                        <div class="aspect-video ...">
                            <img src="{{ asset($previewPath) }}"
                                 alt="{{ $meta['label'] ?? $type }}"
                                 class="h-full w-full object-cover group-hover:scale-[1.02] transition-transform"
                                 loading="lazy">
                        </div>
                    @endif

                    <div class="p-3 space-y-1.5">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $meta['label'] ?? $type }}
                            </h3>
                            <span class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                {{ $categoryKey }}
                            </span>
                        </div>
                        @if (!empty($meta['description'] ?? null))
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $meta['description'] }}
                            </p>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    </div>
@endforeach

                                    </nav>
                                </div>

                                {{-- Cards per category --}}
                                @foreach ($groupedTypes as $categoryKey => $items)
                                    @php
                                        $panelActive = $loop->first || collect($items)->keys()->contains($selectedType);
                                    @endphp

                                    <div
                                        class="js-section-cat-panel {{ $panelActive ? '' : 'hidden' }}"
                                        data-category="{{ $categoryKey }}"
                                        id="section-cat-panel-{{ $categoryKey }}"
                                        role="tabpanel"
                                        aria-labelledby="section-cat-{{ $categoryKey }}"
                                        aria-hidden="{{ $panelActive ? 'false' : 'true' }}"
                                    >
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                            @foreach ($items as $value => $meta)
                                                @php
                                                    $isActive = $selectedType === $value;
                                                    $previewPath = $meta['preview'] ?? null;
                                                @endphp

                                                <button
                                                    type="button"
                                                    class="js-section-type-card group relative text-left rounded-xl border text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary focus-visible:outline-offset-2
                                                    bg-white/80 dark:bg-gray-900/70 hover:bg-primary/5 dark:hover:bg-primary/10
                                                    transition shadow-sm hover:shadow-md
                                                    {{ $isActive ? 'border-primary ring-1 ring-primary/50' : 'border-gray-200 dark:border-gray-700' }}"
                                                    data-type="{{ $value }}"
                                                >
                                                    {{-- Active badge --}}
                                                    @if ($isActive)
                                                        <span
                                                            class="absolute right-2 top-2 inline-flex items-center rounded-full bg-primary px-2 py-0.5 text-[11px] font-semibold text-white shadow">
                                                            ✓ {{ __('Selected') }}
                                                        </span>
                                                    @endif

                                                    {{-- Preview image --}}
                                                    @if ($previewPath)
                                                        <div class="aspect-video w-full overflow-hidden rounded-t-xl border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
                                                            <img
                                                                src="{{ asset($previewPath) }}"
                                                                alt="{{ $meta['label'] ?? $value }}"
                                                                class="h-full w-full object-cover group-hover:scale-[1.02] transition-transform"
                                                                loading="lazy"
                                                            >
                                                        </div>
                                                    @endif

                                                    <div class="p-3 space-y-1.5">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                                                {{ $meta['label'] ?? $value }}
                                                            </h3>
                                                            <span class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                                                {{ $categoryKey }}
                                                            </span>
                                                        </div>

                                                        @if (!empty($meta['description'] ?? null))
                                                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                                                {{ $meta['description'] }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                                @error('type')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Admin-only title --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                {{ __('Section Title (Admin only – optional)') }}
                            </label>
                            <input
                                type="text"
                                name="admin_title"
                                value="{{ old('admin_title') }}"
                                placeholder="{{ __('Example: Home – Main Hero') }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                            >
                            <p class="mt-1 text-[11px] text-gray-400">
                                {{ __('This title helps you recognize the block in the panel, it is not shown to visitors.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Right column: order + active + variant --}}
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                {{ __('Display Order') }}
                            </label>
                            <input
                                type="number"
                                name="order"
                                value="{{ old('order', $nextOrder ?? 1) }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                            >
                            <p class="mt-1 text-[11px] text-gray-400">
                                {{ __('Lower numbers appear higher on the page.') }}
                            </p>
                            @error('order')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                {{ __('Variant (optional)') }}
                            </label>
                            <input
                                type="text"
                                name="variant"
                                value="{{ old('variant') }}"
                                placeholder="default / v2 / v3..."
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                            >
                            <p class="mt-1 text-[11px] text-gray-400">
                                {{ __('Use this if you have multiple design variations for the same section type.') }}
                            </p>
                            @error('variant')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-2">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    class="rounded border-gray-300 dark:border-gray-700 text-primary focus:ring-primary"
                                    {{ old('is_active', 1) ? 'checked' : '' }}
                                >
                                {{ __('Active (visible on the frontend)') }}
                            </label>
                            <p class="mt-1 text-[11px] text-gray-400">
                                {{ __('You can deactivate the section without deleting it.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ───────────────── Hero Content per Language ───────────────── --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 sm:p-7">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ __('Section Content (per language)') }}
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('Fill the hero content for each active language. Visitors will see the version that matches their language.') }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span
                            class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
                            {{ __('Languages') }}: {{ $languages->count() }}
                        </span>
                    </div>
                </div>

                {{-- Language tabs --}}
                <div class="border-b border-gray-200 dark:border-gray-700 mb-4 overflow-x-auto">
                    <nav class="-mb-px flex space-x-2 rtl:space-x-reverse min-w-max"
                         role="tablist"
                         aria-label="{{ __('Languages') }}">
                        @foreach ($languages as $index => $language)
                            <button
                                type="button"
                                class="tab-btn whitespace-nowrap px-3 py-2 text-xs sm:text-sm font-medium border-b-2 rounded-t-md
                                    {{ $index === 0
                                        ? 'border-primary text-primary bg-primary/5'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-300 dark:hover:text-white' }}"
                                id="tab-{{ $language->code }}"
                                data-tab="lang-{{ $language->code }}"
                                role="tab"
                                aria-controls="lang-{{ $language->code }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                tabindex="{{ $index === 0 ? '0' : '-1' }}"
                            >
                                {{ $language->name }} <span class="opacity-60">({{ $language->code }})</span>
                            </button>
                        @endforeach
                    </nav>
                </div>

                {{-- Panels per language --}}
                @foreach ($languages as $index => $language)
                    @php
                        $code = $language->code;
                    @endphp

                    <div
                        class="tab-panel {{ $index === 0 ? '' : 'hidden' }}"
                        id="lang-{{ $code }}"
                        role="tabpanel"
                        aria-labelledby="tab-{{ $code }}"
                        aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                    >
                        <div class="space-y-5">
                            {{-- hidden locale --}}
                            <input
                                type="hidden"
                                name="translations[{{ $code }}][locale]"
                                value="{{ $code }}"
                            >

                            {{-- Block: main text --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-900/40 p-4 sm:p-5 space-y-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('Main Text') }} ({{ $code }})
                                    </h3>
                                    <span class="text-[11px] text-gray-400">
                                        {{ __('Title & description of the hero.') }}
                                    </span>
                                </div>

                                {{-- Section title --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-200">
                                        {{ __('Section title (used in some designs)') }}
                                    </label>
                                    <input
                                        type="text"
                                        name="translations[{{ $code }}][title]"
                                        value="{{ old("translations.$code.title") }}"
                                        placeholder="{{ __('Example: Powerful hosting for your website') }}"
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                                    >
                                </div>

                                {{-- Hero title --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-200">
                                        {{ __('Hero Title (big main headline)') }}
                                    </label>
                                    <input
                                        type="text"
                                        name="translations[{{ $code }}][content][title]"
                                        value="{{ old("translations.$code.content.title") }}"
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                                    >
                                </div>

                                {{-- Eyebrow + subtitle --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-200">
                                            {{ __('Eyebrow (small label above title)') }}
                                        </label>
                                        <input
                                            type="text"
                                            name="translations[{{ $code }}][content][eyebrow]"
                                            value="{{ old("translations.$code.content.eyebrow") }}"
                                            placeholder="{{ __('Example: New – Palgoals Templates') }}"
                                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-200">
                                            {{ __('Subtitle') }}
                                        </label>
                                        <textarea
                                            name="translations[{{ $code }}][content][subtitle]"
                                            rows="2"
                                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                                        >{{ old("translations.$code.content.subtitle") }}</textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- Block: CTAs --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/40 dark:bg-gray-900/40 p-4 sm:p-5 space-y-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('Call To Actions') }}
                                    </h3>
                                    <span class="text-[11px] text-gray-400">
                                        {{ __('Buttons that appear under the hero text.') }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {{-- Primary --}}
                                    <div class="space-y-2">
                                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                            {{ __('Primary Button') }}
                                        </p>
                                        <input
                                            type="text"
                                            name="translations[{{ $code }}][content][primary_button][label]"
                                            value="{{ old("translations.$code.content.primary_button.label") }}"
                                            placeholder="{{ __('Example: Start now') }}"
                                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary mb-2"
                                        >
                                        <input
                                            type="text"
                                            name="translations[{{ $code }}][content][primary_button][url]"
                                            value="{{ old("translations.$code.content.primary_button.url") }}"
                                            placeholder="/checkout"
                                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                                        >
                                    </div>

                                    {{-- Secondary --}}
                                    <div class="space-y-2">
                                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                            {{ __('Secondary Button') }}
                                        </p>
                                        <input
                                            type="text"
                                            name="translations[{{ $code }}][content][secondary_button][label]"
                                            value="{{ old("translations.$code.content.secondary_button.label") }}"
                                            placeholder="{{ __('Example: View templates') }}"
                                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary mb-2"
                                        >
                                        <input
                                            type="text"
                                            name="translations[{{ $code }}][content][secondary_button][url]"
                                            value="{{ old("translations.$code.content.secondary_button.url") }}"
                                            placeholder="/templates"
                                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                                        >
                                    </div>
                                </div>
                            </div>

                            {{-- Block: Features bullets --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/40 dark:bg-gray-900/40 p-4 sm:p-5">
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('Highlights / Features') }}
                                    </h3>
                                    <span class="text-[11px] text-gray-400">
                                        {{ __('Short bullets under the buttons (optional).') }}
                                    </span>
                                </div>

                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-200 mb-1">
                                    {{ __('Features (each line = one bullet)') }}
                                </label>
                                <textarea
                                    name="translations[{{ $code }}][content][features_textarea]"
                                    rows="4"
                                    placeholder="{{ __('Fast setup in minutes') . "\n" . __('Free domain and SSL') . "\n" . __('Arabic support team') }}"
                                    class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                                >{{ old("translations.$code.content.features_textarea") }}</textarea>
                                <p class="mt-1 text-[11px] text-gray-400">
                                    {{ __('We will automatically convert each line into a feature item in the hero component.') }}
                                </p>
                            </div>

                            {{-- Block: Media --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/40 dark:bg-gray-900/40 p-4 sm:p-5">
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('Hero Media (optional)') }}
                                    </h3>
                                    <span class="text-[11px] text-gray-400">
                                        {{ __('Image or video on the right side of the hero.') }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-200">
                                            {{ __('Media Type') }}
                                        </label>
                                        @php
                                            $mediaTypeOld = old("translations.$code.content.media_type", 'image');
                                        @endphp
                                        <select
                                            name="translations[{{ $code }}][content][media_type]"
                                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                                        >
                                            <option value="image" {{ $mediaTypeOld === 'image' ? 'selected' : '' }}>Image</option>
                                            <option value="video" {{ $mediaTypeOld === 'video' ? 'selected' : '' }}>Video</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-200">
                                            {{ __('Media URL') }}
                                        </label>
                                        <input
                                            type="text"
                                            name="translations[{{ $code }}][content][media_url]"
                                            value="{{ old("translations.$code.content.media_url") }}"
                                            placeholder="https://…"
                                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary focus:border-primary"
                                        >
                                        <p class="mt-1 text-[11px] text-gray-400">
                                            {{ __('For images: direct image URL. For video: embed URL (YouTube, etc.).') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <p class="mt-4 text-[11px] text-gray-500 dark:text-gray-400">
                    {{ __('All fields inside "content" will be stored as JSON and rendered by the Hero Default front-end component.') }}
                </p>
            </div>

            {{-- ───────────────── Style Settings (global) ───────────────── --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 sm:p-7 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    {{ __('Style Settings') }}
                </h2>

                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    {{ __('These settings control the visual style of this section (background, alignment, spacing). They are shared across all languages.') }}
                </p>

                {{-- Background Color --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('Background Color (Tailwind class or HEX)') }}
                    </label>
                    <input
                        type="text"
                        name="style[background_color]"
                        value="{{ old('style.background_color', 'bg-background dark:bg-gray-950') }}"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm"
                        placeholder="مثال: bg-background أو #0f172a"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('You can use Tailwind classes like `bg-background` or a HEX color like `#0f172a`.') }}
                    </p>
                </div>

                {{-- Text Alignment --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('Text Alignment') }}
                    </label>
                    @php
                        $alignOld = old('style.text_align', 'text-center lg:text-left');
                    @endphp
                    <select
                        name="style[text_align]"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm"
                    >
                        <option value="text-center lg:text-left" {{ $alignOld === 'text-center lg:text-left' ? 'selected' : '' }}>
                            {{ __('Center on mobile, left on desktop') }}
                        </option>
                        <option value="text-center" {{ $alignOld === 'text-center' ? 'selected' : '' }}>
                            {{ __('Center (all breakpoints)') }}
                        </option>
                        <option value="rtl:text-right ltr:text-left" {{ $alignOld === 'rtl:text-right ltr:text-left' ? 'selected' : '' }}>
                            {{ __('Match language direction') }}
                        </option>
                    </select>
                </div>

                {{-- Vertical Padding --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('Vertical Padding (Y axis)') }}
                    </label>
                    <input
                        type="text"
                        name="style[padding_y]"
                        value="{{ old('style.padding_y', 'py-16 sm:py-20') }}"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm"
                        placeholder="مثال: py-16 sm:py-20"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Tailwind spacing utilities like `py-12`, `py-16 sm:py-24`, etc.') }}
                    </p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between">
                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                    {{ __('Tip: You can always edit this section later and adjust text, buttons, or media without affecting other sections.') }}
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary/90"
                >
                    💾 {{ __('Save Section') }}
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // ===== Language tabs =====
                const langButtons = Array.from(document.querySelectorAll('.tab-btn'));
                const langPanels = Array.from(document.querySelectorAll('.tab-panel'));

                const setActiveLang = (btn) => {
                    const target = btn.getAttribute('data-tab');

                    langButtons.forEach((b) => {
                        const isActive = b === btn;
                        b.classList.toggle('border-primary', isActive);
                        b.classList.toggle('text-primary', isActive);
                        b.classList.toggle('bg-primary/5', isActive);
                        b.classList.toggle('border-transparent', !isActive);
                        b.classList.toggle('text-gray-500', !isActive);
                        b.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        b.setAttribute('tabindex', isActive ? '0' : '-1');
                    });

                    langPanels.forEach((panel) => {
                        const isActive = panel.id === target;
                        panel.classList.toggle('hidden', !isActive);
                        panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                    });
                };

                const focusLangTab = (btn) => {
                    setActiveLang(btn);
                    btn.focus();
                };

                langButtons.forEach((btn) => {
                    btn.addEventListener('click', () => setActiveLang(btn));

                    btn.addEventListener('keydown', (event) => {
                        const { key } = event;
                        if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(key)) return;

                        event.preventDefault();
                        const currentIndex = langButtons.indexOf(btn);
                        if (currentIndex === -1) return;

                        if (key === 'Home') return focusLangTab(langButtons[0]);
                        if (key === 'End') return focusLangTab(langButtons[langButtons.length - 1]);

                        const delta = key === 'ArrowRight' ? 1 : -1;
                        const nextIndex = (currentIndex + delta + langButtons.length) % langButtons.length;
                        focusLangTab(langButtons[nextIndex]);
                    });
                });

                const initialLang = langButtons.find((b) => b.getAttribute('aria-selected') === 'true') || langButtons[0];
                if (initialLang) setActiveLang(initialLang);

                // ===== Section Type: category tabs =====
                const catTabs = Array.from(document.querySelectorAll('.js-section-cat-tab'));
                const catPanels = Array.from(document.querySelectorAll('.js-section-cat-panel'));

                const setActiveCategory = (tab) => {
                    const cat = tab.getAttribute('data-category');

                    catTabs.forEach((t) => {
                        const isActive = t === tab;
                        t.classList.toggle('border-primary', isActive);
                        t.classList.toggle('text-primary', isActive);
                        t.classList.toggle('bg-primary/5', isActive);
                        t.classList.toggle('border-transparent', !isActive);
                        t.classList.toggle('text-gray-500', !isActive);
                        t.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        t.setAttribute('tabindex', isActive ? '0' : '-1');
                    });

                    catPanels.forEach((panel) => {
                        const isActive = panel.getAttribute('data-category') === cat;
                        panel.classList.toggle('hidden', !isActive);
                        panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                    });
                };

                const focusCategoryTab = (tab) => {
                    setActiveCategory(tab);
                    tab.focus();
                };

                catTabs.forEach((tab) => {
                    tab.addEventListener('click', () => setActiveCategory(tab));

                    tab.addEventListener('keydown', (event) => {
                        const { key } = event;
                        if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(key)) return;

                        event.preventDefault();
                        const currentIndex = catTabs.indexOf(tab);
                        if (currentIndex === -1) return;

                        if (key === 'Home') return focusCategoryTab(catTabs[0]);
                        if (key === 'End') return focusCategoryTab(catTabs[catTabs.length - 1]);

                        const delta = key === 'ArrowRight' ? 1 : -1;
                        const nextIndex = (currentIndex + delta + catTabs.length) % catTabs.length;
                        focusCategoryTab(catTabs[nextIndex]);
                    });
                });

                const initialCat = catTabs.find((t) => t.getAttribute('aria-selected') === 'true') || catTabs[0];
                if (initialCat) setActiveCategory(initialCat);

                // ===== Section Type: card click =====
                const typeInput = document.getElementById('section-type-input');
                const typeCards = document.querySelectorAll('.js-section-type-card');
                const selectedLbl = document.getElementById('selected-type-label');

                typeCards.forEach(card => {
                    card.addEventListener('click', () => {
                        const type = card.getAttribute('data-type');

                        // update hidden input
                        if (typeInput) {
                            typeInput.value = type;
                        }

                        // reset states
                        typeCards.forEach(c => {
                            c.classList.remove('border-primary', 'ring-1', 'ring-primary/50');
                            c.classList.add('border-gray-200', 'dark:border-gray-700');

                            const badge = c.querySelector('.js-selected-badge');
                            if (badge) badge.remove();
                        });

                        // mark selected
                        card.classList.remove('border-gray-200', 'dark:border-gray-700');
                        card.classList.add('border-primary', 'ring-1', 'ring-primary/50');

                        // add small "selected" badge
                        const badge = document.createElement('span');
                        badge.className =
                            'js-selected-badge absolute right-2 top-2 inline-flex items-center rounded-full bg-primary px-2 py-0.5 text-[11px] font-semibold text-white shadow';
                        badge.textContent = '✓ {{ __('Selected') }}';
                        card.appendChild(badge);

                        // update small label text
                        if (selectedLbl) {
                            selectedLbl.classList.remove('hidden');
                            const title = card.querySelector('h3')?.textContent?.trim() || type;
                            selectedLbl.textContent = '‎{{ __('Selected') }}: ' + title;
                        }
                    });
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
