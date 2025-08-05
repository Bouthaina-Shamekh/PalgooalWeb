<!-- Breadcrumb -->
@if (!empty($items))
    <div class="relative z-10 mt-6">
        <nav class="text-sm text-white/80" aria-label="Breadcrumb">
            <ol class="flex flex-wrap justify-center items-center gap-2 ">
                @foreach ($items as $index => $item)
                    <li>
                        @if (!isset($item['url']) || $loop->last)
                            <span class="text-white font-semibold">{{ $item['title'] }}</span>
                            @else
                            <a href="{{ $item['url'] }}" class="hover:underline text-white font-semibold">{{ $item['title'] }}</a>
                        @endif
                    </li>
                    @if (!$loop->last)
                        <span class="mx-2">/</span>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>
@endif


            



