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

    $resolveMediaUrl = static function ($value): ?string {
        if (is_numeric($value)) {
            $media = \App\Models\Media::find((int) $value);

            return $media?->url ?? ($media?->file_url ?? null);
        }

        if (is_string($value) && $value !== '') {
            return \Illuminate\Support\Str::startsWith($value, ['http://', 'https://', '//', '/', 'data:'])
                ? $value
                : asset($value);
        }

        return null;
    };

    $sanitizeInlineSvg = static function ($value): ?string {
        $svg = trim((string) $value);

        if ($svg === '' || ! preg_match('/<svg\b/i', $svg)) {
            return null;
        }

        return $svg;
    };

    $steps = collect(is_array($content['steps'] ?? null) ? $content['steps'] : [])
        ->map(function ($step) use ($sanitizeIconClass, $resolveMediaUrl, $sanitizeInlineSvg): ?array {
            if (! is_array($step)) {
                return null;
            }

            $title = trim((string) ($step['title'] ?? $step['label'] ?? ''));
            if ($title === '') {
                return null;
            }

            $iconClass = $sanitizeIconClass($step['icon'] ?? null);
            $iconSvg = $sanitizeInlineSvg($step['icon_svg'] ?? null);
            $iconMediaUrl = $resolveMediaUrl($step['icon_media'] ?? null);
            $requestedSource = trim((string) ($step['icon_source'] ?? ''));
            $iconSource = in_array($requestedSource, ['class', 'svg', 'media'], true) ? $requestedSource : null;

            if ($iconSource === 'svg' && ! $iconSvg) {
                $iconSource = null;
            }

            if ($iconSource === 'media' && ! $iconMediaUrl) {
                $iconSource = null;
            }

            if ($iconSource === 'class' && ! $iconClass) {
                $iconSource = null;
            }

            if (! $iconSource) {
                $iconSource = $iconSvg
                    ? 'svg'
                    : ($iconMediaUrl ? 'media' : 'class');
            }

            return [
                'title' => $title,
                'icon_source' => $iconSource,
                'icon' => $iconClass,
                'icon_svg' => $iconSvg,
                'icon_media_url' => $iconMediaUrl,
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
                        @if (($step['icon_source'] ?? 'class') === 'svg' && ! empty($step['icon_svg']))
                            <span class="inline-flex h-7 w-7 items-center justify-center {{ $iconClasses }}" aria-hidden="true">
                                {!! $step['icon_svg'] !!}
                            </span>
                        @elseif (($step['icon_source'] ?? 'class') === 'media' && ! empty($step['icon_media_url']))
                            <span class="inline-flex h-7 w-7 items-center justify-center" aria-hidden="true">
                                <img src="{{ $step['icon_media_url'] }}" alt="" class="h-full w-full object-contain">
                            </span>
                        @elseif ($step['icon'])
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
