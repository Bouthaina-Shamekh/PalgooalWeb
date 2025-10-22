<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\SectionTranslation;

class Features2Section extends BaseSectionComponent
{
    public function mount()
    {
        parent::mount();

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $features = $content['features'] ?? [];
            if (!is_array($features)) {
                $features = [];
            }

            $this->translationsData[$lang->code] = [
                'title'       => $translation?->title ?? '',
                'subtitle'    => $content['subtitle'] ?? '',
                'button_text' => $content['button_text'] ?? '',
                'button_url'  => $content['button_url'] ?? '',
                'features'    => $features,
            ];
        }
    }

    public function updateFeatures2Section()
    {
        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale'     => $locale,
            ]);

            $features = $data['features'] ?? [];
            if (!is_array($features)) {
                $features = [];
            }

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle'    => $data['subtitle'] ?? '',
                'button_text' => $data['button_text'] ?? '',
                'button_url'  => $data['button_url'] ?? '',
                'features'    => array_values($features),
            ];

            $translation->save();
        }

        $this->section->order = $this->order;
        $this->section->save();

        session()->flash('success', 'تم تحديث سكشن المميزات بنجاح.');
    }

    public function addFeature($locale): void
    {
        $this->translationsData[$locale]['features'][] = [
            'icon'        => '',
            'title'       => '',
            'description' => '',
        ];
    }

    public function removeFeature($locale, $index): void
    {
        if (!isset($this->translationsData[$locale]['features'][$index])) {
            return;
        }

        unset($this->translationsData[$locale]['features'][$index]);
        $this->translationsData[$locale]['features'] = array_values($this->translationsData[$locale]['features']);
    }

    public function render()
    {
        return view('livewire.dashboard.sections.features-2');
    }
}
