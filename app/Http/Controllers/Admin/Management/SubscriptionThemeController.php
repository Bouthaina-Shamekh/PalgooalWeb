<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSubscriptionThemeRequest;
use App\Models\Tenancy\Subscription;
use App\Services\Tenancy\TenantThemeCssGenerator;
use App\Support\Tenancy\TenantThemeSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionThemeController extends Controller
{
    public function __construct(
        private readonly TenantThemeCssGenerator $generator,
    ) {}

    /**
     * Show the theme editor for a subscription.
     */
    public function edit(Subscription $subscription): View
    {
        $theme = TenantThemeSettings::fromArray(
            is_array($subscription->theme_settings) ? $subscription->theme_settings : []
        );

        $cssUrl = $this->generator->urlIfExists($subscription->id);

        return view('dashboard.management.subscriptions.theme', compact('subscription', 'theme', 'cssUrl'));
    }

    /**
     * Save theme settings and regenerate the CSS file.
     *
     * When called from the builder workspace, the form includes a hidden
     * `_return_url` field so we redirect back to the builder instead of the
     * standalone theme editor page.
     */
    public function update(UpdateSubscriptionThemeRequest $request, Subscription $subscription): RedirectResponse
    {
        // Merge validated input on top of existing settings so untouched
        // tokens keep their previous saved values (not reset to defaults).
        $existing = is_array($subscription->theme_settings) ? $subscription->theme_settings : [];
        $merged   = array_merge($existing, $request->themeData());

        $subscription->update(['theme_settings' => $merged]);

        // Regenerate the CSS file from the freshly saved model.
        $this->generator->generate($subscription->refresh());

        // If the request came from the builder workspace (has _return_url),
        // redirect back there with a builder-friendly flash key.
        $returnUrl = trim((string) $request->input('_return_url', ''));

        if ($returnUrl !== '' && str_starts_with($returnUrl, '/') && ! str_starts_with($returnUrl, '//')) {
            return redirect($returnUrl)
                ->with('brand_settings_success', __('Brand settings saved successfully.'));
        }

        return redirect()
            ->route('dashboard.subscriptions.theme.edit', $subscription)
            ->with('success', __('Theme saved and CSS regenerated successfully.'));
    }
}
