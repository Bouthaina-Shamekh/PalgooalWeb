<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_registration_claims', function (Blueprint $table) {
            $table->id();
            $table->string('domain_name_normalized');
            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->restrictOnDelete();
            $table->string('status');
            $table->timestamp('claimed_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(
                'domain_name_normalized',
                'domain_registration_claims_domain_unique'
            );
            $table->index('order_item_id', 'domain_registration_claims_order_item_idx');
            $table->index('status', 'domain_registration_claims_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_registration_claims');
    }
};
