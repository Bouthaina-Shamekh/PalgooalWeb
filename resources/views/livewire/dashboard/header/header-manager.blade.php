<div class="space-y-6">
    {{-- رسالة نجاح --}}
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <h2 class="text-xl font-bold text-primary">إدارة عناصر الهيدر</h2>

    {{-- نموذج إضافة عنصر جديد --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow space-y-4 border border-gray-200 dark:border-gray-600">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">إضافة عنصر جديد</h3>

        {{-- نوع العنصر --}}
        <div class="flex flex-col md:flex-row gap-4 items-center">
            <label class="w-32 text-right text-gray-700 dark:text-gray-300">نوع العنصر:</label>
            <select wire:model="newItem.type" class="form-select rounded px-3 py-2 border w-full md:w-64">
                <option value="link">رابط</option>
                <option value="dropdown">قائمة منسدلة</option>
            </select>
        </div>

        {{-- الرابط --}}
        <div class="flex flex-col md:flex-row gap-4 items-center">
            <label class="w-32 text-right text-gray-700 dark:text-gray-300">الرابط:</label>
            <input wire:model="newItem.url" type="text" class="form-input rounded px-3 py-2 border w-full md:w-2/3" placeholder="مثال: /services" />
        </div>

        {{-- الترتيب --}}
        <div class="flex flex-col md:flex-row gap-4 items-center">
            <label class="w-32 text-right text-gray-700 dark:text-gray-300">الترتيب:</label>
            <input wire:model="newItem.order" type="number" class="form-input rounded px-3 py-2 border w-full md:w-32" />
        </div>

        {{-- الترجمة لكل لغة --}}
        @foreach ($languages as $lang)
            <div class="flex flex-col md:flex-row gap-4 items-center">
                <label class="w-32 text-right text-gray-700 dark:text-gray-300">
                    الاسم ({{ $lang->native }})
                </label>
                <input wire:model="newItem.translations.{{ $lang->code }}" type="text" class="form-input rounded px-3 py-2 border w-full md:w-2/3" />
            </div>
        @endforeach

        <div class="text-left">
            <button wire:click="addItem"
                class="bg-primary hover:bg-secondary text-white font-bold py-2 px-6 rounded transition">
                إضافة
            </button>
        </div>
    </div>

    {{-- عرض العناصر الحالية --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow border border-gray-200 dark:border-gray-600">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">العناصر الحالية:</h3>

        <ul class="space-y-3">
            @forelse ($items as $item)
                <li class="border border-gray-300 dark:border-gray-600 rounded p-4 flex justify-between items-center">
                    <div>
                        <strong>{{ $item['translations'][app()->getLocale()] ?? '-' }}</strong>
                        <span class="text-sm text-gray-500 ml-2">({{ $item['type'] }})</span>
                        <div class="text-sm text-gray-600 dark:text-gray-300">{{ $item['url'] }}</div>
                    </div>
                    {{-- لاحقًا يمكن إضافة زر تعديل وحذف --}}
                </li>
            @empty
                <li class="text-gray-600 dark:text-gray-300">لا توجد عناصر حتى الآن.</li>
            @endforelse
        </ul>
    </div>
</div>


