@include('layouts.partials.dashboard.head')
    <div class="wrapper vh-100">
        <div class="align-items-center h-100 d-flex w-50 mx-auto">
            <div class="mx-auto text-center">
                <h1 class="display-1 m-0 font-weight-bolder text-danger" style="font-size:80px;">403</h1>
                <h1 class="mb-1 text-muted font-weight-bold">OOPS!</h1>
                <h4 class="mb-3 text-black">You are not allowed in this dashboard.</h4>
                <a href="{{ route('dashboard.home')}}" class="btn btn-lg btn-primary px-5">Back To Home</a>
            </div>
        </div>
    </div>
@include('layouts.partials.dashboard.end')
