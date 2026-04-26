@php
    $features = collect($data['features'] ?? [])
        ->filter(function ($item) {
            if (!is_array($item)) {
                return false;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            $icon = trim((string) ($item['icon'] ?? ''));

            return $title !== '' || $description !== '' || $icon !== '';
        })
        ->values();
@endphp

{{-- Tailwind safelist: bg-white dark:bg-gray-900 text-primary --}}
<section class="bg-background dark:bg-gray-950 py-20 px-4 sm:px-8 lg:px-24" dir="auto" aria-labelledby="features-three-heading">
    <div class="max-w-6xl mx-auto text-center mb-12">
        <h2 id="features-three-heading" class="text-3xl lg:text-4xl font-extrabold text-primary mb-4">
            {{ $data['title'] ?? __('Key Advantages') }}
        </h2>
        @if (!empty($data['subtitle']))
            <p class="text-base text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                {{ $data['subtitle'] }}
            </p>
        @endif
    </div>

    <div class="grid gap-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 max-w-6xl mx-auto">
        @forelse ($features as $index => $feature)
            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow hover:shadow-lg transition flex flex-col h-full">
                <div class="flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 text-primary mb-4 shrink-0">
                    @if (!empty($feature['icon']))
                        {!! $feature['icon'] !!}
                    @else
                        <span class="text-lg font-semibold">{{ $loop->iteration }}</span>
                    @endif
                </div>
                <h3 class="text-xl font-semibold text-primary mb-2">
                    {{ $feature['title'] ?? __('Feature Title') }}
                </h3>
                @if (!empty($feature['description']))
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $feature['description'] }}
                    </p>
                @endif
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-3 text-center text-gray-500 dark:text-gray-400">
                {{ __('No features configured yet.') }}
            </div>
        @endforelse
    </div>
</section>
