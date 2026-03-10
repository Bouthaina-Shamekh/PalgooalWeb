{{-- Required Js --}}
<script src="{{asset('assets/dashboard/js/plugins/simplebar.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/plugins/popper.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/icon/custom-icon.js')}}"></script>
<script src="{{asset('assets/dashboard/js/plugins/feather.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/component.js')}}"></script>
<script src="{{asset('assets/dashboard/js/theme.js')}}"></script>
<script src="{{asset('assets/dashboard/js/script.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    layout_change('false');
</script>

<script>
    layout_theme_contrast_change('false');
</script>

<script>
    change_box_container('false');
</script>

<script>
    layout_caption_change('true');
</script>
@if (App::getlocale() == 'ar')
<script>
    // edir rtl or ltr
    layout_rtl_change('true');
</script>
@else
<script>
    // edir rtl or ltr
    layout_rtl_change('false');
</script>
@endif
<script>
    preset_change('preset-brand');
</script>
<style>
    html.preset-brand {
        --colors-primary-50: 237 235 239;
        --colors-primary-100: 216 211 219;
        --colors-primary-200: 185 177 191;
        --colors-primary-300: 146 132 155;
        --colors-primary-400: 97 79 111;
        --colors-primary-500: 36 10 55;
        --colors-primary-600: 32 9 48;
        --colors-primary-700: 27 8 42;
        --colors-primary-800: 22 6 33;
        --colors-primary-900: 15 4 23;
        --colors-primary-950: 10 3 15;
        --colors-primary: 36 10 55;
    }
</style>
<script>
    main_layout_change('vertical');
</script>
{{-- <script src="{{asset('assets/dashboard/Sortable.min.js')}}"></script> --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@stack('scripts')
@stack('modals')
@unless (request()->routeIs('dashboard.general_settings', 'dashboard.menus', 'dashboard.headers'))
    @livewireScripts
@endunless
  <script>
    function changebrand(presetColor) {
      const bodyElement = document.querySelector('body');
      removeClassByPrefix(bodyElement, 'bg-');
      removeClassByPrefix(bodyElement, 'from-');
      removeClassByPrefix(bodyElement, 'to-');
      console.log(presetColor);
      bodyElement.setAttribute('class',presetColor);
    }
    localStorage.setItem('layout', 'color-header');
  </script>
<script>
    window.MEDIA_CONFIG = window.MEDIA_CONFIG || {};
    window.MEDIA_CONFIG.baseUrl = window.MEDIA_CONFIG.baseUrl || "{{ url('admin/media') }}";
    window.MEDIA_CONFIG.csrfToken = window.MEDIA_CONFIG.csrfToken || "{{ csrf_token() }}";
</script>

<script src="{{ asset('assets/dashboard/js/media-picker.js') }}?v={{ filemtime(public_path('assets/dashboard/js/media-picker.js')) }}" defer></script>

@include('dashboard.partials.media-picker')
</body>
</html>
