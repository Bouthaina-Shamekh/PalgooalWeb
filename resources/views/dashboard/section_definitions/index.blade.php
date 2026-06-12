<x-dashboard-layout>

    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'الرئيسية') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                    {{ t('dashboard.Section_Definitions', 'تعريفات الأقسام') }}
                </li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Section_Definitions', 'تعريفات الأقسام') }}</h2>
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-circle-check me-2"></i>{{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card table-card">

        {{-- Toolbar --}}
        <div class="card-header">
            <form method="GET" action="{{ route('dashboard.section_definitions.index') }}"
                  class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-wrap">

                {{-- Search --}}
                <div class="relative flex-1" style="min-width:200px;">
                    <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                        <i class="ti ti-search text-base"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search ?? '' }}"
                           placeholder="{{ t('dashboard.Search_Sections', 'بحث بالاسم أو المفتاح أو الفئة...') }}"
                           class="w-full border rounded-xl pr-9 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                </div>

                {{-- Per-page --}}
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-sm text-gray-500 whitespace-nowrap">{{ t('dashboard.Per_Page', 'لكل صفحة') }}</span>
                    <select name="per_page" onchange="this.form.submit()"
                            class="border rounded-xl px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                        @foreach([10, 25, 50] as $n)
                            <option value="{{ $n }}" {{ ($perPage ?? 20) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Clear --}}
                @if($search)
                    <a href="{{ route('dashboard.section_definitions.index') }}"
                       class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                        <i class="ti ti-x text-base"></i>
                        {{ t('dashboard.Clear_Search', 'مسح') }}
                    </a>
                @endif

                {{-- Action buttons --}}
                <div class="flex gap-2 shrink-0 ms-auto flex-wrap">
                    <a href="{{ route('dashboard.section_definitions.export') }}" class="btn btn-light btn-sm">
                        <i class="ti ti-download me-1"></i>
                        {{ t('dashboard.Export_All', 'تصدير الكل') }}
                    </a>
                    <button type="submit" form="section-definitions-export-selected" class="btn btn-light btn-sm">
                        <i class="ti ti-file-export me-1"></i>
                        {{ t('dashboard.Export_Selected', 'تصدير المحدد') }}
                    </button>
                    <a href="{{ route('dashboard.section_definitions.import') }}" class="btn btn-light btn-sm">
                        <i class="ti ti-upload me-1"></i>
                        {{ t('dashboard.Import_JSON', 'استيراد JSON') }}
                    </a>
                    @can('create', \App\Models\Sections\SectionDefinition::class)
                        <a href="{{ route('dashboard.section_definitions.create') }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-plus me-1"></i>
                            {{ t('dashboard.Add_Definition', 'إضافة تعريف') }}
                        </a>
                    @endcan
                </div>

            </form>
        </div>

        {{-- Hidden export-selected form --}}
        <form id="section-definitions-export-selected" method="POST"
              action="{{ route('dashboard.section_definitions.export-selected') }}">
            @csrf
        </form>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <input type="checkbox" id="select-all" class="form-check-input"
                                       title="{{ t('dashboard.Select_All', 'تحديد الكل') }}">
                            </th>
                            <th>#</th>
                            <th>{{ t('dashboard.Section_Label', 'الاسم') }}</th>
                            <th>{{ t('dashboard.Section_Key', 'المفتاح') }}</th>
                            <th>{{ t('dashboard.Category', 'الفئة') }}</th>
                            <th>{{ t('dashboard.Template', 'القالب') }}</th>
                            <th>{{ t('dashboard.Fields', 'الحقول') }}</th>
                            <th>{{ t('dashboard.Status', 'الحالة') }}</th>
                            <th>{{ t('dashboard.Library', 'المكتبة') }}</th>
                            <th>{{ t('dashboard.Sort_Order', 'الترتيب') }}</th>
                            <th>{{ t('dashboard.Actions', 'إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sectionDefinitions as $def)
                            @php
                                $primaryTemplate      = $def->templates->first();
                                $primaryTemplateKey   = $primaryTemplate?->template_key;
                                $primaryTemplateLabel = $primaryTemplateKey
                                    ? ($templateRegistry[$primaryTemplateKey]['label'] ?? ($primaryTemplate->label ?? $primaryTemplateKey))
                                    : null;
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" form="section-definitions-export-selected"
                                           name="definition_ids[]" value="{{ $def->id }}"
                                           class="form-check-input def-checkbox">
                                </td>
                                <td class="text-muted text-sm">{{ $def->id }}</td>
                                <td>
                                    <div class="font-medium text-gray-800">{{ $def->label }}</div>
                                    @if($def->description)
                                        <div class="text-xs text-gray-400 mt-0.5">
                                            {{ \Illuminate\Support\Str::limit($def->description, 80) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <code class="bg-gray-100 rounded px-2 py-0.5 text-xs text-gray-700 font-mono">
                                        {{ $def->section_key }}
                                    </code>
                                </td>
                                <td class="text-sm text-gray-500">{{ $def->category ?: '—' }}</td>
                                <td>
                                    @if($primaryTemplateKey)
                                        <div class="text-sm font-medium text-gray-800">{{ $primaryTemplateLabel }}</div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $primaryTemplateKey }}</div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-medium text-gray-800">{{ $def->fields_count }}</span>
                                    <div class="mt-0.5">
                                        <a href="{{ route('dashboard.section_definitions.fields.index', $def) }}"
                                           class="text-xs text-primary hover:underline">
                                            {{ t('dashboard.Manage_Fields', 'إدارة الحقول') }}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    @if($def->is_active)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700">
                                            {{ t('dashboard.Active', 'نشط') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700">
                                            {{ t('dashboard.Inactive', 'معطل') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($def->is_visible)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-700">
                                            {{ t('dashboard.Visible', 'ظاهر') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-500">
                                            {{ t('dashboard.Hidden', 'مخفي') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-sm text-gray-500">{{ $def->sort_order }}</td>
                                <td class="whitespace-nowrap">
                                    <div class="flex items-center gap-0.5">
                                        @can('update', $def)
                                            <a href="{{ route('dashboard.section_definitions.edit', $def) }}"
                                               class="w-8 h-8 rounded-xl inline-flex items-center justify-center text-gray-500 hover:text-primary hover:bg-primary/10 transition"
                                               title="{{ t('dashboard.Edit', 'تعديل') }}">
                                                <i class="ti ti-edit text-lg leading-none"></i>
                                            </a>
                                        @endcan
                                        @can('viewAny', \App\Models\Sections\SectionDefinitionField::class)
                                            <a href="{{ route('dashboard.section_definitions.fields.index', $def) }}"
                                               class="w-8 h-8 rounded-xl inline-flex items-center justify-center text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition"
                                               title="{{ t('dashboard.Manage_Fields', 'إدارة الحقول') }}">
                                                <i class="ti ti-layout-list text-lg leading-none"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $def)
                                            <form action="{{ route('dashboard.section_definitions.destroy', $def) }}"
                                                  method="POST" style="display:inline-block"
                                                  onsubmit="return confirm('{{ t('dashboard.Confirm_Delete_Section', 'حذف هذا التعريف؟') }} ({{ $def->sections_count }} {{ t('dashboard.Sections', 'أقسام') }})')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="w-8 h-8 rounded-xl inline-flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 transition"
                                                        title="{{ t('dashboard.Delete', 'حذف') }}">
                                                    <i class="ti ti-trash text-lg leading-none"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
                                    <div class="flex flex-col items-center justify-center py-16 text-center">
                                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        @if($search)
                                            <p class="text-base font-semibold text-gray-700 mb-1">
                                                {{ t('dashboard.No_Search_Results', 'لا توجد نتائج') }}
                                            </p>
                                            <p class="text-sm text-gray-400 mb-5">
                                                {{ t('dashboard.Try_Different_Search', 'جرّب كلمة بحث مختلفة') }}
                                            </p>
                                            <a href="{{ route('dashboard.section_definitions.index') }}" class="btn btn-light btn-sm">
                                                {{ t('dashboard.Clear_Search', 'مسح البحث') }}
                                            </a>
                                        @else
                                            <p class="text-base font-semibold text-gray-700 mb-1">
                                                {{ t('dashboard.No_Section_Definitions', 'لا توجد تعريفات أقسام بعد') }}
                                            </p>
                                            <p class="text-sm text-gray-400 mb-5">
                                                {{ t('dashboard.No_Section_Definitions_Desc', 'أضف تعريفاً جديداً للبدء') }}
                                            </p>
                                            @can('create', \App\Models\Sections\SectionDefinition::class)
                                                <a href="{{ route('dashboard.section_definitions.create') }}" class="btn btn-primary btn-sm">
                                                    <i class="ti ti-plus me-1"></i>
                                                    {{ t('dashboard.Add_Definition', 'إضافة تعريف') }}
                                                </a>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($sectionDefinitions->hasPages())
                <div class="p-4">
                    {{ $sectionDefinitions->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
    document.getElementById('select-all')?.addEventListener('change', function () {
        document.querySelectorAll('.def-checkbox').forEach(cb => cb.checked = this.checked);
    });
    </script>
    @endpush

</x-dashboard-layout>
