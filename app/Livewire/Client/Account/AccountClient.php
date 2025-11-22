<?php

namespace App\Livewire\Client\Account;

use App\Models\ActivityLog;
use Livewire\Component;
use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class AccountClient extends Component
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

    // بيانات العميل الأساسية
    public $client = [
        'id' => null,
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'password' => '',
        'confirm_password' => '',
        'company_name' => '',
        'phone' => '',
        'can_login' => true,
        'avatar' => null,
        'avatar_url' => null,
        'status' => 'active',
        'country' => '',
        'city' => '',
        'address' => '',
        'zip_code' => '',
    ];

    // بيانات جهة الاتصال الجديدة
    public $contact = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'role' => 'general',
        'can_login' => false,
        'password_hash' => '',
    ];

    // بيانات الملاحظة الجديدة
    public $note = [
        'note' => '',
    ];

    public function mount()
    {
        $client = Client::with('contacts', 'notes')->findOrFail(Auth::guard('client')->user()->id);
        $this->client = [
            'id' => $client->id,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'email' => $client->email,
            'company_name' => $client->company_name,
            'phone' => $client->phone,
            'can_login' => $client->can_login,
            'password' => '',
            'confirm_password' => '',
            'avatar' => null,
            'avatar_url' => $client->avatar,
            'status' => $client->status ?? 'active',
            'country' => $client->country ?? '',
            'city' => $client->city ?? '',
            'address' => $client->address ?? '',
            'zip_code' => $client->zip_code ?? '',
            'contacts' => $client->contacts,
            'notes' => $client->notes,
        ];
    }

    public $uppercase;
    public $lowercase;
    public $number;
    public $specialChars;
    public function checkPasswordError()
    {
        $this->uppercase = preg_match('@[A-Z]@', $this->client['password']);
        $this->lowercase = preg_match('@[a-z]@', $this->client['password']);
        $this->number    = preg_match('@[0-9]@', $this->client['password']);
        $this->specialChars = preg_match('@[^\w]@', $this->client['password']);
    }
    public function checkPassword(){
        if (empty($this->client['password'])) return;

        $this->checkPasswordError();

        if (empty($this->client['confirm_password'])) return;
        if ($this->client['password'] !== $this->client['confirm_password']) {
            $this->showAlert('Passwords do not match.', 'warning');
            return;
        }

    }
    public function save()
    {

        $validated = $this->validate([
            'client.first_name' => 'required',
            'client.last_name' => 'required',
            'client.email' => 'required|email|unique:clients,email,' . $this->client['id'],
            'client.company_name' => 'nullable',
            'client.password' => $this->client['id'] ? 'nullable|min:6|same:client.confirm_password' : 'required|min:6|same:client.confirm_password',
            'client.confirm_password' => $this->client['id'] ? 'nullable' : 'required',
            'client.phone' => 'required',
            'client.can_login' => 'boolean',
            'client.avatar' => 'nullable|image',
            'client.status' => 'required|in:active,inactive',
            'client.country' => 'nullable|string|max:2',
            'client.city' => 'nullable|string',
            'client.address' => 'nullable|string',
            'client.zip_code' => 'nullable|string',
        ]);

        $clientValidated = $validated['client'];

        $client = Client::findOrFail($this->client['id']);

        if ($this->client['avatar']) {
            if ($client->avatar && Storage::disk('public')->exists($client->avatar)) {
                Storage::disk('public')->delete($client->avatar);
            }

            $clientValidated['avatar'] = $this->client['avatar']->store('avatars', 'public');
        }else{
            $clientValidated['avatar'] = $client->avatar;
        }

        if (!empty($this->client['password'])) {
            $this->checkPassword();
            if (!$this->uppercase || !$this->lowercase || !$this->number || !$this->specialChars || strlen($this->client['password']) < 8) {
                $this->showAlert('Password should be at least 8 characters in length and include an uppercase letter, a number, and a special character.', 'warning');
                return;
            }
            $this->closeModal();
            $clientValidated['password'] = bcrypt($this->client['password']);
        } else {
            unset($clientValidated['password']);
        }

        $client->update($clientValidated);

        // تسجيل النشاط
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.updated',
            'meta' => [
                'client_id' => $client->id,
                'client_name' => $client->first_name . ' ' . $client->last_name,
                'changes' => array_keys($clientValidated)
            ]
        ]);

        $this->showAlert('Client updated successfully.', 'success');

    }

    // إضافة جهة اتصال جديدة
    public function addContact()
    {
        $validated = $this->validate([
            'contact.name' => 'required|string',
            'contact.email' => 'required|email|unique:client_contacts,email',
            'contact.phone' => 'nullable|string',
            'contact.role' => 'required|in:billing,tech,general',
            'contact.can_login' => 'boolean',
            'contact.password_hash' => $this->contact['can_login'] ? 'required|min:6' : 'nullable',
        ]);

        $contactData = $validated['contact'];
        $contactData['client_id'] = $this->clientId;
        $contactData['can_login'] = $contactData['can_login'] ? 1 : 0;

        if (!empty($contactData['password_hash'])) {
            $contactData['password_hash'] = bcrypt($contactData['password_hash']);
        } else {
            unset($contactData['password_hash']);
        }

        ClientContact::create($contactData);

        // تسجيل النشاط
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.contact.created',
            'meta' => [
                'client_id' => $this->clientId,
                'contact_name' => $contactData['name'],
                'contact_role' => $contactData['role']
            ]
        ]);

        $this->contact = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'role' => 'general',
            'can_login' => false,
            'password_hash' => '',
        ];

        $this->showAlert('Contact added successfully.', 'success');
    }

     // حذف جهة اتصال
     public function deleteContact($contactId)
     {
         $contact = ClientContact::findOrFail($contactId);

         // تسجيل النشاط قبل الحذف
         ActivityLog::create([
             'actor_type' => 'admin',
             'actor_id' => Auth::id(),
             'action' => 'client.contact.deleted',
             'meta' => [
                 'client_id' => $contact->client_id,
                 'contact_name' => $contact->name,
                 'contact_role' => $contact->role
             ]
         ]);

         $contact->delete();
         $this->showAlert('Contact deleted successfully.', 'success');
     }


    public function render()
    {
        return view('livewire.account-clinet');
    }
}
