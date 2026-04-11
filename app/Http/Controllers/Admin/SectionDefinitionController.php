<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectionDefinitionRequest;
use App\Http\Requests\Admin\UpdateSectionDefinitionRequest;
use App\Models\Sections\SectionDefinition;
use App\Models\Sections\Template as SectionTemplate;
use App\Support\Sections\SectionTemplateRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

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

        DB::transaction(function () use ($validated) {
            $sectionDefinition = SectionDefinition::create($this->persistableAttributes($validated));

            $this->syncTemplateSelection($sectionDefinition, $validated['template_key'] ?? null);
        });

        return redirect()
            ->route('dashboard.section_definitions.index')
            ->with('success', __('Section definition created successfully.'));
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

        return redirect()
            ->route('dashboard.section_definitions.index')
            ->with('success', __('Section definition updated successfully.'));
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
        $sectionDefinition->loadMissing(['templates' => fn ($query) => $query->orderByPivot('sort_order')->orderBy('id')]);

        return [
            'sectionDefinition' => $sectionDefinition,
            'templateOptions' => $this->templateOptions($sectionDefinition),
            'editorModeOptions' => [
                SectionDefinition::EDITOR_MODE_DYNAMIC => __('Dynamic'),
                SectionDefinition::EDITOR_MODE_CUSTOM_PRESET => __('Custom'),
            ],
            'selectedTemplateKey' => old(
                'template_key',
                $sectionDefinition->templates->first()?->template_key,
            ),
        ];
    }

    /**
     * Return the template registry options for the form select.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function templateOptions(?SectionDefinition $sectionDefinition = null): array
    {
        $templateOptions = SectionTemplateRegistry::all();
        $currentTemplateKey = $sectionDefinition?->templates()->orderByPivot('sort_order')->first()?->template_key;

        if ($currentTemplateKey && ! isset($templateOptions[$currentTemplateKey])) {
            $templateOptions[$currentTemplateKey] = [
                'template_key' => $currentTemplateKey,
                'label' => __('Unregistered Template') . ' (' . $currentTemplateKey . ')',
                'view' => SectionTemplateRegistry::fallbackView(),
                'category' => null,
                'meta' => [],
            ];
        }

        return $templateOptions;
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

        $templateConfig = SectionTemplateRegistry::get($templateKey);

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
}
