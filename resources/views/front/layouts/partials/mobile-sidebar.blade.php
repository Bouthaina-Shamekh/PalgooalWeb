    <!-- Sidebar Mobile Menu -->
    <div id="mobileSidebar"
        class="fixed inset-y-0 end-0 w-72 bg-white dark:bg-[#1c1c1c] shadow-xl z-50 text-primary dark:text-white font-semibold text-base flex-col md:hidden transform translate-x-full transition-transform duration-300 ease-in-out invisible opacity-0">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <span class="text-lg font-bold">القائمة</span>
            <button id="sidebar-close" class="text-xl hover:text-secondary dark:hover:text-yellow-400"
                aria-label="إغلاق القائمة">&times;</button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            @forelse ($header->items ?? [] as $item)
                @if ($item->type === 'link' || $item->type === 'page')
                    <a href="{{ $item->url }}"
                        class="block text-lg hover:text-secondary dark:hover:text-yellow-400">{{ $item->label }}</a>
                @elseif ($item->type === 'dropdown')
                    <div class="relative">
                        <button type="button" aria-expanded="false"
                            class="w-full flex items-center justify-between text-lg text-right hover:text-secondary dark:hover:text-yellow-400 transition"
                            onclick="toggleMobileDropdown(this)">
                            {{ $item->label }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="hidden mt-2 space-y-1 text-sm transition-all duration-300 ease-in-out">
                            @foreach ($item->processedChildren as $child)
                                <a href="{{ $child['current_url'] ?? '#' }}"
                                    class="block py-1 hover:text-secondary dark:hover:text-yellow-400">
                                    {{ $child['current_label'] ?? '-' }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @empty
                <div class="text-sm text-gray-500 dark:text-gray-400 italic">
                    لا توجد قوائم حالياً
                </div>
            @endforelse
            <!-- User Menu مباشر للجوال -->
            <div class="mt-4 border-t pt-4 border-gray-200 dark:border-gray-700">
                <a href="/login"
                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/10 text-primary dark:text-white">تسجيل
                    الدخول</a>
                <a href="/register"
                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/10 text-primary dark:text-white">إنشاء
                    حساب</a>
            </div>
        </div>
    </div>
