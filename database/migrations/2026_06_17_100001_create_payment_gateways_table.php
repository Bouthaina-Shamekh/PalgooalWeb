<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-007 Phase 5A — Gateway Configuration Management
 *
 * Creates the payment_gateways table.
 * API keys are stored encrypted at rest via Laravel's Crypt facade
 * (mapped through the `encrypted` cast on the PaymentGateway model).
 *
 * Only ONE row should be active at a time (enforced in application layer).
 * Multiple rows allow switching between gateways without data loss.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();

            // Human-readable name, e.g. "Lahza" / "Mock Gateway"
            $table->string('name');

            // Machine key matching config('payment.gateways') map, e.g. 'lahza', 'mock'
            $table->string('driver')->unique();

            // Whether this gateway is the active one for checkout
            $table->boolean('is_active')->default(false);

            // 'sandbox' or 'live'
            $table->enum('mode', ['sandbox', 'live'])->default('sandbox');

            // API credentials — stored encrypted at rest (see PaymentGateway model casts)
            $table->text('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->text('webhook_secret')->nullable();

            // Gateway-specific extra configuration (HMAC algorithm, IP whitelist, etc.)
            $table->json('settings')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
