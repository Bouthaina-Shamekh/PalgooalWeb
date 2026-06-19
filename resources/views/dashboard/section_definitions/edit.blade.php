<x-dashboard-layout>

    {{-- ═══ Page Header ═══ --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'الرئيسية') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.section_definitions.index') }}">{{ t('dashboard.Section_Definitions', 'تعريفات الأقسام') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ $sectionDefinition->label }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Edit_Section_Definition', 'تعديل تعريف القسم') }}</h2>
            </div>
        </div>
    </div>

    {{-- ═══ Flash ═══ --}}
    @if (session('ok'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-circle-check me-2"></i>{{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ═══ Info Ribbon ═══ --}}
    @php
        $bladeFields  = $sectionDefinition->fields()
            ->orderBy('sort_order')->orderBy('id')
            ->get(['field_key', 'field_type', 'field_scope', 'is_required', 'validation_rules', 'default_value', 'schema']);
        $fieldsCount  = $bladeFields->count();
    @endphp
    <div class="card mb-4" style="border-right: 4px solid #4f46e5;">
        <div class="card-body py-3">
            <div class="flex flex-wrap items-center gap-5">
                <div>
                    <div class="text-xs text-slate-400 mb-0.5">{{ t('dashboard.Name', 'الاسم') }}</div>
                    <div class="font-semibold text-slate-900">{{ $sectionDefinition->label }}</div>
                </div>
                <div style="width:1px;height:32px;background:#e2e8f0;flex-shrink:0;"></div>
                <div>
                    <div class="text-xs text-slate-400 mb-0.5">{{ t('dashboard.Field_Key', 'المفتاح') }}</div>
                    <code class="text-sm font-mono bg-indigo-50 text-indigo-700 rounded px-2 py-0.5" dir="ltr">{{ $sectionDefinition->section_key }}</code>
                </div>
                <div style="width:1px;height:32px;background:#e2e8f0;flex-shrink:0;"></div>
                <div>
                    <div class="text-xs text-slate-400 mb-0.5">{{ t('dashboard.Blade_Template', 'قالب Blade') }}</div>
                    @if ($bladeFileStatus === 'published')
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-700">
                            <i class="ti ti-check me-1"></i>{{ t('dashboard.Blade_File_Exists', 'ملف موجود') }}
                        </span>
                    @elseif ($bladeFileStatus === 'external')
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700">
                            <i class="ti ti-alert-triangle me-1"></i>{{ t('dashboard.Blade_File_External', 'كُتب خارجياً') }}
                        </span>
                    @elseif ($bladeFileStatus === 'invalid')
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-500">
                            <i class="ti ti-ban me-1"></i>{{ t('dashboard.Blade_Invalid_Key', 'مفتاح غير صالح') }}
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-red-100 text-red-600">
                            <i class="ti ti-file-x me-1"></i>{{ t('dashboard.Blade_File_Missing', 'لم يُكتب بعد') }}
                        </span>
                    @endif
                </div>
                <div style="width:1px;height:32px;background:#e2e8f0;flex-shrink:0;"></div>
                <div>
                    <div class="text-xs text-slate-400 mb-0.5">{{ t('dashboard.Fields', 'الحقول') }}</div>
                    <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}"
                       class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">
                        <i class="ti ti-layout-list text-base"></i>
                        {{ $fieldsCount }} {{ t('dashboard.Fields', 'حقل') }}
                        <i class="ti ti-arrow-left text-xs"></i>
                    </a>
                </div>
                <div class="ms-auto">
                    <div class="text-xs text-slate-400 mb-0.5">{{ t('dashboard.Last_Updated', 'آخر تحديث') }}</div>
                    <div class="text-sm text-slate-500">{{ $sectionDefinition->updated_at->diffForHumans() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ JS Tabs ═══ --}}
    @php $sdTabDefault = session('_blade_tab') === 'blade' ? 'blade' : 'info'; @endphp
    <div id="sd-tabs-wrapper">

        <div class="card mb-4">
            <div class="card-body py-0 px-2">
                <nav class="flex gap-0">
                    <button type="button" id="sd-tab-btn-info"
                            onclick="sdSetTab('info')"
                            class="sd-tab-btn flex items-center gap-2 px-5 py-3.5 text-sm transition-all rounded-t-lg border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50">
                        <i class="ti ti-settings text-base"></i>
                        {{ t('dashboard.Definition_Information', 'معلومات التعريف') }}
                    </button>
                    <button type="button" id="sd-tab-btn-blade"
                            onclick="sdSetTab('blade')"
                            class="sd-tab-btn flex items-center gap-2 px-5 py-3.5 text-sm transition-all rounded-t-lg border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50">
                        <i class="ti ti-code text-base"></i>
                        {{ t('dashboard.Blade_Template', 'قالب Blade') }}
                        @if ($bladeFileStatus === 'published')
                            <span class="w-4 h-4 rounded-full inline-flex items-center justify-center bg-green-500 text-white" style="font-size:9px;">&#10003;</span>
                        @elseif ($bladeFileStatus === 'missing')
                            <span class="w-4 h-4 rounded-full inline-flex items-center justify-center bg-red-500 text-white" style="font-size:9px;">!</span>
                        @elseif ($bladeFileStatus === 'external')
                            <span class="w-4 h-4 rounded-full inline-flex items-center justify-center bg-amber-500 text-white" style="font-size:9px;">&#9888;</span>
                        @endif
                    </button>
                </nav>
            </div>
        </div>

        {{-- TAB 1 — Definition Information --}}
        <div id="sd-pane-info" class="sd-tab-pane">
            <form action="{{ route('dashboard.section_definitions.update', $sectionDefinition) }}"
                  method="POST" id="section-def-form">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-12 gap-6">
                    <div class="col-span-12 xl:col-span-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-1">{{ t('dashboard.Definition_Information', 'معلومات التعريف') }}</h5>
                                <p class="mb-0 text-sm text-slate-500">{{ t('dashboard.Def_Workflow_Desc', 'أدخل Category و Template Key مستقرين، احفظ، ثم انتقل لتعريفات الحقول.') }}</p>
                            </div>
                            <div class="card-body">
                                @include('dashboard.section_definitions.form')
                            </div>
                        </div>
                    </div>
                    <div class="col-span-12 xl:col-span-4">
                        <div class="card sticky top-6">
                            <div class="card-header">
                                <h5 class="mb-0">{{ t('dashboard.Actions', 'الإجراءات') }}</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-sm text-slate-500 mb-3">{{ t('dashboard.Def_Sidebar_Hint', 'بعد الحفظ يمكنك إدارة الحقول لضبط المخطط الديناميكي للقسم.') }}</p>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
                                    <i class="ti ti-keyboard me-1"></i>{{ t('dashboard.Shortcut_Save', 'اضغط Ctrl+S للحفظ السريع') }}
                                </div>
                            </div>
                            <div class="card-footer d-grid gap-2">
                                <button type="submit" form="section-def-form" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i>{{ t('dashboard.Update_Definition', 'حفظ التعديلات') }}
                                </button>
                                <button type="submit" name="after_save" value="fields" form="section-def-form" class="btn btn-light">
                                    <i class="ti ti-layout-list me-1"></i>{{ t('dashboard.Update_And_Manage_Fields', 'حفظ وإدارة الحقول') }}
                                </button>
                                <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}" class="btn btn-light">
                                    <i class="ti ti-list-details me-1"></i>{{ t('dashboard.Manage_Fields', 'إدارة الحقول') }}
                                    @if ($fieldsCount > 0)
                                        <span class="badge bg-primary ms-1">{{ $fieldsCount }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('dashboard.section_definitions.index') }}" class="btn btn-light">
                                    <i class="ti ti-arrow-right me-1"></i>{{ t('dashboard.Cancel', 'رجوع') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- TAB 2 — Blade Editor --}}
        <div id="sd-pane-blade" class="sd-tab-pane">
            <div class="grid grid-cols-12 gap-6">
                <div class="col-span-12 xl:col-span-8">
                    {{-- ── File Status Card (Phase 3 + Phase 4) ── --}}
                    <div class="card mb-4" id="blade-file-status-card">
                        <div class="card-body py-3">

                            {{-- Row 1: File Status Badge + Message --}}
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                @if ($fileStatus['status'] === 'published')
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold bg-green-100 text-green-700">
                                        <i class="ti ti-circle-check-filled"></i>
                                        {{ t('dashboard.File_Status_Published', 'Published') }}
                                    </span>
                                    <span class="text-sm text-slate-500">{{ t('dashboard.File_Published_Msg', 'تم نشر الملف بواسطة النظام') }}</span>
                                @elseif ($fileStatus['status'] === 'external')
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold bg-orange-100 text-orange-700">
                                        <i class="ti ti-alert-triangle-filled"></i>
                                        {{ t('dashboard.File_Status_External', 'External') }}
                                    </span>
                                    <span class="text-sm text-slate-500">{{ t('dashboard.File_External_Msg', 'الملف موجود لكنه كُتب خارج النظام — blade_written_at غير مضبوط') }}</span>
                                @elseif ($fileStatus['status'] === 'invalid')
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold bg-red-100 text-red-600">
                                        <i class="ti ti-ban"></i>
                                        {{ t('dashboard.File_Status_Invalid', 'Invalid') }}
                                    </span>
                                    <span class="text-sm text-slate-500">{{ t('dashboard.File_Invalid_Msg', 'المفتاح أو الفئة غير صالح — لا يمكن تحديد المسار') }}</span>
                                @else {{-- missing --}}
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold bg-gray-100 text-gray-500">
                                        <i class="ti ti-circle-dashed"></i>
                                        {{ t('dashboard.File_Status_Missing', 'Missing') }}
                                    </span>
                                    <span class="text-sm text-slate-500">{{ t('dashboard.File_Missing_Msg', 'لم يتم إنشاء الملف بعد — اضغط Generate & Write للنشر') }}</span>
                                @endif
                            </div>

                            {{-- Row 2: Sync Status (Phase 4 — only for files that exist on disk) --}}
                            @if (in_array($fileStatus['status'], ['published', 'external']))
                                @php $sync = $fileStatus['sync_status']; @endphp
                                <div class="flex flex-wrap items-center gap-3 pt-2 mb-2" style="border-top:1px solid #f8fafc;">
                                    <span class="text-xs text-slate-400 font-medium">{{ t('dashboard.Sync_Status', 'Sync:') }}</span>

                                    @if ($sync === 'in_sync')
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-50 text-green-600 border border-green-200">
                                            <i class="ti ti-circle-check"></i>
                                            {{ t('dashboard.Sync_In_Sync', 'In Sync') }}
                                        </span>
                                        <span class="text-xs text-slate-400">{{ t('dashboard.Sync_In_Sync_Msg', 'Monaco و disk متطابقان') }}</span>
                                    @elseif ($sync === 'out_of_sync')
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200">
                                            <i class="ti ti-alert-circle-filled"></i>
                                            {{ t('dashboard.Sync_Out_Of_Sync', 'Out Of Sync') }}
                                        </span>
                                        <span class="text-xs text-slate-500">{{ t('dashboard.Sync_Out_Of_Sync_Msg', 'Monaco يحتوي تغييرات لم تُنشر بعد') }}</span>
                                        {{-- Phase 5: Compare Versions — enabled --}}
                                        <button type="button" id="compare-versions-btn"
                                                class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-medium border border-indigo-300 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition cursor-pointer"
                                                title="{{ t('dashboard.Compare_Versions', 'Compare Versions') }}">
                                            <i class="ti ti-git-diff"></i>
                                            {{ t('dashboard.Compare_Versions', 'Compare Versions') }}
                                        </button>
                                    @elseif ($sync === 'external_change')
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200">
                                            <i class="ti ti-refresh-alert"></i>
                                            {{ t('dashboard.Sync_External_Change', 'External Change') }}
                                        </span>
                                        <span class="text-xs text-slate-500">{{ t('dashboard.Sync_External_Change_Msg', 'تم تعديل الملف على disk منذ آخر Publish') }}</span>
                                        {{-- Phase 5: Compare Versions — enabled for external_change too --}}
                                        <button type="button" id="compare-versions-btn"
                                                class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-medium border border-orange-300 text-orange-700 bg-orange-50 hover:bg-orange-100 transition cursor-pointer"
                                                title="{{ t('dashboard.Compare_Versions', 'Compare Versions') }}">
                                            <i class="ti ti-git-diff"></i>
                                            {{ t('dashboard.Compare_Versions', 'Compare Versions') }}
                                        </button>
                                    @else {{-- unknown --}}
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-50 text-gray-400 border border-gray-200">
                                            <i class="ti ti-circle-dashed"></i>
                                            {{ t('dashboard.Sync_Unknown', 'Unknown') }}
                                        </span>
                                        <span class="text-xs text-slate-400">{{ t('dashboard.Sync_Unknown_Msg', 'لا يوجد بصمة محفوظة — اضغط Write لتفعيل التتبع') }}</span>
                                    @endif
                                </div>
                            @endif

                            {{-- Row 3: View Name | Disk Path | Last Published | Copy --}}
                            <div class="flex flex-wrap items-center gap-4 pt-2" style="border-top:1px solid #f1f5f9;">

                                {{-- View Name --}}
                                @if ($fileStatus['view_name'])
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <i class="ti ti-eye text-slate-400 flex-shrink-0"></i>
                                        <div>
                                            <div class="text-xs text-slate-400 mb-0.5">{{ t('dashboard.Blade_View_Name', 'View:') }}</div>
                                            <code class="text-xs font-mono text-indigo-600" dir="ltr">{{ $fileStatus['view_name'] }}</code>
                                        </div>
                                    </div>
                                    <div style="width:1px;height:28px;background:#e2e8f0;flex-shrink:0;"></div>
                                @endif

                                {{-- Disk Path --}}
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <i class="ti ti-file-code text-slate-400 text-xl flex-shrink-0"></i>
                                    <div class="min-w-0">
                                        <div class="text-xs text-slate-400 mb-0.5">{{ t('dashboard.Blade_Expected_Path', 'المسار على الـ disk:') }}</div>
                                        <code class="text-sm font-mono text-slate-700 block truncate" dir="ltr" title="{{ $bladeExpectedPath }}">{{ $bladeExpectedPath }}</code>
                                    </div>
                                </div>

                                {{-- Last Published --}}
                                @if ($sectionDefinition->blade_written_at)
                                    <div style="width:1px;height:28px;background:#e2e8f0;flex-shrink:0;"></div>
                                    <div class="flex-shrink-0">
                                        <div class="text-xs text-slate-400 mb-0.5">{{ t('dashboard.Last_Published', 'آخر نشر:') }}</div>
                                        <div class="text-xs text-slate-600 font-medium">
                                            <i class="ti ti-clock me-1 text-slate-400"></i>{{ $sectionDefinition->blade_written_at->diffForHumans() }}
                                        </div>
                                    </div>
                                @endif

                                {{-- Copy Path --}}
                                <button type="button" id="copy-path-btn"
                                        class="flex-shrink-0 w-8 h-8 rounded-lg inline-flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                                        data-path="{{ $bladeExpectedPath }}"
                                        title="{{ t('dashboard.Copy', 'نسخ المسار') }}">
                                    <i class="ti ti-copy text-base"></i>
                                </button>
                            </div>

                        </div>
                    </div>

                    <div class="card" id="blade-editor-card">
                        <div class="card-header d-flex align-items-center gap-3 flex-wrap" style="background:#f8fafc;">
                            <div class="flex items-center gap-2">
                                <i class="ti ti-code text-indigo-500"></i>
                                <h5 class="mb-0">{{ t('dashboard.Blade_Source_Code', 'كود Blade') }}</h5>
                            </div>
                            <div class="flex items-center gap-2 ms-auto flex-wrap">
                                <button type="button" id="blade-scaffold-btn" class="btn btn-sm btn-light"
                                        title="{{ t('dashboard.Blade_Scaffold_Hint', 'إنشاء stub ذكي من الحقول') }}">
                                    <i class="ti ti-wand me-1 text-violet-500"></i>
                                    <span id="scaffold-btn-label">{{ t('dashboard.Blade_Scaffold', 'Scaffold من الحقول') }}</span>
                                    @if ($fieldsCount > 0)
                                        <span id="scaffold-missing-badge" class="badge ms-1" style="background:#ede9fe;color:#6d28d9;display:none;"></span>
                                    @endif
                                </button>
                                <button type="button" id="copy-code-btn" class="btn btn-sm btn-light">
                                    <i class="ti ti-copy me-1"></i>{{ t('dashboard.Copy', 'نسخ') }}
                                </button>
                                <button type="button" id="clear-code-btn" class="btn btn-sm btn-light text-red-500">
                                    <i class="ti ti-trash me-1"></i>{{ t('dashboard.Clear', 'مسح') }}
                                </button>
                                <div class="d-flex align-items-center gap-1 border border-slate-200 rounded px-1" style="background:#f1f5f9;">
                                    <button type="button" id="zoom-out-btn" class="btn btn-sm p-0" style="width:22px;height:22px;line-height:1;background:transparent;border:none;" title="تصغير الخط (Ctrl+-)">
                                        <i class="ti ti-minus" style="font-size:11px;"></i>
                                    </button>
                                    <span id="font-size-display" class="font-mono text-slate-500" style="font-size:11px;min-width:26px;text-align:center;">13px</span>
                                    <button type="button" id="zoom-in-btn" class="btn btn-sm p-0" style="width:22px;height:22px;line-height:1;background:transparent;border:none;" title="تكبير الخط (Ctrl++)">
                                        <i class="ti ti-plus" style="font-size:11px;"></i>
                                    </button>
                                </div>
                                <button type="button" id="fullscreen-btn" class="btn btn-sm btn-light" title="{{ t('dashboard.Fullscreen', 'تكبير') }}">
                                    <i class="ti ti-maximize" id="fullscreen-icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0" style="background:#1e1e2e;border-radius:0;">
                            <div id="monaco-editor-container" dir="ltr" style="height:580px;"></div>
                        </div>
                        <div class="card-footer d-flex align-items-center gap-3" style="background:#f8fafc;">
                            <span class="text-sm text-slate-400 me-auto font-mono" id="blade-editor-stats"></span>
                            <button type="button" class="btn btn-primary" id="blade-write-btn"
                                    @if ($bladeFileStatus === 'published') data-confirm="{{ t('dashboard.Blade_Confirm_Overwrite', 'الملف موجود على الـ disk. هل تريد استبداله؟') }}" @endif>
                                <i class="ti ti-device-floppy me-1"></i>{{ t('dashboard.Blade_Write_File', 'كتابة الملف على الـ disk') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 xl:col-span-4">
                    <div class="card sticky top-6">
                        <div class="card-header">
                            <h5 class="mb-0">{{ t('dashboard.Actions', 'الإجراءات') }}</h5>
                        </div>
                        <div class="card-footer d-grid gap-2">
                            <button type="button" class="btn btn-primary" id="blade-write-btn-sidebar"
                                    @if ($bladeFileStatus === 'published') data-confirm="{{ t('dashboard.Blade_Confirm_Overwrite', 'الملف موجود. هل تريد الاستبدال؟') }}" @endif>
                                <i class="ti ti-device-floppy me-1"></i>{{ t('dashboard.Blade_Write_File', 'كتابة الملف على الـ disk') }}
                            </button>
                            <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}" class="btn btn-light">
                                <i class="ti ti-layout-list me-1"></i>{{ t('dashboard.Manage_Fields', 'إدارة الحقول') }}
                                @if ($fieldsCount > 0)
                                    <span class="badge bg-primary ms-1">{{ $fieldsCount }}</span>
                                @endif
                            </a>
                            <a href="{{ route('dashboard.section_definitions.index') }}" class="btn btn-light">
                                <i class="ti ti-arrow-right me-1"></i>{{ t('dashboard.Cancel', 'رجوع') }}
                            </a>
                        </div>
                        {{-- QW5: Blade Safety Tips — runtime contract reminder --}}
                        <div class="card-body border-top py-3">
                            <h6 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
                                <i class="ti ti-shield-check me-1 text-green-500"></i>{{ t('dashboard.Blade_Safety_Tips', 'نمط الاستخدام الصحيح') }}
                            </h6>
                            <div class="rounded border border-green-200 bg-green-50 px-2.5 py-2 mb-2">
                                <code class="font-mono font-semibold text-green-700 block mb-0.5" dir="ltr" style="font-size:11px;">$data['key'] ?? ''</code>
                                <div class="text-green-600" style="font-size:10px;">{{ t('dashboard.Blade_Safety_Contract', 'كل الحقول في $data — shared + translatable مدمجان') }}</div>
                            </div>
                            <div class="rounded border border-red-100 bg-red-50 px-2.5 py-1.5 flex flex-wrap items-center gap-x-1.5 gap-y-1" style="font-size:10px;">
                                <span class="text-red-500 font-semibold flex-shrink-0"><i class="ti ti-ban me-0.5"></i>{{ t('dashboard.Blade_Safety_Forbidden', 'لا تستخدم:') }}</span>
                                <code class="font-mono text-red-600 bg-red-100 rounded px-1" dir="ltr">$fields</code>
                                <code class="font-mono text-red-600 bg-red-100 rounded px-1" dir="ltr">$sharedData</code>
                                <code class="font-mono text-red-600 bg-red-100 rounded px-1" dir="ltr">$translatableData</code>
                            </div>
                        </div>

                        <div class="card-body border-top" style="padding-bottom:0;">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-0">
                                    <i class="ti ti-list-details me-1"></i>{{ t('dashboard.Fields', 'الحقول') }}
                                    <span class="font-mono font-normal normal-case text-slate-400 ms-1" style="font-size:9px;">($data[…])</span>
                                </h6>
                                <div class="flex items-center gap-2 text-xs text-slate-400">
                                    <span class="flex items-center gap-1">
                                        <span style="width:7px;height:7px;border-radius:50%;background:#16a34a;display:inline-block;"></span>مستخدم
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span style="width:7px;height:7px;border-radius:50%;background:#d97706;display:inline-block;"></span>ناقص
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if ($bladeFields->isEmpty())
                            <div class="card-body border-top">
                                <div class="rounded-lg border border-dashed border-slate-200 px-4 py-6 text-center">
                                    <i class="ti ti-layout-list text-slate-300 text-2xl mb-2 block"></i>
                                    <p class="text-xs text-slate-400 mb-2">{{ t('dashboard.No_Fields_Yet', 'لا حقول مضافة بعد') }}</p>
                                    <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}" class="text-xs text-primary hover:underline">
                                        {{ t('dashboard.Add_Field', 'أضف حقلاً') }}
                                    </a>
                                </div>
                            </div>
                        @else
                            {{-- QW5: Field Search — client-side filter, no Monaco touch --}}
                            @if ($fieldsCount > 3)
                            <div class="px-3 pb-2 pt-1 border-top" style="background:#f8fafc;">
                                <div class="relative">
                                    <input type="text" id="field-search-input"
                                           class="form-control form-control-sm pe-2"
                                           placeholder="{{ t('dashboard.Search_Fields', 'بحث في الحقول...') }}"
                                           autocomplete="off"
                                           style="font-size:11px;padding-inline-start:1.75rem;border-radius:6px;">
                                    <i class="ti ti-search" style="position:absolute;inset-inline-start:0.5rem;top:50%;transform:translateY(-50%);font-size:11px;color:#94a3b8;pointer-events:none;"></i>
                                </div>
                            </div>
                            @endif
                            <div class="card-body border-top p-0">
                                <div class="flex flex-col" id="fields-reference-list" style="max-height:380px;overflow-y:auto;"
                                     aria-label="{{ t('dashboard.Fields', 'الحقول') }}"
                                >
                                    @foreach ($bladeFields as $f)
                                        @php
                                            // QW2 — resolve validation rules defensively (cast may be array or string)
                                            $fValRules = $f->validation_rules ?? null;
                                            if (is_array($fValRules) && count($fValRules)) {
                                                $fValDisplay = implode(' | ', $fValRules);
                                                $fIsRequired = in_array('required', $fValRules);
                                            } elseif (is_string($fValRules) && strlen($fValRules)) {
                                                $fValDisplay = $fValRules;
                                                $fIsRequired = str_contains($fValRules, 'required');
                                            } else {
                                                $fValDisplay = null;
                                                $fIsRequired = false;
                                            }
                                            // Also respect the dedicated is_required column
                                            $fIsRequired = $fIsRequired || (bool) ($f->is_required ?? false);

                                            // QW2 — resolve default value (cast may be array/null/empty)
                                            $fDefault = $f->default_value ?? null;
                                            if ($fDefault !== null && $fDefault !== [] && $fDefault !== '') {
                                                $fDefaultStr = is_array($fDefault)
                                                    ? json_encode($fDefault, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                                    : (string) $fDefault;
                                                $fDefaultStr = mb_strlen($fDefaultStr) > 45
                                                    ? mb_substr($fDefaultStr, 0, 45) . '…'
                                                    : $fDefaultStr;
                                            } else {
                                                $fDefaultStr = null;
                                            }
                                        @endphp
                                        <div style="border-bottom: 0.5px solid #f1f5f9;">
                                            {{-- Main row (field key + type + scope + required + insert) --}}
                                            <div class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition">
                                                <span id="field-dot-{{ $f->field_key }}"
                                                      style="width:7px;height:7px;border-radius:50%;background:#d97706;flex-shrink:0;transition:background .2s;"></span>
                                                @if ($f->field_scope === 'translatable')
                                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded text-xs font-bold bg-blue-100 text-blue-600 flex-shrink-0"
                                                          title="{{ t('dashboard.Translatable', 'قابل للترجمة') }}">ت</span>
                                                @else
                                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded text-xs font-bold bg-gray-100 text-gray-500 flex-shrink-0"
                                                          title="{{ t('dashboard.Shared', 'مشترك') }}">م</span>
                                                @endif
                                                <code class="text-xs font-mono text-indigo-700 flex-1 min-w-0 truncate" dir="ltr">{{ $f->field_key }}</code>
                                                <span class="text-xs text-slate-400 flex-shrink-0">{{ $f->field_type }}</span>
                                                @if ($fIsRequired)
                                                    <span class="flex-shrink-0 font-semibold"
                                                          style="background:#fee2e2;color:#b91c1c;border-radius:4px;padding:1px 5px;font-size:10px;line-height:1.5;">
                                                        {{ t('dashboard.Required', 'مطلوب') }}
                                                    </span>
                                                @endif
                                                <button type="button"
                                                        id="field-insert-{{ $f->field_key }}"
                                                        class="field-insert-btn flex-shrink-0 text-xs px-2 py-0.5 rounded-md border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition"
                                                        data-key="{{ $f->field_key }}"
                                                        data-type="{{ $f->field_type }}"
                                                        data-scope="{{ $f->field_scope }}"
                                                        style="font-size:11px;line-height:1.4;">
                                                    <i class="ti ti-plus" style="font-size:10px;"></i>
                                                </button>
                                            </div>
                                            {{-- QW2: Details sub-row — validation rules + default value --}}
                                            @if ($fValDisplay || $fDefaultStr)
                                                <div class="px-3 pb-2">
                                                    @if ($fValDisplay)
                                                        <div class="flex items-baseline gap-1 mb-0.5">
                                                            <span class="text-slate-400 flex-shrink-0" style="font-size:10px;">{{ t('dashboard.Validation', 'تحقق') }}:</span>
                                                            <code class="font-mono text-slate-500 truncate block" dir="ltr" style="font-size:10px;">{{ $fValDisplay }}</code>
                                                        </div>
                                                    @endif
                                                    @if ($fDefaultStr)
                                                        <div class="flex items-baseline gap-1">
                                                            <span class="text-slate-400 flex-shrink-0" style="font-size:10px;">{{ t('dashboard.Default_Value', 'الافتراضي') }}:</span>
                                                            <code class="font-mono text-slate-500 truncate block" dir="ltr" style="font-size:10px;">{{ $fDefaultStr }}</code>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                            {{-- QW3: Repeater info — editor available via Pages → Sections (Phase 5C) --}}
                                            @if ($f->isRepeater())
                                                <div class="mx-3 mb-2 flex items-start gap-1.5 rounded border border-blue-200 bg-blue-50 px-2 py-1.5">
                                                    <i class="ti ti-info-circle text-blue-500 flex-shrink-0" style="font-size:11px;margin-top:1px;"></i>
                                                    <span class="text-blue-700" style="font-size:10px;line-height:1.4;">
                                                        {{ t('dashboard.Repeater_Editor_Available', 'محرر حقول Repeater متاح عند تحرير محتوى الصفحة (الصفحات ← الأقسام).') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="card-body" style="padding-top:.75rem;">
                                <p class="text-xs text-slate-400 mb-0">
                                    <i class="ti ti-info-circle me-1"></i>
                                    اضغط <strong style="font-weight:500;">+</strong> لإدراج كود الحقل عند موضع المؤشر
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- end JS tabs wrapper --}}

    {{-- ════════════════════════════════════════════════════════════════
         Blade Scaffold Preview Modal (BladeGenerator — Phase 6)
         ════════════════════════════════════════════════════════════════ --}}
    <div id="blade-scaffold-modal" dir="rtl"
         style="display:none;position:fixed;inset:0;z-index:99990;background:rgba(15,23,42,.6);align-items:center;justify-content:center;padding:16px;">
        <div style="background:#fff;border-radius:16px;width:100%;max-width:860px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.25);overflow:hidden;">

            {{-- Header --}}
            <div style="display:flex;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid #e2e8f0;flex-shrink:0;">
                <div style="width:36px;height:36px;border-radius:10px;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="ti ti-wand" style="font-size:18px;color:#7c3aed;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:15px;font-weight:700;color:#1e293b;">{{ t('dashboard.Blade_Generator_Title', 'Auto Blade Generator') }}</div>
                    <div style="font-size:12px;color:#64748b;">{{ t('dashboard.Blade_Generator_Subtitle', 'Scaffold محسوب من تعريفات الحقول + Component Library') }}</div>
                </div>
                <button id="bsm-close-x" type="button"
                        style="width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;"
                        title="{{ t('dashboard.Close', 'إغلاق') }}">
                    <i class="ti ti-x" style="font-size:14px;"></i>
                </button>
            </div>

            {{-- Loader --}}
            <div id="bsm-loader" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:48px;min-height:200px;">
                <div style="width:36px;height:36px;border-radius:50%;border:3px solid #ede9fe;border-top-color:#7c3aed;animation:bsm-spin .8s linear infinite;"></div>
                <div style="font-size:13px;color:#64748b;">{{ t('dashboard.Blade_Generator_Loading', 'جاري توليد الـ scaffold…') }}</div>
            </div>

            {{-- Body --}}
            <div id="bsm-body" style="display:none;flex:1;min-height:0;flex-direction:column;overflow:hidden;">
                {{-- Stats bar --}}
                <div id="bsm-stats" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:12px;color:#475569;flex-shrink:0;">
                    {{-- filled by JS --}}
                </div>

                {{-- Code block --}}
                <div style="flex:1;min-height:0;overflow:auto;background:#1e1e2e;">
                    <pre id="bsm-code" dir="ltr"
                         style="margin:0;padding:20px 24px;font-family:'Fira Code','Cascadia Code',Consolas,monospace;font-size:13px;line-height:1.7;color:#cdd6f4;white-space:pre;tab-size:4;overflow:visible;"></pre>
                </div>
            </div>

            {{-- Footer --}}
            <div style="display:flex;align-items:center;gap:8px;padding:14px 20px;border-top:1px solid #e2e8f0;flex-shrink:0;background:#f8fafc;flex-wrap:wrap;">
                <button id="bsm-insert" type="button" class="btn btn-primary btn-sm">
                    <i class="ti ti-arrow-bar-to-down me-1"></i>{{ t('dashboard.Blade_Generator_Insert', 'إدراج في المحرر') }}
                </button>
                <button id="bsm-generate-write" type="button" class="btn btn-success btn-sm">
                    <i class="ti ti-file-download me-1"></i>{{ t('dashboard.Generate_Write_Blade', 'توليد وكتابة مباشرة') }}
                </button>
                <button id="bsm-copy" type="button" class="btn btn-light btn-sm">
                    <i class="ti ti-copy me-1"></i>{{ t('dashboard.Blade_Generator_Copy', 'نسخ الكود') }}
                </button>
                <span style="flex:1;"></span>
                <button id="bsm-close" type="button" class="btn btn-light btn-sm">
                    {{ t('dashboard.Close', 'إغلاق') }}
                </button>
            </div>

        </div>
    </div>

    <style>
    @keyframes bsm-spin { to { transform: rotate(360deg); } }
    .bsm-stat { display:inline-flex; align-items:center; gap:4px; }
    .bsm-stat i { font-size:13px; color:#7c3aed; }
    .bsm-chips { display:inline-flex; gap:4px; flex-wrap:wrap; }
    .bsm-chip  { background:#ede9fe; color:#6d28d9; border-radius:20px; padding:1px 9px; font-size:11px; font-weight:600; }
    #bsm-body  { display:flex; }
    </style>

    {{-- ════════════════════════════════════════════════════════════════
         Compare Versions Modal (Monaco Diff Editor — Phase 5)
         ════════════════════════════════════════════════════════════════ --}}
    <div id="compare-modal" dir="rtl"
         style="display:none;position:fixed;inset:0;z-index:99995;background:rgba(15,23,42,.65);align-items:center;justify-content:center;padding:16px;">
        <div style="background:#fff;border-radius:16px;width:100%;max-width:1100px;max-height:92vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.3);overflow:hidden;">

            {{-- Header --}}
            <div style="display:flex;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid #e2e8f0;flex-shrink:0;">
                <div style="width:36px;height:36px;border-radius:10px;background:#fef3c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="ti ti-git-diff" style="font-size:18px;color:#d97706;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:15px;font-weight:700;color:#1e293b;">{{ t('dashboard.Compare_Modal_Title', 'Compare Versions') }}</div>
                    <div id="cvm-subtitle" style="font-size:12px;color:#64748b;">{{ t('dashboard.Compare_Modal_Subtitle', 'مقارنة Draft (Monaco) مع الملف على disk') }}</div>
                </div>
                <button id="cvm-close-x" type="button"
                        style="width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;"
                        title="{{ t('dashboard.Close', 'إغلاق') }}">
                    <i class="ti ti-x" style="font-size:14px;"></i>
                </button>
            </div>

            {{-- Column labels --}}
            <div id="cvm-labels" style="display:none;flex-shrink:0;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">
                    <div style="padding:8px 20px;font-size:12px;font-weight:600;color:#4f46e5;border-right:1px solid #e2e8f0;display:flex;align-items:center;gap:6px;">
                        <i class="ti ti-edit" style="font-size:13px;"></i>
                        {{ t('dashboard.Compare_Draft_Label', 'Draft Version') }}
                        <span style="font-size:11px;font-weight:400;color:#94a3b8;margin-inline-start:4px;">{{ t('dashboard.Compare_Draft_Hint', '(blade_source — Monaco)') }}</span>
                    </div>
                    <div style="padding:8px 20px;font-size:12px;font-weight:600;color:#d97706;display:flex;align-items:center;gap:6px;">
                        <i class="ti ti-file-code" style="font-size:13px;"></i>
                        {{ t('dashboard.Compare_Disk_Label', 'Disk Version') }}
                        <span id="cvm-disk-path" style="font-size:11px;font-weight:400;color:#94a3b8;margin-inline-start:4px;" dir="ltr"></span>
                    </div>
                </div>
            </div>

            {{-- Loader --}}
            <div id="cvm-loader" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:48px;min-height:300px;">
                <div style="width:36px;height:36px;border-radius:50%;border:3px solid #fef3c7;border-top-color:#d97706;animation:bsm-spin .8s linear infinite;"></div>
                <div style="font-size:13px;color:#64748b;">{{ t('dashboard.Compare_Loading', 'جاري تحميل المقارنة…') }}</div>
            </div>

            {{-- Error state --}}
            <div id="cvm-error" style="display:none;flex:1;flex-direction:column;align-items:center;justify-content:center;gap:12px;padding:48px;text-align:center;">
                <i class="ti ti-alert-circle" style="font-size:40px;color:#ef4444;"></i>
                <div id="cvm-error-msg" style="font-size:14px;color:#64748b;"></div>
            </div>

            {{-- Monaco Diff container --}}
            <div id="cvm-diff-container" style="display:none;flex:1;min-height:0;" dir="ltr">
                <div id="cvm-diff-editor" style="width:100%;height:100%;min-height:400px;"></div>
            </div>

            {{-- Footer --}}
            <div style="display:flex;align-items:center;gap:8px;padding:14px 20px;border-top:1px solid #e2e8f0;flex-shrink:0;background:#f8fafc;flex-wrap:wrap;">
                {{-- Publish Draft button: shown when out_of_sync --}}
                <button id="cvm-publish-btn" type="button" class="btn btn-primary btn-sm" style="display:none;">
                    <i class="ti ti-cloud-upload me-1"></i>{{ t('dashboard.Compare_Publish_Draft', 'Publish Draft') }}
                </button>
                {{-- Copy Disk To Draft (Phase 6) — hidden+disabled until fetch resolves --}}
                <button id="cvm-copy-disk-btn" type="button" class="btn btn-warning btn-sm" disabled style="display:none;"
                        title="{{ t('dashboard.Copy_Disk_Btn_Title', 'استيراد محتوى disk إلى Monaco — بدون حفظ تلقائي') }}">
                    <i class="ti ti-arrow-bar-to-right me-1"></i>{{ t('dashboard.Compare_Copy_Disk', 'Copy Disk To Draft') }}
                </button>
                <span style="flex:1;"></span>
                <button id="cvm-close" type="button" class="btn btn-light btn-sm">
                    {{ t('dashboard.Close', 'إغلاق') }}
                </button>
            </div>

        </div>
    </div>

    <style>
    /* ── Monaco Fullscreen ── */
    #blade-editor-card.blade-fullscreen {
        position: fixed !important;
        inset: 0 !important;
        z-index: 9999 !important;
        border-radius: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        margin: 0 !important;
    }
    #blade-editor-card.blade-fullscreen .card-header {
        flex-shrink: 0;
    }
    #blade-editor-card.blade-fullscreen .card-body {
        flex: 1 1 auto !important;
        overflow: hidden !important;
        min-height: 0 !important;
    }
    #blade-editor-card.blade-fullscreen #monaco-editor-container {
        height: 100% !important;
    }
    #blade-editor-card.blade-fullscreen .card-footer {
        flex-shrink: 0;
    }
    /* prevent page scroll when fullscreen */
    body.monaco-fullscreen-active {
        overflow: hidden !important;
    }
    </style>

    @push('scripts')
    <script>
    (function () {
        var SD_KEY = 'sd-edit-{{ $sectionDefinition->id }}-tab';
        var defaultTab = '{{ $sdTabDefault }}';

        function sdSetTab(name) {
            // persist
            try { localStorage.setItem(SD_KEY, name); } catch(e) {}

            // toggle panes
            document.querySelectorAll('.sd-tab-pane').forEach(function (p) {
                p.style.display = (p.id === 'sd-pane-' + name) ? '' : 'none';
            });

            // trigger Monaco layout after pane becomes visible
            if (name === 'blade') {
                setTimeout(function () {
                    if (window.__monacoInstance) { window.__monacoInstance.layout(); }
                }, 60);
            }

            // toggle button styles
            var activeClasses   = ['border-b-2','border-indigo-600','text-indigo-700','bg-indigo-50/60','font-semibold'];
            var inactiveClasses = ['border-b-2','border-transparent','text-slate-500'];
            document.querySelectorAll('.sd-tab-btn').forEach(function (btn) {
                var isActive = btn.id === 'sd-tab-btn-' + name;
                activeClasses.forEach(function (c) {
                    btn.classList.toggle(c, isActive);
                });
                inactiveClasses.forEach(function (c) {
                    btn.classList.toggle(c, !isActive);
                });
                // hover classes — keep always
                btn.classList.add('hover:text-slate-700', 'hover:bg-slate-50');
            });
        }

        // expose globally for onclick=""
        window.sdSetTab = sdSetTab;

        // init from localStorage or server-side default
        var saved = null;
        try { saved = localStorage.getItem(SD_KEY); } catch(e) {}
        sdSetTab(saved === 'blade' || saved === 'info' ? saved : defaultTab);
    })();

    // ── QW5: Field Search — مستقل تماماً عن Monaco ──
    (function () {
        var searchInput = document.getElementById('field-search-input');
        if (!searchInput) return;

        function filterFields(q) {
            q = q.trim().toLowerCase();
            var noMatch = 0;
            document.querySelectorAll('#fields-reference-list > div').forEach(function (row) {
                var keyEl = row.querySelector('code');
                var key   = keyEl ? keyEl.textContent.toLowerCase() : '';
                var show  = !q || key.includes(q);
                row.style.display = show ? '' : 'none';
                if (!show) noMatch++;
            });
        }

        searchInput.addEventListener('input', function () { filterFields(this.value); });
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { this.value = ''; filterFields(''); this.blur(); }
        });
    })();
    </script>
    @endpush

    <form action="{{ route('dashboard.section_definitions.write_blade', $sectionDefinition) }}"
          method="POST" id="blade-write-form" class="d-none">
        @csrf
        <input type="hidden" name="blade_source" id="blade-write-source">
    </form>

    {{-- ── عزل Monaco AMD loader عن أي AMD loader آخر في الـ theme ── --}}
    <script>
    // حفظ أي AMD loader موجود مسبقاً (theme / RequireJS)
    window.__amd_define_backup  = window.define;
    window.__amd_require_backup = window.require;
    // إزالتهما لأن Monaco loader.js يكتشف وجودهما ويتصرف بشكل مختلف
    window.define  = undefined;
    window.require = undefined;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs/loader.js"></script>
    <script>
    // احفظ require الخاص بـ Monaco
    window.__monacoRequire = window.require;
    // أخفِ define.amd فوراً (synchronous — قبل أي script آخر يمكن تشغيله)
    // Monaco modules تستخدم pure AMD calls لا تفحص define.amd
    // أما feather / sweetalert2 / Sortable (UMD) تفحص define.amd → ستتجاهل AMD وتسجّل في window
    if (typeof window.define === 'function') {
        try { window.define.amd = false; } catch (e) {}
    }
    </script>
    @push('scripts')
    <script>
    window.__sdEditorData = {
        fields:         @json($bladeFields->toArray()),
        sectionKey:     @json($sectionDefinition->section_key),
        editId:         {{ $sectionDefinition->id }},
        scaffoldDate:   '{{ now()->toDateString() }}',
        initialContent: @json((string) (old('blade_source') ?? $bladeInitialContent ?? $sectionDefinition->blade_source ?? '')),
        scaffoldUrl:       '{{ route('dashboard.section_definitions.blade_scaffold', $sectionDefinition) }}',
        generateWriteUrl:  '{{ route('dashboard.section_definitions.generate_write_blade', $sectionDefinition) }}',
        compareUrl:        '{{ route('dashboard.section_definitions.compare_blade', $sectionDefinition) }}',
        copyDiskConfirmTitle: @json(t('dashboard.Copy_Disk_Confirm_Title', 'Copy Disk Content To Draft?')),
        copyDiskConfirmBody:  @json(t('dashboard.Copy_Disk_Confirm_Body', 'سيتم استبدال محتوى Monaco الحالي.\nلن يتم حفظ أي شيء أو نشره تلقائياً.')),
        copyDiskSuccessTitle: @json(t('dashboard.Copy_Disk_Success_Title', 'تم نسخ Disk إلى Draft')),
        copyDiskSuccessMsg:   @json(t('dashboard.Copy_Disk_Success_Msg', 'تذكر الحفظ أو النشر إذا أردت الاحتفاظ بالتغييرات.'))
    };
    </script>
    @verbatim
    <script>
    (function () {
        'use strict';

        // feather/Swal/Sortable قد حفظت نفسها في window.* بالفعل — أعد define.amd حتى تتمكن وحدات Monaco من التسجيل
        if (typeof window.define === 'function') { try { window.define.amd = {}; } catch (e) {} }
        window.__monacoRequire.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs' } });

        var data           = window.__sdEditorData;
        var fieldsData     = data.fields;
        var sectionKey     = data.sectionKey;
        var sdEditId       = String(data.editId);
        var scaffoldDate   = data.scaffoldDate;
        var initialContent = data.initialContent || '';

        var writeBtn       = document.getElementById('blade-write-btn');
        var writeBtnSide   = document.getElementById('blade-write-btn-sidebar');
        var writeForm      = document.getElementById('blade-write-form');
        var writeSource    = document.getElementById('blade-write-source');
        var scaffoldBtn    = document.getElementById('blade-scaffold-btn');
        var scaffoldLabel  = document.getElementById('scaffold-btn-label');
        var scaffoldBadge  = document.getElementById('scaffold-missing-badge');
        var statsEl        = document.getElementById('blade-editor-stats');
        var copyCodeBtn    = document.getElementById('copy-code-btn');
        var clearCodeBtn   = document.getElementById('clear-code-btn');
        var copyPathBtn    = document.getElementById('copy-path-btn');
        var monacoInstance = null;

        /* ── 1. DETECTION ── */
        function getCode() { return monacoInstance ? monacoInstance.getValue() : ''; }
        function setCode(v) { if (monacoInstance) monacoInstance.setValue(v); }

        function isFieldUsed(code, key) {
            return code.indexOf('$' + key) !== -1
                || code.indexOf("'" + key + "'") !== -1
                || code.indexOf('"' + key + '"') !== -1;
        }
        function getMissingFields(code) {
            return fieldsData.filter(function (f) { return !isFieldUsed(code, f.field_key); });
        }

        /* ── 2. VISUAL INDICATORS ── */
        function updateFieldIndicators() {
            var code = getCode();
            var missing = 0;
            fieldsData.forEach(function (f) {
                var used = isFieldUsed(code, f.field_key);
                var dot  = document.getElementById('field-dot-' + f.field_key);
                var btn  = document.getElementById('field-insert-' + f.field_key);
                if (!used) missing++;
                if (dot) { dot.style.background = used ? '#16a34a' : '#d97706'; }
                if (btn) {
                    if (used) {
                        btn.innerHTML = '<i class="ti ti-check" style="font-size:10px;color:#16a34a;"></i>';
                        btn.style.background  = '#f0fdf4';
                        btn.style.borderColor = '#bbf7d0';
                        btn.style.color       = '#16a34a';
                    } else {
                        btn.innerHTML = '<i class="ti ti-plus" style="font-size:10px;"></i>';
                        btn.style.background  = '';
                        btn.style.borderColor = '';
                        btn.style.color       = '';
                    }
                }
            });
            if (scaffoldLabel && scaffoldBadge) {
                if (!getCode().trim()) {
                    scaffoldLabel.textContent = 'Scaffold كامل';
                    scaffoldBadge.style.display = 'none';
                } else if (missing > 0) {
                    scaffoldLabel.textContent = 'إضافة الناقص';
                    scaffoldBadge.textContent  = missing;
                    scaffoldBadge.style.display = '';
                } else {
                    scaffoldLabel.textContent = 'Scaffold (استبدال)';
                    scaffoldBadge.style.display = 'none';
                }
            }
        }

        /* ── 3. STATS ── */
        function updateStats() {
            if (!monacoInstance || !statsEl) return;
            var model = monacoInstance.getModel();
            statsEl.textContent = model.getLineCount() + ' سطر · ' + model.getValue().length + ' حرف';
        }

        /* ── 4. CODE GENERATION ── */
        // Runtime contract: $data is a flat array of all field values (shared + translatable merged).
        // $content is an alias for $data in definition-driven sections.
        // Do NOT generate $sharedData, $translatableData, or $fields — those are not defined at runtime.
        function generateSnippet(f) {
            var k = f.field_key, type = f.field_type;
            var lines;
            if (type === 'media' || type === 'image') {
                lines = [
                    '@php $' + k + ' = \\App\\Support\\Sections\\SectionFrontendMediaResolver::resolve($data[\'' + k + '\'] ?? null); @endphp',
                    '@if ($' + k + ')', '    <img src="{{ $' + k + ' }}" alt="{{ $data[\'' + k + '_alt\'] ?? \'\' }}">', '@endif'
                ];
            } else if (type === 'boolean' || type === 'toggle') {
                lines = [
                    '@if (!empty($data[\'' + k + '\']))',
                    '    {{-- ' + k + ' enabled --}}',
                    '@endif'
                ];
            } else if (type === 'repeater') {
                // Smart repeater: build from item_schema when available
                var itemSchema = (f.schema && Array.isArray(f.schema.item_schema)) ? f.schema.item_schema : [];
                // Clean item variable: strip trailing 's' (features→$feature, services→$service)
                var itemVar = (k.length > 2 && k.slice(-1) === 's') ? '$' + k.slice(0, -1) : '$' + k + 'Item';
                if (itemSchema.length > 0) {
                    lines = [
                        '@foreach (is_array($data[\'' + k + '\'] ?? null) ? $data[\'' + k + '\'] : [] as ' + itemVar + ')',
                        '    <div>',
                    ];
                    itemSchema.forEach(function (sub) {
                        var sk = sub.key, stype = sub.type;
                        var isIconField = (sk === 'icon' || sk === 'icon_class' || sk.slice(-5) === '_icon' || sk.slice(-11) === '_icon_class');
                        if (stype === 'media') {
                            lines.push('        @if(!empty(' + itemVar + '[\'' + sk + '\']))');
                            lines.push('            <img src="{{ ' + itemVar + '[\'' + sk + '\'] ?? \'\' }}" alt="">');
                            lines.push('        @endif');
                        } else if (stype === 'boolean') {
                            lines.push('        @if(!empty(' + itemVar + '[\'' + sk + '\']))');
                            lines.push('            {{-- ' + sk + ' enabled --}}');
                            lines.push('        @endif');
                        } else if (stype === 'url') {
                            lines.push('        @if(!empty(' + itemVar + '[\'' + sk + '\']))');
                            lines.push('            <a href="{{ ' + itemVar + '[\'' + sk + '\'] ?? \'\' }}">{{ ' + itemVar + '[\'' + sk + '\'] ?? \'\' }}</a>');
                            lines.push('        @endif');
                        } else if (isIconField) {
                            lines.push('        @if(!empty(' + itemVar + '[\'' + sk + '\']))');
                            lines.push('            <i class="{{ ' + itemVar + '[\'' + sk + '\'] ?? \'\' }}"></i>');
                            lines.push('        @endif');
                        } else {
                            // text, textarea, select — simple output
                            lines.push('        {{ ' + itemVar + '[\'' + sk + '\'] ?? \'\' }}');
                        }
                    });
                    lines.push('    </div>');
                    lines.push('@endforeach');
                } else {
                    // Fallback: no item_schema defined yet
                    lines = [
                        '@foreach (is_array($data[\'' + k + '\'] ?? null) ? $data[\'' + k + '\'] : [] as ' + itemVar + ')',
                        '    {{-- render ' + itemVar + ' --}}',
                        '@endforeach'
                    ];
                }
            } else if (type === 'textarea' || type === 'richtext' || type === 'html') {
                lines = [
                    '@php $' + k + ' = trim((string)($data[\'' + k + '\'] ?? \'\')); @endphp',
                    '@if ($' + k + ')', '    <div>{!! $' + k + ' !!}</div>', '@endif'
                ];
            } else {
                lines = [
                    '@php $' + k + ' = trim((string)($data[\'' + k + '\'] ?? \'\')); @endphp',
                    '@if ($' + k + ')', '    <p>{{ $' + k + ' }}</p>', '@endif'
                ];
            }
            return lines.join('\n');
        }

        function generateFullScaffold() {
            var phpLines  = ['@php', '    // Scaffold: ' + sectionKey + ' — ' + scaffoldDate];
            var htmlParts = ['<section class="section-' + sectionKey + '">'];
            fieldsData.forEach(function (f) {
                var k = f.field_key, type = f.field_type, scope = f.field_scope;
                var comment = '    {{-- ' + k + ' / ' + type + ' / ' + scope + ' --}}';
                if (type === 'media' || type === 'image') {
                    phpLines.push('    $' + k + ' = \\App\\Support\\Sections\\SectionFrontendMediaResolver::resolve($data[\'' + k + '\'] ?? null);');
                    htmlParts.push(comment, '    @if ($' + k + ')', '        <img src="{{ $' + k + ' }}" alt="{{ $data[\'' + k + '_alt\'] ?? \'\' }}">', '    @endif');
                } else if (type === 'boolean' || type === 'toggle') {
                    phpLines.push('    $' + k + ' = !empty($data[\'' + k + '\']);');
                    htmlParts.push(comment, '    @if ($' + k + ')', '        {{-- ' + k + ' enabled --}}', '    @endif');
                } else if (type === 'repeater') {
                    phpLines.push('    $' + k + ' = is_array($data[\'' + k + '\'] ?? null) ? $data[\'' + k + '\'] : [];');
                    // Smart scaffold: use item_schema for meaningful body content
                    var scaffItemSchema = (f.schema && Array.isArray(f.schema.item_schema)) ? f.schema.item_schema : [];
                    var scaffItemVar = (k.length > 2 && k.slice(-1) === 's') ? '$' + k.slice(0, -1) : '$' + k + 'Item';
                    var scaffBody;
                    if (scaffItemSchema.length > 0) {
                        scaffBody = ['    @foreach ($' + k + ' as ' + scaffItemVar + ')', '        <div>'];
                        scaffItemSchema.forEach(function (sub) {
                            var sk = sub.key, stype = sub.type;
                            var isIconField = (sk === 'icon' || sk === 'icon_class' || sk.slice(-5) === '_icon' || sk.slice(-11) === '_icon_class');
                            if (stype === 'media') {
                                scaffBody.push('            @if(!empty(' + scaffItemVar + '[\'' + sk + '\']))');
                                scaffBody.push('                <img src="{{ ' + scaffItemVar + '[\'' + sk + '\'] ?? \'\' }}" alt="">');
                                scaffBody.push('            @endif');
                            } else if (stype === 'boolean') {
                                scaffBody.push('            @if(!empty(' + scaffItemVar + '[\'' + sk + '\']))');
                                scaffBody.push('                {{-- ' + sk + ' enabled --}}');
                                scaffBody.push('            @endif');
                            } else if (stype === 'url') {
                                scaffBody.push('            @if(!empty(' + scaffItemVar + '[\'' + sk + '\']))');
                                scaffBody.push('                <a href="{{ ' + scaffItemVar + '[\'' + sk + '\'] ?? \'\' }}">{{ ' + scaffItemVar + '[\'' + sk + '\'] ?? \'\' }}</a>');
                                scaffBody.push('            @endif');
                            } else if (isIconField) {
                                scaffBody.push('            @if(!empty(' + scaffItemVar + '[\'' + sk + '\']))');
                                scaffBody.push('                <i class="{{ ' + scaffItemVar + '[\'' + sk + '\'] ?? \'\' }}"></i>');
                                scaffBody.push('            @endif');
                            } else {
                                scaffBody.push('            {{ ' + scaffItemVar + '[\'' + sk + '\'] ?? \'\' }}');
                            }
                        });
                        scaffBody.push('        </div>', '    @endforeach');
                    } else {
                        scaffBody = ['    @foreach ($' + k + ' as ' + scaffItemVar + ')', '        {{-- render ' + scaffItemVar + ' --}}', '    @endforeach'];
                    }
                    htmlParts.push(comment);
                    scaffBody.forEach(function (l) { htmlParts.push(l); });
                } else {
                    var isHtml = (type === 'textarea' || type === 'richtext' || type === 'html');
                    phpLines.push('    $' + k + ' = trim((string)($data[\'' + k + '\'] ?? \'\'));');
                    htmlParts.push(comment, '    @if ($' + k + ')',
                        isHtml ? '        <div>{!! $' + k + ' !!}</div>' : '        <p>{{ $' + k + ' }}</p>',
                        '    @endif');
                }
            });
            phpLines.push('@endphp', '');
            htmlParts.push('</section>');
            return phpLines.concat(htmlParts).join('\n');
        }


        /* ── 5. TOAST NOTIFICATION ── */
        function showWriteToast(type, title, detail) {
            // إزالة أي toast سابق
            var prev = document.getElementById('blade-write-toast');
            if (prev) prev.remove();

            var isSuccess = type === 'success';
            var colors = isSuccess
                ? { bg: '#f0fdf4', border: '#86efac', icon: '#16a34a', bar: '#16a34a', text: '#166534' }
                : { bg: '#fef2f2', border: '#fca5a5', icon: '#dc2626', bar: '#dc2626', text: '#991b1b' };

            var iconSvg = isSuccess
                ? '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
                : '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';

            var toast = document.createElement('div');
            toast.id = 'blade-write-toast';
            toast.dir = 'rtl';
            toast.style.cssText = [
                'position:fixed',
                'top:24px',
                'right:24px',
                'z-index:99999',
                'min-width:320px',
                'max-width:460px',
                'background:' + colors.bg,
                'border:1.5px solid ' + colors.border,
                'border-radius:14px',
                'box-shadow:0 8px 32px rgba(0,0,0,.13),0 2px 8px rgba(0,0,0,.07)',
                'overflow:hidden',
                'transform:translateX(120%)',
                'transition:transform .35s cubic-bezier(.34,1.56,.64,1),opacity .35s ease',
                'opacity:0',
                'font-family:inherit',
            ].join(';');

            toast.innerHTML =
                '<div style="display:flex;align-items:flex-start;gap:12px;padding:16px 16px 14px;">' +
                    '<div style="flex-shrink:0;width:36px;height:36px;border-radius:50%;background:' + colors.icon + '22;display:flex;align-items:center;justify-content:center;color:' + colors.icon + ';">' + iconSvg + '</div>' +
                    '<div style="flex:1;min-width:0;">' +
                        '<div style="font-weight:700;font-size:14px;color:' + colors.text + ';line-height:1.3;margin-bottom:' + (detail ? '4px' : '0') + ';">' + title + '</div>' +
                        (detail ? '<div style="font-size:12px;color:#64748b;line-height:1.5;word-break:break-word;">' + detail + '</div>' : '') +
                    '</div>' +
                    '<button id="blade-toast-close" style="flex-shrink:0;background:none;border:none;cursor:pointer;padding:2px;color:#94a3b8;line-height:1;margin-top:-2px;" title="إغلاق">' +
                        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                    '</button>' +
                '</div>' +
                '<div id="blade-toast-bar" style="height:3px;background:' + colors.bar + ';width:100%;transform-origin:right;transition:transform linear;"></div>';

            document.body.appendChild(toast);

            // Close button event
            var closeBtn = document.getElementById('blade-toast-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () { dismissToast(toast); });
            }

            // Animate in
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    toast.style.transform = 'translateX(0)';
                    toast.style.opacity   = '1';
                });
            });

            // Progress bar countdown
            var duration = isSuccess ? 3500 : 6000;
            var bar = document.getElementById('blade-toast-bar');
            if (bar) {
                bar.style.transition = 'transform ' + duration + 'ms linear';
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        bar.style.transform = 'scaleX(0)';
                    });
                });
            }

            // Auto dismiss
            var timer = setTimeout(function () { dismissToast(toast); }, duration);
            toast.addEventListener('mouseenter', function () { clearTimeout(timer); if (bar) bar.style.animationPlayState = 'paused'; bar.style.transition = 'none'; });
            toast.addEventListener('mouseleave', function () {
                var remaining = bar ? parseFloat(bar.style.transform.replace('scaleX(', '')) * duration : 1000;
                if (bar) { bar.style.transition = 'transform ' + Math.max(remaining, 800) + 'ms linear'; bar.style.transform = 'scaleX(0)'; }
                timer = setTimeout(function () { dismissToast(toast); }, Math.max(remaining, 800));
            });
        }

        function dismissToast(toast) {
            if (!toast || !toast.parentNode) return;
            toast.style.transform  = 'translateX(120%)';
            toast.style.opacity    = '0';
            setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 400);
        }

        /* ── 6. WRITE TO DISK ── */
        function doWrite(btn) {
            if (!writeForm || !monacoInstance) return;
            var msg = btn ? btn.dataset.confirm : null;
            if (msg && !window.confirm(msg)) return;

            // URL مباشر — بعد إصلاح .htaccess في public_html لا حاجة لإضافة /public/
            var url = writeForm.action;
            // قراءة XSRF-TOKEN من الـ cookie (أكثر موثوقية من _token في الـ form عند redirect)
            var xsrfCookie = document.cookie.split(';').map(function(c){return c.trim();}).find(function(c){return c.startsWith('XSRF-TOKEN=');});
            var xsrfToken  = xsrfCookie ? decodeURIComponent(xsrfCookie.split('=').slice(1).join('=')) : '';
            var formToken  = writeForm.querySelector('[name=_token]') ? writeForm.querySelector('[name=_token]').value : '';
            var csrf       = formToken; // للـ body
            var code = getCode();
            // نُشفّر الكود بـ base64 لتجاوز ModSecurity على الـ shared hosting
            // الـ Controller يفكّ التشفير قبل الكتابة
            var encoded;
            try {
                encoded = btoa(unescape(encodeURIComponent(code)));
            } catch(e) {
                encoded = btoa(code);
            }
            var body = new URLSearchParams({ _token: csrf, blade_source_b64: encoded });

            if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }

            var fetchHeaders = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
            if (xsrfToken) { fetchHeaders['X-XSRF-TOKEN'] = xsrfToken; }

            fetch(url, {
                method:   'POST',
                headers:  fetchHeaders,
                body:     body,
                redirect: 'manual'
            })
                .then(function (res) {
                    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                    if (res.type === 'opaqueredirect' || res.status === 0) {
                        showWriteToast('error', 'خطأ في الإرسال', 'الطلب تحوّل لـ GET — تحقق من إعدادات Apache.');
                        return;
                    }
                    return res.text().then(function (text) {
                        var data;
                        try { data = JSON.parse(text); } catch (e) {
                            var snippet = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 300);
                            showWriteToast('error', 'خطأ في السيرفر (HTTP ' + res.status + ')', snippet);
                            return;
                        }
                        if (data.ok) {
                            showWriteToast('success', data.message || 'تم كتابة الملف بنجاح', '');
                        } else {
                            showWriteToast('error', 'فشلت الكتابة', data.error || 'حدث خطأ غير معروف.');
                        }
                    });
                })
                .catch(function (err) {
                    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                    showWriteToast('error', 'خطأ في الاتصال', err.message);
                });
        }

        /* ── 7. INSERT AT CURSOR ── */
        function insertAtCursor(snippet) {
            if (!monacoInstance) return;
            var pos   = monacoInstance.getPosition();
            var range = new window.monaco.Range(pos.lineNumber, pos.column, pos.lineNumber, pos.column);
            monacoInstance.executeEdits('insert-field', [{ range: range, text: '\n' + snippet + '\n' }]);
            monacoInstance.focus();
        }

        /* ── 8. EVENT LISTENERS (outside Monaco init — no Monaco API needed) ── */
        if (writeBtn)     writeBtn.addEventListener('click',     function () { doWrite(this); });
        if (writeBtnSide) writeBtnSide.addEventListener('click', function () { doWrite(this); });

        if (copyPathBtn) {
            copyPathBtn.addEventListener('click', function () {
                var path = copyPathBtn.dataset.path || '';
                if (navigator.clipboard && path) {
                    navigator.clipboard.writeText(path).then(function () {
                        copyPathBtn.innerHTML = '<i class="ti ti-check"></i>';
                        setTimeout(function () { copyPathBtn.innerHTML = '<i class="ti ti-copy text-base"></i>'; }, 1500);
                    });
                }
            });
        }

        /* ── 9. CTRL+S SHORTCUT ── */
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                var bladeTabBtn = document.getElementById('sd-tab-btn-blade');
                if (bladeTabBtn && bladeTabBtn.classList.contains('border-indigo-600')) {
                    e.preventDefault();
                    doWrite(writeBtn);
                }
            }
        });

        /* ── 10. MONACO INITIALIZATION ── */
        window.__monacoRequire(['vs/editor/editor.main'], function () {
            // Restore AMD after Monaco loads
            if (typeof window.define === 'function') {
                if (typeof window.__amd_define_backup === 'function') {
                    window.define  = window.__amd_define_backup;
                    window.require = window.__amd_require_backup;
                } else {
                    try { window.define.amd = false; } catch (e) {}
                }
            }

            var container = document.getElementById('monaco-editor-container');
            if (!container) return;

            // Catppuccin Mocha theme
            monaco.editor.defineTheme('catppuccin-mocha', {
                base: 'vs-dark', inherit: true,
                rules: [
                    { token: '',                foreground: 'cdd6f4' },
                    { token: 'comment',         foreground: '6c7086', fontStyle: 'italic' },
                    { token: 'keyword',         foreground: 'cba6f7', fontStyle: 'bold' },
                    { token: 'string',          foreground: 'a6e3a1' },
                    { token: 'tag',             foreground: 'f38ba8' },
                    { token: 'attribute.name',  foreground: 'fab387' },
                    { token: 'attribute.value', foreground: 'a6e3a1' },
                ],
                colors: {
                    'editor.background':              '#1e1e2e',
                    'editor.foreground':              '#cdd6f4',
                    'editorLineNumber.foreground':    '#45475a',
                    'editor.lineHighlightBackground': '#313244',
                    'editorCursor.foreground':        '#f5c2e7',
                    'editor.selectionBackground':     '#45475a',
                    'editorGutter.background':        '#1e1e2e',
                }
            });

            monacoInstance = monaco.editor.create(container, {
                value:                initialContent,
                language:             'html',
                theme:                'catppuccin-mocha',
                fontSize:             13,
                fontFamily:           '"JetBrains Mono", "Cascadia Code", "Fira Code", Consolas, monospace',
                lineNumbers:          'on',
                minimap:              { enabled: true, scale: 1, renderCharacters: false },
                wordWrap:             'off',
                scrollBeyondLastLine: false,
                automaticLayout:      true,
                tabSize:              4,
                insertSpaces:         true,
                folding:              true,
                renderLineHighlight:  'line',
                padding:              { top: 16, bottom: 16 },
                smoothScrolling:      true,
                cursorBlinking:       'smooth',
            });

            // Expose globally for tab switch layout()
            window.__monacoInstance = monacoInstance;

            // Ctrl+S inside Monaco
            monacoInstance.addCommand(
                monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS,
                function () { doWrite(writeBtn); }
            );

            // Content change → stats + indicators
            monacoInstance.onDidChangeModelContent(function () {
                updateStats();
                updateFieldIndicators();
            });

            // ── Scaffold button → server-side BladeGenerator preview ──────────
            if (scaffoldBtn) {
                scaffoldBtn.addEventListener('click', function () {
                    openScaffoldPreview();
                });
            }

            function openScaffoldPreview() {
                var modal    = document.getElementById('blade-scaffold-modal');
                var codeEl   = document.getElementById('bsm-code');
                var statsEl2 = document.getElementById('bsm-stats');
                var loader   = document.getElementById('bsm-loader');
                var body     = document.getElementById('bsm-body');
                if (!modal) return;

                // Show modal in loading state
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                if (loader) loader.style.display = '';
                if (body)   body.style.display   = 'none';

                // Fetch from server
                var url = data.scaffoldUrl;
                fetch(url, {
                    method:  'GET',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (codeEl)   codeEl.textContent = json.scaffold || '';
                    if (statsEl2 && json.stats) {
                        var s = json.stats;
                        statsEl2.innerHTML =
                            '<span class="bsm-stat"><i class="ti ti-layout-list"></i> ' + s.fields     + ' حقل</span>' +
                            '<span class="bsm-stat"><i class="ti ti-repeat"></i> '       + s.repeaters  + ' repeater</span>' +
                            '<span class="bsm-stat"><i class="ti ti-puzzle"></i> '       + s.components + ' component</span>';
                        if (s.component_names && s.component_names.length) {
                            statsEl2.innerHTML += '<span class="bsm-chips">' +
                                s.component_names.map(function(c){ return '<span class="bsm-chip">' + c + '</span>'; }).join('') +
                            '</span>';
                        }
                    }
                    if (loader) loader.style.display = 'none';
                    if (body)   body.style.display   = '';
                })
                .catch(function (err) {
                    if (codeEl)   codeEl.textContent = '{{-- خطأ أثناء التوليد: ' + err + ' --}}';
                    if (loader) loader.style.display = 'none';
                    if (body)   body.style.display   = '';
                });
            }

            // Modal controls
            (function () {
                var modal       = document.getElementById('blade-scaffold-modal');
                var insertBtn   = document.getElementById('bsm-insert');
                var copyBtn     = document.getElementById('bsm-copy');
                var closeBtn    = document.getElementById('bsm-close');
                var closeXBtn   = document.getElementById('bsm-close-x');
                if (!modal) return;

                function closeModal() {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }

                if (closeBtn)  closeBtn.addEventListener('click',  closeModal);
                if (closeXBtn) closeXBtn.addEventListener('click', closeModal);

                // Close on backdrop click
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeModal();
                });
                // Close on Escape
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
                });

                // ── Generate & Write directly ──────────────────────────────────
                var genWriteBtn = document.getElementById('bsm-generate-write');
                if (genWriteBtn) {
                    genWriteBtn.addEventListener('click', function () {
                        doGenerateAndWrite(false);
                    });
                }

                function doGenerateAndWrite(force) {
                    var url = data.generateWriteUrl;
                    if (!url) return;

                    var btn = document.getElementById('bsm-generate-write');
                    var xsrfCookie = document.cookie.split(';')
                        .map(function (c) { return c.trim(); })
                        .find(function (c) { return c.startsWith('XSRF-TOKEN='); });
                    var xsrfToken = xsrfCookie
                        ? decodeURIComponent(xsrfCookie.split('=').slice(1).join('='))
                        : '';
                    var formToken = document.querySelector('[name=_token]')
                        ? document.querySelector('[name=_token]').value
                        : '';

                    var body = new URLSearchParams({ _token: formToken });
                    if (force) { body.append('force', '1'); }

                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="ti ti-loader-2 me-1" style="display:inline-block;animation:bsm-spin .8s linear infinite;"></i>جاري الكتابة…';
                    }

                    fetch(url, {
                        method:   'POST',
                        headers:  {
                            'Accept':            'application/json',
                            'X-Requested-With':  'XMLHttpRequest',
                            'X-XSRF-TOKEN':      xsrfToken,
                        },
                        body:     body,
                        redirect: 'manual',
                    })
                    .then(function (res) {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="ti ti-file-download me-1"></i>توليد وكتابة مباشرة';
                        }

                        if (res.type === 'opaqueredirect' || res.status === 0) {
                            showWriteToast('error', 'خطأ في الإرسال', 'الطلب تحوّل لـ GET — تحقق من إعدادات Apache.');
                            return;
                        }

                        return res.text().then(function (text) {
                            var json;
                            try { json = JSON.parse(text); } catch (e) {
                                var snippet = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 300);
                                showWriteToast('error', 'خطأ في السيرفر (HTTP ' + res.status + ')', snippet);
                                return;
                            }

                            if (json.ok) {
                                // Put generated scaffold into Monaco so editor reflects disk state
                                if (json.scaffold && monacoInstance) {
                                    setCode(json.scaffold);
                                    updateFieldIndicators();
                                    updateStats();
                                }
                                var detail = json.path || '';
                                if (json.view) { detail += (detail ? '\n' : '') + 'View: ' + json.view; }
                                showWriteToast('success', json.message || 'تم توليد وكتابة الملف بنجاح', detail);
                                closeModal();

                            } else if (json.requires_confirmation) {
                                // External file detected — ask user
                                var confirmed = window.confirm(
                                    (json.warning || 'الملف موجود مسبقاً.') +
                                    (json.path ? '\n\nالمسار: ' + json.path : '') +
                                    '\n\nهل تريد الكتابة فوقه؟'
                                );
                                if (confirmed) {
                                    doGenerateAndWrite(true);
                                }

                            } else {
                                showWriteToast('error', 'فشل التوليد', json.error || 'حدث خطأ غير معروف.');
                            }
                        });
                    })
                    .catch(function (err) {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="ti ti-file-download me-1"></i>توليد وكتابة مباشرة';
                        }
                        showWriteToast('error', 'خطأ في الاتصال', err.message);
                    });
                }

                // Insert into Monaco
                if (insertBtn) {
                    insertBtn.addEventListener('click', function () {
                        var code = document.getElementById('bsm-code');
                        if (code && monacoInstance) {
                            var currentCode = getCode().trim();
                            if (currentCode && !window.confirm('استبدال الكود الحالي في المحرر؟')) return;
                            setCode(code.textContent);
                            updateFieldIndicators();
                            updateStats();
                        }
                        closeModal();
                    });
                }

                // Copy code
                if (copyBtn) {
                    copyBtn.addEventListener('click', function () {
                        var code = document.getElementById('bsm-code');
                        if (!code) return;
                        navigator.clipboard.writeText(code.textContent).then(function () {
                            copyBtn.innerHTML = '<i class="ti ti-check me-1"></i>نُسخ!';
                            setTimeout(function () {
                                copyBtn.innerHTML = '<i class="ti ti-copy me-1"></i>نسخ الكود';
                            }, 1800);
                        });
                    });
                }
            })();

            // ── Compare Versions (Phase 5) + Copy Disk To Draft (Phase 6) ──────
            (function () {
                var modal        = document.getElementById('compare-modal');
                var loader       = document.getElementById('cvm-loader');
                var errorDiv     = document.getElementById('cvm-error');
                var errorMsg     = document.getElementById('cvm-error-msg');
                var diffCont     = document.getElementById('cvm-diff-container');
                var diffEdEl     = document.getElementById('cvm-diff-editor');
                var labels       = document.getElementById('cvm-labels');
                var diskPathEl   = document.getElementById('cvm-disk-path');
                var subtitleEl   = document.getElementById('cvm-subtitle');
                var publishBtn   = document.getElementById('cvm-publish-btn');
                var copyDiskBtn  = document.getElementById('cvm-copy-disk-btn');
                var closeBtn     = document.getElementById('cvm-close');
                var closeXBtn    = document.getElementById('cvm-close-x');
                var compareUrl   = (typeof data !== 'undefined') ? data.compareUrl : null;

                var triggerBtns = document.querySelectorAll('#compare-versions-btn');

                if (!modal || !compareUrl || !triggerBtns.length) return;

                var diffEditorInstance = null;
                var _diffOriginalModel = null;
                var _diffModifiedModel = null;

                // ── Phase 6: closure cache for disk content ──────────────────────
                var _cachedDiskContent = null;

                function resetModal() {
                    if (loader)    loader.style.display    = 'flex';
                    if (errorDiv)  errorDiv.style.display  = 'none';
                    if (diffCont)  diffCont.style.display  = 'none';
                    if (labels)    labels.style.display    = 'none';
                    if (publishBtn)  publishBtn.style.display  = 'none';
                    // Reset Copy Disk btn to hidden + disabled
                    if (copyDiskBtn) {
                        copyDiskBtn.style.display = 'none';
                        copyDiskBtn.disabled      = true;
                    }
                    _cachedDiskContent = null;
                }

                function showError(msg) {
                    if (loader)   loader.style.display   = 'none';
                    if (errorMsg) errorMsg.textContent   = msg;
                    if (errorDiv) errorDiv.style.display = 'flex';
                }

                function openCompareVersions() {
                    resetModal();
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';

                    var xsrfCookie = document.cookie.split(';')
                        .map(function (c) { return c.trim(); })
                        .find(function (c) { return c.startsWith('XSRF-TOKEN='); });
                    var xsrfToken = xsrfCookie
                        ? decodeURIComponent(xsrfCookie.split('=').slice(1).join('='))
                        : '';

                    fetch(compareUrl, {
                        method:  'GET',
                        headers: {
                            'Accept':           'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-XSRF-TOKEN':     xsrfToken,
                        },
                    })
                    .then(function (res) {
                        if (!res.ok) {
                            return res.text().then(function (t) {
                                var msg = t;
                                try { msg = JSON.parse(t).message || t; } catch (e2) {}
                                throw new Error('HTTP ' + res.status + ': ' + msg);
                            });
                        }
                        return res.json();
                    })
                    .then(function (json) {
                        if (!json.ok) {
                            showError(json.message || 'حدث خطأ غير معروف.');
                            return;
                        }

                        var draftContent = json.draft || '';
                        var diskContent  = json.disk  || '';
                        var syncStatus   = json.sync  || 'unknown';

                        // Cache disk content for Copy Disk To Draft action
                        _cachedDiskContent = diskContent;

                        if (subtitleEl && json.view_name) {
                            subtitleEl.textContent = 'View: ' + json.view_name;
                        }
                        if (diskPathEl && json.path) {
                            diskPathEl.textContent = json.path;
                        }

                        // Publish Draft: only meaningful when Monaco is ahead of disk
                        if (publishBtn && syncStatus === 'out_of_sync') {
                            publishBtn.style.display = '';
                        }

                        // Copy Disk To Draft: show + enable for out_of_sync & external_change
                        // (in_sync = no point; unknown/missing = no file to copy from)
                        if (copyDiskBtn && (syncStatus === 'out_of_sync' || syncStatus === 'external_change')) {
                            copyDiskBtn.style.display = '';
                            copyDiskBtn.disabled      = false;
                        }

                        if (labels)   labels.style.display   = '';
                        if (loader)   loader.style.display   = 'none';
                        if (diffCont) diffCont.style.display = '';

                        buildDiffEditor(draftContent, diskContent);
                    })
                    .catch(function (err) {
                        showError('خطأ في الاتصال: ' + err.message);
                    });
                }

                function buildDiffEditor(draftContent, diskContent) {
                    if (!diffEdEl) return;

                    if (diffEditorInstance) {
                        try { diffEditorInstance.dispose(); } catch (e) {}
                        diffEditorInstance = null;
                    }
                    if (_diffOriginalModel) {
                        try { _diffOriginalModel.dispose(); } catch (e) {}
                        _diffOriginalModel = null;
                    }
                    if (_diffModifiedModel) {
                        try { _diffModifiedModel.dispose(); } catch (e) {}
                        _diffModifiedModel = null;
                    }

                    var monacoRequireFn = window.__monacoRequire || null;

                    if (!monacoRequireFn) {
                        var script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/monaco-editor@0.46.0/min/vs/loader.js';
                        script.onload = function () {
                            window.__monacoRequire = window.require;
                            try { window.define.amd = false; } catch (e) {}
                            window.__monacoRequire.config({
                                paths: { 'vs': 'https://cdn.jsdelivr.net/npm/monaco-editor@0.46.0/min/vs' }
                            });
                            try { window.define.amd = {}; } catch (e) {}
                            window.__monacoRequire(['vs/editor/editor.main'], function (monacoLib) {
                                createDiff(monacoLib || window.monaco, draftContent, diskContent);
                            });
                        };
                        document.head.appendChild(script);
                        return;
                    }

                    if (window.monaco && window.monaco.editor) {
                        createDiff(window.monaco, draftContent, diskContent);
                        return;
                    }

                    monacoRequireFn(['vs/editor/editor.main'], function (monacoLib) {
                        createDiff(monacoLib || window.monaco, draftContent, diskContent);
                    });
                }

                function createDiff(m, draftContent, diskContent) {
                    if (!m || !diffEdEl) return;

                    _diffOriginalModel = m.editor.createModel(draftContent, 'html');
                    _diffModifiedModel = m.editor.createModel(diskContent,  'html');

                    diffEditorInstance = m.editor.createDiffEditor(diffEdEl, {
                        theme:                'vs-dark',
                        readOnly:             true,
                        renderSideBySide:     true,
                        originalEditable:     false,
                        automaticLayout:      true,
                        minimap:              { enabled: false },
                        fontSize:             13,
                        lineHeight:           21,
                        fontFamily:           "'Fira Code', 'Cascadia Code', Consolas, monospace",
                        scrollBeyondLastLine: false,
                    });

                    diffEditorInstance.setModel({
                        original: _diffOriginalModel,
                        modified: _diffModifiedModel,
                    });
                }

                function closeModal() {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }

                // Wire trigger buttons
                triggerBtns.forEach(function (btn) {
                    btn.addEventListener('click', openCompareVersions);
                });

                // Publish Draft: delegate to existing Write button
                if (publishBtn) {
                    publishBtn.addEventListener('click', function () {
                        closeModal();
                        var writeBtn = document.getElementById('blade-write-btn');
                        if (writeBtn) writeBtn.click();
                    });
                }

                // ── Phase 6: Copy Disk To Draft ──────────────────────────────────
                if (copyDiskBtn) {
                    copyDiskBtn.addEventListener('click', function () {
                        if (!_cachedDiskContent && _cachedDiskContent !== '') return;

                        var confirmed = window.confirm(
                            data.copyDiskConfirmTitle + '\n\n' +
                            data.copyDiskConfirmBody
                        );
                        if (!confirmed) return;

                        // Replace Monaco content — no save/write/publish
                        setCode(_cachedDiskContent);
                        updateFieldIndicators();
                        updateStats();

                        closeModal();

                        showWriteToast(
                            'success',
                            data.copyDiskSuccessTitle,
                            data.copyDiskSuccessMsg
                        );
                    });
                }

                if (closeBtn)  closeBtn.addEventListener('click',  closeModal);
                if (closeXBtn) closeXBtn.addEventListener('click', closeModal);

                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeModal();
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
                });
            })();

            // Field insert buttons
            document.querySelectorAll('.field-insert-btn').forEach(function (ibtn) {
                ibtn.addEventListener('click', function () {
                    var snippet = generateSnippet({ field_key: ibtn.dataset.key, field_type: ibtn.dataset.type, field_scope: ibtn.dataset.scope });
                    insertAtCursor(snippet);
                    updateFieldIndicators();
                    updateStats();
                });
            });

            // Copy code
            if (copyCodeBtn) {
                copyCodeBtn.addEventListener('click', function () {
                    var val = getCode();
                    if (!val) return;
                    navigator.clipboard.writeText(val).then(function () {
                        copyCodeBtn.innerHTML = '<i class="ti ti-check me-1"></i>نُسخ';
                        setTimeout(function () { copyCodeBtn.innerHTML = '<i class="ti ti-copy me-1"></i>نسخ'; }, 1600);
                    });
                });
            }

            // Clear code
            if (clearCodeBtn) {
                clearCodeBtn.addEventListener('click', function () {
                    if (!getCode().trim() || window.confirm('مسح كامل الكود؟')) {
                        setCode('');
                        monacoInstance.focus();
                    }
                });
            }

            // Fullscreen toggle
            var fullscreenBtn  = document.getElementById('fullscreen-btn');
            var fullscreenIcon = document.getElementById('fullscreen-icon');
            var editorCard     = document.getElementById('blade-editor-card');
            var isFullscreen   = false;
            if (fullscreenBtn) {
                fullscreenBtn.addEventListener('click', function () {
                    isFullscreen = !isFullscreen;
                    editorCard.classList.toggle('blade-fullscreen', isFullscreen);
                    document.body.classList.toggle('monaco-fullscreen-active', isFullscreen);
                    if (fullscreenIcon) {
                        fullscreenIcon.className = isFullscreen ? 'ti ti-minimize' : 'ti ti-maximize';
                    }
                    setTimeout(function () { if (monacoInstance) monacoInstance.layout(); }, 60);
                });
            }

            // Zoom in/out
            var zoomIn  = document.getElementById('zoom-in-btn');
            var zoomOut = document.getElementById('zoom-out-btn');
            var fontSizeDisplay = document.getElementById('font-size-display');
            var currentFontSize = 13;
            function applyFontSize(sz) {
                currentFontSize = Math.max(10, Math.min(24, sz));
                monacoInstance.updateOptions({ fontSize: currentFontSize });
                if (fontSizeDisplay) fontSizeDisplay.textContent = currentFontSize + 'px';
            }
            if (zoomIn)  zoomIn.addEventListener('click',  function () { applyFontSize(currentFontSize + 1); });
            if (zoomOut) zoomOut.addEventListener('click', function () { applyFontSize(currentFontSize - 1); });

            updateFieldIndicators();
            updateStats();
        });

    }());
    </script>
    @endverbatim
    @endpush

</x-dashboard-layout>
