<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores lightweight aggregated tenant runtime usage for the canonical
     * Page + Section tenant site.
     */
    public function up(): void
    {
        Schema::create('tenant_runtime_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')
                ->constrained('subscriptions')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('source', 32)->index();
            $table->string('page_model', 64);
            $table->unsignedBigInteger('page_id')->nullable();
            $table->string('path', 191)->default('/');
            $table->string('resolved_slug', 191)->nullable();
            $table->string('locale', 12)->nullable();
            $table->string('bucket_key', 64)->unique();
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'source'], 'tenant_runtime_metrics_tenant_source_index');
            $table->index(['tenant_id', 'page_model', 'page_id'], 'tenant_runtime_metrics_page_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_runtime_metrics');
    }
};
