<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ __('Home') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ __('Section Definitions') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ __('Section Definitions') }}</h2>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded bg-red-100 px-4 py-2 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded bg-red-100 px-4 py-2 text-red-800">
            <ul class="mb-0 list-disc ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <div>
                            <h5 class="mb-1">{{ __('Definition Registry') }}</h5>
                            <p class="mb-0 text-sm text-slate-500">
                                {{ __('Manage the developer-facing section blueprint records only. Field builders and rendering contracts stay separate.') }}
                            </p>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 sm:mt-0">
                            <a href="{{ route('dashboard.section_definitions.export') }}" class="btn btn-light">
                                {{ __('Export All') }}
                            </a>
                            <button type="submit" form="section-definitions-export-selected" class="btn btn-light">
                                {{ __('Export Selected') }}
                            </button>
                            <a href="{{ route('dashboard.section_definitions.import') }}" class="btn btn-secondary">
                                {{ __('Import JSON') }}
                            </a>
                            @can('create', \App\Models\Sections\SectionDefinition::class)
                            <a href="{{ route('dashboard.section_definitions.create') }}" class="btn btn-primary">
                                {{ __('Add Definition') }}
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <form id="section-definitions-export-selected" method="POST"
                        action="{{ route('dashboard.section_definitions.export-selected') }}">
                        @csrf
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
                                    <th class="w-10">
                                        <span class="sr-only">{{ __('Select') }}</span>
                                    </th>
                                    <th>#</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Key') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Template') }}</th>
                                    <th>{{ __('Editor Mode') }}</th>
                                    <th>{{ __('Fields') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Library') }}</th>
                                    <th>{{ __('Sort Order') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sectionDefinitions as $sectionDefinition)
                                    @php
                                        $primaryTemplate = $sectionDefinition->templates->first();
                                        $primaryTemplateKey = $primaryTemplate?->template_key;
                                        $primaryTemplateLabel = $primaryTemplateKey
                                            ? $templateRegistry[$primaryTemplateKey]['label'] ??
                                                ($primaryTemplate->label ?? $primaryTemplateKey)
                                            : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" form="section-definitions-export-selected"
                                                name="definition_ids[]" value="{{ $sectionDefinition->id }}"
                                                class="form-check-input"
                                                aria-label="{{ __('Select :name', ['name' => $sectionDefinition->label]) }}">
                                        </td>
                                        <td>{{ $sectionDefinition->id }}</td>
                                        <td>
                                            <div class="font-medium text-slate-900">{{ $sectionDefinition->label }}
                                            </div>
                                            @if ($sectionDefinition->description)
                                                <div class="mt-1 text-xs text-slate-500">
                                                    {{ \Illuminate\Support\Str::limit($sectionDefinition->description, 90) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">
                                                {{ $sectionDefinition->section_key }}
                                            </code>
                                        </td>
                                        <td>{{ $sectionDefinition->category ?: '-' }}</td>
                                        <td>
                                            @if ($primaryTemplateKey)
                                                <div class="font-medium text-slate-900">{{ $primaryTemplateLabel }}
                                                </div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $primaryTemplateKey }}
                                                </div>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800">
                                                {{ __('Dynamic') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="font-medium text-slate-900">
                                                {{ $sectionDefinition->fields_count }}</div>
                                            <div class="mt-1">
                                                <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}"
                                                    class="text-xs text-primary">
                                                    {{ __('Manage Fields') }}
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="rounded px-2 py-1 text-xs {{ $sectionDefinition->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $sectionDefinition->is_active ? __('Active') : __('Inactive') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="rounded px-2 py-1 text-xs {{ $sectionDefinition->is_visible ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $sectionDefinition->is_visible ? __('Visible') : __('Hidden') }}
                                            </span>
                                        </td>
                                        <td>{{ $sectionDefinition->sort_order }}</td>
                                        <td>
                                            <div class="flex flex-wrap gap-2">
                                                @can('update', $sectionDefinition)
                                                <a href="{{ route('dashboard.section_definitions.edit', $sectionDefinition) }}"
                                                    class="btn btn-sm btn-secondary">
                                                    {{ __('Edit') }}
                                                </a>
                                                @endcan
                                                @can('viewAny', \App\Models\Sections\SectionDefinitionField::class)
                                                <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}"
                                                    class="btn btn-sm btn-light">
                                                    {{ __('Fields') }}
                                                </a>
                                                @endcan
                                                @can('delete', $sectionDefinition)
                                                <form
                                                    action="{{ route('dashboard.section_definitions.destroy', $sectionDefinition) }}"
                                                    method="POST"
                                                    onsubmit="return confirm(@js(__('Delete this section definition? This will also delete :count linked section instance(s) and their translations. Media records, uploaded files, Blade files, and config entries will not be deleted.', ['count' => $sectionDefinition->sections_count])));">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        {{ __('Delete') }}
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="py-8 text-center text-slate-500">
                                            {{ __('No section definitions found yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $sectionDefinitions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
