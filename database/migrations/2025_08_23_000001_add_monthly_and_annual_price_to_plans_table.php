<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('monthly_price_cents')->nullable()->after('price_cents');
            $table->unsignedInteger('annual_price_cents')->nullable()->after('monthly_price_cents');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['monthly_price_cents', 'annual_price_cents']);
        });
    }
};
