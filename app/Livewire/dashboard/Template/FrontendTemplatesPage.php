<?php

namespace App\Livewire\Dashboard\Template;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CategoryTemplate;
use App\Services\TemplateService;

class FrontendTemplatesPage extends Component
{
    use WithPagination;

    public $categories;
    public $selectedCategory = 'all';
    public $maxPrice = 250;
    public $sortBy = 'default';
    public $showSidebar = true;

    protected $queryString = ['selectedCategory', 'maxPrice', 'sortBy'];

    public function mount($maxPrice = 250, $sortBy = 'default', $showSidebar = true)
    {
        $this->maxPrice = $maxPrice;
        $this->sortBy = $sortBy;
        $this->showSidebar = $showSidebar;
        $this->categories = CategoryTemplate::with('translation')->get();
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
            'selectedCategory' => $this->selectedCategory,
            'maxPrice' => $this->maxPrice,
            'sortBy' => $this->sortBy,
        ]);
    }
}
