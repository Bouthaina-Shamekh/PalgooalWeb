<x-dashboard-layout>
    @php
        $primaryTemplateKey = $sectionDefinition->primaryTemplateKey();
        $isDynamicDefinition = $sectionDefinition->editor_mode === \App\Models\Sections\SectionDefinition::EDITOR_MODE_DYNAMIC;
    @endphp

    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ __('Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.section_definitions.index') }}">{{ __('Section Definitions') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ __('Field Definitions') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ __('Field Definitions') }}</h2>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6 gap-y-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h5 class="mb-1">{{ $sectionDefinition->label }}</h5>
                            <p class="mb-0 text-sm text-slate-500">
                                <code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $sectionDefinition->section_key }}</code>
                                <span class="mx-2">/</span>
                                {{ __('Manage the field schema for this section definition only. No frontend rendering is changed here.') }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded px-2 py-1 text-xs {{ $isDynamicDefinition ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $isDynamicDefinition ? __('Dynamic') : __('Custom Preset') }}
                                </span>
                                <span class="rounded px-2 py-1 text-xs {{ $sectionDefinition->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $sectionDefinition->is_active ? __('Active') : __('Inactive') }}
                                </span>
                                <span class="rounded px-2 py-1 text-xs {{ $sectionDefinition->is_visible ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $sectionDefinition->is_visible ? __('Visible In Library') : __('Hidden From Library') }}
                                </span>
                                <span class="rounded px-2 py-1 text-xs {{ $primaryTemplateKey ? 'bg-indigo-100 text-indigo-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ $primaryTemplateKey ? __('Template') . ': ' . $primaryTemplateKey : __('No Template Selected') }}
                                </span>
                            </div>
                            <p class="mt-3 mb-0 text-xs text-slate-500">
                                @if ($isDynamicDefinition)
                                    {{ __('Dynamic definitions become reusable library entries from the database when they are active, visible in the library, and linked to a stable template key. Runtime can still use a code-side override, but normal dynamic renderers may now resolve by convention from that key.') }}
                                @else
                                    {{ __('Custom preset definitions still rely on their code-side preset editor. Fields defined here remain stored metadata and do not change that runtime behavior.') }}
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('dashboard.section_definitions.edit', $sectionDefinition) }}" class="btn btn-light">
                                {{ __('Back To Definition') }}
                            </a>
                            @can('create', \App\Models\Sections\SectionDefinitionField::class)
                            <a href="{{ route('dashboard.section_definitions.fields.create', $sectionDefinition) }}" class="btn btn-primary">
                                {{ __('Add Field') }}
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            @if ($fields->isEmpty())
                <div class="card">
                    <div class="card-body py-12 text-center">
                        <h5 class="mb-2">{{ __('No field definitions yet') }}</h5>
                        <p class="mb-4 text-sm text-slate-500">
                            {{ __('Add the first field to start defining the editable schema for this section blueprint.') }}
                        </p>
                        @can('create', \App\Models\Sections\SectionDefinitionField::class)
                        <a href="{{ route('dashboard.section_definitions.fields.create', $sectionDefinition) }}" class="btn btn-primary">
                            {{ __('Create First Field') }}
                        </a>
                        @endcan
                    </div>
                </div>
            @else
                @can('update', \App\Models\Sections\SectionDefinitionField::class)
                <form action="{{ route('dashboard.section_definitions.fields.reorder', $sectionDefinition) }}" method="POST" class="space-y-6">
                    @csrf

                    @foreach ($fieldGroups as $groupLabel => $groupFields)
                        <div class="card">
                            <div class="card-header">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h5 class="mb-1">{{ $groupLabel }}</h5>
                                        <p class="mb-0 text-sm text-slate-500">
                                            {{ __('Fields are rendered here in their current sort order. Update the numbers and save to reorder.') }}
                                        </p>
                                    </div>
                                    <span class="rounded bg-slate-100 px-3 py-1 text-xs text-slate-700">
                                        {{ trans_choice(':count field|:count fields', $groupFields->count(), ['count' => $groupFields->count()]) }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="table-responsive">
                                    <table class="table table-hover w-full">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Sort') }}</th>
                                                <th>{{ __('Label') }}</th>
                                                <th>{{ __('Key') }}</th>
                                                <th>{{ __('Type') }}</th>
                                                <th>{{ __('Scope') }}</th>
                                                <th>{{ __('Required') }}</th>
                                                <th>{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($groupFields as $field)
                                                <tr>
                                                    <td class="w-28">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            name="sort_orders[{{ $field->id }}]"
                                                            class="form-control"
                                                            value="{{ $field->sort_order }}"
                                                        >
                                                    </td>
                                                    <td>
                                                        <div class="font-medium text-slate-900">{{ $field->label }}</div>
                                                        @if ($field->validation_rules)
                                                            <div class="mt-1 text-xs text-slate-500">
                                                                {{ __('Validation') }}: {{ implode(', ', $field->validation_rules) }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $field->field_key }}</code>
                                                    </td>
                                                    <td>{{ \Illuminate\Support\Str::headline($field->field_type) }}</td>
                                                    <td>
                                                        <span class="rounded px-2 py-1 text-xs {{ $field->isTranslatable() ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-700' }}">
                                                            {{ $field->isTranslatable() ? __('Translatable') : __('Shared') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="rounded px-2 py-1 text-xs {{ $field->is_required ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                                            {{ $field->is_required ? __('Required') : __('Optional') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @can('update', $field)
                                                        <a href="{{ route('dashboard.section_definitions.fields.edit', [$sectionDefinition, $field]) }}"
                                                            class="btn btn-sm btn-secondary">
                                                            {{ __('Edit') }}
                                                        </a>
                                                        @endcan
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Save Field Order') }}
                        </button>
                    </div>
                </form>
                @endcan
            @endif
        </div>
    </div>
</x-dashboard-layout>
