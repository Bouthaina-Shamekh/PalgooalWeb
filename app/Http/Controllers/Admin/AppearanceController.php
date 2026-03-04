<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppearanceController extends Controller
{
    public function header(): View
    {
        return view('dashboard.appearance.header', [
            'settings' => $this->settings(),
            'headerVariants' => config('front_layouts.headers', []),
        ]);
    }

    public function updateHeaderVariant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'active_header_variant' => [
                'required',
                'string',
                Rule::in(array_keys(config('front_layouts.headers', []))),
            ],
        ]);

        $this->settings()->update($validated);

        return back()->with('success', 'Header layout activated successfully.');
    }

    public function updateHeaderSettings(Request $request): RedirectResponse
    {
        $settings = $this->settings();

        $settings->update([
            'header_show_promo_bar' => $request->boolean('header_show_promo_bar'),
            'header_is_sticky' => $request->boolean('header_is_sticky'),
        ]);

        return back()->with('success', 'Header settings saved successfully.');
    }

    public function footer(): View
    {
        return view('dashboard.appearance.footer', [
            'settings' => $this->settings(),
            'footerVariants' => config('front_layouts.footers', []),
        ]);
    }

    public function updateFooterVariant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'active_footer_variant' => [
                'required',
                'string',
                Rule::in(array_keys(config('front_layouts.footers', []))),
            ],
        ]);

        $this->settings()->update($validated);

        return back()->with('success', 'Footer layout activated successfully.');
    }

    public function updateFooterSettings(Request $request): RedirectResponse
    {
        $settings = $this->settings();

        $settings->update([
            'footer_show_contact_banner' => $request->boolean('footer_show_contact_banner'),
            'footer_show_payment_methods' => $request->boolean('footer_show_payment_methods'),
        ]);

        return back()->with('success', 'Footer settings saved successfully.');
    }

    protected function settings(): GeneralSetting
    {
        return GeneralSetting::query()->firstOrCreate([], [
            'active_header_variant' => config('front_layouts.defaults.header', 'default'),
            'active_footer_variant' => config('front_layouts.defaults.footer', 'default'),
            'header_show_promo_bar' => true,
            'header_is_sticky' => true,
            'footer_show_contact_banner' => true,
            'footer_show_payment_methods' => true,
        ]);
    }
}
