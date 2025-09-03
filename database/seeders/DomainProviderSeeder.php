<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DomainProvider;

class DomainProviderSeeder extends Seeder
{
    public function run(): void
    {
        // Enom Test
        DomainProvider::updateOrCreate(
            ['type' => 'enom', 'mode' => 'test'],
            [
                'name'      => 'Enom Test Provider',
                'endpoint'  => null, // الافتراضي يختار من الخدمة
                'username'  => 'enom_test_user',
                'password'  => 'enom_test_pass',
                'api_token' => null,
                'api_key'   => null,
                'client_ip' => null,
                'is_active' => true,
            ]
        );

        // Namecheap Sandbox
        DomainProvider::updateOrCreate(
            ['type' => 'namecheap', 'mode' => 'test'],
            [
                'name'      => 'Namecheap Sandbox',
                'endpoint'  => 'https://api.sandbox.namecheap.com/xml.response',
                'username'  => 'sandbox_user',
                'password'  => null,
                'api_token' => null,
                'api_key'   => 'sandbox_api_key',
                'client_ip' => '127.0.0.1', // غيّرها للـ IP المبيّض في حسابك
                'is_active' => true,
            ]
        );

        // Cloudflare Test (API Token)
        DomainProvider::updateOrCreate(
            ['type' => 'cloudflare', 'mode' => 'test'],
            [
                'name'      => 'Cloudflare Test Provider',
                'endpoint'  => 'https://api.cloudflare.com/client/v4',
                'username'  => null,
                'password'  => null,
                'api_token' => 'cloudflare_test_token', // Cloudflare يعتمد Token
                'api_key'   => null,
                'client_ip' => null,
                'is_active' => true,
            ]
        );
    }
}
