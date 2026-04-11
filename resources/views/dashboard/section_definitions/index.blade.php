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
                        <div class="mt-3 sm:mt-0">
                            <a href="{{ route('dashboard.section_definitions.create') }}" class="btn btn-primary">
                                {{ __('Add Definition') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
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
                                            ? ($templateRegistry[$primaryTemplateKey]['label'] ?? $primaryTemplate->label ?? $primaryTemplateKey)
                                            : null;
                                    @endphp
                                    <tr>
                                        <td>{{ $sectionDefinition->id }}</td>
                                        <td>
                                            <div class="font-medium text-slate-900">{{ $sectionDefinition->label }}</div>
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
                                                <div class="font-medium text-slate-900">{{ $primaryTemplateLabel }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $primaryTemplateKey }}</div>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="rounded px-2 py-1 text-xs {{ $sectionDefinition->editor_mode === \App\Models\Sections\SectionDefinition::EDITOR_MODE_CUSTOM_PRESET ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ $sectionDefinition->editor_mode === \App\Models\Sections\SectionDefinition::EDITOR_MODE_CUSTOM_PRESET ? __('Custom') : __('Dynamic') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="font-medium text-slate-900">{{ $sectionDefinition->fields_count }}</div>
                                            <div class="mt-1">
                                                <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}"
                                                    class="text-xs text-primary">
                                                    {{ __('Manage Fields') }}
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="rounded px-2 py-1 text-xs {{ $sectionDefinition->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $sectionDefinition->is_active ? __('Active') : __('Inactive') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="rounded px-2 py-1 text-xs {{ $sectionDefinition->is_visible ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $sectionDefinition->is_visible ? __('Visible') : __('Hidden') }}
                                            </span>
                                        </td>
                                        <td>{{ $sectionDefinition->sort_order }}</td>
                                        <td>
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('dashboard.section_definitions.edit', $sectionDefinition) }}"
                                                    class="btn btn-sm btn-secondary">
                                                    {{ __('Edit') }}
                                                </a>
                                                <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}"
                                                    class="btn btn-sm btn-light">
                                                    {{ __('Fields') }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="py-8 text-center text-slate-500">
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
