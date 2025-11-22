<div class="space-y-6">
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="{{ route('dashboard.headers') }}">{{ t('dashboard.All_Menus', 'ALL Menus') }}</a></li>
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
                    <h5 class="mb-0 flex items-center gap-2">
                        @if ($mode === 'edit')
                            <i class="ti ti-edit text-warning"></i>
                            تعديل العنصر
                        @else
                            <i class="ti ti-plus text-primary"></i>
                            إضافة عنصر جديد
                        @endif
                    </h5>
                </div>
                <div class="card-body space-y-4">
                    <!-- نوع العنصر -->
                    <div>
                        <label class="form-label">نوع العنصر</label>
                        <select wire:model.live="newItem.type" class="form-select">
                            <option value="link">رابط مباشر</option>
                            <option value="page">ربط بصفحة</option>
                            <option value="dropdown">قائمة منسدلة</option>
                        </select>
                    </div>

                    <!-- الترتيب -->
                    <div>
                        <label class="form-label">الترتيب</label>
                        <input wire:model="newItem.order" type="number" class="form-control"
                            placeholder="رقم الترتيب" />
                    </div>

                    @if ($newItem['type'] === 'page')
                        <!-- ربط بصفحة -->
                        <div>
                            <label class="form-label">اختر الصفحة</label>
                            <select wire:model.live="newItem.page_id" class="form-select">
                                <option value="">-- اختر صفحة --</option>
                                @foreach ($pages as $page)
                                    @php
                                        $pageTranslation = $page->translation();
                                    @endphp
                                    @if ($pageTranslation)
                                        <option value="{{ $page->id }}">
                                            {{ $pageTranslation->title }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @elseif ($newItem['type'] === 'link')
                        <!-- رابط مباشر -->
                        <div>
                            <h6 class="mb-3">ترجمات العنصر</h6>

                            <ul class="flex border-b mb-4 space-x-2 rtl:space-x-reverse" role="tablist">
                                @foreach ($languages as $lang)
                                    <li>
                                        <button type="button" wire:click="$set('activeLang', '{{ $lang->code }}')"
                                            id="lang-tab-{{ $lang->code }}" role="tab"
                                            aria-controls="lang-panel-{{ $lang->code }}"
                                            aria-selected="{{ $activeLang === $lang->code ? 'true' : 'false' }}"
                                            @class([
                                                'px-4 py-2 rounded-t transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400',
                                                'bg-white text-slate-900 shadow-sm border border-slate-200 border-b-white font-semibold' =>
                                                    $activeLang === $lang->code,
                                                'bg-slate-100 text-slate-500 hover:bg-slate-200 border border-transparent' =>
                                                    $activeLang !== $lang->code,
                                            ])>
                                            {{ $lang->name }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>

                            @foreach ($languages as $lang)
                                <div wire:key="header-lang-{{ $lang->code }}" id="lang-panel-{{ $lang->code }}"
                                    role="tabpanel" aria-labelledby="lang-tab-{{ $lang->code }}">
                                    @if ($activeLang === $lang->code)
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block mb-1 font-semibold">اسم العنصر
                                                    ({{ $lang->native }})
                                                </label>
                                                <input wire:model="newItem.translations.{{ $lang->code }}.label"
                                                    type="text" class="w-full border p-2 rounded mb-1"
                                                    placeholder="أدخل اسم العنصر" />
                                            </div>
                                            <div>
                                                <label class="block mb-1 font-semibold">الرابط
                                                    ({{ $lang->native }})</label>
                                                <input wire:model="newItem.translations.{{ $lang->code }}.url"
                                                    type="text" class="w-full border p-2 rounded mb-1"
                                                    placeholder="مثال: /services" />
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @elseif ($newItem['type'] === 'dropdown')
                        <!-- قائمة منسدلة -->
                        <div>
                            <!-- اسم القائمة المنسدلة -->
                            <div class="mb-4">
                                <h6 class="mb-3">اسم القائمة المنسدلة</h6>

                                <ul class="flex border-b mb-4 space-x-2 rtl:space-x-reverse" role="tablist">
                                    @foreach ($languages as $lang)
                                        <li>
                                            <button type="button"
                                                wire:click="$set('activeLang', '{{ $lang->code }}')"
                                                id="dropdown-lang-tab-{{ $lang->code }}" role="tab"
                                                aria-controls="dropdown-lang-panel-{{ $lang->code }}"
                                                aria-selected="{{ $activeLang === $lang->code ? 'true' : 'false' }}"
                                                @class([
                                                    'px-4 py-2 rounded-t transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400',
                                                    'bg-white text-slate-900 shadow-sm border border-slate-200 border-b-white font-semibold' =>
                                                        $activeLang === $lang->code,
                                                    'bg-slate-100 text-slate-500 hover:bg-slate-200 border border-transparent' =>
                                                        $activeLang !== $lang->code,
                                                ])>
                                                {{ $lang->name }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>

                                @foreach ($languages as $lang)
                                    <div wire:key="dropdown-lang-{{ $lang->code }}"
                                        id="dropdown-lang-panel-{{ $lang->code }}" role="tabpanel"
                                        aria-labelledby="dropdown-lang-tab-{{ $lang->code }}">
                                        @if ($activeLang === $lang->code)
                                            <div class="space-y-4">
                                                <div>
                                                    <label class="block mb-1 font-semibold">اسم القائمة
                                                        ({{ $lang->native }})
                                                    </label>
                                                    <input wire:model="newItem.translations.{{ $lang->code }}.label"
                                                        type="text" class="w-full border p-2 rounded mb-1"
                                                        placeholder="أدخل اسم القائمة المنسدلة" />
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <!-- العناصر الفرعية -->
                            <div class="mb-4">
                                <h6 class="mb-3">العناصر الفرعية</h6>
                                <div class="d-flex gap-2 mb-3">
                                    <button wire:click="addChild('link')" class="btn btn-primary btn-sm">
                                        إضافة رابط فرعي
                                    </button>
                                    <button wire:click="addChild('page')" class="btn btn-success btn-sm">
                                        إضافة صفحة فرعية
                                    </button>
                                </div>
                            </div>

                            <div id="hs-basic-usage-example-sortable" class="space-y-3">
                                @foreach ($newItem['children'] ?? [] as $index => $child)
                                    <div data-index="{{ $index }}" wire:key="child-{{ $index }}"
                                        class="card card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="cursor-grab text-muted fs-4">☰</span>
                                            <div class="flex-fill ms-3">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label class="form-label">نوع العنصر الفرعي</label>
                                                        <select
                                                            wire:model.live="newItem.children.{{ $index }}.type"
                                                            class="form-select">
                                                            <option value="link">رابط مباشر</option>
                                                            <option value="page">ربط بصفحة</option>
                                                        </select>
                                                    </div>

                                                    @if (($child['type'] ?? 'link') === 'page')
                                                        <div class="col-12">
                                                            <label class="form-label">اختر الصفحة</label>
                                                            <select
                                                                wire:model.live="newItem.children.{{ $index }}.page_id"
                                                                class="form-select">
                                                                <option value="">-- اختر صفحة --</option>
                                                                @foreach ($pages as $page)
                                                                    @php
                                                                        $pageTranslation = $page->translation();
                                                                    @endphp
                                                                    @if ($pageTranslation)
                                                                        <option value="{{ $page->id }}">
                                                                            {{ $pageTranslation->title }}</option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @else
                                                        <!-- ترجمات العنصر الفرعي -->
                                                        <div class="col-12">
                                                            <label class="form-label fw-bold mb-2">ترجمات العنصر
                                                                الفرعي:</label>

                                                            <ul class="flex border-b mb-3 space-x-1 rtl:space-x-reverse"
                                                                role="tablist">
                                                                @foreach ($languages as $childLang)
                                                                    <li>
                                                                        <button type="button"
                                                                            wire:click="$set('activeLang', '{{ $childLang->code }}')"
                                                                            id="child-lang-tab-{{ $index }}-{{ $childLang->code }}"
                                                                            role="tab"
                                                                            aria-controls="child-lang-panel-{{ $index }}-{{ $childLang->code }}"
                                                                            aria-selected="{{ $activeLang === $childLang->code ? 'true' : 'false' }}"
                                                                            @class([
                                                                                'px-3 py-1 rounded-t text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400',
                                                                                'bg-white text-slate-900 shadow-sm border border-slate-200 border-b-white font-semibold' =>
                                                                                    $activeLang === $childLang->code,
                                                                                'bg-slate-100 text-slate-500 hover:bg-slate-200 border border-transparent' =>
                                                                                    $activeLang !== $childLang->code,
                                                                            ])>
                                                                            {{ $childLang->name }}
                                                                        </button>
                                                                    </li>
                                                                @endforeach
                                                            </ul>

                                                            @foreach ($languages as $childLang)
                                                                <div wire:key="child-{{ $index }}-lang-{{ $childLang->code }}"
                                                                    id="child-lang-panel-{{ $index }}-{{ $childLang->code }}"
                                                                    role="tabpanel"
                                                                    aria-labelledby="child-lang-tab-{{ $index }}-{{ $childLang->code }}">
                                                                    @if ($activeLang === $childLang->code)
                                                                        <div class="space-y-3 p-3 border rounded">
                                                                            <div>
                                                                                <label
                                                                                    class="block mb-1 font-semibold text-sm">اسم
                                                                                    العنصر
                                                                                    ({{ $childLang->native }})
                                                                                </label>
                                                                                <input type="text"
                                                                                    wire:model="newItem.children.{{ $index }}.labels.{{ $childLang->code }}.label"
                                                                                    class="w-full border p-2 rounded text-sm"
                                                                                    placeholder="اسم العنصر بـ {{ $childLang->native }}" />
                                                                            </div>
                                                                            <div>
                                                                                <label
                                                                                    class="block mb-1 font-semibold text-sm">الرابط
                                                                                    ({{ $childLang->native }})</label>
                                                                                <input type="text"
                                                                                    wire:model="newItem.children.{{ $index }}.labels.{{ $childLang->code }}.url"
                                                                                    class="w-full border p-2 rounded text-sm"
                                                                                    placeholder="رابط بـ {{ $childLang->native }}" />
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <button wire:click.prevent="removeChild({{ $index }})"
                                                class="btn btn-link text-danger p-0 ms-2">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if (empty($newItem['children']))
                                <div class="text-center text-muted py-4">
                                    <p>لم يتم إضافة أي عناصر فرعية بعد</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- أزرار الإجراءات -->
                    <div class="text-end mt-4 pt-3 border-top">
                        @if ($mode === 'edit')
                            <button wire:click="updateItem" class="btn btn-success me-2">
                                حفظ التعديلات
                            </button>
                            <button wire:click="cancelEdit" class="btn btn-secondary">
                                إلغاء
                            </button>
                        @else
                            <button wire:click="addItem" class="btn btn-primary">
                                إضافة العنصر
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- العناصر الحالية -->
        <div class="col-span-12 lg:col-span-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-list text-primary"></i>
                        العناصر الحالية
                    </h5>
                    <span class="badge bg-primary">{{ count($items) }} عنصر</span>
                </div>
                <div class="card-body">
                    @if (count($items) > 0)
                        <div class="mb-3 p-3 bg-light rounded">
                            <small class="text-muted d-flex align-items-center gap-2">
                                <i class="ti ti-info-circle"></i>
                                يمكنك إعادة ترتيب العناصر بالسحب والإفلات
                            </small>
                        </div>

                        <div id="sortable-header-items" class="space-y-3">
                            @foreach ($items as $index => $item)
                                <div data-index="{{ $index }}" wire:key="item-{{ $item['id'] }}"
                                    class="card border border-gray-200 hover:border-primary transition-colors">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <!-- محتوى العنصر -->
                                            <div class="d-flex align-items-start gap-3 flex-grow-1">
                                                <!-- أيقونة السحب -->
                                                <div class="cursor-grab text-muted hover:text-primary transition-colors pt-1"
                                                    title="اسحب لإعادة الترتيب">
                                                    <i class="ti ti-grip-vertical fs-5"></i>
                                                </div>

                                                <!-- أيقونة النوع -->
                                                <div class="pt-1">
                                                    @if ($item['type'] === 'link')
                                                        <div
                                                            class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg d-flex align-items-center justify-content-center">
                                                            <i class="ti ti-link text-sm"></i>
                                                        </div>
                                                    @elseif ($item['type'] === 'page')
                                                        <div
                                                            class="w-8 h-8 bg-green-100 text-green-600 rounded-lg d-flex align-items-center justify-content-center">
                                                            <i class="ti ti-file-text text-sm"></i>
                                                        </div>
                                                    @elseif ($item['type'] === 'dropdown')
                                                        <div
                                                            class="w-8 h-8 bg-purple-100 text-purple-600 rounded-lg d-flex align-items-center justify-content-center">
                                                            <i class="ti ti-chevron-down text-sm"></i>
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- معلومات العنصر -->
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-semibold">
                                                        @if ($item['type'] === 'page' && $item['page_title'])
                                                            {{ $item['page_title'] }}
                                                        @else
                                                            {{ $item['translations'][app()->getLocale()]['label'] ?? 'بدون عنوان' }}
                                                        @endif
                                                    </h6>

                                                    <!-- نوع العنصر -->
                                                    <div class="mb-2">
                                                        @if ($item['type'] === 'link')
                                                            <span class="badge bg-blue-subtle text-blue-emphasis">
                                                                <i class="ti ti-link me-1"></i>رابط مباشر
                                                            </span>
                                                        @elseif ($item['type'] === 'page')
                                                            <span class="badge bg-green-subtle text-green-emphasis">
                                                                <i class="ti ti-file-text me-1"></i>صفحة مربوطة
                                                            </span>
                                                        @elseif ($item['type'] === 'dropdown')
                                                            <span class="badge bg-purple-subtle text-purple-emphasis">
                                                                <i class="ti ti-chevron-down me-1"></i>قائمة منسدلة
                                                                @if (!empty($item['children']))
                                                                    <small>({{ count($item['children']) }} عنصر
                                                                        فرعي)</small>
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <!-- الرابط -->
                                                    <small class="text-muted d-block">
                                                        @if ($item['type'] === 'page' && $item['page_title'])
                                                            <i class="ti ti-external-link me-1"></i>
                                                            رابط تلقائي من الصفحة
                                                        @elseif ($item['type'] === 'dropdown')
                                                            <i class="ti ti-info-circle me-1"></i>
                                                            قائمة تحتوي على عناصر فرعية
                                                        @else
                                                            <i class="ti ti-link me-1"></i>
                                                            {{ $item['translations'][app()->getLocale()]['url'] ?? 'لا يوجد رابط' }}
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>

                                            <!-- أزرار الإجراءات -->
                                            <div class="d-flex gap-1">
                                                <button wire:click="editItem({{ $item['id'] }})"
                                                    class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1"
                                                    title="تعديل العنصر">
                                                    <i class="ti ti-edit"></i>
                                                    <span class="d-none d-sm-inline">تعديل</span>
                                                </button>
                                                <button wire:click="confirmDelete({{ $item['id'] }})"
                                                    class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1"
                                                    title="حذف العنصر">
                                                    <i class="ti ti-trash"></i>
                                                    <span class="d-none d-sm-inline">حذف</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="ti ti-list-x display-4 text-muted"></i>
                            </div>
                            <h6 class="text-muted mb-2">لا توجد عناصر حالياً</h6>
                            <p class="text-muted mb-3">ابدأ بإضافة عناصر جديدة للقائمة من القسم الجانبي</p>
                            <small class="text-muted">
                                يمكنك إضافة روابط مباشرة، صفحات مربوطة، أو قوائم منسدلة
                            </small>
                        </div>
                    @endif
                </div>
            </div>
            <!-- [ form-element ] end -->
        </div>
        <!-- [ Main Content ] end -->
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            function initSortableChildren() {
                const el = document.getElementById('hs-basic-usage-example-sortable');
                if (!el || el.dataset.sortableInitialized) return;
                new Sortable(el, {
                    animation: 150,
                    onEnd: function() {
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
                    animation: 200,
                    handle: '.cursor-grab',
                    ghostClass: 'opacity-50',
                    chosenClass: 'shadow-lg border-primary',
                    onStart: function(evt) {
                        evt.item.style.transform = 'rotate(2deg)';
                    },
                    onEnd: function(evt) {
                        evt.item.style.transform = '';
                        const order = Array.from(el.children).map(child => child.dataset.index);
                        const ids = Array.from(el.children).map(child => child.getAttribute('wire:key')
                            .replace('item-', ''));
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

    <style>
        .w-8 {
            width: 2rem;
        }

        .h-8 {
            height: 2rem;
        }

        .space-y-3>*+* {
            margin-top: 0.75rem;
        }

        .cursor-grab:hover {
            cursor: grab;
        }

        .cursor-grab:active {
            cursor: grabbing;
        }

        .sortable-ghost {
            opacity: 0.5;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
        }

        #sortable-header-items .card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            transition: all 0.2s ease;
        }

        .badge {
            font-size: 0.75rem;
        }

        .bg-blue-subtle {
            background-color: #e7f3ff;
        }

        .text-blue-emphasis {
            color: #0056b3;
        }

        .bg-green-subtle {
            background-color: #d1e7dd;
        }

        .text-green-emphasis {
            color: #0a3622;
        }

        .bg-purple-subtle {
            background-color: #e2d9f3;
        }

        .text-purple-emphasis {
            color: #432874;
        }

        .bg-blue-100 {
            background-color: #dbeafe;
        }

        .text-blue-600 {
            color: #2563eb;
        }

        .bg-green-100 {
            background-color: #dcfce7;
        }

        .text-green-600 {
            color: #16a34a;
        }

        .bg-purple-100 {
            background-color: #f3e8ff;
        }

        .text-purple-600 {
            color: #9333ea;
        }
    </style>
