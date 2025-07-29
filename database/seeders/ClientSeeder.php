<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    public function run()
    {
        Client::create([
            'first_name'  => 'Mohammad',
            'last_name'   => 'Ali',
            'email'       => 'client@example.com',
            'password'    => Hash::make('12345678'),
            'company_name'=> 'Acme Co',
            'phone'       => '0501234567',
            'can_login'   => true,
            'avatar'   => 'images/clients/1.png',
        ]);
        Client::create([
            'first_name'  => 'hazem',
            'last_name'   => 'alyahya',
            'email'       => 'info@palgoals.com',
            'password'    => Hash::make('12345678'),
            'company_name'=> 'Acme Co',
            'phone'       => '0501234567',
            'can_login'   => true,
            'avatar'   => 'images/clients/1.png',
        ]);

    }
}
