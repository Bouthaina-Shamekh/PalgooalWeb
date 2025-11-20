<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Models\SubscriptionPage;
use App\Models\Plan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ServeTenantSite
{
    public function handle(Request $request, Closure $next)
    {
        if (! in_array($request->method(), ['GET', 'HEAD'])) {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $primaryDomain = strtolower(config('tenancy.primary_domain', parse_url(config('app.url'), PHP_URL_HOST) ?? ''));

        if ($primaryDomain && in_array($host, [$primaryDomain, 'www.' . $primaryDomain])) {
            return $next($request);
        }

        $subscription = Subscription::with(['plan'])
            ->where('domain_name', $host)
            ->where('status', 'active')
            ->first();

        if (! $subscription || ($subscription->plan && $subscription->plan->plan_type !== Plan::TYPE_MULTI_TENANT)) {
            return $next($request);
        }

        $page = $this->resolvePage($subscription, $request);

        if (! $page) {
            abort(404, 'Page not found for this tenant.');
        }

        View::share('tenantSubscription', $subscription);

        return response()->view('tenant.site', [
            'subscription' => $subscription,
            'page' => $page,
        ]);
    }

    protected function resolvePage(Subscription $subscription, Request $request): ?SubscriptionPage
    {
        $path = trim($request->path(), '/');
        $locale = app()->getLocale();

        $baseQuery = $subscription->pages()
            ->with(['translations', 'sections.translations'])
            ->where('is_active', true)
            ->orderByDesc('is_home');

        if ($path === '' || $path === null) {
            return (clone $baseQuery)
                ->where('is_home', true)
                ->first()
                ?? $baseQuery->first();
        }

        $page = $subscription->pages()
            ->with(['translations', 'sections.translations'])
            ->where('is_active', true)
            ->whereHas('translations', function ($query) use ($path, $locale) {
                $query->where('slug', $path)
                    ->where(function ($q) use ($locale) {
                        $q->where('locale', $locale)
                            ->orWhere('locale', config('app.fallback_locale', 'en'));
                    });
            })
            ->first();

        if ($page) {
            return $page;
        }

        return null;
    }
}
