@php
    $style = is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'py-16 lg:py-24';

    $brandPrefix = $content['brand_prefix'] ?? 'PAL';
    $brandSuffix = $content['brand_suffix'] ?? 'GOALS';
    $sectionTitle = $content['title'] ?? '';
    $sectionDescription = $content['description'] ?? '';

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

    $reviews = collect(is_array($content['reviews'] ?? null) ? $content['reviews'] : [])
        ->map(function ($review) use ($resolveMediaUrl): ?array {
            if (! is_array($review)) {
                return null;
            }

            $name = trim((string) ($review['name'] ?? ''));
            $text = trim((string) ($review['text'] ?? ''));

            if ($name === '' && $text === '') {
                return null;
            }

            $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
            $avatarUrl = $resolveMediaUrl($review['avatar'] ?? null);

            return [
                'name' => $name !== '' ? $name : __('Anonymous'),
                'text' => $text,
                'rating' => $rating,
                'avatar_url' => $avatarUrl,
            ];
        })
        ->filter()
        ->values();
@endphp

<section id="reviews" class="{{ $paddingY }} relative overflow-hidden bg-gray-50 px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        <div class="mb-12 text-center">
            <p class="text-lg md:text-xl">
                <span class="text-red-brand">{{ $brandPrefix }}</span><span class="text-purple-brand">{{ $brandSuffix }}</span>
            </p>

            @if ($sectionTitle)
                <h2 class="text-3xl font-extrabold uppercase leading-tight text-purple-brand md:text-[40px]">
                    {{ $sectionTitle }}
                </h2>
            @endif

            @if ($sectionDescription)
                <p class="mx-auto max-w-xl text-lg leading-relaxed text-gray-dark">
                    {{ $sectionDescription }}
                </p>
            @endif
        </div>

        <div id="reviews-slider" class="scrollbar-hide flex cursor-grab snap-x snap-mandatory select-none items-stretch gap-6 overflow-x-auto pb-12">
            @forelse ($reviews as $review)
                <div class="group w-[85vw] shrink-0 snap-start rounded-[32px] border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-lg md:w-[500px]">
                    <div class="mb-6 flex items-center gap-4">
                        <div class="relative h-24 w-24 flex-shrink-0 overflow-hidden rounded-full border-2 border-purple-brand/10">
                            <div class="absolute inset-0 z-10"></div>
                            @if ($review['avatar_url'])
                                <img src="{{ $review['avatar_url'] }}" class="h-full w-full object-cover" alt="{{ $review['name'] }}" loading="lazy">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-slate-100 text-2xl font-semibold text-slate-500">
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($review['name'], 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <div class="ltr:text-left rtl:text-right">
                            <h3 class="text-lg font-bold text-purple-brand md:text-xl">{{ $review['name'] }}</h3>
                            <div class="mt-1 flex items-center gap-1 ltr:flex-row rtl:flex-row-reverse">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="h-6 w-6" viewBox="0 0 27 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path
                                            d="M13.2738 20.2691L21.477 25.2202L19.3001 15.8887L26.5476 9.61022L17.0037 8.80052L13.2738 0L9.54385 8.80052L0 9.61022L7.24749 15.8887L5.07059 25.2202L13.2738 20.2691Z"
                                            fill="{{ $i <= $review['rating'] ? '#FFBC00' : '#A8A8A8' }}"
                                        />
                                    </svg>
                                @endfor
                            </div>
                        </div>
                    </div>

                    @if ($review['text'])
                        <p class="text-base leading-relaxed text-gray-dark md:text-xl">
                            {{ $review['text'] }}
                        </p>
                    @endif
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
                    {{ __('Add review cards from the section editor.') }}
                </div>
            @endforelse
        </div>

        @if ($reviews->count() > 1)
            <div class="mt-4 flex justify-center gap-2" id="reviews-indicators">
                @foreach ($reviews as $index => $review)
                    <span class="h-2.5 w-2.5 rounded-full {{ $index === 0 ? 'bg-purple-brand' : 'bg-slate-300' }}"></span>
                @endforeach
            </div>
        @endif
    </div>
</section>
