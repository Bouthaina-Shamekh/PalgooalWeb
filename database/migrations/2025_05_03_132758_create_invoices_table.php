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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // ربط بالاشتراك
            $table->foreignId('subscription_id')
                  ->constrained()
                  ->cascadeOnDelete();
            // المبلغ المستحق
            $table->decimal('amount', 10, 2);
            // تاريخ الاستحقاق والدفع
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            // حالة الفاتورة (e.g. pending, paid, overdue)
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
