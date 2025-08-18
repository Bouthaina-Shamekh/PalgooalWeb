<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // اسم الخطة
            $table->string('slug')->unique();           // basic / pro...
            $table->unsignedInteger('price_cents');           // السعر (مثلاً 99.99)
            $table->enum('billing_cycle', ['monthly','annually'])->default('annually');
            $table->json('features')->nullable();       // مساحة/نقل/بريد.. (JSON)
            $table->boolean('is_active')->default(true);
            // تتبّع
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
