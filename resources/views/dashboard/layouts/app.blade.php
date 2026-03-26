    @include('dashboard.layouts.partials.head', ['enableLegacyAdminLivewire' => $enableLegacyAdminLivewire ?? false])
    <!-- [ Sidebar Menu ] start -->
    @include('dashboard.layouts.partials.nav')
    <!-- [ Sidebar Menu ] end -->
    <!-- [ Header Topbar ] start -->
    @include('dashboard.layouts.partials.header')
    <!-- [ Header ] end -->

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
          {{ $slot }}
        </div>
    </div>
    <!-- [ Main Content ] end -->
    <!-- [ Footer ] start -->
    @include('dashboard.layouts.partials.footer')
    <!-- [ Footer ] end -->
    <!-- [ Customizer ] start -->
    @include('dashboard.layouts.partials.end', ['enableLegacyAdminLivewire' => $enableLegacyAdminLivewire ?? false])
    <!-- [ Customizer ] end -->
