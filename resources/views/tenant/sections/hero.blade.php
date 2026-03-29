{{--
Expected content JSON:
{
  "eyebrow": "SaaS landing template",
  "subtitle": "Short supporting paragraph",
  "primary_button": { "label": "Start now", "url": "#cta", "new_tab": false },
  "secondary_button": { "label": "See features", "url": "#features", "new_tab": false },
  "button_label": "Legacy fallback",
  "button_url": "#cta",
  "highlights": ["Fast setup", "RTL ready"],
  "stats": [
    { "value": "24/7", "label": "Ready to publish" }
  ],
  "image": "https://example.com/hero.jpg"
}
--}}
@php
    $eyebrow = trim((string) data_get($content, 'eyebrow', __('SaaS landing template')));
    $title = trim((string) (data_get($content, 'title') ?? $translation->title ?? __('Build a landing page that explains your offer clearly')));
    $subtitle = trim((string) data_get($content, 'subtitle', ''));

    $primaryButton = data_get($content, 'primary_button', []);
    $primaryLabel = trim((string) data_get($primaryButton, 'label', data_get($content, 'button_label', __('Start now'))));
    $primaryUrl = trim((string) data_get($primaryButton, 'url', data_get($content, 'button_url', '#cta')));
    $primaryNewTab = (bool) data_get($primaryButton, 'new_tab', false);

    $secondaryButton = data_get($content, 'secondary_button', []);
    $secondaryLabel = trim((string) data_get($secondaryButton, 'label', __('See features')));
    $secondaryUrl = trim((string) data_get($secondaryButton, 'url', '#features'));
    $secondaryNewTab = (bool) data_get($secondaryButton, 'new_tab', false);

    $highlights = collect(data_get($content, 'highlights', data_get($content, 'features', [])))
        ->map(fn ($item) => is_array($item) ? trim((string) ($item['label'] ?? $item['title'] ?? '')) : trim((string) $item))
        ->filter()
        ->values();

    $stats = collect(data_get($content, 'stats', []))
        ->filter(fn ($item) => is_array($item))
        ->map(function (array $item) {
            return [
                'value' => trim((string) ($item['value'] ?? '')),
                'label' => trim((string) ($item['label'] ?? '')),
            ];
        })
        ->filter(fn (array $item) => $item['value'] !== '' || $item['label'] !== '')
        ->values();

    $image = trim((string) data_get($content, 'image', data_get($content, 'media_url', '')));
    $imageUrl = $image === ''
        ? null
        : (\Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '//']) ? $image : asset(ltrim($image, '/')));
@endphp

<section class="relative isolate overflow-hidden bg-[#240B36] text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.22),_transparent_35%),radial-gradient(circle_at_bottom_left,_rgba(245,158,11,0.22),_transparent_30%)]"></div>

    <div class="relative mx-auto grid max-w-7xl gap-10 px-6 py-16 sm:px-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(18rem,0.95fr)] lg:px-12 lg:py-24">
        <div class="flex flex-col justify-center gap-8 text-start">
            <div class="space-y-5">
                @if ($eyebrow !== '')
                    <span class="inline-flex w-fit items-center rounded-full border border-white/15 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-white/80">
                        {{ $eyebrow }}
                    </span>
                @endif

                <div class="space-y-4">
                    <h1 class="max-w-3xl text-4xl font-semibold leading-tight text-white sm:text-5xl lg:text-6xl">
                        {{ $title }}
                    </h1>

                    @if ($subtitle !== '')
                        <p class="max-w-2xl text-base leading-8 text-white/75 sm:text-lg">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>
            </div>

            @if ($highlights->isNotEmpty())
                <div class="flex flex-wrap gap-3 rtl:flex-row-reverse">
                    @foreach ($highlights as $item)
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/12 bg-white/8 px-4 py-2 text-sm text-white/80 rtl:flex-row-reverse">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            <span>{{ $item }}</span>
                        </span>
                    @endforeach
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-3 rtl:flex-row-reverse">
                @if ($primaryLabel !== '')
                    <a href="{{ $primaryUrl !== '' ? $primaryUrl : '#cta' }}"
                        @if ($primaryNewTab) target="_blank" rel="noopener" @endif
                        class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-[#240B36] shadow-sm transition hover:bg-white/90">
                        {{ $primaryLabel }}
                    </a>
                @endif

                @if ($secondaryLabel !== '')
                    <a href="{{ $secondaryUrl !== '' ? $secondaryUrl : '#features' }}"
                        @if ($secondaryNewTab) target="_blank" rel="noopener" @endif
                        class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        {{ $secondaryLabel }}
                    </a>
                @endif
            </div>

            @if ($stats->isNotEmpty())
                <div class="grid gap-4 border-t border-white/10 pt-6 sm:grid-cols-3">
                    @foreach ($stats as $item)
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4">
                            @if ($item['value'] !== '')
                                <p class="text-2xl font-semibold text-white">{{ $item['value'] }}</p>
                            @endif
                            @if ($item['label'] !== '')
                                <p class="mt-1 text-sm leading-6 text-white/70">{{ $item['label'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="relative flex items-center justify-center lg:justify-end">
            <div class="absolute inset-x-8 top-6 h-40 rounded-full bg-amber-300/20 blur-3xl"></div>

            <div class="relative w-full max-w-xl overflow-hidden rounded-[2rem] border border-white/10 bg-white/10 p-3 shadow-2xl backdrop-blur">
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $title }}" class="h-[24rem] w-full rounded-[1.6rem] object-cover sm:h-[32rem]">
                @else
                    <div class="flex h-[24rem] w-full flex-col justify-between rounded-[1.6rem] bg-gradient-to-br from-white/10 via-white/5 to-transparent p-6 sm:h-[32rem]">
                        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
                            <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white/80">
                                {{ __('Conversion-ready layout') }}
                            </span>
                            <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
                        </div>

                        <div class="space-y-4 text-start">
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-5">
                                <p class="text-sm text-white/70">{{ __('Headline') }}</p>
                                <p class="mt-2 text-2xl font-semibold text-white">
                                    {{ __('Explain your offer in one clear statement') }}
                                </p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
                                    <p class="text-xs uppercase tracking-[0.22em] text-white/55">{{ __('Trust') }}</p>
                                    <p class="mt-2 text-sm text-white/80">{{ __('Add proof, testimonials, and simple next steps.') }}</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
                                    <p class="text-xs uppercase tracking-[0.22em] text-white/55">{{ __('Action') }}</p>
                                    <p class="mt-2 text-sm text-white/80">{{ __('Guide visitors toward one primary action.') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
