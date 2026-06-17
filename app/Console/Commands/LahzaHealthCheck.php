<?php

namespace App\Console\Commands;

use App\Models\PaymentGateway;
use App\Payments\Gateways\LahzaGateway;
use App\Payments\PaymentManager;
use Illuminate\Console\Command;

/**
 * ADR-007 Phase 5B — Lahza Gateway Health Check
 *
 * Verifies that the Lahza gateway is correctly configured and resolvable.
 * Does NOT make any real API calls to Lahza.
 * Does NOT print API keys.
 *
 * Usage:
 *   php artisan lahza:health-check
 *
 * Exit codes:
 *   0 — all checks passed
 *   1 — one or more checks failed
 */
class LahzaHealthCheck extends Command
{
    protected $signature   = 'lahza:health-check';
    protected $description = 'Verify Lahza gateway configuration is complete and resolvable (no API calls made)';

    public function handle(PaymentManager $manager): int
    {
        $this->newLine();
        $this->line('<fg=cyan>─────────────────────────────────────────────</fg=cyan>');
        $this->line('<fg=cyan>  ADR-007 Phase 5B — Lahza Health Check</fg=cyan>');
        $this->line('<fg=cyan>─────────────────────────────────────────────</fg=cyan>');
        $this->newLine();

        $passed = 0;
        $failed = 0;

        // -----------------------------------------------------------------------
        // Check 1 — payment_gateways table exists
        // -----------------------------------------------------------------------
        try {
            $lahzaRow = PaymentGateway::where('driver', 'lahza')->first();
            $this->pass('payment_gateways table accessible');
            $passed++;
        } catch (\Throwable $e) {
            $this->failCheck('payment_gateways table not found — run: php artisan migrate');
            $this->warn('    ' . $e->getMessage());
            $failed++;

            // Cannot continue without the table
            return $this->summary($passed, $failed);
        }

        // -----------------------------------------------------------------------
        // Check 2 — Lahza row exists in DB
        // -----------------------------------------------------------------------
        if ($lahzaRow === null) {
            $this->failCheck('Lahza row missing — run: php artisan db:seed --class=PaymentGatewaySeeder');
            $failed++;
        } else {
            $this->pass('Lahza row found in payment_gateways (driver=lahza)');
            $passed++;
        }

        if ($lahzaRow === null) {
            return $this->summary($passed, $failed);
        }

        // -----------------------------------------------------------------------
        // Check 3 — Lahza is active
        // -----------------------------------------------------------------------
        if ($lahzaRow->is_active) {
            $this->pass('Lahza is_active = true');
            $passed++;
        } else {
            $this->warn('  ⚠  Lahza is_active = false — activate via Admin → Settings → بوابات الدفع');
            // Not a failure — gateway can exist but be inactive
        }

        // -----------------------------------------------------------------------
        // Check 4 — Mode
        // -----------------------------------------------------------------------
        $mode = $lahzaRow->mode;
        if ($mode === 'sandbox') {
            $this->pass('Mode = sandbox (safe for testing)');
            $passed++;
        } elseif ($mode === 'live') {
            $this->warn('  ⚠  Mode = live — real payments will be processed');
            $passed++;
        } else {
            $this->failCheck('Mode is unknown: ' . $mode);
            $failed++;
        }

        // -----------------------------------------------------------------------
        // Check 5 — secret_key configured
        // -----------------------------------------------------------------------
        // We check getRawOriginal() to detect whether a ciphertext exists
        // without ever printing or comparing the decrypted value.
        $hasSecretKey = (bool) $lahzaRow->getRawOriginal('secret_key');
        if ($hasSecretKey) {
            $this->pass('secret_key is configured (ciphertext present — value hidden)');
            $passed++;
        } else {
            $this->failCheck('secret_key is NOT configured — add it via Admin → Settings → بوابات الدفع → Lahza → تعديل');
            $failed++;
        }

        // -----------------------------------------------------------------------
        // Check 6 — webhook_secret configured
        // -----------------------------------------------------------------------
        $hasWebhookSecret = (bool) $lahzaRow->getRawOriginal('webhook_secret');
        if ($hasWebhookSecret) {
            $this->pass('webhook_secret is configured (ciphertext present — value hidden)');
            $passed++;
        } else {
            $this->warn('  ⚠  webhook_secret is NOT configured — webhooks cannot be verified');
            // Warn not fail — gateway can still create sessions
        }

        // -----------------------------------------------------------------------
        // Check 7 — LahzaGateway class is in config map
        // -----------------------------------------------------------------------
        $map   = config('payment.gateways', []);
        $class = $map['lahza'] ?? null;

        if ($class === LahzaGateway::class) {
            $this->pass('config/payment.php maps lahza → ' . LahzaGateway::class);
            $passed++;
        } else {
            $this->failCheck('lahza not found in config/payment.gateways — check config/payment.php');
            $failed++;
        }

        // -----------------------------------------------------------------------
        // Check 8 — LahzaGateway class exists
        // -----------------------------------------------------------------------
        if (class_exists(LahzaGateway::class)) {
            $this->pass('LahzaGateway class exists');
            $passed++;
        } else {
            $this->failCheck('LahzaGateway class does not exist — check app/Payments/Gateways/LahzaGateway.php');
            $failed++;
        }

        // -----------------------------------------------------------------------
        // Check 9 — Gateway resolves from PaymentManager (requires is_active=true)
        // -----------------------------------------------------------------------
        if ($lahzaRow->is_active) {
            try {
                $gateway = $manager->gateway();
                if ($gateway->name() === 'lahza') {
                    $this->pass('PaymentManager resolves gateway; name() = "lahza"');
                    $passed++;
                } else {
                    $this->failCheck('PaymentManager resolved wrong gateway: ' . $gateway->name());
                    $failed++;
                }
            } catch (\Throwable $e) {
                $this->failCheck('PaymentManager::gateway() threw: ' . $e->getMessage());
                $failed++;
            }
        } else {
            $this->line('  <fg=yellow>SKIP</fg=yellow>  Gateway resolution skipped (Lahza not active — another gateway may be active)');
        }

        // -----------------------------------------------------------------------
        // Check 10 — Webhook URL is routable
        // -----------------------------------------------------------------------
        try {
            $webhookUrl = route('payment.webhook', ['gateway' => 'lahza']);
            $this->pass('Webhook URL routable: ' . $webhookUrl);
            $passed++;
        } catch (\Throwable $e) {
            $this->failCheck('Webhook route "payment.webhook" not found — run: php artisan route:list | grep webhook');
            $failed++;
        }

        return $this->summary($passed, $failed);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function pass(string $message): void
    {
        $this->line('  <fg=green>✓</fg=green>  ' . $message);
    }

    private function failCheck(string $message): void
    {
        $this->line('  <fg=red>✗</fg=red>  ' . $message);
    }

    private function summary(int $passed, int $failed): int
    {
        $this->newLine();
        $this->line('<fg=cyan>─────────────────────────────────────────────</fg=cyan>');

        if ($failed === 0) {
            $this->line("<fg=green>  All {$passed} checks passed — Lahza gateway is correctly configured.</fg=green>");
        } else {
            $this->line("<fg=red>  {$failed} check(s) failed, {$passed} passed.</fg=red>");
            $this->line('  Fix the issues above, then re-run: php artisan lahza:health-check');
        }

        $this->line('<fg=cyan>─────────────────────────────────────────────</fg=cyan>');
        $this->newLine();

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
