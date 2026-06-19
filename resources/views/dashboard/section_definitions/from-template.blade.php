<x-dashboard-layout>

{{-- ══════════════════════════════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════════════════════════════ --}}
<div class="page-header">
    <div class="page-block">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'الرئيسية') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.section_definitions.index') }}">{{ t('dashboard.Section_Definitions', 'تعريفات الأقسام') }}</a></li>
                <li class="breadcrumb-item active">{{ t('dashboard.Create_From_Template', 'إنشاء من قالب') }}</li>
            </ol>
        </nav>
        <div class="page-header-title">
            <h2 class="mb-0">{{ t('dashboard.Create_From_Template', 'إنشاء من قالب') }}</h2>
        </div>
    </div>
</div>

{{-- Flash messages --}}
@if(session('ok'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti ti-circle-check me-1"></i> {{ session('ok') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-triangle me-1"></i> {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Hidden submit form — Definition Only (Fields → Edit Fields) --}}
<form id="tpl-form" method="POST" action="{{ route('dashboard.section_definitions.store_from_template') }}">
    @csrf
    <input type="hidden" name="template_key" id="tpl-key-input">
</form>

{{-- Hidden submit form — Section Package (Definition + Fields + Blade → Edit Page) --}}
<form id="pkg-form" method="POST" action="{{ route('dashboard.section_definitions.package') }}">
    @csrf
    <input type="hidden" name="template_key" id="pkg-key-input">
</form>

@php
    $colorMeta = [
        'indigo' => ['hex' => '#4f46e5', 'light' => '#eef2ff', 'mid' => '#c7d2fe'],
        'violet' => ['hex' => '#7c3aed', 'light' => '#f5f3ff', 'mid' => '#ddd6fe'],
        'emerald'=> ['hex' => '#059669', 'light' => '#ecfdf5', 'mid' => '#a7f3d0'],
        'rose'   => ['hex' => '#e11d48', 'light' => '#fff1f2', 'mid' => '#fecdd3'],
        'amber'  => ['hex' => '#d97706', 'light' => '#fffbeb', 'mid' => '#fde68a'],
        'cyan'   => ['hex' => '#0891b2', 'light' => '#ecfeff', 'mid' => '#a5f3fc'],
    ];

    $compColors = [
        'intro'        => 'indigo',
        'description'  => 'slate',
        'cta'          => 'violet',
        'image'        => 'emerald',
        'features'     => 'rose',
        'highlight'    => 'amber',
        'faq'          => 'amber',
        'testimonials' => 'cyan',
        'seo'          => 'slate',
    ];

    $compColorMeta = [
        'indigo' => ['bg' => '#eef2ff', 'text' => '#4338ca'],
        'violet' => ['bg' => '#f5f3ff', 'text' => '#6d28d9'],
        'emerald'=> ['bg' => '#ecfdf5', 'text' => '#047857'],
        'rose'   => ['bg' => '#fff1f2', 'text' => '#be123c'],
        'amber'  => ['bg' => '#fffbeb', 'text' => '#b45309'],
        'cyan'   => ['bg' => '#ecfeff', 'text' => '#0e7490'],
        'slate'  => ['bg' => '#f1f5f9', 'text' => '#475569'],
    ];

    $allComponents = \App\Support\Sections\ComponentLibrary::all();

    $totalTemplates = count($templates);
    // Count only templates whose section_key already exists (not all DB definitions)
    $existingCount = collect($templates)->filter(
        fn($tpl) => $existingKeys->has($tpl['definition']['section_key'] ?? '')
    )->count();
    $availableCount = $totalTemplates - $existingCount;
@endphp

{{-- ══════════════════════════════════════════════════════════════════
     HERO BANNER
══════════════════════════════════════════════════════════════════ --}}
<div class="card mb-4" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none; overflow: hidden; position: relative;">
    <div class="card-body py-4 px-4" style="position: relative; z-index: 1;">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge" style="background: rgba(255,255,255,.2); color: #fff; font-size: 11px; border-radius: 20px; padding: 4px 10px;">
                        <i class="ti ti-bolt me-1"></i>Section Templates
                    </span>
                </div>
                <h3 class="text-white mb-1 fw-bold">اختر قالباً وابدأ في ثوانٍ</h3>
                <p class="mb-0" style="color: rgba(255,255,255,.75); font-size: 14px;">
                    كل قالب ينشئ تعريف القسم + جميع حقوله تلقائياً — دون كتابة سطر واحد
                </p>
            </div>
            <div style="display:flex;flex-direction:row;align-items:center;gap:0;flex-shrink:0;background:rgba(255,255,255,.12);border-radius:12px;overflow:hidden;">
                <div style="text-align:center;padding:10px 20px;">
                    <div style="font-size:26px;font-weight:700;color:#fff;line-height:1;">{{ $totalTemplates }}</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.7);margin-top:2px;">قوالب</div>
                </div>
                <div style="width:1px;background:rgba(255,255,255,.2);align-self:stretch;"></div>
                <div style="text-align:center;padding:10px 20px;">
                    <div style="font-size:26px;font-weight:700;color:#fff;line-height:1;">{{ $availableCount }}</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.7);margin-top:2px;">متاح</div>
                </div>
                <div style="width:1px;background:rgba(255,255,255,.2);align-self:stretch;"></div>
                <div style="text-align:center;padding:10px 20px;">
                    <div style="font-size:26px;font-weight:700;color:#fff;line-height:1;">{{ $existingCount }}</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.7);margin-top:2px;">مُنشأ</div>
                </div>
            </div>
        </div>
    </div>
    {{-- Decorative circles --}}
    <div style="position:absolute;top:-40px;left:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06);z-index:0;"></div>
    <div style="position:absolute;bottom:-60px;left:120px;width:250px;height:250px;border-radius:50%;background:rgba(255,255,255,.04);z-index:0;"></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     TEMPLATE GRID
══════════════════════════════════════════════════════════════════ --}}
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">

    @foreach($templates as $tplKey => $tpl)
        @php
            $color  = $tpl['color'] ?? 'indigo';
            $cm     = $colorMeta[$color] ?? $colorMeta['indigo'];
            $isUsed = $existingKeys->has($tpl['definition']['section_key'] ?? '');

            $resolvedFields = \App\Support\Sections\SectionTemplateLibrary::resolveTemplateFields($tplKey);
            $fieldCount     = count($resolvedFields);
            $componentKeys  = $tpl['components']   ?? [];
            $extraFields    = $tpl['extra_fields']  ?? [];

            // Count repeater fields
            $repeaterFields = array_filter($resolvedFields, fn($f) => ($f['field_type'] ?? '') === 'repeater');
        @endphp

        <div class="tpl-card {{ $isUsed ? 'tpl-card--used' : '' }}"
             style="border-radius: 14px; border: 1.5px solid {{ $isUsed ? '#e2e8f0' : $cm['mid'] }}; overflow: hidden; background: #fff; display: flex; flex-direction: column; transition: box-shadow .2s, transform .15s;"
             @if(!$isUsed)
             onmouseenter="this.style.boxShadow='0 8px 30px rgba(0,0,0,.12)';this.style.transform='translateY(-3px)'"
             onmouseleave="this.style.boxShadow='';this.style.transform=''"
             @endif>

            {{-- ── Colored header zone ─────────────────────────── --}}
            <div style="background: {{ $isUsed ? '#f8fafc' : $cm['light'] }}; padding: 16px 16px 14px; border-bottom: 1px solid {{ $isUsed ? '#f1f5f9' : $cm['mid'] }};">
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    {{-- Icon --}}
                    <div style="flex-shrink:0; width:42px; height:42px; border-radius:11px; background:{{ $isUsed ? '#e2e8f0' : '#fff' }}; display:flex; align-items:center; justify-content:center; box-shadow:{{ $isUsed ? 'none' : '0 1px 6px rgba(0,0,0,.08)' }};">
                        <i class="ti {{ $tpl['icon'] ?? 'ti-layout' }}" style="font-size:20px; color:{{ $isUsed ? '#94a3b8' : $cm['hex'] }};"></i>
                    </div>
                    {{-- Title + badge --}}
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:3px;">
                            <span style="font-size:15px; font-weight:700; color:{{ $isUsed ? '#94a3b8' : '#1e293b' }}; letter-spacing:-.2px;">
                                {{ $tpl['label'] }}
                            </span>
                            @if($isUsed)
                                <span style="font-size:10px; font-weight:700; background:#dcfce7; color:#16a34a; padding:2px 8px; border-radius:20px;">
                                    ✓ مُنشأ
                                </span>
                            @endif
                        </div>
                        <p style="margin:0; font-size:12px; color:{{ $isUsed ? '#cbd5e1' : '#64748b' }}; line-height:1.4;" title="{{ $tpl['description'] ?? '' }}">
                            {{ Str::limit($tpl['description'] ?? '', 60) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- ── Body: Components + meta ─────────────────────── --}}
            <div class="px-3 py-2 flex-grow-1 d-flex flex-column gap-2">

                {{-- Components chips --}}
                @if(!empty($componentKeys))
                    <div>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($componentKeys as $ck)
                                @php
                                    $comp  = $allComponents[$ck] ?? null;
                                    $ccol  = $compColors[$ck] ?? 'slate';
                                    $ccm   = $compColorMeta[$ccol] ?? $compColorMeta['slate'];
                                    $cIcon = $comp['icon'] ?? 'ti-puzzle';
                                    $cName = $comp['name'] ?? $ck;
                                    $cFlds = count($comp['fields'] ?? []);
                                @endphp
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:{{ $isUsed ? '#f8fafc' : $ccm['bg'] }};color:{{ $isUsed ? '#94a3b8' : $ccm['text'] }};"
                                      title="{{ $comp['description'] ?? $cName }}">
                                    <i class="ti {{ $cIcon }}" style="font-size:10px;"></i>
                                    {{ $cName }}
                                </span>
                            @endforeach
                            @if(!empty($extraFields))
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:#f8fafc;color:#94a3b8;border:1px dashed #e2e8f0;">
                                    +{{ count($extraFields) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Repeater sub-field previews --}}
                @foreach($repeaterFields as $rField)
                    @if(!empty($rField['schema']['item_schema']))
                        <div style="font-size:11px;padding:5px 8px;background:#fafafa;border-radius:7px;border:1px solid #f1f5f9;color:#64748b;">
                            <i class="ti ti-refresh" style="font-size:10px;color:#a5b4fc;margin-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}:4px;"></i>
                            <span style="font-weight:600;">{{ $rField['field_key'] }}:</span>
                            {{ implode(' · ', array_column($rField['schema']['item_schema'], 'key')) }}
                        </div>
                    @endif
                @endforeach

                {{-- Stats row --}}
                <div class="d-flex align-items-center justify-content-between mt-auto pt-1">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:11px;background:{{ $isUsed ? '#f1f5f9' : $cm['light'] }};color:{{ $isUsed ? '#94a3b8' : $cm['hex'] }};padding:3px 9px;border-radius:20px;font-weight:700;">
                            {{ $fieldCount }} حقل
                        </span>
                        <span style="font-size:11px;color:#cbd5e1;background:#f8fafc;padding:3px 8px;border-radius:20px;">
                            {{ $tpl['category'] ?? '' }}
                        </span>
                    </div>
                    <code style="font-size:10px;color:#94a3b8;background:#f8fafc;padding:2px 6px;border-radius:5px;">
                        {{ $tpl['definition']['section_key'] ?? '' }}
                    </code>
                </div>
            </div>

            {{-- ── Footer: Actions ─────────────────────────────── --}}
            <div class="px-3 pb-3 pt-2 d-flex flex-column gap-2">
                @if($isUsed)
                    <button type="button" class="btn w-100" disabled
                            style="background:#f8fafc;color:#94a3b8;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;padding:9px;">
                        <i class="ti ti-check me-1"></i>{{ t('dashboard.Template_Already_Created', 'موجود بالفعل') }}
                    </button>
                @else
                    {{-- Primary: Create Package (Definition + Fields + Blade File) --}}
                    <button type="button"
                            class="btn w-100 pkg-btn"
                            style="background:{{ $cm['hex'] }};color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;padding:9px;transition:opacity .15s;"
                            onmouseenter="this.style.opacity='.88'"
                            onmouseleave="this.style.opacity='1'"
                            onclick="createPackage('{{ $tplKey }}', '{{ addslashes($tpl['label']) }}', {{ $fieldCount }})">
                        🚀 {{ t('dashboard.Create_Section_Package', 'إنشاء حزمة السكشن') }}
                    </button>
                    {{-- Secondary: Definition + Fields only (old behaviour) --}}
                    <button type="button"
                            class="btn w-100 tpl-btn"
                            style="background:transparent;color:{{ $cm['hex'] }};border:1.5px solid {{ $cm['mid'] }};border-radius:10px;font-size:12px;font-weight:600;padding:7px;transition:opacity .15s;"
                            onmouseenter="this.style.opacity='.75'"
                            onmouseleave="this.style.opacity='1'"
                            onclick="applyTemplate('{{ $tplKey }}', '{{ addslashes($tpl['label']) }}', {{ $fieldCount }})">
                        <i class="ti ti-bolt me-1"></i>
                        {{ t('dashboard.Create_Definition_Only', 'تعريف + حقول فقط') }}
                    </button>
                @endif
            </div>

        </div>
    @endforeach

</div>

{{-- ── Bottom links ────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mt-4">
    <a href="{{ route('dashboard.section_definitions.index') }}" class="btn btn-light">
        <i class="ti ti-arrow-right me-1"></i>
        {{ t('dashboard.Back_To_Definition', 'العودة لقائمة التعريفات') }}
    </a>
    <a href="{{ route('dashboard.section_definitions.create') }}" class="btn btn-outline-primary">
        <i class="ti ti-pencil me-1"></i>
        {{ t('dashboard.Create_Section_Definition', 'إنشاء تعريف يدوياً') }}
    </a>
</div>

@push('scripts')
<script>
/**
 * Definition + Fields only (old behaviour).
 * Redirects to Fields management page after creation.
 */
function applyTemplate(key, label, fieldCount) {
    if (!confirm('إنشاء "' + label + '"؟\nسيتم إنشاء ' + fieldCount + ' حقل تلقائياً.\n\nسيتم توجيهك إلى صفحة إدارة الحقول.')) return;
    document.getElementById('tpl-key-input').value = key;
    document.getElementById('tpl-form').submit();
}

/**
 * Full Section Package (Definition + Fields + Generated Blade + Written File).
 * Redirects to the Edit page (Blade tab ready) after creation.
 */
function createPackage(key, label, fieldCount) {
    if (!confirm(
        '🚀 إنشاء حزمة سكشن كاملة: "' + label + '"؟\n\n' +
        'سيتم تلقائياً:\n' +
        '  ✓ إنشاء تعريف السكشن\n' +
        '  ✓ إنشاء ' + fieldCount + ' حقل\n' +
        '  ✓ توليد كود Blade من الحقول\n' +
        '  ✓ كتابة الملف على الـ disk\n\n' +
        'سيتم توجيهك إلى صفحة التعديل مباشرةً.'
    )) return;

    document.getElementById('pkg-key-input').value = key;
    document.getElementById('pkg-form').submit();
}
</script>
@endpush

</x-dashboard-layout>
