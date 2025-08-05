<?php

namespace App\Livewire\Dashboard\Template;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CategoryTemplate;
use App\Services\TemplateService;

class FrontendTemplatesPage extends Component
{
    use WithPagination;

    public $selectedCategory = 'all';
    public $maxPrice = 999;
    public $sortBy = 'default';

    public function updating($field)
    {
        if (in_array($field, ['selectedCategory', 'maxPrice', 'sortBy'])) {
            $this->resetPage(); // إعادة الصفحة للأولى عند تغيير الفلترة
        }
    }

    public function getCategoriesProperty()
    {
        return cache()->remember("template_categories_" . app()->getLocale(), 60 * 60, function () {
            return CategoryTemplate::with(['translations' => function ($q) {
                $q->where('locale', app()->getLocale())->orWhere('locale', 'ar');
            }])->get()->map(function ($cat) {
                $translated = $cat->translations->firstWhere('locale', app()->getLocale())
                ?? $cat->translations->firstWhere('locale', 'ar');
                $cat->translated_name = $translated?->name ?? 'غير معرف';
                return $cat;
            });
        });
    }

    public function render()
    {
        $filters = [
            'max_price' => $this->maxPrice,
            'category_id' => $this->selectedCategory,
            'sort_by' => $this->sortBy,
        ];

        $templates = TemplateService::getFrontendTemplates($filters);

        return view('livewire.dashboard.template.frontend-templates-page', [
            'templates' => $templates,
            'categories' => $this->categories,
            'showSidebar' => true,
            'maxPrice' => $this->maxPrice,
        ]);
    }
}

