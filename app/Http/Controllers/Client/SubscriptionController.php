<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenancy\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function show(Request $request, Subscription $subscription)
    {
        return $this->renderContentManagement($request, $subscription);
    }

    public function content(Request $request, Subscription $subscription)
    {
        return $this->renderContentManagement($request, $subscription);
    }

    public function site(Request $request, Subscription $subscription)
    {
        $subscription = $this->resolveOwnedSubscription($request, $subscription);
        $subscription->load([
            'plan',
            'template.translations',
        ]);

        $locale = app()->getLocale();
        $homePage = $subscription->canonicalPages()
            ->with('translations')
            ->where('context', 'tenant')
            ->where('is_active', true)
            ->orderByDesc('is_home')
            ->orderBy('id')
            ->first();

        $homeTranslation = $homePage?->translations->firstWhere('locale', $locale)
            ?? $homePage?->translations->first();

        $siteName = $homeTranslation?->title
            ?? $subscription->template?->translation()?->name
            ?? $subscription->template?->name
            ?? 'Your website';

        $domainName = trim((string) ($subscription->domain_name ?? ''));
        $siteUrl = $domainName !== '' ? tenant_url($domainName) : null;

        return view('client.subscriptions.site', [
            'subscription' => $subscription,
            'siteName' => $siteName,
            'siteUrl' => $siteUrl,
            'domainName' => $domainName,
        ]);
    }

    protected function resolveOwnedSubscription(Request $request, Subscription $subscription): Subscription
    {
        $client = $request->user('client');

        abort_unless($client && $subscription->client_id === $client->id, 403);

        return $subscription;
    }

    protected function renderContentManagement(Request $request, Subscription $subscription)
    {
        $subscription = $this->resolveOwnedSubscription($request, $subscription);

        $subscription->load([
            'plan',
            'template.translations',
        ]);

        $locale = app()->getLocale();
        $pages = $subscription->canonicalPages()
            ->with([
                'translations',
                'sections' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('order');
                },
                'sections.translations',
            ])
            ->where('context', 'tenant')
            ->where('is_active', true)
            ->orderByDesc('is_home')
            ->orderBy('id')
            ->get();

        return view('client.subscriptions.show', [
            'subscription' => $subscription,
            'locale' => $locale,
            'pages' => $pages,
        ]);
    }
}
