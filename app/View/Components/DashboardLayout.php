<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Config;
use Illuminate\View\Component;

class DashboardLayout extends Component
{
    public $title;
    public $enableLegacyAdminLivewire;
    /**
     * Create a new component instance.
     */
    public function __construct($title = null, bool $enableLegacyAdminLivewire = false)
    {
        $this->title = $title ?? Config::get('app.name');
        $this->enableLegacyAdminLivewire = $enableLegacyAdminLivewire;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('dashboard.layouts.app', [
            'title' => $this->title,
            'enableLegacyAdminLivewire' => $this->enableLegacyAdminLivewire,
        ]);
    }
}
