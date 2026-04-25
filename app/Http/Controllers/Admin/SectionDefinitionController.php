<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectionDefinitionRequest;
use App\Http\Requests\Admin\UpdateSectionDefinitionRequest;
use App\Models\SectionTranslation;
use App\Models\Sections\SectionDefinition;
use App\Models\Sections\Template as SectionTemplate;
use App\Support\Sections\SectionCustomPresetRegistry;
use App\Support\Sections\SectionMediaPreviewBuilder;
use App\Support\Sections\SectionTemplateRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class SectionDefinitionController extends Controller
{
    /**
     * List section blueprint definitions for admin/developer management.
     */
    public function index(): View
    {
        $this->authorize('viewAny', SectionDefinition::class);

        $sectionDefinitions = SectionDefinition::query()
            ->with(['templates' => fn ($query) => $query->orderByPivot('sort_order')->orderBy('id')])
            ->withCount('fields')
            ->withCount('sections')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20);

        $templateRegistry = SectionTemplateRegistry::all();

        return view('dashboard.section_definitions.index', compact('sectionDefinitions', 'templateRegistry'));
    }

    /**
     * Show the create form for a new section definition.
     */
    public function create(): View
    {
        $this->authorize('create', SectionDefinition::class);

        $sectionDefinition = new SectionDefinition([
            'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
            'is_active' => true,
            'is_visible' => true,
            'sort_order' => 0,
        ]);

        return view('dashboard.section_definitions.create', $this->formViewData($sectionDefinition));
    }

    /**
     * Store a newly created section definition.
     */
    public function store(StoreSectionDefinitionRequest $request): RedirectResponse
    {
        $this->authorize('create', SectionDefinition::class);

        $validated = $request->validated();
        $sectionDefinition = null;

        DB::transaction(function () use ($validated, &$sectionDefinition) {
            $sectionDefinition = SectionDefinition::create($this->persistableAttributes($validated));

            $this->syncTemplateSelection($sectionDefinition, $validated['template_key'] ?? null);
        });

        if (! $sectionDefinition instanceof SectionDefinition) {
            return redirect()
                ->route('dashboard.section_definitions.index')
                ->with('error', __('Section definition could not be created.'));
        }

        return $this->redirectAfterSave($sectionDefinition, (string) $request->input('after_save', 'fields'), true);
    }

    /**
     * Show the edit form for an existing section definition.
     */
    public function edit(SectionDefinition $sectionDefinition): View
    {
        $this->authorize('edit', $sectionDefinition);

        $sectionDefinition->load(['templates' => fn ($query) => $query->orderByPivot('sort_order')->orderBy('id')]);

        return view('dashboard.section_definitions.edit', $this->formViewData($sectionDefinition));
    }

    /**
     * Update the base section definition record.
     */
    public function update(
        UpdateSectionDefinitionRequest $request,
        SectionDefinition $sectionDefinition,
    ): RedirectResponse {
        $this->authorize('edit', $sectionDefinition);

        $validated = $request->validated();

        DB::transaction(function () use ($sectionDefinition, $validated) {
            $sectionDefinition->update($this->persistableAttributes($validated));

            $this->syncTemplateSelection($sectionDefinition, $validated['template_key'] ?? null);
        });

        return $this->redirectAfterSave($sectionDefinition, (string) $request->input('after_save', 'edit'));
    }

    /**
     * Delete a developer section definition and only its database-owned
     * definition-driven content. Renderer files, media records, uploads, and
     * config registry entries are intentionally untouched.
     */
    public function destroy(SectionDefinition $sectionDefinition): RedirectResponse
    {
        $this->authorize('delete', $sectionDefinition);

        try {
            DB::transaction(function () use ($sectionDefinition): void {
                $linkedSectionIds = $sectionDefinition->sections()
                    ->pluck('id')
                    ->all();

                if ($linkedSectionIds !== []) {
                    SectionTranslation::query()
                        ->whereIn('section_id', $linkedSectionIds)
                        ->delete();

                    $sectionDefinition->sections()
                        ->whereKey($linkedSectionIds)
                        ->delete();
                }

                $sectionDefinition->fields()->delete();
                $sectionDefinition->templates()->detach();
                $sectionDefinition->delete();
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('dashboard.section_definitions.index')
                ->with('error', __('Section definition could not be deleted. Please review linked records and try again.'));
        }

        return redirect()
            ->route('dashboard.section_definitions.index')
            ->with('success', __('Section definition and linked section instances were deleted successfully.'));
    }

    /**
     * Build shared view data for create/edit forms.
     *
     * The UI uses developer-friendly names such as "name" and
     * "is_visible_in_library", while persistence still targets the current
     * normalized schema columns.
     *
     * @return array<string, mixed>
     */
    protected function formViewData(SectionDefinition $sectionDefinition): array
    {
        $sectionDefinition->loadMissing([
            'templates' => fn ($query) => $query->orderByPivot('sort_order')->orderBy('id'),
        ]);

        $previewMediaId = old('preview_media_id');

        if (! is_numeric($previewMediaId) || (int) $previewMediaId <= 0) {
            $previewMediaId = $sectionDefinition->preview_media_id;
        }

        $previewMediaId = is_numeric($previewMediaId) && (int) $previewMediaId > 0
            ? (int) $previewMediaId
            : null;

        $selectedTemplateKey = old(
            'template_key',
            $sectionDefinition->templates->first()?->template_key,
        );
        $selectedCategory = old('category', $sectionDefinition->category);

        return [
            'sectionDefinition' => $sectionDefinition,
            'templateOptions' => $this->templateOptions($sectionDefinition),
            'customPresetOptions' => $this->customPresetOptions($sectionDefinition),
            'editorModeOptions' => [
                SectionDefinition::EDITOR_MODE_DYNAMIC => __('Dynamic'),
                SectionDefinition::EDITOR_MODE_CUSTOM_PRESET => __('Custom'),
            ],
            'previewMediaValue' => $previewMediaId,
            'previewMediaPreviewUrls' => app(SectionMediaPreviewBuilder::class)->build($previewMediaId),
            'selectedEditorMode' => old('editor_mode', $sectionDefinition->editor_mode),
            'selectedTemplateKey' => $selectedTemplateKey,
            'selectedTemplateMeta' => SectionTemplateRegistry::describe($selectedTemplateKey, $selectedCategory),
            'selectedCustomEditorKey' => old(
                'custom_editor_key',
                $sectionDefinition->custom_editor_key,
            ),
        ];
    }

    /**
     * Return the template registry options for the form suggestions.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function templateOptions(?SectionDefinition $sectionDefinition = null): array
    {
        $templateOptions = SectionTemplateRegistry::all();
        $currentTemplateKey = $sectionDefinition?->templates()->orderByPivot('sort_order')->first()?->template_key;

        if ($currentTemplateKey && ! isset($templateOptions[$currentTemplateKey])) {
            $templateOptions[$currentTemplateKey] = SectionTemplateRegistry::describe(
                $currentTemplateKey,
                $sectionDefinition?->category,
            ) ?? [
                'template_key' => $currentTemplateKey,
                'label' => __('Unknown Template') . ' (' . $currentTemplateKey . ')',
                'view' => '',
                'category' => null,
                'meta' => [],
            ];
        }

        return $templateOptions;
    }

    /**
     * Return the custom preset registry options for the form select.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function customPresetOptions(?SectionDefinition $sectionDefinition = null): array
    {
        $customPresetOptions = SectionCustomPresetRegistry::all();
        $currentCustomEditorKey = trim((string) ($sectionDefinition?->custom_editor_key ?? ''));

        if ($currentCustomEditorKey !== '' && ! isset($customPresetOptions[$currentCustomEditorKey])) {
            $customPresetOptions[$currentCustomEditorKey] = [
                'preset_key' => $currentCustomEditorKey,
                'label' => __('Unregistered Custom Preset') . ' (' . $currentCustomEditorKey . ')',
                'description' => __('This key is stored on the definition but is no longer registered in code.'),
                'builder' => '',
                'view' => '',
                'meta' => [],
            ];
        }

        return $customPresetOptions;
    }

    /**
     * Map UI payload fields to the current schema columns.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function persistableAttributes(array $validated): array
    {
        $editorMode = (string) ($validated['editor_mode'] ?? SectionDefinition::EDITOR_MODE_DYNAMIC);

        return [
            'label' => $validated['name'],
            'section_key' => $validated['key'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'preview_media_id' => $validated['preview_media_id'] ?? null,
            'editor_mode' => $editorMode,
            'custom_editor_key' => $editorMode === SectionDefinition::EDITOR_MODE_CUSTOM_PRESET
                ? ($validated['custom_editor_key'] ?? null)
                : null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'is_visible' => (bool) ($validated['is_visible_in_library'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }

    /**
     * Keep the selected template relation aligned with the chosen template key.
     *
     * The database continues to store template references only. Actual Blade
     * rendering remains code-driven through SectionTemplateRegistry.
     */
    protected function syncTemplateSelection(SectionDefinition $sectionDefinition, ?string $templateKey): void
    {
        $templateKey = is_string($templateKey) ? trim($templateKey) : '';

        if ($templateKey === '') {
            $sectionDefinition->templates()->sync([]);

            return;
        }

        $templateConfig = SectionTemplateRegistry::describe($templateKey, $sectionDefinition->category);

        if (! is_array($templateConfig)) {
            $sectionDefinition->templates()->sync([]);

            return;
        }

        $template = SectionTemplate::query()->firstOrCreate(
            ['template_key' => $templateKey],
            [
                'label' => $templateConfig['label'] ?? $templateKey,
                'category' => $templateConfig['category'] ?? null,
                'is_active' => true,
                'is_visible' => true,
                'sort_order' => 0,
            ],
        );

        $sectionDefinition->templates()->sync([
            $template->id => ['sort_order' => 0],
        ]);
    }

    protected function redirectAfterSave(
        SectionDefinition $sectionDefinition,
        string $afterSave = 'edit',
        bool $wasCreated = false,
    ): RedirectResponse {
        $afterSave = trim($afterSave);

        if ($wasCreated || $afterSave === 'fields') {
            return redirect()
                ->route('dashboard.section_definitions.fields.index', $sectionDefinition)
                ->with('success', __('Section definition saved. Continue by managing its field definitions.'));
        }

        return redirect()
            ->route('dashboard.section_definitions.edit', $sectionDefinition)
            ->with('success', __('Section definition updated successfully.'));
    }
}
