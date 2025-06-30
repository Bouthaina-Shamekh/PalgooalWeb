<?php

namespace App\Livewire\Dashboard\Header;

use App\Models\Header;
use App\Models\HeaderItem;
use App\Models\HeaderItemTranslation;
use App\Models\Language;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class HeaderManager extends Component
{
    public Header $header;
    public string $mode = 'create';
    public int|null $editingId = null;
    protected $listeners = ['reorderChildren'];

    public Collection $languages;

    public array $items = [];

    public array $newItem = [
        'type' => 'link',
        'url' => '',
        'order' => 0,
        'translations' => [],
        'children' => [],
    ];

    public function mount()
    {
        $this->languages = Language::all();

        $this->header = Header::firstOrCreate(['name' => 'Main Header']);

        $this->loadItems();
    }

    public function loadItems()
    {
        $this->items = $this->header->items->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => $item->type,
                'url' => $item->url,
                'order' => $item->order,
                'translations' => $item->translations->pluck('label', 'locale')->toArray(),
                'children' => $item->children ?? [],
            ];
        })->toArray();
    }

    public function addItem()
    {
        $item = $this->header->items()->create([
            'type' => $this->newItem['type'],
            'url' => $this->newItem['url'],
            'order' => $this->newItem['order'],
            'children' => $this->newItem['type'] === 'dropdown' ? $this->newItem['children'] : null,
        ]);


        foreach ($this->languages as $lang) {
            HeaderItemTranslation::create([
                'header_item_id' => $item->id,
                'locale' => $lang->code,
                'label' => $this->newItem['translations'][$lang->code] ?? '',
            ]);
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
        'url' => $item->url,
        'order' => $item->order,
        'translations' => $item->translations->pluck('label', 'locale')->toArray(),
        'children' => $item->children ?? [],
    ]);
}

    public function updateItem()
    {
        $item = HeaderItem::findOrFail($this->editingId);
        $item->update([
            'type' => $this->newItem['type'],
            'url' => $this->newItem['url'],
            'order' => $this->newItem['order'],
            'children' => $this->newItem['type'] === 'dropdown' ? $this->newItem['children'] : null,
        ]);

        foreach ($this->languages as $lang) {
            HeaderItemTranslation::updateOrCreate(
                ['header_item_id' => $item->id, 'locale' => $lang->code],
                ['label' => $this->newItem['translations'][$lang->code] ?? '']
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
            'url' => '',
            'order' => 0,
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

public function addChild()
{
    if (!array_key_exists('children', $this->newItem) || !is_array($this->newItem['children'])) {
        $this->newItem['children'] = [];
    }

    $this->newItem['children'][] = [
        'url' => '',
        'labels' => $this->languages->pluck('code')->mapWithKeys(fn($code) => [$code => ''])->toArray()
    ];
}

public function removeChild($index)
{
    unset($this->newItem['children'][$index]);
    $this->newItem['children'] = array_values($this->newItem['children']); // إعادة ترتيب الفهارس
}
public function reorderChildren(array $order)
{
    $newChildren = [];

    foreach ($order as $newIndex => $oldIndex) {
        if (isset($this->newItem['children'][$oldIndex])) {
            $newChildren[$newIndex] = $this->newItem['children'][$oldIndex];
        }
    }

    $this->newItem['children'] = $newChildren;
}

    public function render()
    {
        return view('livewire.dashboard.header.header-manager');
    }
}
