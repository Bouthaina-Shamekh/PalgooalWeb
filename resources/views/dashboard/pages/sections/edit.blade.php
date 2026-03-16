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
    @include('dashboard.pages.sections.partials.editor-form', [
        'formId' => 'section-edit-form',
    ])
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
            window.initSectionEditorTabs?.(document);
            window.initSectionFeatureRepeaters?.(document);
            window.initBuildStepRepeaters?.(document);
            window.initReviewRepeaters?.(document);
        });
    </script>
@endpush
