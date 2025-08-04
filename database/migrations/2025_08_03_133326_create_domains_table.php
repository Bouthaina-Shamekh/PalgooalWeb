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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            // علاقة بالمشتري/العميل
            $table->foreignId('client_id')
                  ->constrained()
                  ->cascadeOnDelete();
            // اسم الدومين
            $table->string('domain_name')->unique();
            // المسجل (Enom.com أو غيره)
            $table->string('registrar');
            // تواريخ التسجيل والتجديد
            $table->date('registration_date');
            $table->date('renewal_date')->nullable();
            // حالة الدومين (active, expired, pending, etc)
            $table->string('status');
            // created_at & updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
