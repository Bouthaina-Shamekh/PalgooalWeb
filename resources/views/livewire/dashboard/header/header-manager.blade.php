<div class="space-y-6">
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.headers') }}">{{ t('dashboard.All_Menus', 'ALL Menus') }}</a></li>
                {{-- <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.All_Pages', 'ALL Pages') }}</li> --}}
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.All_Menus', 'ALL Menus') }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-6">
        <!-- [ form-element ] start -->
        <div class="col-span-12 lg:col-span-6">
            <div class="card">
                <div class="card-header">
                    <h5> {{ $mode === 'edit' ? 'تعديل العنصر' : 'إضافة عنصر جديد' }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label" for="exampleFormControlSelect1">نوع العنصر</label>
                        <select wire:model="newItem.type" class="form-select">
                            <option value="link">رابط</option>
                            <option value="dropdown">قائمة منسدلة</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الرابط:</label>
                        <input  wire:model="newItem.url" type="text" class="form-control" placeholder="مثال: /services" />
                        <small class="form-text text-muted">Please enter your full name</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الترتيب:</label>
                        <input wire:model="newItem.order" type="number" class="form-control" placeholder="Enter email" />
                        <small class="form-text text-muted">Please enter your Email</small>
                    </div>
                    <ul id="hs-basic-usage-example-sortable" class="w-full flex flex-col">
                        @foreach ($newItem['children'] ?? [] as $index => $child)
                            <li data-index="{{ $index }}" wire:key="child-{{ $index }}" class="inline-flex items-center gap-x-3 py-3 px-4 cursor-grab text-sm font-medium bg-white border border-gray-200 text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200">
                                ☰
                                <div class="flex-1 space-y-2">
                                    <div class="mb-3">
                                    <label class="form-label">الرابط:</label>
                                    <input type="text" wire:model="newItem.children.{{ $index }}.url" placeholder="الرابط (مثال: /about)" class="form-control" />
                                    </div>
                                    <div class="grid md:grid-cols-3 gap-2">
                                        @foreach ($languages as $lang)
                                        <div class="mb-3">
                                        <label class="form-label">{{ $lang->native }}:</label>
                                            <input type="text" wire:model="newItem.children.{{ $index }}.label.{{ $lang->code }}" placeholder="الاسم ({{ $lang->native }})" class="form-control" />
                                        </div>
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
                    @if ($newItem['type'] === 'dropdown')
                        <div class="mt-4">
                            <button wire:click="addChild" class="btn btn-primary mb-4">
                                + إضافة رابط فرعي
                            </button>
                        </div>
                    @endif
                    @foreach ($languages as $lang)
                        <div class="mb-3">
                            <label class="form-label">
                                الاسم ({{ $lang->native }})
                            </label>
                            <input wire:model="newItem.translations.{{ $lang->code }}" type="text" class="form-control" />
                        </div>
                    @endforeach
                    <div class="text-left mt-4">
                        @if ($mode === 'edit')
                            <button wire:click="updateItem" class="btn btn-primary mb-4">
                                حفظ التعديلات
                            </button>
                            <button wire:click="cancelEdit" class="btn btn-primary mb-4">
                                إلغاء
                            </button>
                            @else
                            <button wire:click="addItem" class="btn btn-primary mb-4">
                                إضافة
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-span-12 lg:col-span-6">
            <div class="card">
                <div class="card-header">
                    <h5>العناصر الحالية:</h5>
                </div>
                <div class="card-body">
                    <ul id="sortable-header-items" class="space-y-3 divide-y divide-gray-200">
                        @foreach ($items as $index => $item)
                            <li data-index="{{ $index }}" wire:key="item-{{ $item['id'] }}" class="cursor-grab bg-white dark:bg-gray-800 p-4 flex justify-between items-center">
                                <div>
                                    <span class="cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 text-xl mt-2 select-none">
                                        ☰  
                                    </span>
                                    <strong>{{ $item['translations'][app()->getLocale()] ?? '-' }}</strong>
                                    <span class="text-sm text-gray-500 ml-2">({{ $item['type'] }})</span>
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ $item['url'] }}</div>
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="editItem({{ $item['id'] }})" class="px-3 py-1 bg-yellow-400 text-white text-sm rounded hover:bg-yellow-500 transition">
                                        تعديل
                                    </button>
                                    <button wire:click="editItem({{ $item['id'] }})" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                        <i class="ti ti-edit text-xl leading-none"></i>
                                    </button>
                                    <button wire:click="confirmDelete({{ $item['id'] }})" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                        <i class="ti ti-trash text-xl"></i>
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <!-- [ form-element ] end -->
        </div>
        <!-- [ Main Content ] end -->
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









