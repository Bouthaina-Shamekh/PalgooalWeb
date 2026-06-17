<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-008 Phase 1 — Coupon Foundation
 *
 * Adds `coupon_id` FK to `invoices` so we can trace which coupon
 * was applied when generating a particular invoice.
 *
 * - Nullable: invoices without a coupon remain unaffected.
 * - nullOnDelete: if the coupon row is deleted the FK is set to null
 *   (audit trail preserved via discount_cents which already exists).
 *
 * Does NOT modify `discount_cents`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('coupon_id')
                  ->nullable()
                  ->after('payment_attempt_id')
                  ->constrained('coupons')
                  ->nullOnDelete()
                  ->comment('ADR-008: coupon applied when this invoice was created');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Coupon::class);
        });
    }
};
