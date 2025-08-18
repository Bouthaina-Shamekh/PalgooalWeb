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
                  ->constrained()
                  ->cascadeOnDelete();
            // علاقة بالخطة
            $table->foreignId('plan_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->string('status');
            $table->date('start_date'); // active, canceled, pending, …
            $table->date('end_date')->nullable();
            $table->enum('domain_option', ['new','subdomain','existing'])
                  ->default('subdomain')
                  ->comment('new=register new domain, subdomain=our subdomain, existing=use client’s domain');
            // اسم الدومين/السب-دومين/الدومين الحالي
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
