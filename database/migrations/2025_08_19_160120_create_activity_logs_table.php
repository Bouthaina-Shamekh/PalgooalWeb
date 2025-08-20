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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('actor_type', ['admin', 'client', 'system'])->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action'); // 'client.created', 'service.suspended', etc.
            $table->json('meta')->nullable(); // تفاصيل إضافية
            $table->timestamps();

            // فهارس للبحث السريع
            $table->index(['actor_type', 'actor_id']);
            $table->index(['action']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
