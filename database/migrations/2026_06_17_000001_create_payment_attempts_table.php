<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-007 Phase 2 — Create payment_attempts table.
 *
 * Provides an audit trail for every payment interaction with any gateway.
 * In Phase 2, records are created by the webhook handler (Phase 3) or by
 * admin settlement actions. In Phase 1, settlement was anonymous
 * (no PaymentAttempt record); Phase 2 makes it optional and backward-compatible.
 *
 * FK strategy:
 *   invoice_id → invoices.id  nullOnDelete  (invoice soft-delete preserves attempt record)
 *   order_id   → orders.id    nullOnDelete  (order deletion clears FK, keeps record)
 *   client_id  → clients.id   nullOnDelete  (client deletion clears FK, keeps record)
 *
 * Note: invoices.payment_attempt_id FK is added in a SEPARATE migration
 * (2026_06_17_000002) to avoid circular FK constraint between invoices
 * and payment_attempts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();

            // ── Foreign keys ───────────────────────────────────────────────
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->foreign('invoice_id')
                  ->references('id')
                  ->on('invoices')
                  ->nullOnDelete();

            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->nullOnDelete();

            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id')
                  ->references('id')
                  ->on('clients')
                  ->nullOnDelete();

            // ── Gateway identification ─────────────────────────────────────
            // Matches the value returned by PaymentGatewayInterface::name()
            // e.g. 'mock_gateway', 'lahza', 'stripe', 'bank_transfer'
            $table->string('gateway', 50);

            // ── Idempotency ────────────────────────────────────────────────
            // UUID generated at Order creation. Prevents duplicate sessions
            // on browser back-button or network retry.
            $table->string('idempotency_key', 100)->unique();

            // ── Gateway-assigned identifiers ───────────────────────────────
            // gateway_session_id: set when createSession() returns (Phase 4+)
            // Used by webhook handler to look up the correct PaymentAttempt.
            $table->string('gateway_session_id', 255)->nullable()->index();

            // gateway_transaction_id: set when payment is confirmed (webhook)
            // Links this record to the gateway's own dashboard/API.
            $table->string('gateway_transaction_id', 255)->nullable()->index();

            // ── Amount (from gateway — validated against invoice.total_cents) ─
            $table->unsignedBigInteger('gateway_amount_cents')->nullable();
            $table->char('currency', 3)->default('USD');

            // ── Status machine ─────────────────────────────────────────────
            // initiated  → session created, client redirected to gateway
            // pending    → webhook received but not yet settled
            // succeeded  → markPaid() completed, invoice.status = paid
            // failed     → gateway declined or webhook reported failure
            // cancelled  → client cancelled on gateway page
            // refunded   → refund confirmed by gateway
            $table->string('status', 30)->default('initiated')->index();

            // Raw status string from gateway (for debugging provider-specific states)
            $table->string('gateway_status_raw', 100)->nullable();

            // Full gateway response payload (for audit / support queries)
            $table->json('gateway_response')->nullable();

            // ── Lifecycle timestamps ───────────────────────────────────────
            // webhook_verified_at: when verifyWebhook() passed signature check
            $table->timestamp('webhook_verified_at')->nullable();

            // settled_at: when InvoiceSettlementService::markPaid() completed
            $table->timestamp('settled_at')->nullable();

            // refunded_at: when refund was confirmed by gateway webhook/API
            $table->timestamp('refunded_at')->nullable();

            // ── Refund tracking ────────────────────────────────────────────
            // Accumulates partial refund amounts if multiple refunds are issued
            $table->unsignedBigInteger('refund_amount_cents')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
