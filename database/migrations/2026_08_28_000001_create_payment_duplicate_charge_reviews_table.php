<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_duplicate_charge_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_attempt_id')
                ->constrained('payment_attempts')
                ->restrictOnDelete();
            $table->string('review_status', 32);
            $table->string('resolution', 32)->nullable();
            $table->boolean('needs_follow_up');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('reviewer_name');
            $table->timestamp('reviewed_at');
            $table->text('note')->nullable();
            $table->string('detection_classification', 64);
            $table->string('verification_result', 64)->nullable();
            $table->timestamp('verification_checked_at')->nullable();
            $table->json('evidence_snapshot')->nullable();
            $table->timestamps();

            $table->index(
                ['payment_attempt_id', 'reviewed_at'],
                'pdcr_attempt_reviewed_index',
            );
            $table->index(
                ['review_status', 'needs_follow_up', 'reviewed_at'],
                'pdcr_status_followup_reviewed_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_duplicate_charge_reviews');
    }
};
