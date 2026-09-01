<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_provisioning_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->restrictOnDelete();
            $table->foreignId('domain_id')
                ->nullable()
                ->constrained('domains')
                ->nullOnDelete();
            $table->foreignId('provider_id')
                ->constrained('domain_providers')
                ->restrictOnDelete();
            $table->uuid('attempt_uuid')->unique();
            $table->string('operation');
            $table->string('provider_type');
            $table->string('provider_mode');
            $table->string('status');
            $table->string('provider_reference')->nullable();
            $table->string('provider_domain_id')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index('order_item_id', 'domain_provisioning_attempts_order_item_idx');
            $table->index('provider_id', 'domain_provisioning_attempts_provider_idx');
            $table->index('status', 'domain_provisioning_attempts_status_idx');
            $table->index('started_at', 'domain_provisioning_attempts_started_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_provisioning_attempts');
    }
};
