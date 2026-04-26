<!-- تصاميم عصرية واحترافية -->
@props(['data' => [], 'templates' => collect()])
<section
    class="bg-background dark:bg-gray-900 text-primary dark:text-white py-16 px-4 sm:px-8 lg:px-16 rtl:text-right ltr:text-left">
    <header class="text-center mb-10">
        <h2 class="text-title-h2 font-extrabold mb-2 animate-fade-in-up" data-aos="fade-up" data-aos-delay="200">
            {{ $data['title'] ?? 'عنوان غير متوفر' }}
        </h2>
        <p class="text-tertiary text-base dark:text-gray-300 sm:text-lg max-w-2xl mx-auto" data-aos="fade-up"
            data-aos-delay="200">
            {{ $data['subtitle'] ?? '' }}
        </p>
    </header>
    <main class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 px-0 sm:px-12" data-aos="zoom-in"
        data-aos-delay="200">
        @forelse ($templates as $template)
            @php
                $translation = $template->translation() ?? $template->translations->first();
                $slug = $translation->slug ?? $template->slug ?? $template->id;
                $name = $translation->name ?? __('Template');
                $description = $translation->description ?? '';
                $previewUrl = $translation->preview_url ?? null;
            @endphp
            <a href="/templates/{{ $slug }}@if(request('domain'))?domain={{ urlencode(request('domain')) }}@endif" class="block h-full">
                <article
                    class="h-full flex flex-col bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden relative group transition-transform duration-300 hover:-translate-y-2 hover:shadow-2xl border border-primary/10">
                    <meta itemprop="name" content="{{ $name }}">
                    <meta itemprop="description" content="{{ $description }}">
                    <meta itemprop="sku" content="template-{{ $template->id }}">
                    <meta itemprop="category" content="{{ $template->categoryTemplate->translation?->name }}">
                    <meta itemprop="brand" content="Palgoals">
                    <meta itemprop="priceCurrency" content="USD" />
                    <meta itemprop="price" content="{{ $template->discount_price ?? $template->price }}" />
                    <meta itemprop="availability" content="https://schema.org/InStock" />
                    <div class="relative">
                        <img src="{{ asset('storage/' . $template->image) }}" alt="{{ $name }}"
                            class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-105 group-hover:brightness-95" />
                        @if ($template->discount_price)
                            <div
                                class="bg-gradient-to-tr from-secondary to-primary text-white flex items-end justify-center w-24 h-10 absolute -top-2 rtl:-left-10 ltr:-right-10 ltr:rotate-[40deg] rtl:rotate-[320deg] animate-bounce shadow-lg font-bold text-base tracking-wide">
                                خصم
                            </div>
                        @else
                            <div
                                class="bg-gradient-to-tr from-secondary to-primary text-white flex items-end justify-center w-24 h-10 absolute -top-2 rtl:-left-10 ltr:-right-10 ltr:rotate-[40deg] rtl:rotate-[320deg] animate-bounce shadow-lg font-bold text-base tracking-wide">
                                جديد
                            </div>
                        @endif
                        <div
                            class="absolute top-2 right-2 rtl:right-auto rtl:left-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            @if ($previewUrl)
                                <button
                                    class="bg-white/80 dark:bg-white/20 hover:bg-primary text-primary hover:text-white rounded-full p-2 shadow-md transition"
                                    title="معاينة القالب" aria-label="معاينة القالب">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-9 9-9 9s-9-4-9-9a9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            @endif
                            <button
                                class="bg-white/80 dark:bg-white/20 hover:bg-secondary text-secondary hover:text-white rounded-full p-2 shadow-md transition"
                                title="شراء القالب" aria-label="شراء القالب">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.35 2.7A1 1 0 007 17h10a1 1 0 00.95-.68L19 13M7 13V6a1 1 0 011-1h5a1 1 0 011 1v7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-5 flex flex-col justify-between flex-grow rtl:text-right ltr:text-left">
                        <div>
                            <h3
                                class="text-suptitle font-bold text-primary/90 dark:text-white group-hover:text-secondary mb-2">
                                {{ $name }}</h3>
                            <p class="text-primary/70 dark:text-gray-300 text-sm">
                                {{ Str::limit(strip_tags($description), 70) }}</p>
                        </div>
                        <div class="mt-4 flex justify-between items-center text-sm font-bold">

                            <div class="flex items-center gap-2">
                                @if ($template->discount_price)
                                    <span
                                        class="line-through text-suptitle text-primary/40 dark:text-gray-400">${{ $template->price }}</span>
                                    <span
                                        class="text-title-h3 text-secondary dark:text-yellow-400">${{ $template->discount_price }}</span>
                                @else
                                    <span
                                        class="text-title-h3 text-secondary dark:text-yellow-400">${{ $template->price }}</span>
                                @endif
                            </div>
                            <div>
                                <span class="text-yellow-400 text-base">★★★★★</span>
                            </div>
                        </div>
                    </div>
                </article>
            </a>
        @empty
            <p class="text-center text-gray-500 col-span-full">لا توجد قوالب متاحة حاليًا.</p>
        @endforelse
    </main>
    <footer class="flex justify-center items-center gap-4 mt-10">
        <a href="/templates"
            class="relative inline-flex items-center justify-center px-10 py-3 overflow-hidden font-extrabold text-lg text-white rounded-lg shadow-lg bg-primary transition-all duration-300 hover:bg-primary/30 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2">
            <span
                class="absolute inset-0 w-full h-full bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></span>
            <span class="relative z-10 flex items-center gap-2 rtl:flex-row ltr:flex-row">
                <svg class="w-5 h-5 text-white group-hover:translate-x-1 transition-transform duration-300"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                اكتشف القوالب الاحترافية
            </span>
        </a>
    </footer>
</section>
