<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-003 Phase 2 — subscriptions.price_cents
 *
 * Adds integer cents column alongside the existing decimal 'price' column.
 * No-drop policy: 'price' is NOT removed by this migration.
 * Dual-write begins immediately after this migration runs.
 * Backfill: php artisan adr003:backfill-subscription-prices
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('price_cents')->nullable()->after('price')
                ->comment('ADR-003: price in integer cents. NULL = not yet backfilled.');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('price_cents');
        });
    }
};
