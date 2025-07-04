<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\Language;
use App\Models\Section;
use App\Models\SectionTranslation;
use Livewire\Component;

class ServicesSection extends Component
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
                'services' => $content['services'] ?? [],
            ];
        }
    }

    public function updateservicesSection()
    {
        foreach ($this->translationsData as $locale => $data) {
             $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale' => $locale,
            ]);

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle' => $data['subtitle'] ?? '',
                'services' => $data['services'] ?? [],
            ];
            $translation->save();
        }
        session()->flash('success', 'تم تحديث قسم المميزات بنجاح.');
    }

        public function addservices($locale)
    {
        $this->translationsData[$locale]['services'][] = [
            'icon' => '',
            'title' => '',
            'description' => '',
        ];
    }

    public function setActiveLang($code)
    {
        $this->activeLang = $code;
    }

    public function addFeature($locale)
    {
        $this->translationsData[$locale]['services'][] = [
            'icon' => '',
            'title' => '',
            'description' => '',
        ];
    }

    public function removeFeature($locale, $index)
    {
        if (isset($this->translationsData[$locale]['services'][$index])) {
            unset($this->translationsData[$locale]['services'][$index]);
            // إعادة ترتيب الفهارس لتجنب المشاكل
            $this->translationsData[$locale]['services'] = array_values($this->translationsData[$locale]['services']);
        }
    }

    public function deleteMySection()
    {
        $this->dispatch('deleteSection', $this->section->id);
    }
    
    public function render()
    {
        return view('livewire.dashboard.sections.services-section');
    }
}
