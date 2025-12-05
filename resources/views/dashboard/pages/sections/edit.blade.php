<x-dashboard-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 px-4 py-6">
        <header class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('Edit Section') }}: {{ $page->translation()?->title ?? $page->slug }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Page Builder v2 – {{ $sectionTypes[$section->type]['label'] ?? $section->type }}
            </p>
        </header>

        <form method="POST"
              action="{{ route('dashboard.pages.sections.update', [$page, $section]) }}"
              class="space-y-8">
            @csrf
            @method('PUT')

            {{-- Basic settings --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('Basic Settings') }}
                </h2>

                {{-- Section type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('Section Type') }}
                    </label>
                    <select name="type"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                        @foreach($sectionTypes as $value => $meta)
                            <option value="{{ $value }}"
                                {{ old('type', $section->type) === $value ? 'selected' : '' }}>
                                {{ $meta['label'] ?? $value }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Order & Active --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('Display Order') }}
                        </label>
                        <input type="number"
                               name="order"
                               value="{{ old('order', $section->order) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                        @error('order')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('Variant (optional)') }}
                        </label>
                        <input type="text"
                               name="variant"
                               value="{{ old('variant', $section->variant) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                        @error('variant')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   class="rounded border-gray-300 dark:border-gray-700"
                                   {{ old('is_active', $section->is_active) ? 'checked' : '' }}>
                            {{ __('Active (visible on the frontend)') }}
                        </label>
                    </div>
                </div>
            </div>

            {{-- Section Content (per language) --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('Section Content (per language)') }}
                </h2>

                {{-- Tabs --}}
                <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                    <nav class="-mb-px flex space-x-4 rtl:space-x-reverse">
                        @foreach($languages as $index => $language)
                            <button type="button"
                                    class="tab-btn px-3 py-2 text-sm font-medium border-b-2
                                           {{ $index === 0
                                                ? 'border-primary text-primary'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-300 dark:hover:text-white' }}"
                                    data-tab="lang-{{ $language->code }}">
                                {{ $language->name }} ({{ $language->code }})
                            </button>
                        @endforeach
                    </nav>
                </div>

                {{-- Panels --}}
                @foreach($languages as $index => $language)
                    @php
                        $code = $language->code;
                        $translation = $section->translations->firstWhere('locale', $code);
                        $content = $translation?->content ?? [];

                        // تحويل features[] إلى textarea لعرضها
                        $featuresTextarea = old("translations.$code.content.features_textarea")
                            ?? implode("\n", $content['features'] ?? []);
                    @endphp

                    <div class="tab-panel {{ $index === 0 ? '' : 'hidden' }}" id="lang-{{ $code }}">
                        <input type="hidden" name="translations[{{ $code }}][locale]" value="{{ $code }}">

                        {{-- Section title --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                {{ __('Section title') }} ({{ $code }})
                            </label>
                            <input type="text"
                                   name="translations[{{ $code }}][title]"
                                   value="{{ old("translations.$code.title", $translation?->title) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                        </div>

                        @if($section->type === 'hero_default')
                            {{-- Hero Default structured fields --}}

                            {{-- Eyebrow --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ __('Eyebrow (small label above title)') }}
                                </label>
                                <input type="text"
                                       name="translations[{{ $code }}][content][eyebrow]"
                                       value="{{ old("translations.$code.content.eyebrow", $content['eyebrow'] ?? '') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                            </div>

                            {{-- Hero Title --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ __('Hero Title') }}
                                </label>
                                <input type="text"
                                       name="translations[{{ $code }}][content][title]"
                                       value="{{ old("translations.$code.content.title", $content['title'] ?? '') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                            </div>

                            {{-- Subtitle --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ __('Subtitle') }}
                                </label>
                                <textarea
                                    name="translations[{{ $code }}][content][subtitle]"
                                    rows="3"
                                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm"
                                >{{ old("translations.$code.content.subtitle", $content['subtitle'] ?? '') }}</textarea>
                            </div>

                            {{-- Primary button --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        {{ __('Primary Button Label') }}
                                    </label>
                                    <input type="text"
                                           name="translations[{{ $code }}][content][primary_button][label]"
                                           value="{{ old("translations.$code.content.primary_button.label", $content['primary_button']['label'] ?? '') }}"
                                           class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        {{ __('Primary Button URL') }}
                                    </label>
                                    <input type="text"
                                           name="translations[{{ $code }}][content][primary_button][url]"
                                           value="{{ old("translations.$code.content.primary_button.url", $content['primary_button']['url'] ?? '') }}"
                                           class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                                </div>
                            </div>

                            {{-- Secondary button --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        {{ __('Secondary Button Label') }}
                                    </label>
                                    <input type="text"
                                           name="translations[{{ $code }}][content][secondary_button][label]"
                                           value="{{ old("translations.$code.content.secondary_button.label", $content['secondary_button']['label'] ?? '') }}"
                                           class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        {{ __('Secondary Button URL') }}
                                    </label>
                                    <input type="text"
                                           name="translations[{{ $code }}][content][secondary_button][url]"
                                           value="{{ old("translations.$code.content.secondary_button.url", $content['secondary_button']['url'] ?? '') }}"
                                           class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                                </div>
                            </div>

                            {{-- Features --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ __('Features (each line = one bullet)') }}
                                </label>
                                <textarea
                                    name="translations[{{ $code }}][content][features_textarea]"
                                    rows="4"
                                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm"
                                >{{ $featuresTextarea }}</textarea>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Each line will be converted to a feature item.') }}
                                </p>
                            </div>

                            {{-- Media --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        {{ __('Media Type') }}
                                    </label>
                                    @php
                                        $mediaTypeOld = old("translations.$code.content.media_type", $content['media_type'] ?? 'image');
                                    @endphp
                                    <select
                                        name="translations[{{ $code }}][content][media_type]"
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm"
                                    >
                                        <option value="image" {{ $mediaTypeOld === 'image' ? 'selected' : '' }}>Image</option>
                                        <option value="video" {{ $mediaTypeOld === 'video' ? 'selected' : '' }}>Video</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        {{ __('Media URL') }}
                                    </label>
                                    <input type="text"
                                           name="translations[{{ $code }}][content][media_url]"
                                           value="{{ old("translations.$code.content.media_url", $content['media_url'] ?? '') }}"
                                           class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                                </div>
                            </div>
                        @else
                            {{-- Fallback: old generic body field --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ __('Body / main text') }} ({{ $code }})
                                </label>
                                <textarea
                                    name="translations[{{ $code }}][content][body]"
                                    rows="4"
                                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm"
                                >{{ old("translations.$code.content.body", $content['body'] ?? '') }}</textarea>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('This will be saved inside the JSON "content" field as the body key.') }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard.pages.sections.index', $page) }}"
                   class="text-sm text-gray-600 dark:text-gray-300 hover:underline">
                    {{ __('Back to Sections') }}
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary/90">
                    {{ __('Update Section') }}
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const buttons = document.querySelectorAll('.tab-btn');
                const panels  = document.querySelectorAll('.tab-panel');

                buttons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const target = btn.getAttribute('data-tab');

                        buttons.forEach(b => b.classList.remove('border-primary','text-primary'));
                        buttons.forEach(b => b.classList.add('border-transparent','text-gray-500'));

                        btn.classList.add('border-primary','text-primary');

                        panels.forEach(panel => {
                            panel.classList.toggle('hidden', panel.id !== target);
                        });
                    });
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
