<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add soft-delete support to the template_reviews table.
     *
     * The TemplateReview model uses SoftDeletes, so a deleted_at column is
     * required. Without it, calling ->delete() on a review would throw a
     * QueryException because the column does not exist.
     */
    public function up(): void
    {
        Schema::table('template_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('template_reviews', 'deleted_at')) {
                $table->softDeletes()->after('approved');
            }
        });
    }

    /**
     * Remove the soft-delete column from template_reviews.
     */
    public function down(): void
    {
        Schema::table('template_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('template_reviews', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
