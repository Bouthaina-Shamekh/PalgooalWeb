<header class="pc-header" id="header">
    <div class="header-wrapper flex max-sm:px-[15px] px-[25px] grow">
        <!-- [Mobile Media Block] start -->
        <div class="me-auto pc-mob-drp">
            <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
                <!-- ======= Menu collapse Icon ===== -->
                <li class="pc-h-item pc-sidebar-collapse max-lg:hidden lg:inline-flex">
                    <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="sidebar-hide">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>
                <li class="pc-h-item pc-sidebar-popup lg:hidden">
                    <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="mobile-collapse">
                        <i class="ti ti-menu-2 text-2xl leading-none"></i>
                    </a>
                </li>
                <li class="pc-h-item max-md:hidden md:inline-flex">
                    <form class="form-search relative">
                        <i class="search-icon absolute top-2 left-3 text-muted">
                            <svg class="pc-icon w-4 h-4">
                                <use xlink:href="#custom-search-normal-1"></use>
                            </svg>
                        </i>
                        <input type="search" class="form-control px-3 pr-3 pl-10 w-[220px] leading-none rounded-md border border-secondary-200"
                            placeholder="ابحث ... (Ctrl + K)" />
                    </form>
                </li>
            </ul>
        </div>
        <!-- [Mobile Media Block end] -->
        <div class="ms-auto">
            <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
                <li class="nav-item ">
                    <a class="nav-link" rel="alternate" hreflang="" href="">
                        <img width="20" src="" alt="">
                    </a>
                </li>
                <li class="dropdown nav-item">
                    <x-lang.language-switcher-dashboard />
                </li>
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" aria-expanded="false">
                        <svg class="pc-icon">
                            <use xlink:href="#custom-sun-1"></use>
                        </svg>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <a href="#!" class="dropdown-item" onclick="layout_change('dark')">
                            <svg class="pc-icon w-[18px] h-[18px]">
                                <use xlink:href="#custom-moon"></use>
                            </svg>
                            <span>{{__('Dark')}}</span>
                        </a>
                        <a href="#!" class="dropdown-item" onclick="layout_change('light')">
                            <svg class="pc-icon w-[18px] h-[18px]">
                                <use xlink:href="#custom-sun-1"></use>
                            </svg>
                            <span>{{__("Light")}}</span>
                        </a>
                        <a href="#!" class="dropdown-item" onclick="layout_change_default()">
                            <svg class="pc-icon w-[18px] h-[18px]">
                                <use xlink:href="#custom-setting-2"></use>
                            </svg>
                            <span>{{__('Default')}}</span>
                        </a>
                    </div>
                </li>
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" aria-expanded="false">
                        <svg class="pc-icon">
                            <use xlink:href="#custom-setting-2"></use>
                        </svg>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        @foreach(available_locales() as $lang)
                        <a href="{{ route('change_locale', ['locale' => $lang->code]) }}" class="dropdown-item" data-value="false" onclick="layout_rtl_change('false');">
                            <img src="{{asset('assets-dashboard/images/customizer/ltr.svg')}}" alt="img" class="img-fluid" width="30px" />
                            {{ $lang->native }}
                        </a>
                        @endforeach
                    </div>
                </li>
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle me-0 relative" data-pc-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" aria-expanded="false">
                        <svg class="pc-icon">
                            <use xlink:href="#custom-notification"></use>
                        </svg>
                        <span id="notifications_count" class="badge bg-danger text-white rounded-full z-10 absolute -right-2 -top-1 text-[10px] px-1">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-notification dropdown-menu-end pc-h-dropdown p-2">
                        <div class="dropdown-header flex items-center justify-between py-4 px-5">
                            <h5 class="m-0">{{__('Notifications')}}</h5>
                        </div>
                        <div id="unread" class="dropdown-body header-notification-scroll relative py-4 px-5"
                            style="max-height: calc(100vh - 215px)">

                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="flex gap-4">
                                            <div class="shrink-0">

                                                    <svg class="pc-icon text-primary w-[22px] h-[22px]">
                                                        <use xlink:href="#custom-sms"></use>
                                                    </svg>

                                                    <svg class="pc-icon text-primary w-[22px] h-[22px]">
                                                        <use xlink:href="#custom-document-text"></use>
                                                    </svg>

                                            </div>
                                            <span>

                                            </span>

                                                <div class="grow">
                                                    <span class="float-end text-sm text-muted"></span>
                                                    <h5 class="text-body mb-2"></h5>

                                                </div>

                                                <div class="grow">
                                                    <span class="float-end text-sm text-muted"></span>
                                                    <h5 class="text-body mb-2"></h5>



                                                </div>

                                                <div class="grow">
                                                    <span class="float-end text-sm text-muted"></span>
                                                    <h5 class="text-body mb-2"</h5>


                                                </div>

                                        </div>
                                    </div>
                                    {{-- <a href="" class="stretched-link"></a> --}}
                                    {{-- <a href="" class="stretched-link"></a> --}}

                                    {{-- <a href="" class="stretched-link"></a> --}}
                                    <a href="" class="stretched-link"></a>
                                </div>

                        </div>

                    </div>
                </li>
                <li class="dropdown pc-h-item header-user-profile">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-pc-toggle="dropdown" href="#"
                        role="button" aria-haspopup="false" data-pc-auto-close="outside" aria-expanded="false">
                        <x-dashboard.avatar :name="Auth::user()->name" size="40" class="user-avtar w-10 h-10" />
                    </a>
                    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown p-2">
                        <div class="dropdown-header flex items-center justify-between py-4 px-5">
                            <h5 class="m-0">{{__('Profile')}}</h5>
                        </div>
                        <div class="dropdown-body py-4 px-5">
                            <div class="profile-notification-scroll position-relative"
                                style="max-height: calc(100vh - 225px)">
                                <div class="flex mb-1 items-center">
                                    <div class="shrink-0">
                                        <x-dashboard.avatar :name="Auth::user()->name" size="40" class="w-10" />
                                    </div>
                                    <div class="grow ms-3">
                                        <h6 class="mb-1">{{ Auth::user()->name }}</h6>
                                        <span>{{ Auth::user()->email }}</span>
                                    </div>
                                </div>
                                <hr class="border-secondary-500/10 my-4" />
                                <div class="card">
                                    <div class="card-body !py-4">
                                        <div class="flex items-center justify-between">
                                            <h5 class="mb-0 inline-flex items-center">
                                                <svg class="pc-icon text-muted me-2 w-[22px] h-[22px]">
                                                    <use xlink:href="#custom-notification-outline"></use>
                                                </svg>
                                                {{__('Notifications')}}
                                            </h5>
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="checkbox" value="" class="sr-only peer" />
                                                <div
                                                    class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-span mb-3">{{__("Manage")}}</p>
                                <a href="#" class="dropdown-item">
                                    <span>
                                        <svg class="pc-icon text-muted me-2 inline-block">
                                            <use xlink:href="#custom-setting-outline"></use>
                                        </svg>
                                        <span>{{__('Settings')}}</span>
                                    </span>
                                </a>
                                <hr class="border-secondary-500/10 my-4" />
                                <div class="grid mb-3">
                                    <form action="{{ route('logout') }}" method="post">
                                        @csrf
                                        <button class="btn btn-primary flex items-center justify-center">
                                            <svg class="pc-icon me-2 w-[22px] h-[22px]">
                                                <use xlink:href="#custom-logout-1-outline"></use>
                                            </svg>
                                            {{__('Logout')}}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>






