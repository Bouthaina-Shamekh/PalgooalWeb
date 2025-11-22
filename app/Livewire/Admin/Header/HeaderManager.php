<?php

namespace App\Livewire\Admin\Header;

use App\Models\Header;
use App\Models\HeaderItem;
use App\Models\HeaderItemTranslation;
use App\Models\Language;
use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Livewire;

class HeaderManager extends Component
{
    public Header $header;
    public string $mode = 'create';
    public int|null $editingId = null;
    public string $activeLang = 'ar';
    protected $listeners = ['reorderChildren'];

    public Collection $languages;
    public Collection $pages;

    public array $items = [];

    public array $newItem = [
        'type' => 'link',
        'order' => 0,
        'page_id' => null,
        'translations' => [],
        'children' => [],
    ];

    public function mount()
    {
        $this->languages = Language::all();
        $this->pages = Page::with('translations')->where('is_active', true)->get();

        $this->header = Header::firstOrCreate(['name' => 'Main Header']);

        $this->loadItems();
    }

    public function loadItems()
    {
        $items = $this->header->items()->with(['page.translations', 'translations'])->orderBy('order')->get();
        $this->items = $items->map(function ($item) {
            $itemData = [
                'id' => $item->id,
                'type' => $item->type,
                'page_id' => $item->page_id,
                'page_title' => $item->page_id ? ($item->page?->translation()?->title ?? 'صفحة محذوفة') : null,
                'order' => $item->order,
                'translations' => $item->translations->mapWithKeys(function ($translation) {
                    return [$translation->locale => [
                        'label' => $translation->label,
                        'url' => $translation->url
                    ]];
                })->toArray(),
                'children' => [],
            ];

            // معالجة العناصر الفرعية إذا وجدت
            if ($item->type === 'dropdown' && $item->children) {
                $children = [];
                foreach ($item->children as $child) {
                    $childData = [
                        'type' => $child['type'] ?? 'link',
                        'page_id' => $child['page_id'] ?? null,
                        'labels' => $child['labels'] ?? [],
                    ];

                    // إذا كان العنصر الفرعي مربوط بصفحة، جلب بيانات الصفحة
                    if (($child['type'] ?? 'link') === 'page' && !empty($child['page_id'])) {
                        $page = Page::with('translations')->find($child['page_id']);
                        if ($page) {
                            foreach ($this->languages as $lang) {
                                $pageTranslation = $page->translations->where('locale', $lang->code)->first();
                                if ($pageTranslation) {
                                    $childData['labels'][$lang->code] = [
                                        'label' => $pageTranslation->title,
                                        'url' => $pageTranslation->slug ? '/' . $pageTranslation->slug : '#'
                                    ];
                                }
                            }
                        }
                    }

                    $children[] = $childData;
                }
                $itemData['children'] = $children;
            }

            return $itemData;
        })->toArray();
    }

    public function addItem()
    {
        $item = $this->header->items()->create([
            'type' => $this->newItem['type'],
            'page_id' => $this->newItem['type'] === 'page' ? $this->newItem['page_id'] : null,
            'order' => $this->newItem['order'],
            'children' => $this->newItem['type'] === 'dropdown' ? $this->newItem['children'] : null,
        ]);


        // إذا كان مربوط بصفحة، استخدم ترجمات الصفحة
        if ($this->newItem['type'] === 'page' && $this->newItem['page_id']) {
            $page = Page::with('translations')->find($this->newItem['page_id']);
            if ($page) {
                foreach ($this->languages as $lang) {
                    $pageTranslation = $page->translations->where('locale', $lang->code)->first();
                    HeaderItemTranslation::create([
                        'header_item_id' => $item->id,
                        'locale' => $lang->code,
                        'label' => $pageTranslation?->title ?? '',
                        'url' => $pageTranslation?->slug ? '/' . $pageTranslation->slug : '',
                    ]);
                }
            }
        } else {
            // استخدم الترجمات المدخلة يدوياً (للروابط والقوائم المنسدلة)
            foreach ($this->languages as $lang) {
                HeaderItemTranslation::create([
                    'header_item_id' => $item->id,
                    'locale' => $lang->code,
                    'label' => $this->newItem['translations'][$lang->code]['label'] ?? '',
                    'url' => $this->newItem['translations'][$lang->code]['url'] ?? '',
                ]);
            }
        }

        $this->resetForm();

        $this->loadItems();
        session()->flash('success', 'تمت إضافة العنصر بنجاح');
    }

    public function editItem($id)
    {
        $this->mode = 'edit';
        $this->editingId = $id;

        $item = HeaderItem::with('translations')->findOrFail($id);

        $this->newItem = array_merge($this->newItem, [
            'type' => $item->type,
            'page_id' => $item->page_id,
            'order' => $item->order,
            'translations' => $item->translations->mapWithKeys(function ($translation) {
                return [$translation->locale => [
                    'label' => $translation->label,
                    'url' => $translation->url
                ]];
            })->toArray(),
            'children' => $item->children ?? [],
        ]);
    }

    public function updateItem()
    {
        $item = HeaderItem::findOrFail($this->editingId);
        $item->update([
            'type' => $this->newItem['type'],
            'page_id' => $this->newItem['type'] === 'page' ? $this->newItem['page_id'] : null,
            'order' => $this->newItem['order'],
            'children' => $this->newItem['type'] === 'dropdown' ? $this->newItem['children'] : null,
        ]);

        foreach ($this->languages as $lang) {
            HeaderItemTranslation::updateOrCreate(
                ['header_item_id' => $item->id, 'locale' => $lang->code],
                [
                    'label' => $this->newItem['translations'][$lang->code]['label'] ?? '',
                    'url' => $this->newItem['translations'][$lang->code]['url'] ?? ''
                ]
            );
        }
        $this->resetForm();
        $this->loadItems();
        session()->flash('success', 'تم تعديل العنصر بنجاح');
    }

    public function cancelEdit()
    {
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->mode = 'create';
        $this->editingId = null;
        $this->newItem = [
            'type' => 'link',
            'order' => 0,
            'page_id' => null,
            'translations' => [],
            'children' => [],
        ];
    }

    public function confirmDelete($id)
    {
        $item = HeaderItem::findOrFail($id);
        $item->delete();
        $this->loadItems();
        session()->flash('success', 'تم حذف العنصر بنجاح');
    }

    public function addChild($type = 'link')
    {
        if (!array_key_exists('children', $this->newItem) || !is_array($this->newItem['children'])) {
            $this->newItem['children'] = [];
        }

        $childItem = [
            'type' => $type,
            'page_id' => $type === 'page' ? null : null,
            'labels' => $this->languages->pluck('code')->mapWithKeys(fn($code) => [$code => [
                'label' => '',
                'url' => ''
            ]])->toArray()
        ];

        $this->newItem['children'][] = $childItem;
    }

    public function removeChild($index)
    {
        unset($this->newItem['children'][$index]);
        $this->newItem['children'] = array_values($this->newItem['children']); // إعادة ترتيب الفهارس
    }

    // دالة لتحديث ترجمات العنصر الفرعي عند اختيار صفحة
    public function updatedNewItemChildren()
    {
        // معالجة تحديث العناصر الفرعية
        foreach ($this->newItem['children'] ?? [] as $index => &$child) {
            if (($child['type'] ?? 'link') === 'page' && !empty($child['page_id'])) {
                $page = Page::with('translations')->find($child['page_id']);
                if ($page) {
                    // تأكد من وجود مصفوفة labels
                    if (!isset($child['labels'])) {
                        $child['labels'] = [];
                    }

                    foreach ($this->languages as $lang) {
                        $pageTranslation = $page->translations->where('locale', $lang->code)->first();
                        if ($pageTranslation) {
                            $child['labels'][$lang->code] = [
                                'label' => $pageTranslation->title,
                                'url' => $pageTranslation->slug ? '/' . $pageTranslation->slug : '#'
                            ];
                        }
                    }
                }
            }
        }
    }

    public function reorderChildren($order)
    {
        $newChildren = [];

        foreach ($order as $i => $oldIndex) {
            if (isset($this->newItem['children'][$oldIndex])) {
                $newChildren[$i] = $this->newItem['children'][$oldIndex];
            }
        }

        $this->newItem['children'] = array_values($newChildren);
    }

    public function reorderItems($ids)
    {
        // إعادة ترتيب المصفوفة محليًا فقط
        $this->items = collect($ids)->map(function ($id) {
            return collect($this->items)->firstWhere('id', (int) $id);
        })->values()->toArray();
        foreach ($ids as $index => $id) {
            HeaderItem::where('id', $id)->update(['order' => $index]);
        }
    }

    // دالة لمعالجة تغيير نوع العنصر
    public function updatedNewItemType($type)
    {
        // عند تغيير النوع، إعادة تعيين البيانات المرتبطة
        if ($type !== 'page') {
            $this->newItem['page_id'] = null;
        }

        if ($type !== 'dropdown') {
            $this->newItem['children'] = [];
        }

        // إعادة تعيين الترجمات حسب النوع
        if ($type === 'page') {
            $this->newItem['translations'] = [];
        } elseif ($type === 'link' || $type === 'dropdown') {
            // تهيئة الترجمات الفارغة للروابط والقوائم المنسدلة
            $this->newItem['translations'] = [];
            foreach ($this->languages as $lang) {
                $this->newItem['translations'][$lang->code] = [
                    'label' => '',
                    'url' => $type === 'dropdown' ? '' : '' // القوائم المنسدلة لا تحتاج URL
                ];
            }
        }
    }

    // دالة لتحديث الترجمات تلقائياً عند اختيار صفحة
    public function updatedNewItemPageId($pageId)
    {
        if ($pageId && $this->newItem['type'] === 'page') {
            $page = Page::with('translations')->find($pageId);
            if ($page) {
                $this->newItem['translations'] = [];
                foreach ($this->languages as $lang) {
                    $pageTranslation = $page->translations->where('locale', $lang->code)->first();
                    if ($pageTranslation) {
                        $this->newItem['translations'][$lang->code] = [
                            'label' => $pageTranslation->title,
                            'url' => $pageTranslation->slug ? '/' . $pageTranslation->slug : '#'
                        ];
                    }
                }
            }
        }
    }

    // دالة لمعالجة تغيير صفحة العناصر الفرعية
    public function updated($name, $value)
    {
        // معالجة تغيير page_id للعناصر الفرعية
        if (preg_match('/newItem\.children\.(\d+)\.page_id/', $name, $matches)) {
            $childIndex = $matches[1];

            if ($value && isset($this->newItem['children'][$childIndex])) {
                $page = Page::with('translations')->find($value);
                if ($page) {
                    // تحديث ترجمات العنصر الفرعي
                    if (!isset($this->newItem['children'][$childIndex]['labels'])) {
                        $this->newItem['children'][$childIndex]['labels'] = [];
                    }

                    foreach ($this->languages as $lang) {
                        $pageTranslation = $page->translations->where('locale', $lang->code)->first();
                        if ($pageTranslation) {
                            $this->newItem['children'][$childIndex]['labels'][$lang->code] = [
                                'label' => $pageTranslation->title,
                                'url' => $pageTranslation->slug ? '/' . $pageTranslation->slug : '#'
                            ];
                        }
                    }
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.dashboard.header.header-manager');
    }
}

