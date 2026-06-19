<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-003 Phase 3 — Drop legacy decimal price columns.
 *
 * Prerequisites (must be confirmed before running):
 *   1. All rows in templates have price_cents IS NOT NULL (run adr003:backfill-template-prices)
 *   2. All rows in subscriptions have price_cents IS NOT NULL (run adr003:backfill-subscription-prices)
 *   3. All dual-write code removed from TemplateController, SubscriptionController, OrderActivationService
 *   4. All fallback branches removed from Template::resolvedPriceCents() and Subscription::resolvedPriceCents()
 *
 * Validation SQL (run before migrating):
 *   SELECT COUNT(*) FROM templates WHERE price_cents IS NULL;        -- must be 0
 *   SELECT COUNT(*) FROM templates WHERE discount_price_cents IS NULL AND discount_price IS NOT NULL; -- must be 0
 *   SELECT COUNT(*) FROM subscriptions WHERE price_cents IS NULL;    -- must be 0
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $columns = array_filter(
                ['price', 'discount_price'],
                fn($col) => Schema::hasColumn('templates', $col)
            );
            if ($columns) {
                $table->dropColumn(array_values($columns));
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'price')) {
                $table->dropColumn('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('id');
            $table->decimal('discount_price', 10, 2)->nullable()->after('price');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('plan_id');
        });
    }
};
