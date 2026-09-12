<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Invoice WhatsApp Delivery Attempt schema.
 *
 * Durable audit + idempotency record for every deliberate "send this
 * invoice PDF over WhatsApp" action. One row per attempt (including
 * confirmed-failed and indeterminate outcomes, not only successes) --
 * mirrors the existing App\Models\PaymentAttempt / App\Models\
 * DomainProvisioningAttempt precedent in shape and reasoning.
 *
 * invoice_id and requested_by_user_id are both nullable with nullOnDelete()
 * so this audit row survives deletion of the invoice or the requesting
 * admin user -- matching payment_attempts.invoice_id's own documented
 * "invoice soft-delete preserves attempt record" reasoning (see
 * 2026_06_17_000001_create_payment_attempts_table.php).
 *
 * No orchestration/job/sending logic is introduced by this migration --
 * schema only, per the approved Phase 4 design.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_whatsapp_delivery_attempts', function (Blueprint $table) {
            $table->id();

            // ── Owning invoice (nullable + nullOnDelete: audit survives invoice deletion) ──
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('invoices')
                ->nullOnDelete();

            // ── Idempotency token, mirrors domain_provisioning_attempts.attempt_uuid ──
            $table->uuid('attempt_uuid')->unique();

            // ── Recipient (raw snapshot at request time + the actual normalized value sent) ──
            $table->string('recipient_snapshot', 50);
            $table->string('normalized_recipient', 20);

            // ── Document identity (see Phase 4 design doc J: hash+metadata only, no bytes stored) ──
            $table->string('document_filename', 255);
            $table->string('document_hash', 64); // SHA-256 hex digest, fixed length
            $table->unsignedInteger('document_byte_length');

            // ── Provider + outcome ──
            $table->string('provider', 50);
            $table->string('provider_message_id', 255)->nullable();

            // ── Status machine: pending, processing, sent, confirmed_failed, indeterminate ──
            // See App\Models\InvoiceWhatsAppDeliveryAttempt for the constants.
            $table->string('status', 30)->default('pending');

            // ── Failure detail (sanitized -- never a token/header, see the model docblock) ──
            $table->string('failure_code', 100)->nullable();
            $table->text('failure_message')->nullable();

            // ── Safe provider response subset (never the access token -- see model docblock) ──
            $table->json('provider_response')->nullable();

            // ── Who requested this send (nullable: preserves a future automated-send path) ──
            $table->foreignId('requested_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // ── Lifecycle timestamps ──
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            // ── Indexes ──
            $table->index('status', 'invoice_whatsapp_delivery_attempts_status_idx');
            $table->index(
                ['invoice_id', 'normalized_recipient', 'status'],
                'invoice_whatsapp_delivery_attempts_claim_idx',
            );

            // No uniqueness constraint for "one in-flight attempt per invoice+recipient"
            // here -- MySQL has no partial/filtered unique index, so that protection is
            // implemented later at the transaction/locking level (mirrors how
            // invoices.payment_session_status / payment_session_attempt_id claiming
            // already works today, with the same limitation).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_whatsapp_delivery_attempts');
    }
};
