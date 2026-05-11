@props([
    'title' => __('No content yet'),
    'description' => __('Add content from the section editor.'),
])

<div {{ $attributes->merge([
    'class' => 'mx-auto flex w-full max-w-3xl flex-col items-center justify-center rounded-theme-xl border border-dashed border-theme-border bg-theme-surface px-6 py-10 text-center shadow-theme sm:px-8 sm:py-12',
]) }}>
    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-theme-xl border border-theme-border bg-theme-muted text-theme-heading">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h10M4 17h7" />
        </svg>
    </div>

    <h3 class="font-theme-heading text-xl font-bold text-theme-heading sm:text-2xl">
        {{ $title }}
    </h3>

    @if (trim((string) $description) !== '')
        <p class="mt-2 max-w-xl text-sm leading-relaxed text-theme-body sm:text-base">
            {{ $description }}
        </p>
    @endif
</div>
