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

    /** ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ´ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ² ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ´ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ°ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± (ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ°ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ  ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چ) */
    public bool $showPalette = false;
    public ?string $paletteSearch = null;
    public $paletteOrder = null;

    /** Available section keys (lowercase) */
    /** Available section keys (lowercase) */
    public array $availableKeys = [
        'hero',
        'features',
        'features-2',
        'features-3',
        'cta',
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
        'faq',
    ];

    /** Display metadata for the palette cards */
    public array $keyMeta = [
        'hero'            => ['label' => 'Hero',               'unique' => true,  'desc' => 'Primary hero banner with heading, description, and actions.', 'thumb' => null],
        'features'        => ['label' => 'Features',           'unique' => true,  'desc' => 'Icon list that highlights product benefits.',                   'thumb' => null],
        'features-2'      => ['label' => 'Features 2',         'unique' => true,  'desc' => 'Expanded feature grid with optional button.',                  'thumb' => null],
        'features-3'      => ['label' => 'Features 3',         'unique' => true,  'desc' => 'Layered feature cards with white background.',                'thumb' => null],
        'cta'             => ['label' => 'Call To Action',     'unique' => false, 'desc' => 'Promotional block with headline and primary button.',        'thumb' => null],
        'services'        => ['label' => 'Services',           'unique' => true,  'desc' => 'Service list with short supporting copy.',                   'thumb' => null],
        'templates'       => ['label' => 'Templates',          'unique' => true,  'desc' => 'Dynamic listing of available templates.',                     'thumb' => null],
        'works'           => ['label' => 'Works',              'unique' => false, 'desc' => 'Portfolio/gallery grid that can repeat.',                     'thumb' => null],
        'home-works'      => ['label' => 'Home Works',         'unique' => true,  'desc' => 'Homepage spotlight of selected works with CTA.',             'thumb' => null],
        'testimonials'    => ['label' => 'Testimonials',       'unique' => true,  'desc' => 'Customer testimonials slider/carousel.',                     'thumb' => null],
        'blog'            => ['label' => 'Blog',               'unique' => true,  'desc' => 'Latest blog posts preview with links.',                       'thumb' => null],
        'banner'          => ['label' => 'Banner',             'unique' => false, 'desc' => 'Simple banner for text plus optional button.',               'thumb' => null],
        'search-domain'   => ['label' => 'Search Domain',      'unique' => true,  'desc' => 'Domain search widget.',                                       'thumb' => null],
        'templates-pages' => ['label' => 'Templates Pages',    'unique' => true,  'desc' => 'Template grid with filtering and ordering.',                  'thumb' => null],
        'hosting-plans'   => ['label' => 'Hosting Plans',      'unique' => true,  'desc' => 'Overview of available hosting plans.',                         'thumb' => null],
        'faq'             => ['label' => 'FAQ',                'unique' => false, 'desc' => 'Frequently asked questions with answers.',                   'thumb' => null],
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

    /** ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢أ¢â‚¬â€œط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ°ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ­ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ« lowercase + ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ  ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ«ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œأ¢â‚¬ع‘ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ© ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ©ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± */
    private function normalizeKey(?string $key): string
    {
        $k = trim((string)$key);
        if ($k === '') return '';
        $k = Str::of($k)->lower()->toString();

        // ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ«ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬â„¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ®ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢أ¢â‚¬â€œ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ©ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± ط·آ·ط¢آ¸ط·آ¢أ¢â‚¬آ ط·آ¸أ¢â‚¬â„¢ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ´ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ±
        if (in_array($k, ['search_domain', 'searchdomain'])) {
            return 'search-domain';
        }
        return $k;
    }

    /** ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ´ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ°ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± */
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

    /** ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ­ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ  ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ´ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ² */
    public function addFromPalette(string $key): void
    {
        $key = $this->normalizeKey($key);

        // ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ°ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¸أ¢â‚¬ع©ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œأ¢â‚¬ع‘ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ« ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¢آ¤ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ«ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط·إ’ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ«ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط·إ’ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط·إ’ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¯ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ Hosting Plans
        logger()->info('[Sections] addFromPalette clicked', [
            'page_id' => $this->pageId,
            'key' => $key,
        ]);

        if (!in_array($key, $this->availableKeys, true)) {
            session()->flash('error', 'ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œأ¢â‚¬ع‘ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¢آ¤ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ  ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢أ¢â‚¬ع©ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬â„¢ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬â„¢ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾.');
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

    /** ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ­ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ±/ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢أ¢â‚¬آ¢ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œأ¢â‚¬ع‘ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¢آ¤ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ  */
    public function addSection()
    {
        $this->sectionKey = $this->normalizeKey($this->sectionKey);

        // ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ°ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¸أ¢â‚¬ع©ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ² ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ­ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œأ¢â‚¬ع‘ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¢آ¤ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ«ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط·إ’
        if ($this->sectionKey === 'hosting-plans') {
            logger()->info('[Sections] Trying to add hosting-plans', [
                'page_id' => $this->pageId,
            ]);
        }

        $this->validate([
            'sectionKey' => 'required',
        ], [], [
            'sectionKey' => 'ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œأ¢â‚¬ع‘ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¢آ¤ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ ',
        ]);

        // ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ©ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ© ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬â„¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ» ط·آ·ط¢آ¸ط·آ¢أ¢â€ڑآ¬ط·آ¢ط¢آ¤ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œأ¢â‚¬ع‘ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ°ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ­ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ«ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œأ¢â‚¬ع‘ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¢آ¤ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ  ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ«ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ³ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬â„¢ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ  ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬â„¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ±

        // ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œأ¢â‚¬ع‘ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ° order ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ : ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ´ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬â„¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢أ¢â‚¬ع© ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬â„¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ´ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ null ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ´ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ£ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ©ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬â„¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¢آ¢ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¨ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± 1
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
                        case 'features-3':
                            $featuresRaw = $data['features'] ?? '';
                            $content = [
                                'subtitle' => $data['subtitle'] ?? '',
                                'features' => is_array($featuresRaw)
                                    ? $featuresRaw
                                    : array_filter(array_map('trim', explode("\n", $featuresRaw))),
                            ];
                            break;

                        case 'features-2':
                            $featuresRaw = $data['features'] ?? '';
                            $content = [
                                'subtitle'           => $data['subtitle'] ?? '',
                                'button_text'        => $data['button_text'] ?? '',
                                'button_url'         => $data['button_url'] ?? '',
                                'background_variant' => $data['background_variant'] ?? 'white',
                                'features'           => is_array($featuresRaw)
                                    ? $featuresRaw
                                    : array_filter(array_map('trim', explode("\n", $featuresRaw))),
                            ];
                            break;

                        case 'cta':
                            $content = [
                                'subtitle'            => $data['subtitle'] ?? '',
                                'badge'               => $data['badge'] ?? '',
                                'primary_button_text' => $data['primary_button_text'] ?? ($data['button_text'] ?? ''),
                                'primary_button_url'  => $data['primary_button_url'] ?? ($data['button_url'] ?? ''),
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
                                'subtitle'      => $data['subtitle'] ?? '',
                                'hosting-plans' => is_array($data['hosting-plans'] ?? null)
                                    ? $data['hosting-plans']
                                    : [],
                            ];
                            break;

                        case 'faq':
                            $itemsRaw = $data['items'] ?? $data['faq'] ?? [];
                            $items = is_array($itemsRaw) ? $itemsRaw : [];

                            $content = [
                                'subtitle' => $data['subtitle'] ?? '',
                                'items'    => array_values(array_filter(array_map(static function ($item) {
                                    if (!is_array($item)) {
                                        return null;
                                    }

                                    $question = trim((string)($item['question'] ?? ''));
                                    $answer   = trim((string)($item['answer'] ?? ''));

                                    if ($question === '' && $answer === '') {
                                        return null;
                                    }

                                    return [
                                        'question' => $question,
                                        'answer'   => $answer,
                                    ];
                                }, $items))),
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
        session()->flash('success', 'Section created successfully.');
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
        session()->flash('success', 'Section updated successfully.');
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

            // ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ«ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬â„¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œأ¢â‚¬ع‘ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ¢ط¢آ»ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ³ ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¹آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ´ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ§ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ± (JS) ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ°ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ«ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ  ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬ع©ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ²ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ  ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ°ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ´ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµ - Livewire v3
            $this->dispatch('section-deleted-success', sectionId: $id);
        } catch (Exception $e) {
            logger()->error('ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¢آ¤ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آµط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€œأ¢â‚¬ع©ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢ط¸آ¾ ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط·آ·ط¢آ¯ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬â€چط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œأ¢â‚¬ع‘ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ£ط¢آ¢ط£آ¢أ¢â‚¬آ¢ط¹آ¾ط£آ¢أ¢â‚¬â€Œط¢آ¤ط£آ¢أ¢â‚¬â€Œط¹آ©ط·آ¢أ¢â‚¬آ : ' . $e->getMessage());
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
