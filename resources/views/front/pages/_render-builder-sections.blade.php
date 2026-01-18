@php
    $sectionComponents = $sectionComponents ?? [];
@endphp

@foreach ($builderSections as $builderSection)
    @php
        $type = $builderSection['type'] ?? null;
        $component = $sectionComponents[$type] ?? null;

        $data = is_array($builderSection['data'] ?? null) ? $builderSection['data'] : [];

        // ✅ Inject dynamic DB payload only when needed
        if ($type) {
            $data = \App\Support\Sections\SectionQueryResolver::resolve($type, $data);
        }
    @endphp

    @if ($component)
        <x-dynamic-component :component="'template.sections.' . $component" :data="$data" />
    @else
        {{-- Inline / HTML-only rendering --}}
        @switch($type)
            @case('text')
                <section class="py-10 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-4xl mx-auto text-{{ $data['align'] ?? 'left' }}">
                        @if (!empty($data['title']))
                            <h2 class="text-2xl font-bold text-slate-900 mb-3">{{ $data['title'] }}</h2>
                        @endif
                        @if (!empty($data['body']))
                            <p class="text-slate-600">{{ $data['body'] }}</p>
                        @endif
                    </div>
                </section>
                @break

            @case('image')
                <section class="py-8 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-5xl mx-auto text-{{ $data['align'] ?? 'center' }}">
                        <figure class="inline-block">
                            <img src="{{ $data['url'] ?? '' }}" alt="{{ $data['alt'] ?? '' }}"
                                 style="width: {{ $data['width'] ?? '100%' }};">
                            @if (!empty($data['alt']))
                                <figcaption class="text-sm text-slate-500 mt-2">{{ $data['alt'] }}</figcaption>
                            @endif
                        </figure>
                    </div>
                </section>
                @break

            @case('button')
                <section class="py-6 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-4xl mx-auto text-{{ $data['align'] ?? 'center' }}">
                        @php $isOutline = ($data['style'] ?? 'primary') === 'outline'; @endphp
                        <a href="{{ $data['url'] ?? '#' }}"
                           class="inline-flex items-center gap-2 px-5 py-3 rounded-full text-sm font-semibold
                                  {{ $isOutline ? 'border border-slate-300 text-slate-800 bg-white' : 'bg-primary text-white shadow' }}">
                            {{ $data['text'] ?? __('Button') }}
                        </a>
                    </div>
                </section>
                @break

            @default
                {{-- Unknown type: ignore safely --}}
        @endswitch
    @endif
@endforeach
