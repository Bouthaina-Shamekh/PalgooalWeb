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
        // إضافة plan_id إلى جدول templates
        Schema::table('templates', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
        });

        // احذف الجداول القديمة إن وجدت
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');

        // إنشاء جدول الفواتير
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('number')->unique(); // رقم الفاتورة
            $table->enum('status', ['draft', 'unpaid', 'paid', 'cancelled'])->default('draft');
            $table->integer('subtotal_cents')->default(0);
            $table->integer('discount_cents')->default(0);
            $table->integer('tax_cents')->default(0);
            $table->integer('total_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->timestamps();
        });

        // إنشاء جدول بنود الفواتير
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('item_type'); // product | domain | subscription
            $table->unsignedBigInteger('reference_id')->nullable(); // id الخدمة أو الدومين
            $table->string('description');
            $table->unsignedInteger('qty')->default(1);
            $table->integer('unit_price_cents')->default(0);
            $table->integer('total_cents')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف عمود plan_id عند التراجع
        Schema::table('templates', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
