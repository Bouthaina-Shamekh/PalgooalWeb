<?php

namespace App\Livewire\Admin\Sections;

use App\Models\Language;
use App\Models\Section;
use App\Models\SectionTranslation;
use Livewire\Component;

class WorksSection extends BaseSectionComponent
{
    public function mount()
    {
        parent::mount(); // استدعاء mount من BaseSectionComponent

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $this->translationsData[$lang->code] = [
                'title' => $translation?->title ?? '',
                'subtitle' => $content['subtitle'] ?? '',
                'works' => $content['works'] ?? [],
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
                'works' => $data['works'] ?? [],
            ];

            $this->section->order = $this->order;
            $this->section->save();
            $translation->save();
        }

        session()->flash('success', 'تم تحديث قسم الهيرو بنجاح.');
    }

        public function removeworks($locale, $index)
    {
        if (isset($this->translationsData[$locale]['works'][$index])) {
            unset($this->translationsData[$locale]['works'][$index]);
            $this->translationsData[$locale]['works'] = array_values($this->translationsData[$locale]['works']);
        }
    }




    public function render()
    {
        return view('livewire.admin.sections.works-section');
    }
}

