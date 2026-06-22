<x-dashboard-layout>
    <x-slot name="title">{{ t('dashboard.Brand_Colors', 'Brand Colors') }}</x-slot>

    @php
        // Core brand fields: [key, label, description, group]
        $coreFields = [
            ['key' => 'primary',   'label' => t('dashboard.Brand_Primary_Color',   'Primary Color'),   'desc' => t('dashboard.Brand_Primary_Color_Hint',   'Main brand color — used for headings, backgrounds, and primary elements.')],
            ['key' => 'secondary', 'label' => t('dashboard.Brand_Secondary_Color', 'Secondary Color'), 'desc' => t('dashboard.Brand_Secondary_Color_Hint', 'Accent color — used for buttons, highlights, and call-to-action elements.')],
            ['key' => 'muted',     'label' => t('dashboard.Brand_Muted_Color',     'Muted Color'),     'desc' => t('dashboard.Brand_Muted_Color_Hint',     'Light background tint — used for section backgrounds and cards.')],
            ['key' => 'body',      'label' => t('dashboard.Brand_Body_Color',      'Body Color'),      'desc' => t('dashboard.Brand_Body_Color_Hint',      'Default text color for body copy and secondary content.')],
        ];

        $customFields = [
            ['key' => 'custom_1', 'label' => t('dashboard.Brand_Custom_Color_1', 'Custom Color 1')],
            ['key' => 'custom_2', 'label' => t('dashboard.Brand_Custom_Color_2', 'Custom Color 2')],
            ['key' => 'custom_3', 'label' => t('dashboard.Brand_Custom_Color_3', 'Custom Color 3')],
            ['key' => 'custom_4', 'label' => t('dashboard.Brand_Custom_Color_4', 'Custom Color 4')],
            ['key' => 'custom_5', 'label' => t('dashboard.Brand_Custom_Color_5', 'Custom Color 5')],
        ];

        // Get current value for each field (prefer old() after validation error)
        $getValue = function (string $key) use ($brandSettings): string {
            $oldVal = old($key);
            if ($oldVal !== null && $oldVal !== '') {
                return strtolower(trim((string) $oldVal));
            }
            return match ($key) {
                'primary'   => $brandSettings->primary(),
                'secondary' => $brandSettings->secondary(),
                'muted'     => $brandSettings->muted(),
                'body'      => $brandSettings->body(),
                'custom_1'  => $brandSettings->custom1(),
                'custom_2'  => $brandSettings->custom2(),
                'custom_3'  => $brandSettings->custom3(),
                'custom_4'  => $brandSettings->custom4(),
                'custom_5'  => $brandSettings->custom5(),
                default     => '',
            };
        };

        $cssFileUrl = \App\Support\AdminBrand\AdminBrandCssGenerator::publicUrl();
    @endphp

    {{-- page-header --}}
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'الرئيسية') }}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ t('dashboard.Brand_Colors', 'ألوان البراند') }}</li>
                        </ol>
                    </nav>
                    <div class="page-header-title">
                        <h2 class="mb-0">{{ t('dashboard.Brand_Colors', 'ألوان البراند') }}</h2>
                        <p class="text-muted small mb-0 mt-1">{{ t('dashboard.Brand_Colors_Desc', 'تحكم في ألوان الواجهة التسويقية الرئيسية للمنصة.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- flash messages --}}
    @if (session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-circle-check me-2"></i>{{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-triangle me-2"></i>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('dashboard.appearance.brand.settings') }}" method="POST">
        @csrf
        <div class="grid grid-cols-12 gap-6">

            {{-- ── Main Column ─────────────────────────────────────── --}}
            <div class="col-span-12 xl:col-span-8">

                {{-- ── Section 1: Core Brand Colors ──────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill"
                              style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">١</span>
                        <h5 class="mb-0">{{ t('dashboard.Core_Brand_Colors', 'ألوان البراند الأساسية') }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-4">
                            {{ t('dashboard.Core_Brand_Colors_Hint', 'هذه الألوان الأربعة تُشغّل كل utility classes في الواجهة التسويقية مثل bg-purple-brand و text-red-brand.') }}
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($coreFields as $field)
                                @php
                                    $val     = $getValue($field['key']);
                                    $inputId = 'brand_color_' . $field['key'];
                                @endphp
                                <div>
                                    <label for="{{ $inputId }}" class="form-label fw-semibold mb-1">
                                        {{ $field['label'] }}
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input
                                            id="{{ $inputId }}_picker"
                                            type="color"
                                            class="form-control form-control-color p-1 h-10 w-14 flex-shrink-0"
                                            value="{{ $val ?: '#000000' }}"
                                            data-brand-picker="{{ $field['key'] }}"
                                        >
                                        <input
                                            id="{{ $inputId }}"
                                            name="{{ $field['key'] }}"
                                            type="text"
                                            class="form-control font-mono uppercase"
                                            value="{{ strtoupper($val) }}"
                                            pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$"
                                            maxlength="7"
                                            placeholder="#000000"
                                            dir="ltr"
                                            data-brand-hex="{{ $field['key'] }}"
                                        >
                                    </div>
                                    @if (!empty($field['desc']))
                                        <p class="text-xs text-muted mt-1 mb-0">{{ $field['desc'] }}</p>
                                    @endif
                                    @error($field['key'])
                                        <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ── Section 2: Custom Color Slots ──────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill"
                              style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">٢</span>
                        <h5 class="mb-0">{{ t('dashboard.Custom_Color_Slots', 'مساحات الألوان المخصصة') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info d-flex gap-2 py-2 px-3 mb-4" role="alert">
                            <i class="ti ti-info-circle flex-shrink-0 mt-1"></i>
                            <div class="small">
                                {{ t('dashboard.Custom_Colors_Reserved_Hint', 'هذه الألوان متاحة في Page Builder كخيارات background_token و text_token. بعد الحفظ، ستظهر تلقائياً في قوائم ألوان السكشنات.') }}
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($customFields as $field)
                                @php
                                    $val     = $getValue($field['key']);
                                    $inputId = 'brand_color_' . $field['key'];
                                @endphp
                                <div>
                                    <label for="{{ $inputId }}" class="form-label mb-1">
                                        {{ $field['label'] }}
                                        <span class="badge bg-secondary-subtle text-secondary ms-1 px-2 py-0.5 text-xs">
                                            --admin-color-custom-{{ substr($field['key'], -1) }}
                                        </span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input
                                            id="{{ $inputId }}_picker"
                                            type="color"
                                            class="form-control form-control-color p-1 h-10 w-14 flex-shrink-0"
                                            value="{{ $val ?: '#888888' }}"
                                            data-brand-picker="{{ $field['key'] }}"
                                        >
                                        <input
                                            id="{{ $inputId }}"
                                            name="{{ $field['key'] }}"
                                            type="text"
                                            class="form-control font-mono uppercase"
                                            value="{{ strtoupper($val) }}"
                                            pattern="^(#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}))?$"
                                            maxlength="7"
                                            placeholder="{{ t('dashboard.Optional', 'اختياري') }}"
                                            dir="ltr"
                                            data-brand-hex="{{ $field['key'] }}"
                                        >
                                    </div>
                                    @error($field['key'])
                                        <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>{{-- /col-span-8 --}}

            {{-- ── Sidebar ──────────────────────────────────────────── --}}
            <div class="col-span-12 xl:col-span-4">
                <div class="card sticky top-6">
                    <div class="card-header">
                        <h5 class="mb-0">{{ t('dashboard.Actions', 'الإجراءات') }}</h5>
                    </div>
                    <div class="card-body d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-device-floppy me-1"></i>
                            {{ t('dashboard.Save_Brand_Colors', 'حفظ ألوان البراند') }}
                        </button>
                        <a href="{{ route('dashboard.appearance.header') }}" class="btn btn-light w-100">
                            {{ t('dashboard.Cancel', 'إلغاء') }}
                        </a>
                    </div>
                    <div class="card-footer">
                        <p class="text-muted small mb-2 fw-semibold">{{ t('dashboard.CSS_File_Status', 'حالة ملف CSS') }}</p>
                        @if ($cssFileUrl)
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-success-subtle text-success px-2 py-1">
                                    <i class="ti ti-check me-1"></i>{{ t('dashboard.CSS_File_Active', 'الملف نشط') }}
                                </span>
                            </div>
                            <p class="text-muted" style="font-size:0.72rem;word-break:break-all;font-family:monospace;">
                                /storage/admin-theme/admin-brand.css
                            </p>
                        @else
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning-subtle text-warning px-2 py-1">
                                    <i class="ti ti-alert-triangle me-1"></i>{{ t('dashboard.CSS_File_Missing', 'الملف غير موجود') }}
                                </span>
                            </div>
                            <p class="text-muted small mt-1 mb-0">
                                {{ t('dashboard.CSS_File_Missing_Hint', 'سيُنشأ الملف عند الحفظ أو تلقائياً عند تشغيل التطبيق.') }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Help card --}}
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">{{ t('dashboard.How_It_Works', 'كيف يعمل النظام؟') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-3 small text-muted">
                            <div class="d-flex gap-2">
                                <i class="ti ti-number-1 text-primary flex-shrink-0 mt-0.5"></i>
                                <span>{{ t('dashboard.Brand_Help_1', 'ألوانك تُحفظ في قاعدة البيانات') }}</span>
                            </div>
                            <div class="d-flex gap-2">
                                <i class="ti ti-number-2 text-primary flex-shrink-0 mt-0.5"></i>
                                <span>{{ t('dashboard.Brand_Help_2', 'يُولَّد ملف admin-brand.css بـ CSS variables على :root') }}</span>
                            </div>
                            <div class="d-flex gap-2">
                                <i class="ti ti-number-3 text-primary flex-shrink-0 mt-0.5"></i>
                                <span>{{ t('dashboard.Brand_Help_3', 'جميع classes مثل bg-purple-brand و text-red-brand تتغير تلقائياً') }}</span>
                            </div>
                            <div class="d-flex gap-2">
                                <i class="ti ti-number-4 text-primary flex-shrink-0 mt-0.5"></i>
                                <span>{{ t('dashboard.Brand_Help_4', 'إذا حُذف الملف، الموقع يعود للألوان الافتراضية تلقائياً') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>{{-- /sidebar --}}

        </div>{{-- /grid --}}
    </form>

    @push('scripts')
    <script>
    (function () {
        'use strict';

        // Sync color picker ↔ hex text input bidirectionally.
        // Each pair is identified by data-brand-picker="{key}" and data-brand-hex="{key}".
        document.querySelectorAll('[data-brand-picker]').forEach(function (picker) {
            var key     = picker.getAttribute('data-brand-picker');
            var hexInput = document.querySelector('[data-brand-hex="' + key + '"]');
            if (!hexInput) return;

            // Color picker → hex text
            picker.addEventListener('input', function () {
                hexInput.value = picker.value.toUpperCase();
            });

            // Hex text → color picker (validate first)
            hexInput.addEventListener('input', function () {
                var v = hexInput.value.trim();
                if (/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(v)) {
                    picker.value = v;
                }
            });

            // Normalize hex on blur: uppercase + ensure # prefix
            hexInput.addEventListener('blur', function () {
                var v = hexInput.value.trim();
                if (v !== '' && !v.startsWith('#')) {
                    v = '#' + v;
                }
                hexInput.value = v.toUpperCase();
                if (/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(v)) {
                    picker.value = v;
                }
            });
        });
    })();
    </script>
    @endpush

</x-dashboard-layout>
