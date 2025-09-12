<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // عميل اختياري، ولو انحذف العميل نخلي القيمة NULL للحفاظ على السجل المحاسبي
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            $table->string('order_number')->unique();
            $table->enum('status', ['pending', 'active', 'cancelled', 'fraud'])->default('pending');
            $table->string('type')->nullable();   // نوع الطلب: domain / hosting / template / ...
            $table->text('notes')->nullable();

            $table->timestamps();

            // فهارس مفيدة
            $table->index(['client_id', 'status']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
