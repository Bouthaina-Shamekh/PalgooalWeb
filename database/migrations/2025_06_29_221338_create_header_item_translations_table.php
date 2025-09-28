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
        Schema::create('header_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('header_item_id')->constrained()->onDelete('cascade');
            $table->string('locale'); // 'ar' أو 'en'
            $table->string('label');
            $table->string('url')->nullable(); // رابط خاص بكل ترجمة
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('header_item_translations');
    }
};
