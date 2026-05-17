<x-template.layouts.index-layouts
    title="{{ __('Login') }} - {{ t('Frontend.Palgoals', 'Palgoals') }}"
    description="{{ __('Login to your account to access your dashboard and manage your projects.') }}"
    keywords="login, authentication, user access"
    ogImage="{{ asset('assets/dashboard/images/authentication/img-auth-sideimg.jpg') }}">
    <!-- Login Form Section -->
    <main class="bg-[#F2F2F2]">
        <div class="container mx-auto px-4 sm:px-6 lg:px-12 pt-6 pb-24">
            <!-- Breadcrumb -->
            <p class="animate-from-left text-gray-dark text-base mb-8 capitalize flex items-center gap-2">
                <a href="index.html" class="hover:text-purple-brand transition-colors flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14.056" height="11.948" viewBox="0 0 14.056 11.948">
                        <path id="Icon_material-home" data-name="Icon material-home"
                            d="M8.622,16.448V12.231h2.811v4.217h3.514V10.825h2.108L10.028,4.5,3,10.825H5.108v5.622Z"
                            transform="translate(-3 -4.5)" />
                    </svg>
                    Home
                </a>
                / Login
            </p>

            <!-- Card -->
            <section class="mt-10 flex justify-center">
                <div class="w-full max-w-[507px] bg-[#E8E8E8] rounded-[20px] px-6 sm:px-10 py-10">
                    <div class="text-center">
                        <h1 class="uppercase text-[40px] leading-none font-extrabold text-purple-brand font-almarai">
                            Login
                        </h1>
                        <p class="mt-1 text-[#626262] text-base font-almarai">
                            Don't Have An Account Yet?
                            <a href="{{ route('client.register') }}" class="font-bold text-black hover:text-red-brand transition-colors"> Sign Up</a>
                        </p>
                    </div>

                    <form action="{{ route('client.login.store') }}" class="mt-4 space-y-3" method="post">
                        @csrf
                        @if ($errors->any())
                        <div class="mb-3 rounded-[12px] bg-red-100 px-4 py-3 text-sm text-red-700 font-almarai">
                            {{ $errors->first() }}
                        </div>
                        @endif
                        <!-- Email -->
                        <label class="block">
                            <span class="sr-only">Email</span>
                            <div class="relative">
                                <span class="absolute left-6 top-1/2 -translate-y-1/2 text-red-brand">
                                    <svg class="w-[22px] h-[18px]" viewBox="0 0 24 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path
                                            d="M2 4.5C2 3.11929 3.11929 2 4.5 2H19.5C20.8807 2 22 3.11929 22 4.5V13.5C22 14.8807 20.8807 16 19.5 16H4.5C3.11929 16 2 14.8807 2 13.5V4.5Z"
                                            stroke="currentColor" stroke-width="1.6" />
                                        <path d="M3.5 4L12 10.25L20.5 4" stroke="currentColor" stroke-width="1.6"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="Email"
                                    class="w-full h-[60px] rounded-[20px] bg-white pl-14 pr-6 text-[18px] sm:text-[20px] tracking-[0.4px] text-[#696969] font-poppins outline-none focus:ring-2 focus:ring-red-brand/30 @error('email') ring-2 ring-red-500/40 @enderror" />
                            </div>
                           
                        </label>

                        <!-- Password -->
                        <label class="block">
                            <span class="sr-only">Password</span>
                            <div class="relative">
                                <span class="absolute left-6 top-1/2 -translate-y-1/2 text-red-brand">
                                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M7 10V8.5C7 5.46243 9.46243 3 12.5 3C15.5376 3 18 5.46243 18 8.5V10"
                                            stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                        <path
                                            d="M6 10H19C19.5523 10 20 10.4477 20 11V20C20 20.5523 19.5523 21 19 21H6C5.44772 21 5 20.5523 5 20V11C5 10.4477 5.44772 10 6 10Z"
                                            stroke="currentColor" stroke-width="1.6" />
                                        <path d="M12.5 14V17" stroke="currentColor" stroke-width="1.6"
                                            stroke-linecap="round" />
                                    </svg>
                                </span>
                                <input type="password" name="password" autocomplete="current-password"
                                    placeholder="Password"
                                    class="w-full h-[60px] rounded-[20px] bg-white pl-14 pr-6 text-[18px] sm:text-[20px] tracking-[0.4px] text-[#696969] font-poppins outline-none focus:ring-2 focus:ring-red-brand/30 @error('password') ring-2 ring-red-500/40 @enderror" />
                            </div>
                            @error('password')
                            <p class="mt-1 pl-2 text-sm text-red-600 font-almarai">
                                {{ $message }}
                            </p>
                            @enderror
                        </label>

                        <div class="flex justify-end pt-1">
                            <a href="{{ route('client.password.request') }}"
                                class="text-black text-base font-almarai hover:text-red-brand transition-colors">
                                Forgot Your Password?
                            </a>
                        </div>

                        <button type="submit"
                            class="w-full h-[50px] rounded-[10px] bg-red-brand text-white text-[20px] font-almarai capitalize hover:brightness-95 active:brightness-90 transition">
                            Login
                        </button>

                        <!-- OR -->
                        <div class="pt-1">
                            <div class="flex items-center gap-4">
                                <div class="h-px bg-black/20 flex-1"></div>
                                <span class="text-black text-base font-almarai">OR</span>
                                <div class="h-px bg-black/20 flex-1"></div>
                            </div>
                        </div>

                        <!-- Social buttons -->
                        <div class="pt-1 flex items-center justify-between gap-2">
                            <button type="button"
                                class="h-[50px] w-[132px] rounded-[10px] bg-white flex items-center justify-center hover:bg-gray-50 transition"
                                aria-label="Continue with Apple">
                                <svg width="19" height="23" viewBox="0 0 19 23" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_126_31)">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M12.8611 3.54492C13.2923 3.06683 13.6218 2.50591 13.8294 1.89648C14.0371 1.28705 14.1186 0.64193 14.0691 0C12.7585 0.115567 11.539 0.718184 10.6511 1.68896C10.2176 2.14402 9.88411 2.68481 9.67195 3.27637C9.45978 3.86792 9.37364 4.49722 9.41906 5.12402C10.0747 5.13728 10.7249 5.00225 11.3211 4.729C11.9173 4.45576 12.4441 4.05124 12.8621 3.5459M15.7831 11.8159C15.8028 12.7877 16.1087 13.732 16.6624 14.5308C17.2161 15.3296 17.9931 15.9476 18.8961 16.3071C18.5224 17.4374 17.9814 18.5052 17.2911 19.4751C16.3231 20.8201 15.3201 22.1752 13.7381 22.1992C12.1851 22.2322 11.6851 21.3208 9.90806 21.3208C8.13106 21.3208 7.57706 22.1769 6.10806 22.2319C4.58206 22.2879 3.41806 20.7649 2.44406 19.4199C0.450063 16.6519 -1.07294 11.6202 0.973062 8.22021C1.47171 7.38702 2.17365 6.69402 3.01316 6.20605C3.85266 5.71809 4.80227 5.45109 5.77306 5.43018C7.27306 5.40818 8.68606 6.39697 9.60206 6.39697C10.5181 6.39697 12.2381 5.20777 14.0451 5.38477C14.8728 5.40654 15.6848 5.61599 16.42 5.99707C17.1551 6.37815 17.7942 6.9211 18.2891 7.58496C17.5419 8.01538 16.9185 8.63114 16.4792 9.37305C16.0398 10.115 15.7993 10.9578 15.7811 11.8198"
                                            fill="black" />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_126_31">
                                            <rect width="18.896" height="22.232" fill="white" />
                                        </clipPath>
                                    </defs>
                                </svg>
                            </button>
                            <button type="button"
                                class="h-[50px] w-[132px] rounded-[10px] bg-white flex items-center justify-center hover:bg-gray-50 transition"
                                aria-label="Continue with Google">
                                <svg width="22" height="23" viewBox="0 0 22 23" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_126_47)">
                                        <path
                                            d="M21.788 11.364C21.8011 10.5996 21.7222 9.83649 21.553 9.091H11.116V13.216H17.242C17.1259 13.9394 16.8648 14.6319 16.4741 15.2518C16.0835 15.8717 15.5715 16.4061 14.969 16.823L14.948 16.961L18.248 19.517L18.477 19.54C19.5696 18.4841 20.4287 17.2109 20.9988 15.8024C21.569 14.394 21.8374 12.8816 21.787 11.363"
                                            fill="#4285F4" />
                                        <path
                                            d="M11.1161 22.233C13.8237 22.3088 16.4576 21.3452 18.4771 19.54L14.9691 16.823C13.8315 17.5858 12.4842 17.9745 11.1151 17.935C9.7094 17.9268 8.342 17.4762 7.20686 16.6471C6.07172 15.818 5.22648 14.6525 4.79106 13.316L4.66106 13.327L1.23006 15.983L1.18506 16.108C2.1092 17.9496 3.52735 19.4979 5.28098 20.5797C7.0346 21.6616 9.05458 22.2343 11.1151 22.234"
                                            fill="#34A853" />
                                        <path
                                            d="M4.79195 13.315C4.5491 12.6069 4.42411 11.8637 4.42195 11.115C4.42621 10.3675 4.547 9.62529 4.77995 8.91502L4.77395 8.76803L1.29995 6.07202L1.18595 6.12602C0.406332 7.67389 0.000244141 9.38291 0.000244141 11.116C0.000244141 12.8491 0.406332 14.5582 1.18595 16.106L4.79295 13.315"
                                            fill="#FBBC05" />
                                        <path
                                            d="M11.116 4.29999C12.7095 4.27476 14.2507 4.86795 15.416 5.95499L18.552 2.88999C16.5397 1.0023 13.875 -0.03337 11.116 -1.19452e-05C9.05555 -0.00036278 7.03558 0.572402 5.28195 1.65425C3.52833 2.73609 2.11017 4.28437 1.18604 6.12599L4.78003 8.91699C5.22002 7.58108 6.06814 6.41692 7.20485 5.58861C8.34157 4.76029 9.70957 4.30957 11.116 4.29999Z"
                                            fill="#EB4335" />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_126_47">
                                            <rect width="21.788" height="22.232" fill="white" />
                                        </clipPath>
                                    </defs>
                                </svg>
                            </button>
                            <button type="button"
                                class="h-[50px] w-[132px] rounded-[10px] bg-white flex items-center justify-center hover:bg-gray-50 transition"
                                aria-label="Continue with X">
                                <svg width="23" height="23" viewBox="0 0 23 23" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_126_37)">
                                        <path
                                            d="M22.727 22.232L13.84 9.275L13.855 9.287L21.868 0H19.191L12.663 7.559L7.478 0H0.455L8.755 12.1L0 22.232H2.678L9.936 13.822L15.704 22.232H22.727ZM6.42 2.021L18.89 20.211H16.765L4.285 2.021H6.42Z"
                                            fill="black" />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_126_37">
                                            <rect width="22.727" height="22.232" fill="white" />
                                        </clipPath>
                                    </defs>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>

</x-template.layouts.index-layouts>