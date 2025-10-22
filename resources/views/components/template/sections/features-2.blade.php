<section class="bg-white dark:bg-gray-950 py-20 px-4 sm:px-8 lg:px-24" dir="auto" aria-labelledby="features-two-heading">
    <div class="max-w-6xl mx-auto text-center mb-16">
        <h2 id="features-two-heading" class="text-3xl lg:text-4xl font-extrabold text-primary dark:text-white mb-4">
            {{ $data['title'] ?? 'مميزات متقدمة' }}
        </h2>
        @if (!empty($data['subtitle']))
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                {{ $data['subtitle'] }}
            </p>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 text-center">
        @forelse ($data['features'] ?? [] as $feature)
            <div class="bg-background dark:bg-gray-900 rounded-xl p-8 shadow-lg hover:shadow-xl hover:-translate-y-2 transition-all duration-300 flex flex-col items-center h-full">
                <div class="w-20 h-20 mb-6 flex items-center justify-center">
                    @if (!empty($feature['icon']))
                        {!! $feature['icon'] !!}
                    @else
                        <span class="w-16 h-16 flex items-center justify-center rounded-full bg-primary/15 text-primary text-lg font-semibold">
                            {{ $loop->iteration }}
                        </span>
                    @endif
                </div>
                <h3 class="text-xl text-primary dark:text-white font-bold mb-2">
                    {{ $feature['title'] ?? 'ميزة مميزة' }}
                </h3>
                @if (!empty($feature['description']))
                    <p class="text-base text-gray-600 dark:text-gray-400">
                        {{ $feature['description'] }}
                    </p>
                @endif
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-4">
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('لم يتم إضافة مميزات بعد.') }}
                </p>
            </div>
        @endforelse
    </div>

    @if (!empty($data['button_text']))
        <div class="mt-20 text-center">
            <a href="{{ $data['button_url'] ?: '#' }}"
                class="inline-block bg-primary text-white font-semibold px-10 py-4 rounded-lg shadow-lg hover:bg-secondary hover:scale-105 transform transition-all duration-300">
                {{ $data['button_text'] }}
            </a>
        </div>
    @endif
</section>
