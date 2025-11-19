<?php

namespace App\Http\Controllers\Clinet;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionSection;
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
            'pages.translations',
            'pages.sections.translations',
        ]);

        $locale = app()->getLocale();

        return view('client.subscriptions.show', [
            'subscription' => $subscription,
            'locale' => $locale,
        ]);
    }

    public function updateSection(Request $request, Subscription $subscription, SubscriptionSection $section)
    {
        $client = $request->user('client');

        abort_unless($client && $subscription->client_id === $client->id, 403);
        abort_unless($section->page && $section->page->subscription_id === $subscription->id, 404);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $contentPayload = null;
        if (! empty($data['content'])) {
            $rawContent = trim($data['content']);
            $decoded = json_decode($rawContent, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $contentPayload = $decoded;
            } else {
                $contentPayload = $rawContent;
            }
        }

        $locale = app()->getLocale();

        $section->translations()->updateOrCreate(
            ['locale' => $locale],
            [
                'title' => $data['title'] ?? null,
                'content' => $contentPayload,
            ]
        );

        return back()->with([
            'ok' => 'تم حفظ التعديلات بنجاح.',
            'last_section_id' => $section->id,
        ]);
    }
}
