<?php

namespace App\Livewire\Admin\Template;

use Livewire\Component;
use App\Models\CategoryTemplate;
use App\Models\Language;
use Illuminate\Validation\Rule;

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
        'translations.*.name.required' => 'الاسم مطلوب.',
        'translations.*.name.max' => 'يجب ألا يتجاوز الاسم 255 حرفًا.',
        'translations.*.slug.required' => 'الرابط (slug) مطلوب.',
        'translations.*.slug.alpha_dash' => 'يجب أن يحتوي الرابط على أحرف وأرقام وشرطات فقط.',
        'translations.*.slug.unique' => 'هذا الرابط مستخدم بالفعل.',
        'translations.*.slug.max' => 'الرابط طويل جدًا.',
        'translations.*.description.string' => 'الوصف غير صالح.',
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
            session()->flash('error', 'حدث خطأ غير متوقع.');
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

        session()->flash('success', $this->mode === 'edit' ? 'تم تحديث الفئة بنجاح.' : 'تمت إضافة الفئة بنجاح.');
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
        session()->flash('success', 'تم حذف الفئة بنجاح.');
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

