<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\Section;
use App\Models\SectionTranslation;

class HeroSection extends BaseSectionComponent
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
                'button_text-1' => $content['button_text-1'] ?? '',
                'button_url-1' => $content['button_url-1'] ?? '',
                'button_text-2' => $content['button_text-2'] ?? '',
                'button_url-2' => $content['button_url-2'] ?? '',
                'hero' => $content['hero'] ?? [],
            ];
        }
    }

    public static function create($pageId, $order, $translations)
    {
        $section = Section::create([
            'page_id' => $pageId,
            'key' => 'hero',
            'order' => $order,
        ]);

        foreach ($translations as $locale => $data) {
            $content = [
                'subtitle' => $data['subtitle'] ?? '',
                'button_text-1' => $data['button_text-1'] ?? '',
                'button_url-1' => $data['button_url-1'] ?? '',
                'button_text-2' => $data['button_text-2'] ?? '',
                'button_url-2' => $data['button_url-2'] ?? '',
                'hero' => $data['hero'] ?? [],
            ];

            SectionTranslation::create([
                'section_id' => $section->id,
                'locale' => $locale,
                'title' => $data['title'] ?? '',
                'content' => $content,
            ]);
        }

        return $section;
    }

    public function updateHeroSection()
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
                'button_text-2' => $data['button_text-2'] ?? '',
                'button_url-2' => $data['button_url-2'] ?? '',
                'hero' => $data['hero'] ?? [],
            ];

            $this->section->order = $this->order;
            $this->section->save();
            $translation->save();
        }

        session()->flash('success', 'تم تحديث قسم الهيرو بنجاح.');
    }

    public function removehero($locale, $index)
    {
        if (isset($this->translationsData[$locale]['hero'][$index])) {
            unset($this->translationsData[$locale]['hero'][$index]);
            $this->translationsData[$locale]['hero'] = array_values($this->translationsData[$locale]['hero']);
        }
    }

    public function render()
    {
        return view('livewire.dashboard.sections.hero-section');
    }
}
