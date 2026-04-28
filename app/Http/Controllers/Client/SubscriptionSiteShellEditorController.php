<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Admin\SectionController;
use App\Models\Page;
use App\Models\Section;
use App\Models\Sections\SectionDefinition;
use App\Support\Sections\ShellSectionEditorSupport;
use App\Models\Tenancy\Subscription;
use App\Services\Tenancy\TenantSiteShellService;
use Illuminate\Http\Request;

class SubscriptionSiteShellEditorController extends SectionController
{
    protected ?Subscription $workspaceSubscription = null;

    protected string $workspaceShell = TenantSiteShellService::SHELL_HEADER;

    public function __construct(
        protected TenantSiteShellService $shellService,
        protected ShellSectionEditorSupport $shellEditorSupport,
    ) {}

    public function headerIndex(Request $request, Subscription $subscription)
    {
        return parent::index($this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_HEADER));
    }

    public function headerPreview(Request $request, Subscription $subscription)
    {
        return parent::preview($request, $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_HEADER));
    }

    public function headerQuickStore(Request $request, Subscription $subscription)
    {
        return parent::quickStore($request, $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_HEADER));
    }

    public function headerReorder(Request $request, Subscription $subscription)
    {
        return parent::reorder($request, $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_HEADER));
    }

    public function headerEditorPanel(Request $request, Subscription $subscription, Section $section)
    {
        return parent::editor(
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_HEADER),
            $section,
        );
    }

    public function headerEdit(Request $request, Subscription $subscription, Section $section)
    {
        return parent::edit(
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_HEADER),
            $section,
        );
    }

    public function headerUpdate(Request $request, Subscription $subscription, Section $section)
    {
        return parent::update(
            $request,
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_HEADER),
            $section,
        );
    }

    public function headerToggleActive(Request $request, Subscription $subscription, Section $section)
    {
        return parent::toggleActive(
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_HEADER),
            $section,
        );
    }

    public function headerRename(Request $request, Subscription $subscription, Section $section)
    {
        return parent::rename(
            $request,
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_HEADER),
            $section,
        );
    }

    public function headerDuplicate(Request $request, Subscription $subscription, Section $section)
    {
        return parent::duplicate(
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_HEADER),
            $section,
        );
    }

    public function headerDestroy(Request $request, Subscription $subscription, Section $section)
    {
        return parent::destroy(
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_HEADER),
            $section,
        );
    }

    public function footerIndex(Request $request, Subscription $subscription)
    {
        return parent::index($this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_FOOTER));
    }

    public function footerPreview(Request $request, Subscription $subscription)
    {
        return parent::preview($request, $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_FOOTER));
    }

    public function footerQuickStore(Request $request, Subscription $subscription)
    {
        return parent::quickStore($request, $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_FOOTER));
    }

    public function footerReorder(Request $request, Subscription $subscription)
    {
        return parent::reorder($request, $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_FOOTER));
    }

    public function footerEditorPanel(Request $request, Subscription $subscription, Section $section)
    {
        return parent::editor(
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_FOOTER),
            $section,
        );
    }

    public function footerEdit(Request $request, Subscription $subscription, Section $section)
    {
        return parent::edit(
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_FOOTER),
            $section,
        );
    }

    public function footerUpdate(Request $request, Subscription $subscription, Section $section)
    {
        return parent::update(
            $request,
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_FOOTER),
            $section,
        );
    }

    public function footerToggleActive(Request $request, Subscription $subscription, Section $section)
    {
        return parent::toggleActive(
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_FOOTER),
            $section,
        );
    }

    public function footerRename(Request $request, Subscription $subscription, Section $section)
    {
        return parent::rename(
            $request,
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_FOOTER),
            $section,
        );
    }

    public function footerDuplicate(Request $request, Subscription $subscription, Section $section)
    {
        return parent::duplicate(
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_FOOTER),
            $section,
        );
    }

    public function footerDestroy(Request $request, Subscription $subscription, Section $section)
    {
        return parent::destroy(
            $this->resolveOwnedShellPage($request, $subscription, TenantSiteShellService::SHELL_FOOTER),
            $section,
        );
    }

    protected function workspaceRoutePrefix(): string
    {
        return $this->workspaceShell === TenantSiteShellService::SHELL_FOOTER
            ? 'client.subscriptions.site-footer-editor.'
            : 'client.subscriptions.site-header-editor.';
    }

    protected function workspaceMode(): string
    {
        return 'client';
    }

    protected function workspaceModeLabel(): ?string
    {
        return $this->workspaceShell === TenantSiteShellService::SHELL_FOOTER
            ? __('Client site footer editor')
            : __('Client site header editor');
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
            return route('client.subscriptions.site', $this->workspaceSubscription);
        }

        return parent::workspaceShellBackUrl($page);
    }

    protected function workspaceShellBackLabel(): string
    {
        return __('Back to site dashboard');
    }

    protected function editorFormPartial(Page $page, Section $section): string
    {
        return 'dashboard.pages.sections.partials.shell-editor-form';
    }

    protected function workspaceFrontUrl(Page $page): string
    {
        if (! $this->workspaceSubscription instanceof Subscription) {
            return parent::workspaceFrontUrl($page);
        }

        return $this->workspaceSubscription->activeSiteUrl()
            ?? route('client.subscriptions.site', $this->workspaceSubscription);
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
            'workspaceContentLabel' => $this->workspaceShell === TenantSiteShellService::SHELL_FOOTER
                ? __('You are editing your site footer')
                : __('You are editing your site header'),
            'workspacePageSwitcher' => $this->workspacePageSwitcherData($page),
        ]);
    }

    /**
     * Shell editor legacy compatibility only.
     */
    protected function availableSectionTypes(): array
    {
        return $this->shellEditorSupport->availableSectionTypes($this->workspaceShell);
    }

    protected function resolveOwnedShellPage(Request $request, Subscription $subscription, string $shell): Page
    {
        $client = $request->user('client');

        abort_unless($client && (int) $subscription->client_id === (int) $client->id, 403);

        $this->workspaceSubscription = $subscription;
        $this->workspaceShell = $shell;

        $page = $this->shellService->page($subscription, $shell, true, false);

        abort_if(! $page instanceof Page, 404);

        return $page;
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
            ->map(function (Page $tenantPage) {
                $translation = $tenantPage->translation();
                $label = trim((string) ($translation?->title ?? ''));

                if ($label === '') {
                    $label = $tenantPage->is_home ? __('Homepage') : __('Untitled page');
                }

                return [
                    'id' => $tenantPage->getKey(),
                    'label' => $label,
                    'is_home' => (bool) $tenantPage->is_home,
                    'active' => false,
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
            'active' => $this->workspaceShell === TenantSiteShellService::SHELL_HEADER,
            'url' => route('client.subscriptions.site-header-editor.index', ['subscription' => $this->workspaceSubscription]),
        ];

        $pages[] = [
            'id' => 'site-footer',
            'label' => __('Site Footer'),
            'is_home' => false,
            'active' => $this->workspaceShell === TenantSiteShellService::SHELL_FOOTER,
            'url' => route('client.subscriptions.site-footer-editor.index', ['subscription' => $this->workspaceSubscription]),
        ];

        return [
            'label' => __('Page'),
            'pages' => $pages,
            'pages_index_url' => route('client.subscriptions.pages', $this->workspaceSubscription),
        ];
    }

    protected function allowedSectionTypeKeys(?Section $currentSection = null): array
    {
        return array_keys($this->availableSectionTypes());
    }

    protected function workspaceSectionTypes(): array
    {
        return $this->availableSectionTypes();
    }

    protected function sectionTypesForSection(Section $section): array
    {
        return $this->availableSectionTypes();
    }

    protected function sectionDefinitionIdRulesForCreate(): array
    {
        return ['nullable'];
    }

    protected function sectionDefinitionIdRulesForUpdate(Section $section): array
    {
        return ['nullable'];
    }

    protected function buildEditorState(Section $section, iterable $languages, array $sectionTypes): array
    {
        return $this->shellEditorSupport->buildEditorState($section, $languages, $sectionTypes);
    }

    protected function normalizeSubmittedTranslations(
        string $type,
        array $translations,
        ?SectionDefinition $sectionDefinition = null,
    ): array {
        return $this->shellEditorSupport->normalizeTranslations($type, $translations);
    }

    protected function defaultContentForSection(
        string $type,
        string $pageTitle,
        ?SectionDefinition $sectionDefinition = null,
    ): array {
        return $sectionDefinition instanceof SectionDefinition
            ? parent::defaultContentForSection($type, $pageTitle, $sectionDefinition)
            : $this->shellEditorSupport->defaultContent($type);
    }

    protected function defaultStyleForSection(string $type, ?SectionDefinition $sectionDefinition = null): array
    {
        return $sectionDefinition instanceof SectionDefinition
            ? parent::defaultStyleForSection($type, $sectionDefinition)
            : $this->shellEditorSupport->defaultStyle($type);
    }

    protected function resolveLinkedSectionDefinitionForUpdate(
        Section $section,
        ?int $sectionDefinitionId = null,
    ): ?SectionDefinition {
        return null;
    }
}
