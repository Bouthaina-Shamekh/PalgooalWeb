@php
    // Auto-generated scaffold: hero — 2026-06-19
    // $data contains all field values (shared + translatable merged).

    $eyebrow = trim((string) ($data['eyebrow'] ?? '')); // text / trans
    $title = trim((string) ($data['title'] ?? '')); // text / trans
    $subtitle = (string) ($data['subtitle'] ?? ''); // textarea / trans
    $button_label = trim((string) ($data['button_label'] ?? '')); // text / trans
    $button_url = trim((string) ($data['button_url'] ?? '')); // url / trans
    $button_target = trim((string) ($data['button_target'] ?? '')); // select / shared
    $image = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null); // media / shared
    $image_alt = trim((string) ($data['image_alt'] ?? '')); // text / trans
    $image_position = trim((string) ($data['image_position'] ?? '')); // select / shared
@endphp

<section class="section-hero">
    <div class="container">

        {{-- Intro --}}
        @if ($eyebrow)
            <span class="section-eyebrow">{{ $eyebrow }}</span>
        @endif
        @if ($title)
            <h2 class="section-title">{{ $title }}</h2>
        @endif
        @if ($subtitle)
            <div class="section-subtitle">{{ $subtitle }}</div>
        @endif

        {{-- CTA Button --}}
        @if ($button_label)
            <p class="button_label">{{ $button_label }}</p>
        @endif
        @if ($button_url)
            <a href="{{ $button_url }}"
               target="{{ $button_target ?: '_self' }}"
               class="btn btn-primary">
                {{ $button_label }}
            </a>
        @endif
        @if ($button_target)
            <span class="button_target">{{ $button_target }}</span>
        @endif

        {{-- Image --}}
        @if ($image)
            <img src="{{ $image }}"
                 alt="{{ $data['image_alt'] ?? '' }}"
                 class="image">
        @endif
        @if ($image_alt)
            <p class="image_alt">{{ $image_alt }}</p>
        @endif
        @if ($image_position)
            <span class="image_position">{{ $image_position }}</span>
        @endif

    </div>
</section>