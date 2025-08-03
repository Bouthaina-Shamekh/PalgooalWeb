<section class="py-20 px-4 sm:px-8 lg:px-24 bg-white dark:bg-gray-950">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
            <!-- الشريط الجانبي للتصنيفات والفلترة -->
            @if($showSidebar)
            <aside class="space-y-8">
                <!-- التصنيفات -->
                <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-primary mb-3 border-b pb-2">أقسام القوالب</h3>
                    <ul class="space-y-2 text-gray-800">
                        <li>
                            <button wire:click="$set('selectedCategory', 'all')" class="{{ $selectedCategory === 'all' ? 'text-primary font-bold' : '' }}">
                                الكل
                            </button>
                        </li>
                        @foreach ($categories as $cat)
                            <li>
                                <button wire:click="$set('selectedCategory', '{{ $cat->id }}')" class="{{ $selectedCategory == $cat->id ? 'text-primary font-bold' : '' }}">
                                    {{ $cat->translated_name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- الفلترة حسب السعر -->
                <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-primary mb-3 border-b pb-2">الفرز حسب السعر</h3>
                    <input type="range" min="50" max="250" wire:model="maxPrice" class="w-full accent-primary">
                    <p class="text-sm text-gray-700">السعر الأقصى: <strong>{{ $maxPrice }}</strong>$</p>
                </div>
            </aside>
            @endif

            <!-- شبكة القوالب -->
            <div class="lg:col-span-3">
                <!-- أدوات العرض -->
                <div class="flex justify-between items-center mb-6">
                    <p class="text-sm text-gray-600">عرض {{ $templates->count() }} من {{ $templates->total() }} نتيجة</p>
                    <select wire:model="sortBy" class="border border-gray-300 text-sm rounded px-3 py-1">
                        <option value="default">الترتيب الافتراضي</option>
                        <option value="high">الأعلى سعرًا</option>
                        <option value="low">الأقل سعرًا</option>
                    </select>
                </div>

                <!-- شبكة البطاقات -->
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($templates as $t)
                        <a href="{{ route('template.show', $t->slug) }}" class="block group">
                            <article class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden border border-primary/10 transition-transform duration-300 hover:-translate-y-1 hover:shadow-2xl">
                                <div class="relative">
                                    <img src="{{ asset($t->image) }}" alt="{{ $t->name }}" class="w-full h-40 object-cover">
                                    @if($t->discount_price)
                                        <div class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">خصم</div>
                                    @endif
                                </div>
                                <div class="p-5 rtl:text-right">
                                    <h3 class="text-base font-bold text-primary mb-1">{{ $t->name }}</h3>
                                    <p class="text-sm text-gray-500 mb-2">{{ $t->description }}</p>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-500 line-through">{{ $t->discount_price ? '$' . $t->price : '' }}</span>
                                        <span class="text-secondary font-bold text-lg">${{ $t->discount_price ?? $t->price }}</span>
                                    </div>
                                </div>
                            </article>
                        </a>
                    @empty
                        <div class="col-span-full text-center text-gray-500 py-12">لا توجد قوالب مطابقة للفلترة الحالية.</div>
                    @endforelse
                </div>

                <!-- زر تحميل المزيد -->
                <div class="mt-10 text-center">
                    {{ $templates->links('pagination::tailwind') }}
                </div>
            </div>
        </div>
    </div>
</section>
