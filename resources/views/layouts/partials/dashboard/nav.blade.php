<nav class="pc-sidebar">
    <div class="navbar-wrapper">
        <div class="m-header flex items-center py-4 px-6 h-header-height">
            <a href="../dashboard/index.html" class="b-brand flex items-center gap-3">
                <!-- ========   Change your logo from here   ============ -->
                <img src="{{asset('assets-dashboard/images/logo-dark.svg')}}" class="img-fluid logo-lg" alt="logo"/>
            </a>
        </div>
        <div class="navbar-content h-[calc(100vh_-_74px)] py-2.5">
            <div class="card pc-user-card mx-[15px] mb-[15px] bg-theme-sidebaruserbg dark:bg-themedark-sidebaruserbg">
                <div class="card-body !p-5">
                    <div class="flex items-center">
                        <img class="shrink-0 w-[45px] h-[45px] rounded-full" src="https://ui-avatars.com/api/?name={{ Auth::user()->name }}"
                            alt="user-image" />
                        <div class="ml-4 mr-2 grow">
                            <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                            <small>{{ Auth::user()->email }}</small>
                        </div>
                        <a class="shrink-0 btn btn-icon inline-flex btn-link-secondary" data-pc-toggle="collapse"
                            href="#pc_sidebar_userlink">
                            <svg class="pc-icon w-[22px] h-[22px]">
                                <use xlink:href="#custom-sort-outline"></use>
                            </svg>
                        </a>
                    </div>
                    <div class="hidden pc-user-links" id="pc_sidebar_userlink">
                        <div class="pt-3 *:flex *:items-center *:py-2 *:gap-2.5 hover:*:text-primary-500">
                            <a href="{{route('dashboard.users.profile', Auth::user()->id)}}">
                                <i class="text-lg leading-none ti ti-user"></i>
                                <span>{{ t('dashboard.My_Account', 'My Account' )}}</span>
                            </a>
                            <a href="#!">
                                <i class="text-lg leading-none ti ti-settings"></i>
                                <span>{{ t('dashboard.Settings', 'Settings') }}</span>
                            </a>
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <button type="submit" style="display: flex; align-items: center; gap: 5px;">
                                    <i class="text-lg leading-none ti ti-power"></i>
                                    <span>{{ t('dashboard.Logout', 'Logout')}}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <ul class="pc-navbar">
                <li class="pc-item pc-caption">
                    <label>{{ t('dashboard.Navigation', 'Navigation') }}</label>
                </li>
                <li class="pc-item">
                    <a href="{{route('dashboard.home')}}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-home"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.Home', 'Home') }}</span>
                    </a>
                </li>
                <li class="pc-item">
                    <a href="{{route('dashboard.pages')}}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-home"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.Pages', 'Pages') }}</span>
                    </a>
                </li>
                                <li class="pc-item">
                    <a href="{{route('dashboard.headers')}}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-home"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.Menus', 'Menus') }}</span>
                    </a>
                </li>
                <li class="pc-item">
                    <a href="{{route('dashboard.services')}}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-home"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.services', 'services') }}</span>
                    </a>
                </li>
                <li class="pc-item">
                    <a href="{{route('dashboard.feedbacks')}}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-star"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.feedbacks', 'feedbacks') }}</span>
                    </a>
                </li>
                <li class="pc-item">
                    <a href="{{route('dashboard.portfolios')}}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-home"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.portfolios', 'portfolios') }}</span>
                    </a>
                </li>
                <li class="pc-item">
                    <a href="{{route('dashboard.clients')}}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-users"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.clients', 'clients') }}</span>
                    </a>
                </li>
                <li class="pc-item pc-caption">
                    <label>{{ t('dashboard.Widget','Widget') }}</label>
                    <svg class="pc-icon">
                        <use xlink:href="#custom-presentation-chart"></use>
                    </svg>
                </li>
                <!-- @can('view', 'App\Models\User') -->
                <li class="pc-item pc-hasmenu">
                    <a href="#!" class="pc-link">
                        <span class="pc-micon">
                            <i class="fas fa-users"></i>
                        </span>
                        <span class="pc-mtext">
                            {{__('Users')}}
                        </span>
                        @if (App::getLocale() == 'en')
                        <span class="pc-arrow"><i data-feather="chevron-right"></i></span>
                        @else
                        <span class="pc-arrow"><i data-feather="chevron-left"></i></span>
                        @endif
                    </a>
                    <ul class="pc-submenu">
                        <li class="pc-item">
                            <a class="pc-link" href="{{route('dashboard.users.index')}}">
                                {{ t('dashboard.Users_show', 'Users show') }}
                            </a>
                        </li>
                        <li class="pc-item">
                            <a class="pc-link" href="{{route('dashboard.users.create')}}">
                                {{ t('dashboard.Add_User', 'Add User')}}
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="pc-item">
                    <a href="{{route('dashboard.languages.index')}}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-home"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.languages', 'languages')}}</span>

                    </a>
                </li>
                <li class="pc-item">
                    <a href="{{route('dashboard.translation-values.index')}}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-home"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.translation_values', 'translation_values')}}</span>


                    </a>
                </li>
                <li class="pc-item">
                    <a href="{{route('dashboard.media')}}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-home"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.media', 'Media')}}</span>
                    </a>
                </li>
                <li class="pc-item">
                    <a href="{{route('dashboard.general_settings')}}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-cog"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.General_Setting', 'General Setting') }}</span>
                    </a>
                </li>
                <!-- @endcan -->

            </ul>
        </div>
    </div>
</nav>
