<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSubscriptionThemeRequest;
use App\Models\Tenancy\Subscription;
use App\Services\Tenancy\TenantThemeCssGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionThemeController extends Controller
{
    public function __construct(
        private readonly TenantThemeCssGenerator $generator,
    ) {}

    /**
     * Save brand/theme settings from the client builder workspace and redirect back.
     */
    public function update(UpdateSubscriptionThemeRequest $request, Subscription $subscription): RedirectResponse
    {
        // Verify the client owns this subscription.
        $client = $request->user('client');

        abort_unless($client && (int) $subscription->client_id === (int) $client->id, 403);

        $existing = is_array($subscription->theme_settings) ? $subscription->theme_settings : [];
        $merged   = array_merge($existing, $request->themeData());

        $subscription->update(['theme_settings' => $merged]);

        // Regenerate the CSS file immediately so the builder preview reflects changes.
        $this->generator->generate($subscription->refresh());

        // Redirect back to wherever the builder sent us from.
        $returnUrl = $this->resolveReturnUrl($request);

        return redirect($returnUrl)->with('brand_settings_success', __('Brand settings saved successfully.'));
    }

    private function resolveReturnUrl(Request $request): string
    {
        // Accept an explicit same-origin path from the POST body.
        $url = trim((string) $request->input('_return_url', ''));

        if ($url !== '' && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        // Fall back to the Referer header — but only when it points to our own
        // origin, to prevent open-redirect attacks via a crafted Referer.
        $referer = trim((string) $request->headers->get('referer', ''));

        if ($referer !== '') {
            $appUrl = rtrim((string) config('app.url', ''), '/');

            if ($appUrl !== '' && str_starts_with($referer, $appUrl . '/')) {
                return $referer;
            }
        }

        return route('client.home');
    }
}
