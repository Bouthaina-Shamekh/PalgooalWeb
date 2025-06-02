{{-- Required Js --}}
<script src="{{asset('assets/dashboard/js/plugins/bootstrap-slider.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/plugins/jquery-3.7.1.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/plugins/simplebar.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/plugins/popper.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/icon/custom-icon.js')}}"></script>
<script src="{{asset('assets/dashboard/js/plugins/feather.min.js')}}"></script>
<script src="{{asset('assets/dashboard/js/component.js')}}"></script>
<script src="{{asset('assets/dashboard/js/theme.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="{{asset('assets/dashboard/js/script.js')}}"></script>

<div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]">
    <a href="#header"
        class="btn btn-danger buynowlinks animate-[btn-floating_2s_infinite] max-sm:p-[13px] max-sm:rounded-full inline-flex items-center gap-2"
        data-pc-toggle="tooltip" data-pc-title="Buy Now">
        <i class="fas fa-angle-double-up"></i>
    </a>
</div>

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
<script>
    layout_rtl_change('false');
</script>
<script>
    preset_change('preset-1');
</script>
<script>
    main_layout_change('vertical');
</script>
@stack('scripts')
</body>

</html>
