@php
    $data = $data ?? [];

    $brandPrefix = $data['brand_prefix'] ?? 'PAL';
    $brandSuffix = $data['brand_suffix'] ?? 'GOALS';
    $sectionTitle = $data['title'] ?? '';
    $sectionDescription = $data['description'] ?? '';
    $visitLabel = $data['visit_label'] ?? __('Visit');

    $resolveImageUrl = static function ($value): ?string {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (\Illuminate\Support\Str::startsWith($value, ['http://', 'https://', '//', '/', 'data:'])) {
            return $value;
        }

        return \Illuminate\Support\Str::startsWith($value, 'storage/')
            ? asset($value)
            : asset('storage/' . ltrim($value, '/'));
    };

    $works = collect($data['portfolios'] ?? [])
        ->map(function ($portfolio) use ($resolveImageUrl): ?array {
            if (! $portfolio) {
                return null;
            }

            $translation = $portfolio->translations->firstWhere('locale', app()->getLocale())
                ?? $portfolio->translations->first();

            $title = trim((string) ($translation?->title ?? ''));
            if ($title === '') {
                $title = __('Project');
            }

            $meta = trim((string) ($translation?->type ?? ''));
            $externalLink = trim((string) ($translation?->link ?? ''));
            $href = $externalLink !== ''
                ? $externalLink
                : route('portfolio.show', $portfolio->slug ?: $portfolio->id);

            return [
                'title' => $title,
                'meta' => $meta,
                'href' => $href,
                'external' => $externalLink !== '',
                'image_url' => $resolveImageUrl($portfolio->default_image),
            ];
        })
        ->filter()
        ->values();
@endphp

<section id="our-work" class="relative overflow-hidden bg-white px-4 py-16 sm:px-6 lg:px-12 lg:py-24">
    <div class="container mx-auto">
        <div class="mb-6 text-center">
            <p class="text-lg md:text-xl">
                <span class="text-red-brand">{{ $brandPrefix }}</span><span class="text-purple-brand">{{ $brandSuffix }}</span>
            </p>

            @if ($sectionTitle)
                <h2 class="text-3xl font-extrabold uppercase leading-tight text-purple-brand md:text-[40px]">
                    {{ $sectionTitle }}
                </h2>
            @endif

            @if ($sectionDescription)
                <p class="mx-auto max-w-xl text-lg leading-relaxed text-gray-dark md:text-xl">
                    {{ $sectionDescription }}
                </p>
            @endif
        </div>

        <div
            id="our-work-slider"
            class="scrollbar-hide flex cursor-grab snap-x snap-mandatory select-none items-stretch gap-6 overflow-x-auto pb-12 transition-all duration-300 md:px-0"
        >
            @forelse ($works as $work)
                <div class="group first:ml-0 w-[80vw] shrink-0 snap-start rounded-[32px] bg-gray-100 p-4 shadow-sm ring-1 ring-gray-200/50 transition-all duration-300 hover:shadow-lg md:first:ml-0 md:w-[350px] lg:w-[400px]">
                    <div class="relative h-56 w-full overflow-hidden rounded-[24px] shadow-sm lg:h-64">
                        <div class="absolute inset-0 z-10 bg-black/5 transition-colors duration-300 group-hover:bg-transparent"></div>

                        @if ($work['image_url'])
                            <img
                                src="{{ $work['image_url'] }}"
                                class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                                alt="{{ $work['title'] }}"
                                loading="lazy"
                            >
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-slate-200 text-sm font-medium text-slate-500">
                                {{ __('Project image') }}
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between px-2 pb-2 pt-5">
                        <div class="text-left ltr:text-left rtl:text-right">
                            <h3 class="mb-1 text-xl font-bold text-purple-brand transition-colors group-hover:text-red-brand md:text-2xl">
                                {{ $work['title'] }}
                            </h3>

                            @if ($work['meta'] !== '')
                                <p class="text-sm font-medium text-gray-500 md:text-base">{{ $work['meta'] }}</p>
                            @endif
                        </div>

                        <a
                            href="{{ $work['href'] }}"
                            @if ($work['external']) target="_blank" rel="noopener" @endif
                            class="whitespace-nowrap rounded-xl bg-white px-10 py-3 text-base text-purple-brand shadow-sm transition-all duration-300 hover:text-red-brand hover:shadow-md"
                        >
                            {{ $visitLabel }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-slate-500">
                    {{ __('No portfolio items available yet.') }}
                </div>
            @endforelse
        </div>

        @if ($works->count() > 1)
            <div class="mt-4 flex justify-center gap-2" id="our-work-indicators">
                @foreach ($works as $index => $work)
                    <span class="h-2.5 w-2.5 rounded-full {{ $index === 0 ? 'bg-purple-brand' : 'bg-slate-300' }}"></span>
                @endforeach
            </div>
        @endif
    </div>
</section>
