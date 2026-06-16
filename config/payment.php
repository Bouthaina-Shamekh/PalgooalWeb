<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Enabled
    |--------------------------------------------------------------------------
    |
    | Controls whether public-facing checkout flows accept payment attempts.
    |
    | When false:
    |   - CheckoutController::process()        → 503 / redirect with error
    |   - InvoiceCheckoutController::process() → redirect with error
    |
    | When false (does NOT block):
    |   - Admin bulk-mark-paid (InvoiceController) — admin bypass
    |   - DomainRenewalService auto-renew        — internal job bypass
    |
    | Set PAYMENT_GATEWAY_ENABLED=false in production until a real payment
    | gateway is integrated (ADR-007 Phase 5).
    |
    | Default: true — preserves existing behavior when .env flag is not set.
    |
    */
    'enabled' => (bool) env('PAYMENT_GATEWAY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | The key of the active gateway. Must exist in the `gateways` map below.
    | Controls which class PaymentManager::gateway() resolves.
    |
    | Current options:  'mock'
    | Phase 5 options:  'lahza', 'stripe', 'bank_transfer'
    |
    */
    'default_gateway' => env('PAYMENT_GATEWAY', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Gateway Class Map
    |--------------------------------------------------------------------------
    |
    | Maps gateway keys (PAYMENT_GATEWAY env value) to concrete implementation
    | classes. Each class must implement PaymentGatewayInterface.
    |
    | To add a new gateway in Phase 5:
    |   1. Create app/Payments/Gateways/LahzaGateway.php
    |      implementing App\Payments\Contracts\PaymentGatewayInterface.
    |   2. Add the mapping here.
    |   3. Set PAYMENT_GATEWAY=lahza in .env.
    |   No other files need changing.
    |
    */
    'gateways' => [
        'mock'   => \App\Payments\Gateways\MockGateway::class,
        // 'lahza'         => \App\Payments\Gateways\LahzaGateway::class,   // Phase 5
        // 'stripe'        => \App\Payments\Gateways\StripeGateway::class,  // Phase 5
        // 'bank_transfer' => \App\Payments\Gateways\BankTransferGateway::class, // Phase 5
    ],

];
