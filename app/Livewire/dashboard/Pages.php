<?php

namespace App\Livewire\Dashboard;

use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use Livewire\Component;

class Pages extends Component
{
    protected $listeners = ['deleteConfirmed' => 'deleteConfirmed'];

    public $languages;
    public $pages;
    public $view = 'index-page';
    public $activeLang;

    public $slug;
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
        $this->loadPages();
    }

    public function loadPages()
    {
        $this->pages = Page::with('translations')->get();
    }

    public function save()
    {
        $this->validate([
            'slug' => 'required|alpha_dash|unique:pages,slug,' . $this->editingPageId,
        ]);

        if ($this->is_home) {
            Page::where('is_home', true)->update(['is_home' => false]);
        }

        $page = Page::updateOrCreate(
            ['id' => $this->editingPageId],
            [
                'slug' => $this->slug,
                'is_active' => $this->is_active,
                'is_home' => $this->is_home,
            ]
        );

        foreach ($this->languages as $lang) {
            PageTranslation::updateOrCreate(
                ['page_id' => $page->id, 'locale' => $lang->code],
                [
                    'title' => $this->translations[$lang->code]['title'] ?? '',
                    'content' => $this->translations[$lang->code]['content'] ?? '',
                ]
            );
        }

        $this->resetForm();
        $this->loadPages();
        session()->flash('success', 'تم الحفظ بنجاح.');
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
    $this->slug = $page->slug;
    $this->is_active = $page->is_active;
    $this->is_home = $page->is_home;
    $this->mode = 'edit';
    $this->activeLang = app()->getLocale();

    foreach ($this->languages as $lang) {
        $trans = $page->translations->where('locale', $lang->code)->first();
        $this->translations[$lang->code] = [
            'title' => $trans->title ?? '',
            'content' => $trans->content ?? '',
        ];
    }

    $this->view = 'edit-page';
}


    public function resetForm()
    {
        $this->reset(['slug', 'is_active', 'is_home', 'translations', 'editingPageId', 'mode']);
        $this->is_active = true;
        $this->mode = 'create';
    }
    public function setAsHome($id)
    {
        // إزالة الصفحة الرئيسية الحالية
        Page::where('is_home', true)->update(['is_home' => false]);
        // تعيين الصفحة الجديدة
        Page::where('id', $id)->update(['is_home' => true]);
        $this->loadPages(); // تحديث القائمة
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
        session()->flash('success', '✅ تم حذف الصفحة بنجاح');
    } catch (\Exception $e) {
        logger()->error('فشل الحذف: ' . $e->getMessage());
        $this->dispatch('page-delete-failed');
        session()->flash('error', '❌ حدث خطأ أثناء حذف الصفحة');
    }
}


    public function render()
    {
        return view("livewire.dashboard.pages.{$this->view}");
    }
}
