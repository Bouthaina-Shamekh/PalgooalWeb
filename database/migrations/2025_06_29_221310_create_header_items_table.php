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
        Schema::create('header_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('header_id')->constrained()->onDelete('cascade');
            $table->string('type')->default('link'); // 'link' أو 'dropdown'
            $table->string('url')->nullable();
            $table->json('children')->nullable(); // روابط dropdown إن وجدت
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('header_items');
    }
};
