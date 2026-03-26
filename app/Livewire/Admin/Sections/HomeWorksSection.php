<?php

namespace App\Livewire\Admin\Sections;

use App\Models\Language;
use App\Models\Section;
use App\Models\SectionTranslation;
use Livewire\Component;

/**
 * @deprecated deprecated - do not use. Legacy admin Livewire component retained only for fallback safety.
 */
class HomeWorksSection extends BaseSectionComponent
{
    public function mount()
    {
        parent::mount(); // ط§ط³طھط¯ط¹ط§ط، mount ظ…ظ† BaseSectionComponent

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $this->translationsData[$lang->code] = [
                'title' => $translation?->title ?? '',
                'subtitle' => $content['subtitle'] ?? '',
                'button_text-1' => $content['button_text-1'] ?? '',
                'button_url-1' => $content['button_url-1'] ?? '',
                'homeWorks' => $content['homeWorks'] ?? [],
            ];
        }
    }




    public function updatehomeWorksSection()
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
                'homeWorks' => $data['homeWorks'] ?? [],
            ];

            $this->section->order = $this->order;
            $this->section->save();
            $translation->save();
        }

        session()->flash('success', 'طھظ… طھط­ط¯ظٹط« ظ‚ط³ظ… ط§ظ„ظ‡ظٹط±ظˆ ط¨ظ†ط¬ط§ط­.');
    }

        public function removehomeWorks($locale, $index)
    {
        if (isset($this->translationsData[$locale]['homeWorks'][$index])) {
            unset($this->translationsData[$locale]['homeWorks'][$index]);
            $this->translationsData[$locale]['homeWorks'] = array_values($this->translationsData[$locale]['homeWorks']);
        }
    }

    public function render()
    {
        return view('livewire.admin.sections.home-works-section');
    }
}


