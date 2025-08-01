<?php

namespace App\Livewire\Dashboard\Template;

use Livewire\Component;
use App\Models\CategoryTemplate;
use App\Models\Language;

class CategoryManagement extends Component
{
    // لعرض القائمة
    public $categories = [];

    // للغات والتبويبات
    public $languages;
    public $activeLang;

    // لنموذج الإضافة/التعديل
    public $translations = [];


    // للتحكم في الحالة (إضافة أو تعديل)
    public $mode = 'create';
    public $editingCategoryId;

    // قواعد التحقق من الصحة
    protected function rules()
    {
        $rules = [];
        foreach ($this->languages as $lang) {
            $rules["translations.{$lang->code}.name"] = 'required|string|max:255';
            $rules["translations.{$lang->code}.description"] = 'nullable|string';

            $slugRule = ['required', 'string', 'alpha_dash'];
            $translationId = $this->getTranslationId($lang->code);
            $slugRule[] = \Illuminate\Validation\Rule::unique('category_template_translations', 'slug')->ignore($translationId);
            $rules["translations.{$lang->code}.slug"] = $slugRule;
        }
        return $rules;
    }

    // Helper function to get translation ID when editing
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
        $this->categories = CategoryTemplate::with('translations', 'translation')->latest()->get();
    }

    public function save()
    {
        $this->validate();

        $category = null;

        if ($this->mode === 'edit') {
            // في وضع التعديل، ابحث عن الفئة
            $category = CategoryTemplate::find($this->editingCategoryId);
        } else {
            // في وضع الإضافة، قم بإنشاء فئة جديدة
            $category = CategoryTemplate::create(); // آمنة لأن الجدول لا يحتوي على أعمدة قابلة للتعبئة
        }

        if (!$category) {
            session()->flash('error', 'حدث خطأ غير متوقع.');
            return;
        }

        // حفظ الترجمات باستخدام علاقة Eloquent
        foreach ($this->languages as $lang) {
            $category->translations()->updateOrCreate(
                ['locale' => $lang->code], // ابحث عن الترجمة بهذه اللغة
                [ // قم بتحديثها أو إنشائها بهذه البيانات
                    'name' => $this->translations[$lang->code]['name'],
                    'slug' => $this->translations[$lang->code]['slug'],
                    'description' => $this->translations[$lang->code]['description'] ?? null,
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
        $this->reset(['translations', 'editingCategoryId', 'mode']);
        $this->mode = 'create';
        foreach ($this->languages as $lang) {
            $this->translations[$lang->code] = ['name' => '', 'slug' => '', 'description' => ''];
            
        }
    }

    public function render()
    {
        return view('livewire.dashboard.template.category-management');
    }
}