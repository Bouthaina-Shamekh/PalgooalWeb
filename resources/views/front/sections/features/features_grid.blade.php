@php
    // Auto-generated scaffold: features_grid — 2026-06-19
    // $data contains all field values (shared + translatable merged).

    $eyebrow = trim((string) ($data['eyebrow'] ?? '')); // text / trans
    $title = trim((string) ($data['title'] ?? '')); // text / trans
    $subtitle = (string) ($data['subtitle'] ?? ''); // textarea / trans
    $features = is_array($data['features'] ?? null) ? $data['features'] : []; // repeater
@endphp

<section class="section-features_grid">
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

        {{-- features / repeater --}}
        @if (!empty($features))
            <div class="features-list">
                @foreach ($features as $feature)
                    <div class="features-item">
                        <span>{{ $feature['title'] ?? '' }}</span>
                        <span>{{ $feature['description'] ?? '' }}</span>
                        <span>{{ $feature['icon_source'] ?? '' }}</span>
                        @if (!empty($feature['icon']))
                            <i class="{{ $feature['icon'] ?? '' }}"></i>
                        @endif
                        @if (!empty($feature['icon_media']))
                            <img src="{{ $feature['icon_media'] ?? '' }}" alt="">
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</section>