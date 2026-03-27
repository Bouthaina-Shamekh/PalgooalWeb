<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Admin\SectionController;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Section;
use App\Models\Tenancy\Subscription;
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

        $validated = $request->validate([
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

        $domainName = trim((string) ($this->workspaceSubscription->domain_name ?? ''));

        if ($domainName === '') {
            return route('client.subscriptions.pages', $this->workspaceSubscription);
        }

        $baseUrl = Str::startsWith($domainName, ['http://', 'https://'])
            ? rtrim($domainName, '/')
            : rtrim((request()->isSecure() ? 'https://' : 'http://') . ltrim($domainName, '/'), '/');

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
        string $fallbackTitle
    ): string {
        $baseSlug = $this->normalizeSlug($requestedSlug ?: $fallbackTitle);
        $candidate = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($candidate, $locale)) {
            $candidate = $this->normalizeSlug(sprintf('%s-%d', $baseSlug, $suffix));
            $suffix++;
        }

        return $candidate;
    }

    protected function slugExists(string $slug, string $locale): bool
    {
        return PageTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->exists();
    }

    protected function normalizeSlug(?string $value): string
    {
        $slug = trim((string) $value);
        $slug = preg_replace('/[\s_]+/u', '-', $slug) ?? $slug;
        $slug = trim($slug, "-/ \t\n\r\0\x0B");

        return $slug !== '' ? $slug : 'page';
    }
}
