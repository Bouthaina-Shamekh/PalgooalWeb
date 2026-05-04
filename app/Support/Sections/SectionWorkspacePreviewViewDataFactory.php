<?php

namespace App\Support\Sections;

use App\Models\Page;
use App\Models\Section;
use App\Models\Tenancy\Subscription;
use App\Services\Tenancy\TenantSiteShellService;
use Illuminate\Support\Collection;

class SectionWorkspacePreviewViewDataFactory
{
    public function __construct(protected TenantSiteShellService $tenantSiteShellService) {}

    public function build(
        Page $page,
        iterable $sections,
        array $sectionTypes = [],
        iterable $previewTemplates = [],
        int $highlightSectionId = 0,
    ): array {
        $page->loadMissing(['translations', 'tenant', 'subscription']);

        $previewSections = Collection::make($sections)
            ->filter(fn ($section) => $section instanceof Section)
            ->values();
        $pageContext = (string) ($page->context ?? '');
        $isTenantShellPreview = $this->tenantSiteShellService->isShellContext($pageContext);
        $isTenantPagePreview = $pageContext === 'tenant';
        $tenantPreviewData = $this->resolveTenantPreviewData($page, $isTenantPagePreview);
        $pageTitle = $this->resolvePageTitle($page);

        return [
            'seo' => [
                'title' => $pageTitle . ' - ' . __('Sections Preview'),
                'description' => __('Live preview for the sections workspace.'),
                'canonical' => url()->current(),
                'type' => 'website',
            ],
            'stylesheetUrl' => asset('assets/dashboard/css/sections-preview.css'),
            'scriptUrl' => asset('assets/dashboard/js/sections-preview-frame.js'),
            'showFrontChrome' => ! $isTenantShellPreview && ! $isTenantPagePreview,
            'isTenantPagePreview' => $isTenantPagePreview,
            'highlightSectionId' => $highlightSectionId,
            'previewBlocks' => $this->buildPreviewBlocks($previewSections, $highlightSectionId),
            'sectionTypes' => $sectionTypes,
            'previewTemplates' => Collection::make($previewTemplates)->values(),
            'tenantHeaderRenderData' => $tenantPreviewData['header'],
            'tenantFooterRenderData' => $tenantPreviewData['footer'],
            'emptyStateTitle' => __('No sections to preview yet'),
            'emptyStateDescription' => __('Add a section from the workspace library to start the live preview.'),
        ];
    }

    protected function resolvePageTitle(Page $page): string
    {
        $pageTranslation = method_exists($page, 'translation') ? $page->translation() : null;

        return $pageTranslation?->title ?? $page->slug ?? ('#' . $page->id);
    }

    /**
     * @return array{header: array<string, mixed>, footer: array<string, mixed>}
     */
    protected function resolveTenantPreviewData(Page $page, bool $isTenantPagePreview): array
    {
        $tenantSubscription = $this->resolveTenantSubscription($page);
        $tenantNavigationPages = collect();
        $tenantHeaderPage = null;
        $tenantFooterPage = null;

        if ($isTenantPagePreview && $tenantSubscription instanceof Subscription) {
            $tenantNavigationPages = $tenantSubscription->canonicalPages()
                ->with('translations')
                ->where('context', 'tenant')
                ->where('is_active', true)
                ->orderByDesc('is_home')
                ->orderBy('id')
                ->get();

            $tenantShellPages = $this->tenantSiteShellService->pages(
                $tenantSubscription,
                ensure: false,
                onlyActiveSections: true,
            );

            $tenantHeaderPage = $tenantShellPages[TenantSiteShellService::SHELL_HEADER] ?? null;
            $tenantFooterPage = $tenantShellPages[TenantSiteShellService::SHELL_FOOTER] ?? null;
        }

        return [
            'header' => $this->buildTenantRenderData($tenantHeaderPage, $tenantSubscription, $tenantNavigationPages),
            'footer' => $this->buildTenantRenderData($tenantFooterPage, $tenantSubscription, $tenantNavigationPages),
        ];
    }

    protected function resolveTenantSubscription(Page $page): ?Subscription
    {
        if ($page->tenant_id !== null && $page->tenant instanceof Subscription) {
            return $page->tenant;
        }

        if ($page->subscription_id !== null && $page->subscription instanceof Subscription) {
            return $page->subscription;
        }

        return null;
    }

    /**
     * @param  Collection<int, mixed>  $siteNavigationPages
     * @return array{page:?Page,sections:Collection<int, mixed>,subscription:?Subscription,siteNavigationPages:Collection<int, mixed>}
     */
    protected function buildTenantRenderData(
        ?Page $page,
        ?Subscription $subscription,
        Collection $siteNavigationPages,
    ): array {
        return [
            'page' => $page,
            'sections' => $page?->sections ?? collect(),
            'subscription' => $subscription,
            'siteNavigationPages' => $siteNavigationPages,
        ];
    }

    /**
     * @param  Collection<int, Section>  $sections
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildPreviewBlocks(Collection $sections, int $highlightSectionId): Collection
    {
        $hiddenStateLabel = __('Hidden');

        return $sections->map(function (Section $section) use ($highlightSectionId, $hiddenStateLabel) {
            $isHidden = ! (bool) $section->is_active;

            return [
                'id' => (int) $section->id,
                'domId' => 'preview-section-' . $section->id,
                'section' => $section,
                'isHidden' => $isHidden,
                'hiddenStateLabel' => $hiddenStateLabel,
                'containerClass' => trim(
                    'sections-preview-block'
                    . ($highlightSectionId === (int) $section->id ? ' is-highlighted' : '')
                    . ($isHidden ? ' is-hidden' : ''),
                ),
            ];
        });
    }
}
