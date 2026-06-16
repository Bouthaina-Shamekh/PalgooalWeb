<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

/**
 * ADR-007 Phase 5A — Seeds the default gateway rows.
 *
 * Run after migration:
 *   php artisan db:seed --class=PaymentGatewaySeeder
 *
 * Idempotent: uses updateOrCreate so safe to run multiple times.
 *
 * After adding Lahza credentials via the admin UI, run:
 *   php artisan settings:payments:activate lahza
 * or use the admin panel to activate.
 */
class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------------------------------------------------
        // Mock Gateway (default, active=true, mode=sandbox)
        // Used during development and testing. Active by default.
        // -----------------------------------------------------------------------
        PaymentGateway::updateOrCreate(
            ['driver' => 'mock'],
            [
                'name'           => 'Mock Gateway',
                'is_active'      => true,
                'mode'           => 'sandbox',
                'public_key'     => null,
                'secret_key'     => null,
                'webhook_secret' => null,
                'settings'       => null,
            ]
        );

        // -----------------------------------------------------------------------
        // Lahza (inactive, sandbox mode — ready for credentials to be added)
        // Activate via admin UI: Settings → Payments → Activate
        // -----------------------------------------------------------------------
        PaymentGateway::updateOrCreate(
            ['driver' => 'lahza'],
            [
                'name'           => 'Lahza',
                'is_active'      => false,
                'mode'           => 'sandbox',
                'public_key'     => null,
                'secret_key'     => null,
                'webhook_secret' => null,
                'settings'       => [
                    'webhook_ip_whitelist' => [],  // Add Lahza IPs here when confirmed
                    'hmac_algorithm'       => 'sha512',
                ],
            ]
        );

        $this->command->info('PaymentGatewaySeeder: 2 gateway rows seeded (mock=active, lahza=inactive).');
    }
}
