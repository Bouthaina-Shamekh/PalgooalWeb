<?php

namespace App\Livewire\Admin\Sections;

use App\Models\Language;
use App\Models\Section;
use App\Models\SectionTranslation;
use Livewire\Component;

class ServicesSection extends BaseSectionComponent
{
    public Section $section;
    public $translationsData = [];
    public $languages;
    public $activeLang;

    public function mount()
    {
        parent::mount(); // استدعاء mount من BaseSectionComponent

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

            $this->section->order = $this->order;
            $this->section->save();
            $translation->save();
        }

        session()->flash('success', 'تم تحديث قسم الهيرو بنجاح.');
    }

        public function removeservices($locale, $index)
    {
        if (isset($this->translationsData[$locale]['services'][$index])) {
            unset($this->translationsData[$locale]['services'][$index]);
            $this->translationsData[$locale]['services'] = array_values($this->translationsData[$locale]['services']);
        }
    }
    
    public function render()
    {
        return view('livewire.admin.sections.services-section');
    }
}

