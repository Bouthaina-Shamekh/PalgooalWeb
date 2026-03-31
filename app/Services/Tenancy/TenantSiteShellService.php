<?php

namespace App\Services\Tenancy;

use App\Models\Language;
use App\Models\Page;
use App\Models\Tenancy\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TenantSiteShellService
{
    public const SHELL_HEADER = 'header';
    public const SHELL_FOOTER = 'footer';

    public const CONTEXT_HEADER = 'tenant_header';
    public const CONTEXT_FOOTER = 'tenant_footer';

    /**
     * @return array<string, string>
     */
    public function contexts(): array
    {
        return [
            self::SHELL_HEADER => self::CONTEXT_HEADER,
            self::SHELL_FOOTER => self::CONTEXT_FOOTER,
        ];
    }

    public function contextFor(string $shell): string
    {
        return $this->contexts()[$shell] ?? self::CONTEXT_HEADER;
    }

    public function isShellContext(?string $context): bool
    {
        return in_array((string) $context, array_values($this->contexts()), true);
    }

    /**
     * @return array{header:?Page,footer:?Page}
     */
    public function pages(
        Subscription $subscription,
        bool $ensure = false,
        bool $onlyActiveSections = false,
    ): array {
        return [
            self::SHELL_HEADER => $this->page($subscription, self::SHELL_HEADER, $ensure, $onlyActiveSections),
            self::SHELL_FOOTER => $this->page($subscription, self::SHELL_FOOTER, $ensure, $onlyActiveSections),
        ];
    }

    public function page(
        Subscription $subscription,
        string $shell,
        bool $ensure = false,
        bool $onlyActiveSections = false,
    ): ?Page {
        $page = $this->pageQuery($subscription, $shell, $onlyActiveSections)->first();

        if (! $page instanceof Page && $ensure) {
            $this->ensureShellPages($subscription);
            $page = $this->pageQuery($subscription, $shell, $onlyActiveSections)->first();
        }

        return $page;
    }

    /**
     * @param  array<string, array<string, mixed>>  $blueprint
     * @return array{header:Page,footer:Page}
     */
    public function ensureShellPages(Subscription $subscription, array $blueprint = []): array
    {
        return [
            self::SHELL_HEADER => $this->ensureShellPage(
                $subscription,
                self::SHELL_HEADER,
                is_array($blueprint['site_header'] ?? null) ? $blueprint['site_header'] : null,
            ),
            self::SHELL_FOOTER => $this->ensureShellPage(
                $subscription,
                self::SHELL_FOOTER,
                is_array($blueprint['site_footer'] ?? null) ? $blueprint['site_footer'] : null,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $definition
     */
    protected function ensureShellPage(Subscription $subscription, string $shell, ?array $definition = null): Page
    {
        $existingPage = $this->pageQuery($subscription, $shell, false)->first();

        if ($existingPage instanceof Page) {
            return $existingPage;
        }

        return $this->createShellPage(
            $subscription,
            $shell,
            $definition ?? $this->defaultShellDefinition($subscription, $shell),
        );
    }

    protected function pageQuery(
        Subscription $subscription,
        string $shell,
        bool $onlyActiveSections = false,
    ): Builder {
        return Page::query()
            ->with([
                'translations',
                'sections' => function ($query) use ($onlyActiveSections) {
                    if ($onlyActiveSections) {
                        $query->where('is_active', true);
                    }

                    $query->orderBy('order');
                },
                'sections.translations',
            ])
            ->where('tenant_id', $subscription->getKey())
            ->where('context', $this->contextFor($shell))
            ->orderBy('id');
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function createShellPage(Subscription $subscription, string $shell, array $definition): Page
    {
        $page = Page::query()->create([
            'context' => $this->contextFor($shell),
            'tenant_id' => $subscription->getKey(),
            'builder_mode' => $definition['builder_mode'] ?? 'sections',
            'is_active' => (bool) ($definition['is_active'] ?? true),
            'is_home' => false,
            'published_at' => now(),
        ]);

        foreach ($this->normalizedPageTranslations($subscription, $shell, $definition) as $locale => $translation) {
            $page->translations()->create([
                'locale' => $locale,
                'slug' => $translation['slug'] ?? null,
                'title' => $translation['title'] ?? $this->defaultPageTitle($shell, $locale),
                'content' => null,
                'meta_title' => $translation['meta_title'] ?? null,
                'meta_description' => $translation['meta_description'] ?? null,
                'meta_keywords' => $translation['meta_keywords'] ?? null,
                'og_image' => $translation['og_image'] ?? null,
            ]);
        }

        foreach ($this->normalizedSections($subscription, $shell, $definition) as $index => $sectionData) {
            $section = $page->sections()->create([
                'tenant_id' => $subscription->getKey(),
                'type' => (string) ($sectionData['type'] ?? $this->defaultSectionType($shell)),
                'variant' => $sectionData['variant'] ?? null,
                'style' => is_array($sectionData['style'] ?? null) ? $sectionData['style'] : null,
                'order' => (int) ($sectionData['order'] ?? $sectionData['sort_order'] ?? ($index + 1)),
                'is_active' => (bool) ($sectionData['is_active'] ?? true),
            ]);

            foreach ($this->normalizedSectionTranslations($subscription, $shell, $sectionData) as $locale => $translation) {
                $section->translations()->create([
                    'tenant_id' => $subscription->getKey(),
                    'locale' => $locale,
                    'title' => $translation['title'] ?? null,
                    'content' => is_array($translation['content'] ?? null) ? $translation['content'] : [],
                ]);
            }
        }

        return $this->pageQuery($subscription, $shell, false)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, array<string, mixed>>
     */
    protected function normalizedPageTranslations(Subscription $subscription, string $shell, array $definition): array
    {
        $translations = $definition['translations'] ?? [];

        if (is_array($translations) && $translations !== []) {
            return $translations;
        }

        return $this->activeLocaleCodes()
            ->mapWithKeys(fn (string $locale) => [
                $locale => [
                    'slug' => null,
                    'title' => $this->defaultPageTitle($shell, $locale),
                ],
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<int, array<string, mixed>>
     */
    protected function normalizedSections(Subscription $subscription, string $shell, array $definition): array
    {
        $sections = $definition['sections'] ?? [];

        if (is_array($sections) && $sections !== []) {
            return array_values(array_filter($sections, fn ($section) => is_array($section)));
        }

        return [[
            'type' => $this->defaultSectionType($shell),
            'order' => 1,
            'is_active' => true,
            'translations' => $this->activeLocaleCodes()
                ->mapWithKeys(fn (string $locale) => [
                    $locale => [
                        'title' => $this->defaultSectionTitle($shell, $locale),
                        'content' => $this->defaultSectionContent($subscription, $shell, $locale),
                    ],
                ])
                ->all(),
        ]];
    }

    /**
     * @param  array<string, mixed>  $sectionData
     * @return array<string, array<string, mixed>>
     */
    protected function normalizedSectionTranslations(
        Subscription $subscription,
        string $shell,
        array $sectionData,
    ): array {
        $translations = $sectionData['translations'] ?? [];

        if (is_array($translations) && $translations !== []) {
            return $translations;
        }

        return $this->activeLocaleCodes()
            ->mapWithKeys(fn (string $locale) => [
                $locale => [
                    'title' => $this->defaultSectionTitle($shell, $locale),
                    'content' => $this->defaultSectionContent($subscription, $shell, $locale),
                ],
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultShellDefinition(Subscription $subscription, string $shell): array
    {
        return [
            'builder_mode' => 'sections',
            'is_active' => true,
            'translations' => $this->activeLocaleCodes()
                ->mapWithKeys(fn (string $locale) => [
                    $locale => [
                        'slug' => null,
                        'title' => $this->defaultPageTitle($shell, $locale),
                    ],
                ])
                ->all(),
            'sections' => [[
                'type' => $this->defaultSectionType($shell),
                'order' => 1,
                'is_active' => true,
                'translations' => $this->activeLocaleCodes()
                    ->mapWithKeys(fn (string $locale) => [
                        $locale => [
                            'title' => $this->defaultSectionTitle($shell, $locale),
                            'content' => $this->defaultSectionContent($subscription, $shell, $locale),
                        ],
                    ])
                    ->all(),
            ]],
        ];
    }

    protected function defaultSectionType(string $shell): string
    {
        return $shell === self::SHELL_FOOTER ? 'site_footer' : 'site_header';
    }

    protected function defaultPageTitle(string $shell, string $locale): string
    {
        $isArabic = str_starts_with(strtolower($locale), 'ar');

        return match ($shell) {
            self::SHELL_FOOTER => $isArabic ? 'فوتر الموقع' : 'Site Footer',
            default => $isArabic ? 'هيدر الموقع' : 'Site Header',
        };
    }

    protected function defaultSectionTitle(string $shell, string $locale): string
    {
        return $this->defaultPageTitle($shell, $locale);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultSectionContent(Subscription $subscription, string $shell, string $locale): array
    {
        $brandName = $this->defaultBrandName($subscription, $locale);
        $isArabic = str_starts_with(strtolower($locale), 'ar');

        if ($shell === self::SHELL_FOOTER) {
            return [
                'title' => $brandName,
                'description' => $isArabic
                    ? 'اكتب نبذة مختصرة عن مشروعك، وأضف وسائل التواصل الأساسية حتى يعرف الزائر كيف يصل إليك.'
                    : 'Add a short description of your business and the main contact details visitors need.',
                'contact_email' => trim((string) ($subscription->client?->email ?? '')),
                'contact_phone' => trim((string) ($subscription->client?->phone ?? '')),
                'copyright' => $isArabic
                    ? sprintf('© %s %s. جميع الحقوق محفوظة.', now()->year, $brandName)
                    : sprintf('© %s %s. All rights reserved.', now()->year, $brandName),
            ];
        }

        return [
            'title' => $brandName,
            'primary_button' => [
                'label' => $isArabic ? 'تواصل معنا' : 'Contact us',
                'url' => '#contact',
                'new_tab' => false,
            ],
        ];
    }

    protected function defaultBrandName(Subscription $subscription, string $locale): string
    {
        $subscription->loadMissing([
            'template.translations',
            'client',
        ]);

        $templateTranslation = $subscription->template?->translation($locale)
            ?? $subscription->template?->translation();

        $templateName = trim((string) ($templateTranslation?->name ?? $subscription->template?->name ?? ''));

        if ($templateName !== '') {
            return $templateName;
        }

        $companyName = trim((string) ($subscription->client?->company_name ?? ''));

        if ($companyName !== '') {
            return $companyName;
        }

        $clientName = trim(implode(' ', array_filter([
            $subscription->client?->first_name ?? '',
            $subscription->client?->last_name ?? '',
        ])));

        if ($clientName !== '') {
            return $clientName;
        }

        return str_starts_with(strtolower($locale), 'ar') ? 'موقعي' : 'My Website';
    }

    /**
     * @return Collection<int, string>
     */
    protected function activeLocaleCodes(): Collection
    {
        $codes = Language::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('code')
            ->filter(fn ($code) => filled($code))
            ->map(fn ($code) => strtolower(trim((string) $code)))
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return collect([strtolower((string) config('app.fallback_locale', 'en'))]);
        }

        return $codes;
    }
}
