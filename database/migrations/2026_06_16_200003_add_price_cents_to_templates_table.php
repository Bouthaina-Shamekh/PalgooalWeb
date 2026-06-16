<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ADR-003 Phase 1 — Add integer-cents price columns to templates.
     * Old decimal columns (price, discount_price) are NOT dropped — no-drop policy.
     * Dual-write is implemented in TemplateController.
     */
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->unsignedBigInteger('price_cents')->nullable()->after('price')
                  ->comment('ADR-003: price in integer cents. Mirrors the decimal price column.');

            $table->unsignedBigInteger('discount_price_cents')->nullable()->after('discount_price')
                  ->comment('ADR-003: discount_price in integer cents. NULL = no discount.');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['price_cents', 'discount_price_cents']);
        });
    }
};
