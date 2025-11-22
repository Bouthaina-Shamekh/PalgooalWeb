<?php

namespace App\Livewire\Admin\Sections;

use App\Models\SectionTranslation;
use Livewire\Component;

class TemplatesSection extends BaseSectionComponent
{
    public function mount()
    {
        parent::mount(); // استدعاء mount من الكلاس الأساسي

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $this->translationsData[$lang->code] = [
                'title' => $translation?->title ?? '',
                'subtitle' => $content['subtitle'] ?? '',
                'templates' => $content['templates'] ?? [],
            ];
        }
    }

        public function updatetemplatesSection()
    {
        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale' => $locale,
            ]);

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle' => $data['subtitle'] ?? '',
                'templates' => $data['templates'] ?? [],
            ];

            $this->section->order = $this->order;
            $this->section->save();
            $translation->save();
        }

        session()->flash('success', 'تم تحديث قسم المميزات بنجاح.');
    }

    public function removetemplates($locale, $index)
    {
        if (isset($this->translationsData[$locale]['templates'][$index])) {
            unset($this->translationsData[$locale]['templates'][$index]);
            $this->translationsData[$locale]['templates'] = array_values($this->translationsData[$locale]['templates']);
        }
    }


    public function render()
    {
        return view('livewire.dashboard.sections.templates-section');
    }
}

