    @include('layouts.partials.dashboard.head')
    <!-- [ Sidebar Menu ] start -->
    @include('layouts.partials.dashboard.nav')
    <!-- [ Sidebar Menu ] end -->
    <!-- [ Header Topbar ] start -->
    @include('layouts.partials.dashboard.header')
    <!-- [ Header ] end -->

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
          {{ $slot }}
        </div>
    </div>
    <!-- [ Main Content ] end -->
    <!-- [ Footer ] start -->
    @include('layouts.partials.dashboard.footer')
    <!-- [ Footer ] end -->
    <!-- [ Customizer ] start -->
    @include('layouts.partials.dashboard.end')
    <!-- [ Customizer ] end -->