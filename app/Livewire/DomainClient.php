<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;


class DomainClient extends Component
{

    use WithFileUploads;

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

    public $domainData = [
        'client_id' => '',
        'domain_name' => '',
        'registrar' => '',
        'registration_date' => '',
        'renewal_date' => '',
        'status' => '',
    ];

    public $mode = 'search';
    public $domain = '';
    public $domain_name = '';
    public $domain_extension = '.com';
    public $domain_price = 0;
    public $domain_available = null;
    public $domain_check = false;
    public $domain_extensions_available = [];
    public $domain_names_available = [];

    public $domains = [];

    public $domain_extensions = [
        '.com' => 9,
        '.net' => 10,
        '.org' => 12,
        '.io' => 15,
        '.co' => 18,
    ];
    public function mount()
    {
        $client = Client::findOrFail(Auth::guard('client')->user()->id);
        $this->domainData['client_id'] = $client->id;
        $this->domains = Domain::where('client_id', $client->id)->get();
    }

    public function setExtension($extension)
    {
        $this->domain_extension = $extension;
    }
    public function showSearch()
    {
        $this->mode = 'search';
    }

    public function search()
    {
        $this->validate([
            'domain_name' => 'required',
            'domain_extension' => 'required',
        ]);

        $domain = $this->domain_name . $this->domain_extension;

        $this->domain = $domain;
        $this->domain_available = $this->checkDomainWithRapidApi($domain);
        $this->domain_check = true;
        if ($this->domain_available) {
            $this->mode = 'buy';
            $this->domainData['domain_name'] = $domain;
            $this->domainData['registrar'] = 'enom';
            $this->domainData['registration_date'] = Carbon::now()->format('Y-m-d');
            $this->domainData['renewal_date'] = Carbon::now()->addYears(1)->format('Y-m-d');
            $this->domainData['status'] = 'pending';
            $this->domain_extensions_available = [];
            $this->domain_names_available = [];
        }
        if (!$this->domain_available) {
            $this->domain_extensions_available = [];
            $this->domain_names_available = [];
            foreach($this->domain_extensions as $extension => $price){
                $domain = $this->domain_name . $extension;
                $this->domain_available = $this->checkDomainWithRapidApi($domain);
                if ($this->domain_available) {
                    $this->domain_extensions_available[] = $extension;
                }
            }
            if(count($this->domain_extensions_available) == 0){
                $found = 0;
                while ($found < 3) {
                    $random_string = substr(str_shuffle(MD5(microtime())), 0, 3);
                    $domain = $this->domain_name . $random_string . $this->domain_extension;
                    $domain_available = $this->checkDomainWithRapidApi($domain);
                    if ($domain_available) {
                        $this->domain_names_available[] = $domain;
                        $found++;
                    }
                }
            }
        }
        // $this->showAlert('Domain ' . $domain . ' is available.', 'success');
    }
    public function checkDomainWithRapidApi($domain)
    {
        $response = Http::withHeaders([
            'x-rapidapi-host' => 'domainr.p.rapidapi.com',
            'x-rapidapi-key' => 'b33685147cmshf3a6f5c32c46eb1p13e1f9jsna1975004573c',
        ])->get('https://domainr.p.rapidapi.com/v2/status', [
            'domain' => $domain,
        ]);
        if ($response->successful()) {
            $result = $response->json();
            // مثال: نجيب حالة الدومين من الرد
            if (isset($result['status'][0]['status'])) {
                $status = $result['status'][0]['status'];

                // الدومين متاح فقط لو الحالة مش active
                if ($status !== 'active') {
                    return true;  // متاح
                } else {
                    return false; // محجوز
                }
            }
        }

        // لو فشل الطلب أو ما في حالة واضحة، نرجع false
        return false;
    }

    public function save()
    {
        $validated = $this->validate([
            'domainData.client_id' => 'required|exists:clients,id',
            'domainData.domain_name' => 'required|unique:domains,domain_name,' . $this->domainData['domain_name'],
            'domainData.registrar' => 'required',
            'domainData.registration_date' => 'required',
            'domainData.renewal_date' => 'required',
            'domainData.status' => 'required',
        ]);
        $domainValidated = $validated['domainData'];
        $domain = Domain::where('domain_name', $this->domainData['domain_name'])->first();
        if ($domain) {
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
                'client_id' => $domain->client_id,
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
        $this->mode = 'index';
    }


    public function render()
    {
        return view('livewire.domain-client');
    }
}
