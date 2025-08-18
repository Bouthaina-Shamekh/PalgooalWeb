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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();          // كود القسيمة (فريد)
            $table->enum('discount_type', ['fixed', 'percent']); // نوع الخصم: مبلغ ثابت أو نسبة
            $table->decimal('discount_value', 10, 2);  // قيمة الخصم (مثلاً 50.00 أو 20.00%)
            $table->date('expires_at')->nullable();     // تاريخ انتهاء صلاحية القسيمة
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
