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
            ->get(['field_key','field_type','field_scope']);
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
                    @if ($bladeFileStatus === 'exists')
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

    {{-- ═══ Alpine Tabs ═══ --}}
    @php $sdTabDefault = session('_blade_tab') === 'blade' ? 'blade' : 'info'; @endphp
    <div x-data="{
            tab: localStorage.getItem('sd-edit-{{ $sectionDefinition->id }}-tab') || '{{ $sdTabDefault }}',
            setTab(t) { this.tab = t; localStorage.setItem('sd-edit-{{ $sectionDefinition->id }}-tab', t); }
         }">

        <div class="card mb-4">
            <div class="card-body py-0 px-2">
                <nav class="flex gap-0">
                    <button type="button" @click="setTab('info')"
                            :class="tab === 'info' ? 'border-b-2 border-indigo-600 text-indigo-700 bg-indigo-50/60 font-semibold' : 'border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                            class="flex items-center gap-2 px-5 py-3.5 text-sm transition-all rounded-t-lg">
                        <i class="ti ti-settings text-base"></i>
                        {{ t('dashboard.Definition_Information', 'معلومات التعريف') }}
                    </button>
                    <button type="button" @click="setTab('blade')"
                            :class="tab === 'blade' ? 'border-b-2 border-indigo-600 text-indigo-700 bg-indigo-50/60 font-semibold' : 'border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                            class="flex items-center gap-2 px-5 py-3.5 text-sm transition-all rounded-t-lg">
                        <i class="ti ti-code text-base"></i>
                        {{ t('dashboard.Blade_Template', 'قالب Blade') }}
                        @if ($bladeFileStatus === 'exists')
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
        <div x-show="tab === 'info'" x-cloak>
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
        <div x-show="tab === 'blade'" x-cloak>
            <div class="grid grid-cols-12 gap-6">
                <div class="col-span-12 xl:col-span-8">
                    <div class="card mb-4">
                        <div class="card-body py-3">
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <i class="ti ti-file-code text-slate-400 text-xl flex-shrink-0"></i>
                                    <div class="min-w-0">
                                        <div class="text-xs text-slate-400 mb-0.5">{{ t('dashboard.Blade_Expected_Path', 'المسار على الـ disk:') }}</div>
                                        <code class="text-sm font-mono text-slate-700 block truncate" dir="ltr" title="{{ $bladeExpectedPath }}">{{ $bladeExpectedPath }}</code>
                                    </div>
                                </div>
                                @if ($sectionDefinition->blade_written_at)
                                    <div class="flex-shrink-0 text-xs text-slate-400 border-r border-slate-200 pe-4">
                                        <i class="ti ti-clock me-1"></i>{{ t('dashboard.Blade_File_Last_Written', 'آخر كتابة:') }}
                                        {{ $sectionDefinition->blade_written_at->diffForHumans() }}
                                    </div>
                                @endif
                                <button type="button" id="copy-path-btn"
                                        class="flex-shrink-0 w-8 h-8 rounded-lg inline-flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                                        data-path="{{ $bladeExpectedPath }}"
                                        title="{{ t('dashboard.Copy', 'نسخ المسار') }}">
                                    <i class="ti ti-copy text-base"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card">
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
                            </div>
                        </div>
                        <div class="card-body p-0" style="background:#1e1e2e;">
                            <textarea id="blade-source-editor"
                                      class="form-control font-mono border-0"
                                      dir="ltr" rows="28" spellcheck="false" autocomplete="off"
                                      style="font-size:13px;line-height:1.65;tab-size:4;resize:vertical;border-radius:0;background:#1e1e2e;color:#cdd6f4;padding:16px 20px;min-height:420px;">{{ old('blade_source', $sectionDefinition->blade_source) }}</textarea>
                        </div>
                        <div class="card-footer d-flex align-items-center gap-3" style="background:#f8fafc;">
                            <span class="text-sm text-slate-400 me-auto font-mono" id="blade-editor-stats"></span>
                            <button type="button" class="btn btn-primary" id="blade-write-btn"
                                    @if ($bladeFileStatus === 'exists') data-confirm="{{ t('dashboard.Blade_Confirm_Overwrite', 'الملف موجود على الـ disk. هل تريد استبداله؟') }}" @endif>
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
                                    @if ($bladeFileStatus === 'exists') data-confirm="{{ t('dashboard.Blade_Confirm_Overwrite', 'الملف موجود. هل تريد الاستبدال؟') }}" @endif>
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
                        <div class="card-body border-top" style="padding-bottom:0;">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-0">
                                    <i class="ti ti-list-details me-1"></i>{{ t('dashboard.Fields', 'الحقول') }}
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
                            <div class="card-body border-top p-0">
                                <div class="flex flex-col" id="fields-reference-list">
                                    @foreach ($bladeFields as $f)
                                        <div class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 transition"
                                             style="border-bottom: 0.5px solid #f1f5f9;">
                                            <span id="field-dot-{{ $f->field_key }}"
                                                  style="width:7px;height:7px;border-radius:50%;background:#d97706;flex-shrink:0;transition:background .2s;"></span>
                                            @if ($f->field_scope === 'translatable')
                                                <span class="inline-flex items-center justify-center w-4 h-4 rounded text-xs font-bold bg-blue-100 text-blue-600 flex-shrink-0">ت</span>
                                            @else
                                                <span class="inline-flex items-center justify-center w-4 h-4 rounded text-xs font-bold bg-gray-100 text-gray-500 flex-shrink-0">م</span>
                                            @endif
                                            <code class="text-xs font-mono text-indigo-700 flex-1 min-w-0 truncate" dir="ltr">{{ $f->field_key }}</code>
                                            <span class="text-xs text-slate-400 flex-shrink-0">{{ $f->field_type }}</span>
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

    </div>{{-- end Alpine --}}

    <form action="{{ route('dashboard.section_definitions.write_blade', $sectionDefinition) }}"
          method="POST" id="blade-write-form" class="d-none">
        @csrf
        <input type="hidden" name="blade_source" id="blade-write-source">
    </form>

    @push('scripts')
    <script>
    window.__sdEditorData = {
        fields:      @json($bladeFields->toArray()),
        sectionKey:  @json($sectionDefinition->section_key),
        editId:      {{ $sectionDefinition->id }},
        scaffoldDate:'{{ now()->toDateString() }}'
    };
    </script>
    @verbatim
    <script>
    (function () {
        'use strict';

        var fieldsData   = window.__sdEditorData.fields;
        var sectionKey   = window.__sdEditorData.sectionKey;
        var sdEditId     = String(window.__sdEditorData.editId);
        var scaffoldDate = window.__sdEditorData.scaffoldDate;

        var editor         = document.getElementById('blade-source-editor');
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

        /* ── 1. DETECTION ── */
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
            if (!editor) return;
            var code = editor.value;
            var missing = 0;
            fieldsData.forEach(function (f) {
                var used = isFieldUsed(code, f.field_key);
                var dot  = document.getElementById('field-dot-' + f.field_key);
                var btn  = document.getElementById('field-insert-' + f.field_key);
                if (!used) missing++;
                if (dot) {
                    dot.style.background = used ? '#16a34a' : '#d97706';
                    dot.title = used ? 'مستخدم في الكود' : 'غير مستخدم بعد';
                }
                if (btn) {
                    if (used) {
                        btn.innerHTML = '<i class="ti ti-check" style="font-size:10px;color:#16a34a;"></i>';
                        btn.style.background  = '#f0fdf4';
                        btn.style.borderColor = '#bbf7d0';
                        btn.style.color       = '#16a34a';
                        btn.title = 'موجود — انقر للإدراج مجدداً';
                    } else {
                        btn.innerHTML = '<i class="ti ti-plus" style="font-size:10px;"></i>';
                        btn.style.background  = '';
                        btn.style.borderColor = '';
                        btn.style.color       = '';
                        btn.title = 'إدراج عند موضع المؤشر';
                    }
                }
            });
            if (scaffoldLabel && scaffoldBadge) {
                var currentCode = editor.value.trim();
                if (!currentCode) {
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

        /* ── 3. CODE GENERATION ── */
        function generateSnippet(f) {
            var k = f.field_key, type = f.field_type, scope = f.field_scope;
            var src = scope === 'translatable'
                ? "$translatableData['" + k + "'] ?? ''"
                : "$sharedData['" + k + "'] ?? null";
            var lines;
            if (type === 'media' || type === 'image') {
                lines = [
                    '@php $' + k + ' = \\App\\Support\\Sections\\SectionFrontendMediaResolver::resolve($sharedData[\'' + k + '\'] ?? null); @endphp',
                    '@if ($' + k + ')',
                    '    <img src="{{ $' + k + ' }}" alt="">',
                    '@endif'
                ];
            } else if (type === 'boolean' || type === 'toggle') {
                lines = ['@if (' + src + ')', '    {{-- ' + k + ' enabled --}}', '@endif'];
            } else if (type === 'repeater') {
                lines = [
                    '@php $' + k + ' = is_array($sharedData[\'' + k + '\'] ?? null) ? $sharedData[\'' + k + '\'] : []; @endphp',
                    '@foreach ($' + k + ' as $' + k + 'Item)',
                    '    {{-- render item --}}',
                    '@endforeach'
                ];
            } else if (type === 'textarea' || type === 'richtext' || type === 'html') {
                lines = [
                    '@php $' + k + ' = trim((string)(' + src + ')); @endphp',
                    '@if ($' + k + ')',
                    '    <div>{!! $' + k + ' !!}</div>',
                    '@endif'
                ];
            } else {
                lines = [
                    '@php $' + k + ' = trim((string)(' + src + ')); @endphp',
                    '@if ($' + k + ')',
                    '    <p>{{ $' + k + ' }}</p>',
                    '@endif'
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
                    phpLines.push('    $' + k + ' = \\App\\Support\\Sections\\SectionFrontendMediaResolver::resolve($sharedData[\'' + k + '\'] ?? null);');
                    htmlParts.push(comment, '    @if ($' + k + ')', '        <img src="{{ $' + k + ' }}" alt="">', '    @endif');
                } else if (type === 'boolean' || type === 'toggle') {
                    phpLines.push('    $' + k + ' = (bool)($sharedData[\'' + k + '\'] ?? false);');
                    htmlParts.push(comment, '    @if ($' + k + ')', '        {{-- ' + k + ' enabled --}}', '    @endif');
                } else if (type === 'repeater') {
                    phpLines.push('    $' + k + ' = is_array($sharedData[\'' + k + '\'] ?? null) ? $sharedData[\'' + k + '\'] : [];');
                    htmlParts.push(comment, '    @foreach ($' + k + ' as $' + k + 'Item)', '        {{-- render item --}}', '    @endforeach');
                } else {
                    var s = scope === 'translatable' ? "$translatableData['" + k + "'] ?? ''" : "$sharedData['" + k + "'] ?? ''";
                    var isHtml = (type === 'textarea' || type === 'richtext' || type === 'html');
                    phpLines.push('    $' + k + ' = trim((string)(' + s + '));');
                    if (isHtml) {
                        htmlParts.push(comment, '    @if ($' + k + ')', '        <div>{!! $' + k + ' !!}</div>', '    @endif');
                    } else {
                        htmlParts.push(comment, '    @if ($' + k + ')', '        <p>{{ $' + k + ' }}</p>', '    @endif');
                    }
                }
            });
            phpLines.push('@endphp', '');
            htmlParts.push('</section>');
            return phpLines.concat(htmlParts).join('\n');
        }

        /* ── 4. SMART SCAFFOLD ── */
        if (scaffoldBtn && editor) {
            scaffoldBtn.addEventListener('click', function () {
                var code    = editor.value.trim();
                var missing = getMissingFields(editor.value);
                if (!code) {
                    editor.value = fieldsData.length ? generateFullScaffold()
                        : '{{-- لا حقول بعد --}}\n<section class="section-' + sectionKey + '">\n</section>';
                    updateStats(); updateFieldIndicators(); return;
                }
                if (missing.length > 0) {
                    var names = missing.map(function (f) { return '+ ' + f.field_key + ' (' + f.field_type + ')'; }).join('\n');
                    if (!window.confirm('سيتم إضافة ' + missing.length + ' حقل ناقص:\n\n' + names + '\n\nمتابعة؟')) return;
                    var snippets = missing.map(function (f) { return '{{-- ' + f.field_key + ' --}}\n' + generateSnippet(f); }).join('\n\n');
                    editor.value = editor.value.trimEnd() + '\n\n' + snippets;
                    updateStats(); updateFieldIndicators(); return;
                }
                if (window.confirm('كل الحقول موجودة.\n\nاستبدال الكود كاملاً بـ scaffold جديد؟')) {
                    editor.value = generateFullScaffold();
                    updateStats(); updateFieldIndicators();
                }
            });
        }

        /* ── 5. INSERT AT CURSOR ── */
        function insertAtCursor(snippet) {
            if (!editor) return;
            var start  = editor.selectionStart;
            var end    = editor.selectionEnd;
            var before = editor.value.substring(0, start);
            var after  = editor.value.substring(end);
            var pre    = (before.length > 0 && before.slice(-1) !== '\n') ? '\n' : '';
            var post   = (after.length  > 0 && after[0] !== '\n') ? '\n' : '';
            editor.value = before + pre + snippet + post + after;
            var newPos = start + pre.length + snippet.length;
            editor.setSelectionRange(newPos, newPos);
            editor.focus();
            updateStats(); updateFieldIndicators();
        }
        document.querySelectorAll('.field-insert-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                insertAtCursor(generateSnippet({ field_key: btn.dataset.key, field_type: btn.dataset.type, field_scope: btn.dataset.scope }));
            });
        });

        /* ── 6. STATS ── */
        function updateStats() {
            if (!editor || !statsEl) return;
            var lines = editor.value ? editor.value.split('\n').length : 0;
            statsEl.textContent = lines + ' سطر · ' + (editor.value ? editor.value.length : 0) + ' حرف';
        }
        if (editor) {
            editor.addEventListener('input', function () { updateStats(); updateFieldIndicators(); });
            editor.addEventListener('keydown', function (e) {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    var s = editor.selectionStart;
                    editor.value = editor.value.substring(0, s) + '    ' + editor.value.substring(editor.selectionEnd);
                    editor.selectionStart = editor.selectionEnd = s + 4;
                    updateStats();
                }
            });
            updateStats(); updateFieldIndicators();
        }

        /* ── 7. WRITE TO DISK ── */
        function doWrite(btn) {
            if (!writeForm || !writeSource || !editor) return;
            var msg = btn ? btn.dataset.confirm : null;
            if (msg && !window.confirm(msg)) return;
            writeSource.value = editor.value;
            writeForm.submit();
        }
        if (writeBtn)     writeBtn.addEventListener('click',     function () { doWrite(writeBtn); });
        if (writeBtnSide) writeBtnSide.addEventListener('click', function () { doWrite(writeBtnSide); });

        /* ── 8. COPY + CLEAR ── */
        if (copyCodeBtn && editor) {
            copyCodeBtn.addEventListener('click', function () {
                if (!editor.value) return;
                navigator.clipboard.writeText(editor.value).then(function () {
                    var i = copyCodeBtn.querySelector('i');
                    i.className = 'ti ti-check me-1'; copyCodeBtn.style.color = '#16a34a';
                    setTimeout(function () { i.className = 'ti ti-copy me-1'; copyCodeBtn.style.color = ''; }, 1600);
                });
            });
        }
        if (clearCodeBtn && editor) {
            clearCodeBtn.addEventListener('click', function () {
                if (!editor.value.trim() || !window.confirm('مسح كامل الكود؟')) return;
                editor.value = ''; updateStats(); updateFieldIndicators(); editor.focus();
            });
        }
        if (copyPathBtn) {
            copyPathBtn.addEventListener('click', function () {
                navigator.clipboard.writeText(copyPathBtn.dataset.path).then(function () {
                    var i = copyPathBtn.querySelector('i');
                    i.className = 'ti ti-check'; copyPathBtn.style.color = '#16a34a';
                    setTimeout(function () { i.className = 'ti ti-copy text-base'; copyPathBtn.style.color = ''; }, 1600);
                });
            });
        }

        /* ── 9. CTRL+S ── */
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                var tab = localStorage.getItem('sd-edit-' + sdEditId + '-tab') || 'info';
                if (tab === 'blade') { doWrite(writeBtn); }
                else { var form = document.getElementById('section-def-form'); if (form) form.submit(); }
            }
        });

        delete window.__sdEditorData;

    })();
    </script>
    @endverbatim
    @endpush

</x-dashboard-layout>
