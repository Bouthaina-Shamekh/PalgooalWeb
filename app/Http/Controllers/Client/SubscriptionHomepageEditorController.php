<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Admin\SectionController;
use App\Models\Page;
use App\Models\Section;
use App\Models\Tenancy\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionHomepageEditorController extends SectionController
{
    protected ?Subscription $workspaceSubscription = null;

    public function homepageIndex(Request $request, Subscription $subscription)
    {
        return parent::index($this->resolveOwnedHomepage($request, $subscription));
    }

    public function homepagePreview(Request $request, Subscription $subscription)
    {
        return parent::preview($request, $this->resolveOwnedHomepage($request, $subscription));
    }

    public function homepageQuickStore(Request $request, Subscription $subscription)
    {
        return parent::quickStore($request, $this->resolveOwnedHomepage($request, $subscription));
    }

    public function homepageReorder(Request $request, Subscription $subscription)
    {
        return parent::reorder($request, $this->resolveOwnedHomepage($request, $subscription));
    }

    public function homepageEditorPanel(Request $request, Subscription $subscription, Section $section)
    {
        $page = $this->resolveOwnedHomepage($request, $subscription);

        return parent::editor($page, $section);
    }

    public function homepageEdit(Request $request, Subscription $subscription, Section $section)
    {
        $page = $this->resolveOwnedHomepage($request, $subscription);

        return parent::edit($page, $section);
    }

    public function homepageUpdate(Request $request, Subscription $subscription, Section $section)
    {
        $page = $this->resolveOwnedHomepage($request, $subscription);

        return parent::update($request, $page, $section);
    }

    public function homepageToggleActive(Request $request, Subscription $subscription, Section $section)
    {
        $page = $this->resolveOwnedHomepage($request, $subscription);

        return parent::toggleActive($page, $section);
    }

    public function homepageRename(Request $request, Subscription $subscription, Section $section)
    {
        $page = $this->resolveOwnedHomepage($request, $subscription);

        return parent::rename($request, $page, $section);
    }

    public function homepageDuplicate(Request $request, Subscription $subscription, Section $section)
    {
        $page = $this->resolveOwnedHomepage($request, $subscription);

        return parent::duplicate($page, $section);
    }

    public function homepageDestroy(Request $request, Subscription $subscription, Section $section)
    {
        $page = $this->resolveOwnedHomepage($request, $subscription);

        return parent::destroy($page, $section);
    }

    protected function workspaceRoutePrefix(): string
    {
        return 'client.subscriptions.homepage-editor.';
    }

    protected function workspaceMode(): string
    {
        return 'client';
    }

    protected function workspaceModeLabel(): ?string
    {
        return __('Client homepage editor');
    }

    protected function workspaceBaseRouteParameters(Page $page): array
    {
        if ($this->workspaceSubscription instanceof Subscription) {
            return ['subscription' => $this->workspaceSubscription];
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
        return __('Back to content');
    }

    protected function workspaceFrontUrl(Page $page): string
    {
        if (! $this->workspaceSubscription instanceof Subscription) {
            return parent::workspaceFrontUrl($page);
        }

        $activeSiteUrl = $this->workspaceSubscription->activeSiteUrl();

        if ($activeSiteUrl === null) {
            return route('client.subscriptions.site', $this->workspaceSubscription);
        }

        return $activeSiteUrl;
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
            'workspacePageSwitcher' => $this->workspacePageSwitcherData($page),
        ]);
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

    protected function resolveOwnedHomepage(Request $request, Subscription $subscription): Page
    {
        $client = $request->user('client');

        abort_unless($client && (int) $subscription->client_id === (int) $client->id, 403);

        $this->workspaceSubscription = $subscription;

        $page = $subscription->canonicalPages()
            ->with('translations')
            ->where('context', 'tenant')
            ->orderByDesc('is_home')
            ->orderBy('id')
            ->first();

        abort_if(! $page instanceof Page, 404);

        return $page;
    }
}
