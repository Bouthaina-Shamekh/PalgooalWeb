<x-template.layouts.index-layouts
    title="{{ __('Reset Password') }} - {{ t('Frontend.Palgoals', 'Palgoals') }}"
    description="{{ __('Choose a new password to recover access to your account.') }}"
    keywords="reset password, new password, authentication"
    ogImage="{{ asset('assets/dashboard/images/authentication/img-auth-sideimg.jpg') }}"
>
    <main class="bg-[#F2F2F2]">
        <div class="container mx-auto px-4 sm:px-6 lg:px-12 pt-6 pb-24">
            <p class="animate-from-left text-gray-dark text-base mb-8 capitalize flex items-center gap-2">
                <a href="{{ url('/') }}" class="hover:text-purple-brand transition-colors flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14.056" height="11.948" viewBox="0 0 14.056 11.948">
                        <path
                            d="M8.622,16.448V12.231h2.811v4.217h3.514V10.825h2.108L10.028,4.5,3,10.825H5.108v5.622Z"
                            transform="translate(-3 -4.5)" />
                    </svg>
                    Home
                </a>
                / Reset Password
            </p>

            <section class="mt-24 flex justify-center">
                <div class="bg-[#E8E8E8] w-full max-w-[507px] rounded-[20px] px-8 py-10">
                    <h1
                        class="font-almarai font-extrabold md:text-nowrap text-purple-brand text-[34px] md:text-[40px] leading-normal md:leading-[25px] uppercase text-center">
                        Reset Password
                    </h1>

                    <form class="mt-8 flex flex-col items-center gap-5" method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div class="w-full max-w-[406px]">
                            <label class="sr-only" for="reset-email">Email</label>
                            <input
                                id="reset-email"
                                name="email"
                                type="email"
                                inputmode="email"
                                autocomplete="email"
                                value="{{ old('email', $request->email) }}"
                                placeholder="Email"
                                class="bg-white w-full h-[60px] rounded-[20px] font-poppins text-[20px] tracking-[0.4px] text-[#696969] placeholder:text-[#696969] focus:outline-none focus:ring-2 focus:ring-red-brand/30 px-6"
                                required
                            />
                            @error('email')
                                <p class="mt-2 text-sm text-red-brand font-almarai">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full max-w-[406px]">
                            <label class="sr-only" for="reset-password">Password</label>
                            <input
                                id="reset-password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                placeholder="New Password"
                                class="bg-white w-full h-[60px] rounded-[20px] font-poppins text-[20px] tracking-[0.4px] text-[#696969] placeholder:text-[#696969] focus:outline-none focus:ring-2 focus:ring-red-brand/30 px-6"
                                required
                            />
                            @error('password')
                                <p class="mt-2 text-sm text-red-brand font-almarai">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full max-w-[406px]">
                            <label class="sr-only" for="reset-password-confirmation">Confirm Password</label>
                            <input
                                id="reset-password-confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                placeholder="Confirm Password"
                                class="bg-white w-full h-[60px] rounded-[20px] font-poppins text-[20px] tracking-[0.4px] text-[#696969] placeholder:text-[#696969] focus:outline-none focus:ring-2 focus:ring-red-brand/30 px-6"
                                required
                            />
                        </div>

                        <button type="submit"
                            class="w-full max-w-[406px] h-[50px] rounded-[10px] bg-red-brand text-white font-almarai text-[20px] capitalize hover:bg-red-brand/90 transition-colors">
                            Reset Password
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </main>
</x-template.layouts.index-layouts>
