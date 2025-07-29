<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Client;
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
    ];

    public function mount()
    {
        $client = Client::findOrFail(Auth::guard('client')->user()->id);
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
            'client.password' => 'nullable|min:6',
            'client.phone' => 'required',
            // 'client.can_login' => 'boolean',
            'client.avatar' => 'nullable|image',
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
        $this->showAlert('Client updated successfully.', 'success');

    }


    public function render()
    {
        return view('livewire.account-clinet');
    }
}
