@php
    $pageTitle = $page->translation()?->title ?? $page->slug;
    $sectionTypeMeta = $sectionTypes[old('type', $section->type)] ?? ($sectionTypes[$section->type] ?? null);
    $sectionTypeLabel = $sectionTypeMeta['label'] ?? old('type', $section->type);
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
        form="section-edit-form"
        class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
    >
        {{ __('Update Section') }}
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
        id="section-edit-form"
        method="POST"
        action="{{ route('dashboard.pages.sections.update', [$page, $section]) }}"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Section Settings') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Update the type, order, variant, and visibility for this section.') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-2 lg:p-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Section Type') }}</label>
                    <select
                        name="type"
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                    >
                        @foreach($sectionTypes as $value => $meta)
                            <option value="{{ $value }}" {{ old('type', $section->type) === $value ? 'selected' : '' }}>
                                {{ $meta['label'] ?? $value }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Variant') }}</label>
                    <input
                        type="text"
                        name="variant"
                        value="{{ old('variant', $section->variant) }}"
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                        placeholder="default / minimal / v2"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">{{ __('Display Order') }}</label>
                    <input
                        type="number"
                        name="order"
                        value="{{ old('order', $section->order ?? 1) }}"
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
                            {{ old('is_active', $section->is_active) ? 'checked' : '' }}
                        >
                        {{ __('Active on frontend') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Section Content') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Edit localized content for each language.') }}</p>
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
                        $translation = $section->translations->firstWhere('locale', $code);
                        $content = $translation?->content ?? [];

                        $featuresTextarea = old("translations.$code.content.features_textarea");

                        if ($featuresTextarea === null) {
                            if (!empty($content['features']) && is_array($content['features'])) {
                                $featuresTextarea = implode("\n", $content['features']);
                            } else {
                                $featuresTextarea = '';
                            }
                        }

                        $mediaTypeOld = old("translations.$code.content.media_type", $content['media_type'] ?? 'image');
                    @endphp

                    <div class="tab-panel {{ $index === 0 ? '' : 'hidden' }}" id="lang-{{ $code }}">
                        <input type="hidden" name="translations[{{ $code }}][locale]" value="{{ $code }}">

                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Section Title') }} ({{ $code }})</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][title]"
                                    value="{{ old("translations.$code.title", $translation->title ?? '') }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Eyebrow') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][eyebrow]"
                                    value="{{ old("translations.$code.content.eyebrow", $content['eyebrow'] ?? '') }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Hero Title') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][title]"
                                    value="{{ old("translations.$code.content.title", $content['title'] ?? '') }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Subtitle') }}</label>
                                <textarea
                                    name="translations[{{ $code }}][content][subtitle]"
                                    rows="4"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >{{ old("translations.$code.content.subtitle", $content['subtitle'] ?? '') }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Primary Button Label') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][primary_button][label]"
                                    value="{{ old("translations.$code.content.primary_button.label", $content['primary_button']['label'] ?? '') }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Primary Button URL') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][primary_button][url]"
                                    value="{{ old("translations.$code.content.primary_button.url", $content['primary_button']['url'] ?? '') }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Secondary Button Label') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][secondary_button][label]"
                                    value="{{ old("translations.$code.content.secondary_button.label", $content['secondary_button']['label'] ?? '') }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">{{ __('Secondary Button URL') }}</label>
                                <input
                                    type="text"
                                    name="translations[{{ $code }}][content][secondary_button][url]"
                                    value="{{ old("translations.$code.content.secondary_button.url", $content['secondary_button']['url'] ?? '') }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>

                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">{{ __('Features (each line = one bullet)') }}</label>
                                <textarea
                                    name="translations[{{ $code }}][content][features_textarea]"
                                    rows="5"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >{{ $featuresTextarea }}</textarea>
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
                                    value="{{ old("translations.$code.content.media_url", $content['media_url'] ?? '') }}"
                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                                >
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </form>
@endsection

@section('workspace-sidebar')
    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
        <h3 class="text-base font-semibold text-slate-900">{{ __('Editing Summary') }}</h3>
        <div class="mt-4 space-y-3 text-sm text-slate-600">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Page') }}</p>
                <p class="mt-2 font-medium text-slate-900">{{ $pageTitle }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Section Type') }}</p>
                <p class="mt-2 font-medium text-slate-900">{{ $sectionTypeLabel }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Current Order') }}</p>
                <p class="mt-2 font-medium text-slate-900">{{ $section->order ?? 1 }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Status') }}</p>
                <p class="mt-2 font-medium {{ $section->is_active ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $section->is_active ? __('Active on frontend') : __('Hidden on frontend') }}
                </p>
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
                    <span class="block text-xs text-slate-500">{{ __('Switch to the visual page builder.') }}</span>
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
        });
    </script>
@endpush
