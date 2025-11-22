@include('dashboard.layouts.partials..head')
    <div class="wrapper vh-100">
        <div class="align-items-center h-100 d-flex w-50 mx-auto">
            <div class="mx-auto text-center">
                <h1 class="display-1 m-0 font-weight-bolder text-danger" style="font-size:80px;">500</h1>
                <h1 class="mb-1 text-muted font-weight-bold">OOPS!</h1>
                <h4 class="mb-3 text-black">Please contact the engineer at this moment.</h4>
                @if(Config::get('fortify.guard') == 'admin')
                    <a href="{{ route('dashboard.home')}}" class="btn btn-lg btn-primary px-5">Back To Home</a>
                @elseif(Config::get('fortify.guard') == 'publisherGuard')
                    <a href="{{ route('publisher.home')}}" class="btn btn-lg btn-primary px-5">Back To Home</a>
                @endif
            </div>
        </div>
    </div>
@include('dashboard.layouts.partials..end')

