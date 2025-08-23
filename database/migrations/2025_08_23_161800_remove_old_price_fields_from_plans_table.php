<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['price_cents', 'billing_cycle']);
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('price_cents');
            $table->enum('billing_cycle', ['monthly','annually'])->default('annually');
        });
    }
};
