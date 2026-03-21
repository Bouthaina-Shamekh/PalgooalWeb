<?php

namespace App\Support\Sections;

use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;

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
            'hosting_pricing_showcase' => self::hostingPricingShowcase($data),
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
}
