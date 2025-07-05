<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\Language;
use App\Models\Section;
use App\Models\SectionTranslation;
use Livewire\Component;

class OurWorkSection extends Component
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
                'OurWorks' => $content['OurWorks'] ?? [],
            ];
        }
    }

    public function updateOurWorkSection()
    {
        foreach ($this->translationsData as $locale => $data) {
             $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale' => $locale,
            ]);

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle' => $data['subtitle'] ?? '',
                'OurWorks' => $data['OurWorks'] ?? [],
            ];
            $translation->save();
        }
        session()->flash('success', 'تم تحديث قسم المميزات بنجاح.');
    }

    public function addOurWorks($locale)
    {
        $this->translationsData[$locale]['OurWorks'][] = [
            'icon' => '',
            'title' => '',
        ];
    }

    public function setActiveLang($code)
    {
        $this->activeLang = $code;
    }

    public function removeOurWorks($locale, $index)
    {
        if (isset($this->translationsData[$locale]['OurWorks'][$index])) {
            unset($this->translationsData[$locale]['OurWorks'][$index]);
            // إعادة ترتيب الفهارس لتجنب المشاكل
            $this->translationsData[$locale]['OurWorks'] = array_values($this->translationsData[$locale]['OurWorks']);
        }
    }

    public function deleteMySection()
    {
        $this->dispatch('deleteSection', $this->section->id);
    }



    public function render()
    {
        return view('livewire.dashboard.sections.our-work-section');
    }
}
