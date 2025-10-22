@php
    $backgroundPresets = [
        'white'        => ['classes' => 'bg-white dark:bg-gray-950',         'is_dark' => false],
        'gray'         => ['classes' => 'bg-gray-50 dark:bg-gray-900',       'is_dark' => false],
        'stone'        => ['classes' => 'bg-stone-50 dark:bg-stone-900',     'is_dark' => false],
        'slate-light'  => ['classes' => 'bg-slate-100 dark:bg-slate-900',    'is_dark' => false],
        'slate-dark'   => ['classes' => 'bg-slate-900 text-white',           'is_dark' => true],
        'zinc-dark'    => ['classes' => 'bg-zinc-900 text-white',            'is_dark' => true],
        'black'        => ['classes' => 'bg-gray-950 text-white',            'is_dark' => true],
        'sky'          => ['classes' => 'bg-sky-50 dark:bg-sky-900',         'is_dark' => false],
        'blue'         => ['classes' => 'bg-blue-50 dark:bg-blue-900',       'is_dark' => false],
        'indigo'       => ['classes' => 'bg-indigo-600 text-white',          'is_dark' => true],
        'violet'       => ['classes' => 'bg-violet-600 text-white',          'is_dark' => true],
        'purple'       => ['classes' => 'bg-purple-600 text-white',          'is_dark' => true],
        'amber'        => ['classes' => 'bg-amber-50 dark:bg-amber-900',     'is_dark' => false],
        'orange'       => ['classes' => 'bg-orange-500 text-white',          'is_dark' => true],
        'rose'         => ['classes' => 'bg-rose-50 dark:bg-rose-900',       'is_dark' => false],
        'rose-deep'    => ['classes' => 'bg-rose-600 text-white',            'is_dark' => true],
        'emerald'      => ['classes' => 'bg-emerald-50 dark:bg-emerald-900', 'is_dark' => false],
        'emerald-deep' => ['classes' => 'bg-emerald-600 text-white',         'is_dark' => true],
        'teal'         => ['classes' => 'bg-teal-500 text-white',            'is_dark' => true],
    ];

    $variant = $data['background_variant'] ?? 'white';
    $preset = $backgroundPresets[$variant] ?? $backgroundPresets['white'];

    $sectionClasses = $preset['classes'];
    $isDarkSection = $preset['is_dark'];
    $cardClasses = $isDarkSection ? 'bg-white/10 text-white' : 'bg-background dark:bg-gray-900';
    $subtitleClasses = $isDarkSection ? 'text-white/80' : 'text-gray-600 dark:text-gray-300';
    $featureTextClasses = $isDarkSection ? 'text-white/70' : 'text-gray-600 dark:text-gray-400';
    $headingClasses = $isDarkSection ? 'text-white' : 'text-primary dark:text-white';
    $featureTitleClasses = $isDarkSection ? 'text-white' : 'text-primary dark:text-white';
@endphp

{{-- Tailwind safelist: bg-white dark:bg-gray-950 bg-gray-50 dark:bg-gray-900 bg-stone-50 dark:bg-stone-900 bg-slate-100 dark:bg-slate-900 bg-slate-900 text-white bg-zinc-900 bg-gray-950 bg-sky-50 dark:bg-sky-900 bg-blue-50 dark:bg-blue-900 bg-indigo-600 bg-violet-600 bg-purple-600 bg-amber-50 dark:bg-amber-900 bg-orange-500 bg-rose-50 dark:bg-rose-900 bg-rose-600 bg-emerald-50 dark:bg-emerald-900 bg-emerald-600 bg-teal-500 bg-white/10 bg-background dark:bg-gray-900 --}}
<section class="py-20 px-4 sm:px-8 lg:px-24 {{ $sectionClasses }}" dir="auto" aria-labelledby="features-two-heading">
    <div class="max-w-6xl mx-auto text-center mb-16">
        <h2 id="features-two-heading" class="text-3xl lg:text-4xl font-extrabold {{ $headingClasses }} mb-4">
            {{ $data['title'] ?? 'Advanced Features' }}
        </h2>
        @if (!empty($data['subtitle']))
            <p class="text-lg {{ $subtitleClasses }} max-w-3xl mx-auto">
                {{ $data['subtitle'] }}
            </p>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 text-center">
        @forelse ($data['features'] ?? [] as $feature)
            <div class="{{ $cardClasses }} rounded-xl p-8 shadow-lg hover:shadow-xl hover:-translate-y-2 transition-all duration-300 flex flex-col items-center h-full">
                <div class="w-20 h-20 mb-6 flex items-center justify-center">
                    @if (!empty($feature['icon']))
                        {!! $feature['icon'] !!}
                    @else
                        <span class="w-16 h-16 flex items-center justify-center rounded-full bg-primary/15 text-primary text-lg font-semibold">
                            {{ $loop->iteration }}
                        </span>
                    @endif
                </div>
                <h3 class="text-xl font-bold mb-2 {{ $featureTitleClasses }}">
                    {{ $feature['title'] ?? 'Feature title' }}
                </h3>
                @if (!empty($feature['description']))
                    <p class="text-base {{ $featureTextClasses }}">
                        {{ $feature['description'] }}
                    </p>
                @endif
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-4">
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('No features have been added yet.') }}
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
