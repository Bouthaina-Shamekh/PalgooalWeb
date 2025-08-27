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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            // علاقة بالمستخدم (عميل)
            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();
            // علاقة بخطة الاستضافة
            $table->foreignId('plan_id')
                ->constrained('plans')
                ->restrictOnDelete();
            $table->enum('status', ['pending', 'active', 'suspended', 'cancelled'])->default('pending');
            $table->enum('billing_cycle', ['monthly', 'annually'])->default('annually');
            $table->decimal('price', 10, 2)->default(0); // السعر وقت الاشتراك
            $table->string('username')->nullable(); // اسم مستخدم الخدمة
            $table->unsignedBigInteger('server_id')->nullable(); // معرف السيرفر
            $table->date('next_due_date')->nullable(); // تاريخ الاستحقاق القادم
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->enum('domain_option', ['new', 'subdomain', 'existing'])
                ->default('subdomain')
                ->comment('new=register new domain, subdomain=our subdomain, existing=use client’s domain');
            $table->string('domain_name')
                ->nullable()
                ->comment('مثلاً “example.com” أو “client.palgoals.com” أو دومين العميل');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
