<?php

namespace App\Livewire\Admin\Sections;

use App\Models\SectionTranslation;
use Livewire\Component;

class TemplatesPagesSection extends BaseSectionComponent
{
    public function mount()
    {
        parent::mount(); // استدعاء mount من الكلاس الأساسي

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $this->translationsData[$lang->code] = [
                'title' => $translation?->title ?? '',
                'template_sections' => $content['template_sections'] ?? '',
                'templates-pages' => $content['templates-pages'] ?? [],
                'Sort_price' => $content['Sort_price'] ?? [],
            ];
        }
    }

    public function updatetemplatespagesSection()
    {
        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale' => $locale,
            ]);

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle' => $data['subtitle'] ?? '',
                'templates-pages' => $data['templates-pages'] ?? [],
                'template_sections' => $data['template_sections'] ?? '',
                'Sort_price' => $data['Sort_price'] ?? '',
                
            ];

            $this->section->order = $this->order;
            $this->section->save();
            $translation->save();
        }

        session()->flash('success', 'تم تحديث قسم المميزات بنجاح.');
    }

    public function removetemplatespages($locale, $index)
    {
        if (isset($this->translationsData[$locale]['templates-pages'][$index])) {
            unset($this->translationsData[$locale]['templates-pages'][$index]);
            $this->translationsData[$locale]['templates-pages'] = array_values($this->translationsData[$locale]['templates-pages']);
        }
    }

    public function render()
    {
        return view('livewire.dashboard.sections.templates-pages-section');
    }
}

