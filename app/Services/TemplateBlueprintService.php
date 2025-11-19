<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TemplateBlueprintService
{
    public function seedSubscription(Subscription $subscription): string
    {
        $blueprint = $this->loadBlueprint($subscription);

        if (empty($blueprint['pages']) || ! is_array($blueprint['pages'])) {
            return 'No blueprint pages found.';
        }

        DB::transaction(function () use ($subscription, $blueprint) {
            $subscription->pages()->delete();

            foreach ($blueprint['pages'] as $pageData) {
                $page = $subscription->pages()->create([
                    'slug' => $pageData['slug'] ?? 'page-' . uniqid(),
                    'is_home' => (bool) ($pageData['is_home'] ?? false),
                    'is_active' => (bool) ($pageData['is_active'] ?? true),
                    'published_at' => $pageData['published_at'] ?? now(),
                ]);

                foreach ($pageData['translations'] ?? [] as $locale => $translation) {
                    $page->translations()->create([
                        'locale' => $locale,
                        'slug' => $translation['slug'] ?? $pageData['slug'] ?? $locale,
                        'title' => $translation['title'] ?? ucfirst($pageData['slug'] ?? 'Page'),
                        'content' => $translation['content'] ?? null,
                        'meta_title' => $translation['meta_title'] ?? null,
                        'meta_description' => $translation['meta_description'] ?? null,
                        'meta_keywords' => $translation['meta_keywords'] ?? null,
                        'og_image' => $translation['og_image'] ?? null,
                    ]);
                }

                foreach ($pageData['sections'] ?? [] as $sectionData) {
                    $section = $page->sections()->create([
                        'key' => $sectionData['key'] ?? null,
                        'sort_order' => $sectionData['sort_order'] ?? 0,
                    ]);

                    foreach ($sectionData['translations'] ?? [] as $locale => $sectionTranslation) {
                        $section->translations()->create([
                            'locale' => $locale,
                            'title' => $sectionTranslation['title'] ?? null,
                            'content' => $sectionTranslation['content'] ?? null,
                        ]);
                    }
                }
            }
        });

        return 'Blueprint cloned for subscription.';
    }

    protected function loadBlueprint(Subscription $subscription): array
    {
        $slug = $subscription->template?->slug;

        $paths = [];
        if ($slug) {
            $paths[] = resource_path("blueprints/{$slug}.php");
        }
        $paths[] = resource_path('blueprints/default.php');

        foreach ($paths as $path) {
            if ($path && File::exists($path)) {
                $data = include $path;
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        Log::warning('No blueprint file found for subscription', [
            'subscription_id' => $subscription->id,
            'template_slug' => $slug,
        ]);

        return [];
    }
}
