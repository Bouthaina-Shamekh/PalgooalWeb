<section class="py-20 px-4 sm:px-8 lg:px-24 bg-white dark:bg-gray-950">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
            <!-- الشريط الجانبي للتصنيفات والفلترة -->
            <aside class="space-y-8">
                <!-- أقسام القوالب -->
                <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-primary mb-3 border-b pb-2">{{ t('Frontend.template_sections', 'template sections')}}</h3>
                    <ul class="space-y-2 text-gray-800" id="categoryFilter">
                        <li><button data-category="all" class="hover:text-primary" aria-label="{{ t('Frontend.All', 'All')}}">{{ t('Frontend.All', 'All')}}</button></li>
                        <li><button data-category="المتاجر" class="hover:text-primary" aria-label="عرض قوالب المتاجر">المتاجر</button></li>
                        <li><button data-category="منيو مطاعم" class="hover:text-primary" aria-label="عرض قوالب المطاعم">منيو مطاعم</button></li>
                    </ul>
                </div>
                <!-- الفلترة حسب السعر -->
                <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-primary mb-3 border-b pb-2">{{ t('Frontend.Sort_price', 'Sort by price')}}</h3>
                    <label for="priceRange" class="sr-only">أقصى سعر</label>
                    <div class="mb-4">
                        <input id="priceRange" type="range" min="50" max="250" value="250" class="w-full accent-primary">
                    </div>
                    <p class="text-sm text-gray-700">{{ t('Frontend.Maximum_price:', 'Maximum price:')}}<span id="priceValue">250</span>$</p>
                </div>
            </aside>
            <!-- شبكة القوالب -->
            <div class="lg:col-span-3">
                <!-- أدوات العرض -->
                <div class="flex justify-between items-center mb-6">
                    <p id="resultCount" class="text-sm text-gray-600">{{ t('Frontend.Showing 6 of 10 results:', 'Showing 6 of 10 results')}}</p>
                    <label for="sortSelect" class="sr-only">{{ t('Frontend.Sort_price', 'Sort by price')}}</label>
                    <select id="sortSelect" class="border border-gray-300 text-sm rounded px-3 py-1">
                        <option value="default">{{ t('Frontend.Default_order', 'Default order')}}</option>
                        <option value="high">{{ t('Frontend.highest_price', 'highest price')}}</option>
                        <option value="low">{{ t('Frontend.Lowest_price', 'Lowest price')}}</option>
                    </select>
                </div>
                <!-- الشبكة -->
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6" id="templateGrid">
                    <!-- البطاقات ستُحقن هنا ديناميكيًا -->
                </div>
                <div id="noResults" class="hidden text-center text-gray-500 py-10 col-span-full">
                    {{ t('Frontend.There are no templates matching the current filter.', 'There are no templates matching the current filter.')}}
                </div>
                <div class="mt-10 text-center">
                    <button id="loadMoreBtn" class="px-6 py-2 bg-primary text-white rounded-full hover:bg-secondary transition">{{ t('Frontend.See_more', 'See more')}}</button>
                </div>
            </div>
        </div>
    </div>
</section>
<script src="{{ asset('assets/tamplate/js/template.js') }}" defer></script>