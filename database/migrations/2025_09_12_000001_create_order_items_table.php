<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // 191 لطول آمن مع utf8mb4 والفهارس
            $table->string('domain', 191)->nullable();       // example.com
            $table->string('item_option')->nullable();       // register | transfer | subdomain | existing ...
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->json('meta')->nullable();

            $table->timestamps();

            // فهارس وقيود
            // يمنع تكرار نفس الدومين داخل نفس الطلب (يسمح بـ NULL عدة مرات)
            $table->unique(['order_id', 'domain'], 'order_items_order_id_domain_unique');

            // فهرس للبحث بالدومين عبر جميع الطلبات (اختياري لكنه مفيد)
            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
