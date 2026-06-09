<x-template.layouts.index-layouts
    title="{{ __('Register') }} - {{ t('Frontend.Palgoals', 'Palgoals') }}"
    description="{{ __('Register to your account to access your dashboard and manage your projects.') }}"
    keywords="login, authentication, user access"
    ogImage="{{ asset('assets/dashboard/images/authentication/img-auth-sideimg.jpg') }}"
>
        <!-- Signup Form Section -->
        <main class="bg-[#f2f2f2]">
            <section class="container mx-auto px-4 sm:px-6 lg:px-12 pt-6 pb-20">
                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-[#626262] text-base">
                    <svg class="w-4 h-4 shrink-0 text-black/80" viewBox="0 0 24 24" fill="currentColor"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path
                            d="M12 3.172 2.93 11.1a1 1 0 0 0-.341.753V21a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1v-6h5v6a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1v-9.147a1 1 0 0 0-.34-.753L12 3.172Z" />
                    </svg>
                    <p class="font-almarai leading-[25px]">
                        <a href="{{ url('/') }}" class="hover:text-purple-brand transition-colors">Home</a>
                        /&nbsp; Sign Up
                    </p>
                </div>
    
                <!-- Card -->
                <div class="min-h-[calc(971px-128px)] flex items-start justify-center pt-24 md:pt-28">
                    <div class="w-full max-w-[507px] bg-[#e8e8e8] rounded-[20px] px-6 sm:px-10 pt-10 pb-12">
                        <div class="text-center">
                            <h1
                                class="font-almarai font-extrabold text-[#240a37] text-[34px] sm:text-[40px] leading-none uppercase">
                                sign up
                            </h1>
                            <div
                                class="mt-1 flex items-center justify-center gap-2 text-[#626262] text-base leading-[25px]">
                                <span class="font-almarai">Already Have An Account?</span>
                                <a href="{{ route('client.login') }}"
                                    class="font-almarai font-bold text-black hover:text-red-brand transition-colors">Login</a>
                            </div>
                        </div>
    
                        <form class="mt-4 space-y-[10px]" action="{{ route('client.register.store') }}" method="post" novalidate>
                            @csrf

                            @if ($errors->any())
                                <div class="mb-3 rounded-[12px] bg-red-100 px-4 py-3 text-sm text-red-700 font-almarai">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <!-- First Name -->
                            <div class="bg-white rounded-[20px] h-[60.271px] w-full flex items-center gap-3 px-6">
                                <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path
                                        d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                                        stroke="#BA112C" stroke-width="1.6" />
                                    <path
                                        d="M3 21C3 17.6863 5.68629 15 9 15H15C18.3137 15 21 17.6863 21 21"
                                        stroke="#BA112C" stroke-width="1.6" stroke-linecap="round" />
                                </svg>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                    class="w-full h-full bg-transparent outline-none border-0 font-poppins text-[#696969] text-[18px] sm:text-[20px] tracking-[0.4px] placeholder:text-[#696969]"
                                    placeholder="First Name" />
                            </div>
                            @error('first_name')
                                <p class="mt-1 pl-2 text-sm text-red-600 font-almarai">{{ $message }}</p>
                            @enderror

                            <!-- Last Name -->
                            <div class="bg-white rounded-[20px] h-[60.271px] w-full flex items-center gap-3 px-6">
                                <svg class="w-[18px] h-[18px] shrink-0" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path
                                        d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                                        stroke="#BA112C" stroke-width="1.6" />
                                    <path
                                        d="M3 21C3 17.6863 5.68629 15 9 15H15C18.3137 15 21 17.6863 21 21"
                                        stroke="#BA112C" stroke-width="1.6" stroke-linecap="round" />
                                </svg>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                    class="w-full h-full bg-transparent outline-none border-0 font-poppins text-[#696969] text-[18px] sm:text-[20px] tracking-[0.4px] placeholder:text-[#696969]"
                                    placeholder="Last Name" />
                            </div>
                            @error('last_name')
                                <p class="mt-1 pl-2 text-sm text-red-600 font-almarai">{{ $message }}</p>
                            @enderror

                            <!-- Company Name -->
                            <div class="bg-white rounded-[20px] h-[60.271px] w-full flex items-center gap-3 px-6">
                                <svg class="w-[20px] h-[20px] shrink-0" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M3 21H21" stroke="#BA112C" stroke-width="1.6" stroke-linecap="round" />
                                    <path d="M5 21V7C5 6.44772 5.44772 6 6 6H18C18.5523 6 19 6.44772 19 7V21"
                                        stroke="#BA112C" stroke-width="1.6" />
                                    <path d="M9 10H10" stroke="#BA112C" stroke-width="1.6" stroke-linecap="round" />
                                    <path d="M14 10H15" stroke="#BA112C" stroke-width="1.6" stroke-linecap="round" />
                                    <path d="M9 14H10" stroke="#BA112C" stroke-width="1.6" stroke-linecap="round" />
                                    <path d="M14 14H15" stroke="#BA112C" stroke-width="1.6" stroke-linecap="round" />
                                </svg>
                                <input type="text" name="company_name" value="{{ old('company_name') }}" required
                                    class="w-full h-full bg-transparent outline-none border-0 font-poppins text-[#696969] text-[18px] sm:text-[20px] tracking-[0.4px] placeholder:text-[#696969]"
                                    placeholder="Company Name" />
                            </div>
                            @error('company_name')
                                <p class="mt-1 pl-2 text-sm text-red-600 font-almarai">{{ $message }}</p>
                            @enderror

                            <!-- Email -->
                            <div class="bg-white rounded-[20px] h-[60.271px] w-full flex items-center gap-3 px-6">
                                <svg class="w-[22.404px] h-[17.695px] shrink-0" viewBox="0 0 24 19" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path
                                        d="M3.2 1.5H20.8C21.46 1.5 22 2.04 22 2.7V16.3C22 16.96 21.46 17.5 20.8 17.5H3.2C2.54 17.5 2 16.96 2 16.3V2.7C2 2.04 2.54 1.5 3.2 1.5Z"
                                        stroke="#BA112C" stroke-width="1.6" />
                                    <path d="M3 3.5L12 10.5L21 3.5" stroke="#BA112C" stroke-width="1.6"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required
                                    class="w-full h-full bg-transparent outline-none border-0 font-poppins text-[#696969] text-[18px] sm:text-[20px] tracking-[0.4px] placeholder:text-[#696969]"
                                    placeholder="Email" />
                            </div>
                            @error('email')
                                <p class="mt-1 pl-2 text-sm text-red-600 font-almarai">{{ $message }}</p>
                            @enderror
    
                            <!-- Password -->
                            <div class="bg-white rounded-[20px] h-[60.271px] w-full flex items-center gap-3 px-6">
                                <svg class="w-[14.369px] h-[17.563px] shrink-0" viewBox="0 0 16 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M4.5 8V6.5C4.5 4.01472 6.51472 2 9 2C11.4853 2 13.5 4.01472 13.5 6.5V8"
                                        stroke="#BA112C" stroke-width="1.6" stroke-linecap="round" />
                                    <path
                                        d="M3.2 8H14.8C15.46 8 16 8.54 16 9.2V17.3C16 18.24 15.24 19 14.3 19H3.7C2.76 19 2 18.24 2 17.3V9.2C2 8.54 2.54 8 3.2 8Z"
                                        stroke="#BA112C" stroke-width="1.6" />
                                    <path d="M9 12V15" stroke="#BA112C" stroke-width="1.6" stroke-linecap="round" />
                                </svg>
                                <input type="password" name="password" autocomplete="new-password" required
                                    class="w-full h-full bg-transparent outline-none border-0 font-poppins text-[#696969] text-[18px] sm:text-[20px] tracking-[0.4px] placeholder:text-[#696969]"
                                    placeholder="Password" />
                            </div>
                            @error('password')
                                <p class="mt-1 pl-2 text-sm text-red-600 font-almarai">{{ $message }}</p>
                            @enderror
    
                            <!-- Re-Password -->
                            <div class="bg-white rounded-[20px] h-[60.271px] w-full flex items-center gap-3 px-6">
                                <svg class="w-[14.369px] h-[17.563px] shrink-0" viewBox="0 0 16 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M4.5 8V6.5C4.5 4.01472 6.51472 2 9 2C11.4853 2 13.5 4.01472 13.5 6.5V8"
                                        stroke="#BA112C" stroke-width="1.6" stroke-linecap="round" />
                                    <path
                                        d="M3.2 8H14.8C15.46 8 16 8.54 16 9.2V17.3C16 18.24 15.24 19 14.3 19H3.7C2.76 19 2 18.24 2 17.3V9.2C2 8.54 2.54 8 3.2 8Z"
                                        stroke="#BA112C" stroke-width="1.6" />
                                    <path d="M9 12V15" stroke="#BA112C" stroke-width="1.6" stroke-linecap="round" />
                                </svg>
                                <input type="password" name="confirm_password" autocomplete="new-password" required
                                    class="w-full h-full bg-transparent outline-none border-0 font-poppins text-[#696969] text-[18px] sm:text-[20px] tracking-[0.4px] placeholder:text-[#696969]"
                                    placeholder="Confirm Password" />
                            </div>
                            @error('confirm_password')
                                <p class="mt-1 pl-2 text-sm text-red-600 font-almarai">{{ $message }}</p>
                            @enderror
    
                            <div class="pt-3">
                                <button type="submit"
                                    class="w-full h-[50px] rounded-[10px] bg-red-brand text-white font-almarai text-[20px] capitalize hover:brightness-95 active:brightness-90 transition">
                                    Sign Up
                                </button>
                            </div>
    
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
                               <a href="{{ route('google.redirect') }}"
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
</a>
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
                </div>
            </section>
        </main>
    

</x-template.layouts.index-layouts>