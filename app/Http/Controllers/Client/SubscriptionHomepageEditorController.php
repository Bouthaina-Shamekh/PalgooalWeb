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

        $domainName = trim((string) ($this->workspaceSubscription->domain_name ?? ''));

        if ($domainName === '') {
            return route('client.subscriptions.site', $this->workspaceSubscription);
        }

        return Str::startsWith($domainName, ['http://', 'https://'])
            ? $domainName
            : (request()->isSecure() ? 'https://' : 'http://') . ltrim($domainName, '/');
    }

    protected function workspaceVisualBuilderUrl(Page $page): ?string
    {
        return '';
    }

    protected function workspaceBuilderModeUrl(Page $page): ?string
    {
        return '';
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
