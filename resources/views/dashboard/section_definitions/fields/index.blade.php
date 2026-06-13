<x-dashboard-layout>
    @php
        $primaryTemplateKey = $sectionDefinition->primaryTemplateKey();
        $isDynamicDefinition = $sectionDefinition->editor_mode === \App\Models\Sections\SectionDefinition::EDITOR_MODE_DYNAMIC;
    @endphp

    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'الرئيسية') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.section_definitions.index') }}">{{ t('dashboard.Section_Definitions', 'تعريفات الأقسام') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Field_Definitions', 'تعريفات الحقول') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Field_Definitions', 'تعريفات الحقول') }}</h2>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
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

    <div class="grid grid-cols-12 gap-x-6 gap-y-6">

        {{-- Info Card --}}
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h5 class="mb-1">{{ $sectionDefinition->label }}</h5>
                            <p class="mb-0 text-sm text-slate-500">
                                <code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700 font-mono">{{ $sectionDefinition->section_key }}</code>
                                <span class="mx-2 text-gray-300">/</span>
                                {{ t('dashboard.Field_Definitions_Desc', 'إدارة حقول هذا القسم') }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $isDynamicDefinition ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $isDynamicDefinition ? t('dashboard.Dynamic', 'ديناميكي') : t('dashboard.Custom_Preset', 'مخصص') }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $sectionDefinition->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $sectionDefinition->is_active ? t('dashboard.Active', 'نشط') : t('dashboard.Inactive', 'معطل') }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $sectionDefinition->is_visible ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $sectionDefinition->is_visible ? t('dashboard.Visible_In_Library', 'ظاهر في المكتبة') : t('dashboard.Hidden_From_Library', 'مخفي من المكتبة') }}
                                </span>
                                @if($primaryTemplateKey)
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-indigo-100 text-indigo-700 font-mono">
                                        {{ $primaryTemplateKey }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-rose-100 text-rose-700">
                                        {{ t('dashboard.No_Template_Selected', 'لا يوجد قالب') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 shrink-0">
                            <a href="{{ route('dashboard.section_definitions.edit', $sectionDefinition) }}"
                               class="btn btn-light btn-sm flex items-center gap-1">
                                <i class="ti ti-arrow-right text-base"></i>
                                {{ t('dashboard.Back_To_Definition', 'العودة للتعريف') }}
                            </a>
                            @can('create', \App\Models\Sections\SectionDefinitionField::class)
                                <a href="{{ route('dashboard.section_definitions.fields.create', $sectionDefinition) }}"
                                   class="btn btn-primary btn-sm flex items-center gap-1">
                                    <i class="ti ti-plus text-base"></i>
                                    {{ t('dashboard.Add_Field', 'إضافة حقل') }}
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fields Content --}}
        <div class="col-span-12">
            @if ($fields->isEmpty())
                <div class="card">
                    <div class="card-body py-16 text-center">
                        <svg class="w-16 h-16 text-gray-300 mb-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M4 6h16M4 10h16M4 14h8m-8 4h6"/>
                        </svg>
                        <p class="text-base font-semibold text-gray-700 mb-1">{{ t('dashboard.No_Fields_Yet', 'لا توجد حقول بعد') }}</p>
                        <p class="text-sm text-gray-400 mb-5">{{ t('dashboard.No_Fields_Desc', 'أضف أول حقل لبدء تعريف مخطط هذا القسم') }}</p>
                        @can('create', \App\Models\Sections\SectionDefinitionField::class)
                            <a href="{{ route('dashboard.section_definitions.fields.create', $sectionDefinition) }}"
                               class="btn btn-primary btn-sm">
                                <i class="ti ti-plus me-1"></i>
                                {{ t('dashboard.Create_First_Field', 'إنشاء أول حقل') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @else
                @can('update', \App\Models\Sections\SectionDefinitionField::class)
                {{--
                    IMPORTANT: Delete forms must NOT be nested inside the reorder form.
                    Nested forms cause _method=DELETE to leak into the reorder submission.
                    Delete buttons use data-* + JS to submit a shared form outside this form.
                --}}
                <form action="{{ route('dashboard.section_definitions.fields.reorder', $sectionDefinition) }}"
                      method="POST" id="field-reorder-form" class="space-y-6">
                    @csrf

                    @foreach ($fieldGroups as $groupLabel => $groupFields)
                        <div class="card table-card">
                            <div class="card-header">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h6 class="mb-1 font-semibold text-gray-800">{{ $groupLabel }}</h6>
                                        <p class="mb-0 text-sm text-slate-500">
                                            {{ t('dashboard.Fields_Reorder_Hint', 'عدّل الأرقام واحفظ لإعادة الترتيب') }}
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600">
                                        {{ $groupFields->count() }} {{ t('dashboard.Fields', 'حقول') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th class="w-24">{{ t('dashboard.Field_Sort', 'الترتيب') }}</th>
                                                <th>{{ t('dashboard.Field_Label', 'الاسم') }}</th>
                                                <th>{{ t('dashboard.Field_Key', 'المفتاح') }}</th>
                                                <th>{{ t('dashboard.Field_Type', 'النوع') }}</th>
                                                <th>{{ t('dashboard.Field_Scope', 'النطاق') }}</th>
                                                <th>{{ t('dashboard.Field_Required', 'إلزامي') }}</th>
                                                <th>{{ t('dashboard.Actions', 'إجراءات') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($groupFields as $field)
                                                <tr>
                                                    <td class="w-24">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            name="sort_orders[{{ $field->id }}]"
                                                            class="form-control form-control-sm"
                                                            value="{{ $field->sort_order }}"
                                                            style="width:70px;"
                                                        >
                                                    </td>
                                                    <td>
                                                        <div class="font-medium text-gray-800">{{ $field->label }}</div>
                                                        @if ($field->validation_rules)
                                                            <div class="mt-0.5 text-xs text-gray-400">
                                                                {{ t('dashboard.Validation', 'التحقق') }}: {{ implode(', ', $field->validation_rules) }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <code class="bg-gray-100 rounded px-2 py-0.5 text-xs text-gray-700 font-mono">{{ $field->field_key }}</code>
                                                    </td>
                                                    <td class="text-sm text-gray-600">{{ \Illuminate\Support\Str::headline($field->field_type) }}</td>
                                                    <td>
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
                                                            {{ $field->isTranslatable() ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500' }}">
                                                            {{ $field->isTranslatable() ? t('dashboard.Translatable', 'قابل للترجمة') : t('dashboard.Shared', 'مشترك') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
                                                            {{ $field->is_required ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                                                            {{ $field->is_required ? t('dashboard.Required', 'إلزامي') : t('dashboard.Optional', 'اختياري') }}
                                                        </span>
                                                    </td>
                                                    <td class="whitespace-nowrap">
                                                        <div class="flex items-center gap-0.5">
                                                            @can('update', $field)
                                                                <a href="{{ route('dashboard.section_definitions.fields.edit', [$sectionDefinition, $field]) }}"
                                                                   class="w-8 h-8 rounded-xl inline-flex items-center justify-center text-gray-500 hover:text-primary hover:bg-primary/10 transition"
                                                                   title="{{ t('dashboard.Edit', 'تعديل') }}">
                                                                    <i class="ti ti-edit text-lg leading-none"></i>
                                                                </a>
                                                            @endcan
                                                            @can('delete', $field)
                                                                {{-- type="button" prevents submitting the reorder form --}}
                                                                <button type="button"
                                                                        class="field-delete-btn w-8 h-8 rounded-xl inline-flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 transition"
                                                                        data-url="{{ route('dashboard.section_definitions.fields.destroy', [$sectionDefinition, $field]) }}"
                                                                        data-name="{{ $field->label }}"
                                                                        title="{{ t('dashboard.Delete', 'حذف') }}">
                                                                    <i class="ti ti-trash text-lg leading-none"></i>
                                                                </button>
                                                            @endcan
                                                        </div>
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
                            <i class="ti ti-device-floppy me-1"></i>
                            {{ t('dashboard.Save_Field_Order', 'حفظ الترتيب') }}
                        </button>
                    </div>
                </form>
                @endcan
            @endif
        </div>
    </div>

    {{--
        Shared delete form placed OUTSIDE the reorder form.
        JS sets the action URL before submitting.
    --}}
    <form action="" method="POST" id="field-delete-form" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    @push('scripts')
    <script>
    (function () {
        var deleteForm = document.getElementById('field-delete-form');
        document.querySelectorAll('.field-delete-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var name = btn.dataset.name || '';
                if (!window.confirm('{{ t('dashboard.Confirm_Delete_Field', 'حذف هذا الحقل نهائياً؟') }}' + (name ? '\n' + name : ''))) return;
                deleteForm.action = btn.dataset.url;
                deleteForm.submit();
            });
        });
    })();
    </script>
    @endpush

</x-dashboard-layout>
