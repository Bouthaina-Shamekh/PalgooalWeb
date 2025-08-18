<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Domain;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Plan;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use App\Jobs\ProvisionHosting;
use App\Services\ProvisioningService;
use Illuminate\Support\Facades\DB;

class SiteComponent extends Component
{
    use WithPagination;

    // --- حالات التنبيه ---
    public bool $alert       = false;
    public string $alertType = 'success';
    public string $alertMessage = '';

    public function showAlert(string $message, string $type = 'success'): void
    {
        $this->alert        = true;
        $this->alertType    = $type;
        $this->alertMessage = $message;
    }

    public function closeModal(): void
    {
        $this->alert = false;
    }

    public function createAccount(string $domain, string $username, string $password, string $plan, int $quota, int $bwlimit, string $contactEmail){
        $provisioning = app(ProvisioningService::class);
        $result = $provisioning->createAccount(
            $domain,
            $username,
            $password,
            $plan,
            $quota,
            $bwlimit,
            $contactEmail
        );
        if ($result['metadata']['result'] === 1) {
            $accountData = [
                'username' => $result['metadata']['output']['raw'] ?? null, // أو يمكن تحليله لو بغيت
                'domain' => $result['data']['domain'] ?? null,
                'ip' => $result['data']['ip'] ?? null,
                'package' => $result['data']['package'] ?? null,
                'status' => $result['metadata']['result'] ?? 0,
                'message' => $result['metadata']['reason'] ?? '',
            ];
        }else{
            $accountData = false;
        }

        return $accountData;
    }
    // --- المتغيرات الرئيسية ---
    public string $mode     = 'index';
    public string $search   = '';
    public int    $perPage  = 10;
    public ?int   $siteId   = null;

    public $clients;
    public $domains;
    public $subscriptions;
    public $plans;
    public $provisioningStatuses = ['pending', 'active', 'failed'];

    public array $site = [
        'client_id'           => '',
        'domain_id'           => '',
        'subscription_id'     => '',
        'plan_id'             => '',
        'provisioning_status' => 'pending',
        'cpanel_username'     => '',
        'cpanel_password'     => '',
        'cpanel_url'          => '',
        'provisioned_at'      => '',
    ];

    // --- نماذج إضافة وتعديل وحذف ---
    public function showAdd(): void
    {
        $this->mode    = 'add';
        $this->clients = Client::get();
        $this->domains = Domain::get();
        $this->subscriptions = Subscription::get();
        $this->plans   = Plan::get();
        $this->resetForm();
        $this->closeModal();
    }

    public function showEdit(int $id): void
    {
        $this->mode    = 'edit';
        $this->siteId  = $id;
        $this->clients = Client::get();
        $this->domains = Domain::get();
        $this->subscriptions = Subscription::get();
        $this->plans   = Plan::get();

        $site     = Site::findOrFail($id);
        $this->site    = [
            'client_id'           => $site->client_id,
            'domain_id'           => $site->domain_id,
            'subscription_id'     => $site->subscription_id,
            'plan_id'             => $site->plan_id,
            'cpanel_username'     => $site->cpanel_username,
            'cpanel_password'     => $site->cpanel_password,
            'cpanel_url'          => $site->cpanel_url,
            'provisioning_status' => $site->provisioning_status,
            'provisioned_at'      => $site->provisioned_at,
        ];

        $this->closeModal();
    }

    public function showIndex(): void
    {
        $this->mode = 'index';
        $this->closeModal();
    }

    protected function resetForm(): void
    {
        $this->site = [
            'client_id'           => '',
            'domain_id'           => '',
            'subscription_id'     => '',
            'plan_id'             => '',
            'provisioning_status' => 'pending',
            'cpanel_username'     => '',
            'cpanel_password'     => '',
            'cpanel_url'          => '',
            'provisioned_at'      => '',
        ];
        $this->siteId = null;
    }

    // --- فحص قوة كلمة المرور ---
    public $uppercase;
    public $lowercase;
    public $number;
    public $specialChars;
    public function checkPasswordError(){
        $this->uppercase = preg_match('@[A-Z]@', $this->site['cpanel_password']);
        $this->lowercase = preg_match('@[a-z]@', $this->site['cpanel_password']);
        $this->number    = preg_match('@[0-9]@', $this->site['cpanel_password']);
        $this->specialChars = preg_match('@[^\w]@', $this->site['cpanel_password']);
    }
    public function checkPassword(){
        $this->checkPasswordError();
        if(!$this->uppercase || !$this->lowercase || !$this->number || !$this->specialChars || strlen($this->site['cpanel_password']) < 8) {
            $this->showAlert('Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.', 'warning');
            return;
        }
        $this->closeModal();
    }

    // --- حفظ/تعديل السجل ---
    public function save(): void
    {
        DB::beginTransaction();
        try {
            // قواعد التحقق
            $validated = $this->validate([
                'site.client_id'           => 'required|exists:clients,id',
                'site.domain_id'           => 'required|exists:domains,id',
                'site.subscription_id'     => 'required|exists:subscriptions,id',
                'site.plan_id'             => 'required|exists:plans,id',
                'site.provisioning_status' => ['required', Rule::in($this->provisioningStatuses)],
                'site.cpanel_username'     => 'required|string|max:255',
                'site.cpanel_password'     => 'required|string|max:255',
                'site.cpanel_url'          => 'nullable|url|max:255',
                'site.provisioned_at'      => [
                    Rule::requiredIf(fn() => $this->site['provisioning_status'] === 'active'),
                    'nullable',
                    'date',
                ],
            ]);

            $data = $validated['site'];

            if($data['cpanel_url'] == '') {
                $data['cpanel_url'] = 'https://' . config('services.whm.host') . ':' . '2083';
            }

            if ($this->siteId) {
                // تعديل
                $site = Site::findOrFail($this->siteId);

                // إذا غير المستخدم كلمة المرور، نتحقق من قوتها
                if ($this->checkPassword()) {
                    $this->showAlert(
                        'Password must be ≥8 chars, include uppercase, number & special char.',
                        'warning'
                    );
                    return;
                }

                $site->update($data);
                $this->showAlert('Site updated successfully.', 'success');
            } else {
                if (!$this->uppercase || !$this->lowercase || !$this->number || !$this->specialChars || strlen($this->site['cpanel_password']) < 8) {
                    $this->showAlert(
                        'Password must be ≥8 chars, include uppercase, number & special char.',
                        'warning'
                    );
                    return;
                }

                $site = Site::create($data);
                $accountData = $this->createAccount($site->domain->domain_name, $site->cpanel_username, $site->cpanel_password, $site->plan->name, 0, 0, '');

                // if($accountData){
                //     dd($accountData);
                // }
                // dd($accountData);
                $this->showAlert('Site added successfully.', 'success');
            }

            $this->resetForm();
            $this->resetPage();
            $this->mode = 'index';
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->showAlert('Failed to save site: ' . $e->getMessage(), 'error');
        }
    }

    // إذا اخترنا الحالة active، نملأ تلقائياً تاريخ provisioned_at
    public function updatedSiteProvisioningStatus(string $value): void
    {
        if ($value === 'active') {
            $this->site['provisioned_at'] = now()->format('Y-m-d\TH:i');
        }
    }

    // حذف السجل
    public function delete(int $id): void
    {
        Site::findOrFail($id)->delete();
        $this->showAlert('Site deleted successfully.', 'success');
        $this->resetPage();
    }

    // إعادة تهيئة الصفحة عند تغيير البحث أو عدد الصفوف
    public function updateSearch(): void
    {
        $this->resetPage();
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }


    // عرض البيانات
    public function render()
    {
        $sites = Site::query()
            ->when($this->search, fn($q) => $q->where('cpanel_username', 'like', "%{$this->search}%"))
            ->paginate($this->perPage);

        return view('livewire.site', compact('sites'));
    }
}
