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
            $table->text('password')->nullable();   // لتخزين القيم المشفّرة
            $table->text('api_token')->nullable();  // لتخزين القيم المشفّرة
            $table->boolean('is_active')->default(true)->index();
            $table->string('mode', 10)->default('live')->index(); // live/test
            $table->timestamps();

            // لو حابب تستخدم enum بدل string (في MySQL/MariaDB)
            // $table->enum('mode', ['live','test'])->default('live');
        });
    }
    public function down()
    {
        Schema::dropIfExists('domain_providers');
    }
};
