<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('domain_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // enom, namecheap, ...
            $table->string('endpoint')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->text('api_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('mode')->default('live')->after('api_token'); // live أو test
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('domain_providers');
    }
};
