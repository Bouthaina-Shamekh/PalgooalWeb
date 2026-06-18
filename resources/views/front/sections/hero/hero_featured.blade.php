@php
    use Illuminate\Support\Str;
    use App\Support\Sections\SectionFrontendMediaResolver;

    $sectionId = Str::slug(trim((string) ($data['section_id'] ?? 'design'))) ?: 'design';
    $title = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['subtitle'] ?? ''));

    $services = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $iconSource = ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class';
            $icon = trim((string) ($item['icon'] ?? ''));

            $iconMediaUrl = SectionFrontendMediaResolver::resolve($item['icon_media'] ?? null);

            if ($title === '' && $icon === '' && !$iconMediaUrl) {
                return null;
            }

            return [
                'title' => $title,
                'icon_source' => $iconSource,
                'icon' => $icon,
                'icon_media_url' => $iconMediaUrl,
            ];
        })
        ->filter()
        ->values();

    $ctaTitle = trim((string) ($data['cta_title'] ?? ''));
    $ctaButtonLabel = trim((string) ($data['cta_button_label'] ?? ''));
    $buttonUrl = trim((string) ($data['button_url'] ?? ''));

    $backgroundImage = SectionFrontendMediaResolver::resolve($data['background_image'] ?? null);
@endphp

<section id="{{ $sectionId }}" class="relative min-h-[448px] bg-purple-brand overflow-hidden">
    @if ($backgroundImage)
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $backgroundImage }}')"></div>
    @endif

    <div class="absolute inset-0 bg-purple-brand/80"></div>

    <div class="container mx-auto relative px-2">
        <p class="animate-from-left text-[#d9d9d9] text-base mb-4 flex items-center gap-2 py-4">
            <a href="{{ url('/') }}" class="hover:text-white transition-colors flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="12" viewBox="0 0 14.056 11.948">
                    <path d="M8.622,16.448V12.231h2.811v4.217h3.514V10.825h2.108L10.028,4.5,3,10.825H5.108v5.622Z"
                        transform="translate(-3 -4.5)" fill="currentColor" />
                </svg>
                Home
            </a>
            @if ($title !== '')
                / {{ $title }}
            @endif
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center py-6 md:py-10">
            <div class="lg:col-span-7 animate-from-left">
                @if ($title !== '')
                    <h1 class="text-white font-extrabold text-3xl md:text-4xl lg:text-[40px] mb-4 uppercase">
                        {{ $title }}
                    </h1>
                @endif

                @if ($description !== '')
                    <p class="text-[#d9d9d9] text-base md:text-lg leading-relaxed mb-6">
                        {{ $description }}
                    </p>
                @endif

                @if ($services->isNotEmpty())
                    <div class="space-y-3 mb-6">
                        @foreach ($services as $service)
                            <div class="flex items-center gap-3">
                                @if ($service['icon_source'] === 'media' && $service['icon_media_url'])
                                    <img src="{{ $service['icon_media_url'] }}" alt=""
                                        class="h-5 w-5 object-contain flex-shrink-0">
                                @elseif ($service['icon'] !== '')
                                    <i class="{{ $service['icon'] }} text-red-brand text-xl flex-shrink-0"></i>
                                @else
                                    <svg class="h-5 w-5 text-red-brand flex-shrink-0" fill="currentColor"
                                        viewBox="0 0 27 21">
                                        <path d="M8.4 15.9L2.1 9.6L0 11.7L8.4 20.1L26.4 2.1L24.3 0L8.4 15.9Z" />
                                    </svg>
                                @endif

                                @if ($service['title'] !== '')
                                    <span class="text-white text-base md:text-lg">{{ $service['title'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($ctaTitle !== '' || ($ctaButtonLabel !== '' && $buttonUrl !== ''))
                <div class="lg:col-span-5 flex flex-col md:items-end items-center animate-from-right">
                    <div class="bg-purple-brand rounded-[20px] p-8">
                        @if ($ctaTitle !== '')
                            <p class="text-white font-bold text-xl md:text-[29px] leading-tight mb-4">
                                {{ $ctaTitle }}
                            </p>
                        @endif

                        @if ($ctaButtonLabel !== '' && $buttonUrl !== '')
                            <a href="{{ $buttonUrl }}"
                                class="block bg-red-brand text-white text-center py-3 px-4 rounded-xl text-lg md:text-xl hover:bg-red-brand/90 transition-all duration-300 hover:-translate-y-0.5">
                                {{ $ctaButtonLabel }}
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
