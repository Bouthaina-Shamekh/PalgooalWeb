<?php

use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle inbound payment gateway webhook callbacks.
|
| Design requirements (ADR-007 Phase 3):
|   - No CSRF verification  — gateway servers POST without a CSRF token
|   - No session middleware — webhook handler is fully stateless
|   - No auth middleware    — the calling party is a gateway server, not a user
|
| Registration: bootstrap/app.php `then:` callback — keeps these routes
| outside the `web` middleware group so none of the above apply.
|
| Only a `throttle:60,1` rate-limit is applied to prevent abuse.
|
| The {gateway} segment must match the value of PAYMENT_GATEWAY in .env
| (e.g. "mock", "lahza", "stripe"). Mismatches return 404.
|
*/

Route::post('/payment/webhook/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->name('payment.webhook');
