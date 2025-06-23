<?php

namespace App\View\Components\lang;

use App\Models\Language;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LanguageSwitcherDashboard extends Component
{
    public $languages;
    public $currentLocale;
    public $currentLanguage;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->languages = Language::where('is_active', true)->get();
        $this->currentLocale = app()->getLocale();
        $this->currentLanguage = Language::where('code', $this->currentLocale)->first();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.lang.language-switcher-dashboard');
    }
}
