<?php

namespace App\Livewire\Dashboard;

use App\Models\Section;
use App\Models\SectionTranslation;
use App\Models\Language;
use Livewire\Component;
use App\Livewire\Dashboard\Sections\HeroSection; // 🔁 مهم

class Sections extends Component
{
    public $pageId;
    public $sections = [];
    public $languages;

    public $sectionKey = '';
    public $sectionOrder = 0;
    public $translations = [];
    public $translationsData = [];
    protected $listeners = ['deleteSection'];

    public $availableKeys = ['hero', 'features', 'services', 'templates', 'works', 'testimonials', 'blog'];
    public $activeLang;

    public function mount($pageId)
    {
        $this->pageId = $pageId;
        $this->languages = Language::where('is_active', true)->get();
        $this->activeLang = app()->getLocale();
        $this->loadSections();
    }

    public function loadSections()
    {
        $this->sections = Section::with('translations')
            ->where('page_id', $this->pageId)
            ->orderBy('order')
            ->get();

        foreach ($this->sections as $section) {
            foreach ($this->languages as $lang) {
                $translation = $section->translations->firstWhere('locale', $lang->code);
                $content = is_array($translation?->content) ? $translation->content : [];

                $this->translationsData[$section->id][$lang->code] = [
                    'title' => $translation?->title ?? '',
                    'subtitle' => $content['subtitle'] ?? '',
                ];
            }
        }
    }

    public function addSection()
    {
        $this->validate([
            'sectionKey' => 'required',
        ]);

        if (Section::where('page_id', $this->pageId)->where('key', $this->sectionKey)->exists()) {
            session()->flash('error', 'هذا السكشن موجود مسبقًا.');
            return;
        }

        $order = $this->sectionOrder ?: (Section::where('page_id', $this->pageId)->max('order') + 1);

        // ⬇️ تفويض إضافة كل سكشن إلى الكلاس المخصص له
        switch ($this->sectionKey) {
            case 'hero':
                HeroSection::create($this->pageId, $order, $this->translations);
                break;

            default:
                $section = Section::create([
                    'page_id' => $this->pageId,
                    'key' => $this->sectionKey,
                    'order' => $order,
                ]);

                foreach ($this->languages as $lang) {
                    $locale = $lang->code;
                    $data = $this->translations[$locale] ?? [];
                    $content = [];

                    switch ($this->sectionKey) {
                        case 'features':
                            $featuresRaw = $data['features'] ?? '';
                            $content = [
                                'subtitle' => $data['subtitle'] ?? '',
                                'features' => is_array($featuresRaw)
                                    ? $featuresRaw
                                    : array_filter(array_map('trim', explode("\n", $featuresRaw))),
                            ];
                            break;

                        case 'services':
                            $servicesRaw = $data['services'] ?? '';
                            $content = [
                                'subtitle' => $data['subtitle'] ?? '',
                                'services' => is_array($servicesRaw)
                                    ? $servicesRaw
                                    : array_filter(array_map('trim', explode("\n", $servicesRaw))),
                            ];
                            break;

                        case 'templates':
                        case 'works':
                        case 'testimonials':
                        case 'blog':
                            $content = [];
                            break;
                    }

                    SectionTranslation::create([
                        'section_id' => $section->id,
                        'locale' => $locale,
                        'title' => $data['title'] ?? '',
                        'content' => $content,
                    ]);
                }
        }

        $this->reset(['sectionKey', 'sectionOrder', 'translations', 'translationsData']);
        $this->loadSections();
        session()->flash('success', 'تم إضافة السكشن بنجاح.');
    }

    public function updateSection($sectionId, $locale = null)
    {
        $section = Section::with('translations')->findOrFail($sectionId);
        

        $targetLocales = $locale ? [$locale] : array_column($this->languages->toArray(), 'code');

        foreach ($targetLocales as $code) {
            $data = $this->translationsData[$sectionId][$code] ?? [];

            $content = [
                'subtitle' => $data['subtitle'] ?? '',
            ];

            $translation = SectionTranslation::firstOrNew([
                'section_id' => $sectionId,
                'locale' => $code,
            ]);

            $translation->title = $data['title'] ?? '';
            $translation->content = $content;
            $translation->save();
        }

        $this->loadSections();
        session()->flash('success', 'تم تحديث السكشن بنجاح.');
    }

    public function deleteSection($id)
    {
        Section::findOrFail($id)->delete();
        $this->loadSections();
        $this->dispatch('$refresh');
        session()->flash('success', 'تم حذف السكشن بنجاح.');
    }

    public function setActiveLang($code)
    {
        $this->activeLang = $code;
    }

    public function render()
    {
        return view('livewire.dashboard.sections', [
            'languages' => $this->languages,
            'sections' => $this->sections,
            'availableKeys' => $this->availableKeys,
            'activeLang' => $this->activeLang,
            'sectionKey' => $this->sectionKey,
        ]);
    }
}
