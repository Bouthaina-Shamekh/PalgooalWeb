@php
    $subtitle = data_get($content, 'subtitle');
    $buttonLabel = data_get($content, 'button_label', 'Reserve a Table');
    $buttonUrl = data_get($content, 'button_url', '#');
    $image = data_get($content, 'image');
@endphp
<section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-orange-500 to-red-500 text-white">
    @if ($image)
        <img src="{{ $image }}" alt="hero" class="absolute inset-0 w-full h-full object-cover opacity-25 pointer-events-none">
    @endif
    <div class="relative z-10 px-8 py-14 flex flex-col gap-4">
        <p class="text-sm uppercase tracking-[0.3em] text-white/80">{{ __('Restaurant') }}</p>
        <h2 class="text-4xl font-extrabold leading-tight">{{ $translation->title ?? __('A story of flavors') }}</h2>
        @if ($subtitle)
            <p class="text-lg text-white/90 max-w-2xl">{{ $subtitle }}</p>
        @endif
        <div class="mt-6">
            <a href="{{ $buttonUrl }}" class="inline-flex items-center gap-2 bg-white text-orange-600 font-semibold px-5 py-2 rounded-full shadow hover:bg-white/90">
                {{ $buttonLabel }}
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
</section>