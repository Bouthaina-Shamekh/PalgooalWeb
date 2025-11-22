<?php

namespace App\Livewire\Admin\Clients;

use Livewire\Component;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientNote;
use App\Models\ActivityLog;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ClientList extends Component
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
    public $clientId = null;

    // بيانات العميل الأساسية
    public $client = [
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

    // للتحكم في عرض الأقسام في صفحة العرض
    public $activeTab = 'details'; // details, contacts, notes, activities

    public function showAdd()
    {
        $this->mode = 'add';
        $this->resetForm();
        $this->closeModal();
    }

    public function showEdit($id)
    {
        $this->mode = 'edit';
        $this->clientId = $id;
        $client = Client::findOrFail($id);
        $this->client = [
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
        ];
        $this->closeModal();
    }

    public function showDetails($id)
    {
        $this->mode = 'show';
        $this->clientId = $id;
        $this->activeTab = 'details';
        $this->closeModal();
    }

    public function showIndex()
    {
        $this->mode = 'index';
        $this->closeModal();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function resetForm()
    {
        $this->client = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'password' => '',
            'confirm_password' => '',
            'company_name' => '',
            'phone' => '',
            'can_login' => true,
            'avatar' => null,
            'status' => 'active',
            'country' => '',
            'city' => '',
            'address' => '',
            'zip_code' => '',
        ];
        $this->contact = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'role' => 'general',
            'can_login' => false,
            'password_hash' => '',
        ];
        $this->note = [
            'note' => '',
        ];
        $this->clientId = null;
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

    public function checkPassword()
    {
        $this->checkPasswordError();
        if ($this->client['password'] != $this->client['confirm_password']) {
            $this->showAlert('Passwords do not match.', 'warning');
            return;
        }
        if (!$this->uppercase || !$this->lowercase || !$this->number || !$this->specialChars || strlen($this->client['password']) < 8) {
            $this->showAlert('Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.', 'warning');
            return;
        }
        $this->closeModal();
    }

    public function save()
    {
        $validated = $this->validate([
            'client.first_name' => 'required',
            'client.last_name' => 'required',
            'client.email' => 'required|email|unique:clients,email,' . $this->clientId,
            'client.company_name' => 'nullable',
            'client.password' => $this->clientId ? 'nullable|min:6|same:client.confirm_password' : 'required|min:6|same:client.confirm_password',
            'client.confirm_password' => $this->clientId ? 'nullable' : 'required',
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
        $clientValidated['can_login'] = $clientValidated['can_login'] ? 1 : 0;

        if ($this->clientId) {
            $client = Client::findOrFail($this->clientId);

            if ($this->client['avatar']) {
                if ($client->avatar && Storage::disk('public')->exists($client->avatar)) {
                    Storage::disk('public')->delete($client->avatar);
                }

                $clientValidated['avatar'] = $this->client['avatar']->store('avatars', 'public');
            } else {
                $clientValidated['avatar'] = $client->avatar;
            }

            if (!empty($this->client['password'])) {
                $this->checkPassword();
                if (!$this->uppercase || !$this->lowercase || !$this->number || !$this->specialChars || strlen($this->client['password']) < 8) {
                    $this->showAlert('Password should be at least 8 characters in length and include an uppercase letter, a number, and a special character.', 'warning');
                    return;
                }
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
        } else {
            if ($this->client['avatar']) {
                $clientValidated['avatar'] = $this->client['avatar']->store('avatars', 'public');
            }

            if (!empty($this->client['password'])) {
                $this->checkPassword();
                if (!$this->uppercase || !$this->lowercase || !$this->number || !$this->specialChars || strlen($this->client['password']) < 8) {
                    $this->showAlert('Password should be at least 8 characters in length and include an uppercase letter, a number, and a special character.', 'warning');
                    return;
                }
                $clientValidated['password'] = bcrypt($this->client['password']);
            } else {
                $this->showAlert('Password is required.', 'warning');
                return;
            }

            $client = Client::create($clientValidated);

            // تسجيل النشاط
            ActivityLog::create([
                'actor_type' => 'admin',
                'actor_id' => Auth::id(),
                'action' => 'client.created',
                'meta' => [
                    'client_id' => $client->id,
                    'client_name' => $client->first_name . ' ' . $client->last_name,
                ]
            ]);

            $this->showAlert('Client added successfully.', 'success');
        }

        $this->resetForm();
        $this->resetPage();
        $this->mode = 'index';
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

    // إضافة ملاحظة جديدة
    public function addNote()
    {
        $validated = $this->validate([
            'note.note' => 'required|string',
        ]);

        ClientNote::create([
            'client_id' => $this->clientId,
            'admin_id' => Auth::id(),
            'note' => $validated['note']['note'],
        ]);

        // تسجيل النشاط
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.note.created',
            'meta' => [
                'client_id' => $this->clientId,
            ]
        ]);

        $this->note = ['note' => ''];
        $this->showAlert('Note added successfully.', 'success');
    }

    // حذف ملاحظة
    public function deleteNote($noteId)
    {
        $note = ClientNote::findOrFail($noteId);

        // تسجيل النشاط
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.note.deleted',
            'meta' => [
                'client_id' => $note->client_id,
            ]
        ]);

        $note->delete();
        $this->showAlert('Note deleted successfully.', 'success');
    }

    public function delete($id)
    {
        $client = Client::findOrFail($id);

        // تسجيل النشاط قبل الحذف
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.deleted',
            'meta' => [
                'client_id' => $client->id,
                'client_name' => $client->first_name . ' ' . $client->last_name,
            ]
        ]);

        if ($client->avatar && Storage::disk('public')->exists($client->avatar)) {
            Storage::disk('public')->delete($client->avatar);
        }
        $client->delete();

        $this->showAlert('Client deleted successfully.', 'success');
        $this->resetPage();
    }

    public function loginAs($id)
    {
        $admin = Auth::user();

        if (!$admin || !$admin->can('login', Client::class)) {
            $this->showAlert('You are not authorized to login as clients.', 'danger');
            return;
        }

        $client = Client::findOrFail($id);

        if (!$client->can_login) {
            $this->showAlert('Client login is disabled.', 'warning');
            return;
        }

        Auth::guard('client')->login($client);

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => $admin->id,
            'action' => 'client.impersonated',
            'meta' => [
                'client_id' => $client->id,
                'client_email' => $client->email,
            ]
        ]);

        session()->flash('success', 'You are now logged in as the client.');

        return redirect()->route('client.home');
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
        $clients = Client::query()
            ->withCount(['subscriptions', 'domains']) // إضافة العدادات
            ->where(function ($query) {
                $query->where('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('company_name', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        // للصفحة show - جلب بيانات العميل مع العلاقات
        $currentClient = null;
        $clientContacts = collect();
        $clientNotes = collect();
        $clientActivities = collect();

        if ($this->mode === 'show' && $this->clientId) {
            $currentClient = Client::with(['subscriptions', 'domains'])
                ->withCount(['subscriptions', 'domains', 'contacts', 'notes'])
                ->findOrFail($this->clientId);

            $clientContacts = $currentClient->contacts()->orderBy('created_at', 'desc')->get();
            $clientNotes = $currentClient->notes()->with('admin')->orderBy('created_at', 'desc')->get();
            $clientActivities = ActivityLog::where(function ($query) {
                $query->where('actor_type', 'client')->where('actor_id', $this->clientId)
                    ->orWhere('meta->client_id', $this->clientId);
            })
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
        }

        return view('livewire.client', compact(
            'clients',
            'currentClient',
            'clientContacts',
            'clientNotes',
            'clientActivities'
        ));
    }
}

