@php
    $bgUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null);
    $faqItems = $data['faq_items'] ?? [];
@endphp

<section id="hosting-faq" class="bg-[#F8F8F8] py-16 md:py-24 px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        @if (!empty($data['title']))
            <h2 class="text-purple-brand font-extrabold text-3xl md:text-4xl lg:text-[40px] text-center uppercase mb-0 animate-from-up">
                {{ $data['title'] }}
            </h2>
        @endif

        @if (!empty($data['subtitle']))
            <p class="text-[#555] text-base md:text-lg text-center max-w-[800px] mx-auto mb-12 animate-from-up">
                {{ $data['subtitle'] }}
            </p>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-4 animate-from-left order-2 lg:order-1 h-auto md:h-96">
                @if ($bgUrl)
                    <img src="{{ $bgUrl }}"
                         loading="lazy"
                         alt="{{ $data['image_alt'] ?? '' }}"
                         class="aspect-[4/4] w-full h-full rounded-[36px] object-cover">
                @endif
            </div>

            <div class="lg:col-span-8 animate-from-right order-1 lg:order-2">
                <div id="hosting-faq-accordion" class="space-y-0">
                    @if (!empty($faqItems))
                        @foreach ($faqItems as $index => $faqItem)
                            <div class="hosting-faq-item border-b border-gray-200">
                                <button type="button"
                                    class="hosting-faq-toggle w-full flex items-center justify-between py-2 text-left text-purple-brand font-bold text-base md:text-lg lg:text-xl hover:text-red-brand transition-colors">
                                    <span>{{ $faqItem['question'] ?? '' }}</span>
                                    <span class="hosting-faq-icon text-purple-brand text-2xl ml-2">
                                        {{ $index === 0 ? '−' : '+' }}
                                    </span>
                                </button>

                                <div class="hosting-faq-content overflow-hidden transition-all duration-300 ease-out {{ $index !== 0 ? 'hosting-faq-collapsed' : '' }}">
                                    <div class="pb-2 text-[#626262] text-base md:text-lg leading-relaxed">
                                        {{ $faqItem['answer'] ?? '' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
<script>
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".hosting-faq-toggle");
    if (!btn) return;

    const item = btn.closest(".hosting-faq-item");
    const content = item.querySelector(".hosting-faq-content");
    const icon = item.querySelector(".hosting-faq-icon");

    const isOpen = !content.classList.contains("hosting-faq-collapsed");

    // Close all
    document.querySelectorAll(".hosting-faq-item").forEach(el => {
        const c = el.querySelector(".hosting-faq-content");
        const i = el.querySelector(".hosting-faq-icon");

        c.style.maxHeight = "0";
        c.classList.add("hosting-faq-collapsed");
        i.textContent = "+";
    });

    // Toggle current
    if (isOpen) {
        content.style.maxHeight = "0";
        content.classList.add("hosting-faq-collapsed");
        icon.textContent = "+";
    } else {
        content.classList.remove("hosting-faq-collapsed");
        content.style.maxHeight = content.scrollHeight + "px";
        icon.textContent = "−";
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const items = document.querySelectorAll(".hosting-faq-item");

    items.forEach((item, index) => {
        const content = item.querySelector(".hosting-faq-content");
        const icon = item.querySelector(".hosting-faq-icon");

        if (index === 0) {
            content.classList.remove("hosting-faq-collapsed");
            content.style.maxHeight = content.scrollHeight + "px";
            icon.textContent = "−";
        } else {
            content.classList.add("hosting-faq-collapsed");
            content.style.maxHeight = "0";
            icon.textContent = "+";
        }
    });
});
</script>