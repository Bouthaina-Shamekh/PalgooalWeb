<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Breadcrumb extends Component
{
    public $items;

    /**
     * Create a new component instance.
     */
    public function __construct(?array $items = null)
    {
        $page = view()->shared('currentPage');

        $this->items = $items ?? [
            ['title' => t('dashboard.Home', 'Home'), 'url' => '/'],
            ['title' => $page?->translation()?->title ?? 'صفحة']
        ];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.breadcrumb');
    }
}
