<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('headers', function (Blueprint $table) {
            if (! Schema::hasColumn('headers', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }

            if (! Schema::hasColumn('headers', 'location_key')) {
                $table->string('location_key')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('headers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('location_key');
            }
        });

        $headers = DB::table('headers')->orderBy('id')->get();
        foreach ($headers as $header) {
            $baseSlug = Str::slug((string) ($header->name ?? 'menu'));
            if ($baseSlug === '') {
                $baseSlug = 'menu';
            }

            $slug = $baseSlug;
            $suffix = 2;

            while (
                DB::table('headers')
                    ->where('slug', $slug)
                    ->where('id', '!=', $header->id)
                    ->exists()
            ) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            DB::table('headers')
                ->where('id', $header->id)
                ->update([
                    'slug' => $slug,
                    'location_key' => $header->location_key ?: 'header_primary',
                    'is_active' => $header->is_active ?? true,
                ]);
        }

        Schema::table('headers', function (Blueprint $table) {
            $table->unique('slug', 'headers_slug_unique');
            $table->index('location_key', 'headers_location_key_index');
            $table->index('is_active', 'headers_is_active_index');
        });

        $duplicates = DB::table('header_item_translations')
            ->select('header_item_id', 'locale', DB::raw('MIN(id) AS keep_id'), DB::raw('COUNT(*) AS aggregate_count'))
            ->groupBy('header_item_id', 'locale')
            ->having('aggregate_count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('header_item_translations')
                ->where('header_item_id', $duplicate->header_item_id)
                ->where('locale', $duplicate->locale)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('header_item_translations', function (Blueprint $table) {
            $table->unique(['header_item_id', 'locale'], 'header_item_translations_item_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::table('header_item_translations', function (Blueprint $table) {
            $table->dropUnique('header_item_translations_item_locale_unique');
        });

        Schema::table('headers', function (Blueprint $table) {
            $table->dropUnique('headers_slug_unique');
            $table->dropIndex('headers_location_key_index');
            $table->dropIndex('headers_is_active_index');
            $table->dropColumn(['slug', 'location_key', 'is_active']);
        });
    }
};

