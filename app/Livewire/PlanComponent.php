<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Plan;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class PlanComponent extends Component
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
    public $planId = null;

    public $plan = [
        'name' => '',
        'price' => 0,
        'features' => [],
        'feature' => '',
    ];

    public function showAdd()
    {
        $this->mode = 'add';
        $this->resetForm();
        $this->closeModal();
    }

    public function showEdit($id)
    {
        $this->mode = 'edit';
        $this->planId = $id;
        $plan = Plan::findOrFail($id);
        $this->plan = [
            'name' => $plan->name,
            'price' => $plan->price,
            'features' => json_decode($plan->features, true),
            'feature' => '',
        ];
        $this->closeModal();
    }

    public function showIndex()
    {
        $this->mode = 'index';
        $this->closeModal();
    }

    public function resetForm()
    {
        $this->plan = [
            'name' => '',
            'price' => 0,
            'features' => [],
            'feature' => '',
        ];
        $this->planId = null;
    }
    public function addFeature()
    {
        $this->plan['features'][] = $this->plan['feature'];
        $this->plan['feature'] = '';
    }

    public function removeFeature($index)
    {
        unset($this->plan['features'][$index]);
    }

    public function save()
    {
        $validated = $this->validate([
            'plan.name' => 'required',
            'plan.price' => 'required',
            'plan.features' => 'required',
        ]);

        $planValidated = $validated['plan'];

        if ($this->planId) {
            $plan = Plan::findOrFail($this->planId);

            $planValidated['features'] = json_encode($planValidated['features']);

            $plan->update($planValidated);
            $this->showAlert('Plan updated successfully.', 'success');
        } else {

            $planValidated['features'] = json_encode($planValidated['features']);
            Plan::create($planValidated);
            $this->showAlert('Plan added successfully.', 'success');
        }

        $this->resetForm();
        $this->resetPage();
        $this->mode = 'index';
    }

    public function delete($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();

        $this->showAlert('Plan deleted successfully.', 'success');
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
        $plans = Plan::query()
            ->where('name', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.plan', compact('plans'));
    }
}
