<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Template;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class DomainTableClient extends Component
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
    public $client = [];
    public $domainId = null;

    public $domain = [
        'client_id' => '',
        'domain_name' => '',
        'registrar' => '',
        'registration_date' => '',
        'renewal_date' => '',
        'status' => '',
    ];
    public $templates = [];

    public function mount()
    {
        $client = Client::findOrFail(Auth::guard('client')->user()->id);
        $this->client = $client;
        $this->domain['client_id'] = $client->id;
    }

    public function showEdit($id)
    {
        $this->mode = 'edit';
        $this->domainId = $id;
        $this->templates = Template::get();
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
            // تحديث الفاتورة إن وجدت
            $invoiceItem = $domain->invoiceItems()->first();

            if ($invoiceItem && $invoiceItem->invoice) {
                $invoiceItem->update([
                    'description' => 'تحديث دومين: ' . $domain->domain_name,
                ]);
            }
            $this->showAlert('Domain updated successfully.', 'success');
        } else {
            $domain = Domain::create($domainValidated);
            $price_cents = 0;

            $invoice = Invoice::create([
                'client_id' => $domainValidated['client_id'],
                'number' => 'INV-' . strtoupper(Str::random(6)),
                'status' => 'unpaid',
                'subtotal_cents' => $price_cents,
                'total_cents' => $price_cents,
                'currency' => 'USD',
                'due_date' => $domain->renewal_date ?? now()->addDays(7),
            ]);

            $invoice->items()->create([
                'item_type' => 'domain',
                'reference_id' => $domain->id,
                'description' => 'تسجيل دومين ' . $domain->domain_name,
                'qty' => 1,
                'unit_price_cents' => $price_cents,
                'total_cents' => $price_cents,
            ]);
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
            ->where('client_id',$this->client['id'])
            ->paginate($this->perPage);

        return view('livewire.domain-table-client', compact('domains'));
    }
}
