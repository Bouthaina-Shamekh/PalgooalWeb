<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\Language;
use App\Models\Section;
use Livewire\Component;

class BaseSectionComponent extends Component
{
    public Section $section;
    public $translationsData = [];
    public $languages;
    public $activeLang;
    public $order;

    protected $listeners = ['confirm-delete-section' => 'deleteMySection'];

    public function mount()
    {
        $this->languages = Language::where('is_active', true)->get();
        $this->activeLang = app()->getLocale();
        $this->order = $this->section->order;

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $this->translationsData[$lang->code] = [
                'title' => $translation?->title ?? '',
                'subtitle' => $content['subtitle'] ?? '',
            ];
        }
    }

    public function setActiveLang($code)
    {
        $this->activeLang = $code;
    }

    public function deleteMySection()
    {
        $this->section->delete();

        session()->flash('success', 'تم حذف السكشن بنجاح.');
        $this->redirect(request()->header('Referer'), navigate: true);
    }
}
