<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Template;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Http;


class CheckoutClient extends Component
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

    public $template_id = '';
    public $template = null;

    public $checkoutData = [
        'client_id' => '',
        'domain_name' => '',
        'registrar' => '',
        'registration_date' => '',
        'renewal_date' => '',
        'status' => '',
    ];

    public $mode = 'domain';
    public $domain = '';
    public $domain_name = '';
    public $domain_extension = '.com';
    public $domain_price = 0;
    public $domain_available = null;
    public $domain_check = false;
    public $domain_extensions_available = [];
    public $domain_names_available = [];

    public $sumSub = 0;
    public $sumDiscount = 0;
    public $sumTax = 0;

    public $domains = [];

    public $domain_extensions = [
        '.com' => 9,
        '.net' => 10,
        '.org' => 12,
        '.io' => 15,
        '.co' => 18,
    ];
    public $locale;
    public function mount()
    {
        $this->locale = app()->getLocale();
        $this->template = Template::findOrFail($this->template_id)
        ->with(['translations','categoryTemplate.translations'])
        ->first();

        $this->sumSub = $this->template->price;
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
            $this->mode = 'review';
            $this->domain_price = $this->domain_extensions[$this->domain_extension];
            $this->sumSub += $this->domain_price;
            $this->checkoutData['domain_name'] = $domain;
            $this->checkoutData['registrar'] = 'enom';
            $this->checkoutData['registration_date'] = Carbon::now()->format('Y-m-d');
            $this->checkoutData['renewal_date'] = Carbon::now()->addYears(1)->format('Y-m-d');
            $this->checkoutData['status'] = 'pending';
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

    public $client = null;
    public $clientData = [
        'email' => '',
        'password' => '',
    ];
    public $clientDataRegister = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'password' => '',
        'company_name' => '',
        'phone' => '',
    ];
    public $type_login_client = 'login';
    public $check_login_client = false;

    public function setTypeLoginClient($type)
    {
        $this->type_login_client = $type;
    }

    public function loginClient($type)
    {
        if($type == 'login'){
            $this->validate([
                'clientData.email' => 'required|email',
                'clientData.password' => 'required',
            ]);
            $client = Client::where('email', $this->clientData['email'])->first();
            if ($client) {
                if (Hash::check($this->clientData['password'], $client->password)) {
                    Auth::guard('client')->login($client);
                    $this->showAlert('Login successful.', 'success');
                    $this->check_login_client = true;
                    $this->client = $client;
                    $this->type_login_client = '';
                } else {
                    $this->showAlert('Invalid password.', 'error');
                }
            } else {
                $this->showAlert('Client not found.', 'error');
            }
        }
        if($type == 'register'){
            $this->validate([
                'clientDataRegister.email' => 'required|email',
                'clientDataRegister.password' => 'required',
                'clientDataRegister.first_name' => 'required',
                'clientDataRegister.last_name' => 'required',
                'clientDataRegister.phone' => 'required',
            ]);
            $client = Client::where('email', $this->clientDataRegister['email'])->first();
            if ($client) {
                $this->showAlert('Client already exists.', 'error');
                return;
            } else {
                $client = Client::create([
                    'first_name'  => $this->clientDataRegister['first_name'],
                    'last_name'   => $this->clientDataRegister['last_name'],
                    'email'       => $this->clientDataRegister['email'],
                    'password'    => Hash::make($this->clientDataRegister['password']),
                    'company_name'=> $this->clientDataRegister['company_name'],
                    'phone'       => $this->clientDataRegister['phone'],
                ]);
                Auth::guard('client')->login($client);
                $this->showAlert('Client registered successfully.', 'success');
                $this->check_login_client = true;
                $this->client = $client;
                $this->type_login_client = '';
            }
        }
    }

    public $sumTotal = 0;
    public $coupon = '';

    public function applyCoupon()
    {
        // $this->validate([
        //     'coupon' => 'required',
        // ]);
        // $coupon = Coupon::where('code', $this->coupon)->first();
        // if ($coupon) {
        //     $this->sumDiscount = $coupon->discount;
        //     $this->sumTotal = $this->sumSub - $this->sumDiscount;
        // }else{
        //     $this->showAlert('Coupon not found.', 'error');
        // }
        $this->sumDiscount = 10;
        $this->sumTotal = $this->sumSub - $this->sumDiscount;
    }

    public $paymentData = [
        'payment_method' => 'card',
        'ccNumber' => '',
        'ccName' => '',
        'ccExp' => '',
        'ccCvv' => '',
        'bankName' => '',
        'bankRef' => '',
        'bankNote' => '',
    ];
    public $payment_check = false;
    public function setPaymentMethod($method)
    {
        $this->paymentData['payment_method'] = $method;
    }

    public function validateCard()
    {
        $this->validate([
            'paymentData.ccNumber' => 'required|numeric',
            'paymentData.ccName' => 'required',
            'paymentData.ccExp' => 'required',
            'paymentData.ccCvv' => 'required',
        ]);
        $this->payment_check = true;
        $this->showAlert('Card validated successfully.', 'success');
    }

    public function validateBank()
    {
        $this->validate([
            'paymentData.bankName' => 'required',
            'paymentData.bankRef' => 'required',
            'paymentData.bankNote' => 'required',
        ]);
        $this->payment_check = true;
        $this->showAlert('Bank validated successfully.', 'success');
    }


    public function submit()
    {
        $validated = $this->validate([
            'client.id' => 'required|exists:clients,id',
            'checkoutData.domain_name' => 'required|unique:domains,domain_name,' . $this->checkoutData['domain_name'],
            'checkoutData.registrar' => 'required',
            'checkoutData.registration_date' => 'required',
            'checkoutData.renewal_date' => 'required',
            'checkoutData.status' => 'required',
            'paymentData.payment_method' => 'required',
            'template.id' => 'required|exists:templates,id',
        ]);
        $domainValidated = $validated['checkoutData'];
        $domainValidated['client_id'] = $validated['client']['id'];
        $domainValidated['payment_method'] = $validated['paymentData']['payment_method'];
        $domainValidated['template_id'] = $validated['template']['id'];
        $domain = Domain::where('domain_name', $this->checkoutData['domain_name'])->first();
        if ($domain) {
            $domain->update($domainValidated);
            $this->mode = 'success';
            $this->showAlert('Domain updated successfully.', 'success');
        } else {
            Domain::create($domainValidated);
            $this->mode = 'success';
            $this->showAlert('Domain added successfully.', 'success');
        }

        return redirect()->route('client.home');
    }


    public function render()
    {
        return view('livewire.checkout-client');
    }
}
