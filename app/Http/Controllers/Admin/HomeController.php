<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\Language;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function testimonials()
    {
        return view('dashboard.testimonials');
    }

    public function general_settings()
    {
        $generalSettingModel = GeneralSetting::first();

        $generalSetting = [
            'site_title' => $generalSettingModel?->site_title ?? '',
            'site_discretion' => $generalSettingModel?->site_discretion ?? '',
            'logo_url' => $generalSettingModel?->logo ?? '',
            'dark_logo_url' => $generalSettingModel?->dark_logo ?? '',
            'sticky_logo_url' => $generalSettingModel?->sticky_logo ?? '',
            'dark_sticky_logo_url' => $generalSettingModel?->dark_sticky_logo ?? '',
            'admin_logo_url' => $generalSettingModel?->admin_logo ?? '',
            'admin_dark_logo_url' => $generalSettingModel?->admin_dark_logo ?? '',
            'favicon_url' => $generalSettingModel?->favicon ?? '',
            'default_language' => $generalSettingModel?->default_language ?? '',
            'active_header_variant' => $generalSettingModel?->active_header_variant
                ?? config('front_layouts.defaults.header', 'default'),
            'active_footer_variant' => $generalSettingModel?->active_footer_variant
                ?? config('front_layouts.defaults.footer', 'default'),
            'contact_info' => $generalSettingModel?->contact_info ?? [
                'phone' => '',
                'email' => '',
                'address' => '',
            ],
            'social_links' => $generalSettingModel?->social_links ?? [
                'facebook' => '',
                'twitter' => '',
                'linkedin' => '',
                'instagram' => '',
                'whatsapp' => '',
            ],
        ];

        $languages = Language::all();

        return view('dashboard.general-setting', compact('generalSetting', 'languages'));
    }

    public function clients()
    {
        return view('dashboard.clients');
    }

    public function sites()
    {
        return view('dashboard.sites');
    }

    public function subscriptions()
    {
        return view('dashboard.subscriptions');
    }

    public function plans()
    {
        return view('dashboard.plans');
    }

    public function exportGeneralSettings(): StreamedResponse
    {
        $setting = GeneralSetting::first();

        $payload = [
            'meta' => [
                'schema_version' => 1,
                'exported_at' => now()->toIso8601String(),
                'app_url' => config('app.url'),
            ],
            'general_setting' => [
                'site_title' => $setting?->site_title,
                'site_discretion' => $setting?->site_discretion,
                'logo' => $setting?->logo,
                'dark_logo' => $setting?->dark_logo,
                'sticky_logo' => $setting?->sticky_logo,
                'dark_sticky_logo' => $setting?->dark_sticky_logo,
                'admin_logo' => $setting?->admin_logo,
                'admin_dark_logo' => $setting?->admin_dark_logo,
                'favicon' => $setting?->favicon,
                'default_language' => $setting?->default_language,
                'active_header_variant' => $setting?->active_header_variant ?? config('front_layouts.defaults.header', 'default'),
                'active_footer_variant' => $setting?->active_footer_variant ?? config('front_layouts.defaults.footer', 'default'),
                'header_show_promo_bar' => $setting?->header_show_promo_bar,
                'header_is_sticky' => $setting?->header_is_sticky,
                'header_variant_settings' => $setting?->header_variant_settings ?? [],
                'footer_show_contact_banner' => $setting?->footer_show_contact_banner,
                'footer_show_payment_methods' => $setting?->footer_show_payment_methods,
                'contact_info' => $setting?->contact_info ?? [],
                'social_links' => $setting?->social_links ?? [],
            ],
        ];

        $fileName = 'general-settings-' . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(
            function () use ($payload): void {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            },
            $fileName,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
            ]
        );
    }

    public function importGeneralSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'settings_file' => ['required', 'file', 'mimes:json,txt', 'max:2048'],
        ]);

        $raw = (string) $request->file('settings_file')->get();
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return redirect()
                ->route('dashboard.general_settings')
                ->with('error', 'Invalid JSON file. Please upload a valid export file.');
        }

        $payload = $decoded['general_setting'] ?? $decoded;

        if (!is_array($payload)) {
            return redirect()
                ->route('dashboard.general_settings')
                ->with('error', 'Invalid settings payload.');
        }

        $validator = Validator::make($payload, [
            'site_title' => ['nullable', 'string', 'max:255'],
            'site_discretion' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'string', 'max:255'],
            'dark_logo' => ['nullable', 'string', 'max:255'],
            'sticky_logo' => ['nullable', 'string', 'max:255'],
            'dark_sticky_logo' => ['nullable', 'string', 'max:255'],
            'admin_logo' => ['nullable', 'string', 'max:255'],
            'admin_dark_logo' => ['nullable', 'string', 'max:255'],
            'favicon' => ['nullable', 'string', 'max:255'],
            'default_language' => ['nullable', 'integer', Rule::exists('languages', 'id')],
            'active_header_variant' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('front_layouts.headers', []))),
            ],
            'active_footer_variant' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('front_layouts.footers', []))),
            ],
            'header_show_promo_bar' => ['nullable', 'boolean'],
            'header_is_sticky' => ['nullable', 'boolean'],
            'header_variant_settings' => ['nullable', 'array'],
            'footer_show_contact_banner' => ['nullable', 'boolean'],
            'footer_show_payment_methods' => ['nullable', 'boolean'],
            'contact_info' => ['nullable', 'array'],
            'contact_info.phone' => ['nullable', 'string', 'max:255'],
            'contact_info.email' => ['nullable', 'email', 'max:255'],
            'contact_info.address' => ['nullable', 'string', 'max:1000'],
            'social_links' => ['nullable', 'array'],
            'social_links.facebook' => ['nullable', 'url', 'max:255'],
            'social_links.twitter' => ['nullable', 'url', 'max:255'],
            'social_links.linkedin' => ['nullable', 'url', 'max:255'],
            'social_links.instagram' => ['nullable', 'url', 'max:255'],
            'social_links.whatsapp' => ['nullable', 'url', 'max:255'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('dashboard.general_settings')
                ->with('error', 'Import failed: settings file has invalid values.');
        }

        $validated = $validator->validated();

        $validated['active_header_variant'] = $validated['active_header_variant']
            ?? config('front_layouts.defaults.header', 'default');

        $validated['active_footer_variant'] = $validated['active_footer_variant']
            ?? config('front_layouts.defaults.footer', 'default');

        $validated['contact_info'] = array_merge(
            [
                'phone' => '',
                'email' => '',
                'address' => '',
            ],
            (array) ($validated['contact_info'] ?? [])
        );

        $validated['social_links'] = array_merge(
            [
                'facebook' => '',
                'twitter' => '',
                'linkedin' => '',
                'instagram' => '',
                'whatsapp' => '',
            ],
            (array) ($validated['social_links'] ?? [])
        );

        $validated['header_variant_settings'] = is_array($validated['header_variant_settings'] ?? null)
            ? $validated['header_variant_settings']
            : [];

        $setting = GeneralSetting::first();
        if ($setting) {
            $setting->update($validated);
        } else {
            GeneralSetting::create($validated);
        }

        return redirect()
            ->route('dashboard.general_settings')
            ->with('success', 'General settings imported successfully.');
    }

    public function updateGeneralSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_title' => ['required', 'string', 'max:255'],
            'site_discretion' => ['required', 'string', 'max:500'],
            'logo_url' => ['nullable', 'string', 'max:2048'],
            'dark_logo_url' => ['nullable', 'string', 'max:2048'],
            'sticky_logo_url' => ['nullable', 'string', 'max:2048'],
            'dark_sticky_logo_url' => ['nullable', 'string', 'max:2048'],
            'admin_logo_url' => ['nullable', 'string', 'max:2048'],
            'admin_dark_logo_url' => ['nullable', 'string', 'max:2048'],
            'favicon_url' => ['nullable', 'string', 'max:2048'],
            'default_language' => ['required', 'integer', Rule::exists('languages', 'id')],
            'active_header_variant' => [
                'required',
                'string',
                Rule::in(array_keys(config('front_layouts.headers', []))),
            ],
            'active_footer_variant' => [
                'required',
                'string',
                Rule::in(array_keys(config('front_layouts.footers', []))),
            ],
            'contact_info' => ['nullable', 'array'],
            'contact_info.phone' => ['nullable', 'string', 'max:255'],
            'contact_info.email' => ['nullable', 'email', 'max:255'],
            'contact_info.address' => ['nullable', 'string', 'max:1000'],
            'social_links' => ['nullable', 'array'],
            'social_links.facebook' => ['nullable', 'url', 'max:255'],
            'social_links.twitter' => ['nullable', 'url', 'max:255'],
            'social_links.linkedin' => ['nullable', 'url', 'max:255'],
            'social_links.instagram' => ['nullable', 'url', 'max:255'],
            'social_links.whatsapp' => ['nullable', 'url', 'max:255'],
        ]);

        $setting = GeneralSetting::first() ?? new GeneralSetting();

        $setting->site_title = $validated['site_title'];
        $setting->site_discretion = $validated['site_discretion'];
        $setting->default_language = $validated['default_language'];
        $setting->active_header_variant = $validated['active_header_variant'];
        $setting->active_footer_variant = $validated['active_footer_variant'];

        $setting->logo = $this->normalizeMediaPath($validated['logo_url'] ?? null);
        $setting->dark_logo = $this->normalizeMediaPath($validated['dark_logo_url'] ?? null);
        $setting->sticky_logo = $this->normalizeMediaPath($validated['sticky_logo_url'] ?? null);
        $setting->dark_sticky_logo = $this->normalizeMediaPath($validated['dark_sticky_logo_url'] ?? null);
        $setting->admin_logo = $this->normalizeMediaPath($validated['admin_logo_url'] ?? null);
        $setting->admin_dark_logo = $this->normalizeMediaPath($validated['admin_dark_logo_url'] ?? null);
        $setting->favicon = $this->normalizeMediaPath($validated['favicon_url'] ?? null);

        $setting->contact_info = array_merge(
            [
                'phone' => '',
                'email' => '',
                'address' => '',
            ],
            (array) ($validated['contact_info'] ?? [])
        );

        $setting->social_links = array_merge(
            [
                'facebook' => '',
                'twitter' => '',
                'linkedin' => '',
                'instagram' => '',
                'whatsapp' => '',
            ],
            (array) ($validated['social_links'] ?? [])
        );

        $setting->save();

        return redirect()
            ->route('dashboard.general_settings')
            ->with('success', 'General settings updated successfully.');
    }

    public function autoSaveGeneralSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_title' => ['nullable', 'string', 'max:255'],
            'site_discretion' => ['nullable', 'string', 'max:500'],
            'logo_url' => ['nullable', 'string', 'max:2048'],
            'dark_logo_url' => ['nullable', 'string', 'max:2048'],
            'sticky_logo_url' => ['nullable', 'string', 'max:2048'],
            'dark_sticky_logo_url' => ['nullable', 'string', 'max:2048'],
            'admin_logo_url' => ['nullable', 'string', 'max:2048'],
            'admin_dark_logo_url' => ['nullable', 'string', 'max:2048'],
            'favicon_url' => ['nullable', 'string', 'max:2048'],
            'default_language' => ['nullable', 'integer', Rule::exists('languages', 'id')],
            'active_header_variant' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('front_layouts.headers', []))),
            ],
            'active_footer_variant' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('front_layouts.footers', []))),
            ],
            'contact_info' => ['nullable', 'array'],
            'contact_info.phone' => ['nullable', 'string', 'max:255'],
            'contact_info.email' => ['nullable', 'email', 'max:255'],
            'contact_info.address' => ['nullable', 'string', 'max:1000'],
            'social_links' => ['nullable', 'array'],
            'social_links.facebook' => ['nullable', 'url', 'max:255'],
            'social_links.twitter' => ['nullable', 'url', 'max:255'],
            'social_links.linkedin' => ['nullable', 'url', 'max:255'],
            'social_links.instagram' => ['nullable', 'url', 'max:255'],
            'social_links.whatsapp' => ['nullable', 'url', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'saved' => false,
                'message' => 'Draft not saved (check field format)',
            ], 422);
        }

        $validated = $validator->validated();
        $setting = GeneralSetting::first() ?? new GeneralSetting();

        if (array_key_exists('site_title', $validated)) {
            $setting->site_title = $validated['site_title'];
        }

        if (array_key_exists('site_discretion', $validated)) {
            $setting->site_discretion = $validated['site_discretion'];
        }

        if (array_key_exists('default_language', $validated)) {
            $setting->default_language = $validated['default_language'];
        }

        if (array_key_exists('active_header_variant', $validated)) {
            $setting->active_header_variant = $validated['active_header_variant']
                ?: config('front_layouts.defaults.header', 'default');
        }

        if (array_key_exists('active_footer_variant', $validated)) {
            $setting->active_footer_variant = $validated['active_footer_variant']
                ?: config('front_layouts.defaults.footer', 'default');
        }

        $assetMap = [
            'logo' => 'logo_url',
            'dark_logo' => 'dark_logo_url',
            'sticky_logo' => 'sticky_logo_url',
            'dark_sticky_logo' => 'dark_sticky_logo_url',
            'admin_logo' => 'admin_logo_url',
            'admin_dark_logo' => 'admin_dark_logo_url',
            'favicon' => 'favicon_url',
        ];

        foreach ($assetMap as $modelField => $requestField) {
            if (array_key_exists($requestField, $validated)) {
                $setting->{$modelField} = $this->normalizeMediaPath($validated[$requestField]);
            }
        }

        if (array_key_exists('contact_info', $validated)) {
            $setting->contact_info = array_merge(
                [
                    'phone' => '',
                    'email' => '',
                    'address' => '',
                ],
                (array) $validated['contact_info']
            );
        }

        if (array_key_exists('social_links', $validated)) {
            $setting->social_links = array_merge(
                [
                    'facebook' => '',
                    'twitter' => '',
                    'linkedin' => '',
                    'instagram' => '',
                    'whatsapp' => '',
                ],
                (array) $validated['social_links']
            );
        }

        $setting->save();

        return response()->json([
            'saved' => true,
            'saved_at' => now()->format('H:i:s'),
        ]);
    }

    private function extractStoragePathFromUrl(string $value): ?string
    {
        $path = parse_url($value, PHP_URL_PATH);
        if (!is_string($path) || trim($path) === '') {
            return null;
        }

        $normalized = ltrim($path, '/');
        $storagePrefix = 'storage/';
        $position = strpos($normalized, $storagePrefix);

        if ($position === false) {
            return null;
        }

        return ltrim(substr($normalized, $position + strlen($storagePrefix)), '/');
    }

    private function normalizeMediaPath($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        // If value is media id, convert to stored file_path.
        if (ctype_digit($normalized)) {
            $media = Media::find((int) $normalized);
            if ($media && !empty($media->file_path)) {
                return ltrim((string) $media->file_path, '/');
            }
        }

        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://') || str_starts_with($normalized, '//')) {
            return $this->extractStoragePathFromUrl($normalized) ?? $normalized;
        }

        $normalized = ltrim($normalized, '/');
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        return $normalized;
    }
}
