@php
    $breadcrumbHomeLabel = trim((string) ($data['breadcrumb_home_label'] ?? __('Home')));
    $breadcrumbHomeUrl = trim((string) ($data['breadcrumb_home_url'] ?? 'index.html'));
    $breadcrumbCurrentLabel = trim((string) ($data['breadcrumb_current_label'] ?? __('Hosting')));
    $featureItems = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
        ->map(function ($item) {
            if (is_array($item)) {
                $text = trim((string) ($item['text'] ?? ($item['title'] ?? ($item['label'] ?? ''))));
            } elseif (is_scalar($item)) {
                $text = trim((string) $item);
            } else {
                return null;
            }

            if ($text === '') {
                return null;
            }

            return ['text' => $text];
        })
        ->filter()
        ->values();

    $bgUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['background_image'] ?? null);

    if ($featureItems->isEmpty()) {
        $featureItems = collect([
            ['text' => 'Choose your Template'],
            ['text' => 'Control Panel'],
            ['text' => 'Private Domain'],
            ['text' => 'Private Domain'],
        ]);
    }
@endphp
  <section id="hosting" class="relative min-h-[448px] bg-purple-brand overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center" @if ($bgUrl) style="background-image: url('{{ $bgUrl }}')" @endif></div>
        <div class="absolute inset-0 bg-purple-brand/80"></div>
        <div class="container mx-auto relative px-2">
            <p class="animate-from-left text-[#d9d9d9] text-base mb-4 flex items-center gap-2 py-4">
                <a href="{{ $breadcrumbHomeUrl !== '' ? $breadcrumbHomeUrl : 'index.html' }}" class="hover:text-white transition-colors flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="12" viewBox="0 0 14.056 11.948">
                        <path d="M8.622,16.448V12.231h2.811v4.217h3.514V10.825h2.108L10.028,4.5,3,10.825H5.108v5.622Z" transform="translate(-3 -4.5)" fill="currentColor"/>
                    </svg>
                    {{ $breadcrumbHomeLabel }}
                </a>
                / {{ $breadcrumbCurrentLabel }}
            </p>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center py-6 md:py-10">
                <div class="lg:col-span-7 animate-from-left">
                     @if (!empty($data['title']))
                    <h1 class="text-white font-extrabold text-3xl md:text-4xl lg:text-[40px] mb-4 uppercase">{{ $data['title'] }}</h1>
                    @endif
                    @if (!empty($data['subtitle']))
                    <p class="text-[#d9d9d9] text-base md:text-lg leading-relaxed mb-6">{{ $data['subtitle'] }}</p>
                    @endif
                    <div class="space-y-3 mb-6">
                        @foreach ($featureItems as $featureItem)
                            <div class="flex items-center gap-3">
                                <svg class="h-5 text-red-brand flex-shrink-0" fill="currentColor" viewBox="0 0 27 21"><path d="M8.4 15.9L2.1 9.6L0 11.7L8.4 20.1L26.4 2.1L24.3 0L8.4 15.9Z" fill="#BA112C"/></svg>
                                <span class="text-white text-base md:text-lg capitalize">{{ $featureItem['text'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="lg:col-span-5 flex flex-col md:items-end items-center animate-from-right">
                    <div class="bg-purple-brand rounded-[20px] p-8">
                        @if (!empty($data['card_title']))
                        <p class="text-white font-bold text-xl md:text-[29px] leading-tight mb-4 capitalize">{{ $data['card_title'] }}</p>
                        @endif
                        @if (!empty($data['card_button_label']))
                        <a href="{{ $data['card_button_url'] ?? '#' }}" class="block bg-red-brand text-white text-center py-3 px-4 rounded-xl text-lg md:text-xl hover:bg-red-brand/90 transition-all duration-300 hover:-translate-y-0.5">
                            {{ $data['card_button_label'] }}
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
