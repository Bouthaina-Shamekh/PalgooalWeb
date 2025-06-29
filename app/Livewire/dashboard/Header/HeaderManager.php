<?php

namespace App\Livewire\Dashboard\Header;

use App\Models\Header;
use App\Models\HeaderItemTranslation;
use App\Models\Language;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class HeaderManager extends Component
{
    public Header $header;

    public Collection $languages;

    public array $items = [];

    public array $newItem = [
        'type' => 'link',
        'url' => '',
        'order' => 0,
        'translations' => [],
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
            ];
        })->toArray();
    }

    public function addItem()
    {
        $item = $this->header->items()->create([
            'type' => $this->newItem['type'],
            'url' => $this->newItem['url'],
            'order' => $this->newItem['order'],
        ]);

        foreach ($this->languages as $lang) {
            HeaderItemTranslation::create([
                'header_item_id' => $item->id,
                'locale' => $lang->code,
                'label' => $this->newItem['translations'][$lang->code] ?? '',
            ]);
        }

        $this->reset('newItem');
        $this->newItem = [
            'type' => 'link',
            'url' => '',
            'order' => 0,
            'translations' => [],
        ];

        $this->loadItems();
        session()->flash('success', 'تمت إضافة العنصر بنجاح');
    }
    
    public function render()
    {
        return view('livewire.dashboard.header.header-manager');
    }
}
