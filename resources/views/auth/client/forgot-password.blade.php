<x-template.layouts.index-layouts
    title="{{ __('Forgot Password') }} - {{ t('Frontend.Palgoals', 'Palgoals') }}"
    description="{{ __('Reset your password and recover access to your account.') }}"
    keywords="forgot password, reset password, authentication"
    ogImage="{{ asset('assets/dashboard/images/authentication/img-auth-sideimg.jpg') }}"
>
    <!-- Forgot Password Form Section -->
    <main class="bg-[#F2F2F2]">
        <div class="container mx-auto px-4 sm:px-6 lg:px-12 pt-6 pb-24">
            <!-- Breadcrumb -->
            <p class="animate-from-left text-gray-dark text-base mb-8 capitalize flex items-center gap-2">
                <a href="{{ url('/') }}" class="hover:text-purple-brand transition-colors flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14.056" height="11.948" viewBox="0 0 14.056 11.948">
                        <path id="Icon_material-home" data-name="Icon material-home"
                            d="M8.622,16.448V12.231h2.811v4.217h3.514V10.825h2.108L10.028,4.5,3,10.825H5.108v5.622Z"
                            transform="translate(-3 -4.5)" />
                    </svg>
                    Home
                </a>
                / Forgot Password?
            </p>

            <!-- Card -->
            <section class="mt-24 flex justify-center">
                <div class="bg-[#E8E8E8] w-full max-w-[507px] rounded-[20px] px-8 py-10">
                    <h1
                        class="font-almarai font-extrabold md:text-nowrap text-purple-brand text-[34px] md:text-[40px] leading-normal md:leading-[25px] uppercase text-center">
                        Forgot Password?
                    </h1>

                    @if (session('status'))
                        <div class="mt-6 rounded-[12px] bg-green-100 text-green-700 px-4 py-3 text-sm font-almarai">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form class="mt-8 flex flex-col items-center gap-5" action="{{ route('password.email') }}" method="post">
                        @csrf

                        <div class="w-full max-w-[406px]">
                            <label class="sr-only" for="forgot-email">Email</label>

                            <div class="relative">
                                <span class="absolute inset-y-0 ltr:left-6 rtl:right-6 flex items-center text-red-brand"
                                    aria-hidden="true">
                                    <svg class="w-[22px] h-[18px]" viewBox="0 0 22.404 17.695" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M3.734 3.112h14.936c.617 0 1.117.5 1.117 1.116v9.239c0 .616-.5 1.116-1.117 1.116H3.734c-.617 0-1.117-.5-1.117-1.116V4.228c0-.616.5-1.116 1.117-1.116Z"
                                            stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                                        <path d="m2.91 4.09 8.292 6.035L19.494 4.09" stroke="currentColor"
                                            stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>

                                <input
                                    id="forgot-email"
                                    name="email"
                                    type="email"
                                    inputmode="email"
                                    autocomplete="email"
                                    value="{{ old('email') }}"
                                    placeholder="Email"
                                    class="bg-white w-full h-[60px] rounded-[20px] font-poppins text-[20px] tracking-[0.4px] text-[#696969] placeholder:text-[#696969] focus:outline-none focus:ring-2 focus:ring-red-brand/30 ltr:pl-[60px] rtl:pr-[60px] ltr:pr-6 rtl:pl-6"
                                />
                            </div>

                            @error('email')
                                <p class="mt-2 text-sm text-red-brand font-almarai">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <button type="submit"
                            class="w-full max-w-[406px] h-[50px] rounded-[10px] bg-red-brand text-white font-almarai text-[20px] capitalize hover:bg-red-brand/90 transition-colors">
                            Send
                        </button>

                        <a href="{{ route('login') }}"
                            class="text-black text-base font-almarai hover:text-red-brand transition-colors">
                            Back To Login
                        </a>
                    </form>
                </div>
            </section>
        </div>
    </main>
</x-template.layouts.index-layouts>