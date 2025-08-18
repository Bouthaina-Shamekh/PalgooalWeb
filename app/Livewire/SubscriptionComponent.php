<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Domain;
use App\Models\Plan;
use Livewire\Component;
use App\Models\Subscription;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class SubscriptionComponent extends Component
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
    public $subscriptionId = null;
    public $clients = [];
    public $domains = [];
    public $plans  = [];


    public $subscription = [
      'client_id' => '',
      'plan_id'   => '',
      'status' => '',
      'start_date' => '',
      'end_date' => '',
      'domain_option' => '',
      'domain_name' => '',
    ];

    public function showAdd()
    {
        $this->mode = 'add';
        $this->clients = Client::get();
        $this->domains = Domain::get();
        $this->plans  = Plan::get();
        $this->resetForm();
        $this->closeModal();
    }

    public function showEdit($id)
    {
        $this->mode = 'edit';
        $this->subscriptionId = $id;
        $this->clients = Client::get();
        $this->domains = Domain::get();
        $this->plans  = Plan::get();
        $subscription = Subscription::findOrFail($id);
        $this->subscription = [
            'client_id' => $subscription->client_id,
            'plan_id'   => $subscription->plan_id,
            'status' => $subscription->status,
            'start_date' => $subscription->start_date,
            'end_date' => $subscription->end_date,
            'domain_option' => $subscription->domain_option,
            'domain_name' => $subscription->domain_name,
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
        $this->subscription = [
            'client_id' => '',
            'plan_id'   => '',
            'status' => '',
            'start_date' => '',
            'end_date' => '',
            'domain_option' => '',
            'domain_name' => '',
        ];
        $this->subscriptionId = null;
    }

    public function save()
    {
        $validated = $this->validate([
            'subscription.client_id'     => 'required|exists:clients,id',
            'subscription.plan_id'       => 'required|exists:plans,id',
            'subscription.status'        => 'required|in:active,canceled,pending',
            'subscription.start_date'    => 'required|date',
            'subscription.end_date'      => 'nullable|date|after_or_equal:subscription.start_date',
            'subscription.domain_option' => 'required|in:new,subdomain,existing',
            'subscription.domain_name'   => 'required_if:subscription.domain_option,new,existing',
        ]);

        $subscriptionValidated = $validated['subscription'];

        if ($this->subscriptionId) {
            $subscription = Subscription::findOrFail($this->subscriptionId);

            $subscription->update($subscriptionValidated);
            $this->showAlert('Subscription updated successfully.', 'success');
        } else {

            Subscription::create($subscriptionValidated);
            $this->showAlert('Subscription added successfully.', 'success');
        }

        $this->resetForm();
        $this->resetPage();
        $this->mode = 'index';
    }

    public function delete($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->delete();

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
        $subscriptions = Subscription::query()
            ->where('domain_name', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.subscription', compact('subscriptions'));
    }
}
