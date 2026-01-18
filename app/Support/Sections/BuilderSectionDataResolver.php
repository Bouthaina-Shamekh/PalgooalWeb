<?php

namespace App\Support\Sections;

use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\Service;
use App\Models\Template;
use App\Models\CategoryTemplate;
use App\Models\Testimonial;

class BuilderSectionDataResolver
{
    /**
     * Inject dynamic DB data into builder section $data
     * based on section $type (services, templates, blog, ...)
     */
    public static function resolve(string $type, array $data = []): array
    {
        $type = trim(strtolower($type));

        return match ($type) {
            'services' => self::services($data),
            'templates' => self::templates($data),
            'testimonials' => self::testimonials($data),
            'hosting-plans' => self::hostingPlans($data),
            'search-domain' => self::searchDomain($data),
            'templates-pages' => self::templatesPages($data),
            'blog' => self::blog($data),

            default => $data,
        };
    }

    protected static function services(array $data): array
    {
        $data['services'] = Service::with('translations')
            ->orderBy('order')
            ->get();

        return $data;
    }

    protected static function templates(array $data): array
    {
        $data['templates'] = Template::with('translations')
            ->latest()
            ->take(8)
            ->get();

        return $data;
    }

    protected static function testimonials(array $data): array
    {
        $data['testimonials'] = Testimonial::approved()
            ->with('translations')
            ->orderBy('order')
            ->get();

        return $data;
    }

    protected static function hostingPlans(array $data): array
    {
        // نفس منطق legacy تقريبًا لكن مبسّط
        $cat = null;

        $query = Plan::where('is_active', true)
            ->with(['translations', 'category.translations'])
            ->orderBy('id', 'asc');

        if (! empty($data['plan_category_id'])) {
            $query->where('plan_category_id', (int) $data['plan_category_id']);
            $cat = PlanCategory::with('translations')->find((int) $data['plan_category_id']);
        } elseif (! empty($data['plan_category_slug'])) {
            $slug = (string) $data['plan_category_slug'];

            $cat = PlanCategory::whereHas('translations', function ($q) use ($slug) {
                $q->where('slug', $slug)->where('locale', app()->getLocale());
            })
                ->with('translations')
                ->first()
                ?: PlanCategory::whereHas('translations', fn($q) => $q->where('slug', $slug))
                ->with('translations')
                ->first();

            if ($cat) {
                $query->where('plan_category_id', $cat->id);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        $data['plans'] = $query->get();
        $data['category'] = $cat;

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

    protected static function templatesPages(array $data): array
    {
        $data['max_price'] = $data['max_price'] ?? 500;
        $data['sort_by'] = request('sort', $data['sort_by'] ?? 'default');
        $data['show_filter_sidebar'] = $data['show_filter_sidebar'] ?? true;
        $data['selectedCategory'] = $data['selectedCategory'] ?? 'all';

        $data['templates'] = Template::with(['translations', 'categoryTemplate.translations'])
            ->latest()
            ->take(60)
            ->get();

        $data['categories'] = CategoryTemplate::with([
            'translations' => function ($q) {
                $q->where('locale', app()->getLocale())
                    ->orWhere('locale', config('app.fallback_locale', 'ar'));
            },
        ])
            ->get()
            ->map(function ($cat) {
                $t =
                    $cat->translations->firstWhere('locale', app()->getLocale())
                    ?? $cat->translations->firstWhere('locale', config('app.fallback_locale', 'ar'));

                $cat->translated_name = $t?->name ?? 'غير معروف';
                $cat->translated_slug = $t?->slug ?? ($cat->slug ?? 'uncategorized');

                return $cat;
            });

        return $data;
    }

    protected static function blog(array $data): array
    {
        // إذا عندك موديل مقالات/Posts أضف هنا. حالياً نخليها كما هي.
        return $data;
    }
}
