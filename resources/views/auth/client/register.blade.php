@include('dashboard.layouts.partials..head', [
'title' => __('Register'),
])

<!-- [ Main Content ] start -->

<div class="auth-main relative">
    <div class="auth-wrapper v2 flex items-center w-full h-full min-h-screen">
        <div class="auth-sidecontent">
            <img src="{{ asset('assets/dashboard/images/authentication/img-auth-sideimg.jpg') }}" alt="images" class="img-fluid h-screen hidden lg:block" />
        </div>
        <div class="auth-form flex items-center justify-center grow flex-col min-h-screen bg-cover relative p-6 bg-theme-cardbg dark:bg-themedark-cardbg">
            <div class="card sm:my-12 w-full max-w-[680px] border-none shadow-none" style="max-width: 650px;">
                <div class="card-body sm:!p-10">
                    <div class="text-center">
                        <img src="{{ asset('assets/dashboard/images/logo.png') }}" alt="img" class="mx-auto"
                        width="80%" />
                        <div class="grid my-4">
                            <button type="button" class="btn mt-2 flex items-center justify-center gap-2 text-theme-bodycolor dark:text-themedark-bodycolor bg-theme-bodybg dark:bg-themedark-bodybg border border-theme-border dark:border-themedark-border hover:border-primary-500 dark:hover:border-primary-500">
                                <img src="{{ asset('assets/dashboard/images/authentication/facebook.svg') }}" alt="img" /> <span> Sign In with Facebook</span>
                            </button>
                            <button type="button" class="btn mt-2 flex items-center justify-center gap-2 text-theme-bodycolor dark:text-themedark-bodycolor bg-theme-bodybg dark:bg-themedark-bodybg border border-theme-border dark:border-themedark-border hover:border-primary-500 dark:hover:border-primary-500">
                                <img src="{{ asset('assets/dashboard/images/authentication/twitter.svg') }}" alt="img" /> <span> Sign In with Twitter</span>
                            </button>
                            <button type="button" class="btn mt-2 flex items-center justify-center gap-2 text-theme-bodycolor dark:text-themedark-bodycolor bg-theme-bodybg dark:bg-themedark-bodybg border border-theme-border dark:border-themedark-border hover:border-primary-500 dark:hover:border-primary-500">
                                <img src="{{ asset('assets/dashboard/images/authentication/google.svg') }}" alt="img" /> <span> Sign In with Google</span>
                            </button>
                        </div>
                    </div>
                    <div class="relative my-5">
                        <div aria-hidden="true" class="absolute flex inset-0 items-center">
                            <div class="w-full border-t border-theme-border dark:border-themedark-border"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="px-4 bg-theme-cardbg dark:bg-themedark-cardbg">OR</span>
                        </div>
                    </div>
                    <h4 class="text-center font-medium mb-4">Sign up with your work email.</h4>
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('register.store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="type" value="client">
                        <div class="grid grid-cols-12 gap-x-6">
                            <div class="col-span-12 sm:col-span-6">
                                <div class="mb-3">
                                    <input type="text" name="first_name" value="{{old('first_name')}}" class="form-control" placeholder="{{__('First Name')}}" required />
                                </div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="mb-3">
                                    <input type="text" name="last_name" value="{{old('last_name')}}" class="form-control" placeholder="{{__('Last Name')}}" required />
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <input type="email" name="email" value="{{old('email')}}" class="form-control" placeholder="{{__('Email Address')}}" required />
                        </div>
                        <div class="grid grid-cols-12 gap-x-6">
                            <div class="col-span-12 sm:col-span-6">
                                <div class="mb-3">
                                    <input type="password" name="password" class="form-control" placeholder="{{__('Password')}}" required />
                                </div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="mb-4">
                                    <input type="password" name="confirm_password" class="form-control" placeholder="{{__('Confirm Password')}}" required />
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-12 gap-x-6">
                            <div class="col-span-12 sm:col-span-6">
                                <div class="mb-3">
                                    <input type="text" name="company_name" value="{{old('company_name')}}" class="form-control" placeholder="{{__('Company Name')}}" />
                                </div>
                            </div>
                            <div class="col-span-12 sm:col-span-2">
                                <div class="mb-4">
                                    <input type="number" min="0" name="zip_code"  value="{{old('zip_code')}}" class="form-control" placeholder="{{__('Zip Code 972')}}" />
                                </div>
                            </div>
                            <div class="col-span-12 sm:col-span-4">
                                <div class="mb-4">
                                    <input type="text" name="phone" class="form-control" value="{{old('phone')}}" placeholder="{{__('Phone')}}" />
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <input type="file" name="avatar" class="form-control" placeholder="{{__('Avatar')}}" />
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary w-full">Sign up</button>
                        </div>
                    </form>
                    <div class="flex justify-between items-end flex-wrap mt-4">
                        <h6 class="f-w-500 mb-0">Already have an Account?</h6>
                        <a href="{{route('login')}}" class="text-primary-500">Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->

<style>
    .floting-button {
        display: none;
    }

</style>

@include('dashboard.layouts.partials..end')

