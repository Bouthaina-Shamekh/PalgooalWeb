<?php

namespace App\Services\Templates;

use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Template;
use App\Models\Tenancy\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Clone a template blueprint into the canonical Page + Section system.
 *
 * Usage example:
 * $pages = app(\App\Services\Templates\TemplateCloner::class)->cloneToTenant(
 *     template: $subscription->template,
 *     tenant: $subscription,
 *     replaceExisting: true,
 * );
 */
class TemplateCloner
{
    /**
     * Clone the selected template into tenant-owned canonical pages.
     *
     * This service intentionally does not touch builder output, resolver logic,
     * or frontend rendering. It only creates Page + Section records.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Page>
     */
    public function cloneToTenant(Template|int $template, Subscription|int $tenant, bool $replaceExisting = false): Collection
    {
        $template = $template instanceof Template
            ? $template->loadMissing('translations')
            : Template::query()->with('translations')->findOrFail($template);

        $tenant = $tenant instanceof Subscription
            ? $tenant
            : Subscription::query()->findOrFail($tenant);

        $blueprint = $this->loadBlueprint($template);
        $pages = $blueprint['pages'] ?? null;

        if (! is_array($pages) || $pages === []) {
            throw new RuntimeException('No template pages were found for cloning.');
        }

        return DB::transaction(function () use ($pages, $tenant, $replaceExisting) {
            if ($replaceExisting) {
                Page::query()
                    ->where('tenant_id', $tenant->getKey())
                    ->delete();
            }

            $clonedPages = collect();
            $homeAssigned = false;

            foreach ($pages as $pageIndex => $pageData) {
                if (! is_array($pageData)) {
                    continue;
                }

                $sourceWantsHome = (bool) ($pageData['is_home'] ?? false);
                $shouldBeHome = $sourceWantsHome && ! $homeAssigned;

                if ($shouldBeHome) {
                    Page::query()
                        ->where('tenant_id', $tenant->getKey())
                        ->where('is_home', true)
                        ->update(['is_home' => false]);

                    $homeAssigned = true;
                }

                $page = Page::query()->create([
                    'context' => 'tenant',
                    'tenant_id' => $tenant->getKey(),
                    'builder_mode' => $pageData['builder_mode'] ?? 'sections',
                    'is_active' => (bool) ($pageData['is_active'] ?? true),
                    'is_home' => $shouldBeHome,
                    'published_at' => $pageData['published_at'] ?? now(),
                ]);

                foreach ($this->normalizePageTranslations($pageData) as $locale => $translation) {
                    $page->translations()->create([
                        'locale' => $locale,
                        'slug' => $this->makeUniqueSlug(
                            tenant: $tenant,
                            locale: $locale,
                            sourceSlug: $translation['slug'] ?? null,
                            fallback: $translation['title'] ?? ($pageData['slug'] ?? 'page-' . ($pageIndex + 1)),
                            isHome: $shouldBeHome,
                        ),
                        'title' => $translation['title'] ?? ucfirst((string) ($pageData['slug'] ?? 'Page')),
                        'content' => $this->normalizePageContent($translation['content'] ?? null),
                        'meta_title' => $translation['meta_title'] ?? null,
                        'meta_description' => $translation['meta_description'] ?? null,
                        'meta_keywords' => $translation['meta_keywords'] ?? null,
                        'og_image' => $translation['og_image'] ?? null,
                    ]);
                }

                foreach ($pageData['sections'] ?? [] as $sectionIndex => $sectionData) {
                    if (! is_array($sectionData)) {
                        continue;
                    }

                    $section = $page->sections()->create([
                        'tenant_id' => $tenant->getKey(),
                        'type' => (string) ($sectionData['type'] ?? $sectionData['key'] ?? 'generic'),
                        'variant' => $sectionData['variant'] ?? null,
                        'style' => is_array($sectionData['style'] ?? null) ? $sectionData['style'] : null,
                        'order' => (int) ($sectionData['order'] ?? $sectionData['sort_order'] ?? ($sectionIndex + 1)),
                        'is_active' => (bool) ($sectionData['is_active'] ?? true),
                    ]);

                    foreach ($this->normalizeSectionTranslations($sectionData) as $locale => $translation) {
                        $section->translations()->create([
                            'tenant_id' => $tenant->getKey(),
                            'locale' => $locale,
                            'title' => $translation['title'] ?? null,
                            'content' => $translation['content'] ?? null,
                        ]);
                    }
                }

                $clonedPages->push($page->load(['translations', 'sections.translations']));
            }

            return $clonedPages;
        });
    }

    protected function loadBlueprint(Template $template): array
    {
        foreach ($this->resolveBlueprintPaths($template) as $path) {
            if (! File::exists($path)) {
                continue;
            }

            $data = include $path;

            if (is_array($data)) {
                return $data;
            }
        }

        throw new RuntimeException(sprintf(
            'No blueprint file could be resolved for template [%s].',
            $template->getKey()
        ));
    }

    /**
     * @return array<int, string>
     */
    protected function resolveBlueprintPaths(Template $template): array
    {
        $template->loadMissing('translations');

        $translationSlugs = $template->translations
            ->pluck('slug')
            ->filter(fn ($slug) => filled($slug))
            ->map(fn ($slug) => trim((string) $slug, '/'))
            ->unique()
            ->values()
            ->all();

        $paths = array_map(
            fn ($slug) => resource_path("blueprints/{$slug}.php"),
            $translationSlugs
        );

        $paths[] = resource_path('blueprints/default.php');

        return array_values(array_unique($paths));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function normalizePageTranslations(array $pageData): array
    {
        $translations = $pageData['translations'] ?? [];

        if (! is_array($translations) || $translations === []) {
            $locale = (string) config('app.fallback_locale', 'en');

            return [
                $locale => [
                    'slug' => $pageData['slug'] ?? null,
                    'title' => $pageData['title'] ?? ucfirst((string) ($pageData['slug'] ?? 'Page')),
                    'content' => null,
                    'meta_title' => null,
                    'meta_description' => null,
                    'meta_keywords' => null,
                    'og_image' => null,
                ],
            ];
        }

        return $translations;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeSectionTranslations(array $sectionData): array
    {
        $translations = $sectionData['translations'] ?? [];

        if (! is_array($translations) || $translations === []) {
            return [
                (string) config('app.fallback_locale', 'en') => [
                    'title' => $sectionData['title'] ?? null,
                    'content' => $sectionData['content'] ?? null,
                ],
            ];
        }

        return $translations;
    }

    protected function normalizePageContent(mixed $content): ?string
    {
        if ($content === null) {
            return null;
        }

        if (is_string($content)) {
            return $content;
        }

        return json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }

    protected function makeUniqueSlug(
        Subscription $tenant,
        string $locale,
        ?string $sourceSlug,
        string $fallback,
        bool $isHome = false
    ): string {
        $baseSlug = $this->normalizeSlug(
            $sourceSlug ?: ($isHome ? 'home' : $fallback)
        );

        if (! $this->tenantSlugExists($tenant, $baseSlug, $locale) && ! $this->slugExists($baseSlug, $locale)) {
            return $baseSlug;
        }

        $tenantScopedBase = $this->normalizeSlug(sprintf('%s-tenant-%s', $baseSlug, $tenant->getKey()));
        $candidate = $tenantScopedBase;
        $suffix = 2;

        while ($this->tenantSlugExists($tenant, $candidate, $locale) || $this->slugExists($candidate, $locale)) {
            $candidate = $this->normalizeSlug(sprintf('%s-%d', $tenantScopedBase, $suffix));
            $suffix++;
        }

        return $candidate;
    }

    protected function slugExists(string $slug, string $locale): bool
    {
        return PageTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->exists();
    }

    protected function tenantSlugExists(Subscription $tenant, string $slug, string $locale): bool
    {
        return PageTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->whereHas('page', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->getKey());
            })
            ->exists();
    }

    protected function normalizeSlug(?string $value): string
    {
        $slug = trim((string) $value);
        $slug = preg_replace('/[\s_]+/u', '-', $slug) ?? $slug;
        $slug = trim((string) $slug, "-/ \t\n\r\0\x0B");

        return $slug !== '' ? $slug : 'page';
    }
}
