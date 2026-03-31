<?php

namespace App\Http\Middleware;

use App\Models\Page;
use App\Models\Tenancy\Subscription;
use App\Models\Plan;
use App\Services\Tenancy\TenantSiteShellService;
use App\Services\Tenancy\TenantRuntimeUsageRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class ServeTenantSite
{
    public function handle(Request $request, Closure $next)
    {
        if (! in_array($request->method(), ['GET', 'HEAD'])) {
            return $next($request);
        }

        $probePath = trim((string) config('tenancy.domain_verification.path', '/.well-known/palgoals-domain-check'), '/');

        if ($probePath !== '' && $request->is($probePath)) {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $primaryDomain = strtolower(config('tenancy.primary_domain', parse_url(config('app.url'), PHP_URL_HOST) ?? ''));

        if ($primaryDomain && in_array($host, [$primaryDomain, 'www.' . $primaryDomain])) {
            return $next($request);
        }

        $tenantSubdomain = tenant_subdomain_from_host($host);
        $exactHostSubscription = Subscription::with(['plan'])
            ->where('domain_name', $host)
            ->first();

        if ($exactHostSubscription && ! is_platform_tenant_host($host)) {
            if (
                $exactHostSubscription->status !== 'active'
                || ($exactHostSubscription->plan && $exactHostSubscription->plan->plan_type !== Plan::TYPE_MULTI_TENANT)
                || ! $exactHostSubscription->customDomainIsReady()
            ) {
                abort(404, 'Domain is not ready for runtime serving yet.');
            }
        }

        $subscription = Subscription::with(['plan'])
            ->where(function ($query) use ($host, $tenantSubdomain) {
                $query->where('domain_name', $host);

                if ($tenantSubdomain !== null) {
                    $query->orWhere(function ($tenantQuery) use ($tenantSubdomain) {
                        $tenantQuery->where('domain_option', 'subdomain')
                            ->where('subdomain', $tenantSubdomain);
                    });
                }
            })
            ->where('status', 'active')
            ->first();

        if (! $subscription || ($subscription->plan && $subscription->plan->plan_type !== Plan::TYPE_MULTI_TENANT)) {
            return $next($request);
        }

        if (! is_platform_tenant_host($host) && ! $subscription->customDomainIsReady()) {
            abort(404, 'Domain is not ready for runtime serving yet.');
        }

        $page = $this->resolveTenantPage($subscription, $request->path(), app()->getLocale());

        if (! $page) {
            abort(404, 'Page not found for this tenant.');
        }

        View::share('tenantSubscription', $subscription);

        $siteShells = app(TenantSiteShellService::class)->pages(
            $subscription,
            ensure: true,
            onlyActiveSections: true,
        );

        return response()->view('tenant.site', [
            'subscription' => $subscription,
            'page' => $page,
            'headerPage' => $siteShells[TenantSiteShellService::SHELL_HEADER] ?? null,
            'footerPage' => $siteShells[TenantSiteShellService::SHELL_FOOTER] ?? null,
        ]);
    }

    public function resolveTenantPage(
        Subscription $subscription,
        ?string $path = null,
        ?string $locale = null,
        bool $recordUsage = true,
    ): ?Page
    {
        $path = trim((string) $path, '/');
        $locale = $locale ?? app()->getLocale();

        $canonicalPage = $this->resolveCanonicalPage($subscription, $path, $locale);

        if ($canonicalPage) {
            if ($recordUsage) {
                app(TenantRuntimeUsageRecorder::class)->record(
                    subscription: $subscription,
                    source: 'canonical',
                    page: $canonicalPage,
                    path: $path,
                    locale: $locale,
                );
            }

            Log::info('Tenant runtime resolved page from canonical Page + Section.', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->id,
                'path' => $path,
                'page_id' => $canonicalPage->id,
                'source' => 'canonical',
            ]);

            return $canonicalPage;
        }

        return null;
    }

    protected function resolveCanonicalPage(Subscription $subscription, string $path, string $locale): ?Page
    {
        $baseQuery = Page::query()
            ->with([
                'translations',
                'sections' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('order');
                },
                'sections.translations',
            ])
            ->where('context', 'tenant')
            ->where('tenant_id', $subscription->id)
            ->where('is_active', true)
            ->orderByDesc('is_home')
            ->orderBy('id');

        if ($path === '') {
            return (clone $baseQuery)
                ->where('is_home', true)
                ->first()
                ?? $baseQuery->first();
        }

        return (clone $baseQuery)
            ->whereHas('translations', function ($query) use ($path, $locale) {
                $query->where('slug', $path)
                    ->where(function ($q) use ($locale) {
                        $q->where('locale', $locale)
                            ->orWhere('locale', config('app.fallback_locale', 'en'));
                    });
            })
            ->first();
    }
}
