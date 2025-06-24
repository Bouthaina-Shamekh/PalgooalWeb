<?php

namespace App\Livewire\dashboard;

use App\Models\Language;
use App\Models\Service;
use App\Models\ServiceTranslation;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Http\UploadedFile;

class Services extends Component
{
    use WithPagination, WithFileUploads;
    protected $listeners = ['deleteServiceConfirmed'];

    public $alert = false;
    public $alertType = 'success';
    public $alertMessage = '';

    public $mode = 'index';
    public $search = '';
    public $perPage = 10;
    public $serviceId = null;

    public $service = [
        'icon' => '',
        'order' => '',
    ];

    public $serviceTranslations = [];
    public $languages = [];

    public function mount()
    {
        $this->languages = Language::get();
    }

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

        $this->service = [
            'icon' => $service->icon,
            'order' => $service->order,
        ];

        $this->serviceTranslations = [];
        foreach ($this->languages as $lang) {
            $trans = $service->translations->firstWhere('locale', $lang->code);
            $this->serviceTranslations[] = [
                'locale' => $lang->code,
                'title' => $trans?->title ?? '',
                'description' => $trans?->description ?? '',
            ];
        }

        $this->closeModal();
    }

    public function showIndex()
    {
        $this->mode = 'index';
        $this->closeModal();
    }

    public function resetForm()
    {
        $this->serviceId = null;
        $this->service = ['icon' => '', 'order' => ''];

        $this->serviceTranslations = [];
        foreach ($this->languages as $lang) {
            $this->serviceTranslations[] = [
                'locale' => $lang->code,
                'title' => '',
                'description' => '',
            ];
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'service.order' => 'required|integer',
            'service.icon' => 'nullable', // optional file
            'serviceTranslations.*.title' => 'required|string',
            'serviceTranslations.*.description' => 'required|string',
        ]);

        $serviceData = $this->service;

        if ($this->serviceId) {
            $service = Service::findOrFail($this->serviceId);

            // تحقق إن كانت الأيقونة جديدة
            if ($this->service['icon'] instanceof UploadedFile) {
                if ($service->icon && Storage::disk('public')->exists($service->icon)) {
                    Storage::disk('public')->delete($service->icon);
                }
                $serviceData['icon'] = $this->service['icon']->store('icons', 'public');
            } else {
                $serviceData['icon'] = $service->icon;
            }

            $service->update($serviceData);
            $this->showAlert('Service updated successfully.', 'success');
        } else {
            if ($this->service['icon'] instanceof UploadedFile) {
                $serviceData['icon'] = $this->service['icon']->store('icons', 'public');
            }

            $service = Service::create($serviceData);
            $this->showAlert('Service added successfully.', 'success');
        }

        // حفظ الترجمات
        foreach ($this->serviceTranslations as $translation) {
            ServiceTranslation::updateOrCreate(
                ['service_id' => $service->id, 'locale' => $translation['locale']],
                ['title' => $translation['title'], 'description' => $translation['description']]
            );
        }

        $this->resetForm();
        $this->resetPage();
        $this->mode = 'index';
    }
    public function confirmDelete($id)
    {
        // نرسل حدث إلى المتصفح لفتح SweetAlert
        $this->dispatchBrowserEvent('show-delete-confirmation', ['id' => $id]);
    }
    public function deleteServiceConfirmed($id)
    {
        try {
        $service = Service::findOrFail($id);

        if ($service->icon && Storage::disk('public')->exists($service->icon)) {
            Storage::disk('public')->delete($service->icon);
        }

        $service->delete();

        $this->dispatch('service-deleted-success');
        $this->showAlert('✅ تم حذف الخدمة بنجاح', 'success');
        } catch (\Exception $e) {
        logger()->error('خطأ أثناء الحذف: ' . $e->getMessage());
        $this->dispatch('service-delete-failed');
        $this->showAlert('❌ حدث خطأ أثناء الحذف', 'danger');
        }
        $this->resetPage();
    }
    
    public function render()
    {
        $services = Service::with('translations')->paginate($this->perPage);
        return view('livewire.services', compact('services'));
    }
}
