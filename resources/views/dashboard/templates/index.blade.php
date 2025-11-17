<x-dashboard-layout>
    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

        {{-- Header + Actions --}}
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">جميع القوالب</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    عدد القوالب الحالية:
                    <span class="font-semibold">{{ $templates->total() }}</span>
                </p>
            </div>

            <a href="{{ route('dashboard.templates.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white text-sm font-semibold shadow hover:bg-primary/90 transition">
                <span class="text-lg leading-none">➕</span>
                <span>إضافة قالب جديد</span>
            </a>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Table wrapper --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-right">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">الاسم</th>
                            <th class="px-4 py-3">الصورة</th>
                            <th class="px-4 py-3">السعر</th>
                            <th class="px-4 py-3">التصنيف</th>
                            <th class="px-4 py-3">خيارات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        @forelse($templates as $template)
                            @php
                                // محاولة جلب الاسم من دالة translation() إن وجدت، وإلا من أول ترجمة
                                $name = $template->translation()?->name
                                    ?? optional($template->translations->first())->name
                                    ?? '—';

                                $categoryName = $template->categoryTemplate->translation?->name ?? '—';

                                $imageUrl = $template->image
                                    ? asset('storage/' . $template->image)
                                    : null;
                            @endphp

                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-900/40">
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $template->id }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $name }}
                                        </span>

                                        @if (!empty($template->plan_id))
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                الخطة: {{ optional($template->plan)->name ?? $template->plan_id }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    @if($imageUrl)
                                        <div class="w-20 h-14 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                                            <img src="{{ $imageUrl }}"
                                                 alt="صورة القالب"
                                                 class="w-full h-full object-cover">
                                        </div>
                                    @else
                                        <div class="w-20 h-14 flex items-center justify-center rounded-lg border border-dashed border-gray-300 dark:border-gray-600 text-xs text-gray-400">
                                            لا صورة
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-col items-start gap-0.5">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ number_format($template->price, 2) }} $
                                        </span>

                                        @if (!is_null($template->discount_price) && $template->discount_price > 0 && $template->discount_price < $template->price)
                                            <span class="text-xs text-red-600 dark:text-red-400">
                                                خصم: {{ number_format($template->discount_price, 2) }} $
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $categoryName }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('dashboard.templates.edit', $template->id) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-200 dark:hover:bg-blue-900 transition">
                                            تعديل
                                        </a>

                                        <form action="{{ route('dashboard.templates.destroy', $template->id) }}"
                                              method="POST"
                                              class="inline-block"
                                              onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/40 dark:text-red-200 dark:hover:bg-red-900 transition">
                                                حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    لا توجد قوالب بعد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $templates->links() }}
        </div>
    </div>
</x-dashboard-layout>
