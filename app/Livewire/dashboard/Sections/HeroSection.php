<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\Language;
use App\Models\Section;
use App\Models\SectionTranslation;
use Livewire\Component;

class HeroSection extends Component
{
    public Section $section;
    public $translationsData = [];
    public $languages;
    public $activeLang;
    public $order;
    protected $listeners = ['confirm-delete-section' => 'deleteMySection'];


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
        $languages = Language::where('is_active', true)->get();
        foreach ($languages as $lang) {
            $locale = $lang->code;
            $data = $translations[$locale] ?? [];
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

    public function setActiveLang($code)
    {
        $this->activeLang = $code;
    }

    public function removehero($locale, $index)
    {
        if (isset($this->translationsData[$locale]['hero'][$index])) {
            unset($this->translationsData[$locale]['hero'][$index]);
            // إعادة ترتيب الفهارس لتجنب المشاكل
            $this->translationsData[$locale]['hero'] = array_values($this->translationsData[$locale]['hero']);
        }
    }

    public function deleteMySection()
    {
        $this->section->delete();
        session()->flash('success', 'تم حذف السكشن بنجاح.');
        $this->redirect(request()->header('Referer'), navigate: true);
    }

    public function render()
    {
        return view('livewire.dashboard.sections.hero-section');
    }
}
