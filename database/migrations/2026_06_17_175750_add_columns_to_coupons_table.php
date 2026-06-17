<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-008 Phase 1 — Coupon Foundation
 *
 * Adds missing columns to the `coupons` table:
 *   - max_uses          : cap total redemptions (null = unlimited)
 *   - used_count        : how many times this coupon has been redeemed
 *   - is_active         : soft on/off switch for the coupon
 *   - minimum_amount_cents : minimum cart subtotal (cents) required to use coupon
 *
 * Does NOT drop or rename any existing column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedInteger('max_uses')->nullable()->after('expires_at')
                  ->comment('Maximum total uses allowed (null = unlimited)');

            $table->unsignedInteger('used_count')->default(0)->after('max_uses')
                  ->comment('Number of times this coupon has been successfully applied');

            $table->boolean('is_active')->default(true)->after('used_count')
                  ->comment('When false the coupon is disabled regardless of other conditions');

            $table->unsignedBigInteger('minimum_amount_cents')->nullable()->after('is_active')
                  ->comment('Minimum cart subtotal in cents required to apply the coupon (null = no minimum)');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['max_uses', 'used_count', 'is_active', 'minimum_amount_cents']);
        });
    }
};
