{{-- deprecated - do not use. Legacy admin Livewire view retained only for fallback safety. --}}
<section class="py-20 px-4 sm:px-8 lg:px-24 bg-white dark:bg-gray-950">

    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
            <!-- ط§ظ„ط´ط±ظٹط· ط§ظ„ط¬ط§ظ†ط¨ظٹ ظ„ظ„طھطµظ†ظٹظپط§طھ ظˆط§ظ„ظپظ„طھط±ط© -->
            @if($showSidebar)
                <aside class="space-y-8">
                    <!-- ط£ظ‚ط³ط§ظ… ط§ظ„ظ‚ظˆط§ظ„ط¨ -->
                    <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-xl shadow-md">
                        <h3 class="text-xl font-bold text-primary mb-3 border-b pb-2">ط£ظ‚ط³ط§ظ… ط§ظ„ظ‚ظˆط§ظ„ط¨</h3>
                        <ul class="space-y-2 text-gray-800" id="categoryFilter">
                            <li>
                                <button data-category="all" wire:click="$set('selectedCategory', 'all')" class="{{ $selectedCategory === 'all' ? 'text-primary hover:text-primary/80 font-bold' : '' }}" aria-label="ط¹ط±ط¶ ط§ظ„ظƒظ„">ط§ظ„ظƒظ„</button>
                            </li>
                            @foreach ($categories as $cat)
                                <li>
                                    <button wire:click="$set('selectedCategory', '{{ $cat->id }}')" data-category="{{ $cat->translated_name }}" class="{{ $selectedCategory == $cat->id ? 'text-primary font-bold' : '' }}" aria-label="{{ $cat->translated_name }}">{{ $cat->translated_name }}</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <!-- ط§ظ„ظپظ„طھط±ط© ط­ط³ط¨ ط§ظ„ط³ط¹ط± -->
                    <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-xl shadow-md">
                        <h3 class="text-xl font-bold text-primary mb-3 border-b pb-2">ط§ظ„ظپط±ط² ط­ط³ط¨ ط§ظ„ط³ط¹ط±</h3>
                        <label for="priceRange" class="sr-only">ط£ظ‚طµظ‰ ط³ط¹ط±</label>
                        <div class="mb-4">
                            <input id="priceRange" type="range" min="50" max="250" wire:model="maxPrice" class="w-full accent-primary">
                        </div>
                        <p class="text-sm text-gray-700">ط§ظ„ط³ط¹ط± ط§ظ„ط£ظ‚طµظ‰: <span id="priceValue">{{ $maxPrice }}</span>$</p>
                    </div>
                </aside>
            @endif
            <!-- ط´ط¨ظƒط© ط§ظ„ظ‚ظˆط§ظ„ط¨ -->
            <div class="lg:col-span-3">
                <!-- ط£ط¯ظˆط§طھ ط§ظ„ط¹ط±ط¶ -->
                <div class="flex justify-between items-center mb-6">
                    <p id="resultCount" class="text-sm text-gray-600">ط¹ط±ط¶ {{ $templates->count() }} ظ…ظ† {{ $templates->total() }} ظ†طھظٹط¬ط©</p>
                    <label for="sortSelect" class="sr-only">طھط±طھظٹط¨ ط­ط³ط¨ ط§ظ„ط³ط¹ط±</label>
                    <select id="sortSelect" class="border border-gray-300 text-sm rounded px-3 py-1">
                        <option value="default">ط§ظ„طھط±طھظٹط¨ ط§ظ„ط§ظپطھط±ط§ط¶ظٹ</option>
                        <option value="high">ط§ظ„ط£ط¹ظ„ظ‰ ط³ط¹ط±ظ‹ط§</option>
                        <option value="low">ط§ظ„ط£ظ‚ظ„ ط³ط¹ط±ظ‹ط§</option>
                    </select>
                </div>
                @if ($templates->first()?->fallbackNotice)
                    <div class="mb-6 p-4 rounded-lg bg-yellow-50 border border-yellow-300 text-yellow-800 text-sm shadow">
                        ط¨ط¹ط¶ ط§ظ„ظ…ط­طھظˆظ‰ ط؛ظٹط± ظ…طھظˆظپط± ط¨ظ„ط؛طھظƒ ط§ظ„ط­ط§ظ„ظٹط© ظˆطھظ… ط¹ط±ط¶ظ‡ ط¨ط§ظ„ظ„ط؛ط© ط§ظ„ط¹ط±ط¨ظٹط© طھظ„ظ‚ط§ط¦ظٹظ‹ط§.
                    </div>
                @endif
                <!-- ط§ظ„ط´ط¨ظƒط© -->
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6" id="templateGrid">
                    @forelse($templates as $template)
                        <a href="{{ route('template.show', $template->slug) }}" class="block group">
                            <article class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden relative group transition-transform duration-300 hover:-translate-y-2 hover:shadow-2xl animate-fade-in-up border border-primary/10" itemscope itemtype="https://schema.org/Product" lang="ar">
                                <meta itemprop="name" content="{{ $template->name }}">
                                <meta itemprop="description" content="{{ $template->description }}">
                                <meta itemprop="sku" content="template-{{ $template->id }}">
                                <meta itemprop="category" content="ظ‚ظˆط§ظ„ط¨ ظ…ظˆط§ظ‚ط¹">
                                <meta itemprop="brand" content="Palgoals">
                                <meta itemprop="priceCurrency" content="USD" />
                                <meta itemprop="price" content="{{ $template->discount_price ?? $template->price }}" />
                                <meta itemprop="availability" content="https://schema.org/InStock" />
                                <div class="relative">
                                    <img itemprop="image" src="{{ ($img = $template->resolvedImagePath()) ? asset('storage/'.$img) : '' }}" alt="{{ $template->name }}" class="w-full h-40 object-cover transition-transform duration-300 group-hover:scale-105 group-hover:brightness-95" loading="lazy" decoding="async">
                                    <div class="bg-gradient-to-tr from-secondary to-primary text-white flex items-end justify-center w-24 h-10 absolute -top-2 rtl:-left-10 ltr:-right-10 ltr:rotate-[40deg] rtl:rotate-[320deg] animate-bounce shadow-lg font-bold text-base tracking-wide">ط¬ط¯ظٹط¯</div>
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition duration-300"></div>
                                    <div class="absolute top-2 right-2 rtl:right-auto rtl:left-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <button class="bg-white/80 dark:bg-white/20 hover:bg-primary text-primary hover:text-white rounded-full p-2 shadow-md transition" title="ظ…ط¹ط§ظٹظ†ط© ط§ظ„ظ‚ط§ظ„ط¨" aria-label="ظ…ط¹ط§ظٹظ†ط© ط§ظ„ظ‚ط§ظ„ط¨">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-9 9-9 9s-9-4-9-9a9 9 0 0118 0z"/></svg>
                                        </button>
                                        <button class="bg-white/80 dark:bg-white/20 hover:bg-secondary text-secondary hover:text-white rounded-full p-2 shadow-md transition" title="ط´ط±ط§ط، ط§ظ„ظ‚ط§ظ„ط¨" aria-label="ط´ط±ط§ط، ط§ظ„ظ‚ط§ظ„ط¨">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.35 2.7A1 1 0 007 17h10a1 1 0 00.95-.68L19 13M7 13V6a1 1 0 011-1h5a1 1 0 011 1v7"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-5 rtl:text-right ltr:text-left flex flex-col items-start">
                                    <h3 itemprop="name" class="text-suptitle font-bold mb-1 text-primary/90 dark:text-white group-hover:text-secondary transition-colors leading-snug">{{ $template->name }}</h3>
                                    <p itemprop="description" class="text-suptitle font-light mb-2 text-primary/70 dark:text-gray-300">{{ $template->description }}</p>
                                    <div class="flex justify-between items-center text-sm font-bold rtl:flex-row-reverse ltr:flex-row mt-3 w-full">
                                        <div class="flex items-center gap-1" aria-label="ط§ظ„طھظ‚ظٹظٹظ… 4 ظ…ظ† 5 ظ†ط¬ظˆظ…">
                                            <span class="text-yellow-400 text-base">âک…âک…âک…âک…âک†</span>
                                        </div>
                                        <div class="flex items-center gap-2 rtl:flex-row-reverse ltr:flex-row">
                                            <span class="line-through text-suptitle text-primary/40 dark:text-gray-400">{{ $template->discount_price ? '$' . $template->price : '' }}</span>
                                            <span itemprop="price" class="text-title-h3 text-secondary dark:text-yellow-400">${{ $template->discount_price ?? $template->price }}</span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </a>
                    @empty
                        <div id="noResults" class=" text-center text-gray-500 py-10 col-span-full ">
                            ظ„ط§ طھظˆط¬ط¯ ظ‚ظˆط§ظ„ط¨ ظ…ط·ط§ط¨ظ‚ط© ظ„ظ„ظپظ„طھط±ط© ط§ظ„ط­ط§ظ„ظٹط©.
                        </div>
                    @endforelse
                </div>
                <!-- ط²ط± طھط­ظ…ظٹظ„ ط§ظ„ظ…ط²ظٹط¯ -->
                <div class="mt-10 text-center">
                    {{ $templates->links('pagination::tailwind') }}
                </div>
            </div>
        </div>
    </div>
</section>

