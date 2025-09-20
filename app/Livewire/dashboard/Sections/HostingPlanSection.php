<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\SectionTranslation;
use App\Models\PlanCategory;
use App\Models\Plan;

class HostingPlanSection extends BaseSectionComponent
{
    public $availableCategories = [];
    public $selectedCategoryId = null;
    public $selectedCategory = null;
    public $categorySlug = '';
    public $previewPlansCount = 0;

    public function mount()
    {
        parent::mount(); // استدعاء mount من BaseSectionComponent

        // إعداد البيانات للترجمات
        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $this->translationsData[$lang->code] = [
                'title' => $translation?->title ?? '',
                'subtitle' => $content['subtitle'] ?? '',
                'hosting-plans' => $content['hosting-plans'] ?? [],
            ];
        }

        // تحميل التصنيفات
        $this->availableCategories = PlanCategory::with('translations')->get();

        // تهيئة التصنيف المحدد
        $sectionTranslation = $this->section->translations->firstWhere('locale', app()->getLocale())
            ?? $this->section->translations->first();

        $storedContent = is_array($sectionTranslation?->content) ? $sectionTranslation->content : [];

        $this->selectedCategoryId = $storedContent['plan_category_id'] ?? null;
        $this->categorySlug = $storedContent['plan_category_slug'] ?? '';

        if ($this->selectedCategoryId) {
            $this->selectedCategory = PlanCategory::with('translations')->find($this->selectedCategoryId);
        } elseif (!empty($this->categorySlug)) {
            $this->selectedCategory = PlanCategory::whereHas('translations', function ($q) {
                $q->where('slug', $this->categorySlug);
            })->with('translations')->first();
        }

        $this->recalcPreviewCount();
    }

    public function updatedSelectedCategoryId($val)
    {
        $this->categorySlug = '';
        $this->selectedCategory = $val ? PlanCategory::with('translations')->find($val) : null;
        $this->recalcPreviewCount();
    }

    public function updatedCategorySlug($val)
    {
        $this->selectedCategory = null;
        $slug = trim((string)$val);
        if ($slug !== '') {
            $cat = PlanCategory::whereHas('translations', function ($q) use ($slug) {
                $q->where('slug', $slug);
            })->with('translations')->first();
            $this->selectedCategory = $cat;
        }
        $this->recalcPreviewCount();
    }

    protected function recalcPreviewCount()
    {
        $query = Plan::where('is_active', true);

        if ($this->selectedCategory) {
            $query->where('plan_category_id', $this->selectedCategory->id);
        } elseif ($this->selectedCategoryId) {
            $query->where('plan_category_id', (int)$this->selectedCategoryId);
        } elseif (!empty($this->categorySlug)) {
            $cat = PlanCategory::whereHas('translations', function ($q) {
                $q->where('slug', $this->categorySlug);
            })->first();
            if ($cat) $query->where('plan_category_id', $cat->id);
        }

        $this->previewPlansCount = $query->count();
    }

    public function updateHostingPlansSection()
    {
        $this->validate([
            "translationsData.{$this->activeLang}.title" => 'required|string|max:250',
            "translationsData.{$this->activeLang}.subtitle" => 'nullable|string|max:500',
            'selectedCategoryId' => 'nullable|integer|exists:plan_categories,id',
            'categorySlug' => 'nullable|string|max:200',
        ]);

        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale' => $locale,
            ]);

            $translation->title = $data['title'] ?? '';
            $translation->content = [
                'subtitle' => $data['subtitle'] ?? '',
                'hosting-plans' => $data['hosting-plans'] ?? [],
                'plan_category_id' => $this->selectedCategoryId ? (int)$this->selectedCategoryId : null,
                'plan_category_slug' => $this->categorySlug ?: null,
            ];

            $translation->save();
        }

        $this->section->order = $this->order;
        $this->section->save();

        // استخدام Flash Session فقط مثل سكشناتك الأخرى
        $this->flashSuccess('تم تحديث قسم الخطط بنجاح.');
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
