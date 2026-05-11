@php
    $sectionId = trim((string) ($data['section_id'] ?? 'tech-stack'));

    $backgroundClass = match ($data['background_variant'] ?? 'white') {
        'gray' => 'bg-theme-muted',
        'primary', 'purple' => 'bg-theme-primary',
        'secondary', 'red' => 'bg-theme-secondary',
        'dark' => 'bg-gray-950',
        'transparent' => 'bg-transparent',
        default => 'bg-theme-surface',
    };

    $items = collect(is_array($data['items'] ?? null) ? $data['items'] : [])
        ->map(function ($item) {
            if (! is_array($item)) {
                return null;
            }

            $imageUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($item['image'] ?? null);

            if (! $imageUrl) {
                return null;
            }

            return [
                'image_url' => $imageUrl,
                'alt' => trim((string) ($item['alt'] ?? '')),
                'url' => trim((string) ($item['url'] ?? '')),
                'new_tab' => filter_var($item['new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        })
        ->filter()
        ->values();

    $hasContent = $items->isNotEmpty();
@endphp

<section id="{{ $sectionId }}" class="{{ $backgroundClass }} font-theme-body py-12 relative px-4 sm:px-6 lg:px-12 overflow-hidden">
    <div class="container mx-auto">
        @if (! $hasContent)
            <x-front.empty-section-state
                title="{{ __('No technology logos yet') }}"
                description="{{ __('Add logo items from the section editor.') }}"
            />
        @else
            <div
                id="{{ $sectionId }}-container"
                class="flex items-center justify-start overflow-x-auto py-2 pb-6 gap-4 lg:gap-6 cursor-grab scrollbar-hide select-none"
            >
                @foreach ($items as $item)
                    @if ($item['url'] !== '')
                        <a
                            href="{{ $item['url'] }}"
                            @if ($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif
                            class="shrink-0 hover:-translate-y-1 transition-all duration-300 group relative"
                            aria-label="{{ $item['alt'] ?: 'Technology logo' }}"
                        >
                            <span class="absolute inset-0 z-10" aria-hidden="true"></span>

                            <img
                                src="{{ $item['image_url'] }}"
                                class="w-full h-full object-cover"
                                alt="{{ $item['alt'] ?: 'Technology logo' }}"
                                loading="lazy"
                                draggable="false"
                            >
                        </a>
                    @else
                        <div class="shrink-0 hover:-translate-y-1 transition-all duration-300 group relative">
                            <span class="absolute inset-0 z-10" aria-hidden="true"></span>

                            <img
                                src="{{ $item['image_url'] }}"
                                class="w-full h-full object-cover"
                                alt="{{ $item['alt'] ?: 'Technology logo' }}"
                                loading="lazy"
                                draggable="false"
                            >
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>

@if ($hasContent)
    @once
        @push('scripts')
            <script
                src="{{ asset('assets/tamplate/js/tech-stack-drag.js') }}?v={{ filemtime(public_path('assets/tamplate/js/tech-stack-drag.js')) }}"
                defer
            ></script>
        @endpush
    @endonce
@endif
