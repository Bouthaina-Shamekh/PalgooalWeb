@php
    $payload = is_array($data ?? null)
        ? $data
        : (is_array($content ?? null) ? $content : []);

    $style = isset($section) && is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'py-20';

    $brandPrefix = trim((string) ($payload['brand_prefix'] ?? 'PAL'));
    $brandSuffix = trim((string) ($payload['brand_suffix'] ?? 'GOALS'));
    $sectionTitle = trim((string) ($payload['title'] ?? __('Domains')));
    $searchHeading = trim((string) ($payload['search_heading'] ?? ($payload['subtitle'] ?? __('Find your perfect Domain name'))));
    $sectionDescription = trim((string) ($payload['description'] ?? __('Welcome to our domain hosting platform, where your online journey begins.')));
    $inputPlaceholder = trim((string) ($payload['input_placeholder'] ?? __('enter your domain here...')));

    $primaryButton = is_array($payload['primary_button'] ?? null) ? $payload['primary_button'] : [];
    $searchButtonLabel = trim((string) ($primaryButton['label'] ?? __('Search')));
    $searchActionUrl = trim((string) ($primaryButton['url'] ?? route('domains.page', [], false)));

    if ($searchButtonLabel === '') {
        $searchButtonLabel = __('Search');
    }

    if ($searchActionUrl === '') {
        $searchActionUrl = route('domains.page', [], false);
    }
@endphp

<section id="hosting" class="{{ $paddingY }} px-4 md:px-24">
    <div class="text-center mb-8">
        <p class="text-lg md:text-xl">
            <span class="text-red-brand">{{ $brandPrefix }}</span><span class="text-purple-brand">{{ $brandSuffix }}</span>
        </p>

        @if ($sectionTitle !== '')
            <h2 class="text-purple-brand font-extrabold text-3xl md:text-[40px] uppercase">
                {{ $sectionTitle }}
            </h2>
        @endif
    </div>

    <div class="bg-purple-brand rounded-[40px] p-8 md:p-16 text-center text-white max-w-5xl mx-auto shadow-xl">
        @if ($searchHeading !== '')
            <h3 class="text-2xl md:text-3xl font-bold mb-4">
                {{ $searchHeading }}
            </h3>
        @endif

        @if ($sectionDescription !== '')
            <p class="text-base md:text-lg font-light mb-6 opacity-80">
                {{ $sectionDescription }}
            </p>
        @endif

        <form action="{{ $searchActionUrl }}" method="GET" class="flex flex-col md:flex-row gap-4 max-w-3xl mx-auto">
            <input
                type="text"
                name="domain"
                placeholder="{{ $inputPlaceholder }}"
                class="flex-1 bg-white rounded-xl px-6 py-4 text-purple-brand text-xl outline-none text-start"
                autocomplete="off"
            >

            <button
                type="submit"
                class="bg-red-brand text-white px-12 py-4 rounded-xl font-bold text-xl hover:bg-opacity-90 transition"
            >
                {{ $searchButtonLabel }}
            </button>
        </form>
    </div>
</section>
