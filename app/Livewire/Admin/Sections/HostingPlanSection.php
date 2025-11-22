<?php

namespace App\Livewire\Admin\Sections;

use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\SectionTranslation;

class HostingPlanSection extends BaseSectionComponent
{
    public $availableCategories = [];
    public $selectedCategoryId = null;
    public $selectedCategory = null;
    public $categorySlug = '';
    public $previewPlansCount = 0;

    public function mount()
    {
        parent::mount();

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $this->translationsData[$lang->code] = [
                'title' => $translation?->title ?? '',
                'subtitle' => $content['subtitle'] ?? '',
                'hosting-plans' => $content['hosting-plans'] ?? [],
            ];
        }

        $this->availableCategories = PlanCategory::with('translations')->get();

        $sectionTranslation = $this->section->translations->firstWhere('locale', app()->getLocale())
            ?? $this->section->translations->first();

        $storedContent = is_array($sectionTranslation?->content) ? $sectionTranslation->content : [];

        $this->selectedCategoryId = $storedContent['plan_category_id'] ?? null;
        $this->categorySlug = isset($storedContent['plan_category_slug'])
            ? trim((string) $storedContent['plan_category_slug'])
            : '';

        if ($this->selectedCategoryId) {
            $this->selectedCategory = PlanCategory::with('translations')->find($this->selectedCategoryId);
        } elseif ($this->categorySlug !== '') {
            $this->selectedCategory = PlanCategory::whereHas('translations', function ($query) {
                $query->where('slug', $this->categorySlug);
            })->with('translations')->first();
        }

        $this->recalcPreviewCount();
    }

    public function updatedSelectedCategoryId($value)
    {
        $this->categorySlug = '';
        $this->selectedCategory = $value ? PlanCategory::with('translations')->find($value) : null;

        $this->recalcPreviewCount();
    }

    public function updatedCategorySlug($value)
    {
        $slug = trim((string) $value);
        $this->categorySlug = $slug;
        $this->selectedCategory = null;

        if ($slug !== '') {
            $this->selectedCategory = PlanCategory::whereHas('translations', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })->with('translations')->first();
        }

        $this->recalcPreviewCount();
    }

    protected function recalcPreviewCount()
    {
        $query = Plan::where('is_active', true);

        if ($this->selectedCategory) {
            $query->where('plan_category_id', $this->selectedCategory->id);
        } elseif ($this->selectedCategoryId) {
            $query->where('plan_category_id', (int) $this->selectedCategoryId);
        } elseif ($this->categorySlug !== '') {
            $category = PlanCategory::whereHas('translations', function ($query) {
                $query->where('slug', $this->categorySlug);
            })->first();

            if ($category) {
                $query->where('plan_category_id', $category->id);
            }
        }

        $this->previewPlansCount = $query->count();
    }

    public function updateHostingPlansSection()
    {
        $this->categorySlug = trim((string) $this->categorySlug);

        $this->validate([
            "translationsData.{$this->activeLang}.title" => 'required|string|max:250',
            "translationsData.{$this->activeLang}.subtitle" => 'nullable|string|max:500',
            'selectedCategoryId' => 'nullable|integer|exists:plan_categories,id',
            'categorySlug' => 'nullable|string|max:200',
        ]);

        $this->selectedCategoryId = $this->selectedCategoryId ? (int) $this->selectedCategoryId : null;

        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale' => $locale,
            ]);

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle' => $data['subtitle'] ?? '',
                'hosting-plans' => $data['hosting-plans'] ?? [],
                'plan_category_id' => $this->selectedCategoryId,
                'plan_category_slug' => $this->categorySlug !== '' ? $this->categorySlug : null,
            ];

            $translation->save();
        }

        $this->section->order = $this->order;
        $this->section->save();

        $this->flashSuccess('Hosting plan section updated successfully.');
    }

    public function removeHostingPlansSection($locale, $index)
    {
        if (isset($this->translationsData[$locale]['hosting-plans'][$index])) {
            unset($this->translationsData[$locale]['hosting-plans'][$index]);
            $this->translationsData[$locale]['hosting-plans'] = array_values($this->translationsData[$locale]['hosting-plans']);
        }
    }

    public function render()
    {
        return view('livewire.dashboard.sections.hosting-plans-section', [
            'availableCategories' => $this->availableCategories,
            'selectedCategory' => $this->selectedCategory,
            'previewPlansCount' => $this->previewPlansCount,
        ]);
    }
}

