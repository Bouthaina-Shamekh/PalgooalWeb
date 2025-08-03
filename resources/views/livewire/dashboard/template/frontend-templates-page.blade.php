<div>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6" id="templateGrid">
    @forelse ($templates as $template)
        <a href="{{ route('templates.show', $template->slug) }}" class="block group">
            <article class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden relative group transition-transform duration-300 hover:-translate-y-2 hover:shadow-2xl">
                <div class="relative">
                    <img src="{{ asset('storage/' . $template->image) }}" alt="{{ $template->name }}" class="w-full h-40 object-cover">
                </div>
                <div class="p-5 rtl:text-right ltr:text-left">
                    <h3 class="text-suptitle font-bold mb-1 text-primary dark:text-white">{{ $template->name }}</h3>
                    <p class="text-sm text-gray-600">{{ $template->description }}</p>
                    <div class="flex justify-between items-center text-sm font-bold mt-3">
                        @if ($template->discount_price)
                            <span class="line-through text-gray-400">${{ number_format($template->price) }}</span>
                            <span class="text-secondary">${{ number_format($template->discount_price) }}</span>
                        @else
                            <span class="text-primary">${{ number_format($template->price) }}</span>
                        @endif
                    </div>
                </div>
            </article>
        </a>
    @empty
        <div class="col-span-full text-center text-gray-500 py-10">
            لا توجد قوالب مطابقة للفلترة الحالية.
        </div>
    @endforelse
</div>

<div class="mt-10">
    {{ $templates->links() }}
</div>
</div>

{{-- <script src="{{ asset('assets/tamplate/js/template.js') }}" defer></script> --}}
