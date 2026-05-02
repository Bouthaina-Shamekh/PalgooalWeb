<x-dashboard-layout>
    {{-- Breadcrumb --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.subscriptions.index') }}">الاشتراكات</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.subscriptions.edit', $subscription) }}">
                        {{ $subscription->username ?? '#' . $subscription->id }}
                    </a>
                </li>
                <li class="breadcrumb-item" aria-current="page">إعدادات الثيم</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">إعدادات ثيم الموقع</h2>
            </div>
        </div>
    </div>
    {{-- /Breadcrumb --}}

    @if (session('success'))
        <div class="alert alert-success mb-4" role="alert">
            {{ session('success') }}
            @if ($cssUrl)
                &mdash;
                <a href="{{ $cssUrl }}" target="_blank" class="alert-link font-mono text-xs">
                    عرض ملف CSS
                </a>
            @endif
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-4" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="theme-form"
          action="{{ route('dashboard.subscriptions.theme.update', $subscription) }}"
          method="POST">
        @csrf

        <div class="grid grid-cols-12 gap-6">

            {{-- ============================================================
                 COLORS
            ============================================================ --}}
            <div class="col-span-12 lg:col-span-6">
                <div class="card h-full">
                    <div class="card-header">
                        <h5 class="mb-0 flex items-center gap-2">
                            <i class="ti ti-palette text-lg"></i>
                            الألوان
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $colorFields = [
                                'color_primary'   => ['اللون الرئيسي',   'Primary',   $theme->colorPrimary],
                                'color_secondary' => ['اللون الثانوي',   'Secondary', $theme->colorSecondary],
                                'color_surface'   => ['خلفية السطح',    'Surface',   $theme->colorSurface],
                                'color_muted'     => ['خلفية خفيفة',    'Muted',     $theme->colorMuted],
                                'color_heading'   => ['لون العناوين',   'Heading',   $theme->colorHeading],
                                'color_body'      => ['لون النص',       'Body',      $theme->colorBody],
                                'color_border'    => ['لون الحدود',     'Border',    $theme->colorBorder],
                            ];
                        @endphp

                        <div class="grid grid-cols-1 gap-4">
                            @foreach ($colorFields as $key => [$labelAr, $labelEn, $value])
                                @php $inputId = 'theme_color_' . $key; @endphp
                                <div>
                                    <label for="{{ $inputId }}" class="form-label mb-1">
                                        {{ $labelAr }}
                                        <span class="text-xs text-muted ms-1">{{ $labelEn }}</span>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            id="{{ $inputId }}"
                                            type="color"
                                            class="form-control form-control-color p-1 h-10 w-14 cursor-pointer"
                                            value="{{ old($key, $value) }}"
                                            data-theme-color-picker="{{ $key }}"
                                        >
                                        <input
                                            id="{{ $inputId }}_hex"
                                            name="{{ $key }}"
                                            type="text"
                                            class="form-control font-mono uppercase"
                                            value="{{ strtoupper(old($key, $value)) }}"
                                            pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$"
                                            maxlength="7"
                                            placeholder="#000000"
                                            data-theme-hex-input="{{ $key }}"
                                        >
                                    </div>
                                    @error($key)
                                        <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 TYPOGRAPHY + SHAPE + BUTTONS  (right column)
            ============================================================ --}}
            <div class="col-span-12 lg:col-span-6 flex flex-col gap-6">

                {{-- Typography --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 flex items-center gap-2">
                            <i class="ti ti-typography text-lg"></i>
                            الخطوط والأحجام
                        </h5>
                    </div>
                    <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <label class="form-label">خط النص الأساسي</label>
                            <input
                                type="text"
                                name="font_primary"
                                class="form-control"
                                value="{{ old('font_primary', $theme->fontPrimary) }}"
                                placeholder="Inter, sans-serif"
                            >
                            @error('font_primary')
                                <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label">خط العناوين</label>
                            <input
                                type="text"
                                name="font_heading"
                                class="form-control"
                                value="{{ old('font_heading', $theme->fontHeading) }}"
                                placeholder="Inter, sans-serif"
                            >
                            @error('font_heading')
                                <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label">حجم الخط الأساسي</label>
                            <input
                                type="text"
                                name="base_font_size"
                                class="form-control font-mono"
                                value="{{ old('base_font_size', $theme->baseFontSize) }}"
                                placeholder="16px"
                            >
                            @error('base_font_size')
                                <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label">وزن الخط العادي</label>
                            <select name="weight_normal" class="form-select">
                                @foreach (['400' => 'Normal (400)', '300' => 'Light (300)', '500' => 'Medium (500)'] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('weight_normal', $theme->weightNormal) == $val)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">وزن الخط السميك</label>
                            <select name="weight_bold" class="form-select">
                                @foreach (['700' => 'Bold (700)', '600' => 'SemiBold (600)', '800' => 'ExtraBold (800)', '900' => 'Black (900)'] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('weight_bold', $theme->weightBold) == $val)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                </div>

                {{-- Shape --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 flex items-center gap-2">
                            <i class="ti ti-border-radius text-lg"></i>
                            الزوايا (Radius)
                        </h5>
                    </div>
                    <div class="card-body grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ([
                            'radius_sm' => ['SM', $theme->radiusSm],
                            'radius_md' => ['MD', $theme->radiusMd],
                            'radius_lg' => ['LG', $theme->radiusLg],
                            'radius_xl' => ['XL', $theme->radiusXl],
                        ] as $key => [$label, $value])
                            <div>
                                <label class="form-label font-mono">{{ $label }}</label>
                                <input
                                    type="text"
                                    name="{{ $key }}"
                                    class="form-control font-mono"
                                    value="{{ old($key, $value) }}"
                                    placeholder="0.5rem"
                                >
                                @error($key)
                                    <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 flex items-center gap-2">
                            <i class="ti ti-hand-click text-lg"></i>
                            الأزرار
                        </h5>
                    </div>
                    <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <label class="form-label">نمط الزر</label>
                            <select name="button_style" class="form-select">
                                @foreach ([
                                    'filled'  => 'Filled — خلفية ممتلئة',
                                    'outline' => 'Outline — حدود فقط',
                                    'ghost'   => 'Ghost — شفاف',
                                ] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('button_style', $theme->buttonStyle) === $val)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">زاوية الزر (Radius)</label>
                            <input
                                type="text"
                                name="button_radius"
                                class="form-control font-mono"
                                value="{{ old('button_radius', $theme->buttonRadius) }}"
                                placeholder="0.5rem"
                            >
                            @error('button_radius')
                                <p class="text-xs text-danger mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Live preview strip --}}
                <div class="card" id="theme-live-preview">
                    <div class="card-header">
                        <h5 class="mb-0 flex items-center gap-2">
                            <i class="ti ti-eye text-lg"></i>
                            معاينة مبسطة
                        </h5>
                    </div>
                    <div class="card-body flex flex-wrap items-center gap-3" id="preview-strip">
                        <span id="prev-heading"
                            class="text-base font-bold"
                            style="color: {{ $theme->colorHeading }}">
                            عنوان تجريبي
                        </span>
                        <span id="prev-body"
                            class="text-sm"
                            style="color: {{ $theme->colorBody }}">
                            نص عادي
                        </span>
                        <button type="button"
                            id="prev-btn"
                            class="px-4 py-2 text-sm font-bold text-white rounded"
                            style="background-color: {{ $theme->colorPrimary }}; border-radius: {{ $theme->buttonRadius }}">
                            زر رئيسي
                        </button>
                        <span id="prev-badge"
                            class="px-3 py-1 text-xs font-semibold rounded-full text-white"
                            style="background-color: {{ $theme->colorSecondary }}">
                            ثانوي
                        </span>
                        <span id="prev-surface"
                            class="px-3 py-1 text-xs border rounded"
                            style="background-color: {{ $theme->colorMuted }}; border-color: {{ $theme->colorBorder }}; color: {{ $theme->colorBody }}">
                            سطح
                        </span>
                    </div>
                </div>

            </div>

            {{-- ============================================================
                 Save bar
            ============================================================ --}}
            <div class="col-span-12">
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-6 py-4">
                    <div class="text-sm text-muted">
                        @if ($cssUrl)
                            ملف CSS:
                            <a href="{{ $cssUrl }}" target="_blank" class="font-mono text-xs text-primary">
                                {{ $cssUrl }}
                            </a>
                        @else
                            <span class="text-warning">لم يتم توليد ملف CSS بعد — سيتم توليده عند الحفظ.</span>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i class="ti ti-device-floppy"></i>
                        حفظ وتوليد CSS
                    </button>
                </div>
            </div>

        </div>{{-- /grid --}}
    </form>

    {{-- ====================================================================
         Color picker ↔ hex input sync  +  live preview
    ==================================================================== --}}
    @push('scripts')
    <script>
    (function () {
        const form = document.getElementById('theme-form');
        if (!form) return;

        // ----------------------------------------------------------------
        // 1. Sync color picker ↔ hex text input
        // ----------------------------------------------------------------
        const pickerByKey = new Map();
        const hexByKey    = new Map();

        form.querySelectorAll('[data-theme-color-picker]').forEach(el => {
            pickerByKey.set(el.dataset.themeColorPicker, el);
        });
        form.querySelectorAll('[data-theme-hex-input]').forEach(el => {
            hexByKey.set(el.dataset.themeHexInput, el);
        });

        pickerByKey.forEach((picker, key) => {
            const hex = hexByKey.get(key);
            if (!hex) return;

            // Picker → hex text
            picker.addEventListener('input', () => {
                hex.value = picker.value.toUpperCase();
                updatePreview();
            });

            // Hex text → picker
            hex.addEventListener('input', () => {
                const v = hex.value.trim();
                if (/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v)) {
                    picker.value = v;
                    updatePreview();
                }
            });
        });

        // ----------------------------------------------------------------
        // 2. Live preview strip
        // ----------------------------------------------------------------
        const prevHeading = document.getElementById('prev-heading');
        const prevBody    = document.getElementById('prev-body');
        const prevBtn     = document.getElementById('prev-btn');
        const prevBadge   = document.getElementById('prev-badge');
        const prevSurface = document.getElementById('prev-surface');

        function getVal(name) {
            const el = form.querySelector(`[name="${name}"]`);
            return el ? el.value.trim() : '';
        }

        function updatePreview() {
            if (prevHeading) prevHeading.style.color = getVal('color_heading') || '#0f172a';
            if (prevBody)    prevBody.style.color    = getVal('color_body')    || '#475569';
            if (prevBtn) {
                prevBtn.style.backgroundColor = getVal('color_primary') || '#7c3aed';
                prevBtn.style.borderRadius    = getVal('button_radius')  || '0.5rem';
            }
            if (prevBadge)   prevBadge.style.backgroundColor  = getVal('color_secondary') || '#e11d48';
            if (prevSurface) {
                prevSurface.style.backgroundColor = getVal('color_muted')  || '#f8fafc';
                prevSurface.style.borderColor     = getVal('color_border') || '#e2e8f0';
                prevSurface.style.color           = getVal('color_body')   || '#475569';
            }
        }

        // Also trigger preview update when any size/radius field changes
        form.querySelectorAll('input[name="button_radius"]').forEach(el => {
            el.addEventListener('input', updatePreview);
        });
    }());
    </script>
    @endpush

</x-dashboard-layout>
