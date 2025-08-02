<?php

namespace App\Livewire\Dashboard\Template;

use Livewire\Component;
use App\Models\Template;
use App\Models\CategoryTemplate;
use App\Models\Language;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class TemplateManagement extends Component
{
    use WithFileUploads;

    // --- 1. حذف preview_url من الخصائص الرئيسية ---
    public $templates = [], $categories = [], $languages;
    public $activeLang, $mode = 'create', $editingTemplateId;

    // الخصائص الرئيسية للنموذج
    public $category_template_id, $price, $image, $existing_image_url;
    public $discount_price, $discount_ends_at;
    
    // مصفوفة الترجمات التي ستحتوي الآن على preview_url
    public $translations = [];

    // --- 2. تحديث قواعد التحقق ---
    protected function rules()
    {
        $rules = [
            'category_template_id' => 'required|exists:category_templates,id',
            'price' => 'required|numeric|min:0',
            'image' => Rule::requiredIf($this->mode === 'create') . '|nullable|image|max:1024',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'discount_ends_at' => 'nullable|date',
        ];

        foreach ($this->languages as $lang) {
            $rules["translations.{$lang->code}.name"] = 'required|string|max:255';
            $rules["translations.{$lang->code}.slug"] = [
                'required', 'string', 'alpha_dash',
                Rule::unique('template_translations', 'slug')->ignore($this->getTranslationId($lang->code))
            ];
            // نقل قاعدة التحقق الخاصة بـ preview_url إلى هنا
            $rules["translations.{$lang->code}.preview_url"] = 'nullable|url';
            $rules["translations.{$lang->code}.description"] = 'nullable|string';
        }
        return $rules;
    }

    protected function getTranslationId($langCode)
    {
        if ($this->mode === 'edit' && $this->editingTemplateId) {
            return Template::find($this->editingTemplateId)?->translations()->where('locale', $langCode)->first()?->id;
        }
        return null;
    }

    public function mount()
    {
        $this->languages = Language::where('is_active', true)->get();
        $this->categories = CategoryTemplate::with('translation')->get();
        $this->activeLang = $this->languages->first()?->code ?? app()->getLocale();
        $this->resetForm();
        $this->loadTemplates();
    }

    public function loadTemplates()
    {
        $this->templates = Template::with(['categoryTemplate.translation', 'translation'])->latest()->get();
    }

    // --- 3. تحديث دالة الحفظ ---
    public function save()
    {
        $this->validate();

        $imagePath = $this->existing_image_url;
        if ($this->image) {
            if ($this->existing_image_url) {
                Storage::disk('public')->delete($this->existing_image_url);
            }
            $imagePath = $this->image->store('templates', 'public');
        }

        // حذف preview_url من مصفوفة البيانات الرئيسية
        $templateData = [
            'category_template_id' => $this->category_template_id,
            'price' => $this->price,
            'image' => $imagePath,
            'discount_price' => $this->discount_price,
            'discount_ends_at' => $this->discount_ends_at,
        ];

        $template = Template::updateOrCreate(['id' => $this->editingTemplateId], $templateData);

        // إضافة preview_url إلى بيانات الترجمة
        foreach ($this->languages as $lang) {
            $template->translations()->updateOrCreate(
                ['locale' => $lang->code],
                [
                    'name' => $this->translations[$lang->code]['name'],
                    'slug' => $this->translations[$lang->code]['slug'],
                    'preview_url' => $this->translations[$lang->code]['preview_url'] ?? null,
                    'description' => $this->translations[$lang->code]['description'] ?? null,
                ]
            );
        }

        session()->flash('success', $this->mode === 'edit' ? 'تم تحديث القالب بنجاح.' : 'تمت إضافة القالب بنجاح.');
        $this->resetForm();
        $this->loadTemplates();
    }

    // --- 4. تحديث دالة التعديل ---
    public function edit($templateId)
    {
        $template = Template::with('translations')->findOrFail($templateId);
        $this->mode = 'edit';
        $this->editingTemplateId = $templateId;

        // حذف preview_url من هنا
        $this->category_template_id = $template->category_template_id;
        $this->price = $template->price;
        $this->existing_image_url = $template->image;
        $this->image = null;
        $this->discount_price = $template->discount_price;
        $this->discount_ends_at = $template->discount_ends_at ? $template->discount_ends_at->format('Y-m-d\TH:i') : null;

        // إضافة preview_url إلى حلقة الترجمات
        foreach ($this->languages as $lang) {
            $translation = $template->translations->where('locale', $lang->code)->first();
            $this->translations[$lang->code] = [
                'name' => $translation?->name ?? '',
                'slug' => $translation?->slug ?? '',
                'preview_url' => $translation?->preview_url ?? '',
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
        $template = Template::find($id);
        if ($template) {
            if ($template->image) {
                Storage::disk('public')->delete($template->image);
            }
            $template->delete();
            session()->flash('success', 'تم حذف القالب بنجاح.');
            $this->loadTemplates();
        }
    }

    // --- 5. تحديث دالة إعادة التعيين ---
    public function resetForm()
    {
        // حذف preview_url من قائمة الحقول الرئيسية
        $this->reset(['category_template_id', 'price', 'image', 'existing_image_url', 'translations', 'editingTemplateId', 'mode', 'discount_price', 'discount_ends_at']);
        $this->mode = 'create';
        
        // إضافة preview_url إلى مصفوفة الترجمات التي يتم إعادة تعيينها
        foreach ($this->languages as $lang) {
            $this->translations[$lang->code] = ['name' => '', 'slug' => '', 'preview_url' => '', 'description' => ''];
        }
    }

    public function render()
    {
        return view('livewire.dashboard.template.template-management');
    }
}