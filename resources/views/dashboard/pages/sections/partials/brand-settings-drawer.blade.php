{{--
    Brand Settings Drawer
    =====================
    Slide-over panel embedded in the Builder workspace for editing the site's
    brand / theme tokens without leaving the builder.

    Required variables (passed from index.blade.php):
      $brandSettingsUpdateUrl  string             POST target
      $brandSettingsTheme      TenantThemeSettings  current saved values
      $isClientWorkspace       bool
      $isRtl                   bool

    JS strategy
    -----------
    The drawer HTML is inside @yield('workspace-main'), which is rendered
    BEFORE @yield('workspace-sidebar') in the workspace layout.  The trigger
    button lives in the sidebar.  An inline IIFE therefore runs before the
    button exists in the DOM and querySelectorAll('[data-open-brand-settings]')
    returns an empty NodeList — no click handler gets attached.

    Fix: push all JS to @stack('scripts') so it executes at the bottom of
    <body> after ALL HTML has been parsed.  Additionally use event delegation
    (listen on document, check event.target) so the open trigger works
    regardless of when it enters the DOM.
--}}
@php
    /** @var \App\Support\Tenancy\TenantThemeSettings $brandSettingsTheme */
    $t = $brandSettingsTheme;
    $drawerTitle        = $isClientWorkspace ? __('Brand Settings') : __('Theme Settings');
    $drawerDescription  = $isClientWorkspace
        ? __('Customise your site colours, fonts and button style.')
        : __('Configure tenant theme tokens. Changes regenerate the CSS file immediately.');
    $saveLabel          = __('Save Changes');
    $cancelLabel        = __('Cancel');
@endphp

{{-- Overlay --}}
<div id="brand-settings-overlay"
     class="fixed inset-0 z-[62] hidden bg-slate-950/55 transition-opacity"
     aria-hidden="true"></div>

{{-- Drawer --}}
<aside id="brand-settings-drawer"
       class="fixed inset-y-0 z-[63] flex w-full max-w-lg flex-col border-slate-200 bg-white shadow-2xl transition-transform duration-200 {{ $isRtl ? 'left-0 border-r -translate-x-full' : 'right-0 border-l translate-x-full' }}"
       aria-hidden="true"
       aria-labelledby="brand-settings-drawer-title">

    {{-- Header --}}
    <div class="flex-shrink-0 border-b border-slate-200 px-5 py-4 lg:px-6">
        <div class="flex items-start justify-between gap-4 rtl:flex-row-reverse">
            <div>
                <h3 id="brand-settings-drawer-title" class="text-lg font-semibold text-slate-900">{{ $drawerTitle }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $drawerDescription }}</p>
            </div>
            <button type="button" data-close-brand-settings
                    class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
                    aria-label="{{ __('Close') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Success flash (shown by JS after redirect-back) --}}
    <div id="brand-settings-success-banner"
         class="hidden flex-shrink-0 items-center gap-3 border-b border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-800 lg:px-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ __('Brand settings saved successfully.') }}</span>
    </div>

    {{-- Scrollable form body --}}
    <div class="workspace-scrollbar flex-1 overflow-y-auto">
        <form id="brand-settings-form"
              action="{{ $brandSettingsUpdateUrl }}"
              method="POST"
              class="divide-y divide-slate-100">
            @csrf
            {{-- Populated by JS: tells the controller to redirect back to this builder URL --}}
            <input type="hidden" name="_return_url" id="brand-settings-return-url" value="">

            {{-- ── COLOURS ─────────────────────────────────────────────── --}}
            <section class="px-5 py-5 lg:px-6">
                <h4 class="mb-4 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">{{ __('Colours') }}</h4>
                <div class="space-y-4">

                    @php
                        $colorFields = [
                            ['key' => 'color_primary',   'value' => $t->colorPrimary,   'label' => __('Primary')],
                            ['key' => 'color_secondary', 'value' => $t->colorSecondary, 'label' => __('Secondary')],
                            ['key' => 'color_heading',   'value' => $t->colorHeading,   'label' => __('Heading text')],
                            ['key' => 'color_body',      'value' => $t->colorBody,      'label' => __('Body text')],
                            ['key' => 'color_surface',   'value' => $t->colorSurface,   'label' => __('Surface / card')],
                            ['key' => 'color_muted',     'value' => $t->colorMuted,     'label' => __('Muted background')],
                            ['key' => 'color_border',    'value' => $t->colorBorder,    'label' => __('Border')],
                        ];
                    @endphp

                    @foreach ($colorFields as $cf)
                        <div class="flex items-center gap-3 rtl:flex-row-reverse">
                            <label for="brand_{{ $cf['key'] }}"
                                   class="w-36 flex-shrink-0 text-sm font-medium text-slate-700 ltr:text-left rtl:text-right">
                                {{ $cf['label'] }}
                            </label>
                            <div class="flex flex-1 items-center gap-2 rtl:flex-row-reverse">
                                {{-- Native colour picker --}}
                                <input type="color"
                                       id="brand_{{ $cf['key'] }}"
                                       value="{{ $cf['value'] }}"
                                       class="h-9 w-12 flex-shrink-0 cursor-pointer rounded-xl border border-slate-200 bg-white p-1 shadow-sm"
                                       data-color-picker="{{ $cf['key'] }}"
                                       aria-label="{{ $cf['label'] }}">
                                {{-- Hex text input --}}
                                <input type="text"
                                       name="{{ $cf['key'] }}"
                                       value="{{ $cf['value'] }}"
                                       maxlength="7"
                                       placeholder="#000000"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-slate-400 ltr:text-left rtl:text-right"
                                       data-color-hex="{{ $cf['key'] }}"
                                       aria-label="{{ $cf['label'] }} hex">
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ── TYPOGRAPHY ──────────────────────────────────────────── --}}
            <section class="px-5 py-5 lg:px-6">
                <h4 class="mb-4 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">{{ __('Typography') }}</h4>
                <div class="space-y-4">

                    <div>
                        <label for="brand_font_primary" class="mb-1.5 block text-sm font-medium text-slate-700 ltr:text-left rtl:text-right">
                            {{ __('Body font family') }}
                        </label>
                        <input type="text" id="brand_font_primary" name="font_primary"
                               value="{{ $t->fontPrimary }}"
                               placeholder="Cairo, sans-serif"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-slate-400 ltr:text-left rtl:text-right">
                    </div>

                    <div>
                        <label for="brand_font_heading" class="mb-1.5 block text-sm font-medium text-slate-700 ltr:text-left rtl:text-right">
                            {{ __('Heading font family') }}
                        </label>
                        <input type="text" id="brand_font_heading" name="font_heading"
                               value="{{ $t->fontHeading }}"
                               placeholder="Almarai, sans-serif"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-slate-400 ltr:text-left rtl:text-right">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="brand_weight_normal" class="mb-1.5 block text-sm font-medium text-slate-700 ltr:text-left rtl:text-right">
                                {{ __('Normal weight') }}
                            </label>
                            <select id="brand_weight_normal" name="weight_normal"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-slate-400">
                                @foreach (['300' => __('Light 300'), '400' => __('Regular 400'), '500' => __('Medium 500'), '600' => __('Semibold 600')] as $val => $lbl)
                                    <option value="{{ $val }}" @selected($t->weightNormal === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="brand_weight_bold" class="mb-1.5 block text-sm font-medium text-slate-700 ltr:text-left rtl:text-right">
                                {{ __('Bold weight') }}
                            </label>
                            <select id="brand_weight_bold" name="weight_bold"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-slate-400">
                                @foreach (['600' => __('Semibold 600'), '700' => __('Bold 700'), '800' => __('Extrabold 800'), '900' => __('Black 900')] as $val => $lbl)
                                    <option value="{{ $val }}" @selected($t->weightBold === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                </div>
            </section>

            {{-- ── SHAPE / RADIUS ───────────────────────────────────────── --}}
            <section class="px-5 py-5 lg:px-6">
                <h4 class="mb-4 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">{{ __('Border Radius') }}</h4>
                <div class="grid grid-cols-2 gap-3">
                    @foreach ([
                        ['key' => 'radius_sm', 'value' => $t->radiusSm, 'label' => __('Small (sm)')],
                        ['key' => 'radius_md', 'value' => $t->radiusMd, 'label' => __('Medium (md)')],
                        ['key' => 'radius_lg', 'value' => $t->radiusLg, 'label' => __('Large (lg)')],
                        ['key' => 'radius_xl', 'value' => $t->radiusXl, 'label' => __('X-Large (xl)')],
                    ] as $rf)
                        <div>
                            <label for="brand_{{ $rf['key'] }}" class="mb-1.5 block text-sm font-medium text-slate-700 ltr:text-left rtl:text-right">
                                {{ $rf['label'] }}
                            </label>
                            <input type="text" id="brand_{{ $rf['key'] }}" name="{{ $rf['key'] }}"
                                   value="{{ $rf['value'] }}"
                                   placeholder="0.5rem"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-slate-400 ltr:text-left rtl:text-right">
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ── BUTTONS ──────────────────────────────────────────────── --}}
            <section class="px-5 py-5 lg:px-6">
                <h4 class="mb-4 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">{{ __('Buttons') }}</h4>
                <div class="space-y-4">

                    <div>
                        <label for="brand_button_radius" class="mb-1.5 block text-sm font-medium text-slate-700 ltr:text-left rtl:text-right">
                            {{ __('Button radius') }}
                        </label>
                        <input type="text" id="brand_button_radius" name="button_radius"
                               value="{{ $t->buttonRadius }}"
                               placeholder="9999px"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-slate-400 ltr:text-left rtl:text-right">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700 ltr:text-left rtl:text-right">
                            {{ __('Button style') }}
                        </label>
                        <div class="flex gap-2 rtl:flex-row-reverse">
                            @foreach ([
                                'filled'  => __('Filled'),
                                'outline' => __('Outline'),
                                'ghost'   => __('Ghost'),
                            ] as $bsVal => $bsLabel)
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="button_style" value="{{ $bsVal }}"
                                           class="peer sr-only"
                                           @checked($t->buttonStyle === $bsVal)>
                                    <span class="flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition peer-checked:border-slate-900 peer-checked:bg-slate-900 peer-checked:text-white peer-checked:shadow-none">
                                        {{ $bsLabel }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                </div>
            </section>

        </form>
    </div>

    {{-- Footer actions --}}
    <div class="flex-shrink-0 border-t border-slate-200 bg-white px-5 py-4 lg:px-6">
        <div class="flex items-center justify-between gap-3 rtl:flex-row-reverse">
            <button type="button" data-close-brand-settings
                    class="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                {{ $cancelLabel }}
            </button>
            <button type="submit" form="brand-settings-form"
                    class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 disabled:pointer-events-none disabled:opacity-60"
                    id="brand-settings-submit-btn">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6A2.25 2.25 0 016 3.75h1.5m9 0h-9"/>
                </svg>
                <span id="brand-settings-submit-label">{{ $saveLabel }}</span>
            </button>
        </div>
    </div>
</aside>

{{--
    ── Brand Settings Drawer JS ────────────────────────────────────────────────
    Pushed to @stack('scripts') so it executes at the bottom of <body>, AFTER
    all HTML (including the sidebar trigger button) has been parsed.

    Uses event delegation on `document` for the open trigger — this means the
    button works regardless of when it is added to the DOM.
--}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        'use strict';

        var overlay        = document.getElementById('brand-settings-overlay');
        var drawer         = document.getElementById('brand-settings-drawer');
        var form           = document.getElementById('brand-settings-form');
        var returnUrlField = document.getElementById('brand-settings-return-url');
        var submitBtn      = document.getElementById('brand-settings-submit-btn');
        var submitLabel    = document.getElementById('brand-settings-submit-label');
        var successBanner  = document.getElementById('brand-settings-success-banner');

        // Guard: drawer may not exist on pages where the condition was false.
        if (!drawer) return;

        var isRtl       = document.documentElement.dir === 'rtl';
        var hiddenClass = isRtl ? '-translate-x-full' : 'translate-x-full';

        // ── Open ──────────────────────────────────────────────────────────────
        function openDrawer() {
            // Set the _return_url so the controller redirects back here.
            if (returnUrlField) {
                returnUrlField.value = window.location.pathname + window.location.search;
            }
            if (overlay) overlay.classList.remove('hidden');
            drawer.classList.remove(hiddenClass);
            drawer.removeAttribute('aria-hidden');
            document.body.style.overflow = 'hidden';
        }

        // ── Close ─────────────────────────────────────────────────────────────
        function closeDrawer() {
            if (overlay) overlay.classList.add('hidden');
            drawer.classList.add(hiddenClass);
            drawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        // ── Event delegation: open trigger ────────────────────────────────────
        // The trigger button lives in @yield('workspace-sidebar') which is
        // rendered AFTER @yield('workspace-main') in the layout — so it does
        // not exist in the DOM when this script runs without DOMContentLoaded.
        // Even with DOMContentLoaded this delegation approach is more robust.
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-open-brand-settings]')) {
                openDrawer();
            }
        });

        // ── Direct bind: close buttons (inside the drawer — always present) ───
        document.querySelectorAll('[data-close-brand-settings]').forEach(function (btn) {
            btn.addEventListener('click', closeDrawer);
        });

        if (overlay) {
            overlay.addEventListener('click', closeDrawer);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !drawer.classList.contains(hiddenClass)) {
                closeDrawer();
            }
        });

        // ── Colour picker ↔ hex text sync ─────────────────────────────────────
        document.querySelectorAll('[data-color-picker]').forEach(function (picker) {
            var key      = picker.dataset.colorPicker;
            var hexInput = document.querySelector('[data-color-hex="' + key + '"]');
            if (!hexInput) return;

            picker.addEventListener('input', function () {
                hexInput.value = picker.value.toUpperCase();
            });

            hexInput.addEventListener('input', function () {
                var val = hexInput.value.trim();
                if (/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(val)) {
                    picker.value = val;
                }
            });
        });

        // ── Submit: show saving state ──────────────────────────────────────────
        if (form) {
            form.addEventListener('submit', function () {
                if (submitBtn)   submitBtn.disabled     = true;
                if (submitLabel) submitLabel.textContent = @json(__('Saving…'));
            });
        }

        // ── Flash success banner after redirect-back ───────────────────────────
        // workspace.blade.php emits <meta name="brand-settings-success"> when
        // the session contains 'brand_settings_success'.
        var flashMeta = document.querySelector('meta[name="brand-settings-success"]');
        if (flashMeta && successBanner) {
            successBanner.classList.remove('hidden');
            successBanner.classList.add('flex');
            openDrawer();
            setTimeout(function () {
                successBanner.classList.add('hidden');
                successBanner.classList.remove('flex');
            }, 4000);
        }
    });
</script>
@endpush
