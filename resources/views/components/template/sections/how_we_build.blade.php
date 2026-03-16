@php
    $style = is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'py-16 lg:py-24';

    $sectionTitle = $content['title'] ?? '';
    $sectionSubtitle = $content['subtitle'] ?? '';

    $sanitizeIconClass = static function ($value): ?string {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $value) ?? '';
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $value !== '' ? $value : null;
    };

    $steps = collect(is_array($content['steps'] ?? null) ? $content['steps'] : [])
        ->map(function ($step) use ($sanitizeIconClass): ?array {
            if (! is_array($step)) {
                return null;
            }

            $title = trim((string) ($step['title'] ?? $step['label'] ?? ''));
            if ($title === '') {
                return null;
            }

            return [
                'title' => $title,
                'icon' => $sanitizeIconClass($step['icon'] ?? null),
                'is_accent' => (bool) ($step['is_accent'] ?? false),
            ];
        })
        ->filter()
        ->values();
@endphp

<section id="how-we-build" class="{{ $paddingY }} overflow-hidden bg-purple-brand px-4 text-center text-white sm:px-6 lg:px-12">
    <div class="container mx-auto">
        <div class="mb-16">
            @if ($sectionTitle)
                <h2 class="mb-2 text-2xl font-extrabold md:text-3xl">{{ $sectionTitle }}</h2>
            @endif

            @if ($sectionSubtitle)
                <p class="text-lg font-light text-gray-300 opacity-90 md:text-xl">{{ $sectionSubtitle }}</p>
            @endif
        </div>

        @if ($steps->isNotEmpty())
            <div class="relative mx-auto grid max-w-7xl grid-cols-1 gap-12 md:grid-cols-5 md:gap-10">
                @foreach ($steps as $step)
                    @php
                        $isAccent = $step['is_accent'];
                        $cardClasses = $isAccent
                            ? 'bg-red-brand text-white z-20'
                            : 'bg-white text-purple-brand z-10';
                        $iconClasses = $isAccent ? 'text-white' : 'text-red-brand';
                    @endphp

                    <div class="relative flex min-h-[7.5rem] flex-col items-center justify-center gap-3 rounded-2xl p-6 shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl {{ $cardClasses }}">
                        @if ($step['icon'])
                            <i class="{{ $step['icon'] }} {{ $iconClasses }} text-2xl leading-none" aria-hidden="true"></i>
                        @else
                            <i class="ti ti-check {{ $iconClasses }} text-2xl leading-none" aria-hidden="true"></i>
                        @endif

                        <span class="text-base">{{ $step['title'] }}</span>

                        @if (! $loop->last)
                            <div class="pointer-events-none absolute bottom-[-1rem] left-1/2 h-8 w-8 -translate-x-1/2 rotate-45 transform md:bottom-auto md:top-1/2 md:-translate-y-1/2 ltr:md:left-auto ltr:md:right-[-1rem] ltr:md:translate-x-0 rtl:md:left-[-1rem] rtl:md:right-auto rtl:md:translate-x-0 {{ $isAccent ? 'bg-red-brand' : 'bg-white' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
