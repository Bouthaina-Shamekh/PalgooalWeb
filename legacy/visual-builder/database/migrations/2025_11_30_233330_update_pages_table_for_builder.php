<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration prepares the `pages` table
     * for the new Page Builder system by:
     * - Adding a `context` column to distinguish between marketing and tenant pages.
     * - Adding an optional `subscription_id` to link tenant pages to a subscription.
     * - Adding an index to optimize queries by context + subscription.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Page context: defines where this page is used (marketing / tenant / later: distributor, etc.)
            $table->string('context')
                ->default('marketing')
                ->after('id'); // marketing | tenant | later: distributor...

            // Link the page to a specific subscription (for tenant/client pages in the future)
            $table->foreignId('subscription_id')
                ->nullable()
                ->after('context')
                ->constrained()
                ->nullOnDelete();

            // Composite index to speed up lookups by context and subscription
            $table->index(['context', 'subscription_id'], 'pages_context_subscription_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * This will drop the added index, foreign key and columns.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Drop the composite index (use the same name we gave it in `up`)
            $table->dropIndex('pages_context_subscription_index');

            // Drop the foreign key + column in a safe way
            $table->dropConstrainedForeignId('subscription_id');

            // Drop the context column
            $table->dropColumn('context');
        });
    }
};
