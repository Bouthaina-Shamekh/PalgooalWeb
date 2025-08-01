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
        Schema::create('feedback_translations', function (Blueprint $table) {
            $table->id();
            $table->string('feedback');
            $table->string('locale');
            $table->string('name');
            $table->string('major');
            $table->foreignId('feedback_id')->constrained('feedbacks')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_translations');
    }
};
