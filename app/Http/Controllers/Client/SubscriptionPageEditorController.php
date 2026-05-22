<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Admin\SectionController;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Section;
use App\Models\Tenancy\Subscription;
use App\Support\Tenancy\TenantThemeSettings;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionPageEditorController extends SectionController
{
    protected ?Subscription $workspaceSubscription = null;

    public function pages(Request $request, Subscription $subscription)
    {
        $subscription = $this->resolveOwnedSubscription($request, $subscription);
        $subscription->load([
            'plan',
            'template.translations',
        ]);

        $locale = app()->getLocale();
        $pages = $this->tenantPagesQuery($subscription)
            ->with([
                'translations',
                'sections' => function ($query) {
                    $query->orderBy('order');
                },
                'sections.translations',
            ])
            ->orderByDesc('is_home')
            ->orderBy('id')
            ->get();

        return view('client.subscriptions.pages', [
            'subscription' => $subscription,
            'locale' => $locale,
            'pages' => $pages,
        ]);
    }

    public function storePage(Request $request, Subscription $subscription)
    {
        $subscription = $this->resolveOwnedSubscription($request, $subscription);
        $locale = $this->creationLocale();

        $validated = $request->validateWithBag('createPage', [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $page = DB::transaction(function () use ($subscription, $validated, $locale) {
            $title = trim((string) $validated['title']);
            $slug = $this->makeUniqueSlug(
                subscription: $subscription,
                locale: $locale,
                requestedSlug: $validated['slug'] ?? null,
                fallbackTitle: $title,
            );

            $page = Page::query()->create([
                'context' => 'tenant',
                'tenant_id' => $subscription->getKey(),
                'builder_mode' => 'sections',
                'is_active' => true,
                'is_home' => false,
                'published_at' => now(),
            ]);

            $page->translations()->create([
                'locale' => $locale,
                'title' => $title,
                'slug' => $slug,
            ]);

            return $page;
        });

        return redirect()
            ->route('client.subscriptions.pages.editor.index', [
                'subscription' => $subscription,
                'page' => $page,
            ])
            ->with('success', __('Your new page is ready to edit.'));
    }

    public function updatePageSettings(Request $request, Subscription $subscription, Page $page)
    {
        $subscription = $this->resolveOwnedSubscription($request, $subscription);
        $page = $this->resolveOwnedPage($request, $subscription, $page);
        $locale = $this->creationLocale();

        $validated = $request->validateWithBag('pageSettings', [
            'page_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        abort_unless((int) $validated['page_id'] === (int) $page->getKey(), 403);

        DB::transaction(function () use ($page, $subscription, $validated, $locale) {
            $title = trim((string) $validated['title']);
            $slug = $this->makeUniqueSlug(
                subscription: $subscription,
                locale: $locale,
                requestedSlug: $validated['slug'] ?? null,
                fallbackTitle: $title,
                ignorePageId: $page->getKey(),
            );

            $page->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $title,
                    'slug' => $slug,
                ]
            );
        });

        return redirect()
            ->route('client.subscriptions.pages', $subscription)
            ->with('success', __('Page settings updated successfully.'));
    }

    public function setHomePage(Request $request, Subscription $subscription, Page $page)
    {
        $subscription = $this->resolveOwnedSubscription($request, $subscription);
        $page = $this->resolveOwnedPage($request, $subscription, $page);

        DB::transaction(function () use ($subscription, $page) {
            $this->tenantPagesQuery($subscription)->update(['is_home' => false]);
            $page->forceFill(['is_home' => true])->save();
        });

        return redirect()
            ->route('client.subscriptions.pages', $subscription)
            ->with('success', __('Homepage updated successfully.'));
    }

    public function destroyPage(Request $request, Subscription $subscription, Page $page)
    {
        $subscription = $this->resolveOwnedSubscription($request, $subscription);
        $page = $this->resolveOwnedPage($request, $subscription, $page);

        $pages = $this->tenantPagesQuery($subscription)
            ->orderByDesc('is_home')
            ->orderBy('id')
            ->get();

        if ($pages->count() <= 1) {
            return redirect()
                ->route('client.subscriptions.pages', $subscription)
                ->with('error', __('You need to keep at least one page on your site.'));
        }

        DB::transaction(function () use ($subscription, $page, $pages) {
            if ($page->is_home) {
                $replacement = $pages->first(function (Page $candidate) use ($page) {
                    return (int) $candidate->getKey() !== (int) $page->getKey();
                });

                if ($replacement instanceof Page) {
                    $this->tenantPagesQuery($subscription)->update(['is_home' => false]);
                    $replacement->forceFill(['is_home' => true])->save();
                }
            }

            $page->delete();
        });

        return redirect()
            ->route('client.subscriptions.pages', $subscription)
            ->with('success', __('Page deleted successfully.'));
    }

    public function pageIndex(Request $request, Subscription $subscription, Page $page)
    {
        return parent::index($this->resolveOwnedPage($request, $subscription, $page));
    }

    public function pagePreview(Request $request, Subscription $subscription, Page $page)
    {
        return parent::preview($request, $this->resolveOwnedPage($request, $subscription, $page));
    }

    public function pageQuickStore(Request $request, Subscription $subscription, Page $page)
    {
        return parent::quickStore($request, $this->resolveOwnedPage($request, $subscription, $page));
    }

    public function pageReorder(Request $request, Subscription $subscription, Page $page)
    {
        return parent::reorder($request, $this->resolveOwnedPage($request, $subscription, $page));
    }

    public function pageEditorPanel(Request $request, Subscription $subscription, Page $page, Section $section)
    {
        return parent::editor($this->resolveOwnedPage($request, $subscription, $page), $section);
    }

    public function pageEdit(Request $request, Subscription $subscription, Page $page, Section $section)
    {
        return parent::edit($this->resolveOwnedPage($request, $subscription, $page), $section);
    }

    public function pageUpdate(Request $request, Subscription $subscription, Page $page, Section $section)
    {
        return parent::update($request, $this->resolveOwnedPage($request, $subscription, $page), $section);
    }

    public function pageToggleActive(Request $request, Subscription $subscription, Page $page, Section $section)
    {
        return parent::toggleActive($this->resolveOwnedPage($request, $subscription, $page), $section);
    }

    public function pageRename(Request $request, Subscription $subscription, Page $page, Section $section)
    {
        return parent::rename($request, $this->resolveOwnedPage($request, $subscription, $page), $section);
    }

    public function pageDuplicate(Request $request, Subscription $subscription, Page $page, Section $section)
    {
        return parent::duplicate($this->resolveOwnedPage($request, $subscription, $page), $section);
    }

    public function pageDestroy(Request $request, Subscription $subscription, Page $page, Section $section)
    {
        return parent::destroy($this->resolveOwnedPage($request, $subscription, $page), $section);
    }

    protected function workspaceRoutePrefix(): string
    {
        return 'client.subscriptions.pages.editor.';
    }

    protected function workspaceMode(): string
    {
        return 'client';
    }

    protected function workspaceModeLabel(): ?string
    {
        return __('Client page editor');
    }

    protected function workspaceBaseRouteParameters(Page $page): array
    {
        if ($this->workspaceSubscription instanceof Subscription) {
            return [
                'subscription' => $this->workspaceSubscription,
                'page' => $page,
            ];
        }

        return parent::workspaceBaseRouteParameters($page);
    }

    protected function workspaceShellBackUrl(Page $page): string
    {
        if ($this->workspaceSubscription instanceof Subscription) {
            return route('client.subscriptions.pages', $this->workspaceSubscription);
        }

        return parent::workspaceShellBackUrl($page);
    }

    protected function workspaceShellBackLabel(): string
    {
        return __('Back to pages');
    }

    protected function workspaceFrontUrl(Page $page): string
    {
        if (! $this->workspaceSubscription instanceof Subscription) {
            return parent::workspaceFrontUrl($page);
        }

        $baseUrl = $this->workspaceSubscription->activeSiteUrl();

        if ($baseUrl === null) {
            return route('client.subscriptions.pages', $this->workspaceSubscription);
        }

        $baseUrl = rtrim($baseUrl, '/');

        $translation = $page->translation();
        $slug = trim((string) ($translation?->slug ?? ''), '/');

        if ($page->is_home || $slug === '') {
            return $baseUrl;
        }

        return $baseUrl . '/' . $slug;
    }

    protected function workspaceVisualBuilderUrl(Page $page): ?string
    {
        return '';
    }

    protected function workspaceBuilderModeUrl(Page $page): ?string
    {
        return '';
    }

    protected function workspaceViewData(Page $page): array
    {
        return array_merge(parent::workspaceViewData($page), [
            // Explicit: $workspaceSubscription is always known in this controller,
            // so we pass it directly rather than relying on the page->tenant fallback.
            'activeThemeSubscription' => $this->workspaceSubscription,
            'brandSettingsUpdateUrl' => $this->workspaceBrandSettingsUpdateUrl($this->workspaceSubscription),
            'brandSettingsTheme' => $this->workspaceSubscription !== null
                ? TenantThemeSettings::fromArray(
                    is_array($this->workspaceSubscription->theme_settings) ? $this->workspaceSubscription->theme_settings : []
                )
                : null,
            'workspacePageSwitcher' => $this->workspacePageSwitcherData($page),
        ]);
    }

    protected function workspaceBrandSettingsUpdateUrl(?Subscription $subscription): ?string
    {
        if (! $this->workspaceSubscription instanceof Subscription) {
            return null;
        }

        return route('client.subscriptions.brand-settings.update', $this->workspaceSubscription);
    }

    /**
     * @return array<string, mixed>
     */
    protected function workspacePageSwitcherData(Page $currentPage): array
    {
        if (! $this->workspaceSubscription instanceof Subscription) {
            return [];
        }

        $pages = $this->workspaceSubscription->canonicalPages()
            ->with('translations')
            ->where('context', 'tenant')
            ->orderByDesc('is_home')
            ->orderBy('id')
            ->get()
            ->map(function (Page $tenantPage) use ($currentPage) {
                $translation = $tenantPage->translation();
                $label = trim((string) ($translation?->title ?? ''));

                if ($label === '') {
                    $label = $tenantPage->is_home ? __('Homepage') : __('Untitled page');
                }

                return [
                    'id' => $tenantPage->getKey(),
                    'label' => $label,
                    'is_home' => (bool) $tenantPage->is_home,
                    'active' => (int) $tenantPage->getKey() === (int) $currentPage->getKey(),
                    'url' => $tenantPage->is_home
                        ? route('client.subscriptions.homepage-editor.index', ['subscription' => $this->workspaceSubscription])
                        : route('client.subscriptions.pages.editor.index', [
                            'subscription' => $this->workspaceSubscription,
                            'page' => $tenantPage,
                        ]),
                ];
            })
            ->values()
            ->all();

        $pages[] = [
            'id' => 'site-header',
            'label' => __('Site Header'),
            'is_home' => false,
            'active' => false,
            'url' => route('client.subscriptions.site-header-editor.index', ['subscription' => $this->workspaceSubscription]),
        ];

        $pages[] = [
            'id' => 'site-footer',
            'label' => __('Site Footer'),
            'is_home' => false,
            'active' => false,
            'url' => route('client.subscriptions.site-footer-editor.index', ['subscription' => $this->workspaceSubscription]),
        ];

        return [
            'label' => __('Page'),
            'pages' => $pages,
            'pages_index_url' => route('client.subscriptions.pages', $this->workspaceSubscription),
        ];
    }

    protected function resolveOwnedSubscription(Request $request, Subscription $subscription): Subscription
    {
        $client = $request->user('client');

        abort_unless($client && (int) $subscription->client_id === (int) $client->id, 403);

        $this->workspaceSubscription = $subscription;

        return $subscription;
    }

    protected function resolveOwnedPage(Request $request, Subscription $subscription, Page $page): Page
    {
        $subscription = $this->resolveOwnedSubscription($request, $subscription);

        $resolvedPage = $this->tenantPagesQuery($subscription)
            ->with('translations')
            ->whereKey($page->getKey())
            ->first();

        abort_if(! $resolvedPage instanceof Page, 404);

        return $resolvedPage;
    }

    protected function tenantPagesQuery(Subscription $subscription): HasMany
    {
        return $subscription->canonicalPages()
            ->where('context', 'tenant');
    }

    protected function creationLocale(): string
    {
        $locale = strtolower(trim((string) app()->getLocale()));

        return $locale !== '' ? $locale : strtolower((string) config('app.fallback_locale', 'ar'));
    }

    protected function makeUniqueSlug(
        Subscription $subscription,
        string $locale,
        ?string $requestedSlug,
        string $fallbackTitle,
        int|string|null $ignorePageId = null
    ): string {
        $baseSlug = $this->normalizeSlug($requestedSlug ?: $fallbackTitle);
        $candidate = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($candidate, $locale, $subscription, $ignorePageId)) {
            $candidate = $this->normalizeSlug(sprintf('%s-%d', $baseSlug, $suffix));
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Check whether a slug already exists for a given locale, scoped to the
     * subscription's tenant pages only (not globally across all tenants).
     */
    protected function slugExists(
        string $slug,
        string $locale,
        Subscription $subscription,
        int|string|null $ignorePageId = null
    ): bool {
        $query = PageTranslation::query()
            ->join('pages', 'pages.id', '=', 'page_translations.page_id')
            ->where('page_translations.locale', $locale)
            ->where('page_translations.slug', $slug)
            ->where('pages.tenant_id', $subscription->getKey());

        if ($ignorePageId !== null) {
            $query->where('page_translations.page_id', '!=', $ignorePageId);
        }

        return $query->exists();
    }

    protected function normalizeSlug(?string $value): string
    {
        $slug = trim((string) $value);
        $slug = preg_replace('/[\s_]+/u', '-', $slug) ?? $slug;
        $slug = trim($slug, "-/ \t\n\r\0\x0B");

        return $slug !== '' ? $slug : 'page';
    }
}
    