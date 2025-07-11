<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\Section;
use App\Models\SectionTranslation;
use App\Models\Language;
use Livewire\Component;

class FeaturesSection extends BaseSectionComponent
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
                'features' => $content['features'] ?? [],
            ];
        }
    }

    public function updateFeatureSection()
    {
        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale' => $locale,
            ]);

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle' => $data['subtitle'] ?? '',
                'features' => $data['features'] ?? [],
            ];

            $this->section->order = $this->order;
            $this->section->save();
            $translation->save();
        }

        session()->flash('success', 'تم تحديث قسم المميزات بنجاح.');
    }

    public function addFeature($locale)
    {
        $this->translationsData[$locale]['features'][] = [
            'icon' => '',
            'title' => '',
            'description' => '',
        ];
    }

    public function removeFeature($locale, $index)
    {
        if (isset($this->translationsData[$locale]['features'][$index])) {
            unset($this->translationsData[$locale]['features'][$index]);
            $this->translationsData[$locale]['features'] = array_values($this->translationsData[$locale]['features']);
        }
    }

    public function render()
    {
        return view('livewire.dashboard.sections.features-section');
    }
}
 