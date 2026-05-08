<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportSectionDefinitionsRequest;
use App\Models\Sections\SectionDefinition;
use App\Support\Sections\SectionDefinitionExportService;
use App\Support\Sections\SectionDefinitionImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Throwable;

class SectionDefinitionImportExportController extends Controller
{
    public function __construct(
        protected SectionDefinitionExportService $exportService,
        protected SectionDefinitionImportService $importService,
    ) {}

    public function exportAll(): Response
    {
        $this->authorize('viewAny', SectionDefinition::class);

        return $this->downloadExport();
    }

    public function exportSelected(Request $request): Response|RedirectResponse
    {
        $this->authorize('viewAny', SectionDefinition::class);

        $validated = $request->validate([
            'definition_ids' => ['required', 'array', 'min:1'],
            'definition_ids.*' => ['integer', 'exists:section_definitions,id'],
        ]);

        $ids = array_map('intval', $validated['definition_ids']);

        return $this->downloadExport($ids);
    }

    public function importForm(): View
    {
        $this->authorize('create', SectionDefinition::class);

        return view('dashboard.section_definitions.import');
    }

    public function preview(ImportSectionDefinitionsRequest $request): View|RedirectResponse
    {
        $this->authorize('create', SectionDefinition::class);

        try {
            $json = (string) file_get_contents($request->file('definitions_json')->getRealPath());
            $payload = $this->importService->decodeJson($json);
            $preview = $this->importService->preview($payload);
            $token = (string) Str::uuid();

            session()->put("section_definition_import.$token", $payload);

            return view('dashboard.section_definitions.import-preview', [
                'preview' => $preview,
                'token' => $token,
                'strategies' => [
                    SectionDefinitionImportService::STRATEGY_SKIP_EXISTING => __('Create missing definitions and skip existing definitions'),
                    SectionDefinitionImportService::STRATEGY_UPDATE_EXISTING => __('Create missing definitions and update existing definitions'),
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('dashboard.section_definitions.import')
                ->with('error', $exception->getMessage() ?: __('Section definitions JSON could not be previewed.'));
        }
    }

    public function apply(Request $request): RedirectResponse
    {
        $this->authorize('create', SectionDefinition::class);

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'strategy' => ['required', 'string', 'in:' . implode(',', [
                SectionDefinitionImportService::STRATEGY_SKIP_EXISTING,
                SectionDefinitionImportService::STRATEGY_UPDATE_EXISTING,
            ])],
        ]);

        $sessionKey = 'section_definition_import.' . $validated['token'];
        $payload = session()->pull($sessionKey);

        if (! is_array($payload)) {
            return redirect()
                ->route('dashboard.section_definitions.import')
                ->with('error', __('Import preview expired. Upload the JSON file again.'));
        }

        try {
            $stats = $this->importService->import($payload, $validated['strategy']);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('dashboard.section_definitions.import')
                ->with('error', __('Section definitions could not be imported. Please review the JSON and try again.'));
        }

        return redirect()
            ->route('dashboard.section_definitions.index')
            ->with('success', __('Import completed. Created: :created, Updated: :updated, Skipped: :skipped, Invalid: :invalid.', $stats));
    }

    /**
     * @param  array<int, int>|null  $ids
     */
    protected function downloadExport(?array $ids = null): Response
    {
        $payload = $this->exportService->export($ids);
        $json = $this->exportService->toJson($payload);

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $this->exportService->filename($ids) . '"',
        ]);
    }
}
