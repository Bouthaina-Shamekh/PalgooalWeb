<?php

namespace App\Support\Sections;

use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\Template;
use App\Models\Testimonial;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Primary runtime resolver for dynamic section payloads.
 *
 * Architectural note:
 * - This is the active resolver used by the current frontend rendering
 *   paths (`front.pages.page` and legacy section rendering).
 * - All active resolver work belongs here.
 * - Legacy builder type aliases are normalized into canonical types
 *   before reaching this resolver for backward-safe rendering.
 */
class SectionQueryResolver
{
    /**
     * Resolve section data by type.
     * - Keeps static fields as-is
     * - Injects dynamic DB payload when needed
     */
    public static function resolve(?string $type, array $data): array
    {
        $type = is_string($type) ? strtolower(trim($type)) : null;

        return match ($type) {
            'services' => self::services($data),
            'testimonials' => self::testimonials($data),
            'reviews_showcase' => self::testimonials($data),
            'our_work_showcase' => self::portfolios($data),
            'portfolio_slider' => self::portfolioShowcase($data),
            'portfolio_showcase' => self::portfolioShowcase($data),
            'hosting_pricing_showcase' => self::hostingPricingShowcase($data),
            'templates_slider_showcase' => self::templatesSliderShowcase($data),
            'templates_showcase' => self::templatesSliderShowcase($data),
            'templates_listing_showcase' => self::templatesListingShowcase($data),
            'search-domain' => self::searchDomain($data),
            default => $data,
        };
    }

    protected static function services(array $data): array
    {
        $limit = isset($data['limit']) && is_numeric($data['limit']) ? (int) $data['limit'] : null;
        if ($limit !== null && $limit <= 0) $limit = null;

        $order = isset($data['order']) ? strtolower((string) $data['order']) : 'order';
        $order = in_array($order, ['order', 'latest'], true) ? $order : 'order';

        $q = Service::query()->with('translations');

        if ($order === 'latest') {
            $q->latest();
        } else {
            $q->orderBy('order');
        }

        if ($limit) $q->limit($limit);

        $data['services'] = $q->get();

        return $data;
    }

    protected static function testimonials(array $data): array
    {
        $limit = isset($data['limit']) && is_numeric($data['limit']) ? (int) $data['limit'] : null;
        if ($limit !== null && $limit <= 0) $limit = null;

        $q = Testimonial::approved()->with(['translations', 'image'])->orderBy('order');

        if ($limit) $q->limit($limit);

        $data['testimonials'] = $q->get();

        return $data;
    }

    protected static function portfolios(array $data): array
    {
        $limit = isset($data['limit']) && is_numeric($data['limit']) ? (int) $data['limit'] : null;
        if ($limit !== null && $limit <= 0) $limit = null;

        $q = Portfolio::query()
            ->with('translations')
            ->orderBy('order')
            ->orderByDesc('id');

        if ($limit) $q->limit($limit);

        $data['portfolios'] = $q->get();

        return $data;
    }

    protected static function portfolioShowcase(array $data): array
    {
        $limit = isset($data['limit']) && is_numeric($data['limit']) ? (int) $data['limit'] : 8;

        if ($limit <= 0) {
            $limit = 8;
        }

        $data['button_label'] = trim((string) ($data['button_label'] ?? '')) ?: __('Visit');
        $data['limit'] = $limit;

        if (self::hasPortfolioPayload($data)) {
            return $data;
        }

        $showFeaturedOnly = filter_var($data['show_featured_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $categoryId = isset($data['category_id']) && is_numeric($data['category_id'])
            ? (int) $data['category_id']
            : null;

        $activeColumn = self::firstExistingColumn('portfolios', ['is_active', 'active']);
        $featuredColumn = self::firstExistingColumn('portfolios', ['is_featured', 'featured']);
        $categoryColumn = self::firstExistingColumn('portfolios', [
            'category_id',
            'portfolio_category_id',
            'category_portfolio_id',
        ]);

        $query = Portfolio::query()
            ->with(self::portfolioRelations())
            ->when($activeColumn !== null, function ($query) use ($activeColumn) {
                $query->where($activeColumn, true);
            })
            ->when($showFeaturedOnly && $featuredColumn !== null, function ($query) use ($featuredColumn) {
                $query->where($featuredColumn, true);
            })
            ->when($categoryId && $categoryColumn !== null, function ($query) use ($categoryColumn, $categoryId) {
                $query->where($categoryColumn, $categoryId);
            })
            ->when(Schema::hasColumn('portfolios', 'order'), function ($query) {
                $query->orderBy('order');
            }, function ($query) {
                $query->latest('id');
            })
            ->limit($limit);

        $buttonLabel = $data['button_label'];
        $data['portfolio_items'] = $query->get()
            ->map(fn ($portfolio): array => self::portfolioItemPayload($portfolio, $buttonLabel))
            ->values();

        return $data;
    }

    protected static function hostingPricingShowcase(array $data): array
    {
        $buttonLabel = trim((string) ($data['button_label'] ?? ''));
        if ($buttonLabel === '') {
            $buttonLabel = __('Choose Now');
        }

        $visibleCategoryIds = collect($data['visible_category_ids'] ?? [])
            ->map(function ($id) {
                if (is_array($id)) {
                    return null;
                }

                $id = is_string($id) ? trim($id) : $id;

                return is_numeric($id) ? (int) $id : null;
            })
            ->filter(fn ($id) => $id && $id > 0)
            ->values()
            ->all();

        $hostingPlansExist = Plan::query()
            ->where('is_active', true)
            ->where('plan_type', Plan::TYPE_HOSTING)
            ->exists();

        $categories = PlanCategory::query()
            ->active()
            ->when($visibleCategoryIds !== [], function ($query) use ($visibleCategoryIds) {
                $query->whereIn('id', $visibleCategoryIds);
            })
            ->ordered()
            ->with([
                'translations',
                'plans' => function ($query) use ($hostingPlansExist) {
                    $query->active()
                        ->when($hostingPlansExist, function ($plansQuery) {
                            $plansQuery->where('plan_type', Plan::TYPE_HOSTING);
                        })
                        ->with('translations')
                        ->orderBy('id');
                },
            ])
            ->get()
            ->filter(fn ($category) => $category->plans->isNotEmpty())
            ->values();

        $data['button_label'] = $buttonLabel;
        $data['visible_category_ids'] = $visibleCategoryIds;
        $data['plan_categories'] = $categories;

        return $data;
    }

    protected static function searchDomain(array $data): array
    {
        $defaultTlds = DomainTld::where('in_catalog', true)
            ->orderBy('tld')
            ->pluck('tld')
            ->map(fn($t) => strtolower(ltrim($t, '.')))
            ->values()
            ->all();

        $fallbackPrices = DomainTldPrice::with('tld')
            ->whereIn(
                'domain_tld_id',
                DomainTld::where('in_catalog', true)->pluck('id')
            )
            ->where('action', 'register')
            ->where('years', 1)
            ->get()
            ->mapWithKeys(function ($p) {
                $tld = strtolower($p->tld->tld ?? '');
                if ($tld === '') return [];
                $price = $p->sale ?? $p->cost;
                return $price !== null ? [$tld => (float) $price] : [];
            })
            ->toArray();

        $data['default_tlds'] = $data['default_tlds'] ?? $defaultTlds;
        $data['fallback_prices'] = $data['fallback_prices'] ?? $fallbackPrices;
        $data['currency'] = $data['currency'] ?? 'USD';

        return $data;
    }

    protected static function templatesSliderShowcase(array $data): array
    {
        $limit = isset($data['limit']) && is_numeric($data['limit']) ? (int) $data['limit'] : 8;

        if ($limit <= 0) {
            $limit = 8;
        }

        $data['buy_label'] = trim((string) ($data['buy_label'] ?? '')) ?: __('Buy Now');
        $data['preview_label'] = trim((string) ($data['preview_label'] ?? '')) ?: __('Live Preview');
        $data['limit'] = $limit;

        if (! self::hasTemplatePayload($data)) {
            $data['templates'] = self::templateQuery()
                ->latest('id')
                ->limit($limit)
                ->get();
        }

        return $data;
    }

    protected static function templatesListingShowcase(array $data): array
    {
        $itemsPerPage = isset($data['items_per_page']) && is_numeric($data['items_per_page'])
            ? (int) $data['items_per_page']
            : 12;

        if ($itemsPerPage <= 0) {
            $itemsPerPage = 12;
        }

        $data['breadcrumb_label'] = trim((string) ($data['breadcrumb_label'] ?? '')) ?: __('Templates');
        $data['title'] = trim((string) ($data['title'] ?? '')) ?: __('TEMPLATE');
        $data['description'] = trim((string) ($data['description'] ?? '')) ?: __('Choose from a range of templates and publish them instantly');
        $data['all_categories_label'] = trim((string) ($data['all_categories_label'] ?? '')) ?: __('All Hosting');
        $data['type_label'] = trim((string) ($data['type_label'] ?? '')) ?: __('Type');
        $data['best_sellers_label'] = trim((string) ($data['best_sellers_label'] ?? '')) ?: __('Best Sellers');
        $data['price_label'] = trim((string) ($data['price_label'] ?? '')) ?: __('Price');
        $data['buy_label'] = trim((string) ($data['buy_label'] ?? '')) ?: __('Buy Now');
        $data['preview_label'] = trim((string) ($data['preview_label'] ?? '')) ?: __('Live Preview');
        $data['items_per_page'] = $itemsPerPage;

        if (! self::hasTemplatePayload($data)) {
            $data['templates'] = self::templateQuery()
                ->latest('id')
                ->get();
        }

        return $data;
    }

    protected static function portfolioItemPayload(Portfolio $portfolio, string $buttonLabel): array
    {
        $translations = collect($portfolio->translations ?? []);
        $translation = method_exists($portfolio, 'translation')
            ? ($portfolio->translation(app()->getLocale()) ?? $translations->first())
            : ($translations->firstWhere('locale', app()->getLocale()) ?? $translations->first());

        $title = trim((string) ($translation?->title ?? ''));
        $subtitle = trim((string) ($translation?->type ?? ''));
        $externalUrl = self::portfolioExternalUrl($translation?->link ?? null);

        if ($title === '') {
            $title = __('Project');
        }

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'image' => self::portfolioImageUrl($portfolio->default_image ?? null),
            'url' => $externalUrl !== ''
                ? $externalUrl
                : self::portfolioUrl($portfolio),
            'button_label' => $buttonLabel,
        ];
    }

    protected static function portfolioExternalUrl($value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        $value = trim($value);

        if (
            filter_var($value, FILTER_VALIDATE_URL)
            || str_starts_with($value, '//')
            || str_starts_with($value, '/')
        ) {
            return $value;
        }

        if (! str_contains($value, ' ') && str_contains($value, '.')) {
            return 'https://' . $value;
        }

        return '';
    }

    protected static function portfolioImageUrl($value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (
            filter_var($value, FILTER_VALIDATE_URL)
            || str_starts_with($value, '//')
            || str_starts_with($value, 'data:')
        ) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        return str_starts_with($value, 'storage/')
            ? asset($value)
            : asset('storage/' . ltrim($value, '/'));
    }

    protected static function portfolioUrl(Portfolio $portfolio): string
    {
        $routeKey = $portfolio->slug ?: $portfolio->id;

        return $routeKey && Route::has('portfolio.show')
            ? route('portfolio.show', $routeKey, false)
            : '#';
    }

    protected static function portfolioRelations(): array
    {
        $portfolio = new Portfolio();
        $relations = [];

        foreach (['translations', 'category'] as $relation) {
            if (method_exists($portfolio, $relation)) {
                $relations[] = $relation;
            }
        }

        return $relations;
    }

    protected static function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected static function hasPortfolioPayload(array $data): bool
    {
        if (! array_key_exists('portfolio_items', $data)) {
            return false;
        }

        return collect($data['portfolio_items'])->filter()->isNotEmpty();
    }

    protected static function templateQuery()
    {
        return Template::query()
            ->with(['translations', 'categoryTemplate.translation', 'categoryTemplate.translations'])
            ->when(Schema::hasColumn('templates', 'is_active'), function ($query) {
                $query->where('is_active', true);
            });
    }

    protected static function hasTemplatePayload(array $data): bool
    {
        if (! array_key_exists('templates', $data)) {
            return false;
        }

        return collect($data['templates'])->filter()->isNotEmpty();
    }
}
