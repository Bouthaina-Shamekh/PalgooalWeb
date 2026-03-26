<?php

namespace App\Livewire\Admin\Sections;

use App\Models\Language;
use App\Models\Section;
use Livewire\Component;

/**
 * @deprecated deprecated - do not use. Legacy admin Livewire component retained only for fallback safety.
 */
class BaseSectionComponent extends Component
{
    public Section $section;
    public $translationsData = [];
    public $languages;
    public $activeLang;
    public $order;

    protected $listeners = ['confirm-delete-section' => 'deleteMySection'];

    public function mount()
    {
        $this->languages = Language::where('is_active', true)->get();
        $this->activeLang = app()->getLocale();
        $this->order = $this->section->order;

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $this->translationsData[$lang->code] = [
                'title' => $translation?->title ?? '',
                'subtitle' => $content['subtitle'] ?? '',
            ];
        }
    }

    public function setActiveLang($code)
    {
        $this->activeLang = $code;
    }

    public function deleteMySection()
    {
        $this->section->delete();

        session()->flash('success', 'طھظ… ط­ط°ظپ ط§ظ„ط³ظƒط´ظ† ط¨ظ†ط¬ط§ط­.');

        // ط¥ط¹ط§ط¯ط© ط§ظ„طھظˆط¬ظٹظ‡ ظ…ط¹ Livewire
        $this->redirect(request()->header('Referer'), navigate: true);
    }

    /**
     * ط¯ظˆط§ظ„ ظ…ط³ط§ط¹ط¯ط© ظ„ظ„ط³ظƒط´ظ†ط§طھ ظٹظ…ظƒظ† ط§ط³طھط¯ط¹ط§ط¤ظ‡ط§ ظ…ظ† ط£ظٹ ط³ظƒط´ظ† ظٹط±ط« BaseSectionComponent
     */
    public function flashSuccess($message)
    {
        session()->flash('success', $message);
    }

    public function flashError($message)
    {
        session()->flash('error', $message);
    }
}


