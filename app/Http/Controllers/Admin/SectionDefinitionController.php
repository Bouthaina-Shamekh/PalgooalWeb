<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectionDefinitionRequest;
use App\Http\Requests\Admin\UpdateSectionDefinitionRequest;
use App\Models\SectionTranslation;
use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use App\Models\Sections\Template as SectionTemplate;
use App\Support\Sections\SectionMediaPreviewBuilder;
use App\Support\Sections\SectionTemplateFileWriter;
use App\Support\Sections\SectionTemplateRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SectionDefinitionController extends Controller
{
    /**
     * List section blueprint definitions for admin/developer management.
     */
    public function index(\Illuminate\Http\Request $request): View
    {
        $this->authorize('viewAny', SectionDefinition::class);

        $search  = $request->get('search');
        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50])
            ? (int) $request->get('per_page')
            : 20;

        $sectionDefinitions = SectionDefinition::query()
            ->with(['templates' => fn($query) => $query->orderByPivot('sort_order')->orderBy('id')])
            ->withCount('fields')
            ->withCount('sections')
            ->when($search, fn($q) => $q
                ->where('label', 'like', "%{$search}%")
                ->orWhere('section_key', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $templateRegistry = SectionTemplateRegistry::all();

        return view('dashboard.section_definitions.index',
            compact('sectionDefinitions', 'templateRegistry', 'search', 'perPage'));
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
                ->with('error', t('dashboard.Section_Def_Create_Error', 'تعذّر إنشاء تعريف القسم.'));
        }

        return $this->redirectAfterSave($sectionDefinition, (string) $request->input('after_save', 'fields'), true);
    }

    /**
     * Show the edit form for an existing section definition.
     */
    public function edit(SectionDefinition $sectionDefinition): View
    {
        $this->authorize('update', $sectionDefinition);

        $sectionDefinition->load(['templates' => fn($query) => $query->orderByPivot('sort_order')->orderBy('id')]);

        $writer          = app(SectionTemplateFileWriter::class);
        $bladeFileStatus = $writer->fileStatus($sectionDefinition);
        $bladeExpectedPath = $writer->displayPath($sectionDefinition);

        // If blade_source is empty but the file exists on disk, pre-populate from file
        // (happens when the file was created externally — status is 'external')
        if (empty($sectionDefinition->blade_source) && in_array($bladeFileStatus, ['exists', 'external'])) {
            $diskPath = $writer->resolvedPath($sectionDefinition);
            if ($diskPath && file_exists($diskPath)) {
                $sectionDefinition->blade_source = file_get_contents($diskPath);
            }
        }

        return view('dashboard.section_definitions.edit', array_merge(
            $this->formViewData($sectionDefinition),
            compact('bladeFileStatus', 'bladeExpectedPath'),
        ));
    }

    /**
     * Update the base section definition record.
     */
    public function update(
        UpdateSectionDefinitionRequest $request,
        SectionDefinition $sectionDefinition,
    ): RedirectResponse {
        $this->authorize('update', $sectionDefinition);

        $validated = $request->validated();

        DB::transaction(function () use ($sectionDefinition, $validated, $request) {
            $sectionDefinition->update($this->persistableAttributes($validated));

            // Persist blade_source when provided
            $bladeSource = $request->input('blade_source');
            if ($bladeSource !== null) {
                $sectionDefinition->blade_source = $bladeSource !== '' ? $bladeSource : null;
                $sectionDefinition->saveQuietly();
            }

            $this->syncTemplateSelection($sectionDefinition, $validated['template_key'] ?? null);
        });

        // Optionally write file to disk if blade_source was provided
        if ($request->filled('blade_source')) {
            $sectionDefinition->refresh();
            $writer = app(SectionTemplateFileWriter::class);
            $result = $writer->write($sectionDefinition);

            if (! $result['ok']) {
                return $this->redirectAfterSave($sectionDefinition, (string) $request->input('after_save', 'edit'))
                    ->with('warning', t('dashboard.Blade_Write_Failed', 'تم حفظ البيانات لكن فشلت كتابة ملف Blade: ') . $result['error']);
            }
        }

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

        // Delete the Blade file from disk BEFORE removing DB record
        $writer = app(SectionTemplateFileWriter::class);
        $fileResult = $writer->deleteFile($sectionDefinition);

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

                $sectionDefinition->fields()->each(fn (SectionDefinitionField $f) => $f->delete());
                $sectionDefinition->templates()->detach();
                $sectionDefinition->delete();
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('dashboard.section_definitions.index')
                ->with('error', t('dashboard.Section_Def_Delete_Error', 'تعذّر حذف تعريف القسم. راجع السجلات المرتبطة وحاول مجدداً.'));
        }

        $message = t('dashboard.Section_Def_Deleted', 'تم حذف تعريف القسم والأقسام المرتبطة به بنجاح.');
        if (! empty($fileResult['deleted'])) {
            $message .= ' ' . t('dashboard.Blade_File_Deleted', 'تم حذف ملف Blade من الـ disk.');
        }

        return redirect()
            ->route('dashboard.section_definitions.index')
            ->with('ok', $message);
    }

    /**
     * Write blade_source to the correct disk path without re-submitting the full form.
     *
     * POST /admin/section-definitions/{id}/write-blade
     * Restricted to super_admin.
     */
    public function writeBladeFile(Request $request, SectionDefinition $sectionDefinition): RedirectResponse
    {
        $this->authorize('update', $sectionDefinition);


        $bladeSource = $request->input('blade_source');

        if ($bladeSource !== null) {
            $sectionDefinition->blade_source = $bladeSource !== '' ? $bladeSource : null;
            $sectionDefinition->saveQuietly();
        }

        if (empty($sectionDefinition->blade_source)) {
            return redirect()
                ->route('dashboard.section_definitions.edit', $sectionDefinition)
                ->with('error', t('dashboard.Blade_Write_Failed', 'لا يوجد كود Blade للكتابة.'));
        }

        $writer = app(SectionTemplateFileWriter::class);
        $result = $writer->write($sectionDefinition);

        if (! $result['ok']) {
            return redirect()
                ->route('dashboard.section_definitions.edit', $sectionDefinition)
                ->with('error', t('dashboard.Blade_Write_Failed', 'فشلت كتابة ملف Blade: ') . $result['error']);
        }

        return redirect()
            ->route('dashboard.section_definitions.edit', $sectionDefinition)
            ->with('ok', t('dashboard.Blade_Write_Success', 'تم كتابة ملف Blade على الـ disk بنجاح.'));
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
            'templates' => fn($query) => $query->orderByPivot('sort_order')->orderBy('id'),
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
            'editorModeOptions' => [
                SectionDefinition::EDITOR_MODE_DYNAMIC => t('dashboard.Dynamic', 'ديناميكي'),
            ],
            'previewMediaValue' => $previewMediaId,
            'previewMediaPreviewUrls' => app(SectionMediaPreviewBuilder::class)->build($previewMediaId),
            'selectedEditorMode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
            'selectedTemplateKey' => $selectedTemplateKey,
            'selectedTemplateMeta' => SectionTemplateRegistry::describe($selectedTemplateKey, $selectedCategory),
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
                'label' => t('dashboard.Unknown_Template', 'قالب غير معروف') . ' (' . $currentTemplateKey . ')',
                'view' => '',
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
        return [
            'label' => $validated['name'],
            'section_key' => $validated['key'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'preview_media_id' => $validated['preview_media_id'] ?? null,
            'editor_mode' => SectionDefinition::EDITOR_MODE_DYNAMIC,
            'custom_editor_key' => null,
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
                ->with('ok', t('dashboard.Section_Def_Save_Fields', 'تم حفظ تعريف القسم. تابع بإدارة الحقول.'));
        }

        return redirect()
            ->route('dashboard.section_definitions.edit', $sectionDefinition)
            ->with('ok', t('dashboard.Section_Def_Updated', 'تم تحديث تعريف القسم بنجاح.'));
    }
}
