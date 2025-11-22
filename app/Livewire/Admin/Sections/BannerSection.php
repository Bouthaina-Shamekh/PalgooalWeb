<?php

namespace App\Livewire\Admin\Sections;

use App\Models\Language;
use App\Models\Section;
use App\Models\SectionTranslation;
use Livewire\Component;

class BannerSection extends BaseSectionComponent
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
                'banners' => $content['banners'] ?? [],
            ];
        }
    }

        public function updatebannersSection()
    {
        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale' => $locale,
            ]);

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle' => $data['subtitle'] ?? '',
                'banners' => $data['banners'] ?? [],
            ];

            $this->section->order = $this->order;
            $this->section->save();
            $translation->save();
        }

        session()->flash('success', 'تم تحديث قسم الهيرو بنجاح.');
    }

    public function removebanners($locale, $index)
    {
        if (isset($this->translationsData[$locale]['banners'][$index])) {
            unset($this->translationsData[$locale]['banners'][$index]);
            $this->translationsData[$locale]['banners'] = array_values($this->translationsData[$locale]['banners']);
        }
    }



    public function render()
    {
        return view('livewire.admin.sections.banner-section');
    }
}

