<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\Language;
use App\Models\Section;
use App\Models\SectionTranslation;
use Livewire\Component;

class PanelSection extends Component
{
    public Section $section;
    public $translationsData = [];
    public $languages;
    public $activeLang;
    public $order;


    public function mount()
    {
        $this->languages = Language::where('is_active', true)->get();
        $this->activeLang = app()->getLocale();
        $this->order = $this->section->order;

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];
            $this->translationsData[$lang->code] = [
                'title' => $translation?->title ?? '',
                'subtitle' => $content['subtitle'] ?? '',
                'button_text-1' => $content['button_text-1'] ?? '',
                'button_url-1' => $content['button_url-1'] ?? '',
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
        $languages = Language::where('is_active', true)->get();
        foreach ($languages as $lang) {
            $locale = $lang->code;
            $data = $translations[$locale] ?? [];
            $content = [
                'subtitle' => $data['subtitle'] ?? '',
                'button_text-1' => $data['button_text-1'] ?? '',
                'button_url-1' => $data['button_url-1'] ?? '',
            ];
            dd($content);
            SectionTranslation::create([
                'section_id' => $section->id,
                'locale' => $locale,
                'title' => $data['title'] ?? '',
                'content' => $content,
            ]);
        }
        return $section;
    }



    public function updatePanelSection()
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
            ];
            $this->section->order = $this->order;
            $this->section->save();
            $translation->save();
        }

        session()->flash('success', 'تم تحديث قسم اللوحة بنجاح.');
    }

    public function setActiveLang($code)
    {
        $this->activeLang = $code;
    }

    public function removePanel($locale, $index)
    {
        if (isset($this->translationsData[$locale]['hero']) && is_array($this->translationsData[$locale]['hero']) && isset($this->translationsData[$locale]['hero'][$index])) {
            unset($this->translationsData[$locale]['hero'][$index]);
            $this->translationsData[$locale]['hero'] = array_values($this->translationsData[$locale]['hero']);
        }
    }

    public function deleteMySection()
    {
        logger('🔥 تم الضغط على زر الحذف');
            $this->dispatch('confirm-delete-section', [
            'sectionId' => $this->section->id,
        ]);

    }


    public function render()
    {
        return view('livewire.dashboard.sections.panel-section');
    }
}
