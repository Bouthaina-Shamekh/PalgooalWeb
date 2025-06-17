<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GeneralSetting;
use App\Models\Language;
use Illuminate\Http\UploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class GeneralSettingComponent extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $alert = false;
    public $alertType = 'success';
    public $alertMessage = '';

    public function showAlert($message, $type = 'success')
    {
        $this->alert = true;
        $this->alertType = $type;
        $this->alertMessage = $message;
    }

    public function closeModal()
    {
        $this->alert = false;
    }

    public $mode = 'index';
    public $search = '';
    public $perPage = 10;

    public $generalSetting = [
        'site_title' => '',
        'site_discretion' => '',
        'logo' => '',
        'dark_logo' => '',
        'sticky_logo' => '',
        'dark_sticky_logo' => '',
        'admin_logo' => '',
        'admin_dark_logo' => '',
        'favicon' => '',
        'default_language' => '',
    ];
    public $languages = [];
    public function mount()
    {
        $generalSetting = GeneralSetting::first();
        $this->generalSetting = [
            'site_title' => $generalSetting->site_title,
            'site_discretion' => $generalSetting->site_discretion,
            'logo' => $generalSetting->logo,
            'dark_logo' => $generalSetting->dark_logo,
            'sticky_logo' => $generalSetting->sticky_logo,
            'dark_sticky_logo' => $generalSetting->dark_sticky_logo,
            'admin_logo' => $generalSetting->admin_logo,
            'admin_dark_logo' => $generalSetting->admin_dark_logo,
            'favicon' => $generalSetting->favicon,
            'default_language' => $generalSetting->default_language,
        ];
        $this->languages = Language::all();
    }
    public function resetForm()
    {
        $this->generalSetting = [
            'site_title' => '',
            'site_discretion' => '',
            'logo' => '',
            'dark_logo' => '',
            'sticky_logo' => '',
            'dark_sticky_logo' => '',
            'admin_logo' => '',
            'admin_dark_logo' => '',
            'favicon' => '',
            'default_language' => '',
        ];
    }

    public function save()
    {
        $validated = $this->validate([
            'generalSetting.site_title' => 'required',
            'generalSetting.site_discretion' => 'required',
            'generalSetting.logo' => 'nullable|image',
            'generalSetting.dark_logo' => 'nullable|image',
            'generalSetting.sticky_logo' => 'nullable|image',
            'generalSetting.dark_sticky_logo' => 'nullable|image',
            'generalSetting.admin_logo' => 'nullable|image',
            'generalSetting.admin_dark_logo' => 'nullable|image',
            'generalSetting.favicon' => 'nullable|image',
            'generalSetting.default_language' => 'required',
        ]);

        $generalSettingValidated = $validated['generalSetting'];

        $generalSetting = GeneralSetting::first();
        if ($generalSetting) {
            $fields = [
                'logo',
                'dark_logo',
                'sticky_logo',
                'dark_sticky_logo',
                'admin_logo',
                'admin_dark_logo',
                'favicon',
            ];

            foreach ($fields as $field) {
                $file = $this->generalSetting[$field] ?? null;
                if (!empty($file)) {
                    $generalSettingValidated[$field] = $file->store('general_settings', 'public');
                }
            }

            $generalSetting->update($generalSettingValidated);
            $this->showAlert('General setting updated successfully.', 'success');
        } else {
            $fields = [
                'logo',
                'dark_logo',
                'sticky_logo',
                'dark_sticky_logo',
                'admin_logo',
                'admin_dark_logo',
                'favicon',
            ];

            foreach ($fields as $field) {
                $file = $this->generalSetting[$field] ?? null;
                if ($file instanceof UploadedFile) {
                    if (!empty($generalSetting->$field) && Storage::disk('public')->exists($generalSetting->$field)) {
                        Storage::disk('public')->delete($generalSetting->$field);
                    }

                    $generalSettingValidated[$field] = $file->store('general_settings', 'public');
                } else {
                    $generalSettingValidated[$field] = $generalSetting->$field;
                }
            }


            GeneralSetting::create($generalSettingValidated);
            $this->showAlert('General setting added successfully.', 'success');
        }

        $this->resetForm();
        $this->resetPage();
        $this->mode = 'index';
    }

    public function delete($id)
    {
        $generalSetting = GeneralSetting::first();
        if ($generalSetting->avatar && Storage::disk('public')->exists($generalSetting->avatar)) {
            Storage::disk('public')->delete($generalSetting->avatar);
        }
        $generalSetting->delete();

        $this->showAlert('General setting deleted successfully.', 'success');
        $this->resetPage();
    }

    public function updateSearch()
    {
        $this->resetPage();
    }

    public function updatePerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $generalSettings = GeneralSetting::first();

        return view('livewire.general-setting', compact('generalSettings'));
    }
}
