@include('dashboard.layouts.partials.head')
<!-- [ Sidebar Menu ] start -->
@include('client.layouts.partials.nav')
<!-- [ Sidebar Menu ] end -->
<!-- [ Header Topbar ] start -->
@include('client.layouts.partials.header')
<!-- [ Header ] end -->

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        {{ $slot }}

        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->


@include('dashboard.layouts.partials.footer')


@include('client.layouts.partials.end')

