<?php

namespace App\Livewire\dashboard;

use Livewire\Component;
use App\Models\Client;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class ClientComponent extends Component
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
        ];
        $this->clientId = null;
    }

    public $uppercase;
    public $lowercase;
    public $number;
    public $specialChars;
    public function checkPasswordError(){
        $this->uppercase = preg_match('@[A-Z]@', $this->client['password']);
        $this->lowercase = preg_match('@[a-z]@', $this->client['password']);
        $this->number    = preg_match('@[0-9]@', $this->client['password']);
        $this->specialChars = preg_match('@[^\w]@', $this->client['password']);
    }
    public function checkPassword(){
        $this->checkPasswordError();
        if($this->client['password'] != $this->client['confirm_password']){
            $this->showAlert('Passwords do not match.', 'warning');
            return;
        }
        if(!$this->uppercase || !$this->lowercase || !$this->number || !$this->specialChars || strlen($this->client['password']) < 8) {
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
            }else{
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
            Client::create($clientValidated);
            $this->showAlert('Client added successfully.', 'success');
        }

        $this->resetForm();
        $this->resetPage();
        $this->mode = 'index';
    }

    public function delete($id)
    {
        $client = Client::findOrFail($id);
        if ($client->avatar && Storage::disk('public')->exists($client->avatar)) {
            Storage::disk('public')->delete($client->avatar);
        }
        $client->delete();

        $this->showAlert('Client deleted successfully.', 'success');
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
        $clients = Client::query()
            ->where('first_name', 'like', '%' . $this->search . '%')
            ->orWhere('last_name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orWhere('company_name', 'like', '%' . $this->search . '%')
            ->orWhere('phone', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.client', compact('clients'));
    }
}
