<?php

namespace App\Services\Tenancy;

use App\Models\Tenancy\Subscription;
use App\Models\Tenancy\TenantRuntimeMetric;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TenantRuntimeUsageRecorder
{
    protected static ?bool $metricsTableExists = null;

    /**
     * Persist an aggregated runtime usage hit without affecting page delivery.
     */
    public function record(
        Subscription $subscription,
        string $source,
        Model $page,
        string $path = '',
        ?string $locale = null
    ): void {
        $locale = $locale ?? app()->getLocale();
        $normalizedPath = $this->normalizePath($path);
        $resolvedSlug = $this->resolvePageSlug($page, $locale);
        $pageModel = class_basename($page);
        $pageId = $page->getKey();
        $bucketKey = hash('sha256', implode('|', [
            $subscription->id,
            $subscription->id,
            $source,
            $pageModel,
            $pageId,
            $locale,
            $normalizedPath,
        ]));
        $now = now();

        if (! $this->metricsTableExists()) {
            return;
        }

        try {
            $updated = TenantRuntimeMetric::query()
                ->where('bucket_key', $bucketKey)
                ->update([
                    'hits' => DB::raw('hits + 1'),
                    'last_seen_at' => $now,
                    'updated_at' => $now,
                    'resolved_slug' => $resolvedSlug,
                ]);

            if ($updated > 0) {
                return;
            }

            try {
                TenantRuntimeMetric::query()->create([
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $subscription->id,
                    'source' => $source,
                    'page_model' => $pageModel,
                    'page_id' => $pageId,
                    'path' => $normalizedPath,
                    'resolved_slug' => $resolvedSlug,
                    'locale' => $locale,
                    'bucket_key' => $bucketKey,
                    'hits' => 1,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                ]);
            } catch (QueryException $exception) {
                TenantRuntimeMetric::query()
                    ->where('bucket_key', $bucketKey)
                    ->update([
                        'hits' => DB::raw('hits + 1'),
                        'last_seen_at' => $now,
                        'updated_at' => $now,
                        'resolved_slug' => $resolvedSlug,
                    ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Failed to record tenant runtime metric.', [
                'subscription_id' => $subscription->id,
                'source' => $source,
                'path' => $normalizedPath,
                'page_model' => $pageModel,
                'page_id' => $pageId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function metricsTableExists(): bool
    {
        if (self::$metricsTableExists === null) {
            self::$metricsTableExists = Schema::hasTable('tenant_runtime_metrics');
        }

        return self::$metricsTableExists;
    }

    protected function normalizePath(string $path): string
    {
        $path = trim($path, '/');

        return $path !== '' ? $path : '/';
    }

    protected function resolvePageSlug(Model $page, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if (method_exists($page, 'translation')) {
            return $page->translation($locale)?->slug ?? $page->translation()?->slug;
        }

        $translations = $page->relationLoaded('translations')
            ? $page->getRelation('translations')
            : (method_exists($page, 'translations') ? $page->translations()->get() : collect());

        $translation = $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', config('app.fallback_locale', 'en'))
            ?? $translations->first();

        return $translation?->slug ?? ($page->slug ?? null);
    }
}
