<?php

namespace App\Actions\Fortify;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input)
    {
        if(Config::get('fortify.guard') == 'client'){
            Validator::make($input, [
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique(Client::class),
                ],
            ])->validate();
            if(isset($input['avatar']) && $input['avatar'] != null){
                $avatar = $input['avatar'];
                $avatar = $avatar->store('avatars');
            }else{
                $avatar = null;
            }
            return Client::create([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'company_name' => isset($input['company_name']) ? $input['company_name'] : null,
                'phone' => isset($input['phone']) ? $input['phone'] : null,
                'zip_code' => isset($input['zip_code']) ? $input['zip_code'] : null,
                'avatar' => $avatar,
            ]);
        }
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
