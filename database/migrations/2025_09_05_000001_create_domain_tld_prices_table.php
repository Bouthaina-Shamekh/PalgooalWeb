<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('domain_tld_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('domain_tld_id')->index();
            $table->enum('action', ['register', 'renew', 'transfer', 'restore'])->index();
            $table->unsignedTinyInteger('years')->default(1)->index();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('sale', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['domain_tld_id', 'action', 'years']);
            $table->foreign('domain_tld_id')->references('id')->on('domain_tlds')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_tld_prices');
    }
};
