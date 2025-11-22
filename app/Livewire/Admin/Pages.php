<?php

namespace App\Livewire\Admin;

use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Pages extends Component
{
    protected $listeners = ['deleteConfirmed' => 'deleteConfirmed'];
    public $languages;
    public $pages;
    public $view = 'index-page';
    public $activeLang;
    public $is_active = true;
    public $is_home = false;
    public $published_at;
    public $translations = [];
    public $mode = 'create'; // create | edit
    public $editingPageId;

    public function mount()
    {
        $this->languages = Language::where('is_active', true)->get();
        $this->activeLang = app()->getLocale();
        $this->initializeTranslations();
        $this->loadPages();
    }

    protected function initializeTranslations(): void
    {
        $this->translations = [];
        foreach ($this->languages as $lang) {
            $this->translations[$lang->code] = [
                'id' => null,
                'slug' => '',
                'title' => '',
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'og_image' => '',
            ];
        }
    }

    public function loadPages()
    {
        $this->pages = Page::with('translations')->get();
    }

    protected function rules()
    {
        $rules = [
            'is_active' => 'boolean',
            'is_home' => 'boolean',
            'published_at' => 'nullable|date',
        ];

        $defaultLocale = config('app.locale');
        foreach ($this->languages as $lang) {
            $isPrimary = $lang->code === $defaultLocale;
            $titleRules = $isPrimary
                ? ['required', 'string', 'max:190']
                : ['nullable', 'string', 'max:190'];
            $rules["translations.{$lang->code}.title"] = $titleRules;
            $uniqueRule = Rule::unique('page_translations', 'slug')
                ->where(fn($query) => $query->where('locale', $lang->code));
            $existingId = $this->translations[$lang->code]['id'] ?? null;
            if ($existingId) {
                $uniqueRule->ignore($existingId);
            }
            $slugRules = ['nullable', 'string', 'max:190', $uniqueRule];
            if ($isPrimary && !$this->is_home) {
                $slugRules[] = 'required';
            }
            $rules["translations.{$lang->code}.slug"] = $slugRules;
            $rules["translations.{$lang->code}.content"] = ['nullable'];
            $rules["translations.{$lang->code}.meta_title"] = ['nullable', 'string', 'max:190'];
            $rules["translations.{$lang->code}.meta_description"] = ['nullable', 'string', 'max:320'];
            $rules["translations.{$lang->code}.meta_keywords"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$lang->code}.og_image"] = ['nullable', 'string', 'max:255'];
        }
        return $rules;
    }

    public function save()
    {
        $this->validate($this->rules());
        if ($this->is_home) {
            Page::where('is_home', true)->update(['is_home' => false]);
        }
        $publishedAt = null;
        if (!empty($this->published_at)) {
            $publishedAt = Carbon::parse($this->published_at);
        } elseif ($this->is_active) {
            $publishedAt = Carbon::now();
        }
        $page = Page::updateOrCreate(
            ['id' => $this->editingPageId],
            [
                'is_active' => (bool) $this->is_active,
                'is_home' => (bool) $this->is_home,
                'published_at' => $publishedAt,
            ]
        );
        foreach ($this->languages as $lang) {
            $data = $this->translations[$lang->code] ?? [];
            $title = trim($data['title'] ?? '');
            $slug = isset($data['slug']) ? trim($data['slug']) : null;
            $metaTitle = trim($data['meta_title'] ?? '');
            $metaDescription = trim($data['meta_description'] ?? '');
            $metaKeywordsInput = $data['meta_keywords'] ?? '';
            $ogImage = trim($data['og_image'] ?? '');
            $keywords = collect(preg_split('/[,\\x{060C}]/u', (string) $metaKeywordsInput) ?: [])
                ->map(fn($keyword) => trim($keyword))
                ->filter()
                ->values()
                ->all();
            PageTranslation::updateOrCreate(
                ['page_id' => $page->id, 'locale' => $lang->code],
                [
                    'slug' => $this->is_home ? null : ($slug === null || $slug === '' ? null : $slug),
                    'title' => $title,
                    'content' => $data['content'] ?? '',
                    'meta_title' => $metaTitle !== '' ? $metaTitle : $title,
                    'meta_description' => $metaDescription !== '' ? $metaDescription : null,
                    'meta_keywords' => !empty($keywords) ? $keywords : null,
                    'og_image' => $ogImage !== '' ? $ogImage : null,
                ]
            );
        }
        $this->resetForm();
        $this->activeLang = app()->getLocale();
        $this->view = 'index-page';
        $this->loadPages();
        session()->flash('success', "\u{062A}\u{0645} \u{062D}\u{0641}\u{0638} \u{0627}\u{0644}\u{0635}\u{0641}\u{062D}\u{0629} \u{0628}\u{0646}\u{062C}\u{0627}\u{062D}.");
    }
    public function goToAddPage()
    {
        $this->resetForm();
        $this->view = 'add-page';
    }
    public function edit($id)
    {
        $page = Page::with('translations')->findOrFail($id);
        $this->editingPageId = $id;
        $this->is_active = $page->is_active;
        $this->is_home = $page->is_home;
        $this->published_at = $page->published_at?->format('Y-m-d\TH:i');
        $this->mode = 'edit';
        $this->activeLang = app()->getLocale();
        foreach ($this->languages as $lang) {
            $trans = $page->translations->firstWhere('locale', $lang->code);
            $metaKeywords = '';
            if ($trans) {
                if (is_array($trans->meta_keywords)) {
                    $metaKeywords = implode(', ', $trans->meta_keywords);
                } elseif (is_string($trans->meta_keywords)) {
                    $metaKeywords = $trans->meta_keywords;
                }
            }
            $this->translations[$lang->code] = [
                'id' => $trans?->id,
                'slug' => $trans?->slug ?? '',
                'title' => $trans?->title ?? '',
                'content' => $trans?->content ?? '',
                'meta_title' => $trans?->meta_title ?? '',
                'meta_description' => $trans?->meta_description ?? '',
                'meta_keywords' => $metaKeywords,
                'og_image' => $trans?->og_image ?? '',
            ];
        }
        $this->view = 'edit-page';
    }

    public function resetForm()
    {
        $this->editingPageId = null;
        $this->mode = 'create';
        $this->is_active = true;
        $this->is_home = false;
        $this->published_at = null;
        $this->initializeTranslations();
    }
    public function setAsHome($id)
    {
        Page::where('is_home', true)->update(['is_home' => false]);
        Page::where('id', $id)->update(['is_home' => true]);
        $this->loadPages();
        session()->flash('success', t('dashboard.set_as_home_success', 'Page set as home successfully.'));
    }
    public function confirmDelete($id)
    {
        $this->dispatchBrowserEvent('show-delete-confirmation', ['id' => $id]);
    }
    public function deleteConfirmed($id)
    {
        try {
            $page = Page::findOrFail($id);
            $page->delete();
            $this->loadPages();
            $this->dispatch('page-deleted-success');
            session()->flash('success', "\u{062A}\u{0645} \u{062D}\u{0630}\u{0641} \u{0627}\u{0644}\u{0635}\u{0641}\u{062D}\u{0629} \u{0628}\u{0646}\u{062C}\u{0627}\u{062D}.");
        } catch (\Exception $e) {
            logger()->error("\u{0641}\u{0634}\u{0644} \u{062D}\u{0630}\u{0641} \u{0627}\u{0644}\u{0635}\u{0641}\u{062D}\u{0629}: " . $e->getMessage());
            $this->dispatch('page-delete-failed');
            session()->flash('error', "\u{062A}\u{0639}\u{0630}\u{0631} \u{062D}\u{0630}\u{0641} \u{0627}\u{0644}\u{0635}\u{0641}\u{062D}\u{0629}.");
        }
    }
    public function render()
    {
        return view("livewire.dashboard.pages.{$this->view}");
    }
}
