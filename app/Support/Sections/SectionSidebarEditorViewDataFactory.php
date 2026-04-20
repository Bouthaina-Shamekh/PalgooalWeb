<?php

namespace App\Support\Sections;

use App\Models\Section;

class SectionSidebarEditorViewDataFactory
{
    public function build(
        Section $section,
        array $sectionTypes = [],
        string $workspaceMode = 'admin',
        string $workspaceRoutePrefix = 'dashboard.pages.sections.',
        array $workspaceRouteBaseParameters = [],
    ): array {
        $section->loadMissing('translations');

        $workspaceLabels = $this->workspaceLabels($workspaceMode === 'client');
        $currentLocale = app()->getLocale();
        $localizedSectionTranslation = method_exists($section, 'translation')
            ? $section->translation($currentLocale)
            : null;
        $displaySectionTranslation = $localizedSectionTranslation ?? $section->translations->first();
        $resolvedSectionTypeMeta = $section->resolvedTypeMeta($sectionTypes);
        $resolvedSectionTypeLabel = $resolvedSectionTypeMeta['label'];
        $sectionDisplayTitle = $displaySectionTranslation?->title ?: $resolvedSectionTypeLabel;
        $isSectionActive = (bool) $section->is_active;
        $sectionUpdateAction = route(
            $workspaceRoutePrefix . 'update',
            array_merge($workspaceRouteBaseParameters, ['section' => $section]),
            false,
        );

        return [
            'backNavigationLabel' => $workspaceLabels['back'],
            'editorDescription' => $workspaceLabels['description'],
            'saveButtonLabel' => $workspaceLabels['save'],
            'sectionTypeLabel' => $resolvedSectionTypeLabel,
            'sectionDisplayTitle' => $sectionDisplayTitle,
            'sectionStatusLabel' => $isSectionActive ? $workspaceLabels['activeStatus'] : __('Hidden'),
            'sectionStatusClass' => $isSectionActive
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-rose-50 text-rose-700',
            'formConfig' => [
                'formId' => 'sidebar-section-edit-form',
                'formAction' => $sectionUpdateAction,
                'saveAction' => $sectionUpdateAction,
                'formMethod' => 'POST',
                'formMethodSpoof' => 'PUT',
                'formClass' => 'space-y-0',
                'preventNativeSubmit' => true,
                'surfaceClass' => 'rounded-none border-b border-slate-200 bg-transparent shadow-none',
                'sectionHeaderClass' => 'border-b border-slate-200 px-4 py-3',
                'sectionBodyClass' => 'px-4 py-3',
                'settingsGridClass' => 'grid grid-cols-1 gap-3',
                'contentGridClass' => 'grid grid-cols-1 gap-3',
                'showOrderField' => false,
            ],
        ];
    }

    protected function workspaceLabels(bool $isClientWorkspace): array
    {
        return $isClientWorkspace
            ? [
                'back' => __('Back to blocks'),
                'description' => __('Edit this block here, then preview the page right away.'),
                'save' => __('Save Block Changes'),
                'activeStatus' => __('Visible'),
            ]
            : [
                'back' => __('Back to elements'),
                'description' => __('Update this section without leaving the workspace.'),
                'save' => __('Save Changes'),
                'activeStatus' => __('Active'),
            ];
    }
}
