<?php

namespace App\Livewire\Admin\Template;

use Livewire\Component;
use App\Models\CategoryTemplate;
use App\Models\Language;
use Illuminate\Validation\Rule;

/**
 * @deprecated deprecated - do not use. Legacy admin Livewire component retained only for fallback safety.
 */
class CategoryManagement extends Component
{
    public $categories = [];

    public $languages;
    public $activeLang;

    public $translations = [];

    public $mode = 'create';
    public $editingCategoryId;

    protected function rules()
    {
        $rules = [];

        foreach ($this->languages as $lang) {
            $langCode = $lang->code;

            $rules["translations.{$langCode}.name"] = 'required|string|max:255';
            $rules["translations.{$langCode}.description"] = 'nullable|string';

            $slugRules = ['required', 'string', 'alpha_dash'];
            $translationId = $this->getTranslationId($langCode);
            $slugRules[] = Rule::unique('category_template_translations', 'slug')->ignore($translationId);

            $rules["translations.{$langCode}.slug"] = $slugRules;
        }

        return $rules;
    }

    protected $messages = [
        'translations.*.name.required' => 'ط§ظ„ط§ط³ظ… ظ…ط·ظ„ظˆط¨.',
        'translations.*.name.max' => 'ظٹط¬ط¨ ط£ظ„ط§ ظٹطھط¬ط§ظˆط² ط§ظ„ط§ط³ظ… 255 ط­ط±ظپظ‹ط§.',
        'translations.*.slug.required' => 'ط§ظ„ط±ط§ط¨ط· (slug) ظ…ط·ظ„ظˆط¨.',
        'translations.*.slug.alpha_dash' => 'ظٹط¬ط¨ ط£ظ† ظٹط­طھظˆظٹ ط§ظ„ط±ط§ط¨ط· ط¹ظ„ظ‰ ط£ط­ط±ظپ ظˆط£ط±ظ‚ط§ظ… ظˆط´ط±ط·ط§طھ ظپظ‚ط·.',
        'translations.*.slug.unique' => 'ظ‡ط°ط§ ط§ظ„ط±ط§ط¨ط· ظ…ط³طھط®ط¯ظ… ط¨ط§ظ„ظپط¹ظ„.',
        'translations.*.slug.max' => 'ط§ظ„ط±ط§ط¨ط· ط·ظˆظٹظ„ ط¬ط¯ظ‹ط§.',
        'translations.*.description.string' => 'ط§ظ„ظˆطµظپ ط؛ظٹط± طµط§ظ„ط­.',
    ];

    protected function getTranslationId($langCode)
    {
        if ($this->mode === 'edit' && $this->editingCategoryId) {
            $category = CategoryTemplate::find($this->editingCategoryId);
            return $category?->translations()->where('locale', $langCode)->first()?->id;
        }
        return null;
    }

    public function mount()
    {
        $this->languages = Language::where('is_active', true)->get();
        $this->activeLang = $this->languages->first()?->code ?? app()->getLocale();
        $this->resetForm();
        $this->loadCategories();
    }

    public function loadCategories()
    {
        $this->categories = CategoryTemplate::with('translations')->latest()->get();
    }

    public function save()
    {
        $this->validate();

        $category = $this->mode === 'edit'
            ? CategoryTemplate::find($this->editingCategoryId)
            : CategoryTemplate::create();

        if (!$category) {
            session()->flash('error', 'ط­ط¯ط« ط®ط·ط£ ط؛ظٹط± ظ…طھظˆظ‚ط¹.');
            return;
        }

        foreach ($this->languages as $lang) {
            $langCode = $lang->code;
            $category->translations()->updateOrCreate(
                ['locale' => $langCode],
                [
                    'name' => $this->translations[$langCode]['name'],
                    'slug' => $this->translations[$langCode]['slug'],
                    'description' => $this->translations[$langCode]['description'] ?? null,
                ]
            );
        }

        session()->flash('success', $this->mode === 'edit' ? 'طھظ… طھط­ط¯ظٹط« ط§ظ„ظپط¦ط© ط¨ظ†ط¬ط§ط­.' : 'طھظ…طھ ط¥ط¶ط§ظپط© ط§ظ„ظپط¦ط© ط¨ظ†ط¬ط§ط­.');
        $this->resetForm();
        $this->loadCategories();
    }

    public function edit($categoryId)
    {
        $category = CategoryTemplate::with('translations')->findOrFail($categoryId);

        $this->mode = 'edit';
        $this->editingCategoryId = $categoryId;

        foreach ($this->languages as $lang) {
            $translation = $category->translations->where('locale', $lang->code)->first();
            $this->translations[$lang->code] = [
                'name' => $translation?->name ?? '',
                'slug' => $translation?->slug ?? '',
                'description' => $translation?->description ?? '',
            ];
        }
    }

    public function confirmDelete($id)
    {
        $this->delete($id);
    }

    public function delete($id)
    {
        CategoryTemplate::find($id)?->delete();
        session()->flash('success', 'طھظ… ط­ط°ظپ ط§ظ„ظپط¦ط© ط¨ظ†ط¬ط§ط­.');
        $this->loadCategories();
    }

    public function resetForm()
    {
        $this->reset(['translations', 'editingCategoryId']);
        $this->mode = 'create';

        foreach ($this->languages as $lang) {
            $this->translations[$lang->code] = [
                'name' => '',
                'slug' => '',
                'description' => '',
            ];
        }
    }

    public function render()
    {
        return view('livewire.admin.template.category-management');
    }
}


