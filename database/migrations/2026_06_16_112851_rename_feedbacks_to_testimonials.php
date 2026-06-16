<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * ADR-006 — Feedbacks vs Testimonials Naming Strategy
 *
 * Renames the following DB objects to match the PHP class names:
 *
 *   feedbacks                  → testimonials
 *   feedback_translations      → testimonial_translations
 *   feedback_translations.feedback_id  → testimonial_translations.testimonial_id
 *   feedback_translations.feedback     → testimonial_translations.text
 *
 * Execution order:
 *   1. Drop FK (feedback_id → feedbacks.id)
 *   2. Rename table feedback_translations → testimonial_translations
 *   3. Rename table feedbacks → testimonials
 *   4. Rename column feedback_id → testimonial_id
 *   5. Rename column feedback → text
 *   6. Recreate FK (testimonial_id → testimonials.id)
 *
 * The down() method reverses all steps in reverse order.
 *
 * Schema guards (hasTable / hasColumn) prevent failures on partially-applied
 * environments (e.g., staging that was already partially migrated).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Drop the old FK ────────────────────────────────────────────────
        // Guard: only attempt if the old table still exists (idempotency).
        if (Schema::hasTable('feedback_translations')) {
            Schema::table('feedback_translations', function (Blueprint $table) {
                // Laravel names FKs as table_column_foreign.
                // Drop by column name for portability.
                $table->dropForeign(['feedback_id']);
            });
        }

        // ── 2. Rename feedback_translations → testimonial_translations ────────
        if (Schema::hasTable('feedback_translations') && ! Schema::hasTable('testimonial_translations')) {
            Schema::rename('feedback_translations', 'testimonial_translations');
        }

        // ── 3. Rename feedbacks → testimonials ────────────────────────────────
        if (Schema::hasTable('feedbacks') && ! Schema::hasTable('testimonials')) {
            Schema::rename('feedbacks', 'testimonials');
        }

        // ── 4. Rename column feedback_id → testimonial_id ────────────────────
        if (Schema::hasTable('testimonial_translations') && Schema::hasColumn('testimonial_translations', 'feedback_id')) {
            Schema::table('testimonial_translations', function (Blueprint $table) {
                $table->renameColumn('feedback_id', 'testimonial_id');
            });
        }

        // ── 5. Rename column feedback → text ─────────────────────────────────
        if (Schema::hasTable('testimonial_translations') && Schema::hasColumn('testimonial_translations', 'feedback')) {
            Schema::table('testimonial_translations', function (Blueprint $table) {
                $table->renameColumn('feedback', 'text');
            });
        }

        // ── 6. Recreate FK testimonial_id → testimonials.id ──────────────────
        if (
            Schema::hasTable('testimonial_translations') &&
            Schema::hasColumn('testimonial_translations', 'testimonial_id') &&
            Schema::hasTable('testimonials')
        ) {
            Schema::table('testimonial_translations', function (Blueprint $table) {
                $table->foreign('testimonial_id')
                      ->references('id')
                      ->on('testimonials')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        // ── 1. Drop the new FK ────────────────────────────────────────────────
        if (Schema::hasTable('testimonial_translations')) {
            Schema::table('testimonial_translations', function (Blueprint $table) {
                $table->dropForeign(['testimonial_id']);
            });
        }

        // ── 2. Rename text → feedback ─────────────────────────────────────────
        if (Schema::hasTable('testimonial_translations') && Schema::hasColumn('testimonial_translations', 'text')) {
            Schema::table('testimonial_translations', function (Blueprint $table) {
                $table->renameColumn('text', 'feedback');
            });
        }

        // ── 3. Rename testimonial_id → feedback_id ────────────────────────────
        if (Schema::hasTable('testimonial_translations') && Schema::hasColumn('testimonial_translations', 'testimonial_id')) {
            Schema::table('testimonial_translations', function (Blueprint $table) {
                $table->renameColumn('testimonial_id', 'feedback_id');
            });
        }

        // ── 4. Rename testimonials → feedbacks ────────────────────────────────
        if (Schema::hasTable('testimonials') && ! Schema::hasTable('feedbacks')) {
            Schema::rename('testimonials', 'feedbacks');
        }

        // ── 5. Rename testimonial_translations → feedback_translations ────────
        if (Schema::hasTable('testimonial_translations') && ! Schema::hasTable('feedback_translations')) {
            Schema::rename('testimonial_translations', 'feedback_translations');
        }

        // ── 6. Recreate original FK feedback_id → feedbacks.id ───────────────
        if (
            Schema::hasTable('feedback_translations') &&
            Schema::hasColumn('feedback_translations', 'feedback_id') &&
            Schema::hasTable('feedbacks')
        ) {
            Schema::table('feedback_translations', function (Blueprint $table) {
                $table->foreign('feedback_id')
                      ->references('id')
                      ->on('feedbacks')
                      ->onDelete('cascade');
            });
        }
    }
};
