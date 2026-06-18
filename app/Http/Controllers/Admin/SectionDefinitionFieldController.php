<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectionDefinitionFieldRequest;
use App\Http\Requests\Admin\UpdateSectionDefinitionFieldRequest;
use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use App\Support\Sections\FieldPresetLibrary;
use App\Support\Sections\SectionDefinitionFieldFormDataFactory;
use App\Support\Sections\SectionDefinitionLocaleProvider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionDefinitionFieldController extends Controller
{
    public function __construct(
        protected SectionDefinitionLocaleProvider $localeProvider,
        protected SectionDefinitionFieldFormDataFactory $formDataFactory,
    ) {}

    /**
     * List fields for one section definition, grouped for easier review.
     */
    public function index(SectionDefinition $sectionDefinition): View
    {
        $this->authorize('viewAny', SectionDefinitionField::class);

        $fields = $sectionDefinition->fields()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $fieldGroups = $fields->groupBy(fn (SectionDefinitionField $field) => $field->group_name ?: t('dashboard.General', 'عام'));

        return view('dashboard.section_definitions.fields.index', [
            'sectionDefinition' => $sectionDefinition,
            'fields' => $fields,
            'fieldGroups' => $fieldGroups,
        ]);
    }

    /**
     * Show the create form for a new field definition.
     */
    public function create(SectionDefinition $sectionDefinition): View
    {
        $this->authorize('create', SectionDefinitionField::class);

        $field = new SectionDefinitionField([
            'field_type' => SectionDefinitionField::FIELD_TYPE_TEXT,
            'field_scope' => SectionDefinitionField::FIELD_SCOPE_TRANSLATABLE,
            'is_required' => false,
            'is_active' => true,
            'sort_order' => ((int) $sectionDefinition->fields()->max('sort_order')) + 1,
        ]);

        return view('dashboard.section_definitions.fields.create', $this->formViewData($sectionDefinition, $field));
    }

    /**
     * Store a new field definition under the selected section definition.
     */
    public function store(
        StoreSectionDefinitionFieldRequest $request,
        SectionDefinition $sectionDefinition,
    ): RedirectResponse {
        $this->authorize('create', SectionDefinitionField::class);

        $validated = $request->validated();
        $localeCodes = array_map(
            fn (array $locale) => $locale['code'],
            $this->localeProvider->all(),
        );

        try {
            $sectionDefinition->fields()->create(
                $this->formDataFactory->persistableAttributes($validated, $localeCodes),
            );
        } catch (\LogicException $e) {
            return back()->withInput()->withErrors(['item_schema' => $e->getMessage()]);
        }

        return redirect()
            ->route('dashboard.section_definitions.fields.index', $sectionDefinition)
            ->with('ok', t('dashboard.Field_Created', 'تم إنشاء الحقل بنجاح.'));
    }

    /**
     * Show the edit form for one field definition.
     */
    public function edit(
        SectionDefinition $sectionDefinition,
        SectionDefinitionField $field,
    ): View {
        $this->authorize('update', $field);
        $this->ensureFieldBelongsToDefinition($sectionDefinition, $field);

        return view('dashboard.section_definitions.fields.edit', $this->formViewData($sectionDefinition, $field));
    }

    /**
     * Update an existing field definition.
     */
    public function update(
        UpdateSectionDefinitionFieldRequest $request,
        SectionDefinition $sectionDefinition,
        SectionDefinitionField $field,
    ): RedirectResponse {
        $this->authorize('update', $field);
        $this->ensureFieldBelongsToDefinition($sectionDefinition, $field);

        $validated = $request->validated();
        $localeCodes = array_map(
            fn (array $locale) => $locale['code'],
            $this->localeProvider->all(),
        );

        try {
            $field->update(
                $this->formDataFactory->persistableAttributes($validated, $localeCodes),
            );
        } catch (\LogicException $e) {
            return back()->withInput()->withErrors(['item_schema' => $e->getMessage()]);
        }

        return redirect()
            ->route('dashboard.section_definitions.fields.index', $sectionDefinition)
            ->with('ok', t('dashboard.Field_Updated', 'تم تحديث الحقل بنجاح.'));
    }

    /**
     * Persist manual sort-order changes from the index screen.
     */
    public function reorder(Request $request, SectionDefinition $sectionDefinition): RedirectResponse
    {
        $this->authorize('update', SectionDefinitionField::class);

        $validated = $request->validate([
            'sort_orders' => ['required', 'array'],
            'sort_orders.*' => ['required', 'integer', 'min:0'],
        ]);

        $fields = $sectionDefinition->fields()
            ->whereIn('id', array_keys($validated['sort_orders']))
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($validated, $fields) {
            foreach ($validated['sort_orders'] as $fieldId => $sortOrder) {
                $field = $fields->get((int) $fieldId);

                if (! $field) {
                    continue;
                }

                $field->update([
                    'sort_order' => (int) $sortOrder,
                ]);
            }
        });

        return redirect()
            ->route('dashboard.section_definitions.fields.index', $sectionDefinition)
            ->with('ok', t('dashboard.Field_Reordered', 'تم حفظ ترتيب الحقول بنجاح.'));
    }

    /**
     * Apply a field preset — bulk-create a group of pre-defined fields.
     *
     * Existing field keys are skipped to avoid duplicates.
     * sort_order starts after the current max to preserve existing ordering.
     */
    public function applyPreset(Request $request, SectionDefinition $sectionDefinition): RedirectResponse
    {
        $this->authorize('create', SectionDefinitionField::class);

        $validated = $request->validate([
            'preset_key' => ['required', 'string', 'in:' . implode(',', FieldPresetLibrary::keys())],
        ]);

        $preset = FieldPresetLibrary::get($validated['preset_key']);

        if (! $preset) {
            return back()->with('error', t('dashboard.Preset_Invalid', 'مجموعة الحقول غير موجودة.'));
        }

        // Collect existing field_key values so we can skip duplicates.
        $existingKeys = $sectionDefinition->fields()
            ->pluck('field_key')
            ->flip(); // key → index for O(1) lookup

        $nextSortOrder = ((int) $sectionDefinition->fields()->max('sort_order')) + 1;

        $addedCount = 0;

        DB::transaction(function () use ($sectionDefinition, $preset, $existingKeys, &$nextSortOrder, &$addedCount) {
            foreach ($preset['fields'] as $fieldDef) {
                $fieldKey = (string) ($fieldDef['field_key'] ?? '');

                // Skip if this key already exists in the definition.
                if ($fieldKey === '' || $existingKeys->has($fieldKey)) {
                    continue;
                }

                $attributes = [
                    'field_key'   => $fieldKey,
                    'label'       => $fieldDef['label'] ?? $fieldKey,
                    'field_type'  => $fieldDef['field_type'] ?? SectionDefinitionField::FIELD_TYPE_TEXT,
                    'field_scope' => $fieldDef['field_scope'] ?? SectionDefinitionField::FIELD_SCOPE_TRANSLATABLE,
                    'is_required' => (bool) ($fieldDef['is_required'] ?? false),
                    'is_active'   => (bool) ($fieldDef['is_active'] ?? true),
                    'sort_order'  => $nextSortOrder++,
                    'schema'      => $fieldDef['schema'] ?? null,
                    'options'     => $fieldDef['options'] ?? null,
                ];

                $sectionDefinition->fields()->create($attributes);
                $addedCount++;
            }
        });

        if ($addedCount === 0) {
            return redirect()
                ->route('dashboard.section_definitions.fields.index', $sectionDefinition)
                ->with('ok', t('dashboard.Preset_None_Added', 'جميع حقول هذه المجموعة موجودة بالفعل.'));
        }

        $message = strtr(
            t('dashboard.Preset_Applied', 'تمت إضافة :count حقل بنجاح.'),
            [':count' => $addedCount]
        );

        return redirect()
            ->route('dashboard.section_definitions.fields.index', $sectionDefinition)
            ->with('ok', $message);
    }

    /**
     * Delete a field definition.
     */
    public function destroy(
        SectionDefinition $sectionDefinition,
        SectionDefinitionField $field,
    ): RedirectResponse {
        $this->authorize('delete', $field);
        $this->ensureFieldBelongsToDefinition($sectionDefinition, $field);

        $field->delete();

        return redirect()
            ->route('dashboard.section_definitions.fields.index', $sectionDefinition)
            ->with('ok', t('dashboard.Field_Deleted', 'تم حذف الحقل بنجاح.'));
    }

    /**
     * Build shared create/edit form data.
     *
     * @return array<string, mixed>
     */
    protected function formViewData(SectionDefinition $sectionDefinition, SectionDefinitionField $field): array
    {
        $locales = $this->localeProvider->all();
        $groupSuggestions = $sectionDefinition->fields()
            ->whereNotNull('group_name')
            ->where('group_name', '!=', '')
            ->orderBy('group_name')
            ->pluck('group_name')
            ->unique()
            ->values()
            ->all();

        return [
            'sectionDefinition' => $sectionDefinition,
            'field' => $field,
            'locales' => $locales,
            'groupSuggestions' => $groupSuggestions,
        ] + $this->formDataFactory->build($field, $locales);
    }

    protected function ensureFieldBelongsToDefinition(
        SectionDefinition $sectionDefinition,
        SectionDefinitionField $field,
    ): void {
        abort_unless((int) $field->section_definition_id === (int) $sectionDefinition->id, 404);
    }
}
