{{-- Required Js --}}
<script src="{{asset('assets/dashboard/js/plugins/simplebar.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/plugins/popper.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/plugins/i18next.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/plugins/i18nextHttpBackend.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/icon/custom-icon.js')}}"></script>
<script src="{{asset('assets/dashboard/js/plugins/feather.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/component.js')}}"></script>
<script src="{{asset('assets/dashboard/js/theme.js')}}"></script>
<script src="{{asset('assets/dashboard/js/script.js')}}"></script>

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
    preset_change('preset-1');
</script>
<script>
    main_layout_change('vertical');
</script>
@stack('scripts')
@stack('modals')
</body>
</html>