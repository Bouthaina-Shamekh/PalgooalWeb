<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->string('featured_label')->nullable()->after('is_featured');
        });

        DB::table('plan_translations')
            ->select(['id', 'features'])
            ->orderBy('id')
            ->chunkById(100, function ($translations) {
                foreach ($translations as $translation) {
                    if ($translation->features === null) {
                        continue;
                    }

                    $decoded = json_decode($translation->features, true);
                    if (!is_array($decoded)) {
                        continue;
                    }

                    $normalized = [];
                    foreach ($decoded as $item) {
                        if (is_array($item)) {
                            $text = isset($item['text']) ? trim((string) $item['text']) : '';
                            if ($text === '') {
                                continue;
                            }
                            $availableRaw = $item['available'] ?? true;
                            $available = filter_var($availableRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                            $normalized[] = [
                                'text' => $text,
                                'available' => $available === null ? (bool) $availableRaw : (bool) $available,
                            ];
                        } else {
                            $text = trim((string) $item);
                            if ($text === '') {
                                continue;
                            }
                            $normalized[] = [
                                'text' => $text,
                                'available' => true,
                            ];
                        }
                    }

                    DB::table('plan_translations')
                        ->where('id', $translation->id)
                        ->update(['features' => json_encode($normalized)]);
                }
            });
    }

    public function down(): void
    {
        DB::table('plan_translations')
            ->select(['id', 'features'])
            ->orderBy('id')
            ->chunkById(100, function ($translations) {
                foreach ($translations as $translation) {
                    if ($translation->features === null) {
                        continue;
                    }

                    $decoded = json_decode($translation->features, true);
                    if (!is_array($decoded)) {
                        continue;
                    }

                    $normalized = [];
                    foreach ($decoded as $item) {
                        if (is_array($item)) {
                            $text = isset($item['text']) ? trim((string) $item['text']) : '';
                            if ($text === '') {
                                continue;
                            }
                            $normalized[] = $text;
                        } elseif (is_string($item)) {
                            $text = trim($item);
                            if ($text === '') {
                                continue;
                            }
                            $normalized[] = $text;
                        }
                    }

                    DB::table('plan_translations')
                        ->where('id', $translation->id)
                        ->update(['features' => json_encode($normalized)]);
                }
            });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'featured_label']);
        });
    }
};
