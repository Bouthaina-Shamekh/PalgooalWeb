<?php

namespace App\Livewire\Admin\Sections;

use App\Models\SectionTranslation;
use Livewire\Component;

/**
 * @deprecated deprecated - do not use. Legacy admin Livewire component retained only for fallback safety.
 */
class SearchDomainSection extends BaseSectionComponent
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
                'SearchDomain' => $content['SearchDomain'] ?? [],
            ];
        }
    }


    public function updateSearchDomainSection()
    {
        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale' => $locale,
            ]);

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle' => $data['subtitle'] ?? '',
                'SearchDomain' => $data['SearchDomain'] ?? [],
            ];

            $this->section->order = $this->order;
            $this->section->save();
            $translation->save();
        }

        session()->flash('success', 'طھظ… طھط­ط¯ظٹط« ظ‚ط³ظ… ط§ظ„ظ‡ظٹط±ظˆ ط¨ظ†ط¬ط§ط­.');
    }

        public function removeSearchDomain($locale, $index)
    {
        if (isset($this->translationsData[$locale]['SearchDomain'][$index])) {
            unset($this->translationsData[$locale]['SearchDomain'][$index]);
            $this->translationsData[$locale]['SearchDomain'] = array_values($this->translationsData[$locale]['SearchDomain']);
        }
    }




    public function render()
    {
        return view('livewire.admin.sections.search-domain-section');
    }
}


