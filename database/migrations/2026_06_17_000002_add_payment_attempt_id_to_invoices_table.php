<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-007 Phase 2 — Add payment_attempt_id to invoices.
 *
 * Separate migration (must run AFTER 2026_06_17_000001_create_payment_attempts_table)
 * to avoid circular FK constraint:
 *   invoices → payment_attempts (this migration)
 *   payment_attempts → invoices (previous migration)
 *
 * This FK links a settled invoice to the PaymentAttempt that triggered settlement.
 * An invoice may have many PaymentAttempts (via payment_attempts.invoice_id),
 * but is linked to at most one "winning" attempt via this column.
 *
 * nullOnDelete: if the PaymentAttempt record is deleted, the invoice column
 * is nulled rather than deleting the invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_attempt_id')
                  ->nullable()
                  ->after('order_id');

            $table->foreign('payment_attempt_id')
                  ->references('id')
                  ->on('payment_attempts')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['payment_attempt_id']);
            $table->dropColumn('payment_attempt_id');
        });
    }
};
