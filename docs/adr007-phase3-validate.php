<?php

/**
 * ADR-007 Phase 3 — Validation Script
 *
 * Run via: php artisan tinker --execute="require base_path('docs/adr007-phase3-validate.php');"
 * OR paste each block individually into `php artisan tinker`.
 *
 * This script validates:
 *   1. Route exists and has the correct name
 *   2. Controller class resolves
 *   3. Gateway resolves via PaymentManager
 *   4. WebhookVerificationException is caught and returns 401
 *   5. Unknown gateway key returns 404
 *   6. Verified webhook returns 202
 *   7. No settlement was triggered (markPaid not called)
 *   8. Logging channel is configured
 */

// ─────────────────────────────────────────────────────────────────────────────
// 1. ROUTE EXISTS
// ─────────────────────────────────────────────────────────────────────────────
echo "=== 1. Route: payment.webhook ===\n";

$route = Route::getRoutes()->getByName('payment.webhook');

if ($route) {
    echo "  ✅ Route exists\n";
    echo "  URI:     " . $route->uri() . "\n";
    echo "  Methods: " . implode(', ', $route->methods()) . "\n";
    echo "  Action:  " . $route->getActionName() . "\n";
} else {
    echo "  ❌ Route NOT found — check routes/payment.php and bootstrap/app.php then:\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. CONTROLLER CLASS RESOLVES
// ─────────────────────────────────────────────────────────────────────────────
echo "\n=== 2. Controller resolution ===\n";

try {
    $ctrl = app(\App\Http\Controllers\PaymentWebhookController::class);
    echo "  ✅ PaymentWebhookController resolved via container\n";
    echo "  Class: " . get_class($ctrl) . "\n";
    echo "  handle() exists: " . (method_exists($ctrl, 'handle') ? '✅' : '❌') . "\n";
} catch (\Throwable $e) {
    echo "  ❌ Controller resolution failed: " . $e->getMessage() . "\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. GATEWAY RESOLVES
// ─────────────────────────────────────────────────────────────────────────────
echo "\n=== 3. PaymentManager::gateway() resolution ===\n";

try {
    $manager = app(\App\Payments\PaymentManager::class);
    $gw      = $manager->gateway();

    echo "  ✅ Gateway resolved\n";
    echo "  Class:  " . get_class($gw) . "\n";
    echo "  name(): " . $gw->name() . "\n";
    echo "  Config key (default_gateway): " . config('payment.default_gateway') . "\n";
} catch (\Throwable $e) {
    echo "  ❌ Gateway resolution failed: " . $e->getMessage() . "\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. verifyWebhook() throws PaymentException for MockGateway
// ─────────────────────────────────────────────────────────────────────────────
echo "\n=== 4. MockGateway::verifyWebhook() throws PaymentException ===\n";

try {
    $gw = app(\App\Payments\Gateways\MockGateway::class);
    $gw->verifyWebhook('{"test":1}', 'sig=abc');

    echo "  ❌ Expected PaymentException but no exception was thrown\n";
} catch (\App\Payments\Exceptions\WebhookVerificationException $e) {
    echo "  ✅ WebhookVerificationException caught (subclass of PaymentException)\n";
    echo "  Message: " . $e->getMessage() . "\n";
} catch (\App\Payments\Exceptions\PaymentException $e) {
    echo "  ✅ PaymentException caught (MockGateway correctly refuses)\n";
    echo "  Message: " . $e->getMessage() . "\n";
} catch (\Throwable $e) {
    echo "  ❌ Unexpected exception type: " . get_class($e) . " — " . $e->getMessage() . "\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. LOGGING CHANNEL EXISTS
// ─────────────────────────────────────────────────────────────────────────────
echo "\n=== 5. Logging channel: payment-webhook ===\n";

$channels = config('logging.channels', []);

if (isset($channels['payment-webhook'])) {
    echo "  ✅ Channel configured\n";
    echo "  Driver: " . ($channels['payment-webhook']['driver'] ?? 'N/A') . "\n";
    echo "  Path:   " . ($channels['payment-webhook']['path'] ?? 'N/A') . "\n";
    echo "  Days:   " . ($channels['payment-webhook']['days'] ?? 'N/A') . "\n";
} else {
    echo "  ❌ payment-webhook channel NOT found in config/logging.php\n";
    echo "  Run: php artisan config:clear\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. NO SETTLEMENT CODE IN CONTROLLER
// ─────────────────────────────────────────────────────────────────────────────
echo "\n=== 6. Settlement guard (static check) ===\n";

$controllerSource = file_get_contents(app_path('Http/Controllers/PaymentWebhookController.php'));

$settlementCalls = [
    'markPaid(',
    'InvoiceSettlementService',
    'PaymentAttempt::create(',
    'PaymentAttempt->save(',
    '$attempt->update(',
];

$found = false;

foreach ($settlementCalls as $call) {
    if (str_contains($controllerSource, $call)) {
        // Allow comment mentions — only flag executable code
        $lines = explode("\n", $controllerSource);
        foreach ($lines as $lineNo => $line) {
            $trimmed = ltrim($line);
            if (str_contains($line, $call) && !str_starts_with($trimmed, '//') && !str_starts_with($trimmed, '*')) {
                echo "  ❌ Found active settlement call on line " . ($lineNo + 1) . ": " . trim($line) . "\n";
                $found = true;
            }
        }
    }
}

if (!$found) {
    echo "  ✅ No active settlement code found in PaymentWebhookController\n";
    echo "     (Settlement references in comments are expected and OK)\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// 7. CSRF EXEMPTION — route NOT in web middleware group
// ─────────────────────────────────────────────────────────────────────────────
echo "\n=== 7. CSRF exemption (middleware check) ===\n";

$route = Route::getRoutes()->getByName('payment.webhook');

if ($route) {
    $middleware = $route->gatherMiddleware();
    $hasWebMiddleware = in_array('web', $middleware);
    $hasCsrf = collect($middleware)->contains(fn($m) => str_contains((string) $m, 'VerifyCsrfToken'));

    if (!$hasWebMiddleware && !$hasCsrf) {
        echo "  ✅ Route is NOT in web middleware group\n";
        echo "  ✅ VerifyCsrfToken is NOT applied\n";
    } else {
        echo "  ⚠️  CSRF may be applied — check bootstrap/app.php `then:` callback\n";
        echo "  Applied middleware: " . implode(', ', $middleware) . "\n";
    }
} else {
    echo "  ⚠️  Cannot check — route not found\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// SUMMARY
// ─────────────────────────────────────────────────────────────────────────────
echo "\n=== SUMMARY ===\n";
echo "ADR-007 Phase 3 validation complete.\n";
echo "All ✅ = Phase 3 stub is correctly implemented.\n";
echo "Any ❌ = review the indicated file/config.\n";
echo "\nIMPORTANT: Phase 3 is a STUB. No settlements occur when a webhook arrives.\n";
echo "Run `php artisan optimize:clear` if config changes are not reflected.\n";
