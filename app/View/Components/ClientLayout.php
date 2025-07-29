<?php

namespace App\View\Components;

use Closure;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Config;

class ClientLayout extends Component
{
     public $title;
    /**
     * Create a new component instance.
     */
    public function __construct($title = null)
    {
         $this->title = $title ?? Config::get('app.name');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('layouts.client-layout');
    }
}
