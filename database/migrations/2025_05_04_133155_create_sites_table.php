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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            // علاقة بالمشتري/العميل
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            // علاقة بالدومين
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            // حالة التزويد (provisioning)
            $table->string('provisioning_status');
            // بيانات دخول cPanel
            $table->string('cpanel_username');
            $table->string('cpanel_password');
            $table->string('cpanel_url');
            // تاريخ اكتمال التزويد
            $table->timestamp('provisioned_at')->nullable();
            // created_at & updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
