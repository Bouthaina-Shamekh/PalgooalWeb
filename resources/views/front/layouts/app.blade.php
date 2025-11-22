@include('front.layouts.partials.head', ['seo' => $seo ?? null])
@include('front.layouts.partials.header')

<div class="pc-container">
    <div class="pc-content">
        {{ $slot ?? '' }}
        @yield('content')
    </div>
</div>

@include('front.layouts.partials.footer')
@include('front.layouts.partials.end')
