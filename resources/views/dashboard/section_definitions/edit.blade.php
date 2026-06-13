<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'الرئيسية') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.section_definitions.index') }}">{{ t('dashboard.Section_Definitions', 'تعريفات الأقسام') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Edit_Definition', 'تعديل التعريف') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Edit_Section_Definition', 'تعديل تعريف القسم') }}</h2>
            </div>
        </div>
    </div>

    @if (session('ok'))
        <div class="alert alert-success mb-4">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <form action="{{ route('dashboard.section_definitions.update', $sectionDefinition) }}" method="POST" id="section-def-form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-12 gap-6">

            {{-- 1 -- Main form --}}
            <div class="col-span-12 xl:col-span-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-1">{{ t('dashboard.Definition_Information', 'معلومات التعريف') }}</h5>
                        <p class="mb-0 text-sm text-slate-500">
                            {{ t('dashboard.Def_Workflow_Desc', 'اختر Dynamic لوضع المحرر، أدخل Category و Template Key مستقرَّين، احفظ التعريف، ثم انتقل لتعريفات الحقول.') }}
                        </p>
                    </div>
                    <div class="card-body">
                        @include('dashboard.section_definitions.form')
                    </div>
                </div>

                {{-- 2 -- Blade Template Editor --}}
                <div class="card mt-6" id="blade-editor-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">
                                <i class="ti ti-code me-1 text-slate-500"></i>
                                {{ t('dashboard.Blade_Template', 'قالب Blade') }}
                            </h5>
                            <p class="mb-0 text-sm text-slate-500">
                                {{ t('dashboard.Blade_Editor_Hint', 'اكتب كود Blade هنا ثم اضغط "حفظ وكتابة الملف" لنشره على الـ disk.') }}
                            </p>
                        </div>
                        <div class="ms-3 flex-shrink-0">
                            @if ($bladeFileStatus === 'exists')
                                <span class="badge bg-success-subtle text-success border border-success">
                                    <i class="ti ti-check me-1"></i>
                                    {{ t('dashboard.Blade_File_Exists', 'ملف موجود') }}
                                </span>
                            @elseif ($bladeFileStatus === 'external')
                                <span class="badge bg-warning-subtle text-warning border border-warning">
                                    <i class="ti ti-alert-triangle me-1"></i>
                                    {{ t('dashboard.Blade_File_External', 'كُتب خارجياً') }}
                                </span>
                            @elseif ($bladeFileStatus === 'invalid')
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary">
                                    <i class="ti ti-ban me-1"></i>
                                    {{ t('dashboard.Blade_Invalid_Key', 'مفتاح غير صالح') }}
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger">
                                    <i class="ti ti-file-x me-1"></i>
                                    {{ t('dashboard.Blade_File_Missing', 'لم يُكتب بعد') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="mb-3 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                            <span class="text-slate-500">{{ t('dashboard.Blade_Expected_Path', 'المسار المتوقع:') }}</span>
                            <code class="ms-2 text-slate-800" dir="ltr">{{ $bladeExpectedPath }}</code>
                            @if ($sectionDefinition->blade_written_at)
                                <span class="ms-3 text-slate-400 text-xs">
                                    {{ t('dashboard.Blade_File_Last_Written', 'آخر كتابة:') }}
                                    {{ $sectionDefinition->blade_written_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>

                        <div>
                            <label class="form-label d-flex align-items-center justify-content-between">
                                <span>{{ t('dashboard.Blade_Source_Code', 'كود Blade') }}</span>
                                <button type="button" class="btn btn-sm btn-light" id="blade-scaffold-btn"
                                    data-definition-id="{{ $sectionDefinition->id }}"
                                    title="{{ t('dashboard.Blade_Scaffold_Hint', 'انشئ stub من الحقول') }}">
                                    <i class="ti ti-wand me-1"></i>
                                    {{ t('dashboard.Blade_Scaffold', 'Scaffold من الحقول') }}
                                </button>
                            </label>
                            <textarea
                                id="blade-source-editor"
                                name="blade_source"
                                class="form-control font-mono"
                                dir="ltr"
                                rows="20"
                                spellcheck="false"
                                style="font-size:13px;line-height:1.6;tab-size:4;"
                            >{{ old('blade_source', $sectionDefinition->blade_source) }}</textarea>
                        </div>
                    </div>

                    <div class="card-footer d-flex align-items-center justify-content-between gap-3">
                        <div class="text-sm text-slate-500">
                            {{ t('dashboard.Blade_Scaffold_Hint', 'اضغط Scaffold لإنشاء stub تلقائياً من الحقول.') }}
                        </div>
                        <button type="button" class="btn btn-primary" id="blade-write-btn"
                            @if ($bladeFileStatus === 'exists') data-confirm="{{ t('dashboard.Blade_Confirm_Overwrite', 'الملف موجود. هل تريد استبداله؟') }}" @endif>
                            <i class="ti ti-device-floppy me-1"></i>
                            {{ t('dashboard.Blade_Write_File', 'حفظ وكتابة الملف') }}
                        </button>
                    </div>
                </div>

            </div>

            {{-- 3 -- Sidebar --}}
            <div class="col-span-12 xl:col-span-4">
                <div class="card sticky top-6">
                    <div class="card-header">
                        <h5 class="mb-0">{{ t('dashboard.Actions', 'الإجراءات') }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-sm text-slate-500">
                            {{ t('dashboard.Def_Sidebar_Hint', 'بعد الحفظ يمكنك إدارة الحقول لضبط المخطط الديناميكي للقسم.') }}
                        </p>
                    </div>
                    <div class="card-footer d-grid gap-2">
                        <button type="submit" form="section-def-form" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>
                            {{ t('dashboard.Update_Definition', 'حفظ التعديلات') }}
                        </button>
                        <button type="submit" name="after_save" value="fields" form="section-def-form" class="btn btn-light">
                            <i class="ti ti-layout-list me-1"></i>
                            {{ t('dashboard.Update_And_Manage_Fields', 'حفظ وإدارة الحقول') }}
                        </button>
                        <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}" class="btn btn-light">
                            <i class="ti ti-list-details me-1"></i>
                            {{ t('dashboard.Manage_Fields', 'إدارة الحقول') }}
                        </a>
                        <a href="{{ route('dashboard.section_definitions.index') }}" class="btn btn-light">
                            {{ t('dashboard.Cancel', 'إلغاء') }}
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </form>

{{-- Blade write form MUST be outside the main form (nested forms are invalid HTML) --}}
<form action="{{ route('dashboard.section_definitions.write_blade', $sectionDefinition) }}" method="POST" id="blade-write-form" class="d-none">
    @csrf
    <input type="hidden" name="blade_source" id="blade-write-source">
</form>

<script>
(function () {
    'use strict';
    var editor      = document.getElementById('blade-source-editor');
    var writeBtn    = document.getElementById('blade-write-btn');
    var writeForm   = document.getElementById('blade-write-form');
    var writeSource = document.getElementById('blade-write-source');
    var scaffoldBtn = document.getElementById('blade-scaffold-btn');
    var fieldsData  = @json($sectionDefinition->fields()->orderBy('sort_order')->orderBy('id')->get(['field_key', 'field_type', 'field_scope'])->toArray());

    if (writeBtn && writeForm && writeSource && editor) {
        writeBtn.addEventListener('click', function () {
            var msg = writeBtn.dataset.confirm;
            if (msg && !window.confirm(msg)) { return; }
            writeSource.value = editor.value;
            writeForm.submit();
        });
    }

    if (scaffoldBtn && editor) {
        scaffoldBtn.addEventListener('click', function () {
            if (!fieldsData || fieldsData.length === 0) {
                if (editor.value.trim() === '') {
                    editor.value = '@php\n    // No fields yet\n@endphp\n\n<section class="">\n    {{-- TODO --}}\n</section>';
                }
                return;
            }
            var phpLines  = ['@php', '    // auto-generated scaffold'];
            var htmlParts = ['<section class="">'];
            fieldsData.forEach(function (f) {
                var k = f.field_key, t = f.field_type;
                if (t === 'media' || t === 'image') {
                    phpLines.push('    $' + k + ' = \\App\\Support\\Sections\\SectionFrontendMediaResolver::resolve($data[\'' + k + '\'] ?? null);');
                    htmlParts.push('    @if ($' + k + ')', '        <img src="{{ $' + k + ' }}" alt="">', '    @endif');
                } else if (t === 'boolean' || t === 'toggle') {
                    phpLines.push('    $' + k + ' = (bool) ($data[\'' + k + '\'] ?? false);');
                    htmlParts.push('    @if ($' + k + ')', '        {{-- ' + k + ' enabled --}}', '    @endif');
                } else if (t === 'repeater') {
                    phpLines.push('    $' + k + ' = is_array($data[\'' + k + '\'] ?? null) ? $data[\'' + k + '\'] : [];');
                    htmlParts.push('    @foreach ($' + k + ' as $item)', '        {{-- render $item --}}', '    @endforeach');
                } else if (t === 'textarea' || t === 'richtext' || t === 'html') {
                    phpLines.push('    $' + k + ' = trim((string) ($data[\'' + k + '\'] ?? \'\'));');
                    htmlParts.push('    @if ($' + k + ')', '        <div>{!! $' + k + ' !!}</div>', '    @endif');
                } else {
                    phpLines.push('    $' + k + ' = trim((string) ($data[\'' + k + '\'] ?? \'\'));');
                    htmlParts.push('    @if ($' + k + ')', '        <p>{{ $' + k + ' }}</p>', '    @endif');
                }
            });
            phpLines.push('@endphp', '');
            htmlParts.push('</section>');
            editor.value = phpLines.concat(htmlParts).join('\n');
        });
    }
})();
</script>
</x-dashboard-layout>
