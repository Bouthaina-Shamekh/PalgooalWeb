<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: page_builder_structures
     * - Holds the JSON structure returned by GrapesJS (components/project data)
     *   for each marketing page.
     */
    public function up(): void
    {
        Schema::create('page_builder_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->json('structure')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_builder_structures');
    }
};
