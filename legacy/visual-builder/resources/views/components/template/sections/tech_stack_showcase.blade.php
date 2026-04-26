@php
    $style = is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'py-12';

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

    $logoValues = $content['logos'] ?? [];

    if (is_string($logoValues)) {
        $logoValues = array_values(array_filter(array_map('trim', explode(',', $logoValues))));
    } elseif (! is_array($logoValues)) {
        $logoValues = [];
    }

    $logos = collect($logoValues)
        ->map(function ($item) use ($resolveMediaUrl): ?array {
            $url = $resolveMediaUrl($item);

            if (! $url) {
                return null;
            }

            $alt = __('Technology');

            if (is_numeric($item)) {
                $media = \App\Models\Media::find((int) $item);
                $alt = $media?->file_original_name ?: ($media?->file_name ?: $alt);
            }

            return [
                'url' => $url,
                'alt' => $alt,
            ];
        })
        ->filter()
        ->values();

@endphp

<section id="tech-stack" class="{{ $paddingY }} relative bg-white px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        <div
            id="tech-stack-container"
            data-pg-tech-stack-track="1"
            dir="ltr"
            class="pg-drag-scroll scrollbar-hide flex select-none items-center justify-start overflow-x-auto pb-6 py-2"
        >
            @forelse ($logos as $logo)
                @if ($loop->first)
                    <div data-pg-tech-stack-segment="1" class="flex shrink-0 items-center justify-start gap-4 pe-4 lg:gap-6 lg:pe-6">
                @endif
                        <div class="group relative shrink-0 transition-all duration-300 hover:-translate-y-1">
                            <div class="absolute inset-0 z-10"></div>
                            <img src="{{ $logo['url'] }}" class="h-full w-full object-cover" alt="{{ $logo['alt'] }}" loading="lazy" draggable="false">
                        </div>
                @if ($loop->last)
                    </div>
                @endif
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-sm text-slate-500">
                    {{ __('Choose technology logos from the section editor.') }}
                </div>
            @endforelse
        </div>
    </div>
</section>
<script src="{{ asset('assets/tamplate/js/tech-stack-drag.js') }}?v={{ filemtime(public_path('assets/tamplate/js/tech-stack-drag.js')) }}" defer></script>
