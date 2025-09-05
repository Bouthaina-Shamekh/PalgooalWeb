<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('domain_tlds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id')->index();
            $table->string('provider', 50)->index();
            $table->string('tld', 63)->index();
            $table->char('currency', 3)->default('USD');
            $table->boolean('enabled')->default(true);
            $table->boolean('supports_premium')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'tld']);
            $table->foreign('provider_id')->references('id')->on('domain_providers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_tlds');
    }
};
