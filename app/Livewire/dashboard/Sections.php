<?php

namespace App\Livewire\Dashboard;

use App\Models\Section;
use App\Models\SectionTranslation;
use App\Models\Language;
use Livewire\Component;

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
                    'button_text' => $content['button_text'] ?? '',
                    'button_url' => $content['button_url'] ?? '',
                ];
            }
        }
    }

    public function addSection()
{
    $this->validate([
        'sectionKey' => 'required',
    ]);

    // ✅ التحقق من عدم تكرار السكشن
    if (Section::where('page_id', $this->pageId)->where('key', $this->sectionKey)->exists()) {
        session()->flash('error', 'هذا السكشن موجود مسبقًا.');
        return;
    }

    // ✅ ترتيب تلقائي إذا لم يُحدد
    $order = $this->sectionOrder ?: (Section::where('page_id', $this->pageId)->max('order') + 1);

    // ✅ إنشاء السكشن
    $section = Section::create([
        'page_id' => $this->pageId,
        'key' => $this->sectionKey,
        'order' => $order,
    ]);

    foreach ($this->languages as $lang) {
        $locale = $lang->code;
        $data = $this->translations[$locale] ?? [];
        $content = [];

        // ⬇️ بناء المحتوى حسب نوع السكشن
        switch ($this->sectionKey) {
            case 'hero':
                $content = [
                    'subtitle' => $data['subtitle'] ?? '',
                    'button_text' => $data['button_text'] ?? '',
                    'button_url' => $data['button_url'] ?? '',
                ];
                break;

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
                // يمكنك إضافة منطق إضافي هنا لاحقًا عند تخصيص هذه السكشنات
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

    $this->reset(['sectionKey', 'sectionOrder', 'translations', 'translationsData']);
    $this->loadSections();
    session()->flash('success', 'تم إضافة السكشن بنجاح.');
}


    public function updateSection($sectionId, $locale = null)
    {
        $section = Section::with('translations')->findOrFail($sectionId);

        // إذا لم يتم تحديد اللغة، استخدم اللغة الحالية
        $targetLocales = $locale ? [$locale] : array_column($this->languages->toArray(), 'code');

        foreach ($targetLocales as $code) {
            $data = $this->translationsData[$sectionId][$code] ?? [];

            $content = [
                'subtitle' => $data['subtitle'] ?? '',
                'button_text' => $data['button_text'] ?? '',
                'button_url' => $data['button_url'] ?? '',
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
        'languages' => $this->languages,              // ضروري
        'sections' => $this->sections,                // يُفضل تمريره
        'availableKeys' => $this->availableKeys,      // يُستخدم في الواجهة
        'activeLang' => $this->activeLang,            // يُستخدم في التبويبات
        'sectionKey' => $this->sectionKey,
    ]);
    }
}
