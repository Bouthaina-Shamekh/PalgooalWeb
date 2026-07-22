@php
    $section_id = trim((string) ($data['section_id'] ?? '')) ?: 'domain-search';
    $title = trim((string) ($data['title'] ?? '')); // text / trans
    $subtitle = (string) ($data['subtitle'] ?? ''); // textarea / trans
    $placeholder = trim((string) ($data['placeholder'] ?? '')); // text / trans
    $button_text = trim((string) ($data['button_text'] ?? '')); // text / trans
    $search_title = trim((string) ($data['search_title'] ?? '')); // text / trans
    $search_description = (string) ($data['search_description'] ?? ''); // textarea / trans
@endphp

<section id="{{ $section_id }}" class="py-20 px-4 md:px-24">
    <div class="text-center mb-8">
        @if ($title)
            <h2 class="text-purple-brand font-extrabold text-3xl md:text-[40px] uppercase">
                {{ $title }}
            </h2>
        @endif
        @if ($subtitle)
            <p class="text-[#555] text-base md:text-lg leading-relaxed">
                {!! nl2br(e($subtitle)) !!}
            </p>
        @endif
    </div>
    <div class="bg-purple-brand rounded-[40px] p-8 md:p-16 text-center text-white max-w-5xl mx-auto shadow-xl">
        @if ($search_title)
            <h3 class="text-2xl md:text-3xl font-bold mb-4">
                {{ $search_title }}
            </h3>
        @endif
        @if ($search_description)
            <p class="text-base md:text-lg font-light mb-6 opacity-80">
                {!! nl2br(e($search_description)) !!}
            </p>
        @endif
        <div class="flex flex-col md:flex-row gap-4 max-w-3xl mx-auto">
            <input type="text"  placeholder="{{ $placeholder }}" autocomplete="off" spellcheck="false"
                class="flex-1 bg-white rounded-xl px-6 py-4 text-purple-brand text-xl outline-none text-start">
            @if ($button_text)
                <button class="bg-red-brand text-white px-12 py-4 rounded-xl font-bold text-xl hover:bg-opacity-90">
                    {{ $button_text }}
                </button>
            @endif
        </div>
    </div>
</section>
