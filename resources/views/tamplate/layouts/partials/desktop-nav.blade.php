<!-- Desktop Navigation -->
<nav role="navigation" aria-label="القائمة الرئيسية">
    <ul class="hidden md:flex items-center gap-7 font-semibold text-primary dark:text-white text-base">
        @forelse ($header->items ?? [] as $item)
            @if ($item->type === 'link')
                <li><a href="{{ $item->url }}" class="hover:text-secondary dark:hover:text-yellow-400 transition">{{ $item->label }}</a></li>
            @elseif ($item->type === 'dropdown')
                <li class="relative group">
                    <div class="flex items-center gap-1 cursor-pointer hover:text-secondary dark:hover:text-yellow-400 transition">
                        <span>{{ $item->label }}</span>
                        <svg class="w-4 h-4 mt-0.5 transform transition-transform group-hover:rotate-180"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <ul class="absolute top-full end-0 mt-2 w-48 bg-white dark:bg-[#2c2c2c] border border-gray-200 dark:border-gray-700
                        rounded-lg shadow-md z-50 text-sm font-normal flex-col opacity-0 invisible scale-95
                        group-hover:opacity-100 group-hover:visible group-hover:scale-100 transition-all duration-200">
                        @foreach ($item->children ?? [] as $child)
                            <li>
                                <a href="{{ $child['url'] ?? '#' }}"
                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/20">
                                    {{ $child['label'][app()->getLocale()] ?? $child['label']['ar'] ?? '-' }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endif
            @empty
                <li class="text-sm text-gray-500 dark:text-gray-400 italic">
                    لا توجد قوائم حالياً — يمكنك إضافتها من لوحة التحكم
                </li>
        @endforelse
    </ul>
</nav>