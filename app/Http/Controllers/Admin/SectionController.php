<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Page;
use App\Support\Tenancy\TenantThemeSettings;
use App\Models\Section;
use App\Models\SectionTranslation;
use App\Models\Sections\SectionDefinition;
use App\Models\Tenancy\Subscription;
use App\Models\Template;
use App\Support\Sections\DynamicSectionContentNormalizer;
use App\Support\Sections\SectionEditorDataFactory;
use App\Support\Sections\SectionSidebarEditorViewDataFactory;
use App\Support\Sections\SectionWorkspacePreviewViewDataFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Canonical controller-driven section management entrypoint.
 */
class SectionController extends Controller
{
    /**
     * List all sections for a given marketing page.
     */
    public function index(Page $page)
    {
        $this->authorize('viewAny', Section::class);
        $page->loadMissing('translations');

        $sections = Section::with('translations')
            ->where('page_id', $page->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $languages = Language::where('is_active', true)
            ->orderBy('id')
            ->get();

        return view('dashboard.pages.sections.index', [
            'page' => $page,
            'sections' => $sections,
            'languages' => $languages,
            'sectionTypes' => $this->workspaceSectionTypes(),
            'sectionLibraryTypes' => $this->sectionLibraryTypes(),
        ] + $this->workspaceViewData($page));
    }

    /**
     * Render a front-like preview for the sections workspace iframe.
     */
    public function preview(
        Request $request,
        Page $page,
        ?SectionWorkspacePreviewViewDataFactory $previewViewFactory = null,
    ) {
        $this->authorize('viewAny', Section::class);
        $sections = Section::with('translations')
            ->where('page_id', $page->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $previewTemplates = Template::query()
            ->with([
                'translations',
                'categoryTemplate.translation',
                'categoryTemplate.translations',
            ])
            ->latest('id')
            ->limit(8)
            ->get();
        $sectionTypes = $this->workspaceSectionTypes();
        $previewView = ($previewViewFactory ?? app(SectionWorkspacePreviewViewDataFactory::class))->build(
            $page,
            $sections,
            $sectionTypes,
            $previewTemplates,
            $request->integer('highlight'),
        );

        return view('dashboard.pages.sections.preview', [
            'previewView' => $previewView,
        ] + $this->workspaceViewData($page));
    }

    /**
     * Show the full create form for a specific page.
     */
    public function create(Page $page)
    {
        $this->authorize('create', Section::class);
        $languages = Language::where('is_active', true)
            ->orderBy('id')
            ->get();

        $page->loadMissing('translations');

        return view('dashboard.pages.sections.create', [
            'page' => $page,
            'languages' => $languages,
            'sectionTypes' => $this->workspaceSectionTypes(),
            'sectionLibraryTypes' => $this->sectionLibraryTypes(),
            'nextOrder' => $this->nextOrderForPage($page),
        ] + $this->workspaceViewData($page));
    }

    /**
     * Store a newly created section and its translations.
     */
    public function store(Request $request, Page $page)
    {
        $this->authorize('create', Section::class);
        $validated = $request->validate([
            'type'      => ['required', 'string', 'max:100', Rule::in($this->allowedSectionTypeKeys())],
            'section_definition_id' => $this->sectionDefinitionIdRulesForCreate(),
            'variant'   => 'nullable|string|max:100',
            'style'     => 'nullable|array',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',

            'translations'           => 'required|array',
            'translations.*.locale'  => 'required|string',
            'translations.*.title'   => 'nullable|string|max:255',
            'translations.*.content' => 'nullable|array',
        ]);

        $sectionSelection = $this->normalizeSectionCreateSelection(
            (string) $validated['type'],
            isset($validated['section_definition_id']) ? (int) $validated['section_definition_id'] : null,
        );
        $validated['type'] = $sectionSelection['type'];

        $validated['translations'] = $this->normalizeSubmittedTranslations(
            $validated['type'],
            $validated['translations'] ?? [],
            $sectionSelection['section_definition'],
        );

        DB::transaction(function () use ($validated, $page, $sectionSelection) {
            $type = $validated['type'];

            $section = Section::create([
                'page_id'   => $page->id,
                ...$this->sectionDefinitionLinkAttributes($sectionSelection['section_definition_id']),
                'type'      => $type,
                'variant'   => $validated['variant'] ?? null,
                'style'     => $validated['style'] ?? $this->defaultStyleForSection($type, $sectionSelection['section_definition']),
                'order'     => $validated['order'] ?? $this->nextOrderForPage($page),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            foreach ($validated['translations'] as $translationData) {
                SectionTranslation::create([
                    'section_id' => $section->id,
                    'locale'     => $translationData['locale'],
                    'title'      => $translationData['title'] ?? null,
                    'content'    => $translationData['content'] ?? [],
                ]);
            }
        });

        $this->normalizePageSectionOrders($page);

        return redirect()
            ->to($this->workspaceRoute('index', $page))
            ->with('success', 'Section has been created successfully.');
    }

    /**
     * Quick-add a section from the workspace library, then open its editor.
     */
    public function quickStore(Request $request, Page $page)
    {
        $this->authorize('create', Section::class);
        $validated = $request->validate([
            'type'    => ['required', 'string', 'max:100', Rule::in($this->allowedSectionTypeKeys())],
            'section_definition_id' => $this->sectionDefinitionIdRulesForCreate(),
            'variant' => 'nullable|string|max:100',
        ]);

        $sectionSelection = $this->normalizeSectionCreateSelection(
            (string) $validated['type'],
            isset($validated['section_definition_id']) ? (int) $validated['section_definition_id'] : null,
        );

        $page->loadMissing('translations');
        $createdSection = null;

        DB::transaction(function () use ($page, $validated, $sectionSelection, &$createdSection) {
            $createdSection = $this->createDefaultSection(
                $page,
                $sectionSelection['type'],
                $validated['variant'] ?? null,
                $sectionSelection['section_definition_id'],
                $sectionSelection['section_definition'],
            );
        });

        if (! $createdSection instanceof Section) {
            abort(500, 'Section could not be created.');
        }

        $createdSection->refresh()->load('translations');

        $redirectUrl = $this->workspaceRoute('index', $page, null, [
            'highlight' => $createdSection->id,
            'edit' => $createdSection->id,
        ]);
        $editorUrl = $this->workspaceRoute('editor', $page, $createdSection, [], false);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'redirect_url' => $redirectUrl,
                'editor_url' => $editorUrl,
                'section_id' => $createdSection->id,
                'section_card_html' => $this->renderSectionOutlineItem($page, $createdSection),
                'message' => 'Section added. Continue customizing it in the editor.',
            ]);
        }

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Section added. Continue customizing it in the editor.');
    }

    /**
     * Show the edit form for a specific page section.
     */
    public function edit(Page $page, Section $section)
    {
        $this->authorize('update', $section);
        $this->ensureSectionBelongsToPage($page, $section);

        return view('dashboard.pages.sections.edit', $this->sectionEditorViewData($page, $section));
    }

    /**
     * Render the inline workspace editor panel for a specific section.
     */
    public function editor(Page $page, Section $section)
    {
        $this->authorize('update', $section);
        $this->ensureSectionBelongsToPage($page, $section);

        return view('dashboard.pages.sections.partials.sidebar-editor', $this->sectionEditorViewData($page, $section));
    }

    /**
     * Update an existing section and its translations.
     */
    public function update(Request $request, Page $page, Section $section)
    {
        $this->authorize('update', $section);
        // Always respond with JSON for AJAX / XHR / JSON-accepting requests so
        // any error (validation, server-side exception, or unexpected redirect)
        // surfaces as a readable message in the editor rather than the generic
        // "could not be updated" fallback.
        $isJsonRequest = $request->expectsJson()
            || $request->ajax()
            || $request->wantsJson()
            || $request->hasHeader('X-Requested-With')
            || $request->hasHeader('X-HTTP-Method-Override');

        try {
            $this->ensureSectionBelongsToPage($page, $section);

            $validated = $request->validate([
                'type'      => ['required', 'string', 'max:100', Rule::in([$section->type])],
                // nullable: if omitted or empty the controller falls back to the
                // section's currently linked definition (set when the section was created).
                'section_definition_id' => $this->sectionDefinitionIdRulesForUpdate($section),
                'variant'   => 'nullable|string|max:100',
                'style'     => 'nullable|array',
                'order'     => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',

                'translations'           => 'required|array',
                'translations.*.locale'  => 'required|string',
                'translations.*.title'   => 'nullable|string|max:255',
                'translations.*.content' => 'nullable|array',
            ]);

            // Resolve the definition — falls back to the section's own FK when
            // section_definition_id was not submitted or was submitted as empty.
            $submittedDefinitionId = filled($validated['section_definition_id'] ?? null)
                ? (int) $validated['section_definition_id']
                : null;

            $linkedSectionDefinition = $this->resolveLinkedSectionDefinitionForUpdate(
                $section,
                $submittedDefinitionId,
            );

            $validated['translations'] = $this->normalizeSubmittedTranslations(
                $validated['type'],
                $validated['translations'] ?? [],
                $linkedSectionDefinition,
            );

            DB::transaction(function () use ($validated, $section, $linkedSectionDefinition) {
                $type = $validated['type'];

                $section->update([
                    ...$this->sectionDefinitionAttributesForUpdate($type, $section, $linkedSectionDefinition),
                    'type'      => $type,
                    'variant'   => $validated['variant'] ?? null,
                    'style'     => $validated['style'] ?? $section->style,
                    'order'     => $validated['order'] ?? $section->order,
                    'is_active' => (bool) ($validated['is_active'] ?? false),
                ]);

                $locales = [];

                foreach ($validated['translations'] as $locale => $translationData) {
                    $locales[] = $locale;

                    $translation = SectionTranslation::firstOrNew([
                        'section_id' => $section->id,
                        'locale'     => $locale,
                    ]);

                    $translation->title = $translationData['title'] ?? null;
                    $translation->content = $translationData['content'] ?? [];
                    $translation->save();
                }

                if (! empty($locales)) {
                    SectionTranslation::where('section_id', $section->id)
                        ->whereNotIn('locale', $locales)
                        ->delete();
                }
            });

            $this->normalizePageSectionOrders($page);

            $section->refresh()->load('translations');

            if ($isJsonRequest) {
                $sectionTypeMeta = $section->resolvedTypeMeta($this->sectionTypesForSection($section));
                $typeLabel = $sectionTypeMeta['label'];

                $translation = $section->translation(app()->getLocale()) ?? $section->translations->first();

                return response()->json([
                    'ok' => true,
                    'message' => 'Section has been updated successfully.',
                    'section' => [
                        'id' => $section->id,
                        'title' => $translation?->title ?: $typeLabel,
                        'type_label' => $typeLabel,
                        'is_active' => (bool) $section->is_active,
                    ],
                ]);
            }

            return redirect()
                ->to($this->workspaceRoute('index', $page, null, [
                    'highlight' => $section->id,
                ]))
                ->with('success', 'Section has been updated successfully.');

        } catch (ValidationException $e) {
            // Re-throw validation exceptions — Laravel will convert them to the
            // correct 422 JSON response automatically.
            throw $e;
        } catch (\Throwable $e) {
            // Log unexpected errors so they appear in storage/logs/laravel.log.
            \Illuminate\Support\Facades\Log::error('SectionController::update failed', [
                'page_id'    => $page->id,
                'section_id' => $section->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            if ($isJsonRequest) {
                return response()->json([
                    'ok'      => false,
                    'message' => __('Section could not be updated due to a server error. Please try again or contact support.'),
                ], 500);
            }

            throw $e;
        }
    }

    /**
     * Toggle section visibility without opening the editor.
     */
    public function toggleActive(Page $page, Section $section)
    {
        $this->authorize('update', $section);
        $this->ensureSectionBelongsToPage($page, $section);

        $section->update([
            'is_active' => ! $section->is_active,
        ]);

        return redirect()
            ->to($this->workspaceRoute('index', $page, null, ['highlight' => $section->id]))
            ->with('success', 'Section visibility has been updated.');
    }

    /**
     * Quickly rename a section in a specific locale from the workspace.
     */
    public function rename(Request $request, Page $page, Section $section)
    {
        $this->authorize('update', $section);
        $this->ensureSectionBelongsToPage($page, $section);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:10'],
        ]);

        $translation = SectionTranslation::firstOrNew([
            'section_id' => $section->id,
            'locale' => $validated['locale'],
        ]);

        $translation->title = trim((string) $validated['title']);

        if (! is_array($translation->content)) {
            $translation->content = [];
        }

        $translation->save();

        return redirect()
            ->to($this->workspaceRoute('index', $page, null, ['highlight' => $section->id]))
            ->with('success', 'Section title has been updated.');
    }

    /**
     * Move a section up or down in the current page outline.
     */
    public function move(Request $request, Page $page, Section $section)
    {
        $this->authorize('update', $section);
        $this->ensureSectionBelongsToPage($page, $section);

        $validated = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ]);

        $this->normalizePageSectionOrders($page);
        $section->refresh();

        $currentOrder = (int) ($section->order ?? 0);

        $targetQuery = Section::where('page_id', $page->id)
            ->where('id', '!=', $section->id);

        $target = $validated['direction'] === 'up'
            ? $targetQuery->where('order', '<', $currentOrder)->orderBy('order', 'desc')->first()
            : $targetQuery->where('order', '>', $currentOrder)->orderBy('order')->first();

        if (! $target) {
            return redirect()
                ->to($this->workspaceRoute('index', $page, null, ['highlight' => $section->id]));
        }

        DB::transaction(function () use ($section, $target, $currentOrder) {
            $section->update(['order' => $target->order]);
            $target->update(['order' => $currentOrder]);
        });

        return redirect()
            ->to($this->workspaceRoute('index', $page, null, ['highlight' => $section->id]))
            ->with('success', 'Section order has been updated.');
    }

    /**
     * Reorder all sections for the current page via drag and drop.
     */
    public function reorder(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', Section::class);
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required'],
        ]);

        $orderedIds = collect($validated['ids'])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($orderedIds->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid reorder payload.',
            ], 422);
        }

        $pageSectionIds = Section::where('page_id', $page->id)
            ->orderBy('order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values();

        if ($orderedIds->diff($pageSectionIds)->isNotEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid reorder payload.',
            ], 422);
        }

        $missingIds = $pageSectionIds
            ->reject(fn($id) => $orderedIds->contains($id))
            ->values();

        $finalOrder = $orderedIds
            ->concat($missingIds)
            ->values();

        DB::transaction(function () use ($page, $finalOrder): void {
            foreach ($finalOrder as $index => $id) {
                Section::where('page_id', $page->id)
                    ->where('id', $id)
                    ->update(['order' => $index + 1]);
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Section order has been updated.',
        ]);
    }

    /**
     * Duplicate a section as a new draft copy.
     */
    public function duplicate(Page $page, Section $section)
    {
        $this->authorize('create', Section::class);
        $this->ensureSectionBelongsToPage($page, $section);
        $section->loadMissing('translations');

        $duplicate = null;

        DB::transaction(function () use ($page, $section, &$duplicate) {
            $duplicate = Section::create([
                'page_id'   => $page->id,
                ...$this->duplicatedSectionDefinitionAttributes($section),
                'type'      => $section->type,
                'variant'   => $section->variant,
                'style'     => $section->style,
                'order'     => $this->nextOrderForPage($page),
                'is_active' => false,
            ]);

            foreach ($section->translations as $translation) {
                SectionTranslation::create([
                    'section_id' => $duplicate->id,
                    'locale'     => $translation->locale,
                    'title'      => $translation->title ? $translation->title . ' (Copy)' : null,
                    'content'    => $translation->content ?? [],
                ]);
            }
        });

        if (! $duplicate instanceof Section) {
            abort(500, 'Section could not be duplicated.');
        }

        return redirect()
            ->to($this->workspaceRoute('index', $page, null, ['highlight' => $duplicate->id]))
            ->with('success', 'Section duplicated as a draft copy.');
    }

    /**
     * Delete a section.
     */
    public function destroy(Page $page, Section $section)
    {
        $this->authorize('delete', $section);
        $this->ensureSectionBelongsToPage($page, $section);

        $section->delete();
        $this->normalizePageSectionOrders($page);

        return redirect()
            ->to($this->workspaceRoute('index', $page))
            ->with('success', 'Section has been deleted successfully.');
    }

    /**
     * Route prefix used by the current sections workspace surface.
     */
    protected function workspaceRoutePrefix(): string
    {
        return 'dashboard.pages.sections.';
    }

    /**
     * Current workspace consumer mode.
     */
    protected function workspaceMode(): string
    {
        return 'admin';
    }

    /**
     * Small UI label for the current workspace mode.
     */
    protected function workspaceModeLabel(): ?string
    {
        return __('Admin workspace');
    }

    /**
     * Base route parameters for the current workspace surface.
     *
     * @return array<string, mixed>
     */
    protected function workspaceBaseRouteParameters(Page $page): array
    {
        return ['page' => $page];
    }

    /**
     * Build route parameters for workspace routes.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function workspaceRouteParameters(Page $page, ?Section $section = null, array $extra = []): array
    {
        $parameters = $this->workspaceBaseRouteParameters($page);

        if ($section instanceof Section) {
            $parameters['section'] = $section;
        }

        return array_merge($parameters, $extra);
    }

    /**
     * Resolve a workspace route URL.
     *
     * @param  array<string, mixed>  $extra
     */
    protected function workspaceRoute(
        string $name,
        Page $page,
        ?Section $section = null,
        array $extra = [],
        bool $absolute = true
    ): string {
        return route(
            $this->workspaceRoutePrefix() . $name,
            $this->workspaceRouteParameters($page, $section, $extra),
            $absolute
        );
    }

    /**
     * Back link for the workspace shell.
     */
    protected function workspaceShellBackUrl(Page $page): string
    {
        return route('dashboard.pages.index');
    }

    /**
     * Back link label for the workspace shell.
     */
    protected function workspaceShellBackLabel(): string
    {
        return __('Back to pages');
    }

    /**
     * Front preview URL for the current page.
     */
    protected function workspaceFrontUrl(Page $page): string
    {
        $translation = $page->translation();

        return $page->is_home
            ? url('/')
            : ($translation?->slug ? url($translation->slug) : url('/'));
    }

    /**
     * Optional visual builder URL for the workspace shell.
     */
    protected function workspaceVisualBuilderUrl(Page $page): ?string
    {
        return null;
    }

    /**
     * Optional builder-mode toggle URL for the workspace sidebar.
     */
    protected function workspaceBuilderModeUrl(Page $page): ?string
    {
        return route('dashboard.pages.builder-mode', $page);
    }

    /**
     * Shared view config for any workspace surface.
     *
     * @return array<string, mixed>
     */
    protected function workspaceViewData(Page $page): array
    {
        $activeThemeSubscription = $this->resolveActiveThemeSubscription($page);

        return [
            'workspaceMode' => $this->workspaceMode(),
            'workspaceModeLabel' => $this->workspaceModeLabel(),
            'workspaceRoutePrefix' => $this->workspaceRoutePrefix(),
            'workspaceRouteBaseParameters' => $this->workspaceBaseRouteParameters($page),
            'workspaceShellBackUrl' => $this->workspaceShellBackUrl($page),
            'workspaceShellBackLabel' => $this->workspaceShellBackLabel(),
            'workspaceFrontUrl' => $this->workspaceFrontUrl($page),
            'workspaceVisualBuilderUrl' => $this->workspaceVisualBuilderUrl($page),
            'workspaceBuilderModeUrl' => $this->workspaceBuilderModeUrl($page),
            // Resolved explicitly from the page ownership columns so the preview
            // iframe can load the correct tenant theme CSS in admin context.
            'activeThemeSubscription' => $activeThemeSubscription,
            // Brand settings drawer — null when there is no associated subscription.
            'brandSettingsUpdateUrl' => $this->workspaceBrandSettingsUpdateUrl($activeThemeSubscription),
            'brandSettingsTheme' => $activeThemeSubscription !== null
                ? TenantThemeSettings::fromArray(
                    is_array($activeThemeSubscription->theme_settings) ? $activeThemeSubscription->theme_settings : []
                )
                : null,
        ];
    }

    /**
     * Resolve the subscription whose theme CSS should be loaded in the
     * preview iframe.
     *
     * Returns the Subscription linked through the canonical Page::tenant_id
     * owner. Falls back to Page::subscription_id only for older tenant pages
     * that still use that documented legacy linkage.
     *
     * Client subclasses that already hold an explicit $workspaceSubscription
     * override this via their own workspaceViewData() merge.
     */
    protected function resolveActiveThemeSubscription(Page $page): ?Subscription
    {
        $page->loadMissing(['tenant', 'subscription']);

        if ($page->tenant_id !== null && $page->tenant instanceof Subscription) {
            return $page->tenant;
        }

        if ($page->subscription_id !== null && $page->subscription instanceof Subscription) {
            return $page->subscription;
        }

        return null;
    }

    /**
     * Return the URL to POST brand/theme settings to from the builder workspace.
     *
     * Returns null when there is no associated subscription (e.g. marketing pages).
     * Client subclasses override this to use the client-scoped route.
     */
    protected function workspaceBrandSettingsUpdateUrl(?Subscription $subscription): ?string
    {
        if ($subscription === null) {
            return null;
        }

        try {
            return route('dashboard.subscriptions.theme.update', $subscription);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Create a section with lightweight defaults for the quick-add workflow.
     */
    protected function createDefaultSection(
        Page $page,
        string $type,
        ?string $variant = null,
        ?int $sectionDefinitionId = null,
        ?SectionDefinition $sectionDefinition = null,
    ): Section {
        $sectionTypes = $this->sectionLibraryTypes();
        $label = $sectionDefinition?->label
            ?? ($sectionTypes[$type]['label'] ?? Str::headline(str_replace(['_', '-'], ' ', $type)));

        $section = Section::create([
            'page_id'   => $page->id,
            ...$this->sectionDefinitionLinkAttributes($sectionDefinitionId),
            'type'      => $type,
            'variant'   => $variant,
            'style'     => $this->defaultStyleForSection($type, $sectionDefinition),
            'order'     => $this->nextOrderForPage($page),
            'is_active' => true,
        ]);

        foreach ($this->activeLocaleCodes() as $locale) {
            $pageTitle = $page->translation($locale)?->title
                ?? $page->translation()?->title
                ?? ('Page #' . $page->id);

            SectionTranslation::create([
                'section_id' => $section->id,
                'locale'     => $locale,
                'title'      => $label,
                'content'    => $this->defaultContentForSection($type, $pageTitle, $sectionDefinition),
            ]);
        }

        return $section;
    }

    protected function defaultContentForSection(
        string $type,
        string $pageTitle,
        ?SectionDefinition $sectionDefinition = null,
    ): array {
        return $sectionDefinition instanceof SectionDefinition
            ? $this->defaultContentForDefinition($sectionDefinition)
            : [];
    }

    protected function defaultStyleForSection(string $type, ?SectionDefinition $sectionDefinition = null): array
    {
        return [];
    }

    protected function defaultContentForDefinition(SectionDefinition $sectionDefinition): array
    {
        $sectionDefinition->loadMissing([
            'fields' => fn($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        $content = [];

        foreach ($sectionDefinition->fields as $field) {
            [$hasDefault, $defaultValue] = app(\App\Support\Sections\SectionDefinitionRuntimeResolver::class)
                ->resolvedDefaultValue($field, app()->getLocale());

            if ($hasDefault) {
                data_set($content, $field->field_key, $defaultValue);
            }
        }

        return $content;
    }

    /**
     * Keep orders contiguous after move/delete/manual changes.
     */
    protected function normalizePageSectionOrders(Page $page): void
    {
        $sections = Section::where('page_id', $page->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id', 'order']);

        foreach ($sections as $index => $item) {
            $expectedOrder = $index + 1;

            if ((int) ($item->order ?? 0) !== $expectedOrder) {
                Section::whereKey($item->id)->update([
                    'order' => $expectedOrder,
                ]);
            }
        }
    }

    /**
     * Guard route-model binding so a section cannot be edited from another page.
     */
    protected function ensureSectionBelongsToPage(Page $page, Section $section): void
    {
        if ($section->page_id !== $page->id) {
            abort(404);
        }
    }

    /**
     * Definition-backed section library catalog for the standalone create
     * screen and the workspace quick-add drawer.
     *
     * Active, visible SectionDefinition records are the only source for
     * library cards in the normal admin page sections flow.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function sectionLibraryTypes(): array
    {
        $libraryTypes = [];

        if (! Schema::hasTable('section_definitions')) {
            return [];
        }

        $definitions = SectionDefinition::query()
            ->with('previewMedia')
            ->where('is_active', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($definitions as $definition) {
            $definitionKey = trim((string) $definition->section_key);

            if ($definitionKey === '' || isset($libraryTypes[$definitionKey])) {
                continue;
            }

            $libraryTypes[$definitionKey] = [
                'type' => $definitionKey,
                'label' => $definition->label,
                'description' => $definition->description ?: __('Definition-driven section'),
                'category' => $definition->category ?: 'other',
                'preview_url' => $definition->previewMedia?->url,
                'preview' => data_get($definition->settings, 'preview'),
                'section_definition_id' => (int) $definition->id,
                'source' => 'definition',
            ];
        }

        return $libraryTypes;
    }

    /**
     * Allow both legacy types and active section-definition keys to pass
     * through the current save pipeline.
     *
     * The library UI now submits explicit section_definition_id values for
     * definition-driven cards, but the canonical type key still needs to pass
     * validation for both create and quick-add flows.
     *
     * @return array<int, string>
     */
    protected function allowedSectionTypeKeys(?Section $currentSection = null): array
    {
        return collect([
            ...array_keys($this->sectionLibraryTypes()),
            ...($currentSection ? [(string) $currentSection->type] : []),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Normalize the section-library selection into a canonical type and an
     * explicit definition link when a definition-driven card was chosen.
     *
     * @return array{type: string, section_definition_id: int|null, section_definition: SectionDefinition|null}
     */
    protected function normalizeSectionCreateSelection(string $type, ?int $sectionDefinitionId = null): array
    {
        $type = trim($type);
        $sectionDefinition = $this->resolveVisibleLibrarySectionDefinition($sectionDefinitionId)
            ?? $this->resolveVisibleLibrarySectionDefinitionByType($type);

        if ($sectionDefinition instanceof SectionDefinition) {
            return [
                'type' => (string) $sectionDefinition->section_key,
                'section_definition_id' => $this->sectionDefinitionColumnAvailable()
                    ? (int) $sectionDefinition->id
                    : null,
                'section_definition' => $sectionDefinition,
            ];
        }

        return [
            'type' => $type,
            'section_definition_id' => null,
            'section_definition' => null,
        ];
    }

    /**
     * Resolve an explicit section-definition selection from the library.
     *
     * Creation flows only allow active, visible definitions so the UI cannot
     * bind new sections to retired or hidden blueprints.
     */
    protected function resolveVisibleLibrarySectionDefinition(?int $sectionDefinitionId): ?SectionDefinition
    {
        if (! $sectionDefinitionId || ! Schema::hasTable('section_definitions')) {
            return null;
        }

        return SectionDefinition::query()
            ->whereKey($sectionDefinitionId)
            ->where('is_active', true)
            ->where('is_visible', true)
            ->first();
    }

    /**
     * Resolve the primary visible definition for a library type key.
     *
     * This keeps create/quick-add flows definition-first even when a legacy
     * config fallback still exists for the same section_key.
     */
    protected function resolveVisibleLibrarySectionDefinitionByType(string $type): ?SectionDefinition
    {
        $type = trim($type);

        if ($type === '' || ! Schema::hasTable('section_definitions')) {
            return null;
        }

        return SectionDefinition::query()
            ->where('section_key', $type)
            ->where('is_active', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    /**
     * Build the explicit FK payload when the create flow already normalized
     * the selection and no legacy inference is needed.
     *
     * @return array<string, int|null>
     */
    protected function sectionDefinitionLinkAttributes(?int $sectionDefinitionId): array
    {
        if (! $this->sectionDefinitionColumnAvailable()) {
            return [];
        }

        return [
            'section_definition_id' => $sectionDefinitionId,
        ];
    }

    /**
     * Keep relational linking explicit for definition-driven sections.
     *
     * The current create/update UI still submits a type key, so this method
     * resolves the active SectionDefinition once at write time and stores the
     * foreign key for later runtime/editor use.
     *
     * @return array<string, int|null>
     */
    protected function sectionDefinitionAttributesForType(string $type, ?Section $currentSection = null): array
    {
        if (! $this->sectionDefinitionColumnAvailable()) {
            return [];
        }

        return [
            'section_definition_id' => $this->resolveSectionDefinitionIdForType($type, $currentSection),
        ];
    }

    protected function sectionDefinitionAttributesForUpdate(
        string $type,
        Section $currentSection,
        ?SectionDefinition $sectionDefinition = null,
    ): array {
        if (! $this->sectionDefinitionColumnAvailable()) {
            return [];
        }

        if ($sectionDefinition instanceof SectionDefinition) {
            return [
                'section_definition_id' => (int) $sectionDefinition->id,
            ];
        }

        return $this->sectionDefinitionAttributesForType($type, $currentSection);
    }

    /**
     * Preserve any existing explicit definition link when duplicating a
     * definition-driven section instance.
     *
     * @return array<string, int|null>
     */
    protected function duplicatedSectionDefinitionAttributes(Section $section): array
    {
        if (! $this->sectionDefinitionColumnAvailable()) {
            return [];
        }

        return [
            'section_definition_id' => $section->section_definition_id,
        ];
    }

    /**
     * Resolve the definition id that should be linked to a section instance.
     */
    protected function resolveSectionDefinitionIdForType(string $type, ?Section $currentSection = null): ?int
    {
        $type = trim($type);

        if ($currentSection instanceof Section && $currentSection->section_definition_id) {
            $currentSection->loadMissing('sectionDefinition');

            if ((string) ($currentSection->sectionDefinition?->section_key ?? '') === $type) {
                return (int) $currentSection->section_definition_id;
            }
        }

        if ($type === '') {
            return null;
        }

        $definitionId = SectionDefinition::query()
            ->where('section_key', $type)
            ->where('is_active', true)
            ->value('id');

        return $definitionId ? (int) $definitionId : null;
    }

    /**
     * Guard write-time FK usage during zero-downtime deploys before the new
     * sections-table migration has been applied everywhere.
     */
    protected function sectionDefinitionColumnAvailable(): bool
    {
        return Schema::hasColumn('sections', 'section_definition_id');
    }

    /**
     * Render the shared sidebar outline card for async quick-add responses.
     */
    protected function renderSectionOutlineItem(Page $page, Section $section): string
    {
        return view('dashboard.pages.sections.partials.outline-item', [
            'page' => $page,
            'section' => $section,
            'sectionTypes' => $this->sectionTypesForSection($section),
            'currentLocale' => app()->getLocale(),
            'selectedSectionId' => $section->id,
        ] + $this->workspaceViewData($page))->render();
    }

    /**
     * Return the next display order for a page.
     */
    protected function nextOrderForPage(Page $page): int
    {
        $maxOrder = Section::where('page_id', $page->id)->max('order');

        return is_null($maxOrder) ? 1 : ((int) $maxOrder + 1);
    }

    /**
     * Return active locale codes, with a safe fallback to the app locale.
     */
    protected function activeLocaleCodes(): array
    {
        $localeCodes = Language::where('is_active', true)
            ->orderBy('id')
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        return $localeCodes !== [] ? $localeCodes : [app()->getLocale()];
    }

    /**
     * Shared payload for the standalone edit page and inline workspace editor.
     */
    protected function sectionEditorViewData(Page $page, Section $section): array
    {
        $section->load('translations');
        $page->loadMissing('translations');

        $languages = Language::where('is_active', true)
            ->orderBy('id')
            ->get();

        $sectionTypes = $this->sectionTypesForSection($section);
        $editorState = $this->buildEditorState($section, $languages, $sectionTypes);
        $workspaceViewData = $this->workspaceViewData($page);
        $sidebarEditor = app(SectionSidebarEditorViewDataFactory::class)->build(
            $section,
            $sectionTypes,
            (string) ($workspaceViewData['workspaceMode'] ?? $this->workspaceMode()),
            (string) ($workspaceViewData['workspaceRoutePrefix'] ?? $this->workspaceRoutePrefix()),
            is_array($workspaceViewData['workspaceRouteBaseParameters'] ?? null)
                ? $workspaceViewData['workspaceRouteBaseParameters']
                : $this->workspaceBaseRouteParameters($page),
        );

        return [
            'page' => $page,
            'section' => $section,
            'languages' => $languages,
            'sectionTypes' => $sectionTypes,
            'editorState' => $editorState,
            'editorFormPartial' => $this->editorFormPartial($page, $section),
            'sidebarEditor' => $sidebarEditor,
        ] + $workspaceViewData;
    }

    protected function editorFormPartial(Page $page, Section $section): string
    {
        return 'dashboard.pages.sections.partials.dynamic-editor-form';
    }

    protected function normalizeSubmittedTranslations(
        string $type,
        array $translations,
        ?SectionDefinition $sectionDefinition = null,
    ): array {
        if (! $sectionDefinition instanceof SectionDefinition) {
            throw ValidationException::withMessages([
                'section_definition_id' => __('Normal page sections must stay linked to an active section definition.'),
            ]);
        }

        return $this->normalizeDefinitionLinkedTranslations($translations, $sectionDefinition);
    }

    protected function normalizeDefinitionLinkedTranslations(array $translations, SectionDefinition $sectionDefinition): array
    {
        $translations = $this->syncDynamicDefinitionSharedContent($translations, $sectionDefinition);
        $normalizer = app(DynamicSectionContentNormalizer::class);

        foreach ($translations as $key => $translationData) {
            $content = is_array($translationData['content'] ?? null) ? $translationData['content'] : [];
            $translations[$key]['content'] = $normalizer->normalize($content, $sectionDefinition);
        }

        return $translations;
    }

    protected function syncDynamicDefinitionSharedContent(array $translations, SectionDefinition $sectionDefinition): array
    {
        if ($translations === []) {
            return $translations;
        }

        $sectionDefinition->loadMissing([
            'fields' => fn($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        $sharedFields = $sectionDefinition->fields
            ->filter(fn($field) => ! $field->isTranslatable())
            ->values();

        if ($sharedFields->isEmpty()) {
            return $translations;
        }

        $defaultLocaleKey = $this->submittedTranslationKeyForLocale($translations, app()->getLocale())
            ?? array_key_first($translations);

        if ($defaultLocaleKey === null) {
            return $translations;
        }

        $sourceContent = is_array($translations[$defaultLocaleKey]['content'] ?? null)
            ? $translations[$defaultLocaleKey]['content']
            : [];

        foreach ($sharedFields as $field) {
            if (! array_key_exists($field->field_key, $sourceContent)) {
                continue;
            }

            foreach ($translations as $key => $translationData) {
                $content = is_array($translationData['content'] ?? null) ? $translationData['content'] : [];
                $content[$field->field_key] = $sourceContent[$field->field_key];
                $translations[$key]['content'] = $content;
            }
        }

        return $translations;
    }

    protected function submittedTranslationKeyForLocale(array $translations, string $locale): int|string|null
    {
        foreach ($translations as $key => $translationData) {
            $submittedLocale = is_array($translationData)
                ? (string) ($translationData['locale'] ?? $key)
                : (string) $key;

            if ($submittedLocale === $locale) {
                return $key;
            }
        }

        return null;
    }

    protected function resolveLinkedSectionDefinitionForUpdate(
        Section $section,
        ?int $sectionDefinitionId = null,
    ): ?SectionDefinition {
        $sectionDefinitionId ??= $section->section_definition_id;

        if (! $sectionDefinitionId || ! Schema::hasTable('section_definitions')) {
            throw ValidationException::withMessages([
                'section_definition_id' => __('Normal page sections must stay linked to an active section definition.'),
            ]);
        }

        $definition = SectionDefinition::query()
            ->with([
                'fields' => fn($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->whereKey($sectionDefinitionId)
            ->where('is_active', true)
            ->first();

        if (! $definition instanceof SectionDefinition) {
            throw ValidationException::withMessages([
                'section_definition_id' => __('The linked section definition is no longer available.'),
            ]);
        }

        return $definition;
    }

    protected function workspaceSectionTypes(): array
    {
        return $this->sectionLibraryTypes();
    }

    protected function sectionTypesForSection(Section $section): array
    {
        return $this->workspaceSectionTypes();
    }

    protected function sectionDefinitionIdRulesForCreate(): array
    {
        $rules = ['required', 'integer'];

        if (Schema::hasTable('section_definitions')) {
            $rules[] = Rule::exists('section_definitions', 'id')
                ->where(fn($query) => $query
                    ->where('is_active', true)
                    ->where('is_visible', true));
        }

        return $rules;
    }

    protected function sectionDefinitionIdRulesForUpdate(Section $section): array
    {
        // nullable: the form sends the section's current definition ID; if it
        // arrives as empty (e.g. the sections table is missing the column on an
        // older production DB) the update() method falls back to the section's
        // own section_definition_id FK rather than failing validation.
        $rules = ['nullable', 'integer'];

        if (Schema::hasTable('section_definitions')) {
            $rules[] = Rule::exists('section_definitions', 'id')
                ->where(fn($query) => $query->where('is_active', true));
        }

        return $rules;
    }

    protected function buildEditorState(Section $section, iterable $languages, array $sectionTypes): array
    {
        return app(SectionEditorDataFactory::class)->make($section, $languages, $sectionTypes);
    }
}
