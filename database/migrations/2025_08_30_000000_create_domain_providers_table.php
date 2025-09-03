<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('domain_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('type', 50)->index(); // enom, namecheap, cloudflare ...
            $table->string('endpoint', 191)->nullable();
            $table->string('username', 191)->nullable();

            // اعتماداً على المزود:
            $table->text('password')->nullable();    // Enom / Others
            $table->text('api_token')->nullable();   // Enom
            $table->text('api_key')->nullable();     // Namecheap / Cloudflare
            $table->string('client_ip', 45)->nullable(); // IPv4/IPv6 للـ Namecheap

            $table->boolean('is_active')->default(true)->index();
            $table->string('mode', 10)->default('live')->index(); // live/test
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('domain_providers');
    }
};
