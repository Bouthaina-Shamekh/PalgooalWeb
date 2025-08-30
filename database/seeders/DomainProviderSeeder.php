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
                'endpoint'  => null, // سيُختار الافتراضي من الخدمة
                'username'  => 'enom_test_user',
                'password'  => 'enom_test_pass',
                'api_token' => null,
                'is_active' => true,
            ]
        );

        // Namecheap Sandbox
        DomainProvider::updateOrCreate(
            ['type' => 'namecheap', 'mode' => 'test'],
            [
                'name'      => 'Namecheap Sandbox',
                'endpoint'  => 'https://api.sandbox.namecheap.com/xml.response',
                'username'  => 'namecheap_test_user',
                'password'  => 'namecheap_test_pass',
                'api_token' => 'namecheap_test_api_key',
                'is_active' => true,
            ]
        );

        // Cloudflare Test (API Token)
        DomainProvider::updateOrCreate(
            ['type' => 'cloudflare', 'mode' => 'test'],
            [
                'name'      => 'Cloudflare Test Provider',
                'endpoint'  => 'https://api.cloudflare.com/client/v4',
                'username'  => null, // غالباً Cloudflare يعتمد فقط API Token
                'password'  => null,
                'api_token' => 'cloudflare_test_token',
                'is_active' => true,
            ]
        );
    }
}
