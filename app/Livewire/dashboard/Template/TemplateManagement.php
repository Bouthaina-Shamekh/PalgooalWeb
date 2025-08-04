<?php

namespace App\Livewire\Dashboard\Template;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Template;
use App\Models\CategoryTemplate;
use App\Models\Language;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class TemplateManagement extends Component
{
    use WithFileUploads;
    use WithPagination;
    protected $paginationTheme = 'tailwind';

    
    public $categories = [], $languages;
    public $activeLang, $mode = 'create', $editingTemplateId;
    public $category_template_id, $price, $image, $existing_image_url;
    public $discount_price, $discount_ends_at;
    public $translations = [];



    protected function rules()
    {
        $rules = [
            'category_template_id' => 'required|exists:category_templates,id',
            'price' => 'required|numeric|min:0',
            'image' => Rule::requiredIf($this->mode === 'create') . '|nullable|image|max:1024',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'discount_ends_at' => 'nullable|date|after:now',
        ];

        foreach ($this->languages as $lang) {
            $rules["translations.{$lang->code}.name"] = 'required|string|max:255';
            $rules["translations.{$lang->code}.slug"] = [
                'required', 'string', 'alpha_dash',
                Rule::unique('template_translations', 'slug')->ignore($this->getTranslationId($lang->code)),
            ];
            $rules["translations.{$lang->code}.preview_url"] = 'nullable|url';
            $rules["translations.{$lang->code}.description"] = 'nullable|string';

            $rules["translations.{$lang->code}.details.features.*.icon"] = 'nullable|string|max:10';
            $rules["translations.{$lang->code}.details.features.*.title"] = 'required|string|max:255';

            $rules["translations.{$lang->code}.details.specifications.*.key"] = 'required|string|max:255';
            $rules["translations.{$lang->code}.details.specifications.*.value"] = 'required|string|max:255';
        }

        return $rules;
    }

    protected function getTranslationId($langCode)
    {
        static $translationsCache = [];

        if (!isset($translationsCache[$langCode]) && $this->mode === 'edit' && $this->editingTemplateId) {
            $translationsCache[$langCode] = Template::find($this->editingTemplateId)?->translations()
                ->where('locale', $langCode)->first()?->id;
        }

        return $translationsCache[$langCode] ?? null;
    }

    public function mount()
    {
        $this->languages = Language::get();
        $this->categories = CategoryTemplate::with('translation')->get();
        $this->activeLang = $this->languages->first()?->code ?? app()->getLocale();
        $this->resetForm();
    }

    public function loadTemplates()
    {
        $this->templates = Template::with(['categoryTemplate.translation', 'translation'])->latest()->paginate(10);
    }

    public function save()
    {
        foreach ($this->translations as $langCode => &$translation) {
            if (isset($translation['details']['features'])) {
                $translation['details']['features'] = array_values(array_filter(
                    $translation['details']['features'],
                    fn($feature) => !empty($feature['title'])
                ));
            }

            if (isset($translation['details']['specifications'])) {
                $translation['details']['specifications'] = array_values(array_filter(
                    $translation['details']['specifications'],
                    fn($spec) => !empty($spec['key']) && !empty($spec['value'])
                ));
            }
        }

        $this->validate();

        $imagePath = $this->existing_image_url;
        if ($this->image) {
            if ($this->existing_image_url) {
                Storage::disk('public')->delete($this->existing_image_url);
            }
            $imagePath = $this->image->store('templates', 'public');
        }

        $templateData = [
            'category_template_id' => $this->category_template_id,
            'price' => $this->price,
            'image' => $imagePath,
            'discount_price' => $this->discount_price,
            'discount_ends_at' => $this->discount_ends_at,
        ];

        $template = Template::updateOrCreate(['id' => $this->editingTemplateId], $templateData);

        foreach ($this->languages as $lang) {
            $template->translations()->updateOrCreate(
                ['locale' => $lang->code],
                [
                    'name' => $this->translations[$lang->code]['name'],
                    'slug' => $this->translations[$lang->code]['slug'],
                    'preview_url' => $this->translations[$lang->code]['preview_url'] ?? null,
                    'description' => $this->translations[$lang->code]['description'] ?? null,
                    'details' => $this->translations[$lang->code]['details'] ?? [],
                ]
            );
        }

        $this->flashSuccess('تم الحفظ بنجاح.');
        $this->resetForm();
        
    }

    public function edit($templateId)
    {
        $template = Template::with('translations')->findOrFail($templateId);
        $this->mode = 'edit';
        $this->editingTemplateId = $templateId;

        $this->category_template_id = $template->category_template_id;
        $this->price = $template->price;
        $this->existing_image_url = $template->image;
        $this->image = null;
        $this->discount_price = $template->discount_price;
        $this->discount_ends_at = $template->discount_ends_at ? $template->discount_ends_at->format('Y-m-d\TH:i') : null;

        foreach ($this->languages as $lang) {
            $translation = $template->translations->where('locale', $lang->code)->first();
            $this->translations[$lang->code] = [
                'name' => $translation?->name ?? '',
                'slug' => $translation?->slug ?? '',
                'preview_url' => $translation?->preview_url ?? '',
                'description' => $translation?->description ?? '',
                'details' => $translation?->details ?? ['features' => [], 'specifications' => []],
            ];
        }
    }

    public function resetForm()
    {
        $this->reset([
            'category_template_id', 'price', 'image', 'existing_image_url',
            'editingTemplateId', 'discount_price', 'discount_ends_at',
        ]);

        foreach ($this->languages as $lang) {
            $this->translations[$lang->code] = [
                'name' => '',
                'slug' => '',
                'preview_url' => '',
                'description' => '',
                'details' => [
                    'features' => [],
                    'specifications' => [],
                ]
            ];
        }

        $this->mode = 'create';
    }

    public function addFeature($langCode)
    {
        $this->translations[$langCode]['details']['features'][] = ['icon' => '', 'title' => ''];
    }

    public function removeFeature($langCode, $index)
    {
        unset($this->translations[$langCode]['details']['features'][$index]);
        $this->translations[$langCode]['details']['features'] = array_values($this->translations[$langCode]['details']['features']);
    }

    public function addSpecification($langCode)
    {
        $this->translations[$langCode]['details']['specifications'][] = ['key' => '', 'value' => ''];
    }

    public function removeSpecification($langCode, $index)
    {
        unset($this->translations[$langCode]['details']['specifications'][$index]);
        $this->translations[$langCode]['details']['specifications'] = array_values($this->translations[$langCode]['details']['specifications']);
    }

    public function delete($id)
    {
        $template = Template::find($id);
        if ($template) {
            if ($template->image) {
                Storage::disk('public')->delete($template->image);
            }
            $template->delete();
            $this->flashSuccess('تم حذف القالب بنجاح.');
            
        }
    }

    private function flashSuccess($message)
    {
        session()->flash('success', $message);
    }

    public function confirmDelete($id)
    {
        $template = Template::findOrFail($id);

        // حذف الصورة إن وجدت
        if ($template->image && \Storage::disk('public')->exists($template->image)) {
            \Storage::disk('public')->delete($template->image);
        }

        // حذف القالب
        $template->delete();

        session()->flash('success', 'تم حذف القالب بنجاح.');
    }

    protected $messages = [
    'category_template_id.required' => 'يرجى اختيار فئة القالب.',
    'category_template_id.exists' => 'الفئة المختارة غير صالحة.',
    'price.required' => 'يرجى إدخال السعر.',
    'price.numeric' => 'السعر يجب أن يكون رقماً.',
    'price.min' => 'السعر يجب ألا يقل عن 0.',
    'image.required' => 'الصورة مطلوبة.',
    'image.image' => 'الملف المرفوع يجب أن يكون صورة.',
    'image.max' => 'أقصى حجم للصورة هو 1 ميجابايت.',
    'discount_price.numeric' => 'سعر الخصم يجب أن يكون رقماً.',
    'discount_price.min' => 'سعر الخصم لا يمكن أن يكون سالباً.',
    'discount_price.lt' => 'سعر الخصم يجب أن يكون أقل من السعر الأساسي.',
    'discount_ends_at.date' => 'تاريخ الخصم غير صالح.',
    'discount_ends_at.after' => 'تاريخ انتهاء الخصم يجب أن يكون في المستقبل.',

    // الترجمات الديناميكية لكل لغة
    'translations.*.name.required' => 'الاسم مطلوب.',
    'translations.*.name.max' => 'يجب ألا يتجاوز الاسم 255 حرفًا.',
    'translations.*.slug.required' => 'الرابط (slug) مطلوب.',
    'translations.*.slug.alpha_dash' => 'يجب أن يحتوي الرابط فقط على أحرف وأرقام وشرطات.',
    'translations.*.slug.max' => 'يجب ألا يتجاوز الرابط 255 حرفًا.',
    'translations.*.slug.unique' => 'هذا الرابط مستخدم بالفعل.',
    'translations.*.preview_url.url' => 'رابط المعاينة غير صالح.',
    'translations.*.description.max' => 'الوصف طويل جدًا.',

    'translations.*.details.features.*.icon.max' => 'رمز الميزة يجب ألا يزيد عن 10 أحرف.',
    'translations.*.details.features.*.title.required' => 'عنوان الميزة مطلوب.',
    'translations.*.details.features.*.title.max' => 'عنوان الميزة طويل جدًا.',
    'translations.*.details.specifications.*.key.required' => 'مفتاح المواصفة مطلوب.',
    'translations.*.details.specifications.*.key.max' => 'مفتاح المواصفة طويل جدًا.',
    'translations.*.details.specifications.*.value.required' => 'قيمة المواصفة مطلوبة.',
    'translations.*.details.specifications.*.value.max' => 'قيمة المواصفة طويلة جدًا.',
];


    public function render()
    {
        return view('livewire.dashboard.template.template-management', [
            'templates' => Template::with(['categoryTemplate.translations', 'translations'])->latest()->paginate(10),
        ]);
    }
}
