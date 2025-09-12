@props([
    'templates' => collect(),
    'categories' => collect(),
    'max_price' => 500,
    'sort_by' => 'default',
    'show_filter_sidebar' => true,
    'selectedCategory' => 'all',
])
<section class="py-20 px-4 sm:px-8 lg:px-24 bg-white dark:bg-gray-950">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
            <!-- الشريط الجانبي للتصنيفات والفلترة -->
            <aside class="space-y-8">
                <!-- أقسام القوالب -->
                <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-primary mb-3 border-b pb-2">أقسام القوالب</h3>
                    <ul class="space-y-2 text-gray-800" id="categoryFilter">
                        <li><button data-category="all" class="hover:text-primary" aria-label="عرض الكل">الكل</button>
                        </li>
                        @foreach ($categories as $category)
                            <li>
                                <button data-category="{{ trim(Str::lower($category->slug)) }}"
                                    class="hover:text-primary" aria-label="عرض قوالب {{ $category->translated_name }}">
                                    {{ $category->translated_name }}
                                </button>
                            </li>
                        @endforeach


                    </ul>
                </div>
                <!-- الفلترة حسب السعر -->
                <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-primary mb-3 border-b pb-2">الفرز حسب السعر</h3>
                    <label for="priceRange" class="sr-only">أقصى سعر</label>
                    <div class="mb-4">
                        <input id="priceRange" type="range" min="0" max="{{ (int) $max_price }}"
                            value="{{ (int) $max_price }}" class="w-full accent-primary">
                    </div>
                    <p class="text-sm text-gray-700">السعر الأقصى: <span id="priceValue">{{ (int) $max_price }}</span>$
                    </p>
                </div>
            </aside>
            <!-- شبكة القوالب -->
            <div class="lg:col-span-3">
                <!-- أدوات العرض -->
                <div class="flex justify-between items-center mb-6">
                    <p id="resultCount" class="text-sm text-gray-600">عرض 6 من كل النتائج</p>
                    <label for="sortSelect" class="sr-only">ترتيب حسب السعر</label>
                    <select id="sortSelect" class="border border-gray-300 text-sm rounded px-3 py-1">
                        <option value="default" {{ ($sort_by ?? 'default') === 'default' ? 'selected' : '' }}>الترتيب
                            الافتراضي</option>
                        <option value="high" {{ ($sort_by ?? '') === 'high' ? 'selected' : '' }}>الأعلى سعرًا
                        </option>
                        <option value="low" {{ ($sort_by ?? '') === 'low' ? 'selected' : '' }}>الأقل سعرًا
                        </option>
                    </select>
                </div>
                <!-- الشبكة -->
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6" id="templateGrid">
                    @forelse ($templates as $template)
                        @php
                            $translation = $template->translation();
                        @endphp
                        <a href="{{ route('template.show', $translation->slug) }}" data-card
                            data-category="{{ trim(Str::lower($template->categoryTemplate?->slug ?? 'uncategorized')) }}"
                            class="block group" data-price="{{ $template->discount_price ?? $template->price }}">
                            <article style="will-change: transform"
                                class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden relative group transition-transform duration-300 hover:-translate-y-2 hover:shadow-2xl animate-fade-in-up border border-primary/10"
                                itemscope itemtype="https://schema.org/Product" lang="ar">
                                <meta itemprop="name" content="{{ $translation->name }}">
                                <meta itemprop="description" content="{{ $translation->description }}">
                                <meta itemprop="sku" content="template-{{ $template->id }}">
                                <meta itemprop="category"
                                    content="{{ $template->categoryTemplate->translation?->name }}">
                                <meta itemprop="brand" content="Palgoals">
                                <meta itemprop="priceCurrency" content="USD" />
                                <meta itemprop="price" content="{{ $template->discount_price ?? $template->price }}" />
                                <meta itemprop="availability" content="https://schema.org/InStock" />
                                <div class="relative">
                                    <img itemprop="image" src="{{ asset('storage/' . $template->image) }}"
                                        alt="{{ $translation->name }}"
                                        class="w-full h-40 object-cover transition-transform duration-300 group-hover:scale-105 group-hover:brightness-95"
                                        loading="lazy" decoding="async">
                                    <div
                                        class="bg-gradient-to-tr from-secondary to-primary text-white flex items-end justify-center w-24 h-10 absolute -top-2 rtl:-left-10 ltr:-right-10 ltr:rotate-[40deg] rtl:rotate-[320deg] animate-bounce shadow-lg font-bold text-base tracking-wide">
                                        جديد</div>
                                    <div
                                        class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition duration-300">
                                    </div>
                                    <div
                                        class="absolute top-2 right-2 rtl:right-auto rtl:left-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <button
                                            class="bg-white/80 dark:bg-white/20 hover:bg-primary text-primary hover:text-white rounded-full p-2 shadow-md transition"
                                            title="معاينة القالب" aria-label="معاينة القالب">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-9 9-9 9s-9-4-9-9a9 9 0 0118 0z" />
                                            </svg>
                                        </button>
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
                                <div class="p-5 rtl:text-right ltr:text-left flex flex-col items-start">
                                    <h3 itemprop="name"
                                        class="text-suptitle font-bold mb-1 text-primary/90 dark:text-white group-hover:text-secondary transition-colors leading-snug">
                                        {{ $translation->name }}</h3>
                                    <p itemprop="description"
                                        class="text-suptitle font-light mb-2 text-primary/70 dark:text-gray-300">
                                        {{ Str::limit(strip_tags($translation->description), 70) }}</p>
                                    <div
                                        class="flex justify-between items-center text-sm font-bold rtl:flex-row-reverse ltr:flex-row mt-3 w-full">
                                        <div class="flex items-center gap-1" aria-label="التقييم 4 من 5 نجوم">
                                            <span
                                                class="text-yellow-400 text-base">{!! str_repeat('★', floor($template->rating)) !!}{!! str_repeat('☆', 5 - floor($template->rating)) !!}</span>
                                        </div>
                                        <div class="flex items-center gap-2 rtl:flex-row-reverse ltr:flex-row">
                                            @if ($template->discount_price)
                                                <span
                                                    class="line-through text-suptitle text-primary/40 dark:text-gray-400">${{ $template->price }}</span>
                                                <span itemprop="price"
                                                    class="text-title-h3 text-secondary dark:text-yellow-400">${{ $template->discount_price }}</span>
                                            @else
                                                <span itemprop="price"
                                                    class="text-title-h3 text-secondary dark:text-yellow-400">${{ $template->price }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </a>
                    @empty
                        <div id="noResults" class="hidden text-center text-gray-500 py-10 col-span-full">
                            لا توجد قوالب مطابقة للفلترة الحالية.
                        </div>
                    @endforelse
                    <div id="noResults" class="hidden text-center text-gray-500 py-10 col-span-full">
                        لا توجد قوالب مطابقة للفلترة الحالية.
                    </div>

                </div>

                <div class="mt-10 text-center">
                    <button id="loadMoreBtn"
                        class="px-6 py-2 bg-primary text-white rounded-full hover:bg-secondary transition">تحميل
                        المزيد</button>
                </div>
            </div>
        </div>
    </div>
</section>
{{-- <script src="{{ asset('assets/tamplate/js/template.js') }}" defer></script> --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const grid = document.getElementById('templateGrid');
        const cards = Array.from(grid.querySelectorAll('[data-card]'));
        const buttons = document.querySelectorAll('#categoryFilter button');
        const priceRange = document.getElementById('priceRange');
        const priceValue = document.getElementById('priceValue');
        const sortSelect = document.getElementById('sortSelect');
        const noResults = document.getElementById('noResults');
        const resultCount = document.getElementById('resultCount');

        const state = {
            category: 'all',
            maxPrice: parseFloat(priceRange?.value || '999999'),
            sortBy: sortSelect?.value || 'default',
        };

        function applyFiltersAndSort() {
            let visible = [];

            // فلترة حسب التصنيف + السعر
            cards.forEach(card => {
                const cat = (card.dataset.category || '').trim().toLowerCase();
                const price = parseFloat(card.dataset.price || '0');

                const passCat = (state.category === 'all' || cat === state.category);
                const passPrice = (price <= state.maxPrice);

                const show = passCat && passPrice;
                card.style.display = show ? 'block' : 'none';
                if (show) visible.push(card);
            });

            // فرز على الظاهرة فقط
            if (state.sortBy !== 'default') {
                visible.sort((a, b) => {
                    const pa = parseFloat(a.dataset.price || '0');
                    const pb = parseFloat(b.dataset.price || '0');
                    return state.sortBy === 'high' ? (pb - pa) : (pa - pb);
                });
                visible.forEach(card => grid.appendChild(card));
            }

            // لا نتائج + عدّاد
            if (noResults) noResults.classList.toggle('hidden', visible.length > 0);
            if (resultCount) resultCount.textContent = `عرض ${visible.length} من ${cards.length} نتيجة`;
        }

        // أحداث: التصنيفات
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                buttons.forEach(b => b.classList.remove('text-primary', 'font-bold'));
                btn.classList.add('text-primary', 'font-bold');

                state.category = (btn.dataset.category || 'all').trim().toLowerCase();
                applyFiltersAndSort();
            });
        });

        // أحداث: الرينج
        if (priceRange && priceValue) {
            priceValue.textContent = priceRange.value;
            priceRange.addEventListener('input', () => {
                priceValue.textContent = priceRange.value;
                state.maxPrice = parseFloat(priceRange.value);
                applyFiltersAndSort();
            });
        }

        // أحداث: الفرز
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                state.sortBy = sortSelect.value;
                applyFiltersAndSort();
            });
        }

        // تشغيل أولي
        applyFiltersAndSort();
    });
</script>
