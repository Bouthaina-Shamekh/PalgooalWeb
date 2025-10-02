<?php

namespace App\Livewire\dashboard;

use Livewire\Component;
use App\Models\GeneralSetting;
use App\Models\Language;
use Illuminate\Http\UploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class GeneralSettingPage extends Component
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
        'logo_url' => '',
        'dark_logo' => '',
        'dark_logo_url' => '',
        'sticky_logo' => '',
        'sticky_logo_url' => '',
        'dark_sticky_logo' => '',
        'dark_sticky_logo_url' => '',
        'admin_logo' => '',
        'admin_logo_url' => '',
        'admin_dark_logo' => '',
        'admin_dark_logo_url' => '',
        'favicon' => '',
        'favicon_url' => '',
        'default_language' => '',
        'contact_info' => [
            'phone' => '',
            'email' => '',
            'address' => '',
        ],
        'social_links' => [
            'facebook' => '',
            'twitter' => '',
            'linkedin' => '',
            'instagram' => '',
            'whatsapp' => '',
        ],
    ];

    public $languages = [];
    public function mount()
    {
        $generalSetting = GeneralSetting::first();
        $this->generalSetting = [
            'site_title' => $generalSetting->site_title,
            'site_discretion' => $generalSetting->site_discretion,
            'logo' => '',
            'logo_url' => $generalSetting->logo,
            'dark_logo' => '',
            'dark_logo_url' => $generalSetting->dark_logo,
            'sticky_logo' => '',
            'sticky_logo_url' => $generalSetting->sticky_logo,
            'dark_sticky_logo' => '',
            'dark_sticky_logo_url' => $generalSetting->dark_sticky_logo,
            'admin_logo' => '',
            'admin_logo_url' => $generalSetting->admin_logo,
            'admin_dark_logo' => '',
            'admin_dark_logo_url' => $generalSetting->admin_dark_logo,
            'favicon' => '',
            'favicon_url' => $generalSetting->favicon,
            'default_language' => $generalSetting->default_language,
            'contact_info' => $generalSetting->contact_info ?? [
                'phone' => '',
                'email' => '',
                'address' => '',
            ],

            'social_links' => $generalSetting->social_links ?? [
                'facebook' => '',
                'twitter' => '',
                'linkedin' => '',
                'instagram' => '',
                'whatsapp' => '',
            ],
        ];
        $this->languages = Language::all();
    }
    public function resetForm()
    {
        $this->generalSetting = [
            'site_title' => '',
            'site_discretion' => '',
            'logo' => '',
            'logo_url' => '',
            'dark_logo' => '',
            'dark_logo_url' => '',
            'sticky_logo' => '',
            'sticky_logo_url' => '',
            'dark_sticky_logo' => '',
            'dark_sticky_logo_url' => '',
            'admin_logo' => '',
            'admin_logo_url' => '',
            'admin_dark_logo' => '',
            'admin_dark_logo_url' => '',
            'favicon' => '',
            'favicon_url' => '',
            'default_language' => '',
            'contact_info' => [
                'phone' => '',
                'email' => '',
                'address' => '',
            ],
            'social_links' => [
                'facebook' => '',
                'twitter' => '',
                'linkedin' => '',
                'instagram' => '',
                'whatsapp' => '',
            ],
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
            'generalSetting.contact_info.phone' => 'nullable|string',
            'generalSetting.contact_info.email' => 'nullable|email',
            'generalSetting.contact_info.address' => 'nullable|string',

            'generalSetting.social_links.facebook' => 'nullable|url',
            'generalSetting.social_links.twitter' => 'nullable|url',
            'generalSetting.social_links.linkedin' => 'nullable|url',
            'generalSetting.social_links.instagram' => 'nullable|url',
            'generalSetting.social_links.whatsapp' => 'nullable|url',
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
                if ($file instanceof UploadedFile) {
                    if (!empty($generalSetting->$field) && Storage::disk('public')->exists($generalSetting->$field)) {
                        Storage::disk('public')->delete($generalSetting->$field);
                    }

                    $generalSettingValidated[$field] = $file->store('general_settings', 'public');
                } else {
                    $generalSettingValidated[$field] = $generalSetting->$field;
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

        // $this->resetForm();
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

        return view('livewire.general-setting-page', compact('generalSettings'));
    }
}
