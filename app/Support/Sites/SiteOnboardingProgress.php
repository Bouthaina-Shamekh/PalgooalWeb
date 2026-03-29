<?php

namespace App\Support\Sites;

use App\Models\Page;
use App\Models\Tenancy\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SiteOnboardingProgress
{
    public function for(Subscription $subscription): array
    {
        $baseline = $subscription->provisioned_at ?? $subscription->created_at ?? now();
        $pages = $this->tenantPages($subscription);
        $homepage = $pages->firstWhere('is_home', true) ?? $pages->first();

        $steps = [
            [
                'key' => 'edit_homepage',
                'label' => 'Edit homepage',
                'completed' => $this->homepageStepCompleted($homepage, $baseline),
                'url' => route('client.subscriptions.homepage-editor.index', $subscription),
            ],
            [
                'key' => 'manage_pages',
                'label' => 'Manage pages',
                'completed' => $this->pagesStepCompleted($pages, $baseline),
                'url' => route('client.subscriptions.pages', $subscription),
            ],
            [
                'key' => 'connect_domain',
                'label' => 'Connect domain',
                'completed' => $subscription->isCustomDomainVerified(),
                'url' => route('client.domains.index'),
            ],
        ];

        $currentStep = collect($steps)->first(fn (array $step) => ! $step['completed']);
        $currentStepKey = $currentStep['key'] ?? 'complete';

        $steps = array_map(function (array $step) use ($currentStepKey) {
            $step['active'] = $currentStepKey !== 'complete' && $step['key'] === $currentStepKey;

            return $step;
        }, $steps);

        $totalSteps = count($steps);
        $completedSteps = count(array_filter($steps, fn (array $step) => $step['completed']));
        $currentStepPosition = $currentStepKey === 'complete'
            ? $totalSteps
            : ((int) collect($steps)->search(fn (array $step) => $step['key'] === $currentStepKey) + 1);

        return [
            'current_step' => $currentStepKey,
            'current_step_position' => $currentStepPosition,
            'progress_percent' => (int) round(($completedSteps / max(1, $totalSteps)) * 100),
            'completed_steps' => $completedSteps,
            'total_steps' => $totalSteps,
            'steps' => $steps,
        ];
    }

    /**
     * @return Collection<int, Page>
     */
    protected function tenantPages(Subscription $subscription): Collection
    {
        return $subscription->canonicalPages()
            ->where('context', 'tenant')
            ->with([
                'translations:id,page_id,updated_at',
                'sections:id,page_id,updated_at',
                'sections.translations:id,section_id,updated_at',
            ])
            ->orderByDesc('is_home')
            ->orderBy('id')
            ->get();
    }

    protected function homepageStepCompleted(?Page $homepage, CarbonInterface $baseline): bool
    {
        if (! $homepage instanceof Page) {
            return false;
        }

        return $this->pageTouchedAfter($homepage, $baseline);
    }

    /**
     * We intentionally avoid treating template starter pages as completion.
     * This step is complete only when a non-home page shows activity after provisioning.
     *
     * @param Collection<int, Page> $pages
     */
    protected function pagesStepCompleted(Collection $pages, CarbonInterface $baseline): bool
    {
        return $pages->contains(function (Page $page) use ($baseline) {
            if ($page->is_home) {
                return false;
            }

            if ($page->created_at instanceof CarbonInterface && $page->created_at->gt($baseline)) {
                return true;
            }

            return $this->pageTouchedAfter($page, $baseline);
        });
    }

    protected function pageTouchedAfter(Page $page, CarbonInterface $baseline): bool
    {
        if ($page->updated_at instanceof CarbonInterface && $page->updated_at->gt($baseline)) {
            return true;
        }

        if ($page->translations->contains(fn ($translation) => $translation->updated_at instanceof CarbonInterface && $translation->updated_at->gt($baseline))) {
            return true;
        }

        return $page->sections->contains(function ($section) use ($baseline) {
            if ($section->updated_at instanceof CarbonInterface && $section->updated_at->gt($baseline)) {
                return true;
            }

            return $section->translations->contains(
                fn ($translation) => $translation->updated_at instanceof CarbonInterface && $translation->updated_at->gt($baseline)
            );
        });
    }
}
