<header class="pc-header" id="header">

    <div class="header-wrapper flex max-sm:px-[15px] px-[25px] grow"><!-- [Mobile Media Block] start -->

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

                        <i class="search-icon absolute top-[14px] left-[15px]">

                            <svg class="pc-icon w-4 h-4">

                                <use xlink:href="#custom-search-normal-1"></use>

                            </svg>

                        </i>

                        <input type="search" class="form-control px-2.5 pr-3 pl-10 w-[198px] leading-none"

                            placeholder="Ctrl + K" />

                    </form>

                </li>

            </ul>

        </div>

        <!-- [Mobile Media Block end] -->

        <div class="ms-auto">

            <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">

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

                            <span>Dark</span>

                        </a>

                        <a href="#!" class="dropdown-item" onclick="layout_change('light')">

                            <svg class="pc-icon w-[18px] h-[18px]">

                                <use xlink:href="#custom-sun-1"></use>

                            </svg>

                            <span>Light</span>

                        </a>

                        <a href="#!" class="dropdown-item" onclick="layout_change_default()">

                            <svg class="pc-icon w-[18px] h-[18px]">

                                <use xlink:href="#custom-setting-2"></use>

                            </svg>

                            <span>Default</span>

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

                        <button class="dropdown-item" data-value="false" onclick="layout_rtl_change('false');">

                            <img src="{{asset('assets/dashboard/images/customizer/ltr.svg')}}" alt="img" class="img-fluid" width="30px" />

                            <span>ltr</span>

                        </button>

                        <button class="dropdown-item" data-value="true" onclick="layout_rtl_change('true');">

                            <img src="{{asset('assets/dashboard/images/customizer/rtl.svg')}}" alt="img" class="img-fluid" width="30px" />



                            <span>rtl</span>

                        </button>

                    </div>

                </li>

                <li class="dropdown pc-h-item header-user-profile">

                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-pc-toggle="dropdown" href="#"

                        role="button" aria-haspopup="false" data-pc-auto-close="outside" aria-expanded="false">

                        <x-dashboard.avatar :name="Auth::user()->name" size="40"

                            class="user-avtar w-10 h-10" />

                    </a>

                    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown p-2">

                        <div class="dropdown-header flex items-center justify-between py-4 px-5">

                            <h5 class="m-0">Profile</h5>

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

                                                Notification

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

                                <p class="text-span mb-3">Manage</p>

                                <a href="#" class="dropdown-item">

                                    <span>

                                        <svg class="pc-icon text-muted me-2 inline-block">

                                            <use xlink:href="#custom-setting-outline"></use>

                                        </svg>

                                        <span>Settings</span>

                                    </span>

                                </a>

                                <a href="#" class="dropdown-item">

                                    <span>

                                        <svg class="pc-icon text-muted me-2 inline-block">

                                            <use xlink:href="#custom-share-bold"></use>

                                        </svg>

                                        <span>Share</span>

                                    </span>

                                </a>

                                <a href="#" class="dropdown-item">

                                    <span>

                                        <svg class="pc-icon text-muted me-2 inline-block">

                                            <use xlink:href="#custom-lock-outline"></use>

                                        </svg>

                                        <span>Change Password</span>

                                    </span>

                                </a>

                                <hr class="border-secondary-500/10 my-4" />

                                <div class="grid mb-3">

                                    <form action="{{ route('client.logout') }}" method="post">

                                        @csrf

                                        <button class="btn btn-primary flex items-center justify-center">

                                            <svg class="pc-icon me-2 w-[22px] h-[22px]">

                                                <use xlink:href="#custom-logout-1-outline"></use>

                                            </svg>

                                            Logout

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

