<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\SectionTranslation;

class Features2Section extends BaseSectionComponent
{
    public string $activeTab = 'content';

    /**
     * Tailwind-based palette for background variants.
     *
     * @var array<string, array{label:string,classes:string,preview:string,is_dark:bool}>
     */
    protected array $backgroundPresets = [
        // Neutral
        'white'        => ['label' => 'White',          'classes' => 'bg-white dark:bg-gray-950',         'preview' => '#ffffff', 'is_dark' => false],
        'gray'         => ['label' => 'Soft Gray',      'classes' => 'bg-gray-50 dark:bg-gray-900',       'preview' => '#f9fafb', 'is_dark' => false],
        'stone'        => ['label' => 'Warm Stone',     'classes' => 'bg-stone-50 dark:bg-stone-900',     'preview' => '#faf5f0', 'is_dark' => false],
        'slate-light'  => ['label' => 'Slate Light',    'classes' => 'bg-slate-100 dark:bg-slate-900',    'preview' => '#e2e8f0', 'is_dark' => false],
        'slate-dark'   => ['label' => 'Midnight Slate', 'classes' => 'bg-slate-900 text-white',           'preview' => '#0f172a', 'is_dark' => true],
        'zinc-dark'    => ['label' => 'Charcoal',       'classes' => 'bg-zinc-900 text-white',            'preview' => '#18181b', 'is_dark' => true],
        'black'        => ['label' => 'Pure Black',     'classes' => 'bg-gray-950 text-white',            'preview' => '#020617', 'is_dark' => true],

        // Cool
        'sky'          => ['label' => 'Sky Glow',       'classes' => 'bg-sky-50 dark:bg-sky-900',         'preview' => '#eff6ff', 'is_dark' => false],
        'blue'         => ['label' => 'Bright Blue',    'classes' => 'bg-blue-50 dark:bg-blue-900',       'preview' => '#dbeafe', 'is_dark' => false],
        'indigo'       => ['label' => 'Indigo Depth',   'classes' => 'bg-indigo-600 text-white',          'preview' => '#4f46e5', 'is_dark' => true],
        'violet'       => ['label' => 'Violet Bold',    'classes' => 'bg-violet-600 text-white',          'preview' => '#7c3aed', 'is_dark' => true],
        'purple'       => ['label' => 'Royal Purple',   'classes' => 'bg-purple-600 text-white',          'preview' => '#9333ea', 'is_dark' => true],

        // Warm
        'amber'        => ['label' => 'Soft Amber',     'classes' => 'bg-amber-50 dark:bg-amber-900',     'preview' => '#fef3c7', 'is_dark' => false],
        'orange'       => ['label' => 'Vivid Orange',   'classes' => 'bg-orange-500 text-white',          'preview' => '#f97316', 'is_dark' => true],
        'rose'         => ['label' => 'Blush Rose',     'classes' => 'bg-rose-50 dark:bg-rose-900',       'preview' => '#ffe4e6', 'is_dark' => false],
        'rose-deep'    => ['label' => 'Deep Rose',      'classes' => 'bg-rose-600 text-white',            'preview' => '#e11d48', 'is_dark' => true],

        // Natural
        'emerald'      => ['label' => 'Fresh Emerald',  'classes' => 'bg-emerald-50 dark:bg-emerald-900', 'preview' => '#ecfdf5', 'is_dark' => false],
        'emerald-deep' => ['label' => 'Deep Emerald',   'classes' => 'bg-emerald-600 text-white',         'preview' => '#059669', 'is_dark' => true],
        'teal'         => ['label' => 'Teal Accent',    'classes' => 'bg-teal-500 text-white',            'preview' => '#14b8a6', 'is_dark' => true],
    ];

    /**
     * Palette grouping for UI presentation.
     *
     * @var array<string, string[]>
     */
    protected array $backgroundGroups = [
        'Neutral' => ['white', 'gray', 'stone', 'slate-light', 'slate-dark', 'zinc-dark', 'black'],
        'Cool'    => ['sky', 'blue', 'indigo', 'violet', 'purple'],
        'Warm'    => ['amber', 'orange', 'rose', 'rose-deep'],
        'Natural' => ['emerald', 'emerald-deep', 'teal'],
    ];

    public function mount(): void
    {
        parent::mount();

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $features = $content['features'] ?? [];
            if (!is_array($features)) {
                $features = [];
            }

            $backgroundVariant = $this->normalizeBackgroundVariant(
                $content['background_variant'] ?? null,
                $content['background_color'] ?? null
            );

            $this->translationsData[$lang->code] = [
                'title'              => $translation?->title ?? '',
                'subtitle'           => $content['subtitle'] ?? '',
                'button_text'        => $content['button_text'] ?? '',
                'button_url'         => $content['button_url'] ?? '',
                'background_variant' => $backgroundVariant,
                'features'           => $features,
            ];
        }
    }

    public function updateFeatures2Section(): void
    {
        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale'     => $locale,
            ]);

            $features = $data['features'] ?? [];
            if (!is_array($features)) {
                $features = [];
            }

            $backgroundVariant = $this->normalizeBackgroundVariant($data['background_variant'] ?? null);

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle'           => $data['subtitle'] ?? '',
                'button_text'        => $data['button_text'] ?? '',
                'button_url'         => trim((string) ($data['button_url'] ?? '')),
                'background_variant' => $backgroundVariant,
                'features'           => array_values($features),
            ];

            $translation->save();
        }

        $this->section->order = $this->order;
        $this->section->save();

        session()->flash('success', 'Features section updated successfully.');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['content', 'style'], true) ? $tab : 'content';
    }

    public function addFeature(string $locale): void
    {
        if (!isset($this->translationsData[$locale]['features']) || !is_array($this->translationsData[$locale]['features'])) {
            $this->translationsData[$locale]['features'] = [];
        }

        $this->translationsData[$locale]['features'][] = [
            'icon'        => '',
            'title'       => '',
            'description' => '',
        ];
    }

    public function removeFeature(string $locale, int $index): void
    {
        if (!isset($this->translationsData[$locale]['features'][$index])) {
            return;
        }

        unset($this->translationsData[$locale]['features'][$index]);
        $this->translationsData[$locale]['features'] = array_values($this->translationsData[$locale]['features']);
    }

    public function resetBackgroundVariant(string $locale): void
    {
        if (!isset($this->translationsData[$locale])) {
            return;
        }

        $this->translationsData[$locale]['background_variant'] = 'white';
    }

    public function render()
    {
        return view('livewire.dashboard.sections.features-2', [
            'backgroundPresets' => $this->backgroundPresets,
            'backgroundGroups'  => $this->backgroundGroups,
        ]);
    }

    protected function normalizeBackgroundVariant(?string $variant, ?string $legacyColor = null): string
    {
        if (is_string($variant) && isset($this->backgroundPresets[$variant])) {
            return $variant;
        }

        if (is_string($legacyColor)) {
            $hex = strtolower(trim($legacyColor));

            return match ($hex) {
                '#ffffff', '#fff'        => 'white',
                '#f9fafb', '#f8fafc'     => 'gray',
                '#faf5f0', '#f5e9df'     => 'stone',
                '#e2e8f0', '#cbd5e1'     => 'slate-light',
                '#0f172a', '#111827'     => 'slate-dark',
                '#18181b'                => 'zinc-dark',
                '#020617'                => 'black',
                '#eff6ff', '#e0f2fe'     => 'sky',
                '#dbeafe', '#bfdbfe'     => 'blue',
                '#4f46e5', '#312e81'     => 'indigo',
                '#7c3aed', '#5b21b6'     => 'violet',
                '#9333ea', '#6b21a8'     => 'purple',
                '#fef3c7', '#fde68a'     => 'amber',
                '#f97316', '#ea580c'     => 'orange',
                '#ffe4e6', '#fecdd3'     => 'rose',
                '#e11d48', '#be123c'     => 'rose-deep',
                '#ecfdf5', '#d1fae5'     => 'emerald',
                '#059669', '#047857'     => 'emerald-deep',
                '#14b8a6', '#0f766e'     => 'teal',
                default                  => 'white',
            };
        }

        return 'white';
    }
}
