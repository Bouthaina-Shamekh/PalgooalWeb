<?php

namespace App\Livewire\Dashboard;

use App\Models\Section;
use App\Models\SectionTranslation;
use App\Models\Language;
use Livewire\Component;
use App\Livewire\Dashboard\Sections\HeroSection;
use Illuminate\Support\Str;
use Exception;
use Livewire\Attributes\On;

class Sections extends Component
{
    public $pageId;
    public $sections = [];
    public $languages;

    public $sectionKey = '';
    public $sectionOrder = 0;
    public $translations = [];
    public $translationsData = [];

    /** لوحة ودجت جانبية (بدون مودال) */
    public bool $showPalette = false;
    public ?string $paletteSearch = null;
    public $paletteOrder = null;

    /** مفاتيح متاحة (lowercase موحّدة) */
    public array $availableKeys = [
        'hero',
        'features',
        'features-2',
        'services',
        'templates',
        'works',
        'home-works',
        'testimonials',
        'blog',
        'banner',
        'search-domain',
        'templates-pages',
        'hosting-plans',
    ];

    /** وصف/إعدادات عرض لكل ودجت */
    public array $keyMeta = [
        'hero'            => ['label' => 'الواجهة الرئيسية (Hero)',                    'unique' => true,  'desc' => 'بانر رئيسي مع عنوان/وصف وأزرار.', 'thumb' => null],
        'features'        => ['label' => 'مميزات (Features)',                          'unique' => true,  'desc' => 'قائمة مميزات مع أيقونات.',       'thumb' => null],
        'features-2'      => ['label' => 'مميزات متقدمة (Features 2)',                 'unique' => true,  'desc' => 'شبكة مميزات بأيقونات ورسوم.',    'thumb' => null],
        'services'        => ['label' => 'الخدمات (Services)',                          'unique' => true,  'desc' => 'شبكة للخدمات مع وصف مختصر.',     'thumb' => null],
        'templates'       => ['label' => 'القوالب (Templates)',                         'unique' => true,  'desc' => 'قائمة قوالب ديناميكية.',         'thumb' => null],
        'works'           => ['label' => 'الأعمال (Works)',                             'unique' => false, 'desc' => 'معرض أعمال قابل للتكرار.',       'thumb' => null],
        'home-works'      => ['label' => 'أعمالنا في الهوم (Home Works)',               'unique' => true,  'desc' => 'مقتطف أعمال للرئيسية.',          'thumb' => null],
        'testimonials'    => ['label' => 'آراء العملاء (Testimonials)',                 'unique' => true,  'desc' => 'سلايدر تقييمات العملاء.',        'thumb' => null],
        'blog'            => ['label' => 'المدونة (Blog)',                               'unique' => true,  'desc' => 'آخر المقالات مع المزيد.',        'thumb' => null],
        'banner'          => ['label' => 'اللوحة (Banner)',                             'unique' => false, 'desc' => 'بانر بسيط لنص + زر.',            'thumb' => null],
        'search-domain'   => ['label' => 'بحث الدومين (Search Domain)',                 'unique' => true,  'desc' => 'محرك بحث دومين.',               'thumb' => null],
        'templates-pages' => ['label' => 'عرض القوالب مع فلتر (Templates Pages)',       'unique' => true,  'desc' => 'شبكة قوالب بفلترة وترتيب.',     'thumb' => null],
        'hosting-plans'   => ['label' => 'خطط الاستضافة (Hosting Plans)',               'unique' => true,  'desc' => 'عرض خطط الاستضافة المتاحة.',    'thumb' => null],
    ];

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

        // reset translationsData to avoid stale entries
        $this->translationsData = [];

        foreach ($this->sections as $section) {
            foreach ($this->languages as $lang) {
                $translation = $section->translations->firstWhere('locale', $lang->code);
                $content = is_array($translation?->content) ? $translation->content : [];

                $this->translationsData[$section->id][$lang->code] = [
                    'title'    => $translation?->title ?? '',
                    'subtitle' => $content['subtitle'] ?? '',
                ];
            }
        }
    }

    /** تطبيع المفتاح إلى lowercase + دعم الأسماء القديمة */
    private function normalizeKey(?string $key): string
    {
        $k = trim((string)$key);
        if ($k === '') return '';
        $k = Str::of($k)->lower()->toString();

        // خرائط قديمة → جديدة
        if (in_array($k, ['search_domain', 'searchdomain'])) {
            return 'search-domain';
        }
        return $k;
    }

    /** فتح اللوحة الجانبية */
    #[On('open-sections-palette')]
    public function openPalette(): void
    {
        $this->reset(['paletteSearch', 'paletteOrder']);
        $this->showPalette = true;
    }

    public function closePalette(): void
    {
        $this->showPalette = false;
    }

    /** إضافة من الودجت */
    public function addFromPalette(string $key): void
    {
        $key = $this->normalizeKey($key);

        // تتبّع للمساعدة على التشخيص خصوصاً مع Hosting Plans
        logger()->info('[Sections] addFromPalette clicked', [
            'page_id' => $this->pageId,
            'key' => $key,
        ]);

        if (!in_array($key, $this->availableKeys, true)) {
            session()->flash('error', 'نوع السكشن غير معروف.');
            return;
        }

        $this->sectionKey   = $key;
        $this->sectionOrder = (int)($this->paletteOrder ?? 0);
        $this->addSection();

        $this->showPalette = false;
    }

    public function getPaletteKeysProperty(): array
    {
        if (!$this->paletteSearch) return $this->availableKeys;

        $q = mb_strtolower($this->paletteSearch);
        return array_values(array_filter($this->availableKeys, function ($key) use ($q) {
            $meta = $this->keyMeta[$key] ?? ['label' => $key];
            return str_contains(mb_strtolower($key), $q)
                || str_contains(mb_strtolower($meta['label']), $q);
        }));
    }

    /** إضافة/حفظ سكشن */
    public function addSection()
    {
        $this->sectionKey = $this->normalizeKey($this->sectionKey);

        // تتبّع محاولات الإضافة للمساعدة في التشخيص
        if ($this->sectionKey === 'hosting-plans') {
            logger()->info('[Sections] Trying to add hosting-plans', [
                'page_id' => $this->pageId,
            ]);
        }

        $this->validate([
            'sectionKey' => 'required',
        ], [], [
            'sectionKey' => 'نوع السكشن',
        ]);

        // لا تحقق تفرد — السماح بإضافة أي سكشن أكثر من مرة

        // حساب order آمن: لو الجدول فارغ يرجع null فنجعل قيمة افتراضية 1
        $order = $this->sectionOrder ?: (Section::where('page_id', $this->pageId)->max('order') ?? 0) + 1;

        switch ($this->sectionKey) {
            case 'hero':
                HeroSection::create($this->pageId, $order, $this->translations);
                logger()->info('[Sections] Section created', ['key' => $this->sectionKey, 'page_id' => $this->pageId]);
                break;

            default:
                $section = Section::create([
                    'page_id' => $this->pageId,
                    'key'     => $this->sectionKey,
                    'order'   => $order,
                ]);
                logger()->info('[Sections] Section created', ['id' => $section->id, 'key' => $this->sectionKey, 'page_id' => $this->pageId]);

                foreach ($this->languages as $lang) {
                    $locale  = $lang->code;
                    $data    = $this->translations[$locale] ?? [];
                    $content = [];

                    switch ($this->sectionKey) {
                        case 'features':
                        case 'features-2':
                            $featuresRaw = $data['features'] ?? '';
                            $content = [
                                'subtitle' => $data['subtitle'] ?? '',
                                'button_text' => $data['button_text'] ?? '',
                                'button_url' => $data['button_url'] ?? '',
                                'background_variant' => $data['background_variant'] ?? 'white',
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

                        case 'banner':
                        case 'templates':
                        case 'works':
                            $content = [
                                'subtitle' => $data['subtitle'] ?? '',
                            ];
                            break;

                        case 'home-works':
                            $content = [
                                'subtitle'      => $data['subtitle'] ?? '',
                                'button_text-1' => $data['button_text-1'] ?? '',
                                'button_url-1'  => $data['button_url-1'] ?? '',
                            ];
                            break;

                        case 'templates-pages':
                            $content = [
                                'subtitle'          => $data['subtitle'] ?? '',
                                'template-sections' => $data['template-sections'] ?? '',
                            ];
                            break;
                        case 'hosting-plans':
                            $content = [
                                'subtitle'       => $data['subtitle'] ?? '',
                                'hosting-plans'  => is_array($data['hosting-plans'] ?? null)
                                    ? $data['hosting-plans']
                                    : [],
                            ];
                            break;

                        case 'testimonials':
                        case 'search-domain':
                        
                        case 'blog':
                            $content = [];
                            break;
                    }

                    SectionTranslation::create([
                        'section_id' => $section->id,
                        'locale'     => $locale,
                        'title'      => $data['title'] ?? '',
                        'content'    => $content,
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
        $targetLocales = $locale ? [$locale] : $this->languages->pluck('code')->toArray();

        foreach ($targetLocales as $code) {
            $data = $this->translationsData[$sectionId][$code] ?? [];

            $content = [
                'subtitle' => $data['subtitle'] ?? '',
            ];

            $translation = SectionTranslation::firstOrNew([
                'section_id' => $sectionId,
                'locale'     => $code,
            ]);

            $translation->title   = $data['title'] ?? '';
            $translation->content = $content;
            $translation->save();

            $section->order = $this->sectionOrder ?: $section->order;
            $section->save();
        }

        $this->loadSections();
        session()->flash('success', 'تم تحديث السكشن بنجاح.');
    }

    #[On('deleteSection')]
    public function deleteSection($id)
    {
        try {
            $section = Section::find($id);
            if ($section) {
                $section->delete();
            } else {
                logger()->warning('[Sections] deleteSection called for non-existing id', ['id' => $id]);
            }
            $this->loadSections();

            // أرسل حدث للواجهة (JS) بأن الحذف تم بنجاح - Livewire v3
            $this->dispatch('section-deleted-success', sectionId: $id);
        } catch (Exception $e) {
            logger()->error('فشل حذف السكشن: ' . $e->getMessage());
            $this->dispatch('section-delete-failed', sectionId: $id, error: $e->getMessage());
        }
    }

    public function setActiveLang($code)
    {
        $this->activeLang = $code;
    }

    public function render()
    {
        return view('livewire.dashboard.sections', [
            'languages'     => $this->languages,
            'sections'      => $this->sections,
            'availableKeys' => $this->availableKeys,
            'activeLang'    => $this->activeLang,
            'sectionKey'    => $this->sectionKey,
            'showPalette'   => $this->showPalette,
            'paletteKeys'   => $this->paletteKeys,
            'keyMeta'       => $this->keyMeta,
        ]);
    }
}
