<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Domain;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class DomainComponent extends Component
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
    public $clients = [];
    public $domainId = null;

    public $domain = [
        'client_id' => '',
        'domain_name' => '',
        'registrar' => '',
        'registration_date' => '',
        'renewal_date' => '',
        'status' => '',
    ];

    public function showAdd()
    {
        $this->mode = 'add';
        $this->clients = Client::get();
        $this->resetForm();
        $this->closeModal();
    }

    public function showEdit($id)
    {
        $this->mode = 'edit';
        $this->domainId = $id;
         $this->clients = Client::get();
        $domain = Domain::findOrFail($id);
        $this->domain = [
            'client_id' => $domain->client_id,
            'domain_name' => $domain->domain_name,
            'registrar' => $domain->registrar,
            'registration_date' => $domain->registration_date,
            'renewal_date' => $domain->renewal_date,
            'status' => $domain->status,
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
        $this->domain = [
            'client_id' => '',
            'domain_name' => '',
            'registrar' => '',
            'registration_date' => '',
            'renewal_date' => '',
            'status' => '',
        ];
        $this->domainId = null;
    }

   
    
    public function save()
    {
        $validated = $this->validate([
            'domain.client_id' => 'required|exists:clients,id',
            'domain.domain_name' => 'required|unique:domains,domain_name,' . $this->domainId,
            'domain.registrar' => 'required',
            'domain.registration_date' => 'required',
            'domain.renewal_date' => 'required',
            'domain.status' => 'required',
           
        ]);

       $domainValidated = $validated['domain'];

        if ($this->domainId) {
            $domain = Domain::findOrFail($this->domainId);
            $domain->update($domainValidated);
            $this->showAlert('Domain updated successfully.', 'success');
        } else {
            Domain::create($domainValidated);
            $this->showAlert('Domain added successfully.', 'success');
        }

        $this->resetForm();
        $this->resetPage();
        $this->mode = 'index';
    }

    public function delete($id)
    {
        $domain = Domain::findOrFail($id);
        $domain->delete();

        $this->showAlert('Domain deleted successfully.', 'success');
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
        $domains = Domain::query()
            ->orWhere('domain_name', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.domain', compact('domains'));
    }
}
