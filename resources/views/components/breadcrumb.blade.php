<!-- Breadcrumb -->
    @if (!empty($items))
        <nav class="text-sm text-gray-200 animate-fade-in" aria-label="breadcrumb">
            <ol class="inline-flex items-center justify-center gap-2 rtl:space-x-reverse">
                @foreach ($items as $index => $item)
                    <li>
                        @if (!isset($item['url']) || $loop->last)
                            <span class="text-white font-semibold">{{ $item['title'] }}</span>
                            @else
                            <a href="{{ $item['url'] }}" class="hover:text-secondary transition-colors font-medium">
                                {{ $item['title'] }}
                            </a>
                        @endif
                    </li>
                    @if (!$loop->last)
                        <li>
                            <svg class="mx-1 w-4 h-4 text-gray-300 inline-block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>
    @endif

