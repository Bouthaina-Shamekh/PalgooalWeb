<?php

namespace App\Livewire\Dashboard\Template;

use App\Models\Template;
use Livewire\Component;

class TemplateShowPage extends Component
{
    public $slug;
    public $template;

    public function mount($slug)
    {
        $this->slug = $slug;

        $this->template = Template::with(['translations', 'categoryTemplate.translation'])
            ->whereHas('translations', function ($q) use ($slug) {
                $q->where('locale', app()->getLocale())->where('slug', $slug);
            })
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.dashboard.template.template-show-page', [
            'template' => $this->template,
            'translation' => $this->template->getTranslation(),
        ]);
    }
}