@php
    $title = trim((string) ($data['title'] ?? '')) ?: __('Ready to launch your next project?');
    $subtitle = trim((string) ($data['subtitle'] ?? ''));
    $badge = trim((string) ($data['badge'] ?? ''));

    $primaryText = trim((string) ($data['primary_button_text'] ?? ''));
    $primaryUrl = trim((string) ($data['primary_button_url'] ?? ''));
@endphp

<section class="bg-primary text-white py-20 px-4 sm:px-8 lg:px-24" dir="rtl" aria-labelledby="cta-heading">
    <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center justify-between gap-10 text-center md:text-right">
        <div class="max-w-xl space-y-5">
            @if ($badge !== '')
                <span class="inline-flex items-center justify-center rounded-full bg-white/15 text-xs font-semibold tracking-widest px-4 py-1">
                    {{ $badge }}
                </span>
            @endif

            <h2 id="cta-heading" class="text-3xl md:text-4xl font-extrabold leading-relaxed drop-shadow-md">
                {{ $title }}
            </h2>

            @if ($subtitle !== '')
                <p class="text-white/90 text-lg font-light leading-relaxed">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if ($primaryText !== '')
            <div>
                <a href="{{ $primaryUrl !== '' ? $primaryUrl : '#' }}"
                   class="inline-block bg-secondary hover:bg-white hover:text-primary transition font-semibold text-sm sm:text-base px-8 py-3 rounded-lg shadow-md border-2 border-secondary">
                    {{ $primaryText }}
                </a>
            </div>
        @endif
    </div>
</section>
