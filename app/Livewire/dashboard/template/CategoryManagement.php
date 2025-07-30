<?php

namespace App\Livewire\Dashboard\Template;

use Livewire\Component;
use App\Models\CategoryTemplate;
use App\Models\CategoryTemplateTranslation;
use App\Models\Language;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryManagement extends Component
{
    public $languages;
    public $activeLang;
    public $translations = [];
    public $editingCategoryId;
    public $mode = 'create';

    // لتتبع التعديل اليدوي لكل لغة
    public $slugsModified = [];

    public function mount()
    {
        $this->languages = Language::where('is_active', true)->get();
        $this->activeLang = $this->languages->first()?->code ?? app()->getLocale();
        // تهيئة مصفوفات الترجمة والتتبع
        foreach ($this->languages as $lang) {
            $this->translations[$lang->code] = ['name' => '', 'slug' => '', 'description' => ''];
            $this->slugsModified[$lang->code] = false;
        }
    }

    public function updatedTranslations($value, $key)
    {
        // $key => 'ar.name'
        $parts = explode('.', $key);
        $langCode = $parts[0];
        $field = $parts[1];

        // إذا تغير الاسم ولم يتم تعديل السلغ يدويًا لتلك اللغة
        if ($field === 'name' && ($this->slugsModified[$langCode] ?? false) === false) {
            $this->translations[$langCode]['slug'] = Str::slug($value, $langCode);
        }
    }

    public function slugModified($langCode)
    {
        $this->slugsModified[$langCode] = true;
    }

    public function save()
    {
        // بناء قواعد التحقق ديناميكيًا
        $rules = [];
        foreach ($this->languages as $lang) {
            $rules["translations.{$lang->code}.name"] = 'required|string|max:255';
            $rules["translations.{$lang->code}.description"] = 'nullable|string';
            // التحقق من أن الـ slug فريد
            $rules["translations.{$lang->code}.slug"] = [
                'required',
                'string',
                'alpha_dash',
                Rule::unique('category_template_translations', 'slug')->ignore($this->editingCategoryId, 'category_template_id')
            ];
        }
        $this->validate($rules);

        // إنشاء الفئة الرئيسية
        $category = CategoryTemplate::updateOrCreate(['id' => $this->editingCategoryId]);

        // حفظ الترجمات
        foreach ($this->languages as $lang) {
            $category->translations()->updateOrCreate(
                ['locale' => $lang->code],
                [
                    'name' => $this->translations[$lang->code]['name'],
                    'slug' => $this->translations[$lang->code]['slug'],
                    'description' => $this->translations[$lang->code]['description'] ?? null,
                ]
            );
        }

        session()->flash('message', 'تم الحفظ بنجاح.');
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset(['translations', 'editingCategoryId', 'mode', 'slugsModified']);
        $this->mount(); // إعادة التهيئة
    }

    public function render()
    {
        return view('livewire.dashboard.template.category-management');
    }
}
