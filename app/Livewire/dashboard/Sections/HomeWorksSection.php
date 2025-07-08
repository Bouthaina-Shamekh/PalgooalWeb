<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\Language;
use App\Models\Section;
use App\Models\SectionTranslation;
use Livewire\Component;

class HomeWorksSection extends Component
{
    public Section $section;
    public $translationsData = [];
    public $languages;
    public $activeLang;

    public function mount()
    {
        $this->languages = Language::where('is_active', true)->get();
        $this->activeLang = app()->getLocale();

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $this->translationsData[$lang->code] = [
                'title' => $translation?->title ?? '',
                'subtitle' => $content['subtitle'] ?? '',
                'button_text-1' => $content['button_text-1'] ?? '',
                'button_url-1' => $content['button_url-1'] ?? '',
            ];
        }
    }

    public function updateworksSection()
    {
        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale' => $locale,
            ]);

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle' => $data['subtitle'] ?? '',
                'button_text-1' => $data['button_text-1'] ?? '',
                'button_url-1' => $data['button_url-1'] ?? '',
            ];
            $translation->save();
        }
        session()->flash('success', 'تم تحديث قسم المميزات بنجاح.');
    }

    public function setActiveLang($code)
    {
        $this->activeLang = $code;
    }

    public function deleteMySection()
    {
        $this->dispatch('deleteSection', $this->section->id);
    }


    public function render()
    {
        return view('livewire.dashboard.sections.home-works-section');
    }
}
