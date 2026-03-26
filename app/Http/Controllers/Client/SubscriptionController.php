<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenancy\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function show(Request $request, Subscription $subscription)
    {
        $client = $request->user('client');

        abort_unless($client && $subscription->client_id === $client->id, 403);

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
