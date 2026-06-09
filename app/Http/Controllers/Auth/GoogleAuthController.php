<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $fullName = $googleUser->getName();
        $nameParts = explode(' ', $fullName, 2);

        $client = Client::updateOrCreate(
    ['email' => $googleUser->getEmail()],
    [
        'first_name' => $nameParts[0] ?? 'Client',
        'last_name' => $nameParts[1] ?? '',
        'company_name' => $googleUser->getName() ?? 'Google User',
        'password' => bcrypt(Str::random(32)),
        'avatar' => $googleUser->getAvatar(),
        'can_login' => 1,
        'status' => 'active',
    ]
);

        Auth::guard('client')->login($client, true);

        return redirect('/client/home');
    }
}