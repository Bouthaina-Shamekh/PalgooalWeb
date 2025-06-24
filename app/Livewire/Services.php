<?php

namespace App\Livewire;

use App\Models\Language;
use App\Models\Service;
use App\Models\ServiceTranslation;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Component;

class Services extends Component
{
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
    public $serviceId = null;

    public $service = [
        'icon' => '',
        'order' => '',
    ];

    public $serviceTranslations = [];
    public $serviceTranslation = [
        'locale' => '',
        'title' => '',
        'description' => '',
    ];

    public $languages = [];

    public function showAdd()
    {
        $this->mode = 'add';
        $this->resetForm();
        $this->closeModal();
    }

    public function showEdit($id)
    {
        $this->mode = 'edit';
        $this->serviceId = $id;
        $service = Service::findOrFail($id);
        $this->languages = Language::get();
        $this->service = [
            'icon' => $service->icon,
            'order' => $service->order,
        ];
        $this->serviceTranslations = $service->servicetranslations()->get();
        $this->closeModal();
    }

    public function showIndex()
    {
        $this->mode = 'index';
        $this->closeModal();
    }

    public function resetForm()
    {
        $this->service = [
            'icon' => '',
            'order' => '',
        ];
        $this->serviceTranslations = [];
        $this->serviceTranslation = [
            'locale' => '',
            'title' => '',
            'description' => '',
        ];
        $this->serviceId = null;
    }


    public function save()
    {
        $validated = $this->validate([
            'service.icon' => 'required',
            'service.order' => 'required',
        ]);

        $serviceValidated = $validated['service'];

        if ($this->serviceId) {
            $service = Service::findOrFail($this->serviceId);

            if ($this->service['icon']) {
                if ($service->icon && Storage::disk('public')->exists($service->icon)) {
                    Storage::disk('public')->delete($service->icon);
                }

                $serviceValidated['icon'] = $this->service['icon']->store('icons', 'public');
            }else{
                $serviceValidated['icon'] = $service->icon;
            }

            $service->update($serviceValidated);
            foreach ($this->serviceTranslations as $serviceTranslation) {
                ServiceTranslation::updateOrCreate([
                    'service_id' => $service->id,
                    'locale' => $serviceTranslation['locale'],
                ], [
                    'title' => $serviceTranslation['title'],
                    'description' => $serviceTranslation['description'],
                ]);
            }
            $this->showAlert('Service updated successfully.', 'success');
        } else {
            if ($this->service['icon']) {
                $serviceValidated['icon'] = $this->service['icon']->store('icons', 'public');
            }

            $service = Service::create($serviceValidated);
            foreach ($this->serviceTranslations as $serviceTranslation) {
                ServiceTranslation::updateOrCreate([
                    'service_id' => $service->id,
                    'locale' => $serviceTranslation['locale'],
                ], [
                    'title' => $serviceTranslation['title'],
                    'description' => $serviceTranslation['description'],
                ]);
            }
            $this->showAlert('Service added successfully.', 'success');
        }

        $this->resetForm();
        $this->resetPage();
        $this->mode = 'index';
    }

    public function delete($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();
        ServiceTranslation::where('service_id', $id)->delete();

        $this->showAlert('Service deleted successfully.', 'success');
        $this->resetPage();
    }

    public function render()
    {
        $services = Service::query()->paginate($this->perPage);

        return view('livewire.services', compact('services'));
    }
}
