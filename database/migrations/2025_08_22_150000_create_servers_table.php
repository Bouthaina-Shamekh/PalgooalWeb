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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم السيرفر
            $table->string('type')->default('cpanel'); // نوع اللوحة: cpanel, directadmin, ...
            $table->string('ip')->nullable(); // عنوان السيرفر
            $table->string('hostname')->nullable();
            $table->string('username')->nullable();
            // password may be long (store as text)
            $table->text('password')->nullable();
            // api_token can be long (store as text)
            $table->text('api_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
