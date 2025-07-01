<div class="space-y-6">
    <!-- [ breadcrumb ] start -->
    <div class="page-header bg-gray-50 py-4 px-6 rounded shadow">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.headers') }}">{{ t('dashboard.All_Menus', 'ALL Menus') }}</a></li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0 text-lg font-semibold text-gray-800">{{ t('dashboard.All_Menus', 'ALL Menus') }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <h2 class="text-xl font-bold text-primary">إدارة عناصر الهيدر</h2>

    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow space-y-4 border border-gray-200 dark:border-gray-600">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
            {{ $mode === 'edit' ? 'تعديل العنصر' : 'إضافة عنصر جديد' }}
        </h3>

        <div class="flex flex-col md:flex-row gap-4 items-center">
            <label class="w-32 text-right text-gray-700 dark:text-gray-300">نوع العنصر:</label>
            <select wire:model="newItem.type" class="form-select rounded px-3 py-2 border w-full md:w-64">
                <option value="link">رابط</option>
                <option value="dropdown">قائمة منسدلة</option>
            </select>
        </div>

        <div class="flex flex-col md:flex-row gap-4 items-center">
            <label class="w-32 text-right text-gray-700 dark:text-gray-300">الرابط:</label>
            <input wire:model="newItem.url" type="text" class="form-input rounded px-3 py-2 border w-full md:w-2/3" placeholder="مثال: /services" />
        </div>

        <div class="flex flex-col md:flex-row gap-4 items-center">
            <label class="w-32 text-right text-gray-700 dark:text-gray-300">الترتيب:</label>
            <input wire:model="newItem.order" type="number" class="form-input rounded px-3 py-2 border w-full md:w-32" />
        </div>

        <ul id="hs-basic-usage-example-sortable" class="w-full flex flex-col">
            @foreach ($newItem['children'] ?? [] as $index => $child)
            <li data-index="{{ $index }}" wire:key="child-{{ $index }}" class="inline-flex items-center gap-x-3 py-3 px-4 cursor-grab text-sm font-medium bg-white border border-gray-200 text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200">
                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                    <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                </svg>
                <div class="flex-1 space-y-2">
                    <input type="text" wire:model="newItem.children.{{ $index }}.url" placeholder="الرابط (مثال: /about)" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary text-sm dark:bg-gray-700 dark:text-white" />
                    <div class="grid md:grid-cols-3 gap-2">
                        @foreach ($languages as $lang)
                            <input type="text" wire:model="newItem.children.{{ $index }}.label.{{ $lang->code }}" placeholder="الاسم ({{ $lang->native }})" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary text-sm dark:bg-gray-700 dark:text-white" />
                        @endforeach
                    </div>
                    <button wire:click.prevent="removeChild({{ $index }})" class="inline-flex items-center gap-1 text-red-600 hover:text-red-700 hover:underline text-sm transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        حذف
                    </button>
                </div>
            </li>
            @endforeach
        </ul>

        <div class="mt-4">
            <button wire:click="addChild" class="inline-flex items-center gap-2 bg-primary hover:bg-secondary text-white font-semibold px-5 py-2 text-sm rounded shadow transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                إضافة رابط فرعي
            </button>
        </div>

        @foreach ($languages as $lang)
            <div class="flex flex-col md:flex-row gap-4 items-center">
                <label class="w-32 text-right text-gray-700 dark:text-gray-300">
                    الاسم ({{ $lang->native }})
                </label>
                <input wire:model="newItem.translations.{{ $lang->code }}" type="text" class="form-input rounded px-3 py-2 border w-full md:w-2/3" />
            </div>
        @endforeach

        <div class="text-left mt-4">
            @if ($mode === 'edit')
                <button wire:click="updateItem" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-6 rounded transition">
                    حفظ التعديلات
                </button>
                <button wire:click="cancelEdit" class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded transition">
                    إلغاء
                </button>
            @else
                <button wire:click="addItem" class="bg-primary hover:bg-secondary text-white font-bold py-2 px-6 rounded transition">
                    إضافة
                </button>
            @endif
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow border border-gray-200 dark:border-gray-600">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">العناصر الحالية:</h3>
        </div>
        <ul id="sortable-header-items" class="space-y-3 divide-y divide-gray-200">
            @foreach ($items as $index => $item)
                <li data-index="{{ $index }}" wire:key="item-{{ $item['id'] }}" class="cursor-grab bg-white dark:bg-gray-800 p-4 flex justify-between items-center">
                    <div>
                        <strong>{{ $item['translations'][app()->getLocale()] ?? '-' }}</strong>
                        <span class="text-sm text-gray-500 ml-2">({{ $item['type'] }})</span>
                        <div class="text-sm text-gray-600 dark:text-gray-300">{{ $item['url'] }}</div>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="editItem({{ $item['id'] }})" class="px-3 py-1 bg-yellow-400 text-white text-sm rounded hover:bg-yellow-500 transition">
                            تعديل
                        </button>
                        <button wire:click="confirmDelete({{ $item['id'] }})" class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600 transition">
                            حذف
                        </button>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    function initSortableChildren() {
        const el = document.getElementById('hs-basic-usage-example-sortable');
        if (!el || el.dataset.sortableInitialized) return;

        new Sortable(el, {
            animation: 150,
            onEnd: function () {
                const order = Array.from(el.children).map(child => child.dataset.index);
                // استدعاء مباشر على الكومبوننت
                Livewire.find(el.closest('[wire\\:id]').getAttribute('wire:id'))
                        .call('reorderChildren', order);
            }
        });

        el.dataset.sortableInitialized = "true";
    }

    initSortableChildren();

    document.addEventListener('livewire:load', () => {
        Livewire.hook('message.processed', initSortableChildren);
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    function initSortableItems() {
        const el = document.getElementById('sortable-header-items');
        if (!el || el.dataset.sortableInitialized) return;

        new Sortable(el, {
            animation: 150,
            handle: '.cursor-grab',
            onEnd: function () {
                const order = Array.from(el.children).map(child => child.dataset.index);
                const ids = Array.from(el.children).map(child => child.getAttribute('wire:key').replace('item-', ''));
                Livewire.find(el.closest('[wire\\:id]').getAttribute('wire:id'))
                        .call('reorderItems', ids);
            }
        });

        el.dataset.sortableInitialized = "true";
    }

    initSortableItems();

    document.addEventListener('livewire:load', () => {
        Livewire.hook('message.processed', initSortableItems);
    });
});
</script>









