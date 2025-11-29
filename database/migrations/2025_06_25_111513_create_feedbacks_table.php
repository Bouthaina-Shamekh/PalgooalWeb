<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();

            // علاقة مع media
            $table->foreignId('image_id')
                ->nullable()
                ->constrained('media')
                ->nullOnDelete();

            $table->integer('star')->nullable();
            $table->integer('order')->default(0);

            // ندمج is_approved هنا
            $table->boolean('is_approved')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
