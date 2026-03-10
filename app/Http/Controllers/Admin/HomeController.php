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
        $languages = Language::query()->orderBy('id')->get();
        $contentLanguages = $this->generalSettingContentLanguages($languages, $generalSettingModel?->default_language);
        $defaultLocaleCode = $this->resolveDefaultLanguageCode($languages, $generalSettingModel?->default_language);
        $contactInfo = array_merge(
            $this->baseContactInfo(),
            (array) ($generalSettingModel?->contact_info ?? [])
        );

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
            'contact_info' => $contactInfo,
            'social_links' => array_merge(
                $this->baseSocialLinks(),
                (array) ($generalSettingModel?->social_links ?? [])
            ),
            'localized_content' => $this->normalizeStoredLocalizedContent(
                is_array($generalSettingModel?->localized_content ?? null)
                    ? $generalSettingModel->localized_content
                    : [],
                $defaultLocaleCode,
                [
                    'site_title' => $generalSettingModel?->site_title,
                    'site_discretion' => $generalSettingModel?->site_discretion,
                    'contact_address' => $contactInfo['address'] ?? '',
                ],
            ),
        ];

        return view('dashboard.general-setting', compact('generalSetting', 'languages', 'contentLanguages'));
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
                'schema_version' => 2,
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
                'localized_content' => $setting?->localized_content ?? [],
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
            'localized_content' => ['nullable', 'array'],
            'localized_content.site_title' => ['nullable', 'array'],
            'localized_content.site_title.*' => ['nullable', 'string', 'max:255'],
            'localized_content.site_discretion' => ['nullable', 'array'],
            'localized_content.site_discretion.*' => ['nullable', 'string', 'max:500'],
            'localized_content.contact_address' => ['nullable', 'array'],
            'localized_content.contact_address.*' => ['nullable', 'string', 'max:1000'],
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

        $languages = Language::query()->orderBy('id')->get();
        $defaultLocaleCode = $this->resolveDefaultLanguageCode($languages, $validated['default_language'] ?? null);

        $validated['contact_info'] = array_merge(
            $this->baseContactInfo(),
            (array) ($validated['contact_info'] ?? [])
        );

        $validated['social_links'] = array_merge(
            $this->baseSocialLinks(),
            (array) ($validated['social_links'] ?? [])
        );

        $validated['header_variant_settings'] = is_array($validated['header_variant_settings'] ?? null)
            ? $validated['header_variant_settings']
            : [];

        $validated['localized_content'] = $this->normalizeStoredLocalizedContent(
            is_array($validated['localized_content'] ?? null) ? $validated['localized_content'] : [],
            $defaultLocaleCode,
            [
                'site_title' => $validated['site_title'] ?? null,
                'site_discretion' => $validated['site_discretion'] ?? null,
                'contact_address' => $validated['contact_info']['address'] ?? null,
            ],
        );

        $validated['site_title'] = $this->extractPrimaryLocalizedValue(
            $validated['localized_content']['site_title'] ?? [],
            $defaultLocaleCode,
            (string) ($validated['site_title'] ?? ''),
        );
        $validated['site_discretion'] = $this->extractPrimaryLocalizedValue(
            $validated['localized_content']['site_discretion'] ?? [],
            $defaultLocaleCode,
            (string) ($validated['site_discretion'] ?? ''),
        );
        $validated['contact_info']['address'] = $this->extractPrimaryLocalizedValue(
            $validated['localized_content']['contact_address'] ?? [],
            $defaultLocaleCode,
            (string) ($validated['contact_info']['address'] ?? ''),
        );

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
        $validator = Validator::make($request->all(), [
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
            'social_links' => ['nullable', 'array'],
            'social_links.facebook' => ['nullable', 'url', 'max:255'],
            'social_links.twitter' => ['nullable', 'url', 'max:255'],
            'social_links.linkedin' => ['nullable', 'url', 'max:255'],
            'social_links.instagram' => ['nullable', 'url', 'max:255'],
            'social_links.whatsapp' => ['nullable', 'url', 'max:255'],
            'gs_texts' => ['nullable', 'array'],
            'gs_texts.*' => ['nullable', 'array'],
            'gs_texts.*.site_title' => ['nullable', 'string', 'max:255'],
            'gs_texts.*.site_discretion' => ['nullable', 'string', 'max:500'],
            'gs_texts.*.contact_address' => ['nullable', 'string', 'max:1000'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $languages = Language::query()->orderBy('id')->get();
            $defaultLocaleCode = $this->resolveDefaultLanguageCode(
                $languages,
                (int) $request->input('default_language')
            );
            $languageCodes = $this->generalSettingContentLanguages($languages, (int) $request->input('default_language'))
                ->pluck('code')
                ->map(fn ($code) => strtolower((string) $code))
                ->filter()
                ->values()
                ->all();

            $localizedContent = $this->normalizeLocalizedContentFromRequest(
                is_array($request->input('gs_texts')) ? $request->input('gs_texts') : [],
                $languageCodes,
                $defaultLocaleCode,
            );

            if (($localizedContent['site_title'] ?? []) === []) {
                $validator->errors()->add(
                    "gs_texts.$defaultLocaleCode.site_title",
                    'Site title is required for at least one language.'
                );
            }

            if (($localizedContent['site_discretion'] ?? []) === []) {
                $validator->errors()->add(
                    "gs_texts.$defaultLocaleCode.site_discretion",
                    'Site description is required for at least one language.'
                );
            }
        });

        $validated = $validator->validate();

        $setting = GeneralSetting::first() ?? new GeneralSetting();
        $languages = Language::query()->orderBy('id')->get();
        $defaultLocaleCode = $this->resolveDefaultLanguageCode($languages, $validated['default_language']);
        $languageCodes = $this->generalSettingContentLanguages($languages, $validated['default_language'])
            ->pluck('code')
            ->map(fn ($code) => strtolower((string) $code))
            ->filter()
            ->values()
            ->all();
        $existingLocalizedContent = $this->normalizeStoredLocalizedContent(
            is_array($setting->localized_content ?? null) ? $setting->localized_content : [],
            $defaultLocaleCode,
            [
                'site_title' => $setting->getRawOriginal('site_title'),
                'site_discretion' => $setting->getRawOriginal('site_discretion'),
                'contact_address' => data_get($setting->contact_info, 'address'),
            ],
        );
        $localizedContent = $this->normalizeLocalizedContentFromRequest(
            is_array($validated['gs_texts'] ?? null) ? $validated['gs_texts'] : [],
            $languageCodes,
            $defaultLocaleCode,
            [
                'site_title' => $setting->getRawOriginal('site_title'),
                'site_discretion' => $setting->getRawOriginal('site_discretion'),
                'contact_address' => data_get($setting->contact_info, 'address'),
            ],
            $existingLocalizedContent,
        );

        $setting->site_title = $this->extractPrimaryLocalizedValue(
            $localizedContent['site_title'] ?? [],
            $defaultLocaleCode,
            (string) $setting->getRawOriginal('site_title'),
        );
        $setting->site_discretion = $this->extractPrimaryLocalizedValue(
            $localizedContent['site_discretion'] ?? [],
            $defaultLocaleCode,
            (string) $setting->getRawOriginal('site_discretion'),
        );
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
            $this->baseContactInfo(),
            (array) ($validated['contact_info'] ?? [])
        );
        $setting->contact_info['address'] = $this->extractPrimaryLocalizedValue(
            $localizedContent['contact_address'] ?? [],
            $defaultLocaleCode,
            (string) ($setting->contact_info['address'] ?? ''),
        );

        $setting->social_links = array_merge(
            $this->baseSocialLinks(),
            (array) ($validated['social_links'] ?? [])
        );
        $setting->localized_content = $localizedContent;

        $setting->save();

        return redirect()
            ->route('dashboard.general_settings')
            ->with('success', 'General settings updated successfully.');
    }

    public function autoSaveGeneralSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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
            'social_links' => ['nullable', 'array'],
            'social_links.facebook' => ['nullable', 'url', 'max:255'],
            'social_links.twitter' => ['nullable', 'url', 'max:255'],
            'social_links.linkedin' => ['nullable', 'url', 'max:255'],
            'social_links.instagram' => ['nullable', 'url', 'max:255'],
            'social_links.whatsapp' => ['nullable', 'url', 'max:255'],
            'gs_texts' => ['nullable', 'array'],
            'gs_texts.*' => ['nullable', 'array'],
            'gs_texts.*.site_title' => ['nullable', 'string', 'max:255'],
            'gs_texts.*.site_discretion' => ['nullable', 'string', 'max:500'],
            'gs_texts.*.contact_address' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'saved' => false,
                'message' => 'Draft not saved (check field format)',
            ], 422);
        }

        $validated = $validator->validated();
        $setting = GeneralSetting::first() ?? new GeneralSetting();

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

        $defaultLocaleCode = $this->resolveDefaultLanguageCode(
            Language::query()->orderBy('id')->get(),
            $setting->default_language
        );
        $languageCodes = $this->generalSettingContentLanguages(
            Language::query()->orderBy('id')->get(),
            $setting->default_language
        )
            ->pluck('code')
            ->map(fn ($code) => strtolower((string) $code))
            ->filter()
            ->values()
            ->all();
        $existingLocalizedContent = $this->normalizeStoredLocalizedContent(
            is_array($setting->localized_content ?? null) ? $setting->localized_content : [],
            $defaultLocaleCode,
            [
                'site_title' => $setting->getRawOriginal('site_title'),
                'site_discretion' => $setting->getRawOriginal('site_discretion'),
                'contact_address' => data_get($setting->contact_info, 'address'),
            ],
        );

        if (array_key_exists('contact_info', $validated)) {
            $setting->contact_info = array_merge(
                $this->baseContactInfo(),
                (array) $validated['contact_info']
            );
        }

        if (array_key_exists('social_links', $validated)) {
            $setting->social_links = array_merge(
                $this->baseSocialLinks(),
                (array) $validated['social_links']
            );
        }

        if (array_key_exists('gs_texts', $validated) || $setting->exists) {
            $localizedContent = $this->normalizeLocalizedContentFromRequest(
                is_array($validated['gs_texts'] ?? null) ? $validated['gs_texts'] : [],
                $languageCodes,
                $defaultLocaleCode,
                [
                    'site_title' => $setting->getRawOriginal('site_title'),
                    'site_discretion' => $setting->getRawOriginal('site_discretion'),
                    'contact_address' => data_get($setting->contact_info, 'address'),
                ],
                $existingLocalizedContent,
            );

            $setting->localized_content = $localizedContent;
            $setting->site_title = $this->extractPrimaryLocalizedValue(
                $localizedContent['site_title'] ?? [],
                $defaultLocaleCode,
                (string) $setting->getRawOriginal('site_title'),
            );
            $setting->site_discretion = $this->extractPrimaryLocalizedValue(
                $localizedContent['site_discretion'] ?? [],
                $defaultLocaleCode,
                (string) $setting->getRawOriginal('site_discretion'),
            );

            $contactInfo = array_merge($this->baseContactInfo(), (array) ($setting->contact_info ?? []));
            $contactInfo['address'] = $this->extractPrimaryLocalizedValue(
                $localizedContent['contact_address'] ?? [],
                $defaultLocaleCode,
                (string) ($contactInfo['address'] ?? ''),
            );
            $setting->contact_info = $contactInfo;
        }

        $setting->save();

        return response()->json([
            'saved' => true,
            'saved_at' => now()->format('H:i:s'),
        ]);
    }

    private function baseContactInfo(): array
    {
        return [
            'phone' => '',
            'email' => '',
            'address' => '',
        ];
    }

    private function baseSocialLinks(): array
    {
        return [
            'facebook' => '',
            'twitter' => '',
            'linkedin' => '',
            'instagram' => '',
            'whatsapp' => '',
        ];
    }

    private function generalSettingContentLanguages($languages, $defaultLanguageId = null)
    {
        $languages = $languages instanceof \Illuminate\Support\Collection
            ? $languages
            : collect($languages);

        $activeLanguages = $languages
            ->filter(fn ($language) => (bool) ($language->is_active ?? false))
            ->values();

        $defaultLanguage = $languages->firstWhere('id', $defaultLanguageId);
        if ($defaultLanguage && $activeLanguages->doesntContain(fn ($language) => (int) $language->id === (int) $defaultLanguage->id)) {
            $activeLanguages->prepend($defaultLanguage);
        }

        return $activeLanguages->isNotEmpty()
            ? $activeLanguages->values()
            : $languages->values();
    }

    private function resolveDefaultLanguageCode($languages, $defaultLanguageId = null): string
    {
        $languages = $languages instanceof \Illuminate\Support\Collection
            ? $languages
            : collect($languages);

        $defaultLanguage = $languages->firstWhere('id', $defaultLanguageId);
        $defaultCode = strtolower(trim((string) ($defaultLanguage->code ?? '')));

        return $defaultCode !== ''
            ? $defaultCode
            : strtolower((string) config('app.locale', 'en'));
    }

    private function normalizeLocalizedContentFromRequest(
        array $localizedTexts,
        array $languageCodes,
        string $defaultLocale,
        array $legacyValues = [],
        array $existingValues = [],
    ): array {
        $fields = ['site_title', 'site_discretion', 'contact_address'];
        $normalized = [];

        foreach ($fields as $field) {
            $normalized[$field] = $this->normalizeLocalizedTextField(
                $localizedTexts,
                $field,
                $languageCodes,
                $defaultLocale,
                $legacyValues[$field] ?? null,
                is_array($existingValues[$field] ?? null) ? $existingValues[$field] : [],
            );
        }

        return $normalized;
    }

    private function normalizeStoredLocalizedContent(
        array $localizedContent,
        string $defaultLocale,
        array $legacyValues = [],
    ): array {
        $fields = ['site_title', 'site_discretion', 'contact_address'];
        $normalized = [];

        foreach ($fields as $field) {
            $fieldValues = is_array($localizedContent[$field] ?? null)
                ? $localizedContent[$field]
                : [];

            $normalizedFieldValues = [];
            foreach ($fieldValues as $languageCode => $value) {
                $code = strtolower(trim((string) $languageCode));
                $normalizedValue = $this->normalizeScalarValue($value);

                if ($code === '' || $normalizedValue === '') {
                    continue;
                }

                $normalizedFieldValues[$code] = $normalizedValue;
            }

            $legacyValue = $this->normalizeScalarValue($legacyValues[$field] ?? '');
            if ($normalizedFieldValues === [] && $legacyValue !== '') {
                $fallbackCode = $defaultLocale !== ''
                    ? strtolower($defaultLocale)
                    : strtolower((string) config('app.locale', 'en'));

                $normalizedFieldValues[$fallbackCode] = $legacyValue;
            }

            $normalized[$field] = $normalizedFieldValues;
        }

        return $normalized;
    }

    private function normalizeLocalizedTextField(
        array $localizedTexts,
        string $field,
        array $languageCodes,
        string $defaultLocale,
        $legacyValue = null,
        array $existingValues = [],
    ): array {
        $normalized = [];
        $visibleLanguageCodes = collect($languageCodes)
            ->map(fn ($code) => strtolower(trim((string) $code)))
            ->filter()
            ->values()
            ->all();

        foreach ($existingValues as $languageCode => $value) {
            $code = strtolower(trim((string) $languageCode));
            $normalizedValue = $this->normalizeScalarValue($value);
            if ($code === '' || $normalizedValue === '' || in_array($code, $visibleLanguageCodes, true)) {
                continue;
            }

            $normalized[$code] = $normalizedValue;
        }

        foreach ($visibleLanguageCodes as $languageCode) {
            $value = $this->normalizeScalarValue(data_get($localizedTexts, "{$languageCode}.{$field}", ''));
            if ($value !== '') {
                $normalized[$languageCode] = $value;
            } elseif (array_key_exists($languageCode, $normalized)) {
                unset($normalized[$languageCode]);
            }
        }

        $legacyValue = $this->normalizeScalarValue($legacyValue);
        if ($normalized === [] && $legacyValue !== '') {
            $fallbackCode = $defaultLocale !== ''
                ? strtolower($defaultLocale)
                : strtolower((string) config('app.locale', 'en'));

            $normalized[$fallbackCode] = $legacyValue;
        }

        return $normalized;
    }

    private function extractPrimaryLocalizedValue(array $localizedValues, string $defaultLocale, string $fallback = ''): string
    {
        $defaultLocale = strtolower(trim($defaultLocale));
        $fallbackLocale = strtolower((string) config('app.fallback_locale', 'en'));

        if ($defaultLocale !== '') {
            $defaultValue = $this->normalizeScalarValue($localizedValues[$defaultLocale] ?? '');
            if ($defaultValue !== '') {
                return $defaultValue;
            }
        }

        $fallbackLocaleValue = $this->normalizeScalarValue($localizedValues[$fallbackLocale] ?? '');
        if ($fallbackLocaleValue !== '') {
            return $fallbackLocaleValue;
        }

        foreach ($localizedValues as $value) {
            $candidate = $this->normalizeScalarValue($value);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $this->normalizeScalarValue($fallback);
    }

    private function normalizeScalarValue($value): string
    {
        if (is_array($value)) {
            $normalized = '';

            array_walk_recursive($value, static function ($item) use (&$normalized): void {
                if ($normalized !== '') {
                    return;
                }

                if (is_scalar($item) || $item instanceof \Stringable) {
                    $candidate = trim((string) $item);
                    if ($candidate !== '') {
                        $normalized = $candidate;
                    }
                }
            });

            return $normalized;
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return trim((string) $value);
        }

        return '';
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
